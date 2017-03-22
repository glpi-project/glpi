<?php
 
/**
 * Utils of OneLogin PHP Toolkit
 *
 * Defines several often used methods
 */

class OneLogin_Saml2_Utils
{
    /**
    * Translates any string. Accepts args  
    *
    * @param string $msg  Message to be translated
    * @param array  $args Arguments
    * 
    * @return string $translatedMsg  Translated text
    */
    public static function t($msg, $args = array())
    {
        assert('is_string($msg)');
        if (extension_loaded('gettext')) {
            bindtextdomain("phptoolkit", dirname(dirname(dirname(__FILE__))).'/locale');
            textdomain('phptoolkit');

            $translatedMsg = gettext($msg);
        } else {
            $translatedMsg = $msg;
        }
        if (!empty($args)) {
            $params = array_merge(array($translatedMsg), $args);
            $translatedMsg = call_user_func_array('sprintf', $params);
        }
        return $translatedMsg;
    }

    /**
     * This function load an XML string in a save way.
     * Prevent XEE/XXE Attacks
     *
     * @param DOMDocument $dom The document where load the xml.
     * @param string      $xml The XML string to be loaded.
     *
     * @throws DOMExceptions
     *
     * @return DOMDocument $dom The result of load the XML at the DomDocument
     */
    public static function loadXML($dom, $xml)
    {
        assert('$dom instanceof DOMDocument');
        assert('is_string($xml)');

        if (strpos($xml, '<!ENTITY') !== false) {
            throw new Exception('Detected use of ENTITY in XML, disabled to prevent XXE/XEE attacks');
        }

        $oldEntityLoader = libxml_disable_entity_loader(true);
        $res = $dom->loadXML($xml);
        libxml_disable_entity_loader($oldEntityLoader);

        if (!$res) {
            return false;
        } else {
            return $dom;
        }
    }

    /**
     * This function attempts to validate an XML string against the specified schema.
     *
     * It will parse the string into a DOM document and validate this document against the schema.
     *
     * @param string  $xml    The XML string or document which should be validated.
     * @param string  $schema The schema filename which should be used.
     * @param boolean $debug  To disable/enable the debug mode
     *
     * @return string | DOMDocument $dom  string that explains the problem or the DOMDocument
     */
    public static function validateXML($xml, $schema, $debug = false)
    {
        assert('is_string($xml) || $xml instanceof DOMDocument');
        assert('is_string($schema)');

        libxml_clear_errors();
        libxml_use_internal_errors(true);

        if ($xml instanceof DOMDocument) {
            $dom = $xml;
        } else {
            $dom = new DOMDocument;
            $dom = self::loadXML($dom, $xml);
            if (!$dom) {
                return 'unloaded_xml';
            }
        }

        $schemaFile = dirname(__FILE__).'/schemas/' . $schema;
        $oldEntityLoader = libxml_disable_entity_loader(false);
        $res = $dom->schemaValidate($schemaFile);
        libxml_disable_entity_loader($oldEntityLoader);
        if (!$res) {

            $xmlErrors = libxml_get_errors();
            syslog(LOG_INFO, 'Error validating the metadata: '.var_export($xmlErrors, true));

            if ($debug) {
                foreach ($xmlErrors as $error) {
                    echo $error->message."\n";
                }
            }

            return 'invalid_xml';
        }


        return $dom;
    }

    /**
     * Returns a x509 cert (adding header & footer if required).
     *
     * @param string  $cert  A x509 unformated cert
     * @param boolean $heads True if we want to include head and footer
     *
     * @return string $x509 Formated cert
     */

    public static function formatCert($cert, $heads = true)
    {
        $x509cert = str_replace(array("\x0D", "\r", "\n"), "", $cert);
        if (!empty($x509cert)) {
            $x509cert = str_replace('-----BEGIN CERTIFICATE-----', "", $x509cert);
            $x509cert = str_replace('-----END CERTIFICATE-----', "", $x509cert);
            $x509cert = str_replace(' ', '', $x509cert);

            if ($heads) {
                $x509cert = "-----BEGIN CERTIFICATE-----\n".chunk_split($x509cert, 64, "\n")."-----END CERTIFICATE-----\n";
            }

        }
        return $x509cert;
    }

