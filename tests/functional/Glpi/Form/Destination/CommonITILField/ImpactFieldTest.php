<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use Glpi\Form\Destination\CommonITILField\ImpactField;
use Glpi\Form\Destination\CommonITILField\ImpactFieldConfig;
use Glpi\Form\Destination\CommonITILField\ImpactFieldStrategy;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeImpact;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Tests\AbstractDestinationFieldTest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;

final class ImpactFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testImpactFromTemplateDefault(): void
    {
        $default_impact = 3; // (medium)

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::FROM_TEMPLATE
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        // The default template does not define an impact, it should
        // thus be GLPI's default value: 3 (medium)
        $this->assertEquals($default_impact, $ticket->fields['impact']);
    }

    public function testImpactFromTemplateSpecific(): void
    {
        $very_high_impact = 5;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setTemplatePredefinedImpact($very_high_impact);
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::FROM_TEMPLATE
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($very_high_impact, $ticket->fields['impact']);
    }

    public function testSpecificImpact(): void
    {
        global $CFG_GLPI;

        // Allow all impact levels
        $CFG_GLPI['impact_mask'] = array_reduce(range(1, 5), fn($mask, $level) => $mask | (1 << $level), 0);

        $high_impact = 4;
        $medium_impact = 3;
        $low_impact = 2;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            strategy: ImpactFieldStrategy::SPECIFIC_VALUE,
            specific_impact_value: $high_impact,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($high_impact, $ticket->fields['impact']);

        // Allow only the three first impact levels
        $CFG_GLPI['impact_mask'] = (1 << 1) | (1 << 2) | (1 << 3);

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        // The high impact is not allowed, the default value should be used
        $this->assertEquals($medium_impact, $ticket->fields['impact']);

        $this->setImpactConfig($form, new ImpactFieldConfig(
            strategy: ImpactFieldStrategy::SPECIFIC_VALUE,
            specific_impact_value: $low_impact,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($low_impact, $ticket->fields['impact']);
    }

    public function testImpactFromFirstQuestion(): void
    {
        $low_impact = 2;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            strategy: ImpactFieldStrategy::SPECIFIC_ANSWER,
            specific_question_id: $this->getQuestionId($form, "Impact 1"),
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 1" => $low_impact,
            "Impact 2" => 4, // Another impact in another question as a "control" subject
        ]);

        $this->assertEquals($low_impact, $ticket->fields['impact']);
    }

    public function testImpactFromSecondQuestion(): void
    {
        $very_low_impact = 1;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            strategy: ImpactFieldStrategy::SPECIFIC_ANSWER,
            specific_question_id: $this->getQuestionId($form, "Impact 2"),
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 1" => 4, // Another impact in another question as a "control" subject
            "Impact 2" => $very_low_impact,
        ]);

        $this->assertEquals($very_low_impact, $ticket->fields['impact']);
    }

    public function testImpactFromLastValidQuestion(): void
    {
        $very_high_impact = 5;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 1" => 4, // Another impact in another question as a "control" subject
            "Impact 2" => $very_high_impact,
        ]);

        $this->assertEquals($very_high_impact, $ticket->fields['impact']);
    }

    public function testImpactFromLastValidQuestionWithOnlyFirstAnswer(): void
    {
        $very_high_impact = 5;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 1" => $very_high_impact,
        ]);

        $this->assertEquals($very_high_impact, $ticket->fields['impact']);
    }

    public function testImpactFromLastValidQuestionWithOnlySecondtAnswer(): void
    {
        $very_high_impact = 5;

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 2" => $very_high_impact,
        ]);

        $this->assertEquals($very_high_impact, $ticket->fields['impact']);
    }

    public function testImpactFromLastValidQuestionWithoutAnswers(): void
    {
        $default_impact = 3; // (medium)

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($default_impact, $ticket->fields['impact']);
    }

    public function testImpactFromLastValidQuestionWithoutAnswersUsingTemplate(): void
    {
        $high_impact = 4; // (high)

        $form = $this->createAndGetFormWithTwoImpactQuestions();
        $this->setTemplatePredefinedImpact($high_impact);
        $this->setImpactConfig($form, new ImpactFieldConfig(
            ImpactFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($high_impact, $ticket->fields['impact']);
    }

    public function testDefaultConfigIsLastAnswer(): void
    {
        $low_impact = 2;

        $form = $this->createAndGetFormWithTwoImpactQuestions();

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 1" => 4, // Another impact in another question as a "control" subject
            "Impact 2" => $low_impact,
        ]);

        $this->assertEquals($low_impact, $ticket->fields['impact']);
    }

    public function testFractionalImpactAnswerIsIgnored(): void
    {
        $default_impact = 3; // (medium)

        $form = $this->createAndGetFormWithTwoImpactQuestions();

        // Answers are saved without validation here, as a crafted request would be
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Impact 2" => "4.9",
        ]);

        $this->assertEquals($default_impact, $ticket->fields['impact']);
    }

    public function testPriorityIsComputedFromUrgencyAndImpactAnswers(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Urgency", QuestionTypeUrgency::class);
        $builder->addQuestion("Impact", QuestionTypeImpact::class);
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency" => 2,
            "Impact"  => 5,
        ]);

        $this->assertEquals(5, $ticket->fields['impact']);
        $this->assertEquals(
            Ticket::computePriority(2, 5),
            $ticket->fields['priority']
        );
    }

    public function testImpactIsAppliedToChange(): void
    {
        $form = $this->createAndGetFormWithImpactQuestion(FormDestinationChange::class);

        $change = $this->sendFormAndGetCreatedChange($form, [
            "Impact" => 4,
        ]);

        $this->assertEquals(4, $change->fields['impact']);
    }

    public function testImpactIsAppliedToProblem(): void
    {
        $form = $this->createAndGetFormWithImpactQuestion(FormDestinationProblem::class);

        $problem = $this->sendFormAndGetCreatedProblem($form, [
            "Impact" => 1,
        ]);

        $this->assertEquals(1, $problem->fields['impact']);
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        // Formcreator had no impact rule, migrated forms use the default config
        yield 'Default config' => [
            'field_key'     => ImpactField::getKey(),
            'fields_to_set' => [],
            'field_config'  => new ImpactFieldConfig(
                strategy: ImpactFieldStrategy::LAST_VALID_ANSWER
            ),
        ];
    }

    private function setImpactConfig(
        Form $form,
        ImpactFieldConfig $config,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [ImpactField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );
    }

    private function setTemplatePredefinedImpact(int $impact): void
    {
        $template = getItemByTypeName(TicketTemplate::class, "Default");
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => $template->getId(),
            'num' => 11, // Impact
            'value' => $impact,
        ]);
    }

    private function createAndGetFormWithTwoImpactQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Impact 1", QuestionTypeImpact::class);
        $builder->addQuestion("Impact 2", QuestionTypeImpact::class);
        return $this->createForm($builder);
    }

    private function createAndGetFormWithImpactQuestion(string $destination_type): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Impact", QuestionTypeImpact::class);
        $builder->addDestination($destination_type, "My item to create");
        $builder->setShouldInitDestinations(false); // Prevent default ticket
        return $this->createForm($builder);
    }
}
