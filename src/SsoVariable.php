<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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


    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInput($input);

        if ($input === false) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input);

        if ($input === false) {
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|false
     */
    private function prepareInput(array $input): array|false
    {
        if (
            $this->isServerVariableSafe($input['name'] ?? '') === false
            && ($this->isNewItem() || $input['name'] !== $this->fields['name'])
        ) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('The %s variable is unsafe. It should not be used for authentication.'),
                    htmlescape($input['name']),
                ),
                false,
                ERROR
            );
            return false;
        }

        return $input;
    }


    public function cleanRelationData()
    {

        parent::cleanRelationData();

        if ($this->isUsedInAuth()) {
            $newval = ($this->input['_replace_by'] ?? 0);

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
     * @return bool
     */
    private function isUsedInAuth()
    {

        $config_values = Config::getConfigurationValues('core', ['ssovariables_id']);

        return array_key_exists('ssovariables_id', $config_values)
         && $config_values['ssovariables_id'] == $this->fields['id'];
    }

    /**
     * Indicates whether the current variable is safe.
     */
    public function isSafe(): bool
    {
        return $this->isServerVariableSafe($this->fields['name'] ?? '');
    }

    /**
     * Indicates whether a server variable is safe.
     */
    private function isServerVariableSafe(string $name): bool
    {
        $name = mb_strtoupper($name);

        return str_starts_with($name, 'HTTP_') === false
            && $name !== 'PHP_AUTH_USER';
    }
}
