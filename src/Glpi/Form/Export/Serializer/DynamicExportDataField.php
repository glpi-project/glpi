<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\Export\Serializer;

use Glpi\Form\Export\Specification\DataRequirementSpecification;

final readonly class DynamicExportDataField
{
    private mixed $data;

    /** @var DataRequirementSpecification[] */
    private array $requirements;

    public function __construct(
        mixed $data,
        array $requirements
    ) {
        $this->data = $data;
        $this->requirements = $requirements;
    }

    public function getData(): mixed
    {
        return $this->data;
    }


    /** @return DataRequirementSpecification[] */
    public function getRequirements(): array
    {
        return $this->requirements;
    }
}
