<?php

/**
 * Logout endpoint handler for SAML SP authentication client.
 *
 * This endpoint handles both logout requests and logout responses.
 */

if (!array_key_exists('PATH_INFO', $_SERVER)) {
	throw new SimpleSAML_Error_BadRequest('Missing authentication source id in logout URL');
}

$sourceId = substr($_SERVER['PATH_INFO'], 1);

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}
if (!($source instanceof sspmod_saml_Auth_Source_SP)) {
	throw new SimpleSAML_Error_Exception('Source type changed?');
}

$binding = SAML2_Binding::getCurrentBinding();
$message = $binding->receive();

$idpEntityId = $message->getIssuer();
if ($idpEntityId === NULL) {
	/* Without an issuer we have no way to respond to the message. */
	throw new SimpleSAML_Error_BadRequest('Received message on logout endpoint without issuer.');
}

$spEntityId = $source->getEntityId();

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $source->getIdPMetadata($idpEntityId);
$spMetadata = $source->getMetadata();

sspmod_saml_Message::validateMessage($idpMetadata, $spMetadata, $message);

$destination = $message->getDestination();
if ($destination !== NULL && $destination !== SimpleSAML_Utilities::selfURLNoQuery()) {
	throw new SimpleSAML_Error_Exception('Destination in logout message is wrong.');
}

if ($message instanceof SAML2_LogoutResponse) {

	$relayState = $message->getRelayState();
	if ($relayState === NULL) {
		/* Somehow, our RelayState has been lost. */
		throw new SimpleSAML_Error_BadRequest('Missing RelayState in logout response.');
	}

	if (!$message->isSuccess()) {
		SimpleSAML_Logger::warning('Unsuccessful logout. Status was: ' . sspmod_saml_Message::getResponseError($message));
	}

	$state = SimpleSAML_Auth_State::loadState($relayState, 'saml:slosent');
	$state['saml:sp:LogoutStatus'] = $message->getStatus();
	SimpleSAML_Auth_Source::completeLogout($state);

} elseif ($message instanceof SAML2_LogoutRequest) {

	SimpleSAML_Logger::debug('module/saml2/sp/logout: Request from ' . $idpEntityId);
	SimpleSAML_Logger::stats('saml20-idp-SLO idpinit ' . $spEntityId . ' ' . $idpEntityId);

	if ($message->isNameIdEncrypted()) {
		try {
			$keys = sspmod_saml_Message::getDecryptionKeys($idpMetadata, $spMetadata);
		} catch (Exception $e) {
			throw new SimpleSAML_Error_Exception('Error decrypting NameID: ' . $e->getMessage());
		}

		$lastException = NULL;
		foreach ($keys as $i => $key) {
			try {
				$message->decryptNameId($key);
				SimpleSAML_Logger::debug('Decryption with key #' . $i . ' succeeded.');
				$lastException = NULL;
				break;
			} catch (Exception $e) {
				SimpleSAML_Logger::debug('Decryption with key #' . $i . ' failed with exception: ' . $e->getMessage());
				$lastException = $e;
			}
		}
		if ($lastException !== NULL) {
			throw $lastException;
		}
	}

	$nameId = $message->getNameId();
	$sessionIndexes = $message->getSessionIndexes();

	$numLoggedOut = sspmod_saml_SP_LogoutStore::logoutSessions($sourceId, $nameId, $sessionIndexes);
	if ($numLoggedOut === FALSE) {
		/* This type of logout was unsupported. Use the old method. */
		$source->handleLogout($idpEntityId);
		$numLoggedOut = count($sessionIndexes);
	}

	/* Create an send response. */
	$lr = sspmod_saml_Message::buildLogoutResponse($spMetadata, $idpMetadata);
	$lr->setRelayState($message->getRelayState());
	$lr->setInResponseTo($message->getId());

	/* We should return a partial logout if we were unable to log out of all the given session(s). */
	if ($numLoggedOut < count($sessionIndexes)) {
		$lr->setStatus(array(
			'Code' => SAML2_Const::STATUS_SUCCESS,
			'SubCode' => SAML2_Const::STATUS_PARTIAL_LOGOUT,
			'Message' => 'Logged out of ' . $numLoggedOut  . ' of ' . count($sessionIndexes) . ' sessions.'
		));
	}

	$binding->send($lr);
} else {
	throw new SimpleSAML_Error_BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
}
