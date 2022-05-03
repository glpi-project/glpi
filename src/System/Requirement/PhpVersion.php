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

/**
 * @since 9.5.0
 */
class PhpVersion extends AbstractRequirement
{
    /**
     * Minimal required PHP version.
     *
     * @var string
     */
    private $min_version;

    /**
     * Maximum required PHP version (exclusive).
     *
     * @var string
     */
    private $max_version;

    /**
     * @param string $min_version  Minimal required PHP version
     * @param string $max_version  Maximum required PHP version (exclusive)
     */
    public function __construct(string $min_version, string $max_version)
    {
        $this->title = __('PHP Parser');
        $this->min_version = $min_version;
        $this->max_version = $max_version;
    }

    protected function check()
    {
        $this->validated = version_compare(PHP_VERSION, $this->min_version, '>=')
            && version_compare(PHP_VERSION, $this->max_version, '<');

        $this->validation_messages[] = $this->validated
         ? sprintf(__('PHP version (%s) is supported.'), PHP_VERSION)
         : sprintf(__('PHP version must be between %s and %s (exclusive).'), $this->min_version, $this->max_version);
    }
}
