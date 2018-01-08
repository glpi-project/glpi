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

class OperatingSystemKernelVersion extends CommonDropdown {

   public $can_be_translated = false;

   static function getTypeName($nb = 0) {
      return _n('Kernel version', 'Kernel versions', $nb);
   }

   function getAdditionalFields() {
      $fields   = parent::getAdditionalFields();
      $fields[] = [
         'label'  => __('Kernel'),
         'name'   => OperatingSystemKernel::getTypeName(Session::getPluralNumber()),
         'list'   => true,
         'type'   => 'oskernel'
      ];

      return $fields;
   }

   function displaySpecificTypeField($ID, $field = []) {
      switch ($field['type']) {
         case 'oskernel':
            OperatingSystemKernel::dropdown(['value' => $this->fields['operatingsystemkernels_id']]);
            break;
      }
   }

   function getRawName() {
      $kernel = new OperatingSystemKernel();
      $kname = $kernel->getRawName();
      $kvname = parent::getRawName();

      $name = str_replace(
         ['%kernel', '%version'],
         [$kname, $kvname],
         '%kernel %version'
      );
      return trim($name);
   }
}
