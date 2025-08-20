<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Agent\Communication;

use Config;
use DOMDocument;
use DOMElement;
use Glpi\Agent\Communication\Headers\Common;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\Http\HttpException;
use Glpi\Exception\OAuth2KeyException;
use Glpi\Http\Request;
use Glpi\Inventory\Conf;
use Glpi\OAuth\Server;
use GLPIKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use RuntimeException;
use Safe\Exceptions\SimplexmlException;
use Toolbox;
use UnexpectedValueException;

use function Safe\base64_decode;
use function Safe\gzcompress;
use function Safe\gzdecode;
use function Safe\gzdeflate;
use function Safe\gzencode;
use function Safe\gzinflate;
use function Safe\gzuncompress;
use function Safe\iconv;
use function Safe\ini_get;
use function Safe\ini_set;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\simplexml_load_string;

/**
 * Handle agent requests
 * Both XML (legacy) and JSON inventory formats are supported.
 *
 * @see https://github.com/glpi-project/inventory_format/blob/master/inventory.schema.json
 */
abstract class AbstractRequest
{
    public const DEFAULT_FREQUENCY = 24;

    public const XML_MODE    = 0;
    public const JSON_MODE   = 1;

    //FusionInventory agent
    public const PROLOG_QUERY = 'prolog';
    public const INVENT_QUERY = 'inventory';
    public const SNMP_QUERY   = 'snmp';
    public const OLD_SNMP_QUERY   = 'snmpquery';

    //GLPI AGENT ACTION
    public const CONTACT_ACTION = 'contact';
    public const REGISTER_ACTION = 'register';
    public const CONFIG_ACTION = 'configuration';
    public const INVENT_ACTION = 'inventory';
    public const NETDISCOVERY_ACTION = 'netdiscovery';
    public const NETINV_ACTION = 'netinventory';
    public const ESX_ACTION = 'esx';
    public const COLLECT_ACTION = 'collect';
    public const DEPLOY_ACTION = 'deploy';
    public const WOL_ACTION = 'wakeonlan';
    public const GET_PARAMS = 'get_params';

    //GLPI AGENT TASK
    public const INVENT_TASK = 'inventory';
    public const NETDISCOVERY_TASK = 'netdiscovery';
    public const NETINV_TASK = 'netinventory';
    public const ESX_TASK = 'esx';
    public const COLLECT_TASK = 'collect';
    public const DEPLOY_TASK = 'deploy';
    public const WOL_TASK = 'wakeonlan';
    public const REMOTEINV_TASK = 'remoteinventory';

    public const COMPRESS_NONE = 0;
    public const COMPRESS_ZLIB = 1;
    public const COMPRESS_GZIP = 2;
    public const COMPRESS_BR   = 3;
    public const COMPRESS_DEFLATE = 4;

    /** @var ?integer */
    protected ?int $mode = null;
    /** @var string */
    private string $deviceid;
    /** @var DOMDocument|array|null */
    private DOMDocument|array|null $response = null;
    /** @var ?integer */
    private ?int $compression = null;
    /** @var boolean */
    private bool $error = false;
    /** @var boolean */
    protected bool $test_rules = false;
    /** @var Common */
    protected Common $headers;
    /** @var int */
    private int $http_response_code = 200;
    /** @var string */
    protected string $query;
    protected bool $local = false;

    public function __construct()
    {
        $this->headers = $this->initHeaders();
        $this->handleContentType($_SERVER['CONTENT_TYPE'] ?? false);
    }

    abstract protected function initHeaders(): Common;

    /**
     * Set mode and initialize response
     *
     * @param integer $mode Expected mode. One of *_MODE constants
     *
     * @return void
     *
     * @throw RuntimeException
     */
    protected function setMode(int $mode): void
    {
        $this->mode = $mode;
        switch ($mode) {
            case self::XML_MODE:
                $this->response = new DOMDocument();
                $this->response->appendChild(
                    $this->response->createElement('REPLY')
                );
                break;
            case self::JSON_MODE:
                $this->response = [];
                break;
            default:
                throw new RuntimeException("Unknown mode $mode");
        }
        $this->prepareHeaders();
    }

