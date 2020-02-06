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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  GLPI security key
**/
class GLPIKey {
   /**
    * Key file path.
    *
    * @var string
    */
   private $keyfile;

   /**
    * List of crypted DB fields.
    *
    * @var array
    */
   protected $fields = [
      'glpi_mailcollectors.passwd',
      'glpi_authldaps.rootdn_passwd'
   ];

   /**
    * List of crypted configuration values.
    * Each key corresponds to a configuration context, and contains list of configs names.
    *
    * @var array
    */
   protected $configs = [
      'core'   => [
         'smtp_passwd',
         'proxy_passwd'
      ]
   ];

   public function __construct() {
      $this->keyfile = GLPI_CONFIG_DIR . '/glpi.key';
   }

   /**
    * Check if GLPI security key used for decryptable passwords exists
    *
    * @return string
    */
   public function keyExists() {
      return file_exists($this->keyfile) && !empty($this->get());
   }

   /**
    * Get GLPI security key used for decryptable passwords
    *
    * @return string
    */
   public function get() {
      $key = GLPIKEY;
      if (file_exists($this->keyfile)) {
         //load key from existing config file
         $key = file_get_contents($this->keyfile);
      }
      return $key;
   }

   /**
    * Generate GLPI security key used for decryptable passwords
    * and update values in DB if necessary.
    *
    * @return boolean
    */
   public function generate() {
      global $DB;

      $current_key = file_exists($this->keyfile) ? $this->get() : GLPIKEY;

      $success = (bool)file_put_contents($this->keyfile, Toolbox::getRandomString(50));
      if (!$success) {
         return false;
      }

      if ($DB instanceof DBmysql) {
         return $this->migrateFieldsInDb($current_key)
            && $this->migrateConfigsInDb($current_key);
      }

      return true;
   }

   /**
    * Get fields
    *
    * @return array
    */
   public function getFields() {
      global $PLUGIN_HOOKS;

      $fields = $this->fields;
      if (isset($PLUGIN_HOOKS['secured_fields'])) {
         foreach ($PLUGIN_HOOKS['secured_fields'] as $plugfields) {
            $fields = array_merge($fields, $plugfields);
         }
      }

      return $fields;
   }

   /**
    * Get configs
    *
    * @return array
    */
   public function getConfigs() {
      global $PLUGIN_HOOKS;

      $configs = $this->configs;

      if (isset($PLUGIN_HOOKS['secured_configs'])) {
         foreach ($PLUGIN_HOOKS['secured_configs'] as $plugin => $plugconfigs) {
            $configs['plugin:' . $plugin] = $plugconfigs;
         }
      }

      return $configs;
   }

   /**
    * Migrate fields in database
    *
    * @param string $current_key Current key
    *
    * @return void
    */
   protected function migrateFieldsInDb($current_key) {
      global $DB;

      $success = true;

      foreach ($this->getFields() as $field) {
         list($table, $column) = explode('.', $field);

         $iterator = $DB->request([
            'SELECT' => ['id', $column],
            'FROM'   => $table
         ]);

         while ($success && $row = $iterator->next()) {
            $pass = Toolbox::encrypt(Toolbox::decrypt($row[$column], $current_key));
            $success = $DB->update(
               $table,
               [$field  => $pass],
               ['id'    => $row['id']]
            );
         }
      }

      return $success;
   }

   /**
    * Migrate configurations in database
    *
    * @param string $current_key Current key
    *
    * @return boolean
    */
   protected function migrateConfigsInDb($current_key) {
      global $DB;

      $success = true;

      foreach ($this->getConfigs() as $context => $names) {
         $iterator = $DB->request([
            'FROM'   => Config::getTable(),
            'WHERE'  => [
               'context'   => $context,
               'name'      => $names
            ]
         ]);

         while ($success && $row = $iterator->next()) {
            $pass = Toolbox::encrypt(Toolbox::decrypt($row['value'], $current_key));
            $success = $DB->update(
               Config::getTable(),
               ['value' => $pass],
               ['id'    => $row['id']]
            );
         }
      }

      return $success;
   }
}
