<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Agent\Communication;

use DOMDocument;
use DOMElement;
use Glpi\Agent\Communication\Headers\Common;
use Toolbox;

/**
 * Handle agent requests
 * Both XML (legacy) and JSON inventory formats are supported.
 *
 * @see https://github.com/glpi-project/inventory_format/blob/master/inventory.schema.json
 */
abstract class AbstractRequest
{
   const DEFAULT_FREQUENCY = 24;

   const XML_MODE    = 0;
   const JSON_MODE   = 1;

   //FusionInventory agent
   const PROLOG_QUERY = 'PROLOG';
   const INVENT_QUERY = 'INVENTORY';
   const SNMP_QUERY   = 'SNMP';
   const OLD_SNMP_QUERY   = 'SNMPQUERY';

   //GLPI AGENT
   const CONTACT_ACTION = 'contact';
   const REGISTER_ACTION = 'register';
   const CONFI_ACTION = 'configuration';
   const INVENT_ACTION = 'inventory';
   const NETDISCOVERY_ACTION = 'network-discovery';
   const NETINV_ACTION = 'network-inventory';
   const ESX_ACTION = 'esx';
   const COLLECT_ACTION = 'collect';
   const DEPLOY_ACTION = 'deploy';
   const WOL_ACTION = 'wakeonlan';

   const COMPRESS_NONE = 0;
   const COMPRESS_ZLIB = 1;
   const COMPRESS_GZIP = 2;
   const COMPRESS_BR   = 3;

   /** @var integer */
   protected $mode;
   /** @var string */
   private $deviceid;
   /** @var DOMDocument */
   private $response;
    /** @var integer */
   private $compression;
   /** @var boolean */
   private $error = false;
   /** @var boolean */
   private $test_rules = false;
   /** @var Inventory */
   //private $inventory;
   /** @var Glpi\Agent\Communication\Headers\Common */
   protected $headers;
   /** @var int */
   private $http_response_code = 200;

   public function __construct() {
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
   private function setMode($mode) {
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
              throw new \RuntimeException("Unknown mode $mode");
      }
      $this->prepareHeaders();
   }

   /**
    * Guess import mode
    *
    * @return boolean
    */
   private function guessMode($contents) {
      json_decode($contents);
      if (json_last_error() === JSON_ERROR_NONE) {
         $this->setMode(self::JSON_MODE);
      } else {
         //defaults to XML; whose validity is checked later.
         $this->setMode(self::XML_MODE);
      }
   }

