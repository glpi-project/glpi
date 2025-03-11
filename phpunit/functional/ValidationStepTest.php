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

use CommonITILValidation;
use Glpi\ContentTemplates\TemplateDocumentation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Ticket;
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
        $this->hasSessionMessages(ERROR, ['The name field is mandatory']);

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
        $validation_error_message = sprintf(__s('The %s field is mandatory and must be beetween 0 and 100.'), $vs->getAdditionalField('minimal_required_validation_percent')['label'] ?? 'minimal_required_validation_percent');
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
        $this->hasSessionMessages(ERROR, [$expected_validation_error_message]);

        // act add - set a value lower than 0
        $data['minimal_required_validation_percent'] = -1;
        $this->assertFalse($vs->add($data), 'A validation step with "minimal_required_validation_percent" lower than 0 should not be created');
        $this->hasSessionMessages(ERROR, [$expected_validation_error_message]);

        // act update - set a value higher than 100
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['minimal_required_validation_percent'] = 101;
        $this->assertFalse($vs->update($data), 'A validation step with "minimal_required_validation_percent" greater than 100 should not be updated');
        $this->hasSessionMessages(ERROR, [$expected_validation_error_message]);

        // act update - set a value lower than 0
        $vs = new \ValidationStep();
        $data = $this->getValidData();
        $data['id'] = $this->getInitialDefault()->getID();
        $data['minimal_required_validation_percent'] = -1;
        $this->assertFalse($vs->update($data), 'A validation step with "minimal_required_validation_percent" lower than 0 should not be updated');
        $this->hasSessionMessages(ERROR, [$expected_validation_error_message]);
    }

    #[DataProvider('getValidationStepStatusForTicketProvider')]
    public function testgetValidationStepStatusForTicket(int $mininal_required_validation_percent, array $validation_states, int $expected_status)
    {
        // single validation step with 100% required
        [$ticket, $validationstep] = $this->createValidationStepWithValidations($mininal_required_validation_percent, $validation_states);

        $result_status = \TicketValidation::getValidationStepStatusForTicket($ticket->getId(), $validationstep->getID());
        $this->assertEquals(
            $expected_status,
            $result_status,
            $this->getFailureMessage($expected_status, $result_status)
        );
    }

    public function testgetValidationStepAchievementsThrowsExceptionOnTicketWithoutValidation()
    {
        // without any validation step : exception (thrown by getValidationsForTicketAndValidationStep())
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, []);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Get validation step status for a ticket without any validation step');
        \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
    }

    public function testgetValidationStepAchievementsOnSingleValidation(): void
    {
        // accepted
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(100, $achievements[CommonITILValidation::ACCEPTED]);

        // refused
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::REFUSED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(100, $achievements[CommonITILValidation::REFUSED]);

        // waiting
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::WAITING]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(100, $achievements[CommonITILValidation::WAITING]);
    }

    public function testgetValidationStepAchievementsOnMultipleValidation(): void
    {
        // 2 validations with same status
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(100, $achievements[CommonITILValidation::ACCEPTED]);
        // test sum of % is 100
        $this->assertEquals(100, array_sum($achievements));

        // multiple validations with same status
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(100, $achievements[CommonITILValidation::WAITING]);
        $this->assertEquals(100, array_sum($achievements));

        // multiple validations with different status
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::WAITING, CommonITILValidation::REFUSED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(50, $achievements[CommonITILValidation::WAITING]);
        $this->assertEquals(50, $achievements[CommonITILValidation::REFUSED]);
        $this->assertEquals(100, array_sum($achievements));

        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::WAITING, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(33, $achievements[CommonITILValidation::REFUSED]);
        $this->assertEquals(34, $achievements[CommonITILValidation::ACCEPTED]);
        $this->assertEquals(33, $achievements[CommonITILValidation::WAITING]);

        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(67, $achievements[CommonITILValidation::REFUSED]);
        $this->assertEquals(33, $achievements[CommonITILValidation::ACCEPTED]);
        $this->assertEquals(0, $achievements[CommonITILValidation::WAITING]);

        // 4 validations with different status
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(50, $achievements[CommonITILValidation::WAITING]);
        $this->assertEquals(25, $achievements[CommonITILValidation::REFUSED]);
        $this->assertEquals(25, $achievements[CommonITILValidation::ACCEPTED]);

        // 5 validations with different status
        [$ticket, $validation_step] = $this->createValidationStepWithValidations(100, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED]);
        $achievements = \TicketValidation::getValidationStepAchievements($ticket->getID(), $validation_step->getID());
        $this->assertEquals(60, $achievements[CommonITILValidation::REFUSED]);
        $this->assertEquals(40, $achievements[CommonITILValidation::ACCEPTED]);
        $this->assertEquals(100, array_sum($achievements));
    }

    // -- providers
    public static function getValidationStepStatusForTicketProvider()
    {
        /**
         * Array with
         * 0 : mininal_required_validation_percent
         * 1 : [Validation status, ...]
         * 2 : expected ValidationStep status
         */
        return [
            // basic checks : with single validation : step = single validation status

            // ---- Refused validation step
            // 1 validation
            [100, [CommonITILValidation::REFUSED], CommonITILValidation::REFUSED],
            // 2 validations - 50 %
            [100, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::REFUSED],
            [100, [CommonITILValidation::REFUSED, CommonITILValidation::WAITING], CommonITILValidation::REFUSED],
            [100, [CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED], CommonITILValidation::REFUSED],
            [50, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::REFUSED],
            [80, [CommonITILValidation::REFUSED, CommonITILValidation::WAITING], CommonITILValidation::REFUSED],
            [80, [CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED], CommonITILValidation::REFUSED],
            // 3 validations - 100 %
            [100, [CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED], CommonITILValidation::REFUSED],
            [100, [CommonITILValidation::REFUSED, CommonITILValidation::WAITING, CommonITILValidation::WAITING], CommonITILValidation::REFUSED],
            // 3 validations - x/3 limit
            [67, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED], CommonITILValidation::REFUSED],
            [34, [CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::ACCEPTED], CommonITILValidation::REFUSED], // 10

            // --- Accepted validation step
            [100, [CommonITILValidation::ACCEPTED], CommonITILValidation::ACCEPTED],
            // 2 validations
            [100, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED], CommonITILValidation::ACCEPTED],
            [75, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED], CommonITILValidation::ACCEPTED],
            [50, [CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],
            [50, [CommonITILValidation::ACCEPTED, CommonITILValidation::WAITING], CommonITILValidation::ACCEPTED],
            [40, [CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],
            // 3 validations
            [100, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED], CommonITILValidation::ACCEPTED], // 17
            [66, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED, CommonITILValidation::WAITING], CommonITILValidation::ACCEPTED],
            [33, [CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],
            [20, [CommonITILValidation::ACCEPTED, CommonITILValidation::WAITING, CommonITILValidation::WAITING], CommonITILValidation::ACCEPTED],

            // --- Waiting validation step
            [100, [CommonITILValidation::WAITING], CommonITILValidation::WAITING],

            [100, [CommonITILValidation::WAITING, CommonITILValidation::WAITING], CommonITILValidation::WAITING],
            [100, [CommonITILValidation::WAITING, CommonITILValidation::ACCEPTED], CommonITILValidation::WAITING],
            [40, [CommonITILValidation::WAITING, CommonITILValidation::REFUSED], CommonITILValidation::WAITING],

            [100, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING], CommonITILValidation::WAITING],
            [75, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING], CommonITILValidation::WAITING],
            [66, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::ACCEPTED], CommonITILValidation::WAITING],
            [66, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::REFUSED], CommonITILValidation::WAITING],

            // edge cases 0% required
            [0, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],

            // 5 validations -
            [20, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],
            [20, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::REFUSED], CommonITILValidation::WAITING],
            [20, [CommonITILValidation::WAITING, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::WAITING],
            [40, [CommonITILValidation::WAITING, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::REFUSED],
            [40, [CommonITILValidation::WAITING, CommonITILValidation::WAITING, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::WAITING],
            [40, [CommonITILValidation::ACCEPTED, CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED],
        ];
    }

    // --- helpers
    private function getInitialDefault(): \ValidationStep
    {
        return getItemByTypeName(\ValidationStep::class, 'Validation');
    }

    /**
     * /**
     * @return array{\Ticket, \ValidationStep}
     */
    private function createValidationStepWithValidations(int $mininal_required_validation_percent, array $validations_statuses): array
    {
        // create ticket
        /** @var \Ticket $ticket */
        $ticket = $this->createItem(\Ticket::class, ['name' => __METHOD__,
            'content' => __METHOD__,
        ]);

        // create validation step
        $validation_step = $this->createValidationStep($mininal_required_validation_percent);

        foreach ($validations_statuses as $status) {
            // ticket validation can only be created with Waiting status
            $validation = $this->createItem(\TicketValidation::class, $this->getValidTicketData($ticket, $validation_step, CommonITILValidation::WAITING));
            // update status if needed
            if ($status != CommonITILValidation::WAITING) {
                assert($validation->update(['status' => $status] + $validation->fields));
            }
        }

        return [$ticket, $validation_step];
    }

    /**
     * @param int $mininal_required_validation_percent
     */
    private function createValidationStep(int $mininal_required_validation_percent): ValidationStep
    {
        $data = $this->getValidData();
        $data['minimal_required_validation_percent'] = $mininal_required_validation_percent;

        return $this->createItem(\ValidationStep::class, $data);
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

    public function getValidTicketData(\Ticket $ticket, ValidationStep $validation_step, int $validation_status): array
    {
        return [
            'tickets_id' => $ticket->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => getItemByTypeName(\User::class, TU_USER)->getID(),
            'validationsteps_id' => $validation_step->getID(),
            'status' => $validation_status,
        ];
    }

    private function getFailureMessage(int $expected_status, int $result): string
    {
        $status_to_label = function (int $status) {
            $states = [
                CommonITILValidation::WAITING => 'WAITING',
                CommonITILValidation::ACCEPTED => 'ACCEPTED',
                CommonITILValidation::REFUSED => 'REFUSED',
            ];
            return $states[$status] ?? throw new \InvalidArgumentException("Expected status " . var_export($status, true));
        };

        return 'Unexpected validation step status. Expected : ' . $status_to_label($expected_status) . ' - Result : ' . $status_to_label($result);
    }
}
