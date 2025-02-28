<?php

namespace tests\units;
class ValidationStepTest extends \DbTestCase
{
    public function testTheLastValidationCannotBeDeleted_IfItsTheOnlyOne()
    {
        $this->markTestSkipped();
    }

    public function testTheLastValidationStepCanBeDeleted_IfItsNotTheLastOne()
    {
        $this->markTestSkipped();
    }

    public function testDefaultAttributeIsRemoved_WhenSetToAnotherValidationStep()
    {
        $this->markTestSkipped();
    }

    public function testDefaultAttributeIsSetToAnotherValidationStep_WhenTheDefaultIsDeleted()
    {
        $this->markTestSkipped();
    }

    public function testNameAttributeIsMandatory()
    {
        $this->markTestSkipped();
    }

    public function testMinimalRequiredValidationPercentAttributeIsMandatory()
    {
        $this->markTestSkipped();
    }

    public function testMinimalRequiredValidationPercentAttributeIsAPercentage()
    {
        $this->markTestSkipped();
    }

}