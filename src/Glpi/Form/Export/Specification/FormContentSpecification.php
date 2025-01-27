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

namespace Glpi\Form\Export\Specification;

final class FormContentSpecification
{
    public int $id;
    public string $name;
    public ?string $header;
    public ?string $description;
    public string $illustration;
    public string $category_name;
    public string $entity_name;
    public bool $is_recursive;
    public bool $is_active;

    /** @var SectionContentSpecification[] $sections */
    public array $sections = [];

    /** @var CommentContentSpecification[] $comments */
    public array $comments = [];

    /** @var QuestionContentSpecification[] $questions */
    public array $questions = [];

    /** @var AccesControlPolicyContentSpecification[] $policies */
    public array $policies = [];

    /** @var DestinationContentSpecification[] $destinations */
    public array $destinations = [];

    /** @var DataRequirementSpecification[] $data_requirements */
    public array $data_requirements = [];

    /** @return DataRequirementSpecification[] */
    public function getDataRequirements(): array
    {
        return $this->data_requirements;
    }

    public function addDataRequirement(
        string $class,
        string $name,
    ): void {
        $requirement = new DataRequirementSpecification();
        $requirement->itemtype = $class;
        $requirement->name = $name;
        $this->data_requirements[] = $requirement;
    }
}
