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

/// Class DocumentCategory
class DocumentCategory extends CommonTreeDropdown {

   public $can_be_translated = true;


   static function getTypeName($nb = 0) {
      return _n('Document heading', 'Document headings', $nb);
   }


   function cleanRelationData() {

      parent::cleanRelationData();

      if ($this->isUsedAsDefaultCategoryForTickets()) {
         $newval = (isset($this->input['_replace_by']) ? $this->input['_replace_by'] : 0);

         Config::setConfigurationValues(
            'core',
            [
               'documentcategories_id_forticket' => $newval,
            ]
         );
      }
   }


   function isUsed() {

      if (parent::isUsed()) {
         return true;
      }

      return $this->isUsedAsDefaultCategoryForTickets();
   }


   /**
    * Check if category is used as default for tickets documents.
    *
    * @return boolean
    */
   private function isUsedAsDefaultCategoryForTickets() {

      $config_values = Config::getConfigurationValues('core', ['documentcategories_id_forticket']);

      return array_key_exists('documentcategories_id_forticket', $config_values)
         && $config_values['documentcategories_id_forticket'] == $this->fields['id'];
   }
}
