<?php

/**
 * SAML 2 Logout Response
 *
 */
class OneLogin_Saml2_LogoutResponse
{

    /**
     * Object that represents the setting info
     * @var OneLogin_Saml2_Settings
     */
    protected $_settings;

    /**
     * The decoded, unprocessed XML response provided to the constructor.
     * @var string
     */
    protected $_logoutResponse;

    /**
     * A DOMDocument class loaded from the SAML LogoutResponse.
     * @var DomDocument
     */
    public $document;

    /**
    * After execute a validation process, if it fails, this var contains the cause
    * @var string
    */
    private $_error;

    /**
     * Constructs a Logout Response object (Initialize params from settings and if provided
     * load the Logout Response.
     *
     * @param OneLogin_Saml2_Settings $settings Settings.
     * @param string|null             $response An UUEncoded SAML Logout response from the IdP.
     */
    public function __construct(OneLogin_Saml2_Settings $settings, $response = null)
    {
        $this->_settings = $settings;
        if ($response) {
            $decoded = base64_decode($response);
            $inflated = @gzinflate($decoded);
            if ($inflated != false) {
                $this->_logoutResponse = $inflated;
            } else {
                $this->_logoutResponse = $decoded;
            }
            $this->document = new DOMDocument();
            $this->document = OneLogin_Saml2_Utils::loadXML($this->document, $this->_logoutResponse);
        }
    }

    /**
     * Gets the Issuer of the Logout Response.
     *
     * @return string|null $issuer The Issuer
     */
    public function getIssuer()
    {
        $issuer = null;
        $issuerNodes = $this->_query('/samlp:LogoutResponse/saml:Issuer');
        if ($issuerNodes->length == 1) {
            $issuer = $issuerNodes->item(0)->textContent;
        }
        return $issuer;
    }

    /**
     * Gets the Status of the Logout Response.
     * 
     * @return string The Status
     */
    public function getStatus()
    {
        $entries = $this->_query('/samlp:LogoutResponse/samlp:Status/samlp:StatusCode');
        if ($entries->length == 0) {
            return null;
        }
        $status = $entries->item(0)->getAttribute('Value');
        return $status;
    }

