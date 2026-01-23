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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\OLATTRField;
use Glpi\Form\Destination\CommonITILField\OLATTRFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Glpi\Tests\AbstractDestinationFieldTest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use OLA;
use Override;
use SLM;
use Ticket;
use TicketTemplatePredefinedField;

final class OLATTRFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testDefaultTemplateWithPredefinedField(): void
    {
        $this->login();
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );

        $created_ola_ttr = $this->createItem(
            OLA::class,
            [
                'name'            => 'OLATTR',
                'type'            => SLM::TTR,
                'number_time'     => 1,
                'definition_time' => 'hour',
            ]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 191,
                'value'              => $created_ola_ttr->getID(),
            ]
        );

        $this->checkOLATTRFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::FROM_TEMPLATE,
            ),
            expected_olas_ttr_id: $created_ola_ttr->getID()
        );
    }

    public function testSpecificOLATTR(): void
    {
        $this->login();
        $created_ola_ttr = $this->createItem(
            OLA::class,
            [
                'name'            => 'OLATTR',
                'type'            => SLM::TTR,
                'number_time'     => 1,
                'definition_time' => 'hour',
            ]
        );

        $this->checkOLATTRFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_ola_ttr->getID()
            ),
            expected_olas_ttr_id: $created_ola_ttr->getID()
        );
    }

    public function testSpecificOLATTRWithDefaultTemplateWithPredefinedField(): void
    {
        $this->login();
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );

        $created_ola_ttr = $this->createItem(
            OLA::class,
            [
                'name'            => 'OLATTR',
                'type'            => SLM::TTR,
                'number_time'     => 1,
                'definition_time' => 'hour',
            ]
        );
        $created_ola_ttr_for_template = $this->createItem(
            OLA::class,
            [
                'name'            => 'OLATTR',
                'type'            => SLM::TTR,
                'number_time'     => 1,
                'definition_time' => 'hour',
            ]
        );
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 191,
                'value'              => $created_ola_ttr_for_template->getID(),
            ]
        );

        $this->checkOLATTRFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_ola_ttr->getID()
            ),
            expected_olas_ttr_id: $created_ola_ttr->getID()
        );
    }

    public function testSpecificDateAnswer(): void
    {
        $this->login('normal');

        $this->setCurrentTime('2026-01-01 10:00:00');

        $form = $this->createAndGetFormWithTicketDestination();
        $this->checkOLATTRFieldConfiguration(
            form: $form,
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_DATE_ANSWER,
                question_id: $this->getQuestionId($form, 'Date Question')
            ),
            answers: [
                $this->getQuestionId($form, 'Date Question') => '2026-01-02',
            ],
            expected_tto_date: '2026-01-02 00:00:00'
        );
    }

    public function testSpecificDateTimeAnswer(): void
    {
        $this->login('normal');

        $this->setCurrentTime('2026-01-01 10:00:00');

        $form = $this->createAndGetFormWithTicketDestination();
        $this->checkOLATTRFieldConfiguration(
            form: $form,
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_DATE_ANSWER,
                question_id: $this->getQuestionId($form, 'Date and Time Question')
            ),
            answers: [
                $this->getQuestionId($form, 'Date and Time Question') => '2026-01-02 12:34:56',
            ],
            expected_tto_date: '2026-01-02 12:34:56'
        );
    }

    public function testComputedDateFromFormSubmission(): void
    {
        $this->login('normal');

        $this->setCurrentTime('2026-01-01 10:00:00');

        $form = $this->createAndGetFormWithTicketDestination();
        $this->checkOLATTRFieldConfiguration(
            form: $form,
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::COMPUTED_DATE_FROM_FORM_SUBMISSION,
                time_offset: 2,
                time_definition: 'day'
            ),
            expected_tto_date: '2026-01-03 10:00:00'
        );
    }

    public function testComputedDateFromSpecificDateAnswer(): void
    {
        $this->login('normal');

        $this->setCurrentTime('2026-01-01 10:00:00');

        $form = $this->createAndGetFormWithTicketDestination();
        $this->checkOLATTRFieldConfiguration(
            form: $form,
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER,
                question_id: $this->getQuestionId($form, 'Date Question'),
                time_offset: 3,
                time_definition: 'day'
            ),
            answers: [
                $this->getQuestionId($form, 'Date Question') => '2026-01-05',
            ],
            expected_tto_date: '2026-01-08 00:00:00'
        );
    }

    public function testComputedDateFromSpecificDateTimeAnswer(): void
    {
        $this->login('normal');

        $this->setCurrentTime('2026-01-01 10:00:00');

        $form = $this->createAndGetFormWithTicketDestination();
        $this->checkOLATTRFieldConfiguration(
            form: $form,
            config: new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER,
                question_id: $this->getQuestionId($form, 'Date and Time Question'),
                time_offset: 4,
                time_definition: 'day'
            ),
            answers: [
                $this->getQuestionId($form, 'Date and Time Question') => '2026-01-05 15:30:00',
            ],
            expected_tto_date: '2026-01-09 15:30:00'
        );
    }

    private function checkOLATTRFieldConfiguration(
        Form $form,
        OLATTRFieldConfig $config,
        array $answers = [],
        int $expected_olas_ttr_id = 0,
        ?string $expected_tto_date = null,
    ): Ticket {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [OLATTRField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $answers,
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check ola_id_ttr field
        $this->assertEquals($expected_olas_ttr_id, $ticket->fields['olas_id_ttr']);

        // Check internal_time_to_resolve field
        if ($expected_tto_date !== null) {
            $this->assertEquals($expected_tto_date, $ticket->fields['internal_time_to_resolve']);
        }

        // Return the created ticket to be able to check other fields
        return $ticket;
    }

    private function createAndGetFormWithTicketDestination(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: 'Date Question',
            type: QuestionTypeDateTime::class,
            extra_data: json_encode(new QuestionTypeDateTimeExtraDataConfig(
                is_date_enabled: true,
                is_time_enabled: false
            ))
        );
        $builder->addQuestion(
            name: 'Date and Time Question',
            type: QuestionTypeDateTime::class,
            extra_data: json_encode(new QuestionTypeDateTimeExtraDataConfig(
                is_date_enabled: true,
                is_time_enabled: true
            ))
        );
        return $this->createForm($builder);
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'SLA from template or none' => [
            'field_key'     => OLATTRField::getKey(),
            'fields_to_set' => [
                'sla_rule' => 1, // PluginFormcreatorAbstractItilTarget::SLA_RULE_NONE
            ],
            'field_config' => new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::FROM_TEMPLATE
            ),
        ];

        yield 'Specific SLA' => [
            'field_key'     => OLATTRField::getKey(),
            'fields_to_set' => [
                'sla_rule'         => 2, // PluginFormcreatorAbstractItilTarget::SLA_RULE_SPECIFIC
                'ola_question_ttr' => fn(AbstractDestinationFieldTest $context) => $context->createItem(
                    OLA::class,
                    [
                        'name'            => '_test_ola_ttr',
                        'type'            => SLM::TTR,
                        'number_time'     => 1,
                        'definition_time' => 'hour',
                    ]
                )->getID(),
            ],
            'field_config' => fn($migration, $form) => new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: getItemByTypeName(OLA::class, '_test_ola_ttr', true)
            ),
        ];
    }
}
