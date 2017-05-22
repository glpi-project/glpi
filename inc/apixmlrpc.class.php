<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

class APIXmlrpc extends API {
   protected $request_uri;
   protected $url_elements;
   protected $verb;
   protected $parameters;
   protected $debug = 0;
   protected $format = "json";


   /**
    * @see CommonGLPI::GetTypeName()
    */
   public static function getTypeName($nb = 0) {
      return __('XMLRPC API');
   }


   /**
    * parse POST var to retrieve
    *  - Resource
    *  - Identifier
    *  - and parameters
    *
    *  And send to method corresponding identified resource
    *
    * @since version 9.1
    *
    * @return     xmlrpc response
    */
   public function call() {
      $resource = $this->parseIncomingParams();

      // retrieve session (if exist)
      $this->retrieveSession();

      $code = 200;

      if ($resource === "initSession") {
         $this->session_write = true;
         return $this->returnResponse($this->initSession($this->parameters));

      // logout from glpi
      } else if ($resource === "killSession") {
         $this->session_write = true;
         return $this->returnResponse($this->killSession());

      // change active entities
      } else if ($resource === "changeActiveEntities") {
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveEntities($this->parameters));

      // get all entities of logged user
      } else if ($resource === "getMyEntities") {
         return $this->returnResponse($this->getMyEntities($this->parameters));

      // get curent active entity
      } else if ($resource === "getActiveEntities") {
         return $this->returnResponse($this->getActiveEntities($this->parameters));

      // change active profile
      } else if ($resource === "changeActiveProfile") {
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveProfile($this->parameters));

      // get all profiles of current logged user
      } else if ($resource === "getMyProfiles") {
         return $this->returnResponse($this->getMyProfiles($this->parameters));

      // get current active profile
      } else if ($resource === "getActiveProfile") {
         return $this->returnResponse($this->getActiveProfile($this->parameters));

      // get complete php session
      } else if ($resource === "getFullSession") {
         return $this->returnResponse($this->getFullSession($this->parameters));

      // get multiple items (with various itemtype)
      } else if ($resource === "getMultipleItems") {
         return $this->returnResponse($this->getMultipleItems($this->parameters));

      // list searchOptions of an itemtype
      } else if ($resource === "listSearchOptions") {
         return $this->returnResponse($this->listSearchOptions($this->parameters['itemtype'],
                                                               $this->parameters));

      // Search on itemtype
      } else if ($resource === "search") {
         self::checkSessionToken();

         //search
         $response =  $this->searchItems($this->parameters['itemtype'], $this->parameters);

         //add pagination headers
         $additionalheaders                  = array();
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

      // commonDBTM manipulation
      } else if (in_array($resource,
                          array("getItem", "getItems", "createItems", "updateItems", "deleteItems"))) {
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
         } else

         // get an CommonDBTM item
         if ($resource === "getItem") {
            // check id parameter
            if (!isset($this->parameters['id'])) {
               $this->returnError(__("missing id"), 400, "ID_RESOURCE_MISSING");
            }

            $response = $this->getItem($this->parameters['itemtype'], $this->parameters['id'], $this->parameters);

            $additionalheaders = array();
            if (isset($response['date_mod'])) {
               $datemod = strtotime($response['date_mod']);
               $additionalheaders['Last-Modified'] = gmdate("D, d M Y H:i:s", $datemod)." GMT";
            }
            return $this->returnResponse($response, 200, $additionalheaders);

         // get a collection of a CommonDBTM item
         } else if ($resource === "getItems") {
            // return collection of items
            $totalcount = 0;
            $response = $this->getItems($this->parameters['itemtype'], $this->parameters, $totalcount);

            //add pagination headers
            $range = [0, $_SESSION['glpilist_limit']];
            if (isset($this->parameters['range'])) {
               $range = explode("-", $this->parameters['range']);
               // fix end range
               if($range[1] > $totalcount - 1){
                  $range[1] = $totalcount - 1;
               }
               if($range[1] - $range[0] + 1 < $totalcount){
                  $code = 206; // partial content
               }
            }
            $additionalheaders                  = array();
            $additionalheaders["Accept-Range"]  = $this->parameters['itemtype']." ".
                                                  Toolbox::get_max_input_vars();
            if ($totalcount > 0) {
               $additionalheaders["Content-Range"] = implode('-', $range)."/".$totalcount;
            }

            return $this->returnResponse($response, $code, $additionalheaders);

         // create one or many CommonDBTM items
         } else if ($resource === "createItems") {
            $response = $this->createItems($this->parameters['itemtype'], $this->parameters);

            $additionalheaders = array();
            if (count($response) == 1) {
               // add a location targetting created element
               $additionalheaders['location'] = self::$api_url."/".$this->parameters['itemtype']."/".$response['id'];
            } else {
               // add a link header targetting created elements
               $additionalheaders['link'] = "";
               foreach($response as $created_item) {
                  if ($created_item['id']) {
                     $additionalheaders['link'] .= self::$api_url."/".$this->parameters['itemtype'].
                                                  "/".$created_item['id'].",";
                  }
               }
               // remove last comma
               $additionalheaders['link'] = trim($additionalheaders['link'], ",");
            }
            return $this->returnResponse($response, 201);

         // update one or many CommonDBTM items
         } else if ($resource === "updateItems") {
            return $this->returnResponse($this->updateItems($this->parameters['itemtype'],
                                                            $this->parameters));

         // delete one or many CommonDBTM items
         } else if ($resource === "deleteItems") {
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
    * @since version 9.1
    */
   public function parseIncomingParams() {
      $parameters = array();
      $resource = "";

      $parameters = xmlrpc_decode_request(trim($this->getHttpBodyStream()),
                                          $resource,
                                          'UTF-8');

      $this->parameters = (isset($parameters[0]) && is_array($parameters[0])
                          ? $parameters[0]
                          : array());

      // transform input from array to object
      if (isset($this->parameters['input'])
          && is_array($this->parameters['input'])) {
         $first_field = array_values($this->parameters['input'])[0];
         if (is_array($first_field)) {
            foreach($this->parameters['input'] as &$input) {
               $input = json_decode(json_encode($input), false);
            }
         } else {
            $this->parameters['input'] = json_decode(json_encode($this->parameters['input']),
                                                                 false);
         }
      }

      return $resource;
   }

   /**
    * Generic function to send a message and an http code to client
    *
    * @since version 9.1
    *
    * @param mixed    $response          string message or array of data to send
    * @param integer  $httpcode          http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    * @param array    $aditionnalheaders headers to send with http response (must be an array(key => value))
    */
   protected function returnResponse($response, $httpcode = 200, $aditionnalheaders = array()) {
      if (empty($httpcode)) {
         $httpcode = 200;
      }

      foreach($aditionnalheaders as $key => $value) {
         header("$key: $value");
      }

      http_response_code($httpcode);
      self::header($this->debug);

      $response = $this->escapekeys($response);
      $out = xmlrpc_encode_request(NULL, $response, array('encoding' => 'UTF-8',
                                                          'escaping' => 'markup'));
      echo $out;
      exit;
   }

   /**
    * Add a space before all numeric keys to prevent their deletion by xmlrpc_encode_request function
    * see https://bugs.php.net/bug.php?id=21949
    *
    * @since version 9.1
    *
    * @param  array  $response the response array to escape
    *
    * @return array  the escaped response.
    */
   protected function escapekeys($response = array()) {
      if (is_array($response)) {
         $escaped_response = array();
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
