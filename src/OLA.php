<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * OLA Class
 * @since 9.2
 **/
class OLA extends LevelAgreement
{
    protected static $prefix            = 'ola';

    /**
     * Remove associated OLA
     *
     */
    public function cleanDBonPurge()
    {
        // @todoseb à implementer : chercher ola liées et les supprimer
        //        parent::cleanDBonPurge();
    }

    protected static $prefixticket      = 'internal_'; // @todoseb pas utilisé selon phpstorm : voir si on peut vraiment le supprimer
    protected static $levelclass        = 'OlaLevel';
    protected static $levelticketclass  = 'OlaLevel_Ticket';
    protected static $forward_entity_to = ['OlaLevel'];

    /**
     * Get table fields
     *
     * @param integer $subtype of OLA/SLA, can be SLM::TTO or SLM::TTR
     *
     * @return array of 'date' and 'sla' field names
     */
    public static function getFieldNames($subtype)
    {
        throw new \Exception('On ne doit plus utiliser '.__FUNCTION__.' pour les OLA. SLM::TTO + (chercher dans templates) '.$subtype);
        // @todoseb déplacer le parent dans LevelAgreement ? (probablement pas)
    }

    public static function getTypeName($nb = 0)
    {
        // Acronym, no plural
        return __('OLA');
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', SLM::class, self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    public static function getIcon()
    {
        return SLM::getIcon();
    }

    public function showFormWarning()
    {
        global $CFG_GLPI;

        echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='" . __s('Warning') . "'>";
        echo __s('The internal time is recalculated when assigning the OLA');
    }

    public function getAddConfirmation(): array
    {
        return [
            __("The assignment of an OLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the OLA will be triggered under this new date."),
        ];
    }
}
