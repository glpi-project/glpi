<?php

/*
 * Copyright © 2003-2010, The ESUP-Portail consortium & the JA-SIG Collaborative.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *     * Neither the name of the ESUP-Portail consortium & the JA-SIG
 *       Collaborative nor the names of its contributors may be used to endorse or
 *       promote products derived from this software without specific prior
 *       written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @file CAS/client.php
 * Main class of the phpCAS library
 */

// include internationalization stuff
include_once(dirname(__FILE__).'/languages/languages.php');

// include PGT storage classes
include_once(dirname(__FILE__).'/PGTStorage/pgt-main.php');

// include class for storing service cookies.
include_once(dirname(__FILE__).'/CookieJar.php');

// include class for fetching web requests.
include_once(dirname(__FILE__).'/Request/CurlRequest.php');

/**
 * @class CASClient
 * The CASClient class is a client interface that provides CAS authentication
 * to PHP applications.
 *
 * @author Pascal Aubry <pascal.aubry at univ-rennes1.fr>
 */

class CASClient
{

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                          CONFIGURATION                             XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	// ########################################################################
	//  HTML OUTPUT
	// ########################################################################
	/**
	* @addtogroup internalOutput
	* @{
	*/

	/**
	 * This method filters a string by replacing special tokens by appropriate values
	 * and prints it. The corresponding tokens are taken into account:
	 * - __CAS_VERSION__
	 * - __PHPCAS_VERSION__
	 * - __SERVER_BASE_URL__
	 *
	 * Used by CASClient::PrintHTMLHeader() and CASClient::printHTMLFooter().
	 *
	 * @param $str the string to filter and output
	 */
	private function HTMLFilterOutput($str)
	{
		$str = str_replace('__CAS_VERSION__',$this->getServerVersion(),$str);
		$str = str_replace('__PHPCAS_VERSION__',phpCAS::getVersion(),$str);
		$str = str_replace('__SERVER_BASE_URL__',$this->getServerBaseURL(),$str);
		echo $str;
	}

	/**
	 * A string used to print the header of HTML pages. Written by CASClient::setHTMLHeader(),
	 * read by CASClient::printHTMLHeader().
	 *
	 * @hideinitializer
	 * @see CASClient::setHTMLHeader, CASClient::printHTMLHeader()
	 */
	private $_output_header = '';

	/**
	 * This method prints the header of the HTML output (after filtering). If
	 * CASClient::setHTMLHeader() was not used, a default header is output.
	 *
	 * @param $title the title of the page
	 *
	 * @see HTMLFilterOutput()
	 */
	private function printHTMLHeader($title)
	{
		$this->HTMLFilterOutput(str_replace('__TITLE__',
		$title,
		(empty($this->_output_header)
		? '<html><head><title>__TITLE__</title></head><body><h1>__TITLE__</h1>'
		: $this->_output_header)
		)
		);
	}

	/**
	 * A string used to print the footer of HTML pages. Written by CASClient::setHTMLFooter(),
	 * read by printHTMLFooter().
	 *
	 * @hideinitializer
	 * @see CASClient::setHTMLFooter, CASClient::printHTMLFooter()
	 */
	private $_output_footer = '';

	/**
	 * This method prints the footer of the HTML output (after filtering). If
	 * CASClient::setHTMLFooter() was not used, a default footer is output.
	 *
	 * @see HTMLFilterOutput()
	 */
	private function printHTMLFooter()
	{
		$this->HTMLFilterOutput(empty($this->_output_footer)
		?('<hr><address>phpCAS __PHPCAS_VERSION__ '.$this->getString(CAS_STR_USING_SERVER).' <a href="__SERVER_BASE_URL__">__SERVER_BASE_URL__</a> (CAS __CAS_VERSION__)</a></address></body></html>')
		:$this->_output_footer);
	}

	/**
	 * This method set the HTML header used for all outputs.
	 *
	 * @param $header the HTML header.
	 */
	public function setHTMLHeader($header)
	{
		$this->_output_header = $header;
	}

	/**
	 * This method set the HTML footer used for all outputs.
	 *
	 * @param $footer the HTML footer.
	 */
	public function setHTMLFooter($footer)
	{
		$this->_output_footer = $footer;
	}

	/**
	 * @var boolean $_exitOnAuthError; If true, phpCAS will exit on an authentication error.
	 */
	private $_exitOnAuthError = true;

	/**
	 * Configure the client to not call exit() when an authentication failure occurs.
	 *
	 * Needed for testing proper failure handling.
	 *
	 * @return void
	 */
	public function setNoExitOnAuthError () {
		$this->_exitOnAuthError = false;
	}
	
	/**
	 * @var boolean $_exitOnAuthError; If true, phpCAS will clear session tickets from the URL.
	 * After a successful authentication.
	 */
	private $_clearTicketsFromUrl = true;
	
	/**
	 * Configure the client to not send redirect headers and call exit() on authentication
	 * success. The normal redirect is used to remove the service ticket from the
	 * client's URL, but for running unit tests we need to continue without exiting.
	 *
	 * Needed for testing authentication
	 *
	 * @return void
	 */
	public function setNoClearTicketsFromUrl () {
		$this->_clearTicketsFromUrl = false;
	}
	
	/**
	 * @var callback $_postAuthenticateCallbackFunction;  
	 */
	private $_postAuthenticateCallbackFunction = null;
	
	/**
	 * @var array $_postAuthenticateCallbackArgs;  
	 */
	private $_postAuthenticateCallbackArgs = array();
	
	/**
	 * Set a callback function to be run when a user authenticates.
	 *
	 * The callback function will be passed a $logoutTicket as its first parameter,
	 * followed by any $additionalArgs you pass. The $logoutTicket parameter is an
	 * opaque string that can be used to map a session-id to the logout request in order
	 * to support single-signout in applications that manage their own sessions 
	 * (rather than letting phpCAS start the session).
	 *
	 * phpCAS::forceAuthentication() will always exit and forward client unless
	 * they are already authenticated. To perform an action at the moment the user
	 * logs in (such as registering an account, performing logging, etc), register
	 * a callback function here.
	 * 
	 * @param callback $function
	 * @param optional array $additionalArgs
	 * @return void
	 */
	public function setPostAuthenticateCallback ($function, array $additionalArgs = array()) {
		$this->_postAuthenticateCallbackFunction = $function;
		$this->_postAuthenticateCallbackArgs = $additionalArgs;
	}
	
	/**
	 * @var callback $_signoutCallbackFunction;  
	 */
	private $_signoutCallbackFunction = null;
	
	/**
	 * @var array $_signoutCallbackArgs;  
	 */
	private $_signoutCallbackArgs = array();
	
	/**
	 * Set a callback function to be run when a single-signout request is received.
	 *
	 * The callback function will be passed a $logoutTicket as its first parameter,
	 * followed by any $additionalArgs you pass. The $logoutTicket parameter is an
	 * opaque string that can be used to map a session-id to the logout request in order
	 * to support single-signout in applications that manage their own sessions 
	 * (rather than letting phpCAS start and destroy the session).
	 * 
	 * @param callback $function
	 * @param optional array $additionalArgs
	 * @return void
	 */
	public function setSingleSignoutCallback ($function, array $additionalArgs = array()) {
		$this->_signoutCallbackFunction = $function;
		$this->_signoutCallbackArgs = $additionalArgs;
	}


	/** @} */
	// ########################################################################
	//  INTERNATIONALIZATION
	// ########################################################################
	/**
	* @addtogroup internalLang
	* @{
	*/
	/**
	 * A string corresponding to the language used by phpCAS. Written by
	 * CASClient::setLang(), read by CASClient::getLang().

	 * @note debugging information is always in english (debug purposes only).
	 *
	 * @hideinitializer
	 * @sa CASClient::_strings, CASClient::getString()
	 */
	private $_lang = '';

	/**
	 * This method returns the language used by phpCAS.
	 *
	 * @return a string representing the language
	 */
	private function getLang()
	{
		if ( empty($this->_lang) )
		$this->setLang(PHPCAS_LANG_DEFAULT);
		return $this->_lang;
	}

	/**
	 * array containing the strings used by phpCAS. Written by CASClient::setLang(), read by
	 * CASClient::getString() and used by CASClient::setLang().
	 *
	 * @note This array is filled by instructions in CAS/languages/<$this->_lang>.php
	 *
	 * @see CASClient::_lang, CASClient::getString(), CASClient::setLang(), CASClient::getLang()
	 */
	private $_strings;

	/**
	 * This method returns a string depending on the language.
	 *
	 * @param $str the index of the string in $_string.
	 *
	 * @return the string corresponding to $index in $string.
	 *
	 */
	private function getString($str)
	{
		// call CASclient::getLang() to be sure the language is initialized
		$this->getLang();

		if ( !isset($this->_strings[$str]) ) {
			trigger_error('string `'.$str.'\' not defined for language `'.$this->getLang().'\'',E_USER_ERROR);
		}
		return $this->_strings[$str];
	}

	/**
	 * This method is used to set the language used by phpCAS.
	 * @note Can be called only once.
	 *
	 * @param $lang a string representing the language.
	 *
	 * @sa CAS_LANG_FRENCH, CAS_LANG_ENGLISH
	 */
	public function setLang($lang)
	{
		// include the corresponding language file
		include(dirname(__FILE__).'/languages/'.$lang.'.php');

		if ( !is_array($this->_strings) ) {
			trigger_error('language `'.$lang.'\' is not implemented',E_USER_ERROR);
		}
		$this->_lang = $lang;
	}

	/** @} */
	// ########################################################################
	//  CAS SERVER CONFIG
	// ########################################################################
	/**
	* @addtogroup internalConfig
	* @{
	*/

	/**
	 * a record to store information about the CAS server.
	 * - $_server["version"]: the version of the CAS server
	 * - $_server["hostname"]: the hostname of the CAS server
	 * - $_server["port"]: the port the CAS server is running on
	 * - $_server["uri"]: the base URI the CAS server is responding on
	 * - $_server["base_url"]: the base URL of the CAS server
	 * - $_server["login_url"]: the login URL of the CAS server
	 * - $_server["service_validate_url"]: the service validating URL of the CAS server
	 * - $_server["proxy_url"]: the proxy URL of the CAS server
	 * - $_server["proxy_validate_url"]: the proxy validating URL of the CAS server
	 * - $_server["logout_url"]: the logout URL of the CAS server
	 *
	 * $_server["version"], $_server["hostname"], $_server["port"] and $_server["uri"]
	 * are written by CASClient::CASClient(), read by CASClient::getServerVersion(),
	 * CASClient::getServerHostname(), CASClient::getServerPort() and CASClient::getServerURI().
	 *
	 * The other fields are written and read by CASClient::getServerBaseURL(),
	 * CASClient::getServerLoginURL(), CASClient::getServerServiceValidateURL(),
	 * CASClient::getServerProxyValidateURL() and CASClient::getServerLogoutURL().
	 *
	 * @hideinitializer
	 */
	private $_server = array(
		'version' => -1,
		'hostname' => 'none',
		'port' => -1,
		'uri' => 'none');

	/**
	 * This method is used to retrieve the version of the CAS server.
	 * @return the version of the CAS server.
	 */
	private function getServerVersion()
	{
		return $this->_server['version'];
	}

	/**
	 * This method is used to retrieve the hostname of the CAS server.
	 * @return the hostname of the CAS server.
	 */
	private function getServerHostname()
	{ return $this->_server['hostname']; }

	/**
	 * This method is used to retrieve the port of the CAS server.
	 * @return the port of the CAS server.
	 */
	private function getServerPort()
	{ return $this->_server['port']; }

