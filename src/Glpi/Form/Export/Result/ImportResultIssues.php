<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Form\Export\Result;

use Glpi\Form\Export\Specification\DataRequirementSpecification;

final class ImportResultIssues
{
    /** @var array<int, DataRequirementSpecification[]> $issues */
    private array $issues = [];

    /** @param DataRequirementSpecification[] $issues */
    public function addIssues(int $id, array $issues): void
    {
        $this->issues[$id] = $issues;
    }

    /** @return array<int, DataRequirementSpecification[]> */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @deprecated 11.0.14 Use {@see addIssues()}
     * @param DataRequirementSpecification[] $issues
     */
    public function addIssuesForForm(int $form_id, array $issues): void
    {
        $this->addIssues($form_id, $issues);
    }
}
