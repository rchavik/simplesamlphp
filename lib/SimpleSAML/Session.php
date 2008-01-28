<?php
/**
 * The Session class holds information about a user session, and everything attached to it.
 *
 * The session will have a duration, and validity, and also cache information about the different
 * federation protocols, as Shibboleth and SAML 2.0. On the IdP side the Session class holds 
 * information about all the currently logged in SPs. This is used when the user initiate a 
 * Single-Log-Out.
 *
 * @author Andreas �kre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @version $Id$
 */


require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/SessionHandler.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

/**
 * A class representing a session.
 */
class SimpleSAML_Session {

	const STATE_ONLINE = 1;
	const STATE_LOGOUTINPROGRESS = 2;
	const STATE_LOGGEDOUT = 3;

	/**
	 * This variable holds the instance of the session - Singleton approach.
	 */
	private static $instance = null;
	
	/**
	 * The track id is a new random unique identifier that is generate for each session.
	 * This is used in the debug logs and error messages to easily track more information
	 * about what went wrong.
	 */
	private $trackid = 0;

	
	private $configuration = null;
	
	private $authnrequests = array();
	private $shibauthreq = null;
	
	private $authnresponse = null;
	private $idp = null;
	
	private $logoutrequest = null;
	
	private $authenticated = null;
	private $protocol = null;
	private $attributes = null;
	
	
	private $sessionindex = null;
	private $nameid = null;
	private $nameidformat = null;
	
	private $sp_at_idpsessions = array();
	
	// Session duration parameters
	private $sessionstarted = null;
	private $sessionduration = null;
	
	private $dirty = false;
	

	/**
	 * private constructor restricts instantiaton to getInstance()
	 */
	private function __construct($protocol, SimpleSAML_XML_AuthnResponse $message = null, $authenticated = true) {

		$this->configuration = SimpleSAML_Configuration::getInstance();

		$this->protocol = $protocol;
		$this->authnresponse = $message;
		
		
		$this->authenticated = $authenticated;
		if ($authenticated) {
			$this->sessionstarted = time();
		}
		
		$this->sessionduration = $this->configuration->getValue('session.duration');
		
		$this->trackid = SimpleSAML_Utilities::generateTrackID();
	}
	
	
	
	public static function getInstance($allowcreate = false) {

		/* Check if we already have initialized the session. */
		if (isset(self::$instance)) {
			return self::$instance;
		}


		/* Check if we have stored a session stored with the session
		 * handler.
		 */
		$sh = SimpleSAML_SessionHandler::getSessionHandler();
		if($sh->get('SimpleSAMLphp_SESSION') !== NULL) {
			self::$instance = $sh->get('SimpleSAMLphp_SESSION');
			self::$instance->dirty = false;
			return self::$instance;
		}

		/* We don't have a session. Create one if allowed to. Return
		 * null if not.
		 */
		if ($allowcreate) {
			self::init('saml2');
			return self::$instance;
		} else {
			return null;
		}
	}
	
	public static function init($protocol, $message = null, $authenticated = false) {
		
		$preinstance = self::getInstance();
		
		if (isset($preinstance)) {
			if (isset($message)) $preinstance->authnresponse = $message;
			if (isset($authenticated)) $preinstance->setAuthenticated($authenticated);
		} else {	
			self::$instance = new SimpleSAML_Session($protocol, $message, $authenticated);

			/* Save the new session with the session handler. */
			$sh = SimpleSAML_SessionHandler::getSessionHandler();
			$sh->set('SimpleSAMLphp_SESSION', self::$instance);
		}
	}
	
	
	
	
	
	
	public function getTrackID() {
		return $this->trackid;
	}
	
	public function add_sp_session($entityid) {
		$this->sp_at_idpsessions[$entityid] = self::STATE_ONLINE;
	}
	
	public function get_next_sp_logout() {
		
		if (!$this->sp_at_idpsessions) return null;
		
		foreach ($this->sp_at_idpsessions AS $entityid => $sp) {
			if ($sp == self::STATE_ONLINE) {
				$this->sp_at_idpsessions[$entityid] = self::STATE_LOGOUTINPROGRESS;
				return $entityid;
			}
		}
		return null;
	}
	
