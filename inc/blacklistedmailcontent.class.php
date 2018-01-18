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
 * BlacklistedMailContent Class
 *
 * @since 0.85
**/
class BlacklistedMailContent extends CommonDropdown {

   // From CommonDBTM
   public $dohistory       = false;

   static $rightname       = 'config';

   public $can_be_translated = false;


   static function getTypeName($nb = 0) {
      return __('Blacklisted mail content');
   }


   static function canCreate() {
      return static::canUpdate();
   }


   static function canPurge() {
      return static::canUpdate();
   }


   function getAdditionalFields() {

      return [['name'  => 'content',
                         'label' => __('Content'),
                         'type'  => 'textarea',
                         'rows'  => 20,
                         'list'  => true]];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Content'),
         'datatype'           => 'text',
         'massiveaction'      => false
      ];

      return $tab;
   }

}
