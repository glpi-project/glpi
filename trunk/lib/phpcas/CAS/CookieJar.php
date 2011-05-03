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
 * This class provides access to service cookies and handles parsing of response
 * headers to pull out cookie values.
 *
 */
class CAS_CookieJar {

	private $_cookies;

	/**
	 * Create a new cookie jar by passing it a reference to an array in which it
	 * should store cookies.
	 *
	 * @param ref array $storageArray
	 * @return void
	 */
	public function __construct (array &$storageArray) {
		$this->_cookies =& $storageArray;
	}

	/**
	 * Store cookies for a web service request.
	 * Cookie storage is based on RFC 2965: http://www.ietf.org/rfc/rfc2965.txt
	 *
	 * @param string $request_url The URL that generated the response headers.
	 * @param array $response_headers An array of the HTTP response header strings.
	 *
	 * @return void
	 *
	 * @access private
	 */
	public function storeCookies ($request_url, $response_headers) {
		$urlParts = parse_url($request_url);
		$defaultDomain = $urlParts['host'];

		$cookies = $this->parseCookieHeaders($response_headers, $defaultDomain);

		// var_dump($cookies);
		foreach ($cookies as $cookie) {
			// Enforce the same-origin policy by verifying that the cookie
			// would match the url that is setting it
			if (!$this->cookieMatchesTarget($cookie, $urlParts))
				continue;

			// store the cookie
			$this->storeCookie($cookie);

			phpCAS::trace($cookie['name'].' -> '.$cookie['value']);
		}
	}

	/**
	 * Retrieve cookies applicable for a web service request.
	 * Cookie applicability is based on RFC 2965: http://www.ietf.org/rfc/rfc2965.txt
	 *
	 * @param string $request_url The url that the cookies will be for.
	 *
	 * @return array An array containing cookies. E.g. array('name' => 'val');
	 *
	 * @access private
	 */
	public function getCookies ($request_url) {
		if (!count($this->_cookies))
			return array();

		// If our request URL can't be parsed, no cookies apply.
		$target = parse_url($request_url);
		if ($target === FALSE)
			return array();

		$this->expireCookies();

		$matching_cookies = array();
		foreach ($this->_cookies as $key => $cookie) {
			if ($this->cookieMatchesTarget($cookie, $target)) {
				$matching_cookies[$cookie['name']] = $cookie['value'];
			}
		}
		return $matching_cookies;
	}


	/**
	 * Parse Cookies without PECL
	 * From the comments in http://php.net/manual/en/function.http-parse-cookie.php
	 * @param array $header 	An array of header lines.
	 * @param string $defaultDomain 	The domain to use if none is specified in the cookie.
	 * @return array of cookies
	 */
	protected function parseCookieHeaders( $header, $defaultDomain ) {
		phpCAS::traceBegin();
		$cookies = array();
		foreach( $header as $line ) {
			if( preg_match( '/^Set-Cookie2?: /i', $line ) ) {
				$cookies[] = $this->parseCookieHeader($line, $defaultDomain);
			}
		}

		phpCAS::traceEnd($cookies);
		return $cookies;
	}

