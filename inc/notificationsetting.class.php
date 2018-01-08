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
 * Abstract notifications settings class
 */
abstract class NotificationSetting extends CommonDBTM {

   public $table           = 'glpi_configs';
   protected $displaylist  = false;
   static $rightname       = 'config';

   static public function getTypeName($nb = 0) {
      throw new \RuntimeException('getTypeName must be implemented');
   }

   /**
    * Get associated mode
    *
    * @return string
    */
   static public function getMode() {
      //For PHP 5.x; a method cannot be abstract and static
      throw new \RuntimeException('getMode must be implemented');
   }


   /**
    * Get label for enable configuration
    *
    * @return string
    */
   abstract public function getEnableLabel();

   /**
    * Print the config form
    *
    * @return void
    */
   abstract protected function showFormConfig();


   public static function getTable($classname = null) {
      return parent::getTable('Config');
   }


   function defineTabs($options = []) {
      $ong = [];
      $this->addStandardTab(static::class, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case static::class:
            $tabs[1] = __('Setup');
            return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == static::class) {
         switch ($tabnum) {
            case 1 :
               $item->showFormConfig();
               break;
         }
      }
      return true;
   }


   /**
    * Disable (temporary) all notifications
    *
    * @return void
    */
   static public function disableAll() {
      global $CFG_GLPI;

      $CFG_GLPI['use_notifications'] = 0;
      foreach (array_keys($CFG_GLPI) as $key) {
         if (substr($key, 0, strlen('notifications_')) === 'notifications_') {
            $CFG_GLPI[$key] = 0;
         }
      }
   }
}
