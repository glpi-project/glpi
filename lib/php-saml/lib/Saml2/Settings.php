<?php
 
/**
 * Configuration of the OneLogin PHP Toolkit
 *
 */

class OneLogin_Saml2_Settings
{
    /**
     * List of paths.
     *
     * @var array
     */
    private $_paths = array();

    /**
     * Strict. If active, PHP Toolkit will reject unsigned or unencrypted messages 
     * if it expects them signed or encrypted. If not, the messages will be accepted
     * and some security issues will be also relaxed.
     *
     * @var boolean
     */
    private $_strict = false;

    /**
     * Activate debug mode
     *
     * @var boolean
     */
    private $_debug = false;

    /**
     * SP data.
     *
     * @var array
     */
    private $_sp = array();

    /**
     * IdP data.
     *
     * @var array
     */
    private $_idp = array();

    /**
     * Security Info related to the SP.
     *
     * @var array
     */
    private $_security = array();

    /**
     * Setting contacts.
     *
     * @var array
     */
    private $_contacts = array();

    /**
     * Setting organization.
     *
     * @var array
     */
    private $_organization = array();

    /**
     * Setting errors.
     *
     * @var array
     */
    private $_errors = array();

    /**
     * Setting errors.
     *
     * @var array
     */
    private $_spValidationOnly = false;

    /**
     * Initializes the settings:
     * - Sets the paths of the different folders
     * - Loads settings info from settings file or array/object provided
     *
     * @param array|object $settings SAML Toolkit Settings
     * 
     * @exceptions Throws error exception if any settings parameter is invalid
     */
    public function __construct($settings = null, $spValidationOnly = false)
    {
        $this->_spValidationOnly = $spValidationOnly;
        $this->_loadPaths();

        if (!isset($settings)) {
            if (!$this->_loadSettingsFromFile()) {
                throw new OneLogin_Saml2_Error(
                    'Invalid file settings: %s',
                    OneLogin_Saml2_Error::SETTINGS_INVALID,
                    array(implode(', ', $this->_errors))
                );
            }
            $this->_addDefaultValues();
        } else if (is_array($settings)) {
            if (!$this->_loadSettingsFromArray($settings)) {
                throw new OneLogin_Saml2_Error(
                    'Invalid array settings: %s',
                    OneLogin_Saml2_Error::SETTINGS_INVALID,
                    array(implode(', ', $this->_errors))
                );
            }
        } else {
            if (!$this->_loadSettingsFromArray($settings->getValues())) {
                throw new OneLogin_Saml2_Error(
                    'Invalid array settings: %s',
                    OneLogin_Saml2_Error::SETTINGS_INVALID,
                    array(implode(', ', $this->_errors))
                );
            }
        }

        $this->formatIdPCert();
        $this->formatSPCert();
        $this->formatSPKey();
    }

    /**
     * Sets the paths of the different folders
     */
    private function _loadPaths()
    {
        $basePath = dirname(dirname(dirname(__FILE__))).'/';
        $this->_paths = array (
            'base' => $basePath,
            'config' => $basePath,
            'cert' => $basePath.'certs/',
            'lib' => $basePath.'lib/',
            'extlib' => $basePath.'extlib/'
        );

        if (defined('ONELOGIN_CUSTOMPATH')) {
            $this->_paths['config'] = ONELOGIN_CUSTOMPATH;
            $this->_paths['cert'] = ONELOGIN_CUSTOMPATH.'certs/';
        }
    }

    /**
     * Returns base path.
     *
     * @return string  The base toolkit folder path
     */
    public function getBasePath()
    {
        return $this->_paths['base'];
    }

    /**
     * Returns cert path.
     *
     * @return string The cert folder path
     */
    public function getCertPath()
    {
        return $this->_paths['cert'];
    }

    /**
     * Returns config path.
     *
     * @return string The config folder path
     */
    public function getConfigPath()
    {
        return $this->_paths['config'];
    }