    /**
     * Handle request headers
     *
     * @param $data
     */
   public function handleHeaders() {
       $req_headers = [];
      if (!function_exists('getallheaders')) {
         foreach ($_SERVER as $name => $value) {
             /* RFC2616 (HTTP/1.1) defines header fields as case-insensitive entities. */
            if (strtolower(substr($name, 0, 5)) == 'http_') {
               $req_headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
         }
      } else {
          $req_headers = getallheaders();
      }

       $this->headers->setHeaders($req_headers);
   }

   /**
    * Handle agent request
    *
    * @param mixed $data Sent data
    *
    * @return boolean
    */
   public function handleRequest($data) :bool {
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
                  throw new \UnexpectedValueException("You must install Brotli PHP extension.");
               }
               $data = brotli_uncompress($data);
               break;
            default:
               throw new \UnexpectedValueException("Unknown compression mode" . $this->compression);
         }
      }

      if ($this->mode === null) {
         $this->guessMode($data);
      }

      //load and check data
      switch ($this->mode) {
         case self::XML_MODE:
            return $this->handleXMLRequest($data);
         case self::JSON_MODE:
            return $this->handleJSONRequest($data);
      }
   }

    /**
     * Handle Query
     *
     * @param string $query   Query mode (one of self::*_QUERY)
     * @param mixed  $content Contents, optionnal
     *
     * @return boolean
     */
   abstract protected function handleQuery($query, $content = null) :bool;

   /**
    * Handle XML request
    *
    * @param string $data Sent XML
    *
    * @return boolean
    */
   public function handleXMLRequest($data) :bool {
      libxml_use_internal_errors(true);

      if (mb_detect_encoding($data, 'UTF-8', true) === false) {
         $data = utf8_encode($data);
      }
      $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
      if (!$xml) {
         $xml_errors = libxml_get_errors();
         Toolbox::logWarning('Invalid XML: ' . print_r($xml_errors, true));
         $this->addError('XML not well formed!', 400);
         return false;
      }
      $this->deviceid = (string)$xml->DEVICEID;
      //query is not mandatory. Defaults to inventory
      $query = self::INVENT_QUERY;
      if (property_exists($xml, 'QUERY')) {
         $query = (string)$xml->QUERY;
      }

      return $this->handleQuery($query, $xml);
   }

   /**
    * Handle JSON request
    *
    * @param string $data Sent JSON
    *
    * @return boolean
    */
   public function handleJSONRequest($data) :bool {
      $jdata = json_decode($data);
      $this->deviceid = $jdata->deviceid;
      $query = self::INVENT_QUERY;
      if (property_exists($jdata, 'query')) {
         $query = $jdata->query;
      } else if (property_exists($jdata, 'action')) {
         $query = $jdata->action;
      }
      return $this->handleQuery($query, $data);
   }

    /**
     * Get request mode
     *
     * @return null|integer One of self::*_MODE
     */
   public function getMode() {
       return $this->mode;
   }

    /**
     * Adds an error
     *
     * @param string $message Error message
     *
     * @return void
     */
   public function addError($message, $code = 500) {
      $this->error = true;
      $this->http_repsonse_code = $code;
      if ($this->headers->hasHeader('GLPI-Agent-ID')) {
         $this->addToResponse([
            'status' => 'error',
            'message' => $message,
            'expiration' => self::DEFAULT_FREQUENCY
         ]);
      } else {
          $this->addToResponse(['ERROR' => $message]);
      }
   }

   /**
    * Add elements to response
    *
    * @param array $entries Array of key => values entries
    *
    * @return void
    */
   protected function addToResponse(array $entries) {
      if ($this->mode === self::XML_MODE) {
         $root = $this->response->documentElement;
         foreach ($entries as $name => $content) {
            $this->addNode($root, $name, $content);
         }
      } else {
         foreach ($entries as $name => $content) {
             $this->addEntry($this->response, $name, $content);
         }
      }
   }

   /**
    * Add node to response for XML_MODE
    *
    * @param DOMElement        $parent  Parent element
    * @param string            $name    Element name to create
    * @param string|array|null $content Element contents, if any
    *
    * @return void
    */
   private function addNode(DOMElement $parent, $name, $content) {
      if (is_array($content)) {
          $node = $parent->appendChild(
              $this->response->createElement(
                  $name
              )
          );
         foreach ($content as $sname => $scontent) {
            $this->addNode($node, $sname, $scontent);
         }
      } else {
          $parent->appendChild(
              $this->response->createElement(
                  $name,
                  $content
              )
          );
      }
   }

   /**
    * Add node to response for JSON_MODE
    *
    * @param array             $parent  Parent element
    * @param string            $name    Element name to create
    * @param string|array|null $content Element contents, if any
    *
    * @return void
    */
   private function addEntry(array &$parent, $name, $content) {
      if (is_array($content)) {
          $node = $parent[$name];
         foreach ($content as $sname => $scontent) {
            $this->addNode($node, $sname, $scontent);
         }
      } else {
          $parent[$name] = $content;
      }
   }


    /**
     * Get content-type
     *
     * @return string
     */
   public function getContentType() :string {
      if ($this->mode === null) {
         throw new \RuntimeException("Mode has not been set");
      }

      switch (strtolower($this->compression)) {
         case self::COMPRESS_ZLIB:
            return 'application/x-compress-zlib';
         case self::COMPRESS_GZIP:
            return 'application/x-compress-gzip';
         case self::COMPRESS_BR:
            return 'application/x-br';
      }

      switch ($this->mode) {
         case self::XML_MODE:
            return 'application/xml';
         case self::JSON_MODE:
            return 'application/json';
         default:
            throw new \RuntimeException("Unknown mode " . $this->mode);
      }
   }

   /**
    * Get response
    *
    * @return string
    */
   public function getResponse() :string {
      if ($this->mode === null) {
         throw new \RuntimeException("Mode has not been set");
      }

      $data = null;
      switch ($this->mode) {
         case self::XML_MODE:
            $data = $this->response->saveXML();
            break;
         case self::JSON_MODE:
            $data = json_encode($this->response);
            break;
         default:
            throw new \RuntimeException("Unknown mode " . $this->mode);
            break;
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
                   throw new \UnexpectedValueException("You must install Brotli PHP extension.");
               }
               $data = brotli_compress($data);
               break;
            default:
               throw new \UnexpectedValueException("Unknown compression mode" . $this->compression);
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
   public function handleContentType($type) {
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
            $this->compression = self::COMPRESS_BR;
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
   public function inError() {
      return $this->error;
   }

   public function testRules(): self {
      $this->test_rules = true;
      return $this;
   }

    /**
     * Accepted encodings
     *
     * @return string[]
     */
   public function acceptedEncodings(): array {
      $encodings = [
         'gzip',
         'deflate'
      ];

      if (!function_exists('brotli_uncompress')) {
         $encodings[] = 'br';
      }

      return $encodings;
   }

   /**
    * Prepare HTTP headers
    *
    * @return void
    */
   private function prepareHeaders() {
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
   public function getHeaders($legacy = false): array {
       return $this->headers->getHeaders($legacy);
   }

   public function getHttpResponseCode(): int {
       return $this->http_response_code;
   }
}