	/**
	 * Parse a single cookie header line.
	 *
	 * Based on RFC2965 http://www.ietf.org/rfc/rfc2965.txt
	 *
	 * @param string $line The header line.
	 * @param string $defaultDomain The domain to use if none is specified in the cookie.
	 * @return array
	 */
	protected function parseCookieHeader ($line, $defaultDomain) {
		if (!$defaultDomain)
			throw new InvalidArgumentException('$defaultDomain was not provided.');

		// Set our default values
		$cookie = array(
			'domain' => $defaultDomain,
			'path' => '/',
			'secure' => false,
		);

		$line = preg_replace( '/^Set-Cookie2?: /i', '', trim( $line ) );

		// trim any trailing semicolons.
		$line = trim($line, ';');

		phpCAS::trace("Cookie Line: $line");

		// This implementation makes the assumption that semicolons will not
		// be present in quoted attribute values. While attribute values that
		// contain semicolons are allowed by RFC2965, they are hopefully rare
		// enough to ignore for our purposes.
		$attributeStrings = explode( ';', $line );

		foreach( $attributeStrings as $attributeString ) {
			// This implementation makes the assumption that equals symbols will not
			// be present in quoted attribute values. While attribute values that
			// contain equals symbols are allowed by RFC2965, they are hopefully rare
			// enough to ignore for our purposes.
			$attributeParts = explode( '=', $attributeString );

			$attributeName = trim($attributeParts[0]);
			$attributeNameLC = strtolower($attributeName);

			if (isset($attributeParts[1])) {
				$attributeValue = trim($attributeParts[1]);
				// Values may be quoted strings.
				if (strpos($attributeValue, '"') === 0) {
					$attributeValue = trim($attributeValue, '"');
					// unescape any escaped quotes:
					$attributeValue = str_replace('\"', '"', $attributeValue);
				}
			} else {
				$attributeValue = null;
			}

			switch ($attributeNameLC) {
				case 'expires':
					$cookie['expires'] = strtotime($attributeValue);
					break;
				case 'max-age':
					$cookie['max-age'] = (int)$attributeValue;
					// Set an expiry time based on the max-age
					if ($cookie['max-age'])
						$cookie['expires'] = time() + $cookie['max-age'];
					// If max-age is zero, then the cookie should be removed imediately
					// so set an expiry before now.
					else
						$cookie['expires'] = time() - 1;
					break;
				case 'secure':
					$cookie['secure'] = true;
					break;
				case 'domain':
				case 'path':
				case 'port':
				case 'version':
				case 'comment':
				case 'commenturl':
				case 'discard':
					$cookie[$attributeNameLC] = $attributeValue;
					break;
				default:
					$cookie['name'] = $attributeName;
					$cookie['value'] = $attributeValue;
			}
		}

		return $cookie;
	}

	/**
	 * Add, update, or remove a cookie.
	 *
	 * @param array $cookie A cookie array as created by parseCookieHeaders()
	 *
	 * @return void
	 *
	 * @access private
	 */
	protected function storeCookie ($cookie) {
		// Discard any old versions of this cookie.
		$this->discardCookie($cookie);
		$this->_cookies[] = $cookie;

	}

	/**
	 * Discard an existing cookie
	 *
	 * @param stdClass $cookie
	 *
	 * @return void
	 *
	 * @access private
	 */
	protected function discardCookie ($cookie) {
		if (!isset($cookie['domain']) || !isset($cookie['path']) || !isset($cookie['path']))
			throw new InvalidArgumentException('Invalid Cookie array passed.');

		foreach ($this->_cookies as $key => $old_cookie) {
			if ($cookie['domain'] == $old_cookie['domain']
			&& $cookie['path'] == $old_cookie['path']
			&& $cookie['name'] == $old_cookie['name'])
			{
				unset($this->_cookies[$key]);
			}
		}
	}

	/**
	 * Go through our stored cookies and remove any that are expired.
	 *
	 * @return void
	 *
	 * @access private
	 */
	protected function expireCookies () {
		foreach ($this->_cookies as $key => $cookie) {
			if (isset($cookie['expires']) && $cookie['expires'] < time()) {
				unset($this->_cookies[$key]);
			}
		}
	}

	/**
	 * Answer true if cookie is applicable to a target.
	 *
	 * @param array $cookie An array of cookie attributes.
	 * @param array $target An array of URL attributes as generated by parse_url().
	 *
	 * @return boolean
	 *
	 * @access private
	 */
	protected function cookieMatchesTarget ($cookie, $target) {
		if (!is_array($target))
			throw new InvalidArgumentException('$target must be an array of URL attributes as generated by parse_url().');

		// Verify that the scheme matches
		if ($cookie['secure'] && $target['scheme'] != 'https')
		return false;

		// Verify that the host matches
		// Match domain and mulit-host cookies
		if (strpos($cookie['domain'], '.') === 0) {
			// check that the target host a.b.c.edu is within .b.c.edu
			$pos = strripos($target['host'], $cookie['domain']);
			if (!$pos)
			return false;
			// verify that the cookie domain is the last part of the host.
			if ($pos + strlen($cookie['domain']) != strlen($target['host']))
			return false;
		}
		// If the cookie host doesn't begin with '.', the host must case-insensitive
		// match exactly
		else {
			if (strcasecmp($target['host'], $cookie['domain']) !== 0)
			return false;
		}

		// Verify that the port matches
		if (isset($cookie['ports']) && !in_array($target['port'], $cookie['ports']))
		return false;

		// Verify that the path matches
		if (strpos($target['path'], $cookie['path']) !== 0)
		return false;

		return true;
	}

}

?>