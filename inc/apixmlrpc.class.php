<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

class APIXmlrpc extends API {
   protected $request_uri;
   protected $url_elements;
   protected $verb;
   protected $parameters;
   protected $debug = 0;
   protected $format = "json";

   static $content_type = "application/xml";

   public static function getTypeName($nb = 0) {
      return __('XMLRPC API');
   }

   /**
    * Upload and validate files from request and append to $this->parameters['input']
    *
    * @return void
    */
   public function manageUploadedFiles() {
   }

   /**
    * parse POST var to retrieve
    *  - Resource
    *  - Identifier
    *  - and parameters
    *
    *  And send to method corresponding identified resource
    *
    * @since 9.1
    *
    * @return mixed xmlrpc response
    */
   public function call() {
      $resource = $this->parseIncomingParams();

      // retrieve session (if exist)
      $this->retrieveSession();
      $this->initApi();

      $code = 200;

      if ($resource === "initSession") {
         $this->session_write = true;
         return $this->returnResponse($this->initSession($this->parameters));

      } else if ($resource === "killSession") { // logout from glpi
         $this->session_write = true;
         return $this->returnResponse($this->killSession());

      } else if ($resource === "changeActiveEntities") { // change active entities
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveEntities($this->parameters));

      } else if ($resource === "getMyEntities") { // get all entities of logged user
         return $this->returnResponse($this->getMyEntities($this->parameters));

      } else if ($resource === "getActiveEntities") { // get curent active entity
         return $this->returnResponse($this->getActiveEntities($this->parameters));

      } else if ($resource === "changeActiveProfile") { // change active profile
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveProfile($this->parameters));

      } else if ($resource === "getMyProfiles") { // get all profiles of current logged user
         return $this->returnResponse($this->getMyProfiles($this->parameters));

      } else if ($resource === "getActiveProfile") { // get current active profile
         return $this->returnResponse($this->getActiveProfile($this->parameters));

      } else if ($resource === "getFullSession") { // get complete php session
         return $this->returnResponse($this->getFullSession($this->parameters));

      } else if ($resource === "getGlpiConfig") { // get complete php var $CFG_GLPI
         return $this->returnResponse($this->getGlpiConfig($this->parameters));

      } else if ($resource === "getMultipleItems") { // get multiple items (with various itemtype)
         return $this->returnResponse($this->getMultipleItems($this->parameters));

      } else if ($resource === "listSearchOptions") { // list searchOptions of an itemtype
         return $this->returnResponse($this->listSearchOptions($this->parameters['itemtype'],
                                                               $this->parameters));

      } else if ($resource === "search") { // Search on itemtype
         self::checkSessionToken();

         //search
         $response =  $this->searchItems($this->parameters['itemtype'], $this->parameters);

         //add pagination headers
         $additionalheaders                  = [];
         $additionalheaders["Accept-Range"]  = $this->parameters['itemtype']." "
                                               .Toolbox::get_max_input_vars();
         if ($response['totalcount'] > 0) {
            $additionalheaders["Content-Range"] = $response['content-range'];
         }

         // diffent http return codes for complete or partial response
         if ($response['count'] < $response['totalcount']) {
            $code = 206; // partial content
         }

         return $this->returnResponse($response, $code, $additionalheaders);

      } else if ($resource === "lostPassword") {
         return $this->returnResponse($this->lostPassword($this->parameters));

      } else if (in_array($resource,
                          ["getItem", "getItems", "createItems", "updateItems", "deleteItems"])) {
         // commonDBTM manipulation

         // check itemtype parameter
         if (!isset($this->parameters['itemtype'])) {
            $this->returnError(__("missing itemtype"), 400, "ITEMTYPE_RESOURCE_MISSING");
         }
         if (!class_exists($this->parameters['itemtype'])
             || !is_subclass_of($this->parameters['itemtype'], 'CommonDBTM')
             && $this->parameters['itemtype'] != "AllAssets" ) {
            $this->returnError(__("itemtype not found or not an instance of CommonDBTM"),
                               400,
                               "ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM");
         } else if ($resource === "getItem") { // get an CommonDBTM item
            // check id parameter
            if (!isset($this->parameters['id'])) {
               $this->returnError(__("missing id"), 400, "ID_RESOURCE_MISSING");
            }

            $response = $this->getItem($this->parameters['itemtype'], $this->parameters['id'], $this->parameters);

            $additionalheaders = [];
            if (isset($response['date_mod'])) {
               $datemod = strtotime($response['date_mod']);
               $additionalheaders['Last-Modified'] = gmdate("D, d M Y H:i:s", $datemod)." GMT";
            }
            return $this->returnResponse($response, 200, $additionalheaders);

         } else if ($resource === "getItems") { // get a collection of a CommonDBTM item
            // return collection of items
            $totalcount = 0;
            $response = $this->getItems($this->parameters['itemtype'], $this->parameters, $totalcount);

            //add pagination headers
            $range = [0, $_SESSION['glpilist_limit']];
            if (isset($this->parameters['range'])) {
               $range = explode("-", $this->parameters['range']);
               // fix end range
               if ($range[1] > $totalcount - 1) {
                  $range[1] = $totalcount - 1;
               }
               if ($range[1] - $range[0] + 1 < $totalcount) {
                  $code = 206; // partial content
               }
            }
            $additionalheaders                  = [];
            $additionalheaders["Accept-Range"]  = $this->parameters['itemtype']." ".
                                                  Toolbox::get_max_input_vars();
            if ($totalcount > 0) {
               $additionalheaders["Content-Range"] = implode('-', $range)."/".$totalcount;
            }

            return $this->returnResponse($response, $code, $additionalheaders);

         } else if ($resource === "createItems") { // create one or many CommonDBTM items
            $response = $this->createItems($this->parameters['itemtype'], $this->parameters);

            $additionalheaders = [];
            if (isset($response['id'])) {
               // add a location targetting created element
               $additionalheaders['location'] = self::$api_url."/".$this->parameters['itemtype']."/".$response['id'];
            } else {
               // add a link header targetting created elements
               $additionalheaders['link'] = "";
               foreach ($response as $created_item) {
                  if ($created_item['id']) {
                     $additionalheaders['link'] .= self::$api_url."/".$this->parameters['itemtype'].
                                                  "/".$created_item['id'].",";
                  }
               }
               // remove last comma
               $additionalheaders['link'] = trim($additionalheaders['link'], ",");
            }
            return $this->returnResponse($response, 201);

         } else if ($resource === "updateItems") { // update one or many CommonDBTM items
            return $this->returnResponse($this->updateItems($this->parameters['itemtype'],
                                                            $this->parameters));

         } else if ($resource === "deleteItems") { // delete one or many CommonDBTM items
            if (isset($this->parameters['id'])) {
               //override input
               $this->parameters['input'] = new stdClass();;
               $this->parameters['input']->id = $this->parameters['id'];
            }
            return $this->returnResponse($this->deleteItems($this->parameters['itemtype'],
                                                            $this->parameters),
                                                            $code);

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
   public function parseIncomingParams() {
      $parameters = [];
      $resource = "";

      $parameters = xmlrpc_decode_request(trim($this->getHttpBody()),
                                          $resource,
                                          'UTF-8');

      $this->parameters = (isset($parameters[0]) && is_array($parameters[0])
                          ? $parameters[0]
                          : []);

      // transform input from array to object
      if (isset($this->parameters['input'])
          && is_array($this->parameters['input'])) {
         $first_field = array_values($this->parameters['input'])[0];
         if (is_array($first_field)) {
            foreach ($this->parameters['input'] as &$input) {
               $input = json_decode(json_encode($input), false);
            }
         } else {
            $this->parameters['input'] = json_decode(json_encode($this->parameters['input']),
                                                                 false);
         }
      }

      // check boolean parameters
      foreach ($this->parameters as $key => &$parameter) {
         if ($parameter === "true") {
            $parameter = true;
         }
         if ($parameter === "false") {
            $parameter = false;
         }
      }

      return $resource;
   }

   /**
    * Generic function to send a message and an http code to client
    *
    * @since 9.1
    *
    * @param mixed   $response          string message or array of data to send
    * @param integer $httpcode          http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    * @param array   $additionalheaders headers to send with http response (must be an array(key => value))
    *
    * @return void
    */
   protected function returnResponse($response, $httpcode = 200, $additionalheaders = []) {
      if (empty($httpcode)) {
         $httpcode = 200;
      }

      foreach ($additionalheaders as $key => $value) {
         header("$key: $value");
      }

      http_response_code($httpcode);
      self::header($this->debug);

      $response = $this->escapekeys($response);
      $out = xmlrpc_encode_request(null, $response, ['encoding' => 'UTF-8',
                                                          'escaping' => 'markup']);
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
   protected function escapekeys($response = []) {
      if (is_array($response)) {
         $escaped_response = [];
         foreach ($response as $key => $value) {
            if (is_integer($key)) {
               $key = " ".$key;
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
