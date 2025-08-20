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

/**
 * @since 9.1
 */

namespace Glpi\Api;

use AllAssets;
use CommonDBTM;
use Document;
use GLPIUploadHandler;
use ReflectionClass;
use Safe\Exceptions\JsonException;
use stdClass;
use Toolbox;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\strtotime;

class APIRest extends API
{
    public static function getTypeName($nb = 0)
    {
        return __('Rest API');
    }

    /**
     * Upload and validate files from request and append to $this->parameters['input']
     *
     * @return void
     */
    public function manageUploadedFiles()
    {
        foreach (array_keys($_FILES) as $filename) {
            // Randomize files names
            $rand_name = uniqid('', true);
            if (is_array($_FILES[$filename]['name'])) {
                // Input name was suffixed by `[]`. This results in each `$_FILES[$filename]` property being an array.
                // e.g.
                // [
                //     'name' => [
                //         0 => 'image.jpg',
                //         1 => 'document.pdf',
                //     ],
                //     'type' => [
                //         0 => 'image/jpeg',
                //         1 => 'application/pdf',
                //     ]
                // ]
                foreach ($_FILES[$filename]['name'] as &$name) {
                    $name = $rand_name . $name;
                }
            } else {
                // Input name was NOT suffixed by `[]`. This results in each `$_FILES[$filename]` property being a single entry.
                // e.g.
                // [
                //     'name' => 'image.jpg',
                //     'type' => 'image/jpeg',
                // ]
                $name = &$_FILES[$filename]['name'];
                $name = $rand_name . $name;
            }

            $upload_result
            = GLPIUploadHandler::uploadFiles(['name'           => $filename,
                'print_response' => false,
            ]);
            foreach ($upload_result as $uresult) {
                foreach ($uresult as $file_result) {
                    $this->parameters['input']->_filename[]        = $file_result->name;
                    $this->parameters['input']->_prefix_filename[] = $file_result->prefix;
                }
            }
            $this->parameters['upload_result'][] = $upload_result;
        }
    }

