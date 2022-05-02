<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * @since 0.85 (before migration_cleaner)
 **/
class MigrationCleaner extends CommonGLPI
{
    public static $rightname = 'networking';


    public static function getTypeName($nb = 0)
    {
        return __('Migration cleaner');
    }


    /**
     * @see CommonGLPI::getAdditionalMenuOptions()
     **/
    public static function getAdditionalMenuOptions()
    {

        if (static::canView()) {
            $options['networkportmigration']['title']  = NetworkPortMigration::getTypeName(Session::getPluralNumber());
            $options['networkportmigration']['page']   = NetworkPortMigration::getSearchURL(false);
            $options['networkportmigration']['search'] = NetworkPortMigration::getSearchURL(false);

            return $options;
        }
        return false;
    }


    public static function canView()
    {
        global $DB;

        if (!isset($_SESSION['glpishowmigrationcleaner'])) {
            if (
                $DB->tableExists('glpi_networkportmigrations')
                && (countElementsInTable('glpi_networkportmigrations') > 0)
            ) {
                $_SESSION['glpishowmigrationcleaner'] = true;
            } else {
                $_SESSION['glpishowmigrationcleaner'] = false;
            }
        }

        if (
            $_SESSION['glpishowmigrationcleaner']
            && (Session::haveRight("networking", UPDATE)
              || Session::haveRight("internet", UPDATE))
        ) {
            return true;
        }

        return false;
    }

    public static function getIcon()
    {
        return "fas fa-broom";
    }
}