	/**
	 * This method is used to retrieve the URI of the CAS server.
	 * @return a URI.
	 */
	private function getServerURI()
	{ return $this->_server['uri']; }

	/**
	 * This method is used to retrieve the base URL of the CAS server.
	 * @return a URL.
	 */
	private function getServerBaseURL()
	{
		// the URL is build only when needed
		if ( empty($this->_server['base_url']) ) {
			$this->_server['base_url'] = 'https://' . $this->getServerHostname();
			if ($this->getServerPort()!=443) {
				$this->_server['base_url'] .= ':'
				.$this->getServerPort();
			}
			$this->_server['base_url'] .= $this->getServerURI();
		}
		return $this->_server['base_url'];
	}

	/**
	 * This method is used to retrieve the login URL of the CAS server.
	 * @param $gateway true to check authentication, false to force it
	 * @param $renew true to force the authentication with the CAS server
	 * NOTE : It is recommended that CAS implementations ignore the
	 "gateway" parameter if "renew" is set
	 * @return a URL.
	 */
	public function getServerLoginURL($gateway=false,$renew=false) {
		phpCAS::traceBegin();
		// the URL is build only when needed
		if ( empty($this->_server['login_url']) ) {
			$this->_server['login_url'] = $this->getServerBaseURL();
			$this->_server['login_url'] .= 'login?service=';
			$this->_server['login_url'] .= urlencode($this->getURL());
			if($renew) {
				// It is recommended that when the "renew" parameter is set, its value be "true"
				$this->_server['login_url'] .= '&renew=true';
			} elseif ($gateway) {
				// It is recommended that when the "gateway" parameter is set, its value be "true"
				$this->_server['login_url'] .= '&gateway=true';
			}
		}
		phpCAS::traceEnd($this->_server['login_url']);
		return $this->_server['login_url'];
	}

	/**
	 * This method sets the login URL of the CAS server.
	 * @param $url the login URL
	 * @since 0.4.21 by Wyman Chan
	 */
	public function setServerLoginURL($url)
	{
		return $this->_server['login_url'] = $url;
	}


	/**
	 * This method sets the serviceValidate URL of the CAS server.
	 * @param $url the serviceValidate URL
	 * @since 1.1.0 by Joachim Fritschi
	 */
	public function setServerServiceValidateURL($url)
	{
		return $this->_server['service_validate_url'] = $url;
	}


	/**
	 * This method sets the proxyValidate URL of the CAS server.
	 * @param $url the proxyValidate URL
	 * @since 1.1.0 by Joachim Fritschi
	 */
	public function setServerProxyValidateURL($url)
	{
		return $this->_server['proxy_validate_url'] = $url;
	}


	/**
	 * This method sets the samlValidate URL of the CAS server.
	 * @param $url the samlValidate URL
	 * @since 1.1.0 by Joachim Fritschi
	 */
	public function setServerSamlValidateURL($url)
	{
		return $this->_server['saml_validate_url'] = $url;
	}


	/**
	 * This method is used to retrieve the service validating URL of the CAS server.
	 * @return a URL.
	 */
	public function getServerServiceValidateURL()
	{
		// the URL is build only when needed
		if ( empty($this->_server['service_validate_url']) ) {
			switch ($this->getServerVersion()) {
				case CAS_VERSION_1_0:
					$this->_server['service_validate_url'] = $this->getServerBaseURL().'validate';
					break;
				case CAS_VERSION_2_0:
					$this->_server['service_validate_url'] = $this->getServerBaseURL().'serviceValidate';
					break;
			}
		}
		return $this->_server['service_validate_url'].'?service='.urlencode($this->getURL());
	}
	/**
	 * This method is used to retrieve the SAML validating URL of the CAS server.
	 * @return a URL.
	 */
	public function getServerSamlValidateURL()
	{
		phpCAS::traceBegin();
		// the URL is build only when needed
		if ( empty($this->_server['saml_validate_url']) ) {
			switch ($this->getServerVersion()) {
				case SAML_VERSION_1_1:
					$this->_server['saml_validate_url'] = $this->getServerBaseURL().'samlValidate';
					break;
			}
		}
		phpCAS::traceEnd($this->_server['saml_validate_url'].'?TARGET='.urlencode($this->getURL()));
		return $this->_server['saml_validate_url'].'?TARGET='.urlencode($this->getURL());
	}
	/**
	 * This method is used to retrieve the proxy validating URL of the CAS server.
	 * @return a URL.
	 */
	public function getServerProxyValidateURL()
	{
		// the URL is build only when needed
		if ( empty($this->_server['proxy_validate_url']) ) {
			switch ($this->getServerVersion()) {
				case CAS_VERSION_1_0:
					$this->_server['proxy_validate_url'] = '';
					break;
				case CAS_VERSION_2_0:
					$this->_server['proxy_validate_url'] = $this->getServerBaseURL().'proxyValidate';
					break;
			}
		}
		return $this->_server['proxy_validate_url'].'?service='.urlencode($this->getURL());
	}

	/**
	 * This method is used to retrieve the proxy URL of the CAS server.
	 * @return a URL.
	 */
	public function getServerProxyURL()
	{
		// the URL is build only when needed
		if ( empty($this->_server['proxy_url']) ) {
			switch ($this->getServerVersion()) {
				case CAS_VERSION_1_0:
					$this->_server['proxy_url'] = '';
					break;
				case CAS_VERSION_2_0:
					$this->_server['proxy_url'] = $this->getServerBaseURL().'proxy';
					break;
			}
		}
		return $this->_server['proxy_url'];
	}

	/**
	 * This method is used to retrieve the logout URL of the CAS server.
	 * @return a URL.
	 */
	public function getServerLogoutURL()
	{
		// the URL is build only when needed
		if ( empty($this->_server['logout_url']) ) {
			$this->_server['logout_url'] = $this->getServerBaseURL().'logout';
		}
		return $this->_server['logout_url'];
	}

	/**
	 * This method sets the logout URL of the CAS server.
	 * @param $url the logout URL
	 * @since 0.4.21 by Wyman Chan
	 */
	public function setServerLogoutURL($url)
	{
		return $this->_server['logout_url'] = $url;
	}

	/**
	 * An array to store extra curl options.
	 */
	private $_curl_options = array();

	/**
	 * This method is used to set additional user curl options.
	 */
	public function setExtraCurlOption($key, $value)
	{
		$this->_curl_options[$key] = $value;
	}

	/**
	 * The class to instantiate for making web requests in readUrl().
	 * The class specified must implement the CAS_RequestInterface.
	 * By default CAS_CurlRequest is used, but this may be overridden to
	 * supply alternate request mechanisms for testing.
	 */
	private $_requestImplementation = 'CAS_CurlRequest';

	/**
	 * Override the default implementation used to make web requests in readUrl().
	 * This class must implement the CAS_RequestInterface.
	 *
	 * @param string $className
	 * @return void
	 */
	public function setRequestImplementation ($className) {
		$obj = new $className;
		if (!($obj instanceof CAS_RequestInterface))
		throw new InvalidArgumentException('$className must implement the CAS_RequestInterface');

		$this->_requestImplementation = $className;
	}

	/**
	 * This method checks to see if the request is secured via HTTPS
	 * @return true if https, false otherwise
	 */
	private function isHttps() {
		if ( isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			return true;
		} else {
			return false;
		}
	}

	// ########################################################################
	//  CONSTRUCTOR
	// ########################################################################
	/**
	* CASClient constructor.
	*
	* @param $server_version the version of the CAS server
	* @param $proxy TRUE if the CAS client is a CAS proxy, FALSE otherwise
	* @param $server_hostname the hostname of the CAS server
	* @param $server_port the port the CAS server is running on
	* @param $server_uri the URI the CAS server is responding on
	* @param $start_session Have phpCAS start PHP sessions (default true)
	*
	* @return a newly created CASClient object
	*/
	public function CASClient(
	$server_version,
	$proxy,
	$server_hostname,
	$server_port,
	$server_uri,
	$start_session = true) {

		phpCAS::traceBegin();

		$this->_start_session = $start_session;

		if ($this->_start_session && session_id() !== "")
		{
			phpCAS :: error("Another session was started before phpcas. Either disable the session" .
				" handling for phpcas in the client() call or modify your application to leave" .
				" session handling to phpcas");			
		}
		// skip Session Handling for logout requests and if don't want it'
		if ($start_session && !$this->isLogoutRequest())
		{
			phpCAS :: trace("Starting a new session");
			session_start();
		}


		// are we in proxy mode ?
		$this->_proxy = $proxy;

		// Make cookie handling available.
		if ($this->isProxy()) {
			if (!isset($_SESSION['phpCAS']))
			$_SESSION['phpCAS'] = array();
			if (!isset($_SESSION['phpCAS']['service_cookies']))
			$_SESSION['phpCAS']['service_cookies'] = array();
			$this->_serviceCookieJar = new CAS_CookieJar($_SESSION['phpCAS']['service_cookies']);
		}

		//check version
		switch ($server_version) {
			case CAS_VERSION_1_0:
				if ( $this->isProxy() )
				phpCAS::error('CAS proxies are not supported in CAS '
				.$server_version);
				break;
			case CAS_VERSION_2_0:
				break;
			case SAML_VERSION_1_1:
				break;
			default:
				phpCAS::error('this version of CAS (`'
				.$server_version
				.'\') is not supported by phpCAS '
				.phpCAS::getVersion());
		}
		$this->_server['version'] = $server_version;

		// check hostname
		if ( empty($server_hostname)
		|| !preg_match('/[\.\d\-abcdefghijklmnopqrstuvwxyz]*/',$server_hostname) ) {
			phpCAS::error('bad CAS server hostname (`'.$server_hostname.'\')');
		}
		$this->_server['hostname'] = $server_hostname;

		// check port
		if ( $server_port == 0
		|| !is_int($server_port) ) {
			phpCAS::error('bad CAS server port (`'.$server_hostname.'\')');
		}
		$this->_server['port'] = $server_port;

		// check URI
		if ( !preg_match('/[\.\d\-_abcdefghijklmnopqrstuvwxyz\/]*/',$server_uri) ) {
			phpCAS::error('bad CAS server URI (`'.$server_uri.'\')');
		}
		// add leading and trailing `/' and remove doubles
		$server_uri = preg_replace('/\/\//','/','/'.$server_uri.'/');
		$this->_server['uri'] = $server_uri;

		// set to callback mode if PgtIou and PgtId CGI GET parameters are provided
		if ( $this->isProxy() ) {
			$this->setCallbackMode(!empty($_GET['pgtIou'])&&!empty($_GET['pgtId']));
		}

		if ( $this->isCallbackMode() ) {
			//callback mode: check that phpCAS is secured
			if ( !$this->isHttps() ) {
				phpCAS::error('CAS proxies must be secured to use phpCAS; PGT\'s will not be received from the CAS server');
			}
		} else {
			//normal mode: get ticket and remove it from CGI parameters for developpers
			$ticket = (isset($_GET['ticket']) ? $_GET['ticket'] : null);
			switch ($this->getServerVersion()) {
				case CAS_VERSION_1_0: // check for a Service Ticket
					if( preg_match('/^ST-/',$ticket) ) {
						phpCAS::trace('ST \''.$ticket.'\' found');
						//ST present
						$this->setST($ticket);
						//ticket has been taken into account, unset it to hide it to applications
						unset($_GET['ticket']);
					} else if ( !empty($ticket) ) {
						//ill-formed ticket, halt
						phpCAS::error('ill-formed ticket found in the URL (ticket=`'.htmlentities($ticket).'\')');
					}
					break;
				case CAS_VERSION_2_0: // check for a Service or Proxy Ticket
					if( preg_match('/^[SP]T-/',$ticket) ) {
						phpCAS::trace('ST or PT \''.$ticket.'\' found');
						$this->setPT($ticket);
						unset($_GET['ticket']);
					} else if ( !empty($ticket) ) {
						//ill-formed ticket, halt
						phpCAS::error('ill-formed ticket found in the URL (ticket=`'.htmlentities($ticket).'\')');
					}
					break;
				case SAML_VERSION_1_1: // SAML just does Service Tickets
					if( preg_match('/^[SP]T-/',$ticket) ) {
						phpCAS::trace('SA \''.$ticket.'\' found');
						$this->setSA($ticket);
						unset($_GET['ticket']);
					} else if ( !empty($ticket) ) {
						//ill-formed ticket, halt
						phpCAS::error('ill-formed ticket found in the URL (ticket=`'.htmlentities($ticket).'\')');
					}
					break;
			}
		}
		phpCAS::traceEnd();
	}

