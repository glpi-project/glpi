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

namespace Glpi\System\Requirement;

/**
 * @since 10.0.7
 */
class PhpSupportedVersion extends AbstractRequirement
{
    /**
     * Minimal supported version of PHP version.
     *
     * @var string
     * @see https://www.php.net/supported-versions
     */
    private const MIN_SUPPORTED_VERSION = '8.0';

    public function __construct()
    {
        $this->title = __('PHP supported version');
        $this->description = __('An officially supported PHP version should be used to get the benefits of security and bug fixes.');
        $this->optional = true;
    }

    protected function check()
    {
        $php_version =  preg_replace('/^(\d+\.\d+)\..*$/', '$1', phpversion());

        if (version_compare($php_version, self::MIN_SUPPORTED_VERSION, '>=')) {
            $this->validated = true;
            // No validation message as we cannot be sure that PHP is up-to-date.
        } else {
            $this->validated = false;
            $this->validation_messages[] = sprintf(
                __('PHP %s official support has ended. An upgrade to a more recent PHP version is recommended.'),
                $php_version
            );
        }
    }
}