	public function get_sp_list($state = self::STATE_ONLINE) {
		
		$list = array();
		if (!$this->sp_at_idpsessions) return $list;
		
		foreach ($this->sp_at_idpsessions AS $entityid => $sp) {
			if ($sp == $state) {
				$list[] = $entityid;
			}
		}
		return $list;
	}
	

	
	public function set_sp_logout_completed($entityid) {
		$this->sp_at_idpsessions[$entityid] = self::STATE_LOGGEDOUT;
	}
	
	
	public function dump_sp_sessions() {
		foreach ($this->sp_at_idpsessions AS $entityid => $sp) {
			error_log('Dump sp sessions: ' . $entityid . ' status: ' . $sp);
		}
	}


	
	
	/**
	 * This method retrieves from session a cache of a specific Authentication Request
	 * The complete request is not stored, instead the values that will be needed later
	 * are stored in an assoc array.
	 *
	 * @param $protocol 		saml2 or shib13
	 * @param $requestid 		The request id used as a key to lookup the cache.
	 *
	 * @return Returns an assoc array of cached variables associated with the
	 * authentication request.
	 */
	public function getAuthnRequest($protocol, $requestid) {
		if (isset($this->authnrequests[$protocol])) {
			/*
			 * Traverse all cached authentication requests in this session for this user using this protocol
			 */
			foreach ($this->authnrequests[$protocol] AS $id => $cache) {
				/*
				 * If any of the cached requests is elder than the session.requestcache duration, then just
				 * simply delete it :)
				 */
				if ($cache['date'] < $this->configuration->getValue('session.requestcache', time() - (4*60*60) ))
					unset($this->authnrequests[$protocol][$id]);
			}
		}
		/*
		 * Then look if the request id that was requested exists, if so return it.
		 */
		if (isset($this->authnrequests[$protocol][$requestid])) {
			return $this->authnrequests[$protocol][$requestid];
		}

		/*
		 * Could not find requested ID. Throw an error. Could be that it is never set, or that it is deleted due to age.
		 */
		throw new Exception('Could not find cached version of authentication request with ID ' . $requestid . ' (' . $protocol . ')');
	}
	
	/**
	 * This method sets a cached assoc array to the authentication request cache storage.
	 *
	 * @param $protocol 		saml2 or shib13
	 * @param $requestid 		The request id used as a key to lookup the cache.
	 * @param $cache			The assoc array that will be stored.
	 */
	public function setAuthnRequest($protocol, $requestid, array $cache) {
		$cache['date'] = time();
		$this->authnrequests[$protocol][$requestid] = $cache;

	}
	
	
	
	public function setAuthnResponse(SimpleSAML_XML_AuthnResponse $xml) {
		$this->authnresponse = $xml;
	}
	
	public function getAuthnResposne() {
		return $this->authnresponse;
	}
	
	public function setIdP($idp) {
		$this->idp = $idp;
	}
	public function getIdP() {
		return $this->idp;
	}
	
	public function setLogoutRequest(SimpleSAML_XML_SAML20_LogoutRequest $lr) {
		$this->logoutrequest = $lr;
	}
	
	public function getLogoutRequest() {
		return $this->logoutrequest;
	}

	public function setSessionIndex($sessionindex) {
		$this->sessionindex = $sessionindex;
	}
	public function getSessionIndex() {
		return $this->sessionindex;
	}
	public function setNameID($nameid) {
		$this->nameid = $nameid;
	}
	public function getNameID() {
		return $this->nameid;
	}
	public function setNameIDformat($nameidformat) {
		$this->nameidformat = $nameidformat;
	}
	public function getNameIDformat() {
		return $this->nameidformat;
	}

	public function setAuthenticated($auth) {
		$this->authenticated = $auth;
		if ($auth) {
			$this->sessionstarted = time();
		}
	}
	
	public function setSessionDuration($duration) {
		$this->sessionduration = $duration;
	}
	
	
	/*
	 * Is the session representing an authenticated user, and is the session still alive.
	 * This function will return false after the user has timed out.
	 */

	public function isValid() {
		if (!$this->isAuthenticated()) return false;
		return $this->remainingTime() > 0;
	}
	
	/*
	 * If the user is authenticated, how much time is left of the session.
	 */
	public function remainingTime() {
		return $this->sessionduration - (time() - $this->sessionstarted);
	}

	/* 
	 * Is the user authenticated. This function does not check the session duration.
	 */
	public function isAuthenticated() {
		return $this->authenticated;
	}
	
	
	
	
	public function getProtocol() {
		return $this->protocol;
	}
	
	public function getAttributes() {
		return $this->attributes;
	}

	public function getAttribute($name) {
		return $this->attributes[$name];
	}

	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}
	
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}
	 
	 
	/**
	 * Is this session modified since loaded?
	 */
	public function isModified() {
		return $this->dirty;
	}
}

?>