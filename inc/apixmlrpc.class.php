<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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


   public static function getTypeName($nb=0) {
      return __('XMLRPC API');
   }


   /**
    * parse url and http body to retrieve :
    *  TODO
    *
    *  And send to method corresponding identified ressource
    *
    * @return     TODO
    */
   public function call() {
      $ressource = $this->parseIncomingParams();

      // retrieve session (if exist)
      $this->retrieveSession();

      if ($ressource === "initSession") {
         $this->session_write = true;
         return $this->returnResponse($this->initSession($this->parameters));

      // logout from glpi
      } else if ($ressource === "killSession") {
         $this->session_write = true;
         return $this->returnResponse($this->killSession());

      // change active entities
      } else if ($ressource === "changeActiveEntities") {
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveEntities($this->parameters));

      // get all entities of logged user
      } else if ($ressource === "getMyEntities") {
         return $this->returnResponse($this->getMyEntities($this->parameters));

      // get curent active entity
      } else if ($ressource === "getActiveEntities") {
         return $this->returnResponse($this->getActiveEntities($this->parameters));

      // change active profile
      } else if ($ressource === "changeActiveProfile") {
         $this->session_write = true;
         return $this->returnResponse($this->changeActiveProfile($this->parameters));

      // get all profiles of current logged user
      } else if ($ressource === "getMyProfiles") {
         return $this->returnResponse($this->getMyProfiles($this->parameters));

      // get current active profile
      } else if ($ressource === "getActiveProfile") {
         return $this->returnResponse($this->getActiveProfile($this->parameters));

      // get complete php session
      } else if ($ressource === "getFullSession") {
         return $this->returnResponse($this->getFullSession($this->parameters));

      // list searchOptions of an itemtype
      } else if ($ressource === "listSearchOptions") {
         return $this->returnResponse($this->listSearchOptions($this->parameters['itemtype']));

      // Search on itemtype
      } else if ($ressource === "search") {
         self::checkSessionToken();

         //search
         $response =  $this->searchItems($this->parameters['itemtype'], $this->parameters);

         // diffent http return codes for complete or partial response
         if ($response['count'] >= $response['count']) {
            $code = 200; // full content
         } else {
            $code = 206; // partial content
         }

         return $this->returnResponse($response, $code);

      // commonDBTM manipulation
      } else {
         // check itemtype parameter
         if (!isset($this->parameters['itemtype'])) {
            $this->returnError(__("itemtype missing"), 400, "ITEMTYPE_RESSOURCE_MISSING");
         }
         if (!class_exists($this->parameters['itemtype'])
             || !is_subclass_of($this->parameters['itemtype'], 'CommonDBTM')
             && $this->parameters['itemtype'] != "AllAssets" ) {
            $this->returnError(__("itemtype is not found or not an instance of CommonDBTM"),
                               400,
                               "ERROR_ITEMTYPE_NOT_FOUND_NOR_COMMONDBTM");
         } else

         // get an CommonDBTM item
         if ($ressource === "getItem") {
            // check id parameter
            if (!isset($this->parameters['id'])) {
               $this->returnError(__("id missing"), 400, "ID_RESSOURCE_MISSING");
            }

            return $this->returnResponse($this->getItem($this->parameters['itemtype'],
                                                        $this->parameters['id'],
                                                        $this->parameters));

         // get a collection of a CommonDBTM item
         } else if ($ressource === "getItems") {
            return $this->returnResponse($this->getItems($this->parameters['itemtype'],
                                                         $this->parameters));

         // create one or many CommonDBTM items
         } else if ($ressource === "createItems") {
            return $this->returnResponse($this->createItems($this->parameters['itemtype'],
                                                            $this->parameters),
                                                            201);

         // update one or many CommonDBTM items
         } else if ($ressource === "updateItems") {
            return $this->returnResponse($this->updateItems($this->parameters['itemtype'],
                                                            $this->parameters));

         // delete one or many CommonDBTM items
         } else if ($ressource === "deleteItems") {
            return $this->returnResponse($this->deleteItems($this->parameters['itemtype'],
                                                            $this->parameters),
                                                            204);

         }
      }

      $this->messageLostError();
   }


   /**
    * Construct this->parameters from query string and http body
    */
   public function parseIncomingParams() {
      $parameters = array();
      $ressource = "";

      if (isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
         $parameters = xmlrpc_decode_request($GLOBALS["HTTP_RAW_POST_DATA"],
                                             $ressource,
                                             'UTF-8');
      }

      $this->parameters = (isset($parameters[0]) && is_array($parameters[0])
                          ? $parameters[0]
                          : array());

      return $ressource;
   }

   /**
    * Generic function to send a message and an http code to client
    *
    * @param mixed    $response    string message or array of data to send
    * @param integer  $httpcode        http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    * @param array    $aditionnalheaders header to send with http response (must be an array(key => value))
    */
   public function returnResponse($response, $httpcode = 200, $aditionnalheaders = array()) {
      if (empty($httpcode)) {
         $httpcode = 200;
      }

      foreach($aditionnalheaders as $key => $value) {
         header("$key: $value");
      }

      http_response_code($httpcode);
      self::header($this->debug);

      echo xmlrpc_encode_request(NULL, $response,array('encoding'=>'UTF-8', 'escaping'=>'markup'));
   }

}