    /**
     * Parse url and http body to retrieve :
     *  - HTTP VERB (GET/POST/DELETE/PUT)
     *  - Resource : Rest endpoint
     *  - Identifier
     *  - and parameters
     *
     * And send to method corresponding identified resource
     *
     * Then send response to client.
     *
     * @return void
     */
    public function call()
    {

        //parse http request and find parts
        $this->request_uri  = $_SERVER['REQUEST_URI'];
        $this->verb         = $_SERVER['REQUEST_METHOD'];
        $path_info          = (isset($_SERVER['PATH_INFO'])) ? str_replace("api/", "", $_SERVER['PATH_INFO']) : '';
        $path_info          = str_replace('v1/', '', $path_info);
        $path_info          = trim($path_info, '/');
        $this->url_elements = explode('/', $path_info);

        // retrieve requested resource
        $resource      = trim(strval($this->url_elements[0]));
        $is_inline_doc = (strlen($resource) == 0) || ($resource == "api");

        // Add headers for CORS
        $this->cors();

        // retrieve parameters (in body, query_string, headers)
        $this->parseIncomingParams($is_inline_doc);

        // show debug if required
        if (isset($this->parameters['debug'])) {
            $this->debug = $this->parameters['debug'];
            if (empty($this->debug)) {
                $this->debug = 1;
            }
        }

        // retrieve session (if exist)
        $this->retrieveSession();
        $this->initApi();
        $this->manageUploadedFiles();

        // retrieve param who permit session writing
        if (isset($this->parameters['session_write'])) {
            $this->session_write = (bool) $this->parameters['session_write'];
        }

        // Do not unlock the php session for ressources that may handle it
        if (in_array($resource, $this->getRessourcesWithSessionWrite())) {
            $this->session_write = true;
        }

        // Check API session unless blacklisted (init session, ...)
        if (!$is_inline_doc && !in_array($resource, $this->getRessourcesAllowedWithoutSession())) {
            $this->initEndpoint(true, $resource);
        }

        // inline documentation (api/)
        if ($is_inline_doc) {
            $this->inlineDocumentation("apirest.md");
        } elseif ($resource === "initSession") {
            // ## DECLARE ALL ENDPOINTS ##
            // login into glpi
            $this->returnResponse($this->initSession($this->parameters));
        } elseif ($resource === "killSession") {
            // logout from glpi
            $this->returnResponse($this->killSession());
        } elseif ($resource === "changeActiveEntities") {
            // change active entities
            $this->returnResponse($this->changeActiveEntities($this->parameters));
        } elseif ($resource === "getMyEntities") {
            // get all entities of logged user
            $this->returnResponse($this->getMyEntities($this->parameters));
        } elseif ($resource === "getActiveEntities") {
            // get curent active entity
            $this->returnResponse($this->getActiveEntities());
        } elseif ($resource === "changeActiveProfile") {
            // change active profile
            $this->returnResponse($this->changeActiveProfile($this->parameters));
        } elseif ($resource === "getMyProfiles") {
            // get all profiles of current logged user
            $this->returnResponse($this->getMyProfiles());
        } elseif ($resource === "getActiveProfile") {
            // get current active profile
            $this->returnResponse($this->getActiveProfile());
        } elseif ($resource === "getFullSession") {
            // get complete php session
            $this->returnResponse($this->getFullSession());
        } elseif ($resource === "getGlpiConfig") {
            // get complete php var $CFG_GLPI
            $this->returnResponse($this->getGlpiConfig());
        } elseif ($resource === "listSearchOptions") {
            // list searchOptions of an itemtype
            $itemtype = $this->getItemtype(1);
            $this->returnResponse($this->listSearchOptions($itemtype, $this->parameters));
        } elseif ($resource === "getMultipleItems") {
            // get multiple items (with various itemtype)
            $this->returnResponse($this->getMultipleItems($this->parameters));
        } elseif ($resource === "search") {
            // Search on itemtype
            $itemtype = $this->getItemtype(1, true, true);
            // clean stdObjects in parameter
            $params   = json_decode(json_encode($this->parameters), true);
            // search
            $response =  $this->searchItems($itemtype, $params);

            // add pagination headers
            $additionalheaders                  = [];
            $additionalheaders["Accept-Range"]  = $itemtype . " " . Toolbox::get_max_input_vars();
            if ($response['totalcount'] > 0) {
                $additionalheaders["Content-Range"] = $response['content-range'];
            }

            // different http return codes for complete or partial response
            if ($response['count'] >= $response['totalcount']) {
                $code = 200; // full content
            } else {
                $code = 206; // partial content
            }

            $this->returnResponse($response, $code, $additionalheaders);
        } elseif ($resource === "lostPassword") {
            if ($this->verb != 'PUT' && $this->verb != 'PATCH') {
                // forbid password reset when HTTP verb is not PUT or PATCH
                $this->returnError(__("Only HTTP verb PUT is allowed"));
            }
            $this->returnResponse($this->lostPassword($this->parameters));
        } elseif ($resource == 'getMassiveActions') {
            $this->returnResponse(
                $this->getMassiveActions(
                    $this->getItemtype(1, false, false),
                    $this->url_elements[2] ?? null,
                    json_decode(json_encode($this->parameters), true)['is_deleted'] ?? false,
                )
            );
        } elseif ($resource == "getMassiveActionParameters") {
            $this->returnResponse(
                $this->getMassiveActionParameters(
                    $this->getItemtype(1, false, false),
                    $this->url_elements[2] ?? null,
                    json_decode(json_encode($this->parameters), true)['is_deleted'] ?? false,
                )
            );
        } elseif ($resource == "applyMassiveAction") {
            // Parse parameters
            $params = json_decode(json_encode($this->parameters), true);
            $ids = $params['ids'] ?? [];

            if (empty($ids)) {
                $this->returnError("No ids supplied", 400, "ERROR_MASSIVEACTION_NO_IDS");
            }

            $this->applyMassiveAction(
                $this->getItemtype(1, false, false),
                $this->url_elements[2] ?? null,
                $ids,
                $params['input'] ?? []
            );
        } elseif (preg_match('%user/(\d+)/picture%i', $path_info, $matches)) {
            $this->userPicture((int) $matches[1]);
        } else {
            // commonDBTM manipulation
            $itemtype          = $this->getItemtype(0);
            $id                = $this->getId();
            $additionalheaders = [];
            $code              = 200;
            switch ($this->verb) {
                default:
                case "GET": // retrieve item(s)
                    if (
                        $itemtype === Document::class
                        && $id > 0
                        && (
                            ($_SERVER['HTTP_ACCEPT'] ?? null) === 'application/octet-stream'
                            || ($this->parameters['alt'] ?? null) === 'media'
                        )
                    ) {
                        // Raw document download
                        $document = new Document();
                        if (!$document->getFromDB($id)) {
                            $this->messageNotfoundError();
                        }
                        if (!$document->can($id, READ)) {
                            $this->messageRightError();
                        }
                        $document->getAsResponse()->send();
                        exit(); // @phpstan-ignore glpi.forbidExit (API response is streamed)
                    }

                    if ($id !== false) {
                        $response = $this->getItem($itemtype, $id, $this->parameters);
                        if (isset($response['date_mod'])) {
                            $datemod = strtotime($response['date_mod']);
                            $additionalheaders['Last-Modified'] = gmdate("D, d M Y H:i:s", $datemod) . " GMT";
                        }
                    } else {
                        // return collection of items
                        $totalcount = 0;
                        $response = $this->getItems($itemtype, $this->parameters, $totalcount);

                        //add pagination headers
                        $range = [0, $_SESSION['glpilist_limit']];
                        if (isset($this->parameters['range'])) {
                            $range = explode("-", $this->parameters['range']);
                        }

                        // fix end range
                        if ($range[1] > $totalcount - 1) {
                            $range[1] = $totalcount - 1;
                        }

                        // trigger partial content return code
                        if ($range[1] - $range[0] + 1 < $totalcount) {
                            $code = 206; // partial content
                        }

                        $additionalheaders["Accept-Range"]  = $itemtype . " " . Toolbox::get_max_input_vars();
                        if ($totalcount > 0) {
                            $additionalheaders["Content-Range"] = implode('-', $range) . "/" . $totalcount;
                        }
                    }
                    break;

                case "POST": // create item(s)
                    $response = $this->createItems($itemtype, $this->parameters);
                    $code     = 201;
                    if (isset($response['id'])) {
                        // add a location targetting created element
                        $additionalheaders['location'] = self::$api_url . "/$itemtype/" . $response['id'];
                    } else {
                        // add a link header targetting created elements
                        $additionalheaders['link'] = "";
                        foreach ($response as $created_item) {
                            if ($created_item['id']) {
                                $additionalheaders['link'] .= self::$api_url . "/$itemtype/"
                                                     . $created_item['id'] . ",";
                            }
                        }
                        // remove last comma
                        $additionalheaders['link'] = trim($additionalheaders['link'], ",");
                    }
                    break;

                case "PUT": // update item(s)
                case "PATCH": // update item(s)
                    if (!isset($this->parameters['input'])) {
                        $this->messageBadArrayError();
                    }

                    // if id is passed by query string, add it into input parameter
                    if (
                        is_object($this->parameters['input'])
                        && ($id > 0 || $id == 0 && $itemtype == "Entity")
                        && !property_exists($this->parameters['input'], 'id')
                    ) {
                        $this->parameters['input']->id = $id;
                    }
                    $response = $this->updateItems($itemtype, $this->parameters);
                    break;

                case "DELETE": //delete item(s)
                    // if id is passed by query string, construct an object with it
                    if ($id !== false) {
                        // override input
                        $this->parameters['input']     = new stdClass();
                        $this->parameters['input']->id = $id;
                    }
                    $response = $this->deleteItems($itemtype, $this->parameters);
                    break;
            }
            $this->returnResponse($response, $code, $additionalheaders);
        }

        $this->messageLostError();
    }


