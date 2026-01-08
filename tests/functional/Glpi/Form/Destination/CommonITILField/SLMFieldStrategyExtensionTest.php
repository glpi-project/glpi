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
use Glpi\Form\Destination\CommonITILField\SLATTOField;
use Glpi\Form\Destination\CommonITILField\SLATTOFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
use Glpi\Form\Destination\FormDestinationManager;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\SpecificAnswerSLMStrategy;
use SLA;
use SLM;
use Ticket;

/**
 * Tests for the SLM field strategy extension mechanism.
 *
 * These tests use the SpecificAnswerSLMStrategy from the tester plugin
 * to validate that plugins can register custom strategies for SLA/OLA fields.
 */
final class SLMFieldStrategyExtensionTest extends DbTestCase
{
    use FormTesterTrait;

    public function testPluginSLMFieldStrategyIsRegistered(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $manager = FormDestinationManager::getInstance();
        $strategies = $manager->getSLMFieldStrategies();

        // Core strategies should be present
        $this->assertArrayHasKey(SLMFieldStrategy::FROM_TEMPLATE->value, $strategies);
        $this->assertArrayHasKey(SLMFieldStrategy::SPECIFIC_VALUE->value, $strategies);

        // Plugin strategy should be present
        $this->assertArrayHasKey(SpecificAnswerSLMStrategy::KEY, $strategies);
        $this->assertInstanceOf(SpecificAnswerSLMStrategy::class, $strategies[SpecificAnswerSLMStrategy::KEY]);
    }

    public function testGetSLMFieldStrategy(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $manager = FormDestinationManager::getInstance();

        // Core strategy
        $strategy = $manager->getSLMFieldStrategy(SLMFieldStrategy::FROM_TEMPLATE->value);
        $this->assertInstanceOf(SLMFieldStrategy::class, $strategy);
        $this->assertSame(SLMFieldStrategy::FROM_TEMPLATE, $strategy);

        // Plugin strategy
        $strategy = $manager->getSLMFieldStrategy(SpecificAnswerSLMStrategy::KEY);
        $this->assertInstanceOf(SpecificAnswerSLMStrategy::class, $strategy);

        // Unknown strategy
        $strategy = $manager->getSLMFieldStrategy('unknown_strategy');
        $this->assertNull($strategy);
    }

    public function testGetStrategiesForDropdownIncludesPluginStrategies(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $field = new SLATTOField();
        $values = $field->getStrategiesForDropdown();

        // Should contain core strategies
        $this->assertArrayHasKey(SLMFieldStrategy::FROM_TEMPLATE->value, $values);
        $this->assertArrayHasKey(SLMFieldStrategy::SPECIFIC_VALUE->value, $values);

        // Should contain plugin strategy
        $this->assertArrayHasKey(SpecificAnswerSLMStrategy::KEY, $values);
    }

    public function testStrategiesAreSortedByWeight(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $field = new SLATTOField();
        $values = $field->getStrategiesForDropdown();
        $keys = array_keys($values);

        // Core strategies have weight 10 and 20, plugin strategy has 100
        // So plugin strategy should be last
        $this->assertEquals(SpecificAnswerSLMStrategy::KEY, end($keys));
    }

    public function testSLMFieldConfigWithPluginStrategy(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $config = new SLATTOFieldConfig(
            strategy: SpecificAnswerSLMStrategy::KEY,
            specific_slm_id: null,
            extra_data: [SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID => 42],
        );

        // Check strategy key
        $this->assertEquals(SpecificAnswerSLMStrategy::KEY, $config->getStrategyKey());

        // Check strategy instance is resolved
        $strategy = $config->getStrategy();
        $this->assertInstanceOf(SpecificAnswerSLMStrategy::class, $strategy);

        // Check extra data
        $this->assertEquals(42, $config->getExtraDataValue(SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID));
        $this->assertNull($config->getExtraDataValue('non_existent'));
        $this->assertEquals('default', $config->getExtraDataValue('non_existent', 'default'));
    }

