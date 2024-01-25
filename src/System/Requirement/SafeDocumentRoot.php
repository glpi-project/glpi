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

namespace Glpi\System\Requirement;

/**
 * @since 10.0.7
 */
final class SafeDocumentRoot extends AbstractRequirement
{
    public function __construct()
    {
        parent::__construct(
            __('Safe configuration of web root directory'),
            sprintf(
                __('Web server root directory should be `%s` to ensure non-public files cannot be accessed.'),
                realpath(GLPI_ROOT) . DIRECTORY_SEPARATOR . 'public'
            ),
            true,
            true,
            isCommandLine() // out of context when tested from CLI
        );
    }

    protected function check()
    {
        if (isCommandLine()) {
            $this->validated = false;
            $this->validation_messages[] = __('Checking web server root directory configuration cannot be done on CLI context.');
            return;
        }

        $included_files = get_included_files();
        $initial_script = array_shift($included_files);

        // If `auto_prepend_file` configuration is used, ignore first included files
        // as long as they are not located inside GLPI directory tree.
        $prepended_file = ini_get('auto_prepend_file');
        if ($prepended_file !== '' && $prepended_file !== 'none') {
            while (
                $initial_script !== null
                && !str_starts_with(
                    realpath($initial_script) ?: '',
                    realpath(GLPI_ROOT)
                )
            ) {
                $initial_script = array_shift($included_files);
            }
        }

        if ($initial_script !== null && realpath($initial_script) === realpath(sprintf('%s/public/index.php', GLPI_ROOT))) {
            // Configuration is safe if install/update script is accessed through `public/index.php` router script.
            $this->validated = true;
            $this->validation_messages[] = __('Web server root directory configuration seems safe.');
        } else {
            $this->validated = false;
            $this->validation_messages[] = __('Web server root directory configuration is not safe as it permits access to non-public files. See installation documentation for more details.');
        }
    }
}
