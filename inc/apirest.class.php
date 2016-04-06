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

class APIRest extends API {
   protected $request_uri;
   protected $url_elements;
   protected $verb;
   protected $parameters;
   protected $debug = 0;
   protected $format = "json";


   public static function getTitle() {
      return __('Rest API');
   }


   /**
    * parse url and http body to retrieve :
    *  - HTTP VERB (GET/POST/DELETE/PUT)
    *  - Ressource : Rest endpoint
    *  - Identifier
    *  - and parameters
    *
    *  And send to method corresponding identified ressource
    *
    * @return     json with response or error
    */
   public function call() {
      //parse http request and find parts
      $this->request_uri  = $_SERVER['REQUEST_URI'];
      $this->verb         = $_SERVER['REQUEST_METHOD'];
      $path_info          = trim($_SERVER['PATH_INFO'], '/');
      $path_info          = str_replace("api/", "", $path_info);
      $this->url_elements = explode('/', $path_info);
      $this->parseIncomingParams();

      // show debug if required
      if (isset($this->parameters['debug'])) {
         $this->debug = $this->parameters['debug'];
         if (empty($this->debug)) {
            $this->debug = 1;
         }

         if ($this->debug >= 2) {
            $this->showDebug();
         }
      }

      if ($this->verb == "OPTIONS") {
         header("OK", 200);
         die;
      }

      // retrieve requested ressource
      $ressource = trim(strval($this->url_elements[0]));

      // retrieve session (if exist)
      $this->retrieveSession();

      // retrieve param who permit session writing
      if (isset($this->parameters['session_write'])) {
         $this->session_write = boolval($this->parameters['session_write']);
      }

      // inline documentation (api/)
      if (strlen($ressource) == 0 || $ressource == "api") {
         return $this->inlineDocumentation("apirest.md");

      ## DECLARE ALL ENDPOINTS ##
      // login into glpi
      } else if ($ressource === "initSession") {
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
         if (!isset($this->url_elements[1]))  {
            $this->returnError(__("parameter itemtype missing"), 400, "ERROR_ITEMTYPE_MISSING");
         }
         $itemtype = $this->url_elements[1];
         return $this->returnResponse($this->listSearchOptions($itemtype));

      // Search on itemtype
      } else if ($ressource === "search") {
         self::checkSessionToken();
         if (!isset($this->url_elements[1])) {
            $this->returnError();
         }
         $itemtype = $this->url_elements[1];
         if (class_exists($itemtype)
               && is_subclass_of($itemtype, 'CommonDBTM')
             || $itemtype == "AllAssets" ) {
                  $itemtype = $this->url_elements[1];
                  //clean stdObjects in parameter
                  $params = json_decode(json_encode($this->parameters), true);
                  //search
                  return $this->searchItems($itemtype, $params);
          } else {
             $this->returnError();
          }

      // commonDBTM manipulation
      } else if (class_exists($ressource)
                 && is_subclass_of($ressource, 'CommonDBTM')) {
         $additionalheaders = array();
         $code = 200;
         switch ($this->verb) {
            default:
            case "GET": // retrieve item(s)
               if (isset($this->url_elements[1])) {
                  //get single item
                  $id = intval($this->url_elements[1]);
                  $response = $this->getItem($ressource, $id, $this->parameters);
                  if (isset($response['date_mod'])) {
                     $datemod = strtotime($response['date_mod']);
                     $additionalheaders['Last-Modified'] = date('r', $datemod);
                  }
               } else {
                  // return collection of items
                  $response = $this->getItems($ressource, $this->parameters);
               }
               break;

            case "POST": // create item(s)
               $response = $this->createItems($ressource, $this->parameters);
               $code = 201;
               if (count($response) == 1) {
                  // add a location targetting created element
                  $additionalheaders['location'] = self::$api_url.$itemtype."/".$response['id'];
               } else {
                  // add a link header targetting created elements
                  $additionalheaders['link'] = "";
                  foreach($response as $created_item) {
                     if ($created_item['id']) {
                        $additionalheaders['link'].= self::$api_url.$itemtype.
                                                     "/".$created_item['id'].",";
                     }
                  }
                  // remove last comma
                  $additionalheaders['link'] = trim($additionalheaders['link'], ",");
               }
               break;

            case "PUT": // update item(s)
               $response = $this->updateItems($ressource, $this->parameters);
               break;

            case "DELETE": //delete item(s)
               if (isset($this->url_elements[1])) {
                  $id = intval($this->url_elements[1]);
                  $response = $this->deleteItem($ressource, $id, $this->parameters);
               }
               break;
         }
         return $this->returnResponse($response, $code, $additionalheaders);
      }

      $this->messageLostError();
   }

