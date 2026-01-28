<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Config;

use CommonGLPI;

final class ProxyExclusions
{
    /** @var array<class-string<CommonGLPI>, ProxyExclusion> */
    private array $exclusions = [];

    public function addExclusion(ProxyExclusion $exclusion): self
    {
        $this->exclusions[$exclusion->getClassname()] = $exclusion;
        return $this;
    }

    /** @param array<ProxyExclusion> $exclusions */
    public function addExclusions(array $exclusions): self
    {
        foreach ($exclusions as $exclusion) {
            $this->addExclusion($exclusion);
        }
        return $this;
    }

    /**
     * @return array<class-string<CommonGLPI>, ProxyExclusion>
     */
    public function getExclusions(): array
    {
        return $this->exclusions;
    }

    public function getDropDownValues(): array
    {
        $values = [];
        foreach ($this->exclusions as $exclusion) {
            $values[$exclusion->getClassname()] = $exclusion->getLabel();
        }
        return $values;
    }

    public function getDescriptions(): array
    {
        $descriptions = [__('Objects that should not use proxy configuration:')];
        foreach ($this->exclusions as $exclusion) {
            $descriptions[] = $exclusion->getDescription();
        }
        return $descriptions;
    }
}