    /**
     * Returns a private key (adding header & footer if required).
     *
     * @param string  $key   A private key
     * @param boolean $heads True if we want to include head and footer
     *
     * @return string $rsaKey Formated private key
     */

    public static function formatPrivateKey($key, $heads = true)
    {
        $key = str_replace(array("\x0D", "\r", "\n"), "", $key);
        if (!empty($key)) {

            if (strpos($key, '-----BEGIN PRIVATE KEY-----') !== false) {
                $key = str_replace('-----BEGIN PRIVATE KEY-----', "", $key);
                $key = str_replace('-----END PRIVATE KEY-----', "", $key);
                $key = str_replace(' ', '', $key);

                if ($heads) {
                    $key = "-----BEGIN PRIVATE KEY-----\n".chunk_split($key, 64, "\n")."-----END PRIVATE KEY-----\n";
                }
            } else {
                $key = str_replace('-----BEGIN RSA PRIVATE KEY-----', "", $key);
                $key = str_replace('-----END RSA PRIVATE KEY-----', "", $key);
                $key = str_replace(' ', '', $key);

                if ($heads) {
                    $key = "-----BEGIN RSA PRIVATE KEY-----\n".chunk_split($key, 64, "\n")."-----END RSA PRIVATE KEY-----\n";
                }
            }
        }
        return $key;
    }

    /**
     * Executes a redirection to the provided url (or return the target url).
     *
     * @param string  $url        The target url
     * @param array   $parameters Extra parameters to be passed as part of the url
     * @param boolean $stay       True if we want to stay (returns the url string) False to redirect
     *
     * @return string $url
     */
    public static function redirect($url, $parameters = array(), $stay = false)
    {
        assert('is_string($url)');
        assert('is_array($parameters)');

        if (substr($url, 0, 1) === '/') {
            $url = self::getSelfURLhost() . $url;
        }

        /* Verify that the URL is to a http or https site. */
        if (!preg_match('@^https?://@i', $url)) {
            throw new OneLogin_Saml2_Error(
                'Redirect to invalid URL: ' . $url,
                OneLogin_Saml2_Error::REDIRECT_INVALID_URL
            );
        }

        
        /* Add encoded parameters */
        if (strpos($url, '?') === false) {
            $paramPrefix = '?';
        } else {
            $paramPrefix = '&';
        }

        foreach ($parameters as $name => $value) {

            if ($value === null) {
                $param = urlencode($name);
            } else if (is_array($value)) {
                $param = "";
                foreach ($value as $val) {
                    $param .= urlencode($name) . "[]=" . urlencode($val). '&';
                }
                if (!empty($param)) {
                    $param = substr($param, 0, -1);
                }
            } else {
                $param = urlencode($name) . '=' . urlencode($value);
            }

            if (!empty($param)) {
                $url .= $paramPrefix . $param;
                $paramPrefix = '&';
            }
        }

        if ($stay) {
            return $url;
        }

        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');
        header('Location: ' . $url);
        exit();
    }

    /**
     * Returns the protocol + the current host + the port (if different than
     * common ports).
     *
     * @return string $url
     */
    public static function getSelfURLhost()
    {
        $currenthost = self::getSelfHost();

        $port = '';

        if (self::isHTTPS()) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        if (isset($_SERVER["HTTP_X_FORWARDED_PORT"])) {
            $portnumber = $_SERVER["HTTP_X_FORWARDED_PORT"];
        } else if (isset($_SERVER["SERVER_PORT"])) {
            $portnumber = $_SERVER["SERVER_PORT"];
        }

        if (isset($portnumber) && ($portnumber != '80') && ($portnumber != '443')) {
            $port = ':' . $portnumber;
        }

        return $protocol."://" . $currenthost . $port;
    }