    /**
     * Guess import mode
     *
     * @param mixed $contents
     *
     * @return void
     */
    private function guessMode(mixed $contents): void
    {
        // In the case handleContentType() didn't set mode, just check $contents first char
        if ($contents[0] === '{') {
            $this->setMode(self::JSON_MODE);
        } else {
            //defaults to XML; whose validity is checked later.
            $this->setMode(self::XML_MODE);
        }
    }

    /**
     * Display module name
     *
     * @param ?string $internalModule
     *
     * @return string readable method name
     */
    public static function getModuleName(?string $internalModule): string
    {
        switch ($internalModule) {
            case self::INVENT_QUERY:
            case self::INVENT_ACTION:
                return __("Inventory");
            case self::OLD_SNMP_QUERY:
            case self::SNMP_QUERY:
            case self::NETINV_ACTION:
                return __("Network inventory (SNMP)");
            case self::NETDISCOVERY_ACTION:
                return __("Network discovery (SNMP)");
            default:
                return $internalModule ?? '';
        }
    }

    /**
     * Handle request headers
     *
     * @return void
     */
    public function handleHeaders(): void
    {
        $req_headers = getallheaders();
        $this->headers->setHeaders($req_headers);
    }

    /**
     * Handle agent request
     *
     * @param mixed $data Sent data
     *
     * @return boolean
     */
    public function handleRequest(mixed $data): bool
    {
        $base_mode = $this->mode;
        $guess_mode = ($base_mode === null);
        $this->setMode(self::JSON_MODE);

        $auth_required = false;
        if (!$this->isLocal()) {
            $auth_required = Config::getConfigurationValue('inventory', 'auth_required');
        }
        if ($auth_required === Conf::CLIENT_CREDENTIALS) {
            $request = new Request('POST', $_SERVER['REQUEST_URI'], $this->headers->getHeaders());
            try {
                $client = Server::validateAccessToken($request);
                if (!in_array('inventory', $client['scopes'], true)) {
                    $this->addError('Access denied. Agent must authenticate using client credentials and have the "inventory" OAuth scope', 401);
                    return false;
                }
            } catch (OAuth2KeyException $e) {
                ErrorHandler::logCaughtException($e);
                $this->addError($e->getMessage());
                return false;
            } catch (OAuthServerException) {
                $this->addError('Authorization header required to send an inventory', 401);
                return false;
            }
        }

        if ($auth_required === Conf::BASIC_AUTH) {
            $authorization_header = $this->headers->getHeader('Authorization');
            if (is_null($authorization_header)) {
                $this->headers->setHeader("www-authenticate", 'Basic realm="basic"');
                $this->addError('Authorization header required to send an inventory', 401);
                return false;
            } else {
                $allowed = false;
                // if Authorization start with 'Basic'
                if (preg_match('/^Basic\s+(.*)$/i', $authorization_header, $matches)) {
                    $inventory_login = Config::getConfigurationValue('inventory', 'basic_auth_login');
                    $inventory_password = (new GLPIKey())
                        ->decrypt(Config::getConfigurationValue('inventory', 'basic_auth_password'));
                    $agent_credential = base64_decode($matches[1]);
                    [$agent_login, $agent_password] = explode(':', $agent_credential, 2);
                    if (
                        $inventory_login == $agent_login
                        && $inventory_password == $agent_password
                    ) {
                        $allowed = true;
                    }
                }
                if (!$allowed) {
                    $this->addError('Access denied. Wrong login or password for basic authentication.', 401);
                    return false;
                }
            }
        }

        // Some network inventories may request may contain lots of information.
        // e.g. a Huawei S5720-52X-LI-AC inventory file may weigh 20MB,
        // and GLPI will consume about 500MB of memory to handle it,
        // and may take up to 2 minutes on server that has low performances.
        //
        // Setting limits to 1GB / 5 minutes should permit to handle any inventories request.
        $memory_limit       = (int) Toolbox::getMemoryLimit();
        $max_execution_time = ini_get('max_execution_time');
        if ($memory_limit > 0 && $memory_limit < (1024 * 1024 * 1024)) {
            ini_set('memory_limit', '1024M');
        }
        if ($max_execution_time > 0 && $max_execution_time < 300) {
            ini_set('max_execution_time', '300');
        }

        if ($this->compression !== self::COMPRESS_NONE) {
            switch ($this->compression) {
                case self::COMPRESS_ZLIB:
                    $data = gzuncompress($data);
                    break;
                case self::COMPRESS_GZIP:
                    $data = gzdecode($data);
                    break;
                case self::COMPRESS_BR:
                    if (!function_exists('brotli_uncompress')) {
                        trigger_error(
                            'Brotli PHP extension is required to handle Brotli compression algorithm in inventory feature.',
                            E_USER_WARNING
                        );
                    } else {
                        $data = brotli_uncompress($data);
                    }
                    break;
                case self::COMPRESS_DEFLATE:
                    $data = gzinflate($data);
                    break;
                default:
                    throw new UnexpectedValueException("Unknown compression mode" . $this->compression);
            }
        }

        if ($guess_mode) {
            $this->guessMode($data);
        } else {
            $this->setMode($base_mode);
        }

        //load and check data
        return match ($this->mode) {
            self::XML_MODE => $this->handleXMLRequest($data),
            self::JSON_MODE => $this->handleJSONRequest($data),
            default => false,
        };
    }

