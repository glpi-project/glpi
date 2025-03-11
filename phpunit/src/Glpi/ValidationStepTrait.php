<?php

namespace Glpi\PHPUnit\Tests\Glpi;

trait ValidationStepTrait
{
    protected function getInitialDefaultValidationStep(): \ValidationStep
    {
        return getItemByTypeName(\ValidationStep::class, 'Validation');
    }
}