    /**
     * Returns the current host.
     *
     * @return string $currentHost The current host
     */
    public static function getSelfHost()
    {

        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $currentHost = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $currentHost = $_SERVER['SERVER_NAME'];
        } else {
            if (function_exists('gethostname')) {
                $currentHost = gethostname();
            } else {
                $currentHost = php_uname("n");
            }
        }

        if (strstr($currentHost, ":")) {
            $currentHostData = explode(":", $currentHost);
            $possiblePort = array_pop($currentHostData);
            if (is_numeric($possiblePort)) {
                $currentHost = implode(':', $currentHostData);
            }
        }
        return $currentHost;
    }

    /**
     * Checks if https or http.
     *
     * @return boolean $isHttps  False if https is not active
     */
    public static function isHTTPS()
    {
        $isHttps =  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
        return $isHttps;
    }

    /**
     * Returns the URL of the current host + current view.
     *
     * @return string
     */
    public static function getSelfURLNoQuery()
    {

        $selfURLhost = self::getSelfURLhost();
        $selfURLNoQuery = $selfURLhost . $_SERVER['SCRIPT_NAME'];
        if (isset($_SERVER['PATH_INFO'])) {
            $selfURLNoQuery .= $_SERVER['PATH_INFO'];
        }
        return $selfURLNoQuery;
    }

    /**
     * Returns the routed URL of the current host + current view.
     *
     * @return string
     */
    public static function getSelfRoutedURLNoQuery()
    {

        $selfURLhost = self::getSelfURLhost();
        $route = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $route = $_SERVER['REQUEST_URI'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $route = str_replace($_SERVER['QUERY_STRING'], '', $route);
                if (substr($route, -1) == '?') {
                    $route = substr($route, 0, -1);
                }
            }
        }

        $selfRoutedURLNoQuery = $selfURLhost . $route;
        return $selfRoutedURLNoQuery;
    }

    /**
     * Returns the URL of the current host + current view + query.
     *
     * @return string
     */
    public static function getSelfURL()
    {
        $selfURLhost = self::getSelfURLhost();

        $requestURI = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestURI = $_SERVER['REQUEST_URI'];
            if ($requestURI[0] !== '/') {
                if (preg_match('#^https?://[^/]*(/.*)#i', $requestURI, $matches)) {
                    $requestURI = $matches[1];
                }
            }
        }
        return $selfURLhost . $requestURI;
    }

     /**
     * Extract a query param - as it was sent - from $_SERVER[QUERY_STRING]
     *
     * @param string The param to-be extracted
     */
    public static function extractOriginalQueryParam ($name)
    {
        $index = strpos($_SERVER['QUERY_STRING'], $name.'=');
        $substring = substr($_SERVER['QUERY_STRING'], $index + strlen($name) + 1);
        $end = strpos($substring, '&');
        return $end ? substr($substring, 0, strpos($substring, '&')) : $substring;
    }

    /**
     * Generates an unique string (used for example as ID for assertions).
     *
     * @return string  A unique string
     */
    public static function generateUniqueID()
    {
        return 'ONELOGIN_' . sha1(uniqid(mt_rand(), true));
    }

    /**
     * Converts a UNIX timestamp to SAML2 timestamp on the form
     * yyyy-mm-ddThh:mm:ss(\.s+)?Z.
     *
     * @param string $time The time we should convert (DateTime).
     *
     * @return $timestamp SAML2 timestamp.
     */
    public static function parseTime2SAML($time)
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = strftime("%Y-%m-%dT%H:%M:%SZ", $time);
        date_default_timezone_set($defaultTimezone);
        return $timestamp;
    }

    /**
     * Converts a SAML2 timestamp on the form yyyy-mm-ddThh:mm:ss(\.s+)?Z
     * to a UNIX timestamp. The sub-second part is ignored.
     *
     * @param string $time The time we should convert (SAML Timestamp).
     *
     * @return $timestamp  Converted to a unix timestamp.
     */
    public static function parseSAML2Time($time)
    {
        $matches = array();

        /* We use a very strict regex to parse the timestamp. */
        $exp1 = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)';
        $exp2 = 'T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
        if (preg_match($exp1 . $exp2, $time, $matches) == 0) {
            throw new Exception(
                'Invalid SAML2 timestamp passed to' .
                ' parseSAML2Time: ' . $time
            );
        }

        /* Extract the different components of the time from the
         * matches in the regex. intval will ignore leading zeroes
         * in the string.
         */
        $year = intval($matches[1]);
        $month = intval($matches[2]);
        $day = intval($matches[3]);
        $hour = intval($matches[4]);
        $minute = intval($matches[5]);
        $second = intval($matches[6]);

        /* We use gmmktime because the timestamp will always be given
         * in UTC.
         */
        $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

        return $ts;
    }


    /**
     * Interprets a ISO8601 duration value relative to a given timestamp.
     *
     * @param string $duration  The duration, as a string.
     * @param int    $timestamp The unix timestamp we should apply the
     *                          duration to. Optional, default to the
     *                          current time.
     *
     * @return int The new timestamp, after the duration is applied.
     */
    public static function parseDuration($duration, $timestamp = null)
    {
        assert('is_string($duration)');
        assert('is_null($timestamp) || is_int($timestamp)');

        /* Parse the duration. We use a very strict pattern. */
        $durationRegEx = '#^(-?)P(?:(?:(?:(\\d+)Y)?(?:(\\d+)M)?(?:(\\d+)D)?(?:T(?:(\\d+)H)?(?:(\\d+)M)?(?:(\\d+)S)?)?)|(?:(\\d+)W))$#D';
        if (!preg_match($durationRegEx, $duration, $matches)) {
            throw new Exception('Invalid ISO 8601 duration: ' . $duration);
        }

        $durYears = (empty($matches[2]) ? 0 : (int)$matches[2]);
        $durMonths = (empty($matches[3]) ? 0 : (int)$matches[3]);
        $durDays = (empty($matches[4]) ? 0 : (int)$matches[4]);
        $durHours = (empty($matches[5]) ? 0 : (int)$matches[5]);
        $durMinutes = (empty($matches[6]) ? 0 : (int)$matches[6]);
        $durSeconds = (empty($matches[7]) ? 0 : (int)$matches[7]);
        $durWeeks = (empty($matches[8]) ? 0 : (int)$matches[8]);

        if (!empty($matches[1])) {
            /* Negative */
            $durYears = -$durYears;
            $durMonths = -$durMonths;
            $durDays = -$durDays;
            $durHours = -$durHours;
            $durMinutes = -$durMinutes;
            $durSeconds = -$durSeconds;
            $durWeeks = -$durWeeks;
        }

        if ($timestamp === null) {
            $timestamp = time();
        }

        if ($durYears !== 0 || $durMonths !== 0) {
            /* Special handling of months and years, since they aren't a specific interval, but
             * instead depend on the current time.
             */

            /* We need the year and month from the timestamp. Unfortunately, PHP doesn't have the
             * gmtime function. Instead we use the gmdate function, and split the result.
             */
            $yearmonth = explode(':', gmdate('Y:n', $timestamp));
            $year = (int)($yearmonth[0]);
            $month = (int)($yearmonth[1]);

            /* Remove the year and month from the timestamp. */
            $timestamp -= gmmktime(0, 0, 0, $month, 1, $year);

            /* Add years and months, and normalize the numbers afterwards. */
            $year += $durYears;
            $month += $durMonths;
            while ($month > 12) {
                $year += 1;
                $month -= 12;
            }
            while ($month < 1) {
                $year -= 1;
                $month += 12;
            }

            /* Add year and month back into timestamp. */
            $timestamp += gmmktime(0, 0, 0, $month, 1, $year);
        }

        /* Add the other elements. */
        $timestamp += $durWeeks * 7 * 24 * 60 * 60;
        $timestamp += $durDays * 24 * 60 * 60;
        $timestamp += $durHours * 60 * 60;
        $timestamp += $durMinutes * 60;
        $timestamp += $durSeconds;

        return $timestamp;
    }

    /**
     * Compares 2 dates and returns the earliest.
     *
     * @param string $cacheDuration The duration, as a string.
     * @param string $validUntil    The valid until date, as a string or as a timestamp
     *
     * @return int $expireTime  The expiration time.
     */
    public static function getExpireTime($cacheDuration = null, $validUntil = null)
    {
        $expireTime = null;

        if ($cacheDuration !== null) {
            $expireTime = self::parseDuration($cacheDuration, time());
        }

        if ($validUntil !== null) {
            if (is_int($validUntil)) {
                $validUntilTime = $validUntil;
            } else {
                $validUntilTime = self::parseSAML2Time($validUntil);
            }
            if ($expireTime === null || $expireTime > $validUntilTime) {
                $expireTime = $validUntilTime;
            }
        }

        return $expireTime;
    }


    /**
     * Extracts nodes from the DOMDocument.
     *
     * @param DOMDocument $dom     The DOMDocument
     * @param string      $query   Xpath Expresion
     * @param DomElement  $context Context Node (DomElement) 
     *
     * @return DOMNodeList The queried nodes
     */
    public static function query($dom, $query, $context = null)
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('samlp', OneLogin_Saml2_Constants::NS_SAMLP);
        $xpath->registerNamespace('saml', OneLogin_Saml2_Constants::NS_SAML);
        $xpath->registerNamespace('ds', OneLogin_Saml2_Constants::NS_DS);
        $xpath->registerNamespace('xenc', OneLogin_Saml2_Constants::NS_XENC);

        if (isset($context)) {
            $res = $xpath->query($query, $context);
        } else {
            $res = $xpath->query($query);
        }
        return $res;
    }

    /**
     * Checks if the session is started or not.
     *
     * @return boolean true if the sessíon is started
     */
    public static function isSessionStarted()
    {
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            return session_status() === PHP_SESSION_ACTIVE ? true : false;
        } else {
            return session_id() === '' ? false : true;
        }
    }

    /**
     * Deletes the local session.
     */
    public static function deleteLocalSession()
    {

        if (OneLogin_Saml2_Utils::isSessionStarted()) {
            session_destroy();
        }

        unset($_SESSION);
    }

    /**
     * Calculates the fingerprint of a x509cert.
     *
     * @param string $x509cert x509 cert
     *
     * @return string Formated fingerprint
     */
    public static function calculateX509Fingerprint($x509cert, $alg='sha1')
    {
        assert('is_string($x509cert)');

        $lines = explode("\n", $x509cert);

        $data = '';

        foreach ($lines as $line) {
            /* Remove '\r' from end of line if present. */
            $line = rtrim($line);
            if ($line === '-----BEGIN CERTIFICATE-----') {
                /* Delete junk from before the certificate. */
                $data = '';
            } elseif ($line === '-----END CERTIFICATE-----') {
                /* Ignore data after the certificate. */
                break;
            } elseif ($line === '-----BEGIN PUBLIC KEY-----' || $line === '-----BEGIN RSA PRIVATE KEY-----') {
                /* This isn't an X509 certificate. */
                return null;
            } else {
                /* Append the current line to the certificate data. */
                $data .= $line;
            }
        }
        $decodedData = base64_decode($data);

        switch ($alg) {
            case 'sha512':
            case 'sha384':
            case 'sha256':
                $fingerprint = hash($alg, $decodedData, FALSE);
                break;
            case 'sha1':
            default:
                $fingerprint = strtolower(sha1($decodedData));
                break;
        }
        return $fingerprint;
    }

    /**
     * Formates a fingerprint.
     *
     * @param string $fingerprint fingerprint
     *
     * @return string Formated fingerprint
     */
    public static function formatFingerPrint($fingerprint)
    {
        $formatedFingerprint = str_replace(':', '', $fingerprint);
        $formatedFingerprint = strtolower($formatedFingerprint);
        return $formatedFingerprint;
    }

    /**
     * Generates a nameID.
     *
     * @param string $value  fingerprint
     * @param string $spnq   SP Name Qualifier
     * @param string $format SP Format
     * @param string $cert   IdP Public cert to encrypt the nameID
     *
     * @return string $nameIDElement DOMElement | XMLSec nameID
     */
    public static function generateNameId($value, $spnq, $format, $cert = null)
    {

        $doc = new DOMDocument();

        $nameId = $doc->createElement('saml:NameID');
        if (isset($spnq)) {
            $nameId->setAttribute('SPNameQualifier', $spnq);
        }
        $nameId->setAttribute('Format', $format);
        $nameId->appendChild($doc->createTextNode($value));

        $doc->appendChild($nameId);

        if (!empty($cert)) {
            $seckey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'public'));
            $seckey->loadKey($cert);

            $enc = new XMLSecEnc();
            $enc->setNode($nameId);
            $enc->type = XMLSecEnc::Element;

            $symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
            $symmetricKey->generateSessionKey();
            $enc->encryptKey($seckey, $symmetricKey);

            $encryptedData = $enc->encryptNode($symmetricKey);

            $newdoc = new DOMDocument();

            $encryptedID = $newdoc->createElement('saml:EncryptedID');

            $newdoc->appendChild($encryptedID);

            $encryptedID->appendChild($encryptedID->ownerDocument->importNode($encryptedData, true));

            return $newdoc->saveXML($encryptedID);
        } else {
            return $doc->saveXML($nameId);
        }
    }


    /**
     * Gets Status from a Response.
     *
     * @param DomElement $dom The Response as XML
     *
     * @return array $status The Status, an array with the code and a message.
     */
    public static function getStatus($dom)
    {
        $status = array();

        $statusEntry = self::query($dom, '/samlp:Response/samlp:Status');
        if ($statusEntry->length == 0) {
            throw new Exception('Missing Status on response');
        }

        $codeEntry = self::query($dom, '/samlp:Response/samlp:Status/samlp:StatusCode', $statusEntry->item(0));
        if ($codeEntry->length == 0) {
            throw new Exception('Missing Status Code on response');
        }
        $code = $codeEntry->item(0)->getAttribute('Value');
        $status['code'] = $code;

        $messageEntry = self::query($dom, '/samlp:Response/samlp:Status/samlp:StatusMessage', $statusEntry->item(0));
        if ($messageEntry->length == 0) {
            $subCodeEntry = self::query($dom, '/samlp:Response/samlp:Status/samlp:StatusCode/samlp:StatusCode', $statusEntry->item(0));
            if ($subCodeEntry->length > 0) {
                $status['msg'] = $subCodeEntry->item(0)->getAttribute('Value');
            } else {
                $status['msg'] = '';
            }
        } else {
            $msg = $messageEntry->item(0)->textContent;
            $status['msg'] = $msg;
        }

        return $status;
    }

    /**
     * Decrypts an encrypted element.
     *
     * @param DOMElement     $encryptedData The encrypted data.
     * @param XMLSecurityKey $inputKey      The decryption key.
     *
     * @return DOMElement  The decrypted element.
     */
    public static function decryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey)
    {

        $enc = new XMLSecEnc();

        $enc->setNode($encryptedData);
        $enc->type = $encryptedData->getAttribute("Type");

        $symmetricKey = $enc->locateKey($encryptedData);
        if (!$symmetricKey) {
            throw new Exception('Could not locate key algorithm in encrypted data.');
        }

        $symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
        if (!$symmetricKeyInfo) {
            throw new Exception('Could not locate <dsig:KeyInfo> for the encrypted key.');
        }

        $inputKeyAlgo = $inputKey->getAlgorith();
        if ($symmetricKeyInfo->isEncrypted) {
            $symKeyInfoAlgo = $symmetricKeyInfo->getAlgorith();

            if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
                $inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
            }

            if ($inputKeyAlgo !== $symKeyInfoAlgo) {
                throw new Exception(
                    'Algorithm mismatch between input key and key used to encrypt ' .
                    ' the symmetric key for the message. Key was: ' .
                    var_export($inputKeyAlgo, true) . '; message was: ' .
                    var_export($symKeyInfoAlgo, true)
                );
            }

            $encKey = $symmetricKeyInfo->encryptedCtx;
            $symmetricKeyInfo->key = $inputKey->key;
            $keySize = $symmetricKey->getSymmetricKeySize();
            if ($keySize === null) {
                // To protect against "key oracle" attacks
                throw new Exception('Unknown key size for encryption algorithm: ' . var_export($symmetricKey->type, true));
            }

            $key = $encKey->decryptKey($symmetricKeyInfo);
            if (strlen($key) != $keySize) {
                $encryptedKey = $encKey->getCipherValue();
                $pkey = openssl_pkey_get_details($symmetricKeyInfo->key);
                $pkey = sha1(serialize($pkey), true);
                $key = sha1($encryptedKey . $pkey, true);

                /* Make sure that the key has the correct length. */
                if (strlen($key) > $keySize) {
                    $key = substr($key, 0, $keySize);
                } elseif (strlen($key) < $keySize) {
                    $key = str_pad($key, $keySize);
                }
            }
            $symmetricKey->loadkey($key);
        } else {
            $symKeyAlgo = $symmetricKey->getAlgorith();
            if ($inputKeyAlgo !== $symKeyAlgo) {
                throw new Exception(
                    'Algorithm mismatch between input key and key in message. ' .
                    'Key was: ' . var_export($inputKeyAlgo, true) . '; message was: ' .
                    var_export($symKeyAlgo, true)
                );
            }
            $symmetricKey = $inputKey;
        }

        $decrypted = $enc->decryptNode($symmetricKey, false);

        $xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$decrypted.'</root>';
        $newDoc = new DOMDocument();
        $newDoc->preserveWhiteSpace = false;
        $newDoc->formatOutput = true;
        $newDoc = self::loadXML($newDoc, $xml);
        if (!$newDoc) {
            throw new Exception('Failed to parse decrypted XML.');
        }
 
        $decryptedElement = $newDoc->firstChild->firstChild;
        if ($decryptedElement === null) {
            throw new Exception('Missing encrypted element.');
        }

        return $decryptedElement;
    }

     /**
    * Converts a XMLSecurityKey to the correct algorithm.
    *
    * @param XMLSecurityKey $key The key.
    * @param string $algorithm The desired algorithm.
    * @param string $type Public or private key, defaults to public.
    * @return XMLSecurityKey The new key.
    * @throws Exception
    */
    public static function castKey(XMLSecurityKey $key, $algorithm, $type = 'public')
    {
        assert('is_string($algorithm)');
        assert('$type === "public" || $type === "private"');
        // do nothing if algorithm is already the type of the key
        if ($key->type === $algorithm) {
            return $key;
        }
        $keyInfo = openssl_pkey_get_details($key->key);
        if ($keyInfo === false) {
            throw new Exception('Unable to get key details from XMLSecurityKey.');
        }
        if (!isset($keyInfo['key'])) {
            throw new Exception('Missing key in public key details.');
        }
        $newKey = new XMLSecurityKey($algorithm, array('type'=>$type));
        $newKey->loadKey($keyInfo['key']);
        return $newKey;
    }

    /**
     * Adds signature key and senders certificate to an element (Message or Assertion).
     *
     * @param string|DomDocument $xml            The element we should sign
     * @param string             $key            The private key
     * @param string             $cert           The public
     * @param string             $signAlgorithm Signature algorithm method
     */
    public static function addSign($xml, $key, $cert, $signAlgorithm = XMLSecurityKey::RSA_SHA1)
    {
        if ($xml instanceof DOMDocument) {
            $dom = $xml;
        } else {
            $dom = new DOMDocument();
            $dom = self::loadXML($dom, $xml);
            if (!$dom) {
                throw new Exception('Error parsing xml string');
            }
        }

        /* Load the private key. */
        $objKey = new XMLSecurityKey($signAlgorithm, array('type' => 'private'));
        $objKey->loadKey($key, false);

        /* Get the EntityDescriptor node we should sign. */
        $rootNode = $dom->firstChild;

        /* Sign the metadata with our private key. */
        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $objXMLSecDSig->addReferenceList(
            array($rootNode),
            XMLSecurityDSig::SHA1,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
            array('id_name' => 'ID')
        );

        $objXMLSecDSig->sign($objKey);

        /* Add the certificate to the signature. */
        $objXMLSecDSig->add509Cert($cert, true);

        $insertBefore = $rootNode->firstChild;
        $messageTypes = array('AuthnRequest', 'Response', 'LogoutRequest','LogoutResponse');
        if (in_array($rootNode->localName, $messageTypes)) {
            $issuerNodes = self::query($dom, '/'.$rootNode->tagName.'/saml:Issuer');
            if ($issuerNodes->length == 1) {
                $insertBefore = $issuerNodes->item(0)->nextSibling;
            }
        }

        /* Add the signature. */
        $objXMLSecDSig->insertSignature($rootNode, $insertBefore);

        /* Return the DOM tree as a string. */
        $signedxml = $dom->saveXML();

        return $signedxml;
    }




    /**
     * Validates a signature (Message or Assertion).
     *
     * @param string|DomDocument $xml            The element we should validate
     * @param string|null        $cert           The pubic cert
     * @param string|null        $fingerprint    The fingerprint of the public cert
     * @param string|null        $fingerprintalg The algorithm used to get the fingerprint
     */
    public static function validateSign($xml, $cert = null, $fingerprint = null, $fingerprintalg = 'sha1')
    {
        if ($xml instanceof DOMDocument) {
            $dom = clone $xml;
        } else if ($xml instanceof DOMElement) {
            $dom = clone $xml->ownerDocument;
        } else {
            $dom = new DOMDocument();
            $dom = self::loadXML($dom, $xml);
        }

        # Check if Reference URI is empty
        try {
            $signatureElems = $dom->getElementsByTagName('Signature');
            foreach ($signatureElems as $signatureElem) {
                $referenceElems = $dom->getElementsByTagName('Reference');
                if (count($referenceElems) > 0) {
                    $referenceElem = $referenceElems->item(0);
                    if ($referenceElem->getAttribute('URI') == '') {
                        $referenceElem->setAttribute('URI', '#'.$signatureElem->parentNode->getAttribute('ID'));
                    }
                }
            }
        } catch (Exception $e) {
            //It's ok, let's continue;
        }

        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->idKeys = array('ID');

        $objDSig = $objXMLSecDSig->locateSignature($dom);
        if (!$objDSig) {
            throw new Exception('Cannot locate Signature Node');
        }

        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey) {
            throw new Exception('We have no idea about the key');
        }

        $objXMLSecDSig->canonicalizeSignedInfo();

        try {
            $retVal = $objXMLSecDSig->validateReference();
        } catch (Exception $e) {
            throw $e;
        }

        XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);

        if (!empty($cert)) {
            $objKey->loadKey($cert, false, true);
            return ($objXMLSecDSig->verify($objKey) === 1);
        } else {
            $domCert = $objKey->getX509Certificate();
            $domCertFingerprint = OneLogin_Saml2_Utils::calculateX509Fingerprint($domCert, $fingerprintalg);
            if (OneLogin_Saml2_Utils::formatFingerPrint($fingerprint) !== $domCertFingerprint) {
                return false;
            } else {
                $objKey->loadKey($domCert, false, true);
                return ($objXMLSecDSig->verify($objKey) === 1);
            }
        }
    }
}
