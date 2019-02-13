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

use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends atoum {
   private $int;
   private $str;
   protected $apcu_cached_methods = [];
   protected $nscache;

   public function setUp() {
      // By default, no session, not connected
      $_SESSION = [
         'glpi_use_mode'         => Session::NORMAL_MODE,
         'glpi_currenttime'      => date("Y-m-d H:i:s"),
         'glpiis_ids_visible'    => 0
      ];
   }

   public function beforeTestMethod($method) {
      if (in_array($method, $this->apcu_cached_methods)) {
         $this->nscache = 'glpitestcache' . GLPI_VERSION;
         global $GLPI_CACHE;
         //run with cache
         $storage = \Zend\Cache\StorageFactory::factory([
            'adapter'   => 'apcu',
            'options'   => [
               'namespace' => $this->nscache
            ]
         ]);
         $GLPI_CACHE = new SimpleCacheDecorator($storage);
      }
   }

   public function afterTestMethod($method) {
      global $GLPI_CACHE;
      $GLPI_CACHE->clear();
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
