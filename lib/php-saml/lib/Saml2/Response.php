<?php

/**
 * SAML 2 Authentication Response
 *
 */

class OneLogin_Saml2_Response
{

    /**
     * Settings
     * @var OneLogin_Saml2_Settings
     */
    protected $_settings;

    /**
     * The decoded, unprocessed XML response provided to the constructor.
     * @var string
     */
    public $response;

    /**
     * A DOMDocument class loaded from the SAML Response.
     * @var DomDocument
     */
    public $document;

    /**
     * A DOMDocument class loaded from the SAML Response (Decrypted).
     * @var DomDocument
     */
    public $decryptedDocument;

    /**
    * The response contains an encrypted assertion.
    * @var boolean
    */
    public $encrypted = false;

    /**
    * After validation, if it fail this var has the cause of the problem
    * @var string
    */
    private $_error;

    /**
     * Constructs the SAML Response object.
     *
     * @param OneLogin_Saml2_Settings $settings Settings.
     * @param string                  $response A UUEncoded SAML response from the IdP.
     */
    public function __construct(OneLogin_Saml2_Settings $settings, $response)
    {
        $this->_settings = $settings;

        $this->response = base64_decode($response);

        $this->document = new DOMDocument();
        $this->document = OneLogin_Saml2_Utils::loadXML($this->document, $this->response);
        if (!$this->document) {
            throw new Exception('SAML Response could not be processed');
        }

        // Quick check for the presence of EncryptedAssertion
        $encryptedAssertionNodes = $this->document->getElementsByTagName('EncryptedAssertion');
        if ($encryptedAssertionNodes->length !== 0) {
            $this->decryptedDocument = clone $this->document;
            $this->encrypted = true;
            $this->_decryptAssertion($this->decryptedDocument);
        }
    }