    /**
     * Retrieve and check itemtype from $this->url_elements
     *
     * @param integer $index      we'll find itemtype in this index of $this->url_elements
     *                            (default o)
     * @param boolean $recursive  can we go depper or we trigger an http error if we fail to find itemtype?
     *                            (default true)
     * @param boolean $all_assets if we can have allasset virtual type (default false)
     *
     * @return false|class-string<CommonDBTM>
     */
    private function getItemtype($index = 0, $recursive = true, $all_assets = false)
    {

        if (isset($this->url_elements[$index])) {
            $all_assets = $all_assets && $this->url_elements[$index] == AllAssets::getType();
            $valid_class = Toolbox::isCommonDBTM($this->url_elements[$index])
            || Toolbox::isAPIDeprecated($this->url_elements[$index]);

            if ($all_assets || $valid_class) {
                $itemtype = $this->url_elements[$index];

                if (
                    $recursive
                     && ($additional_itemtype = $this->getItemtype(2, false))
                ) {
                    $this->parameters['parent_itemtype'] = $itemtype;
                    $itemtype                            = $additional_itemtype;
                }

                // AllAssets
                if ($all_assets) {
                    return AllAssets::getType();
                }

                // Load namespace for deprecated
                $deprecated = Toolbox::isAPIDeprecated($itemtype);
                if ($deprecated) {
                    $itemtype = "Glpi\Api\Deprecated\\$itemtype";
                }

                // Get case sensitive itemtype name
                $itemtype = (new ReflectionClass($itemtype))->getName();
                if ($deprecated) {
                    // Remove deprecated namespace
                    $itemtype = str_replace("Glpi\Api\Deprecated\\", "", $itemtype);
                }
                return $itemtype;
            }
            $this->returnError(
                __("resource not found or not an instance of CommonDBTM"),
                400,
                "ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM"
            );
        } elseif ($recursive) {
            $this->returnError(__("missing resource"), 400, "ERROR_RESOURCE_MISSING");
        }

        return false;
    }


