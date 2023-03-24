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
 * @since 10.0.4
 */
final class DataDirectoriesProtectedPath extends AbstractRequirement
{
    /**
     * Constants defining directories to check.
     *
     * @var string[]
     */
    private $directories_constants;

    /**
     * Constant defining variable root directory.
     *
     * @var string
     */
    private $var_root_constant;

    /**
     * GLPI root directory.
     *
     * @var string
     */
    private $glpi_root_directory;

    /**
     * @param array $directories_constants  Constants defining directories to check.
     * @param string $var_root_constant     Constant defining variable root directory.
     * @param string $root_directory        Web root directory.
     */
    public function __construct(
        array $directories_constants,
        string $var_root_constant = 'GLPI_VAR_DIR',
        string $glpi_root_directory = GLPI_ROOT
    ) {
        $this->title = __('Safe path for data directories');
        $this->description = __('GLPI data directories should be placed outside web root directory. It can be achieved by redefining corresponding constants. See installation documentation for more details.');
        $this->optional = true;

        $this->directories_constants = $directories_constants;
        $this->var_root_constant     = $var_root_constant;
        $this->glpi_root_directory   = $glpi_root_directory;
    }

    protected function check()
    {
        $glpi_root_directory = realpath($this->glpi_root_directory);

        $missing_directories = [];
        $unsafe_directories  = [];

        $var_root_directory = constant($this->var_root_constant);
        if (!is_dir($var_root_directory)) {
            $missing_directories[$this->var_root_constant] = $var_root_directory;
        } elseif (str_starts_with(realpath($var_root_directory), $glpi_root_directory)) {
            $unsafe_directories[$this->var_root_constant] = $var_root_directory;
        }

        $var_root_directory = realpath($var_root_directory);

        foreach ($this->directories_constants as $directory_constant) {
            $directory_path = constant($directory_constant);

            if (!is_dir($directory_path)) {
                $missing_directories[$directory_constant] = $directory_path;
                continue;
            }

            $directory_path = realpath($directory_path);
            if (str_starts_with($directory_path, $var_root_directory)) {
                // Ignore directory as it is included in variable root path that is already tested independently.
                continue;
            }

            if (str_starts_with(realpath($directory_path), $glpi_root_directory)) {
                $unsafe_directories[$directory_constant] = $directory_path;
            }
        }

        if (count($missing_directories) === 0 && count($unsafe_directories) === 0) {
            $this->validated = true;
            $this->validation_messages[] = __('GLPI data directories are located in a secured path.');
        } else {
            $this->validated = false;
            if (count($missing_directories) > 0) {
                $this->validation_messages[] = __('The following directories do not exist and cannot be tested:');
                foreach ($missing_directories as $constant => $path) {
                    $this->validation_messages[] = sprintf('‣ "%s" ("%s")', $path, $constant);
                }
            }
            if (count($unsafe_directories) > 0) {
                $this->validation_messages[] = sprintf(
                    __('The following directories should be placed outside "%s":'),
                    $glpi_root_directory
                );
                foreach ($unsafe_directories as $constant => $path) {
                    $this->validation_messages[] = sprintf('‣ "%s" ("%s")', $path, $constant);
                }
            }
            $this->validation_messages[] = sprintf(
                __('You can ignore this suggestion if your web server root directory is "%s".'),
                $glpi_root_directory . DIRECTORY_SEPARATOR . 'public'
            );
        }
    }
}