    /**
     * Handle Query
     *
     * @param string $action  Action (one of self::*_ACTION)
     * @param ?mixed $content Contents, optional
     *
     * @return boolean
     */
    abstract protected function handleAction(string $action, mixed $content = null): bool;

    /**
     * Handle Task
     *
     * @param string $task  Task (one of self::*_TASK)
     *
     * @return array
     */
    abstract protected function handleTask(string $task): array;

    /**
     * Handle XML request
     *
     * @param string $data Sent XML
     *
     * @return boolean
     */
    public function handleXMLRequest(string $data): bool
    {
        libxml_use_internal_errors(true);

        if (mb_detect_encoding($data, 'UTF-8', true) === false) {
            $data = iconv('ISO-8859-1', 'UTF-8', $data);
        }
        try {
            $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        } catch (SimplexmlException $e) {
            $xml_errors = libxml_get_errors();
            /* @var \LibXMLError $xml_error */
            foreach ($xml_errors as $xml_error) {
                \trigger_error(
                    \sprintf(
                        'XML error `%s` at line %d.',
                        $xml_error->message,
                        $xml_error->line
                    ),
                    E_USER_WARNING
                );
            }
            $this->addError('XML not well formed!', 400);
            return false;
        } finally {
            libxml_clear_errors();
        }

        $this->deviceid = (string) $xml->DEVICEID;
        //query is not mandatory. Defaults to inventory
        $action = self::INVENT_QUERY;
        if (property_exists($xml, 'QUERY')) {
            $action = strtolower((string) $xml->QUERY);
        }

        return $this->handleAction($action, $xml);
    }

    /**
     * Handle JSON request
     *
     * @param string $data Sent JSON
     *
     * @return boolean
     */
    public function handleJSONRequest(string $data): bool
    {
        if (!Toolbox::isJSON($data)) {
            $this->addError('JSON not well formed!', 400);
            return false;
        }

        $jdata = json_decode($data);

        $this->deviceid = $jdata->deviceid ?? null;
        $action = self::INVENT_ACTION;
        if (property_exists($jdata, 'action')) {
            $action = $jdata->action;
        } elseif (property_exists($jdata, 'query')) {
            $action = $jdata->query;
        }

        return $this->handleAction($action, $jdata);
    }

    /**
     * Get request mode
     *
     * @return null|integer One of self::*_MODE
     */
    public function getMode(): ?int
    {
        return $this->mode;
    }

