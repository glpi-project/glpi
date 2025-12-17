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
use Glpi\Form\Destination\CommonITILField\OLATTOField;
use Glpi\Form\Destination\CommonITILField\OLATTOFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
use Glpi\Form\Form;
use Glpi\Tests\AbstractDestinationFieldTest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Glpi\Tests\Glpi\SLMTrait;
use OLA;
use Override;
use SLM;
use Ticket;
use TicketTemplatePredefinedField;

final class OLATTOFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;
    use SLMTrait;

    public function testDefaultTemplateWithPredefinedField(): void
    {
        $this->login();
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );

        ['ola' => $created_ola_tto] = $this->createOLA();

        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 190,
                'value'              => $created_ola_tto->getID(),
            ]
        );

        $this->checkOLATTOFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::FROM_TEMPLATE,
            ),
            expected_olas_tto_id: $created_ola_tto->getID()
        );
    }

    public function testSpecificOLATTO(): void
    {
        $this->login();
        ['ola' => $created_ola_tto] = $this->createOLA();

        $this->checkOLATTOFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_ola_tto->getID()
            ),
            expected_olas_tto_id: $created_ola_tto->getID()
        );
    }

    public function testSpecificOLATTOWithDefaultTemplateWithPredefinedField(): void
    {
        $this->login();
        $default_template = (new Ticket())->getITILTemplateToUse(
            entities_id: $_SESSION["glpiactive_entity"]
        );

        ['ola' => $created_ola_tto] = $this->createOLA();
        ['ola' => $created_ola_tto_for_template] = $this->createOLA();

        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $default_template->getID(),
                'num'                => 190,
                'value'              => $created_ola_tto_for_template->getID(),
            ]
        );

        $this->checkOLATTOFieldConfiguration(
            form: $this->createAndGetFormWithTicketDestination(),
            config: new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_ola_tto->getID()
            ),
            expected_olas_tto_id: $created_ola_tto->getID()
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'OLA from template or none' => [
            'field_key'     => OLATTOField::getKey(),
            'fields_to_set' => [
                'sla_rule' => 1, // PluginFormcreatorAbstractItilTarget::SLA_RULE_NONE
            ],
            'field_config' => new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::FROM_TEMPLATE
            ),
        ];

        yield 'Specific OLA' => [
            'field_key'     => OLATTOField::getKey(),
            'fields_to_set' => [
                'sla_rule'         => 2, // PluginFormcreatorAbstractItilTarget::SLA_RULE_SPECIFIC
                'ola_question_tto' => fn(AbstractDestinationFieldTest $context) => $context->createItem(
                    OLA::class,
                    [
                        'name'            => '_test_ola_tto',
                        'type'            => SLM::TTO,
                        'number_time'     => 1,
                        'definition_time' => 'hour',
                        'groups_id' => getItemByTypeName(\Group::class, '_test_group_1', true),
                        'slms_id' => getItemByTypeName(SLM::class, 'Test SLM', true), // should be created by the test case
                    ]
                )->getID(),
            ],
            'field_config' => fn($migration, $form) => new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: getItemByTypeName(OLA::class, '_test_ola_tto', true)
            ),
        ];
    }

    private function checkOLATTOFieldConfiguration(
        Form $form,
        OLATTOFieldConfig $config,
        int $expected_olas_tto_id
    ): Ticket {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [OLATTOField::getKey() => $config->jsonSerialize()]],
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
        /** @var Ticket $ticket */
        $ticket = current($created_items);

        // Check ola_id_tto field
        $ticket_tto_data = $ticket->getOlasTTOData()[0] ?? throw new \Exception('Ola TTO not found');
        $this->assertEquals($expected_olas_tto_id, $ticket_tto_data['olas_id']);

        // Return the created ticket to be able to check other fields
        return $ticket;
    }

    private function createAndGetFormWithTicketDestination(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }
}