    /**
     * Retrieve in url_element the current id. If we have a multiple id (ex /Ticket/1/TicketFollwup/2),
     * it always find the second
     *
     * @return integer|boolean id of current itemtype (or false if not found)
     */
    private function getId()
    {

        $id            = isset($this->url_elements[1]) && is_numeric($this->url_elements[1])
                       ? intval($this->url_elements[1])
                       : false;
        $additional_id = isset($this->url_elements[3]) && is_numeric($this->url_elements[3])
                       ? intval($this->url_elements[3])
                       : false;

        if ($additional_id || isset($this->parameters['parent_itemtype'])) {
            $this->parameters['parent_id'] = $id;
            $id = $additional_id;
        }

        return $id;
    }


    /**
     * Construct this->parameters from query string and http body
     *
     * @param boolean $is_inline_doc Is the current request asks to display inline documentation
     *  This will remove the default behavior who set content-type to application/json
     *
     * @return void
     */
    public function parseIncomingParams($is_inline_doc = false)
    {

        $parameters = [];

        // first of all, pull the GET vars
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $parameters);
        }

        // now how about PUT/POST bodies? These override what we got from GET
        $body = trim($this->getHttpBody());
        if (strlen($body) > 0 && $this->verb == "GET") {
            // GET method requires an empty body
            $this->returnError(
                "GET Request should not have json payload (http body)",
                400,
                "ERROR_JSON_PAYLOAD_FORBIDDEN"
            );
        }

        $content_type = "";
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            $content_type = $_SERVER['HTTP_CONTENT_TYPE'];
        } else {
            if (!$is_inline_doc) {
                $content_type = "application/json";
            }
        }

        if (str_contains($content_type, "application/json")) {
            try {
                if ($body != '') {
                    $body_params = json_decode($body);
                    foreach ($body_params as $param_name => $param_value) {
                        $parameters[$param_name] = $param_value;
                    }
                }
            } catch (JsonException $e) {
                $this->returnError(
                    "JSON payload seems not valid",
                    400,
                    "ERROR_JSON_PAYLOAD_INVALID",
                    false
                );
            }
            $this->format = "json";
        } elseif (str_contains($content_type, "multipart/form-data")) {
            if (count($_FILES) <= 0) {
                // likely uploaded files is too big so $_REQUEST will be empty also.
                // see http://us.php.net/manual/en/ini.core.php#ini.post-max-size
                $this->returnError(
                    "The file seems too big",
                    400,
                    "ERROR_UPLOAD_FILE_TOO_BIG_POST_MAX_SIZE",
                    false
                );
            }

            // with this content_type, php://input is empty... (see http://php.net/manual/en/wrappers.php.php)
            try {
                $uploadManifest = json_decode($_REQUEST['uploadManifest']);
                foreach ($uploadManifest as $field => $value) {
                    $parameters[$field] = $value;
                }
                $this->format = "json";

                // move files into _tmp folder
                $parameters['upload_result'] = [];
                $parameters['input']->_filename = [];
                $parameters['input']->_prefix_filename = [];
            } catch (JsonException $e) {
                $this->returnError(
                    "JSON payload seems not valid",
                    400,
                    "ERROR_JSON_PAYLOAD_INVALID",
                    false
                );
            }
        } elseif (str_contains($content_type, "application/x-www-form-urlencoded")) {
            parse_str($body, $postvars);
            foreach ($postvars as $field => $value) {
                // $parameters['input'] needs to be an object when process API Request
                if ($field === 'input') {
                    $value = (object) $value;
                }
                $parameters[$field] = $value;
            }
            $this->format = "html";
        } else {
            $this->format = "html";
        }

        // retrieve HTTP headers
        $headers = getallheaders();
        if (count($headers) > 0) {
            $fixedHeaders = [];
            foreach ($headers as $key => $value) {
                $fixedHeaders[ucwords(strtolower($key), '-')] = $value;
            }
            $headers = $fixedHeaders;
        }

        // try to retrieve basic auth
        if (
            isset($_SERVER['PHP_AUTH_USER'])
            && isset($_SERVER['PHP_AUTH_PW'])
        ) {
            $parameters['login']    = $_SERVER['PHP_AUTH_USER'];
            $parameters['password'] = $_SERVER['PHP_AUTH_PW'];
        }

        // try to retrieve user_token in header
        if (
            isset($headers['Authorization'])
            && (str_contains($headers['Authorization'], 'user_token'))
        ) {
            $auth = explode(' ', $headers['Authorization']);
            if (isset($auth[1])) {
                $parameters['user_token'] = $auth[1];
            }
        }

        // try to retrieve session_token in header
        if (isset($headers['Session-Token'])) {
            $parameters['session_token'] = $headers['Session-Token'];
        }

        // try to retrieve app_token in header
        if (isset($headers['App-Token'])) {
            $parameters['app_token'] = $headers['App-Token'];
        }

        // check boolean parameters
        foreach ($parameters as $key => &$parameter) {
            if ($parameter === "true") {
                $parameter = true;
            }
            if ($parameter === "false") {
                $parameter = false;
            }
        }

        $this->parameters = $parameters;

    }


    public function returnResponse($response, $httpcode = 200, $additionalheaders = [])
    {

        if (empty($httpcode)) {
            $httpcode = 200;
        }

        foreach ($additionalheaders as $key => $value) {
            header("$key: $value");
        }

        http_response_code($httpcode); // @phpstan-ignore glpi.forbidHttpResponseCode (API response is streamed)
        $this->header($this->debug);

        if ($response !== null) {
            $json = json_encode(
                $response,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($this->debug ? JSON_PRETTY_PRINT : 0)
            );
        } else {
            $json = '';
        }

        echo $json;

        exit(); // @phpstan-ignore glpi.forbidExit (API response is streamed)
    }


    /**
     * Display the APIRest Documentation in Html (parsed from markdown)
     *
     * @param string $file relative path of documentation file (default 'apirest.md')
     *
     * @return void
     */
    public function inlineDocumentation($file = "apirest.md")
    {

        if ($this->format == "html") {
            parent::inlineDocumentation($file);
        } elseif ($this->format == "json") {
            echo file_get_contents(GLPI_ROOT . '/' . $file);
        }
        exit(); // @phpstan-ignore glpi.forbidExit (API response is streamed)
    }
}
