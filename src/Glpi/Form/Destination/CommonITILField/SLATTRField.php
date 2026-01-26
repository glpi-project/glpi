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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Form;
use Glpi\Form\Migration\FormMigration;
use LevelAgreement;
use Override;
use SLA;
use SLM;

final class SLATTRField extends SLMField
{
    #[Override]
    public function getLabel(): string
    {
        return __("TTR");
    }

    #[Override]
    public function getWeight(): int
    {
        return 210;
    }

    #[Override]
    public function getSLM(): LevelAgreement
    {
        return new SLA();
    }

    #[Override]
    public function getType(): int
    {
        return SLM::TTR;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return SLATTRFieldConfig::class;
    }

    #[Override]
    protected function getFieldNameToConvertSpecificSLMID(): string
    {
        return 'sla_question_ttr';
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        $parent_config = parent::convertFieldConfig($migration, $form, $rawData);
        if ($parent_config != $this->getDefaultConfig($form)) {
            return $parent_config;
        }

        switch ($rawData['due_date_rule']) {
            case 2: // PluginFormcreatorAbstractItilTarget::DUE_DATE_RULE_ANSWER
                return $this->getConfig($form, [$this->getKey() => [
                    SLMFieldConfig::STRATEGY => SLMFieldStrategy::SPECIFIC_DATE_ANSWER->value,
                    SLMFieldConfig::QUESTION_ID => $migration->getMappedItemTarget(
                        'PluginFormcreatorQuestion',
                        $rawData['due_date_question'] ?? 0
                    )['items_id']
                ]]);
            case 3: // PluginFormcreatorAbstractItilTarget::DUE_DATE_RULE_TICKET
                return $this->getConfig($form, [$this->getKey() => [
                    SLMFieldConfig::STRATEGY => SLMFieldStrategy::COMPUTED_DATE_FROM_FORM_SUBMISSION->value,
                    SLMFieldConfig::TIME_OFFSET => (int)($rawData['due_date_value'] ?? 0),
                    SLMFieldConfig::TIME_DEFINITION => $this->getTimeDefinitionFromLegacy($rawData['due_date_period'])
                ]]);
            case 4: // PluginFormcreatorAbstractItilTarget::DUE_DATE_RULE_CALC
                return $this->getConfig($form, [$this->getKey() => [
                    SLMFieldConfig::STRATEGY => SLMFieldStrategy::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER->value,
                    SLMFieldConfig::QUESTION_ID => $migration->getMappedItemTarget(
                        'PluginFormcreatorQuestion',
                        $rawData['due_date_question'] ?? 0
                    )['items_id'],
                    SLMFieldConfig::TIME_OFFSET => (int)($rawData['due_date_value'] ?? 0),
                    SLMFieldConfig::TIME_DEFINITION => $this->getTimeDefinitionFromLegacy($rawData['due_date_period'])
                ]]);
        }

        return $this->getDefaultConfig($form);
    }

    private function getTimeDefinitionFromLegacy(int $due_date_value): string
    {
        $time_keys = array_keys(LevelAgreement::getDefinitionTimeValues());
        $time_definition = $time_keys[$due_date_value - 1];

        if ($time_definition !== null) {
            return $time_definition;
        }

        // Fallback to first value
        return current($time_keys);
    }
}
