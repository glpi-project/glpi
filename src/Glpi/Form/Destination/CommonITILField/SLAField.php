<?php

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;

abstract class SLAField extends SLMField
{
    #[\Override]
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
        /** @var class-string<\SLA> $slm_class */
        $slm_class = $this->getSLMClass();
        if (!$slm_class::getById($slm_id)) {
            return $input;
        }

        $input[$slm_class::getFieldNames($this->getType())[1]] = $slm_id;

        return $input;
    }
}
