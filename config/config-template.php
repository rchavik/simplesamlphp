<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * 
 */

$config = array (

	/*
	 * This option configures the base directory for this simpleSAMLphp
	 * installation. Under most circumstances this option is optional,
	 * and can be left unset.
	 *
	 * Example:
	 *  'basedir' => '/var/simplesamlphp/',
	 */
	'basedir' => NULL,


	/*
	 * Setup the following parameters to match the directory of your installation.
	 * See the user manual for more details.
	 */
	'baseurlpath'			=> 'simplesaml/',
	'templatedir'			=> 'templates/default/',
	'metadatadir'			=> 'metadata/',
	'attributenamemapdir'	=> 'attributemap/',
	
	/*
	 * If you set the debug parameter to true, all SAML messages will be visible in the
	 * browser, and require the user to click the submit button. If debug is set to false,
	 * Browser/POST SAML messages will be automaticly submitted.
	 */
	'debug'					=>	false,
	
	/*
	 * Logging.
	 * 
	 * Choose a syslog facility to use for logging.
	 * And define the minimum log level to log
	 *		LOG_ERR				No statistics, only errors
	 *		LOG_WARNING			No statistics, only warnings/errors
	 *		LOG_NOTICE			Statistics and errors 
	 *		LOG_INFO			Verbose logs
	 *		LOG_DEBUG			Full debug logs - not reccomended for production
	 */
	'logging.facility'		=> LOG_LOCAL5,
	'logging.level'			=> LOG_NOTICE,
	
	/*
	 * Enable
	 * 
	 * Which functionality in simpleSAMLphp do you want to enable. Normally you would enable only 
	 * one of the functionalities below, but in some cases you could run multiple functionalities.
	 * In example when you are setting up a federation bridge.
	 */
	'enable.saml20-sp'		=> true,
	'enable.saml20-idp'		=> false,
	'enable.shib13-sp'		=> false,
	'enable.shib13-idp'		=> false,
	'enable.openid-provider'=> false,
	
	/* 
	 * This value is the duration of the session in seconds. Make sure that the time duration of
	 * cookies both at the SP and the IdP exceeds this duration.
	 */
	'session.duration'		=>  8 * (60*60), // 8 hours.
	
	'language.available'	=> array('en', 'no'),
	'language.default'		=> 'en',
	
	/*
	 * Default IdPs. If you do not enter an idpentityid in the SSO initialization endpoints,
	 * the default IdP configured here will be used.
	 *
	 * To enable the SAML 2.0 IdP Discovery service for a SAML 2.0 SP, you need to set the
	 * default-saml20-idp to be null, like this:
	 *
	 * 		'default-saml20-idp'	=> null,
	 *
	 */
	'default-saml20-idp'	=> 'max.feide.no',
	'default-shib13-idp'	=> 'urn:mace:switch.ch:aaitest:dukono.switch.ch',
	
	/*
	 * IdP Discovery service look configuration. 
	 * Wether to display a list of idp or to display a dropdown box. For many IdP' a dropdown box  
	 * gives the best use experience.
	 * 
	 * When using dropdown box a cookie is used to highlight the previously chosen IdP in the dropdown.  
	 * This makes it easier for the user to choose the IdP
	 * 
	 * Options: [links,dropdown]
	 * 
	 */
	#'idpdisco.layout' => 'dropdown',
	'idpdisco.layout' => 'links',


	/*
	 * Meta data handler.
	 *
	 *	Options: [flatfile,saml2xmlmeta]
	 *
	 */
	'metadata.handler'		=> 'flatfile',

	
	/*
	 * LDAP configuration. This is only relevant if you use the LDAP authentication plugin.
	 */
	'auth.ldap.dnpattern'  => 'uid=%username%,dc=feide,dc=no,ou=feide,dc=uninett,dc=no',
	'auth.ldap.hostname'   => 'ldap.uninett.no',
	'auth.ldap.attributes' => 'objectclass=*',
	
	/*
	 * Radius authentication. This is only relevant if you use the Radius authentication plugin.
	 * user attributes are expected to be stored in a Vendor-Specific RADIUS string attribute and have
	 * the form aai-attribute=value
	 * vendor and vendor-attr below indicate in which RADIUS attribute the AAI attributes are in.
	 * multiple occurences of that RADIUS attribute are supported
	 */
	'auth.radius.hostname'        => 'radius.example.org',
	'auth.radius.port'            => '1812',
	'auth.radius.secret'          => 'topsecret',
	'auth.radius.URNForUsername'  => 'urn:mace:dir:attribute-def:eduPersonPrincipalName',
	'auth.radius.vendor'          => '23735',
	'auth.radius.vendor-attr'     => '4',

	
	/*
	 * These parameters are only relevant if you setup an OpenID Provider.
	 */
	'openid.userid_attributename' => 'eduPersonPrincipalName',
	'openid.delegation_prefix'    => 'https://openid.feide.no/',
	'openid.filestore'            => '/tmp/openidstore',
	

	/*
	 * This configuration option allows you to select which session handler
	 * SimpleSAMLPHP should use to store the session information. Currently
	 * we have two session handlers:
	 * - 'phpsession': The default PHP session handler.
	 * - 'memcache': Stores the session information in one or more
	 *   memcache servers by using the MemcacheStore class.
	 *
	 * The default session handler is 'phpsession'.
	 */
	'session.handler'       => 'phpsession',


	/*
	 * Configuration for the MemcacheStore class. This allows you to store
	 * multiple redudant copies of sessions on different memcache servers.
	 *
	 * 'memcache_store.servers' is an array of server groups. Every data
	 * item will be mirrored in every server group.
	 *
	 * Each server group is an array of servers. The data items will be
	 * load-balanced between all servers in each server group.
	 *
	 * Each server is an array of parameters for the server. The following
	 * options are available:
	 *  - 'hostname': This is the hostname or ip address where the
	 *    memcache server runs. This is the only required option.
	 *  - 'port': This is the port number of the memcache server. If this
	 *    option isn't set, then we will use the 'memcache.default_port'
	 *    ini setting. This is 11211 by default.
	 *  - 'weight': This sets the weight of this server in this server
	 *    group. http://php.net/manual/en/function.Memcache-addServer.php
	 *    contains more information about the weight option.
	 *  - 'timeout': The timeout for this server. By default, the timeout
	 *    is 3 seconds.
	 *
	 * Example of redudant configuration with load balancing:
	 * This configuration makes it possible to lose both servers in the
	 * a-group or both servers in the b-group without losing any sessions.
	 * Note that sessions will be lost if one server is lost from both the
	 * a-group and the b-group.
	 *
	 * 'memcache_store.servers' => array(
	 *     array(
	 *         array('hostname' => 'mc_a1'),
	 *         array('hostname' => 'mc_a2'),
	 *     ),
	 *     array(
	 *         array('hostname' => 'mc_b1'),
	 *         array('hostname' => 'mc_b2'),
	 *     ),
	 * ),
	 *
	 * Example of simple configuration with only one memcache server,
	 * running on the same computer as the web server:
	 * Note that all sessions will be lost if the memcache server crashes.
	 *
	 * 'memcache_store.servers' => array(
	 *     array(
	 *         array('hostname' => 'localhost'),
	 *     ),
	 * ),
	 *
	 */
	'memcache_store.servers' => array(
		array(
			array('hostname' => 'localhost'),
		),
	),


	/*
	 * This value is the duration data should be stored in memcache. Data
	 * will be dropped from the memcache servers when this time expires.
	 * The time will be reset every time the data is written to the
	 * memcache servers.
	 *
	 * This value should always be larger than the 'session.duration'
	 * option. Not doing this may result in the session being deleted from
	 * the memcache servers while it is still in use.
	 *
	 * Set this value to 0 if you don't want data to expire.
	 *
	 * Note: The oldest data will always be deleted if the memcache server
	 * runs out of storage space.
	 */
	'memcache_store.expires' =>  36 * (60*60), // 36 hours.



	/*
	 * This option enables or disables the login-auto authentication
	 * handler. This handler is implemented in 'www/auth/login-auto.php'.
	 *
	 * When this option is set to true, a user can go to the
	 * 'auth/login-auto.php' web page to be authenticated as an example
	 * user. The user will receive the attributes set in the
	 * 'auth.auto.attributes' option.
	 *
	 * WARNING: setting this option to true will make it possible to use
	 * this authenticator for all users, irrespectively of the 'auth'
	 * setting in the IdP's metadata. They can always use it by opening the
	 * 'auth/login-auto.php' webpage manually.
	 */
	'auth.auto.enable' => false,

	/*
	 * This option configures which attributes the login-auto
	 * authentication handler will set for the user. It is an array of
	 * arrays. The name of the attribute is the index in the first array,
	 * and all the values for the attribute is given in the array
	 * referenced to by the name.
	 *
	 * Example:
	 * 'auth.auto.attributes' => array(
	 *     'edupersonaffiliation' => array('student', 'member'),
	 *     'uid' => array('example_uid'),
	 *     'mail' => array('example@example.com'),
	 * ),
	 */
	'auth.auto.attributes' => array(
		'edupersonaffiliation' => array('student', 'member'),
		'title' => array('Example user title'),
		'uid' => array('example_uid'),
		'mail' => array('example@example.com'),
		'cn' => array('Example user commonname'),
		'givenname' => array('Example user givenname'),
		'sn' => array("Example surname"),
	),

	/*
	 * When this option is set to true, the login-auto authentication
	 * handler will ask for a username and a password. This can be used to
	 * test the IdP. The username and password isn't verified, and the
	 * user/script can enter anything.
	 */
	'auth.auto.ask_login' => false,

	/*
	 * This option configures a delay in the login-auto authentication
	 * handler. The script will wait for the given number of milliseconds
	 * before authenticating the user. This can, for example, be used in
	 * a simple simulation of a slow LDAP server.
	 */
	'auth.auto.delay_login' => 0,

);


?>