    /**
     * Returns lib path.
     *
     * @return string The library folder path
     */
    public function getLibPath()
    {
        return $this->_paths['lib'];
    }

    /**
     * Returns external lib path.
     *
     * @return string  The external library folder path
     */
    public function getExtLibPath()
    {
        return $this->_paths['extlib'];
    }

    /**
     * Returns schema path.
     *
     * @return string  The external library folder path
     */
    public function getSchemasPath()
    {
        return $this->_paths['lib'].'schemas/';
    }

    /**
     * Loads settings info from a settings Array
     *
     * @param array $settings SAML Toolkit Settings
     * 
     * @return boolean  True if the settings info is valid
     */
    private function _loadSettingsFromArray($settings)
    {
        if (isset($settings['sp'])) {
            $this->_sp = $settings['sp'];
        }
        if (isset($settings['idp'])) {
            $this->_idp = $settings['idp'];
        }

        $errors = $this->checkSettings($settings);
        if (empty($errors)) {
            $this->_errors = array();

            if (isset($settings['strict'])) {
                $this->_strict = $settings['strict'];
            }
            if (isset($settings['debug'])) {
                $this->_debug = $settings['debug'];
            }

            if (isset($settings['security'])) {
                $this->_security = $settings['security'];
            }

            if (isset($settings['contactPerson'])) {
                $this->_contacts = $settings['contactPerson'];
            }

            if (isset($settings['organization'])) {
                $this->_organization = $settings['organization'];
            }

            $this->_addDefaultValues();
            return true;
        } else {
            $this->_errors = $errors;
            return false;
        }
    }

    /**
     * Loads settings info from the settings file
     *
     * @return boolean  True if the settings info is valid
     */
    private function _loadSettingsFromFile()
    {
        $filename = $this->getConfigPath().'settings.php';

        if (!file_exists($filename)) {
            throw new OneLogin_Saml2_Error(
                'Settings file not found: %s',
                OneLogin_Saml2_Error::SETTINGS_FILE_NOT_FOUND,
                array($filename)
            );
        }

        include $filename;

        // Add advance_settings if exists

        $advancedFilename = $this->getConfigPath().'advanced_settings.php';

        if (file_exists($advancedFilename)) {
            include $advancedFilename;
            $settings = array_merge($settings, $advancedSettings);
        }


        return $this->_loadSettingsFromArray($settings);
    }

