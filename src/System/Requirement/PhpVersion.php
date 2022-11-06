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

use Glpi\Toolbox\VersionParser;

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
        // Remove potential stability flag used in version strings
        $min_version = VersionParser::getNormalizedVersion($this->min_version, false);
        $max_version = VersionParser::getNormalizedVersion($this->max_version, false);

        // Use `-dev` stability flag in comparisons
        // -> 7.4.0-dev, 7.4.0-alphaX, 7.4.0-rcX are accepted when $this->min_version is 7.4.0
        // -> 8.2.0-dev, 8.2.0-alphaX, 8.2.0-rcX is refused when $this->max_version is 8.2.0
        $this->validated = version_compare(PHP_VERSION, sprintf('%s-dev', $min_version), '>=')
            && version_compare(PHP_VERSION, sprintf('%s-dev', $max_version), '<');

        $this->validation_messages[] = $this->validated
            ? sprintf(__('PHP version (%s) is supported.'), PHP_VERSION)
            : sprintf(__('PHP version must be between %s and %s (exclusive).'), $min_version, $max_version);
    }
}