    /**
     * Adds an error
     *
     * @param ?string $message Error message
     * @param integer $code    HTTP response code
     *
     * @return void
     */
    public function addError(?string $message, int $code = 500): void
    {
        if ($code >= 400) {
            $this->error = true;
        }
        $this->http_response_code = $code;
        if (!empty($message)) {
            $message = mb_strlen($message, 'UTF-8') < 250 ? $message : mb_substr($message, 0, 250, 'UTF-8');
            if ($this->mode === self::JSON_MODE) {
                $this->addToResponse([
                    'status' => 'error',
                    'message' => $message,
                    'expiration' => self::DEFAULT_FREQUENCY,
                ]);
            } else {
                $this->addToResponse([
                    'ERROR' => [
                        'content'    => $message,
                        'attributes' => [],
                        'type'       => XML_CDATA_SECTION_NODE,
                    ],
                ]);
            }
        }
    }

    /**
     * Add elements to response
     *
     * @param array $entries Array of key => values entries
     *
     * @return void
     */
    public function addToResponse(array $entries): void
    {
        if ($this->mode === self::XML_MODE) {
            $root = $this->response->documentElement;
            foreach ($entries as $name => $content) {
                $this->addNode($root, $name, $content);
            }
        } else {
            foreach ($entries as $name => $content) {
                if ($name == "message" && isset($this->response[$name])) {
                    $this->response[$name] .= ";$content";
                } elseif ($name == "disabled") {
                    $this->response[$name][] = $content;
                } else {
                    $this->response[$name] = $content;
                }
            }
        }
    }

    /**
     * Add node to response for XML_MODE
     *
     * @param DOMElement        $parent  Parent element
     * @param ?mixed            $name    Element name to create
     * @param array|string|null $content Element contents, if any
     *
     * @return void
     */
    private function addNode(DOMElement $parent, mixed $name, array|string|null $content): void
    {
        if (is_array($content) && !isset($content['content']) && !isset($content['attributes'])) {
            if (is_string($name)) {
                $node = $parent->appendChild($this->response->createElement($name));
                if (!$node instanceof DOMElement) {
                    // Should never actually happen but help with static analysis
                    throw new RuntimeException("Node is not a DOMElement");
                }
            } else {
                $node = $parent;
            }

            foreach ($content as $sname => $scontent) {
                $this->addNode($node, $sname, $scontent);
            }
        } else {
            $type = $content['type'] ?? null;
            $attributes = [];
            if (is_array($content) && isset($content['content']) && isset($content['attributes'])) {
                $attributes = $content['attributes'];
                $content = $content['content'];
            }

            if ($type == XML_CDATA_SECTION_NODE) {
                // Handle CDATA sections
                $new_node = $this->response->createElement($name);
                $cdata = $this->response->createCDATASection($content);
                $new_node->appendChild($cdata);
            } else {
                // Normal sections
                $new_node = $this->response->createElement(
                    $name,
                    $content
                );
            }

            if (count($attributes)) {
                foreach ($attributes as $aname => $avalue) {
                    $attr = $this->response->createAttribute($aname);
                    $attr->value = $avalue;
                    $new_node->appendChild($attr);
                }
            }

            $parent->appendChild($new_node);
        }
    }


    /**
     * Get content-type
     *
     * @return string
     */
    public function getContentType(): string
    {
        if ($this->mode === null) {
            throw new RuntimeException("Mode has not been set");
        }

        if ($this->compression !== null) {
            switch (strtolower((string) $this->compression)) {
                case self::COMPRESS_ZLIB:
                    return 'application/x-compress-zlib';
                case self::COMPRESS_GZIP:
                    return 'application/x-compress-gzip';
                case self::COMPRESS_BR:
                    return 'application/x-br';
                case self::COMPRESS_DEFLATE:
                    return 'application/x-compress-deflate';
            }
        }

        return match ($this->mode) {
            self::XML_MODE => 'application/xml',
            self::JSON_MODE => 'application/json',
            default => throw new RuntimeException("Unknown mode " . $this->mode),
        };
    }