    /**
     * Add default values if the settings info is not complete
     */
    private function _addDefaultValues()
    {
        if (!isset($this->_sp['assertionConsumerService']['binding'])) {
            $this->_sp['assertionConsumerService']['binding'] = OneLogin_Saml2_Constants::BINDING_HTTP_POST;
        }
        if (isset($this->_sp['singleLogoutService']) && !isset($this->_sp['singleLogoutService']['binding'])) {
            $this->_sp['singleLogoutService']['binding'] = OneLogin_Saml2_Constants::BINDING_HTTP_REDIRECT;
        }

        // Related to nameID
        if (!isset($this->_sp['NameIDFormat'])) {
            $this->_sp['NameIDFormat'] = OneLogin_Saml2_Constants::NAMEID_UNSPECIFIED;
        }
        if (!isset($this->_security['nameIdEncrypted'])) {
            $this->_security['nameIdEncrypted'] = false;
        }
        if (!isset($this->_security['requestedAuthnContext'])) {
            $this->_security['requestedAuthnContext'] = true;
        }

        // sign provided
        if (!isset($this->_security['authnRequestsSigned'])) {
            $this->_security['authnRequestsSigned'] = false;
        }
        if (!isset($this->_security['logoutRequestSigned'])) {
            $this->_security['logoutRequestSigned'] = false;
        }
        if (!isset($this->_security['logoutResponseSigned'])) {
            $this->_security['logoutResponseSigned'] = false;
        }
        if (!isset($this->_security['signMetadata'])) {
            $this->_security['signMetadata'] = false;
        }

        // sign expected
        if (!isset($this->_security['wantMessagesSigned'])) {
            $this->_security['wantMessagesSigned'] = false;
        }
        if (!isset($this->_security['wantAssertionsSigned'])) {
            $this->_security['wantAssertionsSigned'] = false;
        }

        // encrypt expected
        if (!isset($this->_security['wantAssertionsEncrypted'])) {
            $this->_security['wantAssertionsEncrypted'] = false;
        }
        if (!isset($this->_security['wantNameIdEncrypted'])) {
            $this->_security['wantNameIdEncrypted'] = false;
        }

        // XML validation
        if (!isset($this->_security['wantXMLValidation'])) {
            $this->_security['wantXMLValidation'] = true;
        }

        // Algorithm
        if (!isset($this->_security['signatureAlgorithm'])) {
            $this->_security['signatureAlgorithm'] = XMLSecurityKey::RSA_SHA1;
        }

        // Certificates / Private key /Fingerprint
        if (!isset($this->_idp['x509cert'])) {
            $this->_idp['x509cert'] = '';
        }
        if (!isset($this->_idp['certFingerprint'])) {
            $this->_idp['certFingerprint'] = '';
        }
        if (!isset($this->_idp['certFingerprintAlgorithm'])) {
            $this->_idp['certFingerprintAlgorithm'] = 'sha1';
        }

        if (!isset($this->_sp['x509cert'])) {
            $this->_sp['x509cert'] = '';
        }
        if (!isset($this->_sp['privateKey'])) {
            $this->_sp['privateKey'] = '';
        }
    }

    /**
     * Checks the settings info.
     *
     * @param array $settings Array with settings data
     *
     * @return array $errors  Errors found on the settings data
     */
    public function checkSettings($settings)
    {
        assert('is_array($settings)');

        if (!is_array($settings) || empty($settings)) {
            $errors = array('invalid_syntax');
        } else {
            $errors = array();
            if (!$this->_spValidationOnly) {
                $idpErrors = $this->checkIdPSettings($settings);
                $errors = array_merge($idpErrors, $errors);
            }
            $spErrors = $this->checkSPSettings($settings);
            $errors = array_merge($spErrors, $errors);
        }

        return $errors;
    }

    /**
     * Checks the IdP settings info.
     *
     * @param array $settings Array with settings data
     *
     * @return array $errors  Errors found on the IdP settings data
     */
    public function checkIdPSettings($settings)
    {
        assert('is_array($settings)');

        if (!is_array($settings) || empty($settings)) {
            return array('invalid_syntax');
        }

        $errors = array();

        if (!isset($settings['idp']) || empty($settings['idp'])) {
            $errors[] = 'idp_not_found';
        } else {
            $idp = $settings['idp'];
            if (!isset($idp['entityId']) || empty($idp['entityId'])) {
                $errors[] = 'idp_entityId_not_found';
            }

            if (!isset($idp['singleSignOnService'])
                || !isset($idp['singleSignOnService']['url'])
                || empty($idp['singleSignOnService']['url'])
            ) {
                $errors[] = 'idp_sso_not_found';
            } else if (!filter_var($idp['singleSignOnService']['url'], FILTER_VALIDATE_URL)) {
                $errors[] = 'idp_sso_url_invalid';
            }

            if (isset($idp['singleLogoutService'])
                && isset($idp['singleLogoutService']['url'])
                && !empty($idp['singleLogoutService']['url'])
                && !filter_var($idp['singleLogoutService']['url'], FILTER_VALIDATE_URL)
            ) {
                $errors[] = 'idp_slo_url_invalid';
            }

            if (isset($settings['security'])) {
                $security = $settings['security'];

                $existsX509 = isset($idp['x509cert']) && !empty($idp['x509cert']);
                $existsFingerprint = isset($idp['certFingerprint']) && !empty($idp['certFingerprint']);
                if (((isset($security['wantAssertionsSigned']) && $security['wantAssertionsSigned'] == true)
                    || (isset($security['wantMessagesSigned']) && $security['wantMessagesSigned'] == true))
                    && !($existsX509 || $existsFingerprint)
                ) {
                    $errors[] = 'idp_cert_or_fingerprint_not_found_and_required';
                }
                if ((isset($security['nameIdEncrypted']) && $security['nameIdEncrypted'] == true)
                    && !($existsX509)
                ) {
                    $errors[] = 'idp_cert_not_found_and_required';
                }
            }
        }

        return $errors;
    }