    /**
    * Determines if the SAML Response is valid using the certificate.
    *
    * @param string $requestId The ID of the AuthNRequest sent by this SP to the IdP
    *
    * @throws Exception
    * @return bool Validate the document
    */
    public function isValid($requestId = null)
    {
        $this->_error = null;
        try {
            // Check SAML version
            if ($this->document->documentElement->getAttribute('Version') != '2.0') {
                throw new Exception('Unsupported SAML version');
            }

            if (!$this->document->documentElement->hasAttribute('ID')) {
                throw new Exception('Missing ID attribute on SAML Response');
            }

            $status = $this->checkStatus();

            $singleAssertion = $this->validateNumAssertions();
            if (!$singleAssertion) {
                throw new Exception('SAML Response must contain 1 assertion');
            }

            $idpData = $this->_settings->getIdPData();
            $idPEntityId = $idpData['entityId'];
            $spData = $this->_settings->getSPData();
            $spEntityId = $spData['entityId'];

            $signedElements = array();
            if ($this->encrypted) {
                $signNodes = $this->decryptedDocument->getElementsByTagName('Signature');
            } else {
                $signNodes = $this->document->getElementsByTagName('Signature');
            }
            foreach ($signNodes as $signNode) {
                $signedElements[] = $signNode->parentNode->localName;
            }

            if (!empty($signedElements)) {
                // Check SignedElements
                if (!$this->validateSignedElements($signedElements)) {
                    throw new Exception('Found an unexpected Signature Element. SAML Response rejected');
                }
            }

            if ($this->_settings->isStrict()) {
                $security = $this->_settings->getSecurityData();

                if ($security['wantXMLValidation']) {
                    $res = OneLogin_Saml2_Utils::validateXML($this->document, 'saml-schema-protocol-2.0.xsd', $this->_settings->isDebugActive());
                    if (!$res instanceof DOMDocument) {
                        throw new Exception("Invalid SAML Response. Not match the saml-schema-protocol-2.0.xsd");
                    }
                }

                $currentURL = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();
                
                if ($this->document->documentElement->hasAttribute('InResponseTo')) {
                    $responseInResponseTo = $this->document->documentElement->getAttribute('InResponseTo');
                }

                // Check if the InResponseTo of the Response matchs the ID of the AuthNRequest (requestId) if provided
                if (isset($requestId) && isset($responseInResponseTo)) {
                    if ($requestId != $responseInResponseTo) {
                        throw new Exception("The InResponseTo of the Response: $responseInResponseTo, does not match the ID of the AuthNRequest sent by the SP: $requestId");
                    }
                }

                if (!$this->encrypted && $security['wantAssertionsEncrypted']) {
                    throw new Exception("The assertion of the Response is not encrypted and the SP requires it");
                }

                if ($security['wantNameIdEncrypted']) {
                    $encryptedIdNodes = $this->_queryAssertion('/saml:Subject/saml:EncryptedID/xenc:EncryptedData');
                    if ($encryptedIdNodes->length == 0) {
                        throw new Exception("The NameID of the Response is not encrypted and the SP requires it");
                    }
                }

                // Validate Asserion timestamps
                $validTimestamps = $this->validateTimestamps();
                if (!$validTimestamps) {
                    throw new Exception('Timing issues (please check your clock settings)');
                }

                // EncryptedAttributes are not supported
                $encryptedAttributeNodes = $this->_queryAssertion('/saml:AttributeStatement/saml:EncryptedAttribute');
                if ($encryptedAttributeNodes->length > 0) {
                    throw new Exception("There is an EncryptedAttribute in the Response and this SP not support them");
                }

                // Check destination
                if ($this->document->documentElement->hasAttribute('Destination')) {
                    $destination = trim($this->document->documentElement->getAttribute('Destination'));
                    if (!empty($destination)) {
                        if (strpos($destination, $currentURL) !== 0) {
                            $currentURLrouted = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();

                            if (strpos($destination, $currentURLrouted) !== 0) {
                                throw new Exception("The response was received at $currentURL instead of $destination");
                            }
                        }
                    }
                }

                // Check audience
                $validAudiences = $this->getAudiences();
                if (!empty($validAudiences) && !in_array($spEntityId, $validAudiences)) {
                    throw new Exception("$spEntityId is not a valid audience for this Response");
                }

                // Check the issuers
                $issuers = $this->getIssuers();
                foreach ($issuers as $issuer) {
                    $trimmedIssuer = trim($issuer);

                    if (empty($trimmedIssuer) || $trimmedIssuer !== $idPEntityId) {
                        throw new Exception("Invalid issuer in the Assertion/Response");
                    }
                }

                // Check the session Expiration
                $sessionExpiration = $this->getSessionNotOnOrAfter();
                if (!empty($sessionExpiration) &&  $sessionExpiration <= time()) {
                    throw new Exception("The attributes have expired, based on the SessionNotOnOrAfter of the AttributeStatement of this Response");
                }

                // Check the SubjectConfirmation, at least one SubjectConfirmation must be valid
                $anySubjectConfirmation = false;
                $subjectConfirmationNodes = $this->_queryAssertion('/saml:Subject/saml:SubjectConfirmation');
                foreach ($subjectConfirmationNodes as $scn) {
                    if ($scn->hasAttribute('Method') && $scn->getAttribute('Method') != OneLogin_Saml2_Constants::CM_BEARER) {
                        continue;
                    }
                    $subjectConfirmationDataNodes = $scn->getElementsByTagName('SubjectConfirmationData');
                    if ($subjectConfirmationDataNodes->length == 0) {
                        continue;
                    } else {
                        $scnData = $subjectConfirmationDataNodes->item(0);
                        if ($scnData->hasAttribute('InResponseTo')) {
                            $inResponseTo = $scnData->getAttribute('InResponseTo');
                            if ($responseInResponseTo != $inResponseTo) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('Recipient')) {
                            $recipient = $scnData->getAttribute('Recipient');
                            if (!empty($recipient) && strpos($recipient, $currentURL) === false) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('NotOnOrAfter')) {
                            $noa = OneLogin_Saml2_Utils::parseSAML2Time($scnData->getAttribute('NotOnOrAfter'));
                            if ($noa <= time()) {
                                continue;
                            }
                        }
                        if ($scnData->hasAttribute('NotBefore')) {
                            $nb = OneLogin_Saml2_Utils::parseSAML2Time($scnData->getAttribute('NotBefore'));
                            if ($nb > time()) {
                                continue;
                            }
                        }
                        $anySubjectConfirmation = true;
                        break;
                    }
                }

                if (!$anySubjectConfirmation) {
                    throw new Exception("A valid SubjectConfirmation was not found on this Response");
                }

                if ($security['wantAssertionsSigned'] && !in_array('Assertion', $signedElements)) {
                    throw new Exception("The Assertion of the Response is not signed and the SP requires it");
                }
                
                if ($security['wantMessagesSigned'] && !in_array('Response', $signedElements)) {
                    throw new Exception("The Message of the Response is not signed and the SP requires it");
                }
            }

            if (!empty($signedElements)) {
                $cert = $idpData['x509cert'];
                $fingerprint = $idpData['certFingerprint'];
                $fingerprintalg = $idpData['certFingerprintAlgorithm'];

                # If find a Signature on the Response, validates it checking the original response
                if (in_array('Response', $signedElements)) {
                    $documentToValidate = $this->document;
                } else {
                    # Otherwise validates the assertion (decrypted assertion if was encrypted)
                    $documentToValidate = $this->decryptedDocument;
                    if ($this->encrypted) {
                        $documentToValidate = $this->decryptedDocument;
                        $encryptedIDNodes = OneLogin_Saml2_Utils::query($this->decryptedDocument, '/samlp:Response/saml:EncryptedAssertion/saml:Assertion/saml:Subject/saml:EncryptedID');
                        if ($encryptedIDNodes->length > 0) {
                            throw new Exception('Unsigned SAML Response that contains a signed and encrypted Assertion with encrypted nameId is not supported.');
                        }
                    } else {
                        $documentToValidate = $this->document;
                    }
                }

                if (!OneLogin_Saml2_Utils::validateSign($documentToValidate, $cert, $fingerprint, $fingerprintalg)) {
                    throw new Exception('Signature validation failed. SAML Response rejected');
                }
            } else {
                throw new Exception('No Signature found. SAML Response rejected');
            }
            return true;
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            $debug = $this->_settings->isDebugActive();
            if ($debug) {
                echo $this->_error;
            }
            return false;
        }
    }

