<?php

/**
 * Holds SAML settings for the SamlResponse and SamlAuthRequest classes.
 *
 * These settings need to be filled in by the user prior to being used.
 */
class OneLogin_Saml_Settings
{
    const NAMEID_EMAIL_ADDRESS                 = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
    const NAMEID_X509_SUBJECT_NAME             = 'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName';
    const NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME = 'urn:oasis:names:tc:SAML:1.1:nameid-format:WindowsDomainQualifiedName';
    const NAMEID_KERBEROS   = 'urn:oasis:names:tc:SAML:2.0:nameid-format:kerberos';
    const NAMEID_ENTITY     = 'urn:oasis:names:tc:SAML:2.0:nameid-format:entity';
    const NAMEID_TRANSIENT  = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
    const NAMEID_PERSISTENT = 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent';

    /**
     * The URL to submit SAML authentication requests to.
     * @var string
     */
    public $idpSingleSignOnUrl = '';

    /**
     * The URL to submit SAML Logout Request to.
     * @var string
     */
    public $idpSingleLogOutUrl = '';

    /**
     * The x509 certificate used to authenticate the request.
     * @var string
     */
    public $idpPublicCertificate = '';

    /**
     * The URL where to the SAML Response/SAML Assertion will be posted.
     * @var string
     */
    public $spReturnUrl = '';

    /**
     * The name of the application.
     * @var string
     */
    public $spIssuer = 'php-saml';

    /**
     * Specifies what format to return the authentication token, i.e, the email address.
     * @var string
     */
    public $requestedNameIdFormat = self::NAMEID_EMAIL_ADDRESS;

    /**
     * Return an Array with the values (compatibility with the new version)
     */
    public function getValues()
    {
        $values = array();

        $values['sp'] = array();
        $values['sp']['entityId'] = $this->spIssuer;
        $values['sp']['assertionConsumerService'] = array(
            'url' => $this->spReturnUrl,
        );
        $values['sp']['NameIDFormat'] = $this->requestedNameIdFormat;

        $values['idp'] = array();
        $values['idp']['entityId'] = $this->idpSingleSignOnUrl;
        $values['idp']['singleSignOnService'] = array(
            'url' => $this->idpSingleSignOnUrl,
        );
        $values['idp']['singleLogoutService'] = array(
            'url' => $this->idpSingleLogOutUrl,
        );
        $values['idp']['x509cert'] = $this->idpPublicCertificate;

        return $values;
    }
}
