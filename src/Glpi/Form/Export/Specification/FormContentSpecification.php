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

use Glpi\Form\Export\Serializer\DynamicExportData;

final class FormContentSpecification
{
    public int $id;
    public string $uuid;
    public string $name;
    public ?string $header = null;
    public ?string $description = null;
    public string $category_name;
    public string $entity_name;
    public bool $is_recursive;
    public bool $is_active;
    public string $submit_button_visibility_strategy;

    /** @var string|CustomIllustrationContentSpecification $illustration**/
    public string|CustomIllustrationContentSpecification $illustration;

    /** @var ConditionDataSpecification[] $conditions */
    public array $submit_button_conditions;

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

    /** @var TranslationContentSpecification[] $translations */
    public array $translations = [];

    /** @var DataRequirementSpecification[] $data_requirements */
    public array $data_requirements = [];

    /** @return DataRequirementSpecification[] */
    public function getDataRequirements(): array
    {
        return $this->data_requirements;
    }

    public function addDataRequirement(
        DataRequirementSpecification $requirement
    ): void {
        $this->data_requirements[] = $requirement;
    }

    public function addRequirementsFromDynamicData(DynamicExportData $data): void
    {
        array_push($this->data_requirements, ...$data->getRequirements());
    }
}
