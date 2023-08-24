<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Api;

use Toolbox;

class APIXmlrpc extends API
{
    public static $content_type = "application/xml";

    public static function getTypeName($nb = 0)
    {
        return __('XMLRPC API');
    }

    /**
     * Upload and validate files from request and append to $this->parameters['input']
     *
     * @return void
     */
    public function manageUploadedFiles()
    {
    }

    /**
     * parse POST var to retrieve
     *  - Resource
     *  - Identifier
     *  - and parameters
     *
     * And send to method corresponding identified resource
     *
     * Then send response to client.
     *
     * @since 9.1
     *
     * @return void xmlrpc response
     */
    public function call()
    {
        Toolbox::logInfo('Deprecated: Usage of XML-RPC has been deprecated. Please use REST API.');

        $resource = $this->parseIncomingParams();

        // retrieve session (if exist)
        $this->retrieveSession();
        $this->initApi();

        $code = 200;

        // Do not unlock the php session for ressources that may handle it
        if (in_array($resource, $this->getRessourcesWithSessionWrite())) {
            $this->session_write = true;
        }

        // Check API session unless blacklisted (init session, ...)
        if (!in_array($resource, $this->getRessourcesAllowedWithoutSession())) {
            $this->initEndpoint(true, $resource);
        }

        if ($resource === "initSession") {
            $this->returnResponse($this->initSession($this->parameters));
        } elseif ($resource === "killSession") { // logout from glpi
            $this->returnResponse($this->killSession());
        } elseif ($resource === "changeActiveEntities") { // change active entities
            $this->returnResponse($this->changeActiveEntities($this->parameters));
        } elseif ($resource === "getMyEntities") { // get all entities of logged user
            $this->returnResponse($this->getMyEntities($this->parameters));
        } elseif ($resource === "getActiveEntities") { // get curent active entity
            $this->returnResponse($this->getActiveEntities());
        } elseif ($resource === "changeActiveProfile") { // change active profile
            $this->returnResponse($this->changeActiveProfile($this->parameters));
        } elseif ($resource === "getMyProfiles") { // get all profiles of current logged user
            $this->returnResponse($this->getMyProfiles());
        } elseif ($resource === "getActiveProfile") { // get current active profile
            $this->returnResponse($this->getActiveProfile());
        } elseif ($resource === "getFullSession") { // get complete php session
            $this->returnResponse($this->getFullSession());
        } elseif ($resource === "getGlpiConfig") { // get complete php var $CFG_GLPI
            $this->returnResponse($this->getGlpiConfig());
        } elseif ($resource === "getMultipleItems") { // get multiple items (with various itemtype)
            $this->returnResponse($this->getMultipleItems($this->parameters));
        } elseif ($resource === "listSearchOptions") { // list searchOptions of an itemtype
            $this->returnResponse($this->listSearchOptions(
                $this->parameters['itemtype'],
                $this->parameters
            ));
        } elseif ($resource === "search") { // Search on itemtype
            $this->checkSessionToken();

            // search
            $response =  $this->searchItems($this->parameters['itemtype'], $this->parameters);

            // add pagination headers
            $additionalheaders                  = [];
            $additionalheaders["Accept-Range"]  = $this->parameters['itemtype'] . " "
                                               . Toolbox::get_max_input_vars();
            if ($response['totalcount'] > 0) {
                $additionalheaders["Content-Range"] = $response['content-range'];
            }

            // different http return codes for complete or partial response
            if ($response['count'] < $response['totalcount']) {
                $code = 206; // partial content
            }

            $this->returnResponse($response, $code, $additionalheaders);
        } elseif ($resource === "lostPassword") {
            $this->returnResponse($this->lostPassword($this->parameters));
        } elseif (
            in_array(
                $resource,
                ["getItem", "getItems", "createItems", "updateItems", "deleteItems"]
            )
        ) {
            // commonDBTM manipulation

            // check itemtype parameter
            if (!isset($this->parameters['itemtype'])) {
                $this->returnError(__("missing itemtype"), 400, "ITEMTYPE_RESOURCE_MISSING");
            }
            if (
                !class_exists($this->parameters['itemtype'])
                || !is_subclass_of($this->parameters['itemtype'], 'CommonDBTM')
            ) {
                $this->returnError(
                    __("itemtype not found or not an instance of CommonDBTM"),
                    400,
                    "ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM"
                );
            } elseif ($resource === "getItem") { // get an CommonDBTM item
                // check id parameter
                if (!isset($this->parameters['id'])) {
                    $this->returnError(__("missing id"), 400, "ID_RESOURCE_MISSING");
                }

                $response = $this->getItem($this->parameters['itemtype'], $this->parameters['id'], $this->parameters);

                $additionalheaders = [];
                if (isset($response['date_mod'])) {
                    $datemod = strtotime($response['date_mod']);
                    $additionalheaders['Last-Modified'] = gmdate("D, d M Y H:i:s", $datemod) . " GMT";
                }
                $this->returnResponse($response, 200, $additionalheaders);
            } elseif ($resource === "getItems") { // get a collection of a CommonDBTM item
                // return collection of items
                $totalcount = 0;
                $response = $this->getItems($this->parameters['itemtype'], $this->parameters, $totalcount);

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

                $additionalheaders                  = [];
                $additionalheaders["Accept-Range"]  = $this->parameters['itemtype'] . " " .
                                                  Toolbox::get_max_input_vars();
                if ($totalcount > 0) {
                    $additionalheaders["Content-Range"] = implode('-', $range) . "/" . $totalcount;
                }

                $this->returnResponse($response, $code, $additionalheaders);
            } elseif ($resource === "createItems") { // create one or many CommonDBTM items
                $response = $this->createItems($this->parameters['itemtype'], $this->parameters);

                $additionalheaders = [];
                if (isset($response['id'])) {
                    // add a location targetting created element
                    $additionalheaders['location'] = self::$api_url . "/" . $this->parameters['itemtype'] . "/" . $response['id'];
                } else {
                    // add a link header targetting created elements
                    $additionalheaders['link'] = "";
                    foreach ($response as $created_item) {
                        if ($created_item['id']) {
                            $additionalheaders['link'] .= self::$api_url . "/" . $this->parameters['itemtype'] .
                                                  "/" . $created_item['id'] . ",";
                        }
                    }
                    // remove last comma
                    $additionalheaders['link'] = trim($additionalheaders['link'], ",");
                }
                $this->returnResponse($response, 201);
            } elseif ($resource === "updateItems") { // update one or many CommonDBTM items
                $this->returnResponse($this->updateItems(
                    $this->parameters['itemtype'],
                    $this->parameters
                ));
            } elseif ($resource === "deleteItems") { // delete one or many CommonDBTM items
                if (isset($this->parameters['id'])) {
                    // override input
                    $this->parameters['input'] = new \stdClass();
                    $this->parameters['input']->id = $this->parameters['id'];
                }
                $this->returnResponse(
                    $this->deleteItems(
                        $this->parameters['itemtype'],
                        $this->parameters
                    ),
                    $code
                );
            }
        }

        $this->messageLostError();
    }


