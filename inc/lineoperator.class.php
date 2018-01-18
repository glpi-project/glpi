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

/**
 * @since 9.2
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class LineOperator extends CommonDropdown {

   static $rightname = 'lineoperator';

   public $can_be_translated = false;

   static function getTypeName($nb = 0) {
      return _n('Line operator', 'Line operators', $nb);
   }

   function getAdditionalFields() {
      return [['name'  => 'mcc',
                  'label' => __('Mobile Country Code'),
                  'type'  => 'text',
                  'list'  => true],
            ['name'  => 'mnc',
                  'label' => __('Mobile Network Code'),
                  'type'  => 'text',
                  'list'  => true],
      ];
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'mcc',
            'name'               => __('Mobile Country Code'),
            'datatype'           => 'text'
      ];

      $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'mnc',
            'name'               => __('Mobile Network Code'),
            'datatype'           => 'text'
      ];

      return $tab;
   }

}
