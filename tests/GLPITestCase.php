<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use atoum\atoum;

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends atoum {
   private $int;
   private $str;
   protected $cached_methods = [];
   protected $has_failed = false;

   public function beforeTestMethod($method) {
      // By default, no session, not connected
      $this->resetSession();

      if (in_array($method, $this->cached_methods)) {
         //run with cache
         define('CACHED_TESTS', true);
      }
   }

   public function afterTestMethod($method) {
      global $GLPI_CACHE;
      $GLPI_CACHE->clear();

      global $PHPLOGGER;
      $handlers = $PHPLOGGER->getHandlers();
      foreach ($handlers as $handler) {
         $records  = $handler->getRecords();
         $messages = array_column($records, 'message');
         $this->integer(count($records))
            ->isEqualTo(
               0,
               sprintf(
                  'Unexpected logs records found in %s::%s() test: %s',
                  static::class,
                  $method,
                  "\n" . implode("\n", $messages)
               )
            );
      }

      if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && !$this->has_failed) {
         unset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO]);
         $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo(
            [],
            sprintf(
               "Some messages has not been handled in %s::%s:\n%s",
               static::class,
               $method,
               print_r($_SESSION['MESSAGE_AFTER_REDIRECT'], true)
            )
         );
      }
   }

   protected function resetSession() {
      Session::destroy();
      Session::start();

      $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
      $_SESSION['glpiactive_entity'] = 0;

      global $CFG_GLPI;
      foreach ($CFG_GLPI['user_pref_field'] as $field) {
         if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
            $_SESSION["glpi$field"] = $CFG_GLPI[$field];
         }
      }
   }

   protected function hasSessionMessages(int $level, array $messages): void {
      $this->has_failed = true;
      $this->boolean(isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]))->isTrue('No messages for selected level!');
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'][$level])->isIdenticalTo(
         $messages,
         'Expecting ' . print_r($messages, true) . 'got: ' . print_r($_SESSION['MESSAGE_AFTER_REDIRECT'][$level], true)
      );
      unset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]); //reset
      $this->has_failed = false;
   }

   protected function hasNoSessionMessages(array $levels) {
      foreach ($levels as $level) {
         $this->hasNoSessionMessage($level);
      }
   }

   protected function hasNoSessionMessage(int $level) {
      $this->has_failed = true;
      $this->boolean(isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]))->isFalse(
         sprintf(
            'Messages for level %s are present in session: %s',
            $level,
            print_r($_SESSION['MESSAGE_AFTER_REDIRECT'][$level] ?? [], true)
         )
      );
      $this->has_failed = false;
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
