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

/**
 * Provides support for performing web-requests via curl
 */
abstract class CAS_AbstractRequest
	implements CAS_RequestInterface
{

	protected $url = null;
	protected $cookies = array();
	protected $headers = array();
	protected $isPost = FALSE;
	protected $postBody = null;
	protected $caCertPath = null;
	private $sent = FALSE;
	private $responseHeaders = array();
	private $responseBody = null;
	private $errorMessage = '';

	/*********************************************************
	 * Configure the Request
	 *********************************************************/

	/**
	 * Set the URL of the Request
	 *
	 * @param string $url
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function setUrl ($url) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->url = $url;
	}

	/**
	 * Add a cookie to the request.
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function addCookie ($name, $value) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->cookies[$name] = $value;
	}

	/**
	 * Add an array of cookies to the request.
	 * The cookie array is of the form
	 *     array('cookie_name' => 'cookie_value', 'cookie_name2' => cookie_value2')
	 *
	 * @param array $cookies
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function addCookies (array $cookies) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->cookies = array_merge($this->cookies, $cookies);
	}

	/**
	 * Add a header string to the request.
	 *
	 * @param string $header
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function addHeader ($header) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->headers[] = $header;
	}

	/**
	 * Add an array of header strings to the request.
	 *
	 * @param array $headers
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function addHeaders (array $headers) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->headers = array_merge($this->headers, $headers);
	}

	/**
	 * Make the request a POST request rather than the default GET request.
	 *
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function makePost () {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->isPost = TRUE;
	}

	/**
	 * Add a POST body to the request
	 *
	 * @param string $body
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function setPostBody ($body) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);
		if (!$this->isPost)
			throw new CAS_OutOfSequenceException('Cannot add a POST body to a GET request, use makePost() first.');

		$this->postBody = $body;
	}

	/**
	 * Specify the path to an SSL CA certificate to validate the server with.
	 *
	 * @param string $sslCertPath
	 * @return void
	 * @throws CAS_OutOfSequenceException If called after the Request has been sent.
	 */
	public function setSslCaCert ($caCertPath) {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);

		$this->caCertPath = $caCertPath;
	}

	/*********************************************************
	 * 2. Send the Request
	 *********************************************************/

	/**
	 * Perform the request.
	 *
	 * @return boolean TRUE on success, FALSE on failure.
	 * @throws CAS_OutOfSequenceException If called multiple times.
	 */
	public function send () {
		if ($this->sent)
			throw new CAS_OutOfSequenceException('Request has already been sent cannot send again.');
		if (is_null($this->url) || !$this->url)
			throw new CAS_OutOfSequenceException('A url must be specified via setUrl() before the request can be sent.');

		$this->sent = true;
		return $this->_sendRequest();
	}

	/**
	 * Send the request and store the results.
	 *
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	abstract protected function _sendRequest ();

	/**
	 * Store the response headers.
	 *
	 * @param array $headers
	 * @return void
	 */
	protected function storeResponseHeaders (array $headers) {
		$this->responseHeaders = array_merge($this->responseHeaders, $headers);
	}

	/**
	 * Store a single response header to our array.
	 *
	 * @param string $header
	 * @return void
	 */
	protected function storeResponseHeader ($header) {
		$this->responseHeaders[] = $header;
	}

	/**
	 * Store the response body.
	 *
	 * @param string $body
	 * @return void
	 */
	protected function storeResponseBody ($body) {
		$this->responseBody = $body;
	}

	/**
	 * Add a string to our error message.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function storeErrorMessage ($message) {
		$this->errorMessage .= $message;
	}

	/*********************************************************
	 * 3. Access the response
	 *********************************************************/

	/**
	 * Answer the headers of the response.
	 *
	 * @return array An array of header strings.
	 * @throws CAS_OutOfSequenceException If called before the Request has been sent.
	 */
	public function getResponseHeaders () {
		if (!$this->sent)
			throw new CAS_OutOfSequenceException('Request has not been sent yet. Cannot '.__METHOD__);

		return $this->responseHeaders;
	}

	/**
	 * Answer the body of response.
	 *
	 * @return string
	 * @throws CAS_OutOfSequenceException If called before the Request has been sent.
	 */
	public function getResponseBody () {
		if (!$this->sent)
			throw new CAS_OutOfSequenceException('Request has not been sent yet. Cannot '.__METHOD__);

		return $this->responseBody;
	}

	/**
	 * Answer a message describing any errors if the request failed.
	 *
	 * @return string
	 * @throws CAS_OutOfSequenceException If called before the Request has been sent.
	 */
	public function getErrorMessage () {
		if (!$this->sent)
			throw new CAS_OutOfSequenceException('Request has not been sent yet. Cannot '.__METHOD__);

		return $this->errorMessage;
	}
}