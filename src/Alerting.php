<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 *  Alerting class
 *
  **/
class Alerting extends CommonGLPI
{
    public static $rightname                = 'alerting_summary';

    public static function getTypeName($nb = 0)
    {
        return __('Alerting');
    }

    /**
     * @since 0.85
     **/
    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * Print a good title
     *
     *@return void
     **/
    public static function title()
    {
        Html::displayTitle(
            "",
            self::getTypeName(),
            "<i class='fas fa-check fa-lg me-2'></i>" . self::getTypeName()
        );
    }

    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = [ READ => __('Read')];
        return $values;
    }


    public static function getIcon()
    {
        return "ti ti-alert-square";
    }
}