    /**
     * Checks if the Status is success
     * 
     * @throws $statusExceptionMsg If status is not success
     */
    public function checkStatus()
    {
        $status = OneLogin_Saml2_Utils::getStatus($this->document);

        if (isset($status['code']) && $status['code'] !== OneLogin_Saml2_Constants::STATUS_SUCCESS) {
            $explodedCode = explode(':', $status['code']);
            $printableCode = array_pop($explodedCode);

            $statusExceptionMsg = 'The status code of the Response was not Success, was '.$printableCode;
            if (!empty($status['msg'])) {
                $statusExceptionMsg .= ' -> '.$status['msg'];
            }

            throw new Exception($statusExceptionMsg);
        }
    }

    /**
     * Gets the audiences.
     * 
     * @return array @audience The valid audiences of the response
     */
    public function getAudiences()
    {
        $audiences = array();

        $entries = $this->_queryAssertion('/saml:Conditions/saml:AudienceRestriction/saml:Audience');
        foreach ($entries as $entry) {
            $value = trim($entry->textContent);
            if (!empty($value)) {
                $audiences[] = $value;
            }
        }

        return array_unique($audiences);
    }

    /**
     * Gets the Issuers (from Response and Assertion).
     * 
     * @return array @issuers The issuers of the assertion/response
     */
    public function getIssuers()
    {
        $issuers = array();

        $responseIssuer = $this->_query('/samlp:Response/saml:Issuer');
        if ($responseIssuer->length == 1) {
            $issuers[] = $responseIssuer->item(0)->textContent;
        }

        $assertionIssuer = $this->_queryAssertion('/saml:Issuer');
        if ($assertionIssuer->length == 1) {
            $issuers[] = $assertionIssuer->item(0)->textContent;
        }

        return array_unique($issuers);
    }

    /**
     * Gets the NameID Data provided by the SAML response from the IdP.
     *
     * @return array Name ID Data (Value, Format, NameQualifier, SPNameQualifier)
     */
    public function getNameIdData()
    {
        $encryptedIdDataEntries = $this->_queryAssertion('/saml:Subject/saml:EncryptedID/xenc:EncryptedData');

        if ($encryptedIdDataEntries->length == 1) {
            $encryptedData = $encryptedIdDataEntries->item(0);

            $key = $this->_settings->getSPkey();
            $seckey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'private'));
            $seckey->loadKey($key);

