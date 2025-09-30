<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Form\Destination\CommonITILField\UrgencyField;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldConfig;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use TicketTemplate;
use TicketTemplatePredefinedField;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

final class UrgencyFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testUrgencyFromTemplateDefault(): void
    {
        $default_urgency = 3; // (medium)

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::FROM_TEMPLATE
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        // The default template does not define an urgency, it should
        // thus be GLPI's default value: 3 (medium)
        $this->assertEquals($default_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromTemplateSpecific(): void
    {
        $very_high_urgency = 5;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setTemplatePredefinedUrgency($very_high_urgency);
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::FROM_TEMPLATE
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($very_high_urgency, $ticket->fields['urgency']);
    }

    public function testSpecificUrgency(): void
    {
        global $CFG_GLPI;

        // Allow all urgency levels
        $CFG_GLPI['urgency_mask'] = array_reduce(range(1, 5), fn($mask, $level) => $mask | (1 << $level), 0);

        $high_urgency = 4;
        $medium_urgency = 3;
        $low_urgency = 2;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            strategy: UrgencyFieldStrategy::SPECIFIC_VALUE,
            specific_urgency_value: $high_urgency,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($high_urgency, $ticket->fields['urgency']);

        // Allow only the three first urgency levels
        $CFG_GLPI['urgency_mask'] = (1 << 1) | (1 << 2) | (1 << 3);

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        // The high urgency is not allowed, the default value should be used
        $this->assertEquals($medium_urgency, $ticket->fields['urgency']);

        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            strategy: UrgencyFieldStrategy::SPECIFIC_VALUE,
            specific_urgency_value: $low_urgency,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($low_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromFirstQuestion(): void
    {
        $low_urgency = 2;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
            specific_question_id: $this->getQuestionId($form, "Urgency 1"),
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 1" => $low_urgency,
            "Urgency 2" => 4, // Another urgency in another question as a "control" subject
        ]);

        $this->assertEquals($low_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromSecondQuestion(): void
    {
        $very_low_urgency = 1;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
            specific_question_id: $this->getQuestionId($form, "Urgency 2"),
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 1" => 4, // Another urgency in another question as a "control" subject
            "Urgency 2" => $very_low_urgency,
        ]);

        $this->assertEquals($very_low_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromLastValidQuestion(): void
    {
        $very_high_urgency = 5;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 1" => 4, // Another urgency in another question as a "control" subject
            "Urgency 2" => $very_high_urgency,
        ]);

        $this->assertEquals($very_high_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromLastValidQuestionWithOnlyFirstAnswer(): void
    {
        $very_high_urgency = 5;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 1" => $very_high_urgency,
        ]);

        $this->assertEquals($very_high_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromLastValidQuestionWithOnlySecondtAnswer(): void
    {
        $very_high_urgency = 5;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 2" => $very_high_urgency,
        ]);

        $this->assertEquals($very_high_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromLastValidQuestionWithoutAnswers(): void
    {
        $default_urgency = 3; // (medium)

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($default_urgency, $ticket->fields['urgency']);
    }

    public function testUrgencyFromLastValidQuestionWithoutAnswersUsingTemplate(): void
    {
        $high_urgency = 4; // (medium)

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();
        $this->setTemplatePredefinedUrgency($high_urgency);
        $this->setUrgencyConfig($form, new UrgencyFieldConfig(
            UrgencyFieldStrategy::LAST_VALID_ANSWER,
        ));

        $ticket = $this->sendFormAndGetCreatedTicket($form);

        $this->assertEquals($high_urgency, $ticket->fields['urgency']);
    }

    public function testDefaultConfigIsLastAnswer(): void
    {
        $low_urgency = 2;

        $form = $this->createAndGetFormWithTwoUrgencyQuestions();

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Urgency 1" => 4, // Another urgency in another question as a "control" subject
            "Urgency 2" => $low_urgency,
        ]);

        $this->assertEquals($low_urgency, $ticket->fields['urgency']);
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Urgency from template or Medium' => [
            'field_key'     => UrgencyField::getKey(),
            'fields_to_set' => [
                'urgency_rule' => 1, // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_NONE
            ],
            'field_config' => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::FROM_TEMPLATE
            ),
        ];

        yield 'Specific urgency' => [
            'field_key'     => UrgencyField::getKey(),
            'fields_to_set' => [
                'urgency_rule' => 2, // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_SPECIFIC
                'urgency_question' => 4, // High urgency
            ],
            'field_config' => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::SPECIFIC_VALUE,
                specific_urgency_value: 4 // High urgency
            ),
        ];

        yield 'Equals to the answer to the question' => [
            'field_key'     => UrgencyField::getKey(),
            'fields_to_set' => [
                'urgency_rule'     => 3, // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_ANSWER
                'urgency_question' => 79,
            ],
            'field_config' => fn($migration, $form) => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    79
                )['items_id'] ?? throw new \Exception("Question not found")
            ),
        ];
    }

    private function setUrgencyConfig(
        Form $form,
        UrgencyFieldConfig $config,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [UrgencyField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );
    }

    private function setTemplatePredefinedUrgency(int $urgency): void
    {
        $template = getItemByTypeName(TicketTemplate::class, "Default");
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => $template->getId(),
            'num' => 10, // Urgency
            'value' => $urgency,
        ]);
    }

    private function createAndGetFormWithTwoUrgencyQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Urgency 1", QuestionTypeUrgency::class);
        $builder->addQuestion("Urgency 2", QuestionTypeUrgency::class);
        return $this->createForm($builder);
    }
}
