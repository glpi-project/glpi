<?php
 
/**
 * Error class of OneLogin PHP Toolkit
 *
 * Defines the Error class
 */
class OneLogin_Saml2_Error extends Exception
{
    // Errors
    const SETTINGS_FILE_NOT_FOUND = 0;
    const SETTINGS_INVALID_SYNTAX = 1;
    const SETTINGS_INVALID = 2;
    const METADATA_SP_INVALID = 3;
    const SP_CERTS_NOT_FOUND = 4;
    const REDIRECT_INVALID_URL = 5;
    const PUBLIC_CERT_FILE_NOT_FOUND = 6;
    const PRIVATE_KEY_FILE_NOT_FOUND = 7;
    const SAML_RESPONSE_NOT_FOUND = 8;
    const SAML_LOGOUTMESSAGE_NOT_FOUND = 9;
    const SAML_LOGOUTREQUEST_INVALID = 10;
    const SAML_LOGOUTRESPONSE_INVALID  = 11;
    const SAML_SINGLE_LOGOUT_NOT_SUPPORTED = 12;
    
    /**
     * Constructor
     *
        * @param string  $msg  Describes the error.
        * @param integer $code The code error (defined in the error class).
        * @param array   $args Arguments used in the message that describes the error.
     */
    public function __construct($msg, $code = 0, $args = null)
    {
        assert('is_string($msg)');
        assert('is_int($code)');

        $message = OneLogin_Saml2_Utils::t($msg, $args);

        parent::__construct($message, $code);
    }
}
