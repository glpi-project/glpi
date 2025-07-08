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

namespace Glpi\System\Requirement;

/**
 * @since 10.0.0
 */
class DirectoriesWriteAccess extends AbstractRequirement
{
    /**
     * Directories paths.
     *
     * @var string[]
     */
    private $paths;

    /**
     * @param string   $title     Requirement title.
     * @param string[] $paths     Directories paths.
     * @param bool     $optional  Indicated if write access is optional.
     */
    public function __construct(string $title, array $paths, bool $optional = false)
    {
        parent::__construct($title, null, $optional);

        $this->paths = $paths;
    }

    protected function check()
    {

        $this->validated = true;

        foreach ($this->paths as $path) {
            $directory_write_access = new DirectoryWriteAccess($path);
            $this->validated = $this->validated && $directory_write_access->isValidated();
            $this->validation_messages = array_merge($this->validation_messages, $directory_write_access->getValidationMessages());
        }
    }
}