	/** @} */

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                           Session Handling                         XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	/**
	 * A variable to whether phpcas will use its own session handling. Default = true
	 * @hideinitializer
	 */
	private $_start_session = true;

	private function setStartSession($session)
	{
		$this->_start_session = session;
	}

	public function getStartSession($session)
	{
		$this->_start_session = session;
	}

	/**
	 * Renaming the session
	 */
	private function renameSession($ticket)
	{
		phpCAS::traceBegin();
		if($this->_start_session){
			if (!empty ($this->_user))
			{
				$old_session = $_SESSION;
				session_destroy();
				// set up a new session, of name based on the ticket
				$session_id = preg_replace('/[^\w]/', '', $ticket);
				phpCAS :: trace("Session ID: ".$session_id);
				session_id($session_id);
				session_start();
				phpCAS :: trace("Restoring old session vars");
				$_SESSION = $old_session;
			} else
			{
				phpCAS :: error('Session should only be renamed after successfull authentication');
			}
		}else{
			phpCAS :: trace("Skipping session rename since phpCAS is not handling the session.");
		}
		phpCAS::traceEnd();
	}

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                           AUTHENTICATION                           XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	/**
	 * @addtogroup internalAuthentication
	 * @{
	 */

	/**
	 * The Authenticated user. Written by CASClient::setUser(), read by CASClient::getUser().
	 * @attention client applications should use phpCAS::getUser().
	 *
	 * @hideinitializer
	 */
	private $_user = '';

	/**
	 * This method sets the CAS user's login name.
	 *
	 * @param $user the login name of the authenticated user.
	 *
	 */
	private function setUser($user)
	{
		$this->_user = $user;
	}

	/**
	 * This method returns the CAS user's login name.
	 * @warning should be called only after CASClient::forceAuthentication() or
	 * CASClient::isAuthenticated(), otherwise halt with an error.
	 *
	 * @return the login name of the authenticated user
	 */
	public function getUser()
	{
		if ( empty($this->_user) ) {
			phpCAS::error('this method should be used only after '.__CLASS__.'::forceAuthentication() or '.__CLASS__.'::isAuthenticated()');
		}
		return $this->_user;
	}



	/***********************************************************************************************************************
	 * Atrributes section
	 *
	 * @author Matthias Crauwels <matthias.crauwels@ugent.be>, Ghent University, Belgium
	 *
	 ***********************************************************************************************************************/
	/**
	 * The Authenticated users attributes. Written by CASClient::setAttributes(), read by CASClient::getAttributes().
	 * @attention client applications should use phpCAS::getAttributes().
	 *
	 * @hideinitializer
	 */
	private $_attributes = array();

	public function setAttributes($attributes)
	{ $this->_attributes = $attributes; }

	public function getAttributes() {
		if ( empty($this->_user) ) { // if no user is set, there shouldn't be any attributes also...
			phpCAS::error('this method should be used only after '.__CLASS__.'::forceAuthentication() or '.__CLASS__.'::isAuthenticated()');
		}
		return $this->_attributes;
	}

	public function hasAttributes()
	{ return !empty($this->_attributes); }

	public function hasAttribute($key)
	{ return (is_array($this->_attributes) && array_key_exists($key, $this->_attributes)); }

	public function getAttribute($key)	{
		if($this->hasAttribute($key)) {
			return $this->_attributes[$key];
		}
	}

	/**
	 * This method is called to renew the authentication of the user
	 * If the user is authenticated, renew the connection
	 * If not, redirect to CAS
	 */
	public function renewAuthentication(){
		phpCAS::traceBegin();
		// Either way, the user is authenticated by CAS
		if( isset( $_SESSION['phpCAS']['auth_checked'] ) )
		unset($_SESSION['phpCAS']['auth_checked']);
		if ( $this->isAuthenticated() ) {
			phpCAS::trace('user already authenticated; renew');
			$this->redirectToCas(false,true);
		} else {
			$this->redirectToCas();
		}
		phpCAS::traceEnd();
	}

