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

namespace Glpi\System;

use Glpi\System\Requirement\RequirementInterface;
use Traversable;

/**
 * @since 9.5.0
 */
class RequirementsList implements \IteratorAggregate
{
    /**
     * Requirements.
     *
     * @var RequirementInterface[]
     */
    private $requirements;

    /**
     * @param RequirementInterface[] $requirements
     */
    public function __construct(array $requirements = [])
    {
        $this->requirements = $requirements;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->requirements);
    }

    /**
     * Indicates if a mandatory requirement is missing.
     *
     * @return boolean
     */
    public function hasMissingMandatoryRequirements()
    {
        foreach ($this->requirements as $requirement) {
            if (!$requirement->isOutOfContext() && $requirement->isMissing() && !$requirement->isOptional()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Indicates if an optional requirement is missing.
     *
     * @return boolean
     */
    public function hasMissingOptionalRequirements()
    {
        foreach ($this->requirements as $requirement) {
            if (!$requirement->isOutOfContext() && $requirement->isMissing() && $requirement->isOptional()) {
                return true;
            }
        }
        return false;
    }
}
