<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/// Class DocumentCategory
class DocumentCategory extends CommonTreeDropdown
{
    public $can_be_translated = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Document heading', 'Document headings', $nb);
    }


    public function cleanRelationData()
    {

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


    public function isUsed()
    {

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
    private function isUsedAsDefaultCategoryForTickets()
    {

        $config_values = Config::getConfigurationValues('core', ['documentcategories_id_forticket']);

        return array_key_exists('documentcategories_id_forticket', $config_values)
         && $config_values['documentcategories_id_forticket'] == $this->fields['id'];
    }

    public static function getIcon()
    {
        return "fas fa-tags";
    }
}
