<?php
 
/**
 * Metadata lib of OneLogin PHP Toolkit
 *
 */

class OneLogin_Saml2_Metadata
{
    const TIME_VALID = 172800;  // 2 days
    const TIME_CACHED = 604800; // 1 week

    /**
     * Generates the metadata of the SP based on the settings
     *
     * @param string    $sp            The SP data
     * @param string    $authnsign     authnRequestsSigned attribute
     * @param string    $wsign         wantAssertionsSigned attribute 
     * @param DateTime  $validUntil    Metadata's valid time
     * @param Timestamp $cacheDuration Duration of the cache in seconds
     * @param array     $contacts      Contacts info
     * @param array     $organization  Organization ingo
     *
     * @return string SAML Metadata XML
     */
    public static function builder($sp, $authnsign = false, $wsign = false, $validUntil = null, $cacheDuration = null, $contacts = array(), $organization = array(), $attributes = array())
    {

        if (!isset($validUntil)) {
            $validUntil =  time() + self::TIME_VALID;
        }
        $validUntilTime =  gmdate('Y-m-d\TH:i:s\Z', $validUntil);

        if (!isset($cacheDuration)) {
            $cacheDuration = self::TIME_CACHED;
        }

        $sls = '';

        if (isset($sp['singleLogoutService'])) {
            $sls = <<<SLS_TEMPLATE
        <md:SingleLogoutService Binding="{$sp['singleLogoutService']['binding']}"
                                Location="{$sp['singleLogoutService']['url']}" />

SLS_TEMPLATE;
        }

        if ($authnsign) {
            $strAuthnsign = 'true';
        } else {
            $strAuthnsign = 'false';
        }

        if ($wsign) {
            $strWsign = 'true';
        } else {
            $strWsign = 'false';
        }

        $strOrganization = '';
        if (!empty($organization)) {
            $organizationInfoNames = array();
            $organizationInfoDisplaynames = array();
            $organizationInfoUrls = array();
            foreach ($organization as $lang => $info) {
                $organizationInfoNames[] = <<<ORGANIZATION_NAME
       <md:OrganizationName xml:lang="{$lang}">{$info['name']}</md:OrganizationName>
ORGANIZATION_NAME;
                $organizationInfoDisplaynames[] = <<<ORGANIZATION_DISPLAY
       <md:OrganizationDisplayName xml:lang="{$lang}">{$info['displayname']}</md:OrganizationDisplayName>
ORGANIZATION_DISPLAY;
                $organizationInfoUrls[] = <<<ORGANIZATION_URL
       <md:OrganizationURL xml:lang="{$lang}">{$info['url']}</md:OrganizationURL>
ORGANIZATION_URL;
            }
            $orgData = implode("\n", $organizationInfoNames)."\n".implode("\n", $organizationInfoDisplaynames)."\n".implode("\n", $organizationInfoUrls);
            $strOrganization = <<<ORGANIZATIONSTR

    <md:Organization>
{$orgData}
    </md:Organization>
ORGANIZATIONSTR;
        }

        $strContacts = '';
        if (!empty($contacts)) {
            $contactsInfo = array();
            foreach ($contacts as $type => $info) {
                $contactsInfo[] = <<<CONTACT
    <md:ContactPerson contactType="{$type}">
        <md:GivenName>{$info['givenName']}</md:GivenName>
        <md:EmailAddress>{$info['emailAddress']}</md:EmailAddress>
    </md:ContactPerson>
CONTACT;
            }
            $strContacts = "\n".implode("\n", $contactsInfo);
        }

        $metadata = <<<METADATA_TEMPLATE
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="{$validUntilTime}"
                     cacheDuration="PT{$cacheDuration}S"
                     entityID="{$sp['entityId']}">
    <md:SPSSODescriptor AuthnRequestsSigned="{$strAuthnsign}" WantAssertionsSigned="{$strWsign}" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
{$sls}        <md:NameIDFormat>{$sp['NameIDFormat']}</md:NameIDFormat>
        <md:AssertionConsumerService Binding="{$sp['assertionConsumerService']['binding']}"
                                     Location="{$sp['assertionConsumerService']['url']}"
                                     index="1" />
    </md:SPSSODescriptor>{$strOrganization}{$strContacts}
</md:EntityDescriptor>
METADATA_TEMPLATE;
        return $metadata;
    }

    /**
     * Signs the metadata with the key/cert provided
     *
     * @param string $metadata SAML Metadata XML
     * @param string $key      x509 key
     * @param string $cert     x509 cert
     *
     * @return string Signed Metadata
     */
    public static function signMetadata($metadata, $key, $cert, $signAlgorithm = XMLSecurityKey::RSA_SHA1)
    {
        return OneLogin_Saml2_Utils::addSign($metadata, $key, $cert, $signAlgorithm);
    }

    /**
     * Adds the x509 descriptors (sign/encriptation) to the metadata
     * The same cert will be used for sign/encrypt
     *
     * @param string $metadata SAML Metadata XML
     * @param string $cert     x509 cert
     *
     * @return string Metadata with KeyDescriptors
     */
    public static function addX509KeyDescriptors($metadata, $cert)
    {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        try {
            $xml = OneLogin_Saml2_Utils::loadXML($xml, $metadata);
            if (!$xml) {
                throw new Exception('Error parsing metadata');
            }
        } catch (Exception $e) {
            throw new Exception('Error parsing metadata. '.$e->getMessage());
        }

        $formatedCert = OneLogin_Saml2_Utils::formatCert($cert, false);
        $x509Certificate = $xml->createElementNS(OneLogin_Saml2_Constants::NS_DS, 'X509Certificate', $formatedCert);

        $keyData = $xml->createElementNS(OneLogin_Saml2_Constants::NS_DS, 'ds:X509Data');
        $keyData->appendChild($x509Certificate);

        $keyInfo = $xml->createElementNS(OneLogin_Saml2_Constants::NS_DS, 'ds:KeyInfo');
        $keyInfo->appendChild($keyData);
        
        $keyDescriptor = $xml->createElementNS(OneLogin_Saml2_Constants::NS_MD, "md:KeyDescriptor");

        $SPSSODescriptor = $xml->getElementsByTagName('SPSSODescriptor')->item(0);
        $SPSSODescriptor->insertBefore($keyDescriptor->cloneNode(), $SPSSODescriptor->firstChild);
        $SPSSODescriptor->insertBefore($keyDescriptor->cloneNode(), $SPSSODescriptor->firstChild);

        $signing = $xml->getElementsByTagName('KeyDescriptor')->item(0);
        $signing->setAttribute('use', 'signing');

        $encryption = $xml->getElementsByTagName('KeyDescriptor')->item(1);
        $encryption->setAttribute('use', 'encryption');

        $signing->appendChild($keyInfo);
        $encryption->appendChild($keyInfo->cloneNode(true));

        return $xml->saveXML();
    }
}
