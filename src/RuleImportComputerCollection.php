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

/// Import rules collection class
// @deprecated 10.0.0 @see RuleImportAssetCollection
class RuleImportComputerCollection extends RuleCollection
{
   // From RuleCollection
    public $stop_on_first_match = true;
    public static $rightname           = 'rule_import';
    public $menu_option         = 'linkcomputer';


    /**
     * @since 0.84
     *
     * @return boolean
     **/
    public function canList()
    {
        if (Plugin::haveImport()) {
            return static::canView();
        }
        return false;
    }


    public function getTitle()
    {
        return __('Rules for import and link computers');
    }
}
