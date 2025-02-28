<?php

namespace tests\units;

use ValidationStep;

class ValidationStepTest extends \DbTestCase
{
    public function testDefaultValidationStepExistAtInstallation()
    {
        $this->assertGreaterThan(0, countElementsInTable(\ValidationStep::getTable()), 'At least one validation step should be created at installation');
        $this->assertEquals(1, $this->getInitialDefault()->getField('is_default'), 'A default validation step should be created at installation');
    }

    public function testTheLastValidationCannotBeDeletedIfItsTheOnlyOne()
    {
        assert(1 === countElementsInTable(\ValidationStep::getTable()), 'Test expects only one validation step at start');

        $default = $this->getInitialDefault();
        $this->assertFalse($default->delete(['id' => $default->getID()]), 'The last remaining validation step must not be deleted');
    }

    public function testAValidationStepCanBeDeletedIfItsNotTheLastOne()
    {
        $this->createItem(\ValidationStep::class, $this->getValidData());

        $default = $this->getInitialDefault();
        $this->assertTrue($default->delete(['id' => $default->getID()]), 'The (initial) validation can be deleted, if it is not the last remaining one.');
    }

    public function testDefaultAttributeIsRemovedWhenSetToAnotherValidationStep()
    {
        // initial default is the default (@see testDefaultValidationStepExistAtInstallation() )
        // act : create a new validation step and set it as default
        $this->createItem(\ValidationStep::class, ['is_default' => 1] + $this->getValidData());

        // assert
        $this->assertEquals(0, $this->getInitialDefault()->getField('is_default'), 'Previous default validation step should not be the default anymore.');
    }

    public function testDefaultAttributeIsSetToAnotherValidationStepWhenTheDefaultIsDeleted()
    {
        // arrange - create a non default validation step
        $new = $this->createItem(\ValidationStep::class, $this->getValidData());
        $new_name = $new->getField('name');

        // act - delete the default validation step
        $default = $this->getInitialDefault();
        assert(true === $default->delete(['id' => $default->getID()]), 'Test expect that the (initial) validation step can be deleted.');

        // assert - the previous non default validation step is the new default
        $new_default = getItemByTypeName(ValidationStep::class, $new_name);
        $this->assertEquals(1, $new_default->getField('is_default'), 'The previous non default validation step should be the new default.');
    }

    public function testNameAttributeIsMandatory()
    {
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        unset($data['name']);

        $this->assertFalse($vs->add($data), 'A validation step without name should not be created');
    }

    public function testMinimalRequiredValidationPercentAttributeIsMandatory()
    {
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        unset($data['mininal_required_validation_percent']);

        $this->assertFalse($vs->add($data), 'A validation step without minimal required validation percent should not be created');
    }

    public function testMinimalRequiredValidationPercentAttributeIsAPercentage()
    {
        $vs = new \ValidationStep();
        $data = $this->getValidData();

        // act - set a value higher than 100
        $data['mininal_required_validation_percent'] = 101;
        $this->assertFalse($vs->add($data), 'A validation step with a minimal required validation percent greater than 100 should not be created');

        // act - set a value lower than 0
        $data['mininal_required_validation_percent'] = -1;
        $this->assertFalse($vs->add($data), 'A validation step with a minimal required validation percent lower than 0 should not be created');
    }

    private function getInitialDefault(): \ValidationStep
    {
        return getItemByTypeName(\ValidationStep::class, 'Validation');
    }

    /**
     * Fields for a valid validation step
     *
     * @return array<string, mixed>
     */
    private function getValidData(): array
    {
        return [
            'name' => 'Tech team',
            'mininal_required_validation_percent' => 100,
        ];
    }

}