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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use OLA;
use Override;
use SLM;

final class OLATTOField extends SLMField
{
    #[Override]
    public function getLabel(): string
    {
        return __("Internal TTO");
    }

    #[Override]
    public function getWeight(): int
    {
        return 220;
    }

    #[Override]
    public function getSLMClass(): string
    {
        return OLA::class;
    }

    #[Override]
    public function getType(): int
    {
        return SLM::TTO;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return OLATTOFieldConfig::class;
    }

    #[Override]
    protected function getFieldNameToConvertSpecificSLMID(): string
    {
        return 'ola_question_tto';
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof SLMFieldConfig) {
            throw new \InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $slm_id = $strategy->getSLMID($config);

        // Do not edit input if invalid value was found
        $slm_class = $this->getSLMClass();
        if (!$slm_class::getById($slm_id)) {
            return $input;
        }

        $input['_olas_id'] = [$slm_id];
        $input['_la_update'] = true; // This is required to update the SLM field in the ticket

        return $input;
    }

}