    public function testSLMFieldConfigWithCoreStrategy(): void
    {
        $config = new SLATTOFieldConfig(
            strategy: SLMFieldStrategy::SPECIFIC_VALUE,
            specific_slm_id: 123,
        );

        // Check strategy key
        $this->assertEquals('specific_value', $config->getStrategyKey());

        // Check strategy instance
        $strategy = $config->getStrategy();
        $this->assertSame(SLMFieldStrategy::SPECIFIC_VALUE, $strategy);
    }

    public function testJsonSerializationWithPluginStrategy(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $config = new SLATTOFieldConfig(
            strategy: SpecificAnswerSLMStrategy::KEY,
            specific_slm_id: null,
            extra_data: [SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID => 42],
        );

        $serialized = $config->jsonSerialize();

        $this->assertEquals(SpecificAnswerSLMStrategy::KEY, $serialized[SLMFieldConfig::STRATEGY]);
        $this->assertNull($serialized[SLMFieldConfig::SLM_ID]);
        $this->assertEquals(
            [SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID => 42],
            $serialized[SLMFieldConfig::EXTRA_DATA]
        );
    }

    public function testJsonDeserializationWithPluginStrategy(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        $data = [
            SLMFieldConfig::STRATEGY => SpecificAnswerSLMStrategy::KEY,
            SLMFieldConfig::SLM_ID => null,
            SLMFieldConfig::EXTRA_DATA => [SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID => 42],
        ];

        $config = SLATTOFieldConfig::jsonDeserialize($data);

        $this->assertEquals(SpecificAnswerSLMStrategy::KEY, $config->getStrategyKey());
        $this->assertInstanceOf(SpecificAnswerSLMStrategy::class, $config->getStrategy());
        $this->assertEquals(42, $config->getExtraDataValue(SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID));
    }

    public function testJsonDeserializationWithUnknownStrategyFallsBack(): void
    {
        $data = [
            SLMFieldConfig::STRATEGY => 'unknown_strategy',
            SLMFieldConfig::SLM_ID => null,
        ];

        $config = SLATTOFieldConfig::jsonDeserialize($data);

        // Should fallback to FROM_TEMPLATE
        $this->assertSame(SLMFieldStrategy::FROM_TEMPLATE, $config->getStrategy());
    }

    public function testPluginStrategyCanComputeSLMIDFromAnswer(): void
    {
        $this->login();
        $this->activatePlugin('tester');

        // Create a test SLA
        $sla = $this->createItem(
            SLA::class,
            [
                'name'            => 'Test SLA for plugin strategy',
                'type'            => SLM::TTO,
                'number_time'     => 1,
                'definition_time' => 'hour',
            ]
        );

        // Create form with a short text question
        $builder = new FormBuilder();
        $builder->addQuestion('SLA ID', QuestionTypeShortText::class);
        $form = $this->createForm($builder);

        // Get the question ID
        $questions = $form->getQuestions();
        $question = current($questions);
        $question_id = $question->getID();

        // Create config with plugin strategy pointing to the question
        $config = new SLATTOFieldConfig(
            strategy: SpecificAnswerSLMStrategy::KEY,
            specific_slm_id: null,
            extra_data: [SpecificAnswerSLMStrategy::EXTRA_KEY_QUESTION_ID => $question_id],
        );

        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [SLATTOField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // Submit form with the SLA ID as answer
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            [
                $this->getQuestionId($form, 'SLA ID') => (string) $sla->getID(),
            ],
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);
        $this->assertInstanceOf(Ticket::class, $ticket);

        // Check sla_id_tto field was set by the plugin strategy
        $this->assertEquals($sla->getID(), $ticket->fields['slas_id_tto']);
    }

    private function activatePlugin(string $plugin_name): void
    {
        $plugin = new \Plugin();
        $plugin->checkStates(true);
        $plugin->getFromDBbyDir($plugin_name);

        if (!$plugin->isActivated($plugin_name)) {
            $plugin->activate($plugin->getID());
            $plugin->checkStates(true);
            $plugin->load($plugin_name);
        }
    }
}
