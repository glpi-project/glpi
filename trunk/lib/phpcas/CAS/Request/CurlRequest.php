<?php
/*
 * Copyright © 2003-2010, The ESUP-Portail consortium & the JA-SIG Collaborative.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *	   * Redistributions of source code must retain the above copyright notice,
 *		 this list of conditions and the following disclaimer.
 *	   * Redistributions in binary form must reproduce the above copyright notice,
 *		 this list of conditions and the following disclaimer in the documentation
 *		 and/or other materials provided with the distribution.
 *	   * Neither the name of the ESUP-Portail consortium & the JA-SIG
 *		 Collaborative nor the names of its contributors may be used to endorse or
 *		 promote products derived from this software without specific prior
 *		 written permission.

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

require_once dirname(__FILE__).'/RequestInterface.php';
require_once dirname(__FILE__).'/AbstractRequest.php';

/**
 * Provides support for performing web-requests via curl
 */
class CAS_CurlRequest
	extends CAS_AbstractRequest
	implements CAS_RequestInterface
{

	/**
	 * Set additional curl options
	 *
	 * @param array $options
	 * @return void
	 */
	public function setCurlOptions (array $options) {
		$this->curlOptions = $options;
	}
	private $curlOptions = array();

	/**
	 * Send the request and store the results.
	 *
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	protected function _sendRequest () {
		phpCAS::traceBegin();

		/*********************************************************
		 * initialize the CURL session
		 *********************************************************/
		$ch = curl_init($this->url);

		if (version_compare(PHP_VERSION,'5.1.3','>=')) {
			//only avaible in php5
			curl_setopt_array($ch, $this->curlOptions);
		} else {
			foreach ($this->curlOptions as $key => $value) {
				curl_setopt($ch, $key, $value);
			}
		}

		/*********************************************************
		 * Set SSL configuration
		 *********************************************************/
		if ($this->caCertPath) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_CAINFO, $this->caCertPath);
			phpCAS::trace('CURL: Set CURLOPT_CAINFO');
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}

		/*********************************************************
		 * Configure curl to capture our output.
		 *********************************************************/
		// return the CURL output into a variable
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// get the HTTP header with a callback
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, '_curlReadHeaders'));

		/*********************************************************
		 * Add cookie headers to our request.
		 *********************************************************/
		if (count($this->cookies)) {
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $this->cookies));
		}

		/*********************************************************
		 * Add any additional headers
		 *********************************************************/
		if (count($this->headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		}

		/*********************************************************
		 * Flag and Body for POST requests
		 *********************************************************/
		if ($this->isPost) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postBody);
		}

		/*********************************************************
		 * Perform the query
		 *********************************************************/
		$buf = curl_exec ($ch);
		if ( $buf === FALSE ) {
			phpCAS::trace('curl_exec() failed');
			$this->storeErrorMessage('CURL error #'.curl_errno($ch).': '.curl_error($ch));
			$res = FALSE;
		} else {
			$this->storeResponseBody($buf);
			phpCAS::trace("Response Body: \n".$buf."\n");
			$res = TRUE;

		}
		// close the CURL session
		curl_close ($ch);

		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * Internal method for capturing the headers from a curl request.
	 *
	 * @param handle $ch
	 * @param string $header
	 * @return void
	 */
	public function _curlReadHeaders ($ch, $header) {
		$this->storeResponseHeader($header);
		return strlen($header);
	}
}