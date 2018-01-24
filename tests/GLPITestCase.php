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

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends atoum {
   private $int;
   private $str;
   protected $cached_methods = [];
   protected $nscache;
   protected $container;

   public function setUp() {
      global $container; //FIXME: how to avoid this? //Also find how to make that work...
      $this->container = $container;

      // By default, no session, not connected
      $_SESSION = [
         'glpi_use_mode'         => Session::NORMAL_MODE,
         'glpi_currenttime'      => date("Y-m-d H:i:s"),
         'glpiis_ids_visible'    => 0,
         'glpiticket_timeline'   => 1
      ];
   }

   public function beforeTestMethod($method) {
      if (in_array($method, $this->cached_methods)) {
         $this->nscache = 'glpitestcache' . GLPI_VERSION;
         global $GLPI_CACHE;
         //run with cache
         define('CACHED_TESTS', true);
         //ZendCache does not works with PHP5 acpu...
         $adapter = (version_compare(PHP_VERSION, '7.0.0') >= 0) ? 'apcu' : 'apc';
         $GLPI_CACHE = \Zend\Cache\StorageFactory::factory([
            'adapter'   => $adapter,
            'options'   => [
               'namespace' => $this->nscache
            ]
         ]);
      }
   }

   public function afterTestMethod($method) {
      if (in_array($method, $this->cached_methods)) {
         global $GLPI_CACHE;
         if ($GLPI_CACHE instanceof \Zend\Cache\Storage\Adapter\AbstractAdapter) {
            $GLPI_CACHE->flush();
         }
         $GLPI_CACHE = false;
      }

      // Cleanup log directory
      foreach (glob(GLPI_LOG_DIR . '/*.log') as $file) {
         if (file_exists($file)) {
            unlink($file);
         }
      }
   }

   /**
    * Get a unique random string
    */
   protected function getUniqueString() {
      if (is_null($this->str)) {
         return $this->str = uniqid('str');
      }
      return $this->str .= 'x';
   }

   /**
    * Get a unique random integer
    */
   protected function getUniqueInteger() {
      if (is_null($this->int)) {
         return $this->int = mt_rand(1000, 10000);
      }
      return $this->int++;
   }
}
