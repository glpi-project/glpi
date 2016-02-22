<?php

class OneLogin_Saml_AuthRequest
{

    /**
     * @var OneLogin_Saml2_Auth object
     */
    protected $auth;

    /**
     * Constructs the OneLogin_Saml2_Auth, initializing
     * the SP SAML instance.
     *
     * @param OneLogin_Saml2_Settings $settings Settings
     */
    public function __construct($settings)
    {
        $this->auth = new OneLogin_Saml2_Auth($settings);
    }

    /**
     * Obtains the SSO URL containing the AuthRequest
     * message deflated.
     *
     * @param OneLogin_Saml2_Settings $settings Settings
     */
    public function getRedirectUrl($returnTo = null)
    {
        $settings = $this->auth->getSettings();
        $authnRequest = new OneLogin_Saml2_AuthnRequest($settings);
        $parameters = array('SAMLRequest' => $authnRequest->getRequest());
        if (!empty($returnTo)) {
            $parameters['RelayState'] = $returnTo;
        } else {
            $parameters['RelayState'] = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();
        }
        $url = OneLogin_Saml2_Utils::redirect($this->auth->getSSOurl(), $parameters, true);
        return $url;
    }

    protected function _generateUniqueID()
    {
        return OneLogin_Saml2_Utils::generateUniqueID();
    }

    protected function _getTimestamp()
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = strftime("%Y-%m-%dT%H:%M:%SZ");
        date_default_timezone_set($defaultTimezone);
        return $timestamp;
    }
}
