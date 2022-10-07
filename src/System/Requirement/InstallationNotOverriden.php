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

namespace Glpi\System\Requirement;

use FilesystemIterator;

final class InstallationNotOverriden extends AbstractRequirement
{
    public function __construct()
    {
        $this->title = __('Installation overrides detection');
    }

    protected function check()
    {
        $version_folder = GLPI_ROOT . '/.version/';
        try {
            $file_iterator = new FilesystemIterator($version_folder);
        } catch (\UnexpectedValueException $e) {
            $this->validated = false;
            $this->validation_messages[] = __("`.version` folder doesn't exist.");
            $this->optional = true;
            return;
        }

        if (iterator_count($file_iterator) == 0) {
            $this->validated = false;
            $this->optional  = true;
            $this->validation_messages[] = __("Cannot check as no version file found.");
            return;
        }

        if (iterator_count($file_iterator) > 1) {
            $this->validated = false;
            $this->validation_messages[] = __("Your GLPI installation is overwrited.");
            $this->validation_messages[] = __("We detected files of previous versions of GLPI.");
            $this->validation_messages[] = __("This can lead to security issues or bugs.");
            $this->validation_messages[] = __("Please update your instance according to the recommanded procedure.");
            return;
        }

        $file_iterator->rewind();
        $file_version = $file_iterator->current()->getFilename();
        $current_version = \Glpi\Toolbox\VersionParser::getNormalizedVersion(GLPI_VERSION, false);
        if (!file_exists($version_folder . $current_version)) {
            $this->validated = false;
            $this->validation_messages[] = sprintf(
                __("GLPI version found base in filesystem (%s) mismatches the one defined in includes (%s)."),
                $file_version,
                $current_version
            );
            return;
        }

        $this->validated = true;
        $this->validation_messages[] = __s('Installation is not overriden.');
    }
}
