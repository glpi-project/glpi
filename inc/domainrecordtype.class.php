<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

class DomainRecordType extends CommonDropdown
{
   static $rightname = 'dropdown';

   static public $knowtypes = [
      [
         'id'        => 1,
         'name'      => 'A',
         'comment'   => 'Host address'
      ], [
         'id'        => 2,
         'name'      => 'AAAA',
         'comment'   => 'IPv6 host address'
      ], [
         'id'        => 3,
         'name'      => 'ALIAS',
         'comment'   => 'Auto resolved alias'
      ], [
         'id'        => 4,
         'name'      => 'CNAME',
         'comment'   => 'Canonical name for an alias',
      ], [
         'id'        => 5,
         'name'      => 'MX',
         'comment'   => 'Mail eXchange'
      ], [
         'id'        => 6,
         'name'      => 'NS',
         'comment'   => 'Name Server'
      ], [
         'id'        => 7,
         'name'      => 'PTR',
         'comment'   => 'Pointer'
      ], [
         'id'        => 8,
         'name'      => 'SOA',
         'comment'   => 'Start Of Authority',
      ], [
         'id'        => 9,
         'name'      => 'SRV',
         'comment'   => 'Location of service'
      ], [
         'id'        => 10,
         'name'      => 'TXT',
      'comment'    => 'Descriptive text'
      ]
   ];

   static function getTypeName($nb = 0) {
      return _n('Record type', 'Records types', $nb);
   }

   public static function getDefaults() {
      return array_map(
         function($e) {
            $e['is_recursive'] = 1;
            return $e;
         },
         self::$knowtypes
      );
   }

}