    /**
     * Construct this->parameters from POST data
     *
     * @since 9.1
     *
     * @return string
     */
    public function parseIncomingParams()
    {
        $parameters = [];
        $resource = "";

        $parameters = xmlrpc_decode_request(
            trim($this->getHttpBody()),
            $resource,
            'UTF-8'
        );

        $this->parameters = (isset($parameters[0]) && is_array($parameters[0])
                          ? $parameters[0]
                          : []);

       // transform input from array to object
        if (
            isset($this->parameters['input'])
            && is_array($this->parameters['input'])
        ) {
            $first_field = array_values($this->parameters['input'])[0];
            if (is_array($first_field)) {
                foreach ($this->parameters['input'] as &$input) {
                    $input = json_decode(json_encode($input), false);
                }
            } else {
                $this->parameters['input'] = json_decode(
                    json_encode($this->parameters['input']),
                    false
                );
            }
        }

       // check boolean parameters
        foreach ($this->parameters as &$parameter) {
            if ($parameter === "true") {
                $parameter = true;
            }
            if ($parameter === "false") {
                $parameter = false;
            }
        }

        return $resource;
    }


    protected function returnResponse($response, $httpcode = 200, $additionalheaders = [])
    {
        if (empty($httpcode)) {
            $httpcode = 200;
        }

        foreach ($additionalheaders as $key => $value) {
            header("$key: $value");
        }

        http_response_code($httpcode);
        $this->header($this->debug);

        $response = $this->escapekeys($response);
        $out = xmlrpc_encode_request(null, $response, ['encoding' => 'UTF-8',
            'escaping' => 'markup'
        ]);
        echo $out;
        exit;
    }

    /**
     * Add a space before all numeric keys to prevent their deletion by xmlrpc_encode_request function
     * see https://bugs.php.net/bug.php?id=21949
     *
     * @since 9.1
     *
     * @param  array $response the response array to escape
     *
     * @return array the escaped response.
     */
    protected function escapekeys($response = [])
    {
        if (is_array($response)) {
            $escaped_response = [];
            foreach ($response as $key => $value) {
                if (is_integer($key)) {
                    $key = " " . $key;
                }
                if (is_array($value)) {
                    $value = $this->escapekeys($value);
                }
                $escaped_response[$key] = $value;
            }
            return $escaped_response;
        }
        return $response;
    }
}