	/**
	 * This method is called to be sure that the user is authenticated. When not
	 * authenticated, halt by redirecting to the CAS server; otherwise return TRUE.
	 * @return TRUE when the user is authenticated; otherwise halt.
	 */
	public function forceAuthentication()
	{
		phpCAS::traceBegin();

		if ( $this->isAuthenticated() ) {
			// the user is authenticated, nothing to be done.
			phpCAS::trace('no need to authenticate');
			$res = TRUE;
		} else {
			// the user is not authenticated, redirect to the CAS server
			if (isset($_SESSION['phpCAS']['auth_checked'])) {
				unset($_SESSION['phpCAS']['auth_checked']);
			}
			$this->redirectToCas(FALSE/* no gateway */);
			// never reached
			$res = FALSE;
		}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * An integer that gives the number of times authentication will be cached before rechecked.
	 *
	 * @hideinitializer
	 */
	private $_cache_times_for_auth_recheck = 0;

	/**
	 * Set the number of times authentication will be cached before rechecked.
	 *
	 * @param $n an integer.
	 */
	public function setCacheTimesForAuthRecheck($n)
	{
		$this->_cache_times_for_auth_recheck = $n;
	}

	/**
	 * This method is called to check whether the user is authenticated or not.
	 * @return TRUE when the user is authenticated, FALSE otherwise.
	 */
	public function checkAuthentication()
	{
		phpCAS::traceBegin();

		if ( $this->isAuthenticated() ) {
			phpCAS::trace('user is authenticated');
			$res = TRUE;
		} else if (isset($_SESSION['phpCAS']['auth_checked'])) {
			// the previous request has redirected the client to the CAS server with gateway=true
			unset($_SESSION['phpCAS']['auth_checked']);
			$res = FALSE;
		} else {
			// avoid a check against CAS on every request
			if (! isset($_SESSION['phpCAS']['unauth_count']) )
			$_SESSION['phpCAS']['unauth_count'] = -2; // uninitialized
				
			if (($_SESSION['phpCAS']['unauth_count'] != -2 && $this->_cache_times_for_auth_recheck == -1)
			|| ($_SESSION['phpCAS']['unauth_count'] >= 0 && $_SESSION['phpCAS']['unauth_count'] < $this->_cache_times_for_auth_recheck))
			{
				$res = FALSE;

				if ($this->_cache_times_for_auth_recheck != -1)
				{
					$_SESSION['phpCAS']['unauth_count']++;
					phpCAS::trace('user is not authenticated (cached for '.$_SESSION['phpCAS']['unauth_count'].' times of '.$this->_cache_times_for_auth_recheck.')');
				}
				else
				{
					phpCAS::trace('user is not authenticated (cached for until login pressed)');
				}
			}
			else
			{
				$_SESSION['phpCAS']['unauth_count'] = 0;
				$_SESSION['phpCAS']['auth_checked'] = true;
				phpCAS::trace('user is not authenticated (cache reset)');
				$this->redirectToCas(TRUE/* gateway */);
				// never reached
				$res = FALSE;
			}
		}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method is called to check if the user is authenticated (previously or by
	 * tickets given in the URL).
	 *
	 * @return TRUE when the user is authenticated. Also may redirect to the same URL without the ticket.
	 */
	public function isAuthenticated()
	{
		phpCAS::traceBegin();
		$res = FALSE;
		$validate_url = '';

		if ( $this->wasPreviouslyAuthenticated() ) {
			if($this->hasST() || $this->hasPT() || $this->hasSA()){
				// User has a additional ticket but was already authenticated
				phpCAS::trace('ticket was present and will be discarded, use renewAuthenticate()');
				header('Location: '.$this->getURL());
				phpCAS::trace( "Prepare redirect to remove ticket: ".$this->getURL() );
				phpCAS::traceExit();
				exit();
			}else{
				// the user has already (previously during the session) been
				// authenticated, nothing to be done.
				phpCAS::trace('user was already authenticated, no need to look for tickets');
				$res = TRUE;
			}
		}
		else {
			if ( $this->hasST() ) {
				// if a Service Ticket was given, validate it
				phpCAS::trace('ST `'.$this->getST().'\' is present');
				$this->validateST($validate_url,$text_response,$tree_response); // if it fails, it halts
				phpCAS::trace('ST `'.$this->getST().'\' was validated');
				if ( $this->isProxy() ) {
					$this->validatePGT($validate_url,$text_response,$tree_response); // idem
					phpCAS::trace('PGT `'.$this->getPGT().'\' was validated');
					$_SESSION['phpCAS']['pgt'] = $this->getPGT();
				}
				$_SESSION['phpCAS']['user'] = $this->getUser();
				if($this->hasAttributes()){
					$_SESSION['phpCAS']['attributes'] = $this->getAttributes();
				}
				$res = TRUE;
				$logoutTicket = $this->getST();
			}
			elseif ( $this->hasPT() ) {
				// if a Proxy Ticket was given, validate it
				phpCAS::trace('PT `'.$this->getPT().'\' is present');
				$this->validatePT($validate_url,$text_response,$tree_response); // note: if it fails, it halts
				phpCAS::trace('PT `'.$this->getPT().'\' was validated');
				if ( $this->isProxy() ) {
					$this->validatePGT($validate_url,$text_response,$tree_response); // idem
					phpCAS::trace('PGT `'.$this->getPGT().'\' was validated');
					$_SESSION['phpCAS']['pgt'] = $this->getPGT();
				}
				$_SESSION['phpCAS']['user'] = $this->getUser();
				if($this->hasAttributes()){
					$_SESSION['phpCAS']['attributes'] = $this->getAttributes();
				}
				$res = TRUE;
				$logoutTicket = $this->getPT();
			}
			elseif ( $this->hasSA() ) {
				// if we have a SAML ticket, validate it.
				phpCAS::trace('SA `'.$this->getSA().'\' is present');
				$this->validateSA($validate_url,$text_response,$tree_response); // if it fails, it halts
				phpCAS::trace('SA `'.$this->getSA().'\' was validated');
				$_SESSION['phpCAS']['user'] = $this->getUser();
				$_SESSION['phpCAS']['attributes'] = $this->getAttributes();
				$res = TRUE;
				$logoutTicket = $this->getSA();
			}
			else {
				// no ticket given, not authenticated
				phpCAS::trace('no ticket found');
			}
			if ($res) {
				// call the post-authenticate callback if registered.
				if ($this->_postAuthenticateCallbackFunction) {
					$args = $this->_postAuthenticateCallbackArgs;
					array_unshift($args, $logoutTicket);
					call_user_func_array($this->_postAuthenticateCallbackFunction, $args);
				}
				
				// if called with a ticket parameter, we need to redirect to the app without the ticket so that CAS-ification is transparent to the browser (for later POSTS)
				// most of the checks and errors should have been made now, so we're safe for redirect without masking error messages.
				// remove the ticket as a security precaution to prevent a ticket in the HTTP_REFERRER
				if ($this->_clearTicketsFromUrl) {
					header('Location: '.$this->getURL());
					phpCAS::trace( "Prepare redirect to : ".$this->getURL() );
					phpCAS::traceExit();
					exit();
				}
			}
		}

		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method tells if the current session is authenticated.
	 * @return true if authenticated based soley on $_SESSION variable
	 * @since 0.4.22 by Brendan Arnold
	 */
	public function isSessionAuthenticated ()
	{
		return !empty($_SESSION['phpCAS']['user']);
	}

	/**
	 * This method tells if the user has already been (previously) authenticated
	 * by looking into the session variables.
	 *
	 * @note This function switches to callback mode when needed.
	 *
	 * @return TRUE when the user has already been authenticated; FALSE otherwise.
	 */
	private function wasPreviouslyAuthenticated()
	{
		phpCAS::traceBegin();

		if ( $this->isCallbackMode() ) {
			$this->callback();
		}

		$auth = FALSE;

		if ( $this->isProxy() ) {
			// CAS proxy: username and PGT must be present
			if ( $this->isSessionAuthenticated() && !empty($_SESSION['phpCAS']['pgt']) ) {
				// authentication already done
				$this->setUser($_SESSION['phpCAS']['user']);
				if(isset($_SESSION['phpCAS']['attributes'])){
					$this->setAttributes($_SESSION['phpCAS']['attributes']);
				}
				$this->setPGT($_SESSION['phpCAS']['pgt']);
				phpCAS::trace('user = `'.$_SESSION['phpCAS']['user'].'\', PGT = `'.$_SESSION['phpCAS']['pgt'].'\'');
				
				// Include the list of proxies
				if (isset($_SESSION['phpCAS']['proxies'])) {
					$this->setProxies($_SESSION['phpCAS']['proxies']);
					phpCAS::trace('proxies = "'.implode('", "', $_SESSION['phpCAS']['proxies']).'"'); 
				}
				
				$auth = TRUE;
			} elseif ( $this->isSessionAuthenticated() && empty($_SESSION['phpCAS']['pgt']) ) {
				// these two variables should be empty or not empty at the same time
				phpCAS::trace('username found (`'.$_SESSION['phpCAS']['user'].'\') but PGT is empty');
				// unset all tickets to enforce authentication
				unset($_SESSION['phpCAS']);
				$this->setST('');
				$this->setPT('');
			} elseif ( !$this->isSessionAuthenticated() && !empty($_SESSION['phpCAS']['pgt']) ) {
				// these two variables should be empty or not empty at the same time
				phpCAS::trace('PGT found (`'.$_SESSION['phpCAS']['pgt'].'\') but username is empty');
				// unset all tickets to enforce authentication
				unset($_SESSION['phpCAS']);
				$this->setST('');
				$this->setPT('');
			} else {
				phpCAS::trace('neither user not PGT found');
			}
		} else {
			// `simple' CAS client (not a proxy): username must be present
			if ( $this->isSessionAuthenticated() ) {
				// authentication already done
				$this->setUser($_SESSION['phpCAS']['user']);
				if(isset($_SESSION['phpCAS']['attributes'])){
					$this->setAttributes($_SESSION['phpCAS']['attributes']);
				}
				phpCAS::trace('user = `'.$_SESSION['phpCAS']['user'].'\'');
				
				// Include the list of proxies
				if (isset($_SESSION['phpCAS']['proxies'])) {
					$this->setProxies($_SESSION['phpCAS']['proxies']);
					phpCAS::trace('proxies = "'.implode('", "', $_SESSION['phpCAS']['proxies']).'"'); 
				}
				
				$auth = TRUE;
			} else {
				phpCAS::trace('no user found');
			}
		}

		phpCAS::traceEnd($auth);
		return $auth;
	}

	/**
	 * This method is used to redirect the client to the CAS server.
	 * It is used by CASClient::forceAuthentication() and CASClient::checkAuthentication().
	 * @param $gateway true to check authentication, false to force it
	 * @param $renew true to force the authentication with the CAS server
	 */
	public function redirectToCas($gateway=false,$renew=false){
		phpCAS::traceBegin();
		$cas_url = $this->getServerLoginURL($gateway,$renew);
		header('Location: '.$cas_url);
		phpCAS::trace( "Redirect to : ".$cas_url );

		$this->printHTMLHeader($this->getString(CAS_STR_AUTHENTICATION_WANTED));

		printf('<p>'.$this->getString(CAS_STR_SHOULD_HAVE_BEEN_REDIRECTED).'</p>',$cas_url);
		$this->printHTMLFooter();

		phpCAS::traceExit();
		exit();
	}


	/**
	 * This method is used to logout from CAS.
	 * @params $params an array that contains the optional url and service parameters that will be passed to the CAS server
	 */
	public function logout($params) {
		phpCAS::traceBegin();
		$cas_url = $this->getServerLogoutURL();
		$paramSeparator = '?';
		if (isset($params['url'])) {
			$cas_url = $cas_url . $paramSeparator . "url=" . urlencode($params['url']);
			$paramSeparator = '&';
		}
		if (isset($params['service'])) {
			$cas_url = $cas_url . $paramSeparator . "service=" . urlencode($params['service']);
		}
		header('Location: '.$cas_url);
		phpCAS::trace( "Prepare redirect to : ".$cas_url );

		session_unset();
		session_destroy();

		$this->printHTMLHeader($this->getString(CAS_STR_LOGOUT));
		printf('<p>'.$this->getString(CAS_STR_SHOULD_HAVE_BEEN_REDIRECTED).'</p>',$cas_url);
		$this->printHTMLFooter();

		phpCAS::traceExit();
		exit();
	}

	/**
	 * @return true if the current request is a logout request.
	 */
	private function isLogoutRequest() {
		return !empty($_POST['logoutRequest']);
	}

	/**
	 * This method handles logout requests.
	 * @param $check_client true to check the client bofore handling the request,
	 * false not to perform any access control. True by default.
	 * @param $allowed_clients an array of host names allowed to send logout requests.
	 * By default, only the CAs server (declared in the constructor) will be allowed.
	 */
	public function handleLogoutRequests($check_client=true, $allowed_clients=false) {
		phpCAS::traceBegin();
		if (!$this->isLogoutRequest()) {
			phpCAS::trace("Not a logout request");
			phpCAS::traceEnd();
			return;
		}
		if(!$this->_start_session && is_null($this->_signoutCallbackFunction)){
			phpCAS::trace("phpCAS can't handle logout requests if it does not manage the session.");
		}
		phpCAS::trace("Logout requested");
		phpCAS::trace("SAML REQUEST: ".$_POST['logoutRequest']);
		if ($check_client) {
			if (!$allowed_clients) {
				$allowed_clients = array( $this->getServerHostname() );
			}
			$client_ip = $_SERVER['REMOTE_ADDR'];
			$client = gethostbyaddr($client_ip);
			phpCAS::trace("Client: ".$client."/".$client_ip);
			$allowed = false;
			foreach ($allowed_clients as $allowed_client) {
				if (($client == $allowed_client) or ($client_ip == $allowed_client)) {
					phpCAS::trace("Allowed client '".$allowed_client."' matches, logout request is allowed");
					$allowed = true;
					break;
				} else {
					phpCAS::trace("Allowed client '".$allowed_client."' does not match");
				}
			}
			if (!$allowed) {
				phpCAS::error("Unauthorized logout request from client '".$client."'");
				printf("Unauthorized!");
				phpCAS::traceExit();
				exit();
			}
		} else {
			phpCAS::trace("No access control set");
		}
		// Extract the ticket from the SAML Request
		preg_match("|<samlp:SessionIndex>(.*)</samlp:SessionIndex>|", $_POST['logoutRequest'], $tick, PREG_OFFSET_CAPTURE, 3);
		$wrappedSamlSessionIndex = preg_replace('|<samlp:SessionIndex>|','',$tick[0][0]);
		$ticket2logout = preg_replace('|</samlp:SessionIndex>|','',$wrappedSamlSessionIndex);
		phpCAS::trace("Ticket to logout: ".$ticket2logout);
		
		// call the post-authenticate callback if registered.
		if ($this->_signoutCallbackFunction) {
			$args = $this->_signoutCallbackArgs;
			array_unshift($args, $ticket2logout);
			call_user_func_array($this->_signoutCallbackFunction, $args);
		}
		
		// If phpCAS is managing the session, destroy it.
		if ($this->_start_session) {
			$session_id = preg_replace('/[^\w]/','',$ticket2logout);
			phpCAS::trace("Session id: ".$session_id);
	
			// destroy a possible application session created before phpcas
			if(session_id() !== ""){
				session_unset();
				session_destroy();
			}
			// fix session ID
			session_id($session_id);
			$_COOKIE[session_name()]=$session_id;
			$_GET[session_name()]=$session_id;
	
			// Overwrite session
			session_start();
			session_unset();
			session_destroy();
		}
		
		printf("Disconnected!");
		phpCAS::traceExit();
		exit();
	}

	/** @} */

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                  BASIC CLIENT FEATURES (CAS 1.0)                   XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	// ########################################################################
	//  ST
	// ########################################################################
	/**
	* @addtogroup internalBasic
	* @{
	*/

	/**
	 * the Service Ticket provided in the URL of the request if present
	 * (empty otherwise). Written by CASClient::CASClient(), read by
	 * CASClient::getST() and CASClient::hasPGT().
	 *
	 * @hideinitializer
	 */
	private $_st = '';

	/**
	 * This method returns the Service Ticket provided in the URL of the request.
	 * @return The service ticket.
	 */
	public  function getST()
	{ return $this->_st; }

	/**
	 * This method stores the Service Ticket.
	 * @param $st The Service Ticket.
	 */
	public function setST($st)
	{ $this->_st = $st; }

	/**
	 * This method tells if a Service Ticket was stored.
	 * @return TRUE if a Service Ticket has been stored.
	 */
	public function hasST()
	{ return !empty($this->_st); }

	/** @} */

	// ########################################################################
	//  ST VALIDATION
	// ########################################################################
	/**
	* @addtogroup internalBasic
	* @{
	*/

	/**
	 * the certificate of the CAS server CA.
	 *
	 * @hideinitializer
	 */
	private $_cas_server_ca_cert = '';

	/**
	 * Set to true not to validate the CAS server.
	 *
	 * @hideinitializer
	 */
	private $_no_cas_server_validation = false;


	/**
	 * Set the CA certificate of the CAS server.
	 *
	 * @param $cert the PEM certificate of the CA that emited the cert of the server
	 */
	public function setCasServerCACert($cert)
	{
		$this->_cas_server_ca_cert = $cert;
	}

	/**
	 * Set no SSL validation for the CAS server.
	 */
	public function setNoCasServerValidation()
	{
		$this->_no_cas_server_validation = true;
	}

	/**
	 * This method is used to validate a ST; halt on failure, and sets $validate_url,
	 * $text_reponse and $tree_response on success. These parameters are used later
	 * by CASClient::validatePGT() for CAS proxies.
	 * Used for all CAS 1.0 validations
	 * @param $validate_url the URL of the request to the CAS server.
	 * @param $text_response the response of the CAS server, as is (XML text).
	 * @param $tree_response the response of the CAS server, as a DOM XML tree.
	 *
	 * @return bool TRUE when successfull, halt otherwise by calling CASClient::authError().
	 */
	public function validateST($validate_url,&$text_response,&$tree_response)
	{
		phpCAS::traceBegin();
		// build the URL to validate the ticket
		$validate_url = $this->getServerServiceValidateURL().'&ticket='.$this->getST();
		if ( $this->isProxy() ) {
			// pass the callback url for CAS proxies
			$validate_url .= '&pgtUrl='.urlencode($this->getCallbackURL());
		}

		// open and read the URL
		if ( !$this->readURL($validate_url,array(),$headers,$text_response,$err_msg) ) {
			phpCAS::trace('could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')');
			$this->authError('ST not validated',
			$validate_url,
			TRUE/*$no_response*/);
		}

		// analyze the result depending on the version
		switch ($this->getServerVersion()) {
			case CAS_VERSION_1_0:
				if (preg_match('/^no\n/',$text_response)) {
					phpCAS::trace('ST has not been validated');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					FALSE/*$bad_response*/,
					$text_response);
				}
				if (!preg_match('/^yes\n/',$text_response)) {
					phpCAS::trace('ill-formed response');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// ST has been validated, extract the user name
				$arr = preg_split('/\n/',$text_response);
				$this->setUser(trim($arr[1]));
				break;
			case CAS_VERSION_2_0:

				// create new DOMDocument object
				$dom = new DOMDocument();
				// Fix possible whitspace problems
				$dom->preserveWhiteSpace = false;
				// read the response of the CAS server into a DOM object
				if ( !($dom->loadXML($text_response))) {
					phpCAS::trace('dom->loadXML() failed');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// read the root node of the XML tree
				if ( !($tree_response = $dom->documentElement) ) {
					phpCAS::trace('documentElement() failed');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// insure that tag name is 'serviceResponse'
				if ( $tree_response->localName != 'serviceResponse' ) {
					phpCAS::trace('bad XML root node (should be `serviceResponse\' instead of `'.$tree_response->localName.'\'');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}

				if ( $tree_response->getElementsByTagName("authenticationSuccess")->length != 0) {
					// authentication succeded, extract the user name
					$success_elements = $tree_response->getElementsByTagName("authenticationSuccess");
					if ( $success_elements->item(0)->getElementsByTagName("user")->length == 0) {
						// no user specified => error
						$this->authError('ST not validated',
						$validate_url,
						FALSE/*$no_response*/,
						TRUE/*$bad_response*/,
						$text_response);
					}
					$this->setUser(trim($success_elements->item(0)->getElementsByTagName("user")->item(0)->nodeValue));
					$this->readExtraAttributesCas20($success_elements);
				} else if ( $tree_response->getElementsByTagName("authenticationFailure")->length != 0) {
					phpCAS::trace('<authenticationFailure> found');
					// authentication failed, extract the error code and message
					$auth_fail_list = $tree_response->getElementsByTagName("authenticationFailure");
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					FALSE/*$bad_response*/,
					$text_response,
					$auth_fail_list->item(0)->getAttribute('code')/*$err_code*/,
					trim($auth_fail_list->item(0)->nodeValue)/*$err_msg*/);
				} else {
					phpCAS::trace('neither <authenticationSuccess> nor <authenticationFailure> found');
					$this->authError('ST not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				break;
		}
		$this->renameSession($this->getST());
		// at this step, ST has been validated and $this->_user has been set,
		phpCAS::traceEnd(TRUE);
		return TRUE;
	}


	/**
	 * This method will parse the DOM and pull out the attributes from the XML
	 * payload and put them into an array, then put the array into the session.
	 *
	 * @param $text_response the XML payload.
	 * @return bool TRUE when successfull, halt otherwise by calling CASClient::authError().
	 */
	private function readExtraAttributesCas20($success_elements)
	{
		# PHPCAS-43 add CAS-2.0 extra attributes
		phpCAS::traceBegin();

		$extra_attributes = array();
		
		// "Jasig Style" Attributes:
		// 
		// 	<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
		// 		<cas:authenticationSuccess>
		// 			<cas:user>jsmith</cas:user>
		// 			<cas:attributes>
		// 				<cas:attraStyle>RubyCAS</cas:attraStyle>
		// 				<cas:surname>Smith</cas:surname>
		// 				<cas:givenName>John</cas:givenName>
		// 				<cas:memberOf>CN=Staff,OU=Groups,DC=example,DC=edu</cas:memberOf>
		// 				<cas:memberOf>CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu</cas:memberOf>
		// 			</cas:attributes>
		// 			<cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
		// 		</cas:authenticationSuccess>
		// 	</cas:serviceResponse>
		// 
		if ( $success_elements->item(0)->getElementsByTagName("attributes")->length != 0) {
			$attr_nodes = $success_elements->item(0)->getElementsByTagName("attributes");
			phpCas :: trace("Found nested jasig style attributes");
			if($attr_nodes->item(0)->hasChildNodes()){
				// Nested Attributes
				foreach ($attr_nodes->item(0)->childNodes as $attr_child) {
					phpCas :: trace("Attribute [".$attr_child->localName."] = ".$attr_child->nodeValue);
					$this->addAttributeToArray($extra_attributes, $attr_child->localName, $attr_child->nodeValue);
				}
			}
		} 
		// "RubyCAS Style" attributes
		// 
		// 	<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
		// 		<cas:authenticationSuccess>
		// 			<cas:user>jsmith</cas:user>
		// 			
		// 			<cas:attraStyle>RubyCAS</cas:attraStyle>
		// 			<cas:surname>Smith</cas:surname>
		// 			<cas:givenName>John</cas:givenName>
		// 			<cas:memberOf>CN=Staff,OU=Groups,DC=example,DC=edu</cas:memberOf>
		// 			<cas:memberOf>CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu</cas:memberOf>
		// 			
		// 			<cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
		// 		</cas:authenticationSuccess>
		// 	</cas:serviceResponse>
		// 
		else {
			phpCas :: trace("Testing for rubycas style attributes");
			$childnodes = $success_elements->item(0)->childNodes;
			foreach ($childnodes as $attr_node) {
				switch ($attr_node->localName) {
					case 'user':
					case 'proxies':
					case 'proxyGrantingTicket':
						continue;
					default:
						if (strlen(trim($attr_node->nodeValue))) {
							phpCas :: trace("Attribute [".$attr_node->localName."] = ".$attr_node->nodeValue);
							$this->addAttributeToArray($extra_attributes, $attr_node->localName, $attr_node->nodeValue);
						}
				}
			}
		}
		
		// "Name-Value" attributes.
		// 
		// Attribute format from these mailing list thread:
		// http://jasig.275507.n4.nabble.com/CAS-attributes-and-how-they-appear-in-the-CAS-response-td264272.html
		// Note: This is a less widely used format, but in use by at least two institutions.
		// 
		// 	<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
		// 		<cas:authenticationSuccess>
		// 			<cas:user>jsmith</cas:user>
		// 			
		// 			<cas:attribute name='attraStyle' value='Name-Value' />
		// 			<cas:attribute name='surname' value='Smith' />
		// 			<cas:attribute name='givenName' value='John' />
		// 			<cas:attribute name='memberOf' value='CN=Staff,OU=Groups,DC=example,DC=edu' />
		// 			<cas:attribute name='memberOf' value='CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu' />
		// 			
		// 			<cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
		// 		</cas:authenticationSuccess>
		// 	</cas:serviceResponse>
		// 
		if (!count($extra_attributes) && $success_elements->item(0)->getElementsByTagName("attribute")->length != 0) {
			$attr_nodes = $success_elements->item(0)->getElementsByTagName("attribute");
			$firstAttr = $attr_nodes->item(0);
			if (!$firstAttr->hasChildNodes() && $firstAttr->hasAttribute('name') && $firstAttr->hasAttribute('value')) {
				phpCas :: trace("Found Name-Value style attributes");
				// Nested Attributes
				foreach ($attr_nodes as $attr_node) {
					if ($attr_node->hasAttribute('name') && $attr_node->hasAttribute('value')) {
						phpCas :: trace("Attribute [".$attr_node->getAttribute('name')."] = ".$attr_node->getAttribute('value'));
						$this->addAttributeToArray($extra_attributes, $attr_node->getAttribute('name'), $attr_node->getAttribute('value'));
					}
				}
			}
		}
		
		$this->setAttributes($extra_attributes);
		phpCAS::traceEnd();
		return TRUE;
	}
	
	/**
	 * Add an attribute value to an array of attributes.
	 * 
	 * @param ref array $array
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	private function addAttributeToArray (array &$attributeArray, $name, $value) {
		// If multiple attributes exist, add as an array value
		if (isset($attributeArray[$name])) {
			// Initialize the array with the existing value
			if (!is_array($attributeArray[$name])) {
				$existingValue = $attributeArray[$name];
				$attributeArray[$name] = array($existingValue);
			}
			
			$attributeArray[$name][] = trim($value);
		} else {
			$attributeArray[$name] = trim($value);
		}
	}
	
	// ########################################################################
	//  SAML VALIDATION
	// ########################################################################
	/**
	* @addtogroup internalBasic
	* @{
	*/

	/**
	 * This method is used to validate a SAML TICKET; halt on failure, and sets $validate_url,
	 * $text_reponse and $tree_response on success. These parameters are used later
	 * by CASClient::validatePGT() for CAS proxies.
	 *
	 * @param $validate_url the URL of the request to the CAS server.
	 * @param $text_response the response of the CAS server, as is (XML text).
	 * @param $tree_response the response of the CAS server, as a DOM XML tree.
	 *
	 * @return bool TRUE when successfull, halt otherwise by calling CASClient::authError().
	 */
	public function validateSA($validate_url,&$text_response,&$tree_response)
	{
		phpCAS::traceBegin();

		// build the URL to validate the ticket
		$validate_url = $this->getServerSamlValidateURL();

		// open and read the URL
		if ( !$this->readURL($validate_url,array(),$headers,$text_response,$err_msg) ) {
			phpCAS::trace('could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')');
			$this->authError('SA not validated', $validate_url, TRUE/*$no_response*/);
		}

		phpCAS::trace('server version: '.$this->getServerVersion());

		// analyze the result depending on the version
		switch ($this->getServerVersion()) {
			case SAML_VERSION_1_1:

				// create new DOMDocument Object
				$dom = new DOMDocument();
				// Fix possible whitspace problems
				$dom->preserveWhiteSpace = false;
				// read the response of the CAS server into a DOM object
				if ( !($dom->loadXML($text_response))) {
					phpCAS::trace('dom->loadXML() failed');
					$this->authError('SA not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// read the root node of the XML tree
				if ( !($tree_response = $dom->documentElement) ) {
					phpCAS::trace('documentElement() failed');
					$this->authError('SA not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// insure that tag name is 'Envelope'
				if ( $tree_response->localName != 'Envelope' ) {
					phpCAS::trace('bad XML root node (should be `Envelope\' instead of `'.$tree_response->localName.'\'');
					$this->authError('SA not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				// check for the NameIdentifier tag in the SAML response
				if ( $tree_response->getElementsByTagName("NameIdentifier")->length != 0) {
					$success_elements = $tree_response->getElementsByTagName("NameIdentifier");
					phpCAS::trace('NameIdentifier found');
					$user = trim($success_elements->item(0)->nodeValue);
					phpCAS::trace('user = `'.$user.'`');
					$this->setUser($user);
					$this->setSessionAttributes($text_response);
				} else {
					phpCAS::trace('no <NameIdentifier> tag found in SAML payload');
					$this->authError('SA not validated',
					$validate_url,
					FALSE/*$no_response*/,
					TRUE/*$bad_response*/,
					$text_response);
				}
				break;
		}
		$this->renameSession($this->getSA());
		// at this step, ST has been validated and $this->_user has been set,
		phpCAS::traceEnd(TRUE);
		return TRUE;
	}

	/**
	 * This method will parse the DOM and pull out the attributes from the SAML
	 * payload and put them into an array, then put the array into the session.
	 *
	 * @param $text_response the SAML payload.
	 * @return bool TRUE when successfull and FALSE if no attributes a found
	 */
	private function setSessionAttributes($text_response)
	{
		phpCAS::traceBegin();

		$result = FALSE;

		$attr_array = array();

		// create new DOMDocument Object
		$dom = new DOMDocument();
		// Fix possible whitspace problems
		$dom->preserveWhiteSpace = false;
		if (($dom->loadXML($text_response))) {
			$xPath = new DOMXpath($dom);
			$xPath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:1.0:protocol');
			$xPath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:1.0:assertion');
			$nodelist = $xPath->query("//saml:Attribute");

			if($nodelist){
				foreach($nodelist as $node){
					$xres = $xPath->query("saml:AttributeValue", $node);
					$name = $node->getAttribute("AttributeName");
					$value_array = array();
					foreach($xres as $node2){
						$value_array[] = $node2->nodeValue;
					}
					$attr_array[$name] = $value_array;
				}
				// UGent addition...
				foreach($attr_array as $attr_key => $attr_value) {
					if(count($attr_value) > 1) {
						$this->_attributes[$attr_key] = $attr_value;
						phpCAS::trace("* " . $attr_key . "=" . $attr_value);
					}
					else {
						$this->_attributes[$attr_key] = $attr_value[0];
						phpCAS::trace("* " . $attr_key . "=" . $attr_value[0]);
					}
				}
				$result = TRUE;
			}else{
				phpCAS::trace("SAML Attributes are empty");
				$result = FALSE;
			}
		}
		phpCAS::traceEnd($result);
		return $result;
	}

	/** @} */

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                     PROXY FEATURES (CAS 2.0)                       XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	// ########################################################################
	//  PROXYING
	// ########################################################################
	/**
	* @addtogroup internalProxy
	* @{
	*/

	/**
	 * A boolean telling if the client is a CAS proxy or not. Written by CASClient::CASClient(),
	 * read by CASClient::isProxy().
	 */
	private $_proxy;

	/**
	 * Handler for managing service cookies.
	 */
	private $_serviceCookieJar;

	/**
	 * Tells if a CAS client is a CAS proxy or not
	 *
	 * @return TRUE when the CAS client is a CAs proxy, FALSE otherwise
	 */
	public function isProxy()
	{
		return $this->_proxy;
	}

	/** @} */
	// ########################################################################
	//  PGT
	// ########################################################################
	/**
	* @addtogroup internalProxy
	* @{
	*/

	/**
	 * the Proxy Grnting Ticket given by the CAS server (empty otherwise).
	 * Written by CASClient::setPGT(), read by CASClient::getPGT() and CASClient::hasPGT().
	 *
	 * @hideinitializer
	 */
	private $_pgt = '';

	/**
	 * This method returns the Proxy Granting Ticket given by the CAS server.
	 * @return The Proxy Granting Ticket.
	 */
	private function getPGT()
	{ return $this->_pgt; }

	/**
	 * This method stores the Proxy Granting Ticket.
	 * @param $pgt The Proxy Granting Ticket.
	 */
	private function setPGT($pgt)
	{ $this->_pgt = $pgt; }

	/**
	 * This method tells if a Proxy Granting Ticket was stored.
	 * @return TRUE if a Proxy Granting Ticket has been stored.
	 */
	private function hasPGT()
	{ return !empty($this->_pgt); }

	/** @} */

	// ########################################################################
	//  CALLBACK MODE
	// ########################################################################
	/**
	* @addtogroup internalCallback
	* @{
	*/
	/**
	 * each PHP script using phpCAS in proxy mode is its own callback to get the
	 * PGT back from the CAS server. callback_mode is detected by the constructor
	 * thanks to the GET parameters.
	 */

	/**
	 * a boolean to know if the CAS client is running in callback mode. Written by
	 * CASClient::setCallBackMode(), read by CASClient::isCallbackMode().
	 *
	 * @hideinitializer
	 */
	private $_callback_mode = FALSE;

	/**
	 * This method sets/unsets callback mode.
	 *
	 * @param $callback_mode TRUE to set callback mode, FALSE otherwise.
	 */
	private function setCallbackMode($callback_mode)
	{
		$this->_callback_mode = $callback_mode;
	}

	/**
	 * This method returns TRUE when the CAs client is running i callback mode,
	 * FALSE otherwise.
	 *
	 * @return A boolean.
	 */
	private function isCallbackMode()
	{
		return $this->_callback_mode;
	}

	/**
	 * the URL that should be used for the PGT callback (in fact the URL of the
	 * current request without any CGI parameter). Written and read by
	 * CASClient::getCallbackURL().
	 *
	 * @hideinitializer
	 */
	private $_callback_url = '';

	/**
	 * This method returns the URL that should be used for the PGT callback (in
	 * fact the URL of the current request without any CGI parameter, except if
	 * phpCAS::setFixedCallbackURL() was used).
	 *
	 * @return The callback URL
	 */
	private function getCallbackURL()
	{
		// the URL is built when needed only
		if ( empty($this->_callback_url) ) {
			$final_uri = '';
			// remove the ticket if present in the URL
			$final_uri = 'https://';
			$final_uri .= $this->getServerUrl();
			$request_uri = $_SERVER['REQUEST_URI'];
			$request_uri = preg_replace('/\?.*$/','',$request_uri);
			$final_uri .= $request_uri;
			$this->setCallbackURL($final_uri);
		}
		return $this->_callback_url;
	}

	/**
	 * This method sets the callback url.
	 *
	 * @param $callback_url url to set callback
	 */
	public function setCallbackURL($url)
	{
		return $this->_callback_url = $url;
	}

	/**
	 * This method is called by CASClient::CASClient() when running in callback
	 * mode. It stores the PGT and its PGT Iou, prints its output and halts.
	 */
	private function callback()
	{
		phpCAS::traceBegin();
		if (preg_match('/PGTIOU-[\.\-\w]/', $_GET['pgtIou'])){
			if(preg_match('/[PT]GT-[\.\-\w]/', $_GET['pgtId'])){
				$this->printHTMLHeader('phpCAS callback');
				$pgt_iou = $_GET['pgtIou'];
				$pgt = $_GET['pgtId'];
				phpCAS::trace('Storing PGT `'.$pgt.'\' (id=`'.$pgt_iou.'\')');
				echo '<p>Storing PGT `'.$pgt.'\' (id=`'.$pgt_iou.'\').</p>';
				$this->storePGT($pgt,$pgt_iou);
				$this->printHTMLFooter();
			}else{
				phpCAS::error('PGT format invalid' . $_GET['pgtId']);
			}
		}else{
			phpCAS::error('PGTiou format invalid' . $_GET['pgtIou']);
		}
		phpCAS::traceExit();
		exit();
	}

	/** @} */

	// ########################################################################
	//  PGT STORAGE
	// ########################################################################
	/**
	* @addtogroup internalPGTStorage
	* @{
	*/

	/**
	 * an instance of a class inheriting of PGTStorage, used to deal with PGT
	 * storage. Created by CASClient::setPGTStorageFile(), used
	 * by CASClient::setPGTStorageFile() and CASClient::initPGTStorage().
	 *
	 * @hideinitializer
	 */
	private $_pgt_storage = null;

	/**
	 * This method is used to initialize the storage of PGT's.
	 * Halts on error.
	 */
	private function initPGTStorage()
	{
		// if no SetPGTStorageXxx() has been used, default to file
		if ( !is_object($this->_pgt_storage) ) {
			$this->setPGTStorageFile();
		}

		// initializes the storage
		$this->_pgt_storage->init();
	}

	/**
	 * This method stores a PGT. Halts on error.
	 *
	 * @param $pgt the PGT to store
	 * @param $pgt_iou its corresponding Iou
	 */
	private function storePGT($pgt,$pgt_iou)
	{
		// ensure that storage is initialized
		$this->initPGTStorage();
		// writes the PGT
		$this->_pgt_storage->write($pgt,$pgt_iou);
	}

	/**
	 * This method reads a PGT from its Iou and deletes the corresponding storage entry.
	 *
	 * @param $pgt_iou the PGT Iou
	 *
	 * @return The PGT corresponding to the Iou, FALSE when not found.
	 */
	private function loadPGT($pgt_iou)
	{
		// ensure that storage is initialized
		$this->initPGTStorage();
		// read the PGT
		return $this->_pgt_storage->read($pgt_iou);
	}

	/**
	 * This method is used to tell phpCAS to store the response of the
	 * CAS server to PGT requests onto the filesystem.
	 *
	 * @param $format the format used to store the PGT's (`plain' and `xml' allowed)
	 * @param $path the path where the PGT's should be stored
	 */
	public function setPGTStorageFile($format='',
	$path='')
	{
		// check that the storage has not already been set
		if ( is_object($this->_pgt_storage) ) {
			phpCAS::error('PGT storage already defined');
		}

		// create the storage object
		$this->_pgt_storage = new CAS_PGTStorageFile($this,$format,$path);
	}


	// ########################################################################
	//  PGT VALIDATION
	// ########################################################################
	/**
	* This method is used to validate a PGT; halt on failure.
	*
	* @param $validate_url the URL of the request to the CAS server.
	* @param $text_response the response of the CAS server, as is (XML text); result
	* of CASClient::validateST() or CASClient::validatePT().
	* @param $tree_response the response of the CAS server, as a DOM XML tree; result
	* of CASClient::validateST() or CASClient::validatePT().
	*
	* @return bool TRUE when successfull, halt otherwise by calling CASClient::authError().
	*/
	private function validatePGT(&$validate_url,$text_response,$tree_response)
	{
		phpCAS::traceBegin();
		if ( $tree_response->getElementsByTagName("proxyGrantingTicket")->length == 0) {
			phpCAS::trace('<proxyGrantingTicket> not found');
			// authentication succeded, but no PGT Iou was transmitted
			$this->authError('Ticket validated but no PGT Iou transmitted',
			$validate_url,
			FALSE/*$no_response*/,
			FALSE/*$bad_response*/,
			$text_response);
		} else {
			// PGT Iou transmitted, extract it
			$pgt_iou = trim($tree_response->getElementsByTagName("proxyGrantingTicket")->item(0)->nodeValue);
			if(preg_match('/PGTIOU-[\.\-\w]/',$pgt_iou)){
				$pgt = $this->loadPGT($pgt_iou);
				if ( $pgt == FALSE ) {
					phpCAS::trace('could not load PGT');
					$this->authError('PGT Iou was transmitted but PGT could not be retrieved',
					$validate_url,
					FALSE/*$no_response*/,
					FALSE/*$bad_response*/,
					$text_response);
				}
				$this->setPGT($pgt);
			}else{
				phpCAS::trace('PGTiou format error');
				$this->authError('PGT Iou was transmitted but has wrong fromat',
				$validate_url,
				FALSE/*$no_response*/,
				FALSE/*$bad_response*/,
				$text_response);
			}
		}
		phpCAS::traceEnd(TRUE);
		return TRUE;
	}

	// ########################################################################
	//  PGT VALIDATION
	// ########################################################################

	/**
	 * This method is used to retrieve PT's from the CAS server thanks to a PGT.
	 *
	 * @param $target_service the service to ask for with the PT.
	 * @param $err_code an error code (PHPCAS_SERVICE_OK on success).
	 * @param $err_msg an error message (empty on success).
	 *
	 * @return a Proxy Ticket, or FALSE on error.
	 */
	private function retrievePT($target_service,&$err_code,&$err_msg)
	{
		phpCAS::traceBegin();

		// by default, $err_msg is set empty and $pt to TRUE. On error, $pt is
		// set to false and $err_msg to an error message. At the end, if $pt is FALSE
		// and $error_msg is still empty, it is set to 'invalid response' (the most
		// commonly encountered error).
		$err_msg = '';

		// build the URL to retrieve the PT
		$cas_url = $this->getServerProxyURL().'?targetService='.urlencode($target_service).'&pgt='.$this->getPGT();

		// open and read the URL
		if ( !$this->readURL($cas_url,array(),$headers,$cas_response,$err_msg) ) {
			phpCAS::trace('could not open URL \''.$cas_url.'\' to validate ('.$err_msg.')');
			$err_code = PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE;
			$err_msg = 'could not retrieve PT (no response from the CAS server)';
			phpCAS::traceEnd(FALSE);
			return FALSE;
		}

		$bad_response = FALSE;

		if ( !$bad_response ) {
			// create new DOMDocument object
			$dom = new DOMDocument();
			// Fix possible whitspace problems
			$dom->preserveWhiteSpace = false;
			// read the response of the CAS server into a DOM object
			if ( !($dom->loadXML($cas_response))) {
				phpCAS::trace('dom->loadXML() failed');
				// read failed
				$bad_response = TRUE;
			}
		}

		if ( !$bad_response ) {
			// read the root node of the XML tree
			if ( !($root = $dom->documentElement) ) {
				phpCAS::trace('documentElement failed');
				// read failed
				$bad_response = TRUE;
			}
		}

		if ( !$bad_response ) {
			// insure that tag name is 'serviceResponse'
			if ( $root->localName != 'serviceResponse' ) {
				phpCAS::trace('localName failed');
				// bad root node
				$bad_response = TRUE;
			}
		}

		if ( !$bad_response ) {
			// look for a proxySuccess tag
			if ( $root->getElementsByTagName("proxySuccess")->length != 0) {
				$proxy_success_list = $root->getElementsByTagName("proxySuccess");

				// authentication succeded, look for a proxyTicket tag
				if ( $proxy_success_list->item(0)->getElementsByTagName("proxyTicket")->length != 0) {
					$err_code = PHPCAS_SERVICE_OK;
					$err_msg = '';
					$pt = trim($proxy_success_list->item(0)->getElementsByTagName("proxyTicket")->item(0)->nodeValue);
					phpCAS::trace('original PT: '.trim($pt));
					phpCAS::traceEnd($pt);
					return $pt;
				} else {
					phpCAS::trace('<proxySuccess> was found, but not <proxyTicket>');
				}
			}
			// look for a proxyFailure tag
			else if ( $root->getElementsByTagName("proxyFailure")->length != 0) {
				$proxy_failure_list = $root->getElementsByTagName("proxyFailure");

				// authentication failed, extract the error
				$err_code = PHPCAS_SERVICE_PT_FAILURE;
				$err_msg = 'PT retrieving failed (code=`'
				.$proxy_failure_list->item(0)->getAttribute('code')
				.'\', message=`'
				.trim($proxy_failure_list->item(0)->nodeValue)
				.'\')';
				phpCAS::traceEnd(FALSE);
				return FALSE;
			} else {
				phpCAS::trace('neither <proxySuccess> nor <proxyFailure> found');
			}
		}

		// at this step, we are sure that the response of the CAS server was ill-formed
		$err_code = PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE;
		$err_msg = 'Invalid response from the CAS server (response=`'.$cas_response.'\')';

		phpCAS::traceEnd(FALSE);
		return FALSE;
	}

	// ########################################################################
	// ACCESS TO EXTERNAL SERVICES
	// ########################################################################

	/**
	 * This method is used to acces a remote URL.
	 *
	 * @param $url the URL to access.
	 * @param $cookies an array containing cookies strings such as 'name=val'
	 * @param $headers an array containing the HTTP header lines of the response
	 * (an empty array on failure).
	 * @param $body the body of the response, as a string (empty on failure).
	 * @param $err_msg an error message, filled on failure.
	 *
	 * @return TRUE on success, FALSE otherwise (in this later case, $err_msg
	 * contains an error message).
	 */
	private function readURL($url, array $cookies, &$headers, &$body, &$err_msg)
	{
		$className = $this->_requestImplementation;
		$request = new $className();

		if (count($this->_curl_options)) {
			$request->setCurlOptions($this->_curl_options);
		}

		$request->setUrl($url);
		$request->addCookies($cookies);

		if (empty($this->_cas_server_ca_cert) && !$this->_no_cas_server_validation) {
			phpCAS::error('one of the methods phpCAS::setCasServerCACert() or phpCAS::setNoCasServerValidation() must be called.');
		}
		if ($this->_cas_server_ca_cert != '') {
			$request->setSslCaCert($this->_cas_server_ca_cert);
		}

		// add extra stuff if SAML
		if ($this->hasSA()) {
			$request->addHeader("soapaction: http://www.oasis-open.org/committees/security");
			$request->addHeader("cache-control: no-cache");
			$request->addHeader("pragma: no-cache");
			$request->addHeader("accept: text/xml");
			$request->addHeader("connection: keep-alive");
			$request->addHeader("content-type: text/xml");
			$request->makePost();
			$request->setPostBody($this->buildSAMLPayload());
		}

		if ($request->send()) {
			$headers = $request->getResponseHeaders();
			$body = $request->getResponseBody();
			$err_msg = '';
			return true;
		} else {
			$headers = '';
			$body = '';
			$err_msg = $request->getErrorMessage();
			return false;
		}
	}

	/**
	 * This method is used to build the SAML POST body sent to /samlValidate URL.
	 *
	 * @return the SOAP-encased SAMLP artifact (the ticket).
	 */
	private function buildSAMLPayload()
	{
		phpCAS::traceBegin();

		//get the ticket
		$sa = $this->getSA();

		$body=SAML_SOAP_ENV.SAML_SOAP_BODY.SAMLP_REQUEST.SAML_ASSERTION_ARTIFACT.$sa.SAML_ASSERTION_ARTIFACT_CLOSE.SAMLP_REQUEST_CLOSE.SAML_SOAP_BODY_CLOSE.SAML_SOAP_ENV_CLOSE;

		phpCAS::traceEnd($body);
		return ($body);
	}

	private  $_curl_headers = array();
	/**
	 * This method is the callback used by readURL method to request HTTP headers.
	 */
	public function _curl_read_headers($ch, $header)
	{
		$this->_curl_headers[] = $header;
		return strlen($header);
	}

	/**
	 * This method is used to access an HTTP[S] service.
	 *
	 * @param $url the service to access.
	 * @param $err_code an error code Possible values are PHPCAS_SERVICE_OK (on
	 * success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE, PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE,
	 * PHPCAS_SERVICE_PT_FAILURE, PHPCAS_SERVICE_NOT AVAILABLE.
	 * @param $output the output of the service (also used to give an error
	 * message on failure).
	 *
	 * @return TRUE on success, FALSE otherwise (in this later case, $err_code
	 * gives the reason why it failed and $output contains an error message).
	 */
	public function serviceWeb($url,&$err_code,&$output)
	{
		phpCAS::traceBegin();
		$cookies = array();
		// at first retrieve a PT
		$pt = $this->retrievePT($url,$err_code,$ptoutput);

		$res = TRUE;

		// test if PT was retrieved correctly
		if ( !$pt ) {
			// note: $err_code and $err_msg are filled by CASClient::retrievePT()
			phpCAS::trace('PT was not retrieved correctly');
			$res = FALSE;
		} else {
			// add cookies if necessary
			$cookies = array();
			foreach ( $this->_serviceCookieJar->getCookies($url) as $name => $val ) {
				$cookies[] = $name.'='.$val;
			}
				
			// build the URL including the PT
			if ( strstr($url,'?') === FALSE ) {
				$service_url = $url.'?ticket='.$pt;
			} else {
				$service_url = $url.'&ticket='.$pt;
			}
			phpCAS::trace('reading URL`'.$service_url.'\'');
			if ( !$this->readURL($service_url,$cookies,$headers,$output,$err_msg) ) {
				phpCAS::trace('could not read URL`'.$service_url.'\'');
				$err_code = PHPCAS_SERVICE_NOT_AVAILABLE;
				// give an error message
				$output = sprintf($this->getString(CAS_STR_SERVICE_UNAVAILABLE),
				$service_url,
				$err_msg);
				$res = FALSE;
			} else {
				// URL has been fetched, extract the cookies
				phpCAS::trace('URL`'.$service_url.'\' has been read, storing cookies:');
				$this->_serviceCookieJar->storeCookies($service_url, $headers);
			}
			// Check for the redirect after authentication
			foreach($headers as $header){
				if (preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches))
				{
					$redirect_url = trim(array_pop($matches));
					phpCAS :: trace('Found redirect:'.$redirect_url);
					$cookies = array();
					foreach ( $this->_serviceCookieJar->getCookies($redirect_url) as $name => $val ) {
						$cookies[] = $name.'='.$val;
					}
					phpCAS::trace('reading URL`'.$redirect_url.'\'');
					if ( !$this->readURL($redirect_url,$cookies,$headers,$output,$err_msg) ) {
						phpCAS::trace('could not read URL`'.$redirect_url.'\'');
						$err_code = PHPCAS_SERVICE_NOT_AVAILABLE;
						// give an error message
						$output = sprintf($this->getString(CAS_STR_SERVICE_UNAVAILABLE),
						$service_url,
						$err_msg);
						$res = FALSE;
					} else {
						// URL has been fetched, extract the cookies
						phpCAS::trace('URL`'.$redirect_url.'\' has been read, storing cookies:');
						$this->_serviceCookieJar->storeCookies($redirect_url, $headers);
					}
					break;
				}

			}
		}

		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method is used to access an IMAP/POP3/NNTP service.
	 *
	 * @param $url a string giving the URL of the service, including the mailing box
	 * for IMAP URLs, as accepted by imap_open().
	 * @param $service a string giving for CAS retrieve Proxy ticket
	 * @param $flags options given to imap_open().
	 * @param $err_code an error code Possible values are PHPCAS_SERVICE_OK (on
	 * success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE, PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE,
	 * PHPCAS_SERVICE_PT_FAILURE, PHPCAS_SERVICE_NOT AVAILABLE.
	 * @param $err_msg an error message on failure
	 * @param $pt the Proxy Ticket (PT) retrieved from the CAS server to access the URL
	 * on success, FALSE on error).
	 *
	 * @return an IMAP stream on success, FALSE otherwise (in this later case, $err_code
	 * gives the reason why it failed and $err_msg contains an error message).
	 */
	public function serviceMail($url,$service,$flags,&$err_code,&$err_msg,&$pt)
	{
		phpCAS::traceBegin();
		// at first retrieve a PT
		$pt = $this->retrievePT($service,$err_code,$ptoutput);

		$stream = FALSE;

		// test if PT was retrieved correctly
		if ( !$pt ) {
			// note: $err_code and $err_msg are filled by CASClient::retrievePT()
			phpCAS::trace('PT was not retrieved correctly');
		} else {
			phpCAS::trace('opening IMAP URL `'.$url.'\'...');
			$stream = @imap_open($url,$this->getUser(),$pt,$flags);
			if ( !$stream ) {
				phpCAS::trace('could not open URL');
				$err_code = PHPCAS_SERVICE_NOT_AVAILABLE;
				// give an error message
				$err_msg = sprintf($this->getString(CAS_STR_SERVICE_UNAVAILABLE),
				$url,
				var_export(imap_errors(),TRUE));
				$pt = FALSE;
				$stream = FALSE;
			} else {
				phpCAS::trace('ok');
			}
		}

		phpCAS::traceEnd($stream);
		return $stream;
	}

	/** @} */

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                  PROXIED CLIENT FEATURES (CAS 2.0)                 XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	// ########################################################################
	//  PT
	// ########################################################################
	/**
	* @addtogroup internalProxied
	* @{
	*/

	/**
	 * the Proxy Ticket provided in the URL of the request if present
	 * (empty otherwise). Written by CASClient::CASClient(), read by
	 * CASClient::getPT() and CASClient::hasPGT().
	 *
	 * @hideinitializer
	 */
	private  $_pt = '';

	/**
	 * This method returns the Proxy Ticket provided in the URL of the request.
	 * @return The proxy ticket.
	 */
	public function getPT()
	{
		//      return 'ST'.substr($this->_pt, 2);
		return $this->_pt;
	}

	/**
	 * This method stores the Proxy Ticket.
	 * @param $pt The Proxy Ticket.
	 */
	public function setPT($pt)
	{ $this->_pt = $pt; }

	/**
	 * This method tells if a Proxy Ticket was stored.
	 * @return TRUE if a Proxy Ticket has been stored.
	 */
	public function hasPT()
	{ return !empty($this->_pt); }
	
	
	/**
	 * This array will store a list of proxies in front of this application. This
	 * property will only be populated if this script is being proxied rather than
	 * accessed directly.
	 *
	 * It is set in CASClient::validatePT() and can be read by CASClient::getProxies()
	 * @access private
	 */
	private $_proxies = array();
	
	/**
	 * Answer an array of proxies that are sitting in front of this application.
	 *
	 * This method will only return a non-empty array if we have received and validated
	 * a Proxy Ticket.
	 * 
	 * @return array
	 * @access public
	 * @since 6/25/09
	 */
	public function getProxies () {
		return $this->_proxies;
	}
	
	/**
	 * Set the Proxy array, probably from persistant storage.
	 * 
	 * @param array $proxies
	 * @return void
	 * @access private
	 * @since 6/25/09
	 */
	private function setProxies ($proxies) {
		$this->_proxies = $proxies;
	}
	
	/**
	 * This method returns the SAML Ticket provided in the URL of the request.
	 * @return The SAML ticket.
	 */
	public function getSA()
	{ return 'ST'.substr($this->_sa, 2); }

	/**
	 * This method stores the SAML Ticket.
	 * @param $sa The SAML Ticket.
	 */
	public function setSA($sa)
	{ $this->_sa = $sa; }

	/**
	 * This method tells if a SAML Ticket was stored.
	 * @return TRUE if a SAML Ticket has been stored.
	 */
	public function hasSA()
	{ return !empty($this->_sa); }

	/** @} */
	// ########################################################################
	//  PT VALIDATION
	// ########################################################################
	/**
	* @addtogroup internalProxied
	* @{
	*/

	/**
	 * This method is used to validate a ST or PT; halt on failure
	 * Used for all CAS 2.0 validations
	 * @return bool TRUE when successfull, halt otherwise by calling CASClient::authError().
	 */
	public function validatePT(&$validate_url,&$text_response,&$tree_response)
	{
		phpCAS::traceBegin();
		phpCAS::trace($text_response);
		// build the URL to validate the ticket
		$validate_url = $this->getServerProxyValidateURL().'&ticket='.$this->getPT();

		if ( $this->isProxy() ) {
			// pass the callback url for CAS proxies
			$validate_url .= '&pgtUrl='.urlencode($this->getCallbackURL());
		}

		// open and read the URL
		if ( !$this->readURL($validate_url,array(),$headers,$text_response,$err_msg) ) {
			phpCAS::trace('could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')');
			$this->authError('PT not validated',
			$validate_url,
			TRUE/*$no_response*/);
		}

		// create new DOMDocument object
		$dom = new DOMDocument();
		// Fix possible whitspace problems
		$dom->preserveWhiteSpace = false;
		// read the response of the CAS server into a DOMDocument object
		if ( !($dom->loadXML($text_response))) {
			// read failed
			$this->authError('PT not validated',
			$validate_url,
			FALSE/*$no_response*/,
			TRUE/*$bad_response*/,
			$text_response);
		}

		// read the root node of the XML tree
		if ( !($tree_response = $dom->documentElement) ) {
			// read failed
			$this->authError('PT not validated',
			$validate_url,
			FALSE/*$no_response*/,
			TRUE/*$bad_response*/,
			$text_response);
		}
		// insure that tag name is 'serviceResponse'
		if ( $tree_response->localName != 'serviceResponse' ) {
			// bad root node
			$this->authError('PT not validated',
			$validate_url,
			FALSE/*$no_response*/,
			TRUE/*$bad_response*/,
			$text_response);
		}
		if ( $tree_response->getElementsByTagName("authenticationSuccess")->length != 0) {
			// authentication succeded, extract the user name
			$success_elements = $tree_response->getElementsByTagName("authenticationSuccess");
			if ( $success_elements->item(0)->getElementsByTagName("user")->length == 0) {
				// no user specified => error
				$this->authError('PT not validated',
				$validate_url,
				FALSE/*$no_response*/,
				TRUE/*$bad_response*/,
				$text_response);
			}

			$this->setUser(trim($success_elements->item(0)->getElementsByTagName("user")->item(0)->nodeValue));
			$this->readExtraAttributesCas20($success_elements);
			
			// Store the proxies we are sitting behind for authorization checking
			if ( sizeof($arr = $success_elements->item(0)->getElementsByTagName("proxy")) > 0) {
				foreach ($arr as $proxyElem) {
					phpCAS::trace("Storing Proxy: ".$proxyElem->nodeValue);
					$this->_proxies[] = trim($proxyElem->nodeValue);
				}
				$_SESSION['phpCAS']['proxies'] = $this->_proxies;
			}
			
		} else if ( $tree_response->getElementsByTagName("authenticationFailure")->length != 0) {
			// authentication succeded, extract the error code and message
			$auth_fail_list = $tree_response->getElementsByTagName("authenticationFailure");
			$this->authError('PT not validated',
			$validate_url,
			FALSE/*$no_response*/,
			FALSE/*$bad_response*/,
			$text_response,
			$auth_fail_list->item(0)->getAttribute('code')/*$err_code*/,
			trim($auth_fail_list->item(0)->nodeValue)/*$err_msg*/);
		} else {
			$this->authError('PT not validated',
			$validate_url,
			FALSE/*$no_response*/,
			TRUE/*$bad_response*/,
			$text_response);
		}

		$this->renameSession($this->getPT());
		// at this step, PT has been validated and $this->_user has been set,

		phpCAS::traceEnd(TRUE);
		return TRUE;
	}

	/** @} */

	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
	// XX                                                                    XX
	// XX                               MISC                                 XX
	// XX                                                                    XX
	// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

	/**
	 * @addtogroup internalMisc
	 * @{
	 */

	// ########################################################################
	//  URL
	// ########################################################################
	/**
	* the URL of the current request (without any ticket CGI parameter). Written
	* and read by CASClient::getURL().
	*
	* @hideinitializer
	*/
	private $_url = '';

	/**
	 * This method returns the URL of the current request (without any ticket
	 * CGI parameter).
	 *
	 * @return The URL
	 */
	private function getURL()
	{
		phpCAS::traceBegin();
		// the URL is built when needed only
		if ( empty($this->_url) ) {
			$final_uri = '';
			// remove the ticket if present in the URL
			$final_uri = ($this->isHttps()) ? 'https' : 'http';
			$final_uri .= '://';

			$final_uri .= $this->getServerUrl();
			$request_uri	= explode('?', $_SERVER['REQUEST_URI'], 2);
			$final_uri		.= $request_uri[0];
				
			if (isset($request_uri[1]) && $request_uri[1])
			{
				$query_string	= $this->removeParameterFromQueryString('ticket', $request_uri[1]);

				// If the query string still has anything left, append it to the final URI
				if ($query_string !== '')
				$final_uri	.= "?$query_string";

			}
				
			phpCAS::trace("Final URI: $final_uri");
			$this->setURL($final_uri);
		}
		phpCAS::traceEnd($this->_url);
		return $this->_url;
	}

	/**
	 * Try to figure out the server URL with possible Proxys / Ports etc.
	 * @return Server URL with domain:port
	 */
	private function getServerUrl(){
		$server_url = '';
		if(!empty($_SERVER['HTTP_X_FORWARDED_HOST'])){
			$server_url = $_SERVER['HTTP_X_FORWARDED_HOST'];
		}else if(!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])){
			$server_url = $_SERVER['HTTP_X_FORWARDED_SERVER'];
		}else{
			if (empty($_SERVER['SERVER_NAME'])) {
				$server_url = $_SERVER['HTTP_HOST'];
			} else {
				$server_url = $_SERVER['SERVER_NAME'];
			}
		}
		if (!strpos($server_url, ':')) {
			if ( ($this->isHttps() && $_SERVER['SERVER_PORT']!=443)
			|| (!$this->isHttps() && $_SERVER['SERVER_PORT']!=80) ) {
				$server_url .= ':';
				$server_url .= $_SERVER['SERVER_PORT'];
			}
		}
		return $server_url;
	}



	/**
	 * Removes a parameter from a query string
	 *
	 * @param string $parameterName
	 * @param string $queryString
	 * @return string
	 *
	 * @link http://stackoverflow.com/questions/1842681/regular-expression-to-remove-one-parameter-from-query-string
	 */
	private function removeParameterFromQueryString($parameterName, $queryString)
	{
		$parameterName	= preg_quote($parameterName);
		return preg_replace("/&$parameterName(=[^&]*)?|^$parameterName(=[^&]*)?&?/", '', $queryString);
	}


	/**
	 * This method sets the URL of the current request
	 *
	 * @param $url url to set for service
	 */
	public function setURL($url)
	{
		$this->_url = $url;
	}

	// ########################################################################
	//  AUTHENTICATION ERROR HANDLING
	// ########################################################################
	/**
	* This method is used to print the HTML output when the user was not authenticated.
	*
	* @param $failure the failure that occured
	* @param $cas_url the URL the CAS server was asked for
	* @param $no_response the response from the CAS server (other
	* parameters are ignored if TRUE)
	* @param $bad_response bad response from the CAS server ($err_code
	* and $err_msg ignored if TRUE)
	* @param $cas_response the response of the CAS server
	* @param $err_code the error code given by the CAS server
	* @param $err_msg the error message given by the CAS server
	*/
	private function authError($failure,$cas_url,$no_response,$bad_response='',$cas_response='',$err_code='',$err_msg='')
	{
		phpCAS::traceBegin();

		$this->printHTMLHeader($this->getString(CAS_STR_AUTHENTICATION_FAILED));
		printf($this->getString(CAS_STR_YOU_WERE_NOT_AUTHENTICATED),htmlentities($this->getURL()),$_SERVER['SERVER_ADMIN']);
		phpCAS::trace('CAS URL: '.$cas_url);
		phpCAS::trace('Authentication failure: '.$failure);
		if ( $no_response ) {
			phpCAS::trace('Reason: no response from the CAS server');
		} else {
			if ( $bad_response ) {
				phpCAS::trace('Reason: bad response from the CAS server');
			} else {
				switch ($this->getServerVersion()) {
					case CAS_VERSION_1_0:
						phpCAS::trace('Reason: CAS error');
						break;
					case CAS_VERSION_2_0:
						if ( empty($err_code) )
						phpCAS::trace('Reason: no CAS error');
						else
						phpCAS::trace('Reason: ['.$err_code.'] CAS error: '.$err_msg);
						break;
				}
			}
			phpCAS::trace('CAS response: '.$cas_response);
		}
		$this->printHTMLFooter();
		phpCAS::traceExit();

		if ($this->_exitOnAuthError)
		exit();
	}

	/** @} */
}

?>