   /**
    * Construct this->parameters from query string and http body
    */
   public function parseIncomingParams() {
      $parameters = array();

      // first of all, pull the GET vars
      if (isset($_SERVER['QUERY_STRING'])) {
         parse_str($_SERVER['QUERY_STRING'], $parameters);
      }

      // now how about PUT/POST bodies? These override what we got from GET
      $body = trim(file_get_contents("php://input"));
      if (strlen($body) > 0 && $this->verb == "GET") {
         // GET method requires an empty body
         $this->returnError("GET Request should not have json payload (http body)", 400, "ERROR_JSON_PAYLOAD_FORBIDDEN");
      }

      $content_type = "";
      if(isset($_SERVER['CONTENT_TYPE'])) {
         $content_type = $_SERVER['CONTENT_TYPE'];
      }

      if (strpos($content_type, "application/json") !== false) {
         if($body_params = json_decode($body)) {
            foreach($body_params as $param_name => $param_value) {
               $parameters[$param_name] = $param_value;
            }
         } else if (strlen($body) > 0) {
            $this->returnError("JSON payload seems not valid", 400, "ERROR_JSON_PAYLOAD_INVALID", false);
         }
         $this->format = "json";

      } elseif (strpos($content_type, "multipart/form-data") !== false) {
         if (count($_FILES) <= 0) {
            // likely uploaded files is too big so $_REQUEST will be empty also.
            // see http://us.php.net/manual/en/ini.core.php#ini.post-max-size
            $this->returnError("The file seems too big", 400, "ERROR_UPLOAD_FILE_TOO_BIG_POST_MAX_SIZE", false);
         }

         // with this content_type, php://input is empty... (see http://php.net/manual/en/wrappers.php.php)
         if(!$uploadManifest = json_decode(stripcslashes($_REQUEST['uploadManifest']))) {
            $this->returnError("JSON payload seems not valid", 400, "ERROR_JSON_PAYLOAD_INVALID", false);
         }
         foreach($uploadManifest as $field => $value) {
            $parameters[$field] = $value;
         }
         $this->format = "json";

      } elseif (strpos($content_type, "application/x-www-form-urlencoded") !== false) {
         parse_str($body, $postvars);
         foreach($postvars as $field => $value) {
            $parameters[$field] = $value;
         }
         $this->format = "html";

      } else {
         $this->format = "html";
      }

      $this->parameters = $parameters;
   }


   /**
    * Send 404 error to client
    */
   public function messageNotfoundError() {
      $this->returnError(__("Not found"),
                         404,
                         "ERROR_NOT_FOUND",
                         false);
   }


   /**
    * Send 401 error to client
    */
   public function messageBadArrayError() {
      $this->returnError(__("input parameter must be an an array of objects"),
                         400,
                         "ERROR_BAD_ARRAY");
   }


   /**
    * Send 405 error to client
    */
   public function messageLostError() {
      $this->returnError(__("Method Not Allowed"),
                         405,
                         "ERROR_METHOD_NOT_ALLOWED");
   }


   /**
    * Send 401 error to client
    */
   public function messageRightError() {
      $this->returnError(__("You don't have permission to perform this action"),
                         401,
                         "ERROR_RIGHT_MISSING",
                         false);
   }


   /**
    * Session Token KO
    */
   public function messageSessionError() {
      $this->returnError(__("session_token seems invalid"),
                         401,
                         "ERROR_SESSION_TOKEN_INVALID",
                         false);
   }


   /**
    * Session Token missing
    */
   public function messageSessionTokenMissing() {
      $this->returnError(__("parameter session_token missing or empty"),
                         400,
                         "ERROR_SESSION_TOKEN_MISSING");
   }


   /**
    * Send 401 error to client
    */
   public function messageNotDeletedError() {
      $this->returnError(__("You must mark the item for deletion before actualy deleting it"),
                         401,
                         "ERROR_NOT_DELETED",
                         false);
   }


   /**
    * Generic function to send a error message and an error code to client
    *
    * @param      string   $message     message to send (human readable)
    * @param      integer  $httpcode    http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    * @param      string   $statuscode  API status (to represend more precisely the current error)
    * @param      boolean  $docmessage  if true, add a link to inline document in message
    */
   public function returnError($message = "Bad Request", $httpcode = 400, $statuscode = "ERROR", $docmessage = true) {
      global $CFG_GLPI;

      if (empty($httpcode)) {
         $httpcode = 400;
      }
      if (empty($statuscode)) {
         $statuscode = "ERROR";
      }

      if ($docmessage) {
         $message .= "; ".sprintf(__("see documentation with your browser on %s"), self::$api_url);
      }
      $this->returnResponse(array($statuscode, $message), $httpcode);
      die;
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

      $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                     | ($this->debug?JSON_PRETTY_PRINT:0));
      if ($this->debug) {
         echo "<pre>";
         var_dump($response);
         echo "</pre>";
      } else {
         echo $json;
      }
   }

   /**
    * Display the APIRest Documentation in Html (parsed from markdown)
    *
    * @param      string  $file   relative path of documentation file
    */
   public function inlineDocumentation($file = "apirest.md") {
      global $CFG_GLPI;

      if ($this->format == "html") {
         parent::inlineDocumentation($file);
      } else if ($this->format == "json") {
         echo file_get_contents(GLPI_ROOT.'/'.$file);
      }
   }
}