    /**
     * Checks the SP settings info.
     *
     * @param array $settings Array with settings data
     *
     * @return array $errors  Errors found on the SP settings data
     */
    public function checkSPSettings($settings)
    {
        assert('is_array($settings)');

        if (!is_array($settings) || empty($settings)) {
            return array('invalid_syntax');
        }

        $errors = array();

        if (!isset($settings['sp']) || empty($settings['sp'])) {
            $errors[] = 'sp_not_found';
        } else {
            $sp = $settings['sp'];
            $security = array();
            if (isset($settings['security'])) {
                $security = $settings['security'];
            }

            if (!isset($sp['entityId']) || empty($sp['entityId'])) {
                $errors[] = 'sp_entityId_not_found';
            }

            if (!isset($sp['assertionConsumerService'])
                || !isset($sp['assertionConsumerService']['url'])
                || empty($sp['assertionConsumerService']['url'])
            ) {
                $errors[] = 'sp_acs_not_found';
            } else if (!filter_var($sp['assertionConsumerService']['url'], FILTER_VALIDATE_URL)) {
                $errors[] = 'sp_acs_url_invalid';
            }

            if (isset($sp['singleLogoutService'])
                && isset($sp['singleLogoutService']['url'])
                && !filter_var($sp['singleLogoutService']['url'], FILTER_VALIDATE_URL)
            ) {
                $errors[] = 'sp_sls_url_invalid';
            }

            if (isset($security['signMetadata']) && is_array($security['signMetadata'])) {
                if (!isset($security['signMetadata']['keyFileName'])
                    || !isset($security['signMetadata']['certFileName'])
                ) {
                    $errors[] = 'sp_signMetadata_invalid';
                }
            }

            if (((isset($security['authnRequestsSigned']) && $security['authnRequestsSigned'] == true)
                || (isset($security['logoutRequestSigned']) && $security['logoutRequestSigned'] == true)
                || (isset($security['logoutResponseSigned']) && $security['logoutResponseSigned'] == true)
                || (isset($security['wantAssertionsEncrypted']) && $security['wantAssertionsEncrypted'] == true)
                || (isset($security['wantNameIdEncrypted']) && $security['wantNameIdEncrypted'] == true))
                && !$this->checkSPCerts()
            ) {
                $errors[] = 'sp_certs_not_found_and_required';
            }
        }

        if (isset($settings['contactPerson'])) {
            $types = array_keys($settings['contactPerson']);
            $validTypes = array('technical', 'support', 'administrative', 'billing', 'other');
            foreach ($types as $type) {
                if (!in_array($type, $validTypes)) {
                    $errors[] = 'contact_type_invalid';
                    break;
                }
            }

            foreach ($settings['contactPerson'] as $type => $contact) {
                if (!isset($contact['givenName']) || empty($contact['givenName'])
                    || !isset($contact['emailAddress']) || empty($contact['emailAddress'])
                ) {
                    $errors[] = 'contact_not_enought_data';
                    break;
                }
            }
        }

        if (isset($settings['organization'])) {
            foreach ($settings['organization'] as $organization) {
                if (!isset($organization['name']) || empty($organization['name'])
                    || !isset($organization['displayname']) || empty($organization['displayname'])
                    || !isset($organization['url']) || empty($organization['url'])
                ) {
                    $errors[] = 'organization_not_enought_data';
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Checks if the x509 certs of the SP exists and are valid.
     *
     * @return boolean
     */
    public function checkSPCerts()
    {
        $key = $this->getSPkey();
        $cert = $this->getSPcert();
        return (!empty($key) && !empty($cert));
    }

    /**
     * Returns the x509 private key of the SP.
     *
     * @return string SP private key
     */
    public function getSPkey()
    {
        $key = null;
        if (isset($this->_sp['privateKey']) && !empty($this->_sp['privateKey'])) {
            $key = $this->_sp['privateKey'];
        } else {
            $keyFile = $this->_paths['cert'].'sp.key';

            if (file_exists($keyFile)) {
                $key = file_get_contents($keyFile);
            }
        }
        return $key;
    }

    /**
     * Returns the x509 public cert of the SP.
     *
     * @return string SP public cert
     */
    public function getSPcert()
    {
        $cert = null;

        if (isset($this->_sp['x509cert']) && !empty($this->_sp['x509cert'])) {
            $cert = $this->_sp['x509cert'];
        } else {
            $certFile = $this->_paths['cert'].'sp.crt';

            if (file_exists($certFile)) {
                $cert = file_get_contents($certFile);
            }
        }
        return $cert;
    }

    /**
     * Gets the IdP data.
     *
     * @return array  IdP info
     */
    public function getIdPData()
    {
        return $this->_idp;
    }

    /**
     * Gets the SP data.
     *
     * @return array  SP info
     */
    public function getSPData()
    {
        return $this->_sp;
    }

    /**
     * Gets security data.
     *
     * @return array  SP info
     */
    public function getSecurityData()
    {
        return $this->_security;
    }

    /**
     * Gets contact data.
     *
     * @return array  SP info
     */
    public function getContacts()
    {
        return $this->_contacts;
    }

    /**
     * Gets organization data.
     *
     * @return array  SP info
     */
    public function getOrganization()
    {
        return $this->_organization;
    }

    /**
     * Gets the SP metadata. The XML representation.
     *
     * @return string  SP metadata (xml)
     */
    public function getSPMetadata()
    {
        $metadata = OneLogin_Saml2_Metadata::builder($this->_sp, $this->_security['authnRequestsSigned'], $this->_security['wantAssertionsSigned'], null, null, $this->getContacts(), $this->getOrganization());

        $cert = $this->getSPcert();

        if (!empty($cert)) {
            $metadata = OneLogin_Saml2_Metadata::addX509KeyDescriptors($metadata, $cert);
        }

        //Sign Metadata
        if (isset($this->_security['signMetadata']) && $this->_security['signMetadata'] !== false) {
            if ($this->_security['signMetadata'] === true) {
                $keyMetadata = $this->getSPkey();
                $certMetadata = $cert;

                if (!$keyMetadata) {
                    throw new OneLogin_Saml2_Error(
                        'Private key not found.',
                        OneLogin_Saml2_Error::PRIVATE_KEY_FILE_NOT_FOUND
                    );
                }
                
                if (!$certMetadata) {
                    throw new OneLogin_Saml2_Error(
                        'Public cert file not found.',
                        OneLogin_Saml2_Error::PUBLIC_CERT_FILE_NOT_FOUND
                    );
                }
            } else {
                if (!isset($this->_security['signMetadata']['keyFileName'])
                    || !isset($this->_security['signMetadata']['certFileName'])
                ) {
                    throw new OneLogin_Saml2_Error(
                        'Invalid Setting: signMetadata value of the sp is not valid',
                        OneLogin_Saml2_Error::SETTINGS_INVALID_SYNTAX
                    );
                }
                $keyFileName = $this->_security['signMetadata']['keyFileName'];
                $certFileName = $this->_security['signMetadata']['certFileName'];

                $keyMetadataFile = $this->_paths['cert'].$keyFileName;
                $certMetadataFile = $this->_paths['cert'].$certFileName;
            

                if (!file_exists($keyMetadataFile)) {
                    throw new OneLogin_Saml2_Error(
                        'Private key file not found: %s',
                        OneLogin_Saml2_Error::PRIVATE_KEY_FILE_NOT_FOUND,
                        array($keyMetadataFile)
                    );
                }
                
                if (!file_exists($certMetadataFile)) {
                    throw new OneLogin_Saml2_Error(
                        'Public cert file not found: %s',
                        OneLogin_Saml2_Error::PUBLIC_CERT_FILE_NOT_FOUND,
                        array($certMetadataFile)
                    );
                }
                $keyMetadata = file_get_contents($keyMetadataFile);
                $certMetadata = file_get_contents($certMetadataFile);
            }

            $metadata = OneLogin_Saml2_Metadata::signMetadata($metadata, $keyMetadata, $certMetadata);
        }
        return $metadata;
    }

    /**
     * Validates an XML SP Metadata.
     *
     * @param string $xml Metadata's XML that will be validate
     *
     * @return Array The list of found errors
     */
    public function validateMetadata($xml)
    {
        assert('is_string($xml)');

        $errors = array();
        $res = OneLogin_Saml2_Utils::validateXML($xml, 'saml-schema-metadata-2.0.xsd', $this->_debug);
        if (!$res instanceof DOMDocument) {
            $errors[] = $res;
        } else {
            $dom = $res;
            $element = $dom->documentElement;
            if ($element->tagName !== 'md:EntityDescriptor') {
                $errors[] = 'noEntityDescriptor_xml';
            } else {
                $validUntil = $cacheDuration = $expireTime = null;

                if ($element->hasAttribute('validUntil')) {
                    $validUntil = OneLogin_Saml2_Utils::parseSAML2Time($element->getAttribute('validUntil'));
                }
                if ($element->hasAttribute('cacheDuration')) {
                    $cacheDuration = $element->getAttribute('cacheDuration');
                }

                $expireTime = OneLogin_Saml2_Utils::getExpireTime($cacheDuration, $validUntil);
                if (isset($expireTime) && time() > $expireTime) {
                    $errors[] = 'expired_xml';
                }
            }
        }

        // TODO: Support Metadata Sign Validation

        return $errors;
    }

    /**
     * Formats the IdP cert.
     */
    public function formatIdPCert()
    {
        if (isset($this->_idp['x509cert'])) {
            $this->_idp['x509cert'] = OneLogin_Saml2_Utils::formatCert($this->_idp['x509cert']);
        }
    }

    /**
     * Formats the SP cert.
     */
    public function formatSPCert()
    {
        if (isset($this->_sp['x509cert'])) {
            $this->_sp['x509cert'] = OneLogin_Saml2_Utils::formatCert($this->_sp['x509cert']);
        }
    }

    /**
     * Formats the SP private key.
     */
    public function formatSPKey()
    {
        if (isset($this->_sp['privateKey'])) {
            $this->_sp['privateKey'] = OneLogin_Saml2_Utils::formatPrivateKey($this->_sp['privateKey']);
        }
    }

    /**
     * Returns an array with the errors, the array is empty when the settings is ok.
     *
     * @return array Errors
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Activates or deactivates the strict mode.
     *
     * @param boolean $value Strict parameter
     */
    public function setStrict($value)
    {
        assert('is_bool($value)');

        $this->_strict = $value;
    }

    /**
     * Returns if the 'strict' mode is active.
     *
     * @return boolean Strict parameter
     */
    public function isStrict()
    {
        return $this->_strict;
    }

    /**
     * Returns if the debug is active.
     *
     * @return boolean Debug parameter
     */
    public function isDebugActive()
    {
        return $this->_debug;
    }

    /**
     * Sets the IdP certificate.
     *
     * @param string $value IdP certificate
     */
    public function setIdPCert($cert)
    {
      $this->_idp['x509cert'] = $cert;
      $this->formatIdPCert();
    }
}