            $nameId = OneLogin_Saml2_Utils::decryptElement($encryptedData, $seckey);

        } else {
            $entries = $this->_queryAssertion('/saml:Subject/saml:NameID');
            if ($entries->length == 1) {
                $nameId = $entries->item(0);
            }
        }

        if (!isset($nameId)) {
            throw new Exception("Not NameID found in the assertion of the Response");
        }

        $nameIdData = array();
        $nameIdData['Value'] = $nameId->nodeValue;
        foreach (array('Format', 'SPNameQualifier', 'NameQualifier') as $attr) {
            if ($nameId->hasAttribute($attr)) {
                $nameIdData[$attr] = $nameId->getAttribute($attr);
            }
        }

        return $nameIdData;
    }

    /**
     * Gets the NameID provided by the SAML response from the IdP.
     *
     * @return string Name ID Value
     */
    public function getNameId()
    {
        $nameIdData = $this->getNameIdData();
        return $nameIdData['Value'];
    }

    /**
     * Gets the SessionNotOnOrAfter from the AuthnStatement.
     * Could be used to set the local session expiration
     * 
     * @return DateTime|null The SessionNotOnOrAfter value
     */
    public function getSessionNotOnOrAfter()
    {
        $notOnOrAfter = null;
        $entries = $this->_queryAssertion('/saml:AuthnStatement[@SessionNotOnOrAfter]');
        if ($entries->length !== 0) {
            $notOnOrAfter = OneLogin_Saml2_Utils::parseSAML2Time($entries->item(0)->getAttribute('SessionNotOnOrAfter'));
        }
        return $notOnOrAfter;
    }

    /**
     * Gets the SessionIndex from the AuthnStatement.
     * Could be used to be stored in the local session in order
     * to be used in a future Logout Request that the SP could
     * send to the SP, to set what specific session must be deleted
     * 
     * @return string|null The SessionIndex value
     */

    public function getSessionIndex()
    {
        $sessionIndex = null;
        $entries = $this->_queryAssertion('/saml:AuthnStatement[@SessionIndex]');
        if ($entries->length !== 0) {
            $sessionIndex = $entries->item(0)->getAttribute('SessionIndex');
        }
        return $sessionIndex;
    }

    /**
     * Gets the Attributes from the AttributeStatement element.
     * 
     * @return array The attributes of the SAML Assertion
     */
    public function getAttributes()
    {
        $attributes = array();

        /* EncryptedAttributes not supported

        $encriptedAttributes = $this->_queryAssertion('/saml:AttributeStatement/saml:EncryptedAttribute');

        if ($encriptedAttributes->length > 0) {
            foreach ($encriptedAttributes as $encriptedAttribute) {
                $key = $this->_settings->getSPkey();
                $seckey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'private'));
                $seckey->loadKey($key);
                $attribute = OneLogin_Saml2_Utils::decryptElement($encriptedAttribute->firstChild(), $seckey);
            }
        }
        */

        $entries = $this->_queryAssertion('/saml:AttributeStatement/saml:Attribute');

        /** @var $entry DOMNode */
        foreach ($entries as $entry) {
            $attributeName = $entry->attributes->getNamedItem('Name')->nodeValue;

            $attributeValues = array();
            foreach ($entry->childNodes as $childNode) {
                $tagName = ($childNode->prefix ? $childNode->prefix.':' : '') . 'AttributeValue';
                if ($childNode->nodeType == XML_ELEMENT_NODE && $childNode->tagName === $tagName) {
                    $attributeValues[] = $childNode->nodeValue;
                }
            }

            $attributes[$attributeName] = $attributeValues;
        }
        return $attributes;
    }

    /**
     * Verifies that the document only contains a single Assertion (encrypted or not).
     *
     * @return bool TRUE if the document passes.
     */
    public function validateNumAssertions()
    {
        $encryptedAssertionNodes = $this->document->getElementsByTagName('EncryptedAssertion');
        $assertionNodes = $this->document->getElementsByTagName('Assertion');
        return ($assertionNodes->length + $encryptedAssertionNodes->length == 1);
    }

    /**
     * Verifies that the document is still valid according Conditions Element.
     *
     * @return bool
     */
    public function validateTimestamps()
    {
        if ($this->encrypted) {
            $document = $this->decryptedDocument;
        } else {
            $document = $this->document;
        }

        $timestampNodes = $document->getElementsByTagName('Conditions');
        for ($i = 0; $i < $timestampNodes->length; $i++) {
            $nbAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotBefore");
            $naAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotOnOrAfter");
            if ($nbAttribute && OneLogin_SAML2_Utils::parseSAML2Time($nbAttribute->textContent) > time() + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT) {
                return false;
            }
            if ($naAttribute && OneLogin_SAML2_Utils::parseSAML2Time($naAttribute->textContent) + OneLogin_Saml2_Constants::ALLOWED_CLOCK_DRIFT <= time()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifies that the document has the expected signed nodes.
     *
     * @return bool
     */
    public function validateSignedElements($signedElements)
    {
        if (count($signedElements) > 2) {
            return false;
        }
        $ocurrence = array_count_values($signedElements);
        if ((in_array('Response', $signedElements) && $ocurrence['Response'] > 1) ||
            (in_array('Assertion', $signedElements) && $ocurrence['Assertion'] > 1) ||
            !in_array('Response', $signedElements) && !in_array('Assertion', $signedElements)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Extracts a node from the DOMDocument (Assertion).
     *
     * @param string $assertionXpath Xpath Expresion
     *
     * @throws Exception
     * @return DOMNodeList The queried node
     */
    protected function _queryAssertion($assertionXpath)
    {
        if ($this->encrypted) {
            $xpath = new DOMXPath($this->decryptedDocument);
        } else {
            $xpath = new DOMXPath($this->document);
        }

        $xpath->registerNamespace('samlp', OneLogin_Saml2_Constants::NS_SAMLP);
        $xpath->registerNamespace('saml', OneLogin_Saml2_Constants::NS_SAML);
        $xpath->registerNamespace('ds', OneLogin_Saml2_Constants::NS_DS);
        $xpath->registerNamespace('xenc', OneLogin_Saml2_Constants::NS_XENC);

        $assertionNode = $this->encrypted ? '/samlp:Response/saml:EncryptedAssertion/saml:Assertion'
                : '/samlp:Response/saml:Assertion';
        $signatureQuery = $assertionNode . '/ds:Signature/ds:SignedInfo/ds:Reference';
        $assertionReferenceNode = $xpath->query($signatureQuery)->item(0);
        if (!$assertionReferenceNode) {
            // is the response signed as a whole?
            $signatureQuery = '/samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference';
            $assertionReferenceNode = $xpath->query($signatureQuery)->item(0);
            if ($assertionReferenceNode) {
                $id = substr($assertionReferenceNode->attributes->getNamedItem('URI')->nodeValue, 1);
                $nameQuery = "/samlp:Response[@ID='$id']/".($this->encrypted? "saml:EncryptedAssertion/" : "")."saml:Assertion" . $assertionXpath;
            } else {
                $nameQuery = "/samlp:Response/".($this->encrypted? "saml:EncryptedAssertion/" : "")."saml:Assertion" . $assertionXpath;
            }
        } else {
            $id = substr($assertionReferenceNode->attributes->getNamedItem('URI')->nodeValue, 1);
            $nameQuery = $assertionNode."[@ID='$id']" . $assertionXpath;
        }

        return $xpath->query($nameQuery);
    }

    /**
     * Extracts nodes that match the query from the DOMDocument (Response Menssage)
     *
     * @param string $query Xpath Expresion
     *
     * @return DOMNodeList The queried nodes
     */
    private function _query($query)
    {
        return OneLogin_Saml2_Utils::query($this->document, $query);
    }

    /**
     * Decrypts the Assertion (DOMDocument)
     *
     * @param string $dom DomDocument
     *
     * @throws Exception
     * @return DOMDocument Decrypted Assertion
     */
    protected function _decryptAssertion($dom)
    {
        $pem = $this->_settings->getSPkey();

        if (empty($pem)) {
            throw new Exception("No private key available, check settings");
        }
        
        $objenc = new XMLSecEnc();
        $encData = $objenc->locateEncryptedData($dom);
        if (!$encData) {
            throw new Exception("Cannot locate encrypted assertion");
        }
        
        $objenc->setNode($encData);
        $objenc->type = $encData->getAttribute("Type");
        if (!$objKey = $objenc->locateKey()) {
            throw new Exception("Unknown algorithm");
        }

        $key = null;
        if ($objKeyInfo = $objenc->locateKeyInfo($objKey)) {
            if ($objKeyInfo->isEncrypted) {
                $objencKey = $objKeyInfo->encryptedCtx;
                $objKeyInfo->loadKey($pem, false, false);
                $key = $objencKey->decryptKey($objKeyInfo);
            }
        }
                
        if (empty($objKey->key)) {
            $objKey->loadKey($key);
        }
        $decrypt = $objenc->decryptNode($objKey, true);
        if ($decrypt instanceof DOMDocument) {
            return $decrypt;
        } else {
            return $decrypt->ownerDocument;
        }
    }

    /* After execute a validation process, if fails this method returns the cause
     *
     * @return string Cause 
     */
    public function getError()
    {
        return $this->_error;
    }
}