    /**
     * Determines if the SAML LogoutResponse is valid
     *
     * @param string $requestId The ID of the LogoutRequest sent by this SP to the IdP
     *
     * @throws Exception
     * @return bool Returns if the SAML LogoutResponse is or not valid
     */
    public function isValid($requestId = null, $retrieveParametersFromServer=false)
    {
        $this->_error = null;
        try {

            $idpData = $this->_settings->getIdPData();
            $idPEntityId = $idpData['entityId'];

            if ($this->_settings->isStrict()) {
                $security = $this->_settings->getSecurityData();

                if ($security['wantXMLValidation']) {
                    $res = OneLogin_Saml2_Utils::validateXML($this->document, 'saml-schema-protocol-2.0.xsd', $this->_settings->isDebugActive());
                    if (!$res instanceof DOMDocument) {
                        throw new Exception("Invalid SAML Logout Response. Not match the saml-schema-protocol-2.0.xsd");
                    }
                }

                // Check if the InResponseTo of the Logout Response matchs the ID of the Logout Request (requestId) if provided
                if (isset($requestId) && $this->document->documentElement->hasAttribute('InResponseTo')) {
                    $inResponseTo = $this->document->documentElement->getAttribute('InResponseTo');
                    if ($requestId != $inResponseTo) {
                        throw new Exception("The InResponseTo of the Logout Response: $inResponseTo, does not match the ID of the Logout request sent by the SP: $requestId");
                    }
                }

                // Check issuer
                $issuer = $this->getIssuer();
                if (!empty($issuer) && $issuer != $idPEntityId) {
                    throw new Exception("Invalid issuer in the Logout Response");
                }

                $currentURL = OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery();

                // Check destination
                if ($this->document->documentElement->hasAttribute('Destination')) {
                    $destination = $this->document->documentElement->getAttribute('Destination');
                    if (!empty($destination)) {
                        if (strpos($destination, $currentURL) === false) {
                            throw new Exception("The LogoutResponse was received at $currentURL instead of $destination");
                        }
                    }
                }

                if ($security['wantMessagesSigned']) {
                    if (!isset($_GET['Signature'])) {
                        throw new Exception("The Message of the Logout Response is not signed and the SP requires it");
                    }
                }
            }

            if (isset($_GET['Signature'])) {
                if (!isset($_GET['SigAlg'])) {
                    $signAlg = XMLSecurityKey::RSA_SHA1;
                } else {
                    $signAlg = $_GET['SigAlg'];
                }

                if ($retrieveParametersFromServer) {
                    $signedQuery = 'SAMLResponse='.OneLogin_Saml2_Utils::extractOriginalQueryParam('SAMLResponse');
                    if (isset($_GET['RelayState'])) {
                        $signedQuery .= '&RelayState='.OneLogin_Saml2_Utils::extractOriginalQueryParam('RelayState');
                    }
                    $signedQuery .= '&SigAlg='.OneLogin_Saml2_Utils::extractOriginalQueryParam('SigAlg');
                } else {
                    $signedQuery = 'SAMLResponse='.urlencode($_GET['SAMLResponse']);
                    if (isset($_GET['RelayState'])) {
                        $signedQuery .= '&RelayState='.urlencode($_GET['RelayState']);
                    }
                    $signedQuery .= '&SigAlg='.urlencode($signAlg);
                }

                if (!isset($idpData['x509cert']) || empty($idpData['x509cert'])) {
                    throw new Exception('In order to validate the sign on the Logout Response, the x509cert of the IdP is required');
                }
                $cert = $idpData['x509cert'];

                $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'public'));
                $objKey->loadKey($cert, false, true);

                if ($signAlg != XMLSecurityKey::RSA_SHA1) {
                    try {
                        $objKey = OneLogin_Saml2_Utils::castKey($objKey, $signAlg, 'public');
                    } catch (Exception $e) {
                        throw new Exception('Invalid signAlg in the recieved Logout Response');
                    }
                }

                if (!$objKey->verifySignature($signedQuery, base64_decode($_GET['Signature']))) {
                    throw new Exception('Signature validation failed. Logout Response rejected');
                }
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
     * Extracts a node from the DOMDocument (Logout Response Menssage)
     *
     * @param string $query Xpath Expresion
     *
     * @return DOMNodeList The queried node
     */
    private function _query($query)
    {
        return OneLogin_Saml2_Utils::query($this->document, $query);

    }

    /**
     * Generates a Logout Response object.
     *
     * @param string $inResponseTo InResponseTo value for the Logout Response. 
     */
    public function build($inResponseTo)
    {

        $spData = $this->_settings->getSPData();
        $idpData = $this->_settings->getIdPData();

        $id = OneLogin_Saml2_Utils::generateUniqueID();
        $issueInstant = OneLogin_Saml2_Utils::parseTime2SAML(time());

        $logoutResponse = <<<LOGOUTRESPONSE
<samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                  xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                  ID="{$id}"
                  Version="2.0"
                  IssueInstant="{$issueInstant}"
                  Destination="{$idpData['singleLogoutService']['url']}"
                  InResponseTo="{$inResponseTo}"
                  >
    <saml:Issuer>{$spData['entityId']}</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success" />
    </samlp:Status>
</samlp:LogoutResponse>
LOGOUTRESPONSE;
        $this->_logoutResponse = $logoutResponse;
    }

    /**
     * Returns a Logout Response object.
     *
     * @return string Logout Response deflated and base64 encoded
     */
    public function getResponse()
    {
        $deflatedResponse = gzdeflate($this->_logoutResponse);
        return base64_encode($deflatedResponse);
    }

    /* After execute a validation process, if fails this method returns the cause.
     *
     * @return string Cause 
     */
    public function getError()
    {
        return $this->_error;
    }
}
