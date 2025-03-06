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
        // assert on add
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        unset($data['name']);

        $this->assertFalse($vs->add($data), 'A validation step without name should not be created');
        $this->hasSessionMessages(ERROR, [ 'The name field is mandatory']);

        // assert on update
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['name'] = '';

        $this->assertFalse($vs->update($data), 'A validation step without name should not be updated');
        $this->hasSessionMessages(ERROR, ['The name field is mandatory']);
    }

    public function testMinimalRequiredValidationPercentAttributeIsMandatory()
    {
        // assert on add
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        unset($data['minimal_required_validation_percent']);
        $validation_error_message = sprintf(__s('The %s field is mandatory and must be beetween 0 and 100.'), $vs->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent')
;
        $this->assertFalse($vs->add($data), 'A validation step without minimal required validation percent should not be created');
        $this->hasSessionMessages(ERROR, [$validation_error_message]);

        // assert on update
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['minimal_required_validation_percent'] = '';

        $this->assertFalse($vs->add($data), 'A validation step without minimal required validation percent should not be updated');
        $this->hasSessionMessages(ERROR, [$validation_error_message]);
    }

    public function testMinimalRequiredValidationPercentAttributeIsAPercentage()
    {
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $expected_validation_error_message = sprintf(__s('The %s field is mandatory and must be beetween 0 and 100.'), $vs->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent');

        // act add - set a value higher than 100
        $data['minimal_required_validation_percent'] = 101;
        $this->assertFalse($vs->add($data), 'A validation step with "minimal_required_validation_percent" greater than 100 should not be created');
        $this->hasSessionMessages(ERROR, [ $expected_validation_error_message]);

        // act add - set a value lower than 0
        $data['minimal_required_validation_percent'] = -1;
        $this->assertFalse($vs->add($data), 'A validation step with "minimal_required_validation_percent" lower than 0 should not be created');
        $this->hasSessionMessages(ERROR, [ $expected_validation_error_message]);

        // act update - set a value higher than 100
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['minimal_required_validation_percent'] = 101;
        $this->assertFalse($vs->update($data), 'A validation step with "minimal_required_validation_percent" greater than 100 should not be updated');
        $this->hasSessionMessages(ERROR, [ $expected_validation_error_message]);

        // act update - set a value lower than 0
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['minimal_required_validation_percent'] = -1;
        $this->assertFalse($vs->update($data), 'A validation step with "minimal_required_validation_percent" lower than 0 should not be updated');
        $this->hasSessionMessages(ERROR, [ $expected_validation_error_message]);
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
            'minimal_required_validation_percent' => 100,
        ];
    }
}
