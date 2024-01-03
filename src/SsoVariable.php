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

/**
 * @since 0.84
 **/
class SsoVariable extends CommonDropdown
{
    public static $rightname = 'config';

    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {

        return _n(
            'Field storage of the login in the HTTP request',
            'Fields storage of the login in the HTTP request',
            $nb
        );
    }


    public static function canCreate()
    {
        return static::canUpdate();
    }


    /**
     * @since 0.85
     **/
    public static function canPurge()
    {
        return static::canUpdate();
    }


    public function cleanRelationData()
    {

        parent::cleanRelationData();

        if ($this->isUsedInAuth()) {
            $newval = (isset($this->input['_replace_by']) ? $this->input['_replace_by'] : 0);

            Config::setConfigurationValues(
                'core',
                [
                    'ssovariables_id' => $newval,
                ]
            );
        }
    }


    public function isUsed()
    {

        if (parent::isUsed()) {
            return true;
        }

        return $this->isUsedInAuth();
    }


    /**
     * Check if variable is used in auth process.
     *
     * @return boolean
     */
    private function isUsedInAuth()
    {

        $config_values = Config::getConfigurationValues('core', ['ssovariables_id']);

        return array_key_exists('ssovariables_id', $config_values)
         && $config_values['ssovariables_id'] == $this->fields['id'];
    }
}