    /**
     * Get response
     *
     * @return string
     */
    public function getResponse(): string
    {
        // Default to return empty response on no response set
        $data = "";
        if ($this->response !== null) {
            if ($this->mode === null) {
                throw new RuntimeException("Mode has not been set");
            }

            $data = match ($this->mode) {
                self::XML_MODE => trim($this->response->saveXML()),
                self::JSON_MODE => json_encode($this->response),
                default => throw new UnexpectedValueException("Unknown mode " . $this->mode),
            };

            if ($this->compression === null) {
                throw new RuntimeException("Compression has not been set");
            }

            if ($this->compression !== self::COMPRESS_NONE) {
                switch ($this->compression) {
                    case self::COMPRESS_ZLIB:
                        $data = gzcompress($data);
                        break;
                    case self::COMPRESS_GZIP:
                        $data = gzencode($data);
                        break;
                    case self::COMPRESS_BR:
                        if (!function_exists('brotli_compress')) {
                            trigger_error(
                                'Brotli PHP extension is required to handle Brotli compression algorithm in inventory feature.',
                                E_USER_WARNING
                            );
                        } else {
                            $data = brotli_compress($data);
                        }
                        break;
                    case self::COMPRESS_DEFLATE:
                        $data = gzdeflate($data);
                        break;
                    default:
                        throw new UnexpectedValueException("Unknown compression mode" . $this->compression);
                }
            }
        }

        return $data;
    }

    /**
     * Handle Content-Type header
     *
     * @param string $type Content type
     *
     * @return void
     */
    public function handleContentType(string $type): void
    {
        switch (strtolower($type)) {
            case 'application/x-zlib':
            case 'application/x-compress-zlib':
                $this->compression = self::COMPRESS_ZLIB;
                break;
            case 'application/x-gzip':
            case 'application/x-compress-gzip':
                $this->compression = self::COMPRESS_GZIP;
                break;
            case 'application/x-br':
            case 'application/x-compress-br':
                if (!function_exists('brotli_compress')) {
                    $exception = new HttpException(415, 'Brotli PHP extension is missing!');
                    $exception->setMessageToDisplay('Unsupported compression');
                    throw $exception;
                } else {
                    $this->compression = self::COMPRESS_BR;
                }
                break;
            case 'application/x-deflate':
            case 'application/x-compress-deflate':
                $this->compression = self::COMPRESS_DEFLATE;
                break;
            case 'application/xml':
                $this->compression = self::COMPRESS_NONE;
                $this->setMode(self::XML_MODE);
                break;
            case 'application/json':
                $this->setMode(self::JSON_MODE);
                $this->compression = self::COMPRESS_NONE;
                break;
            case 'text/plain': //probably JSON
            default:
                $this->compression = self::COMPRESS_NONE;
                break;
        }
    }

    /**
     * Is current request in error?
     *
     * @return boolean
     */
    public function inError(): bool
    {
        return $this->error;
    }

    public function testRules(): self
    {
        $this->test_rules = true;
        return $this;
    }

    /**
     * Accepted encodings
     *
     * @return string[]
     */
    public function acceptedEncodings(): array
    {
        $encodings = [
            'gzip',
            'deflate',
        ];

        if (!function_exists('brotli_compress')) {
            $encodings[] = 'br';
        }

        return $encodings;
    }

    /**
     * Prepare HTTP headers
     *
     * @return void
     */
    private function prepareHeaders(): void
    {
        $headers = [
            'Content-Type' => $this->getContentType(),
        ];
        $this->headers->setHeaders($headers);
    }

    /**
     * Get HTTP headers
     *
     * @param boolean $legacy Set to true to shunt required headers checks
     *
     * @return array
     */
    public function getHeaders(bool $legacy = true): array
    {
        return $this->headers->getHeaders($legacy);
    }

    public function getHttpResponseCode(): int
    {
        return $this->http_response_code;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getDeviceID(): string
    {
        return $this->deviceid;
    }

    /**
     * Handle GLPI framework messages
     *
     * @return void
     */
    public function handleMessages(): void
    {
        if (count($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'];
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
            foreach ($messages as $code => $all_messages) {
                if ($code != INFO) {
                    foreach ($all_messages as $message) {
                        $this->addError($message, 500);
                    }
                }
            }
        }
    }

    /**
     * Mark inventory as local
     * @return $this
     */
    public function setLocal(): self
    {
        $this->local = true;
        return $this;
    }

    /**
     * Is inventory local?
     *
     * @return boolean
     */
    public function isLocal(): bool
    {
        return $this->local;
    }
}
