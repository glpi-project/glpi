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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\OLATTRField;
use Glpi\Form\Destination\CommonITILField\OLATTRFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use OLA;
use Override;
use SLM;
use Ticket;
use TicketTemplatePredefinedField;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

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

    private function checkOLATTRFieldConfiguration(
        Form $form,
        OLATTRFieldConfig $config,
        int $expected_olas_ttr_id
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
            [],
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check ola_id_ttr field
        $this->assertEquals($expected_olas_ttr_id, $ticket->fields['olas_id_ttr']);

        // Return the created ticket to be able to check other fields
        return $ticket;
    }

    private function createAndGetFormWithTicketDestination(): Form
    {
        $builder = new FormBuilder();
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
