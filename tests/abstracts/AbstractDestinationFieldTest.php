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

use DbTestCase;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\Migration\FormMigration;
use Glpi\Migration\PluginMigrationResult;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractDestinationFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public static function setUpBeforeClass(): void
    {
        global $DB;

        parent::setUpBeforeClass();

        $queries = $DB->getQueriesFromFile(sprintf('%s/tests/glpi-formcreator-migration-data.sql', GLPI_ROOT));
        foreach ($queries as $query) {
            $DB->doQuery($query);
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $DB;

        $tables = $DB->listTables('glpi\_plugin\_formcreator\_%');
        foreach ($tables as $table) {
            $DB->dropTable($table['TABLE_NAME']);
        }

        parent::tearDownAfterClass();
    }

    abstract public static function provideConvertFieldConfigFromFormCreator(): iterable;

    #[DataProvider('provideConvertFieldConfigFromFormCreator')]
    public function testConvertFieldConfigFromFormCreator(
        string $field_key,
        array $fields_to_set,
        callable|JsonFieldInterface $field_config
    ): void {
        global $DB;

        if (!empty($fields_to_set)) {
            // Compute some values
            foreach ($fields_to_set as $key => $value) {
                if (is_callable($value)) {
                    $fields_to_set[$key] = $value($this);
                }
            }

            // Update target fields
            $this->assertNotFalse($DB->update(
                'glpi_plugin_formcreator_targettickets',
                $fields_to_set,
                ['glpi_plugin_formcreator_forms.name' => 'Test form migration for targets'],
                [
                    'JOIN' => [
                        'glpi_plugin_formcreator_forms' => [
                            'ON' => [
                                'glpi_plugin_formcreator_targettickets' => 'plugin_formcreator_forms_id',
                                'glpi_plugin_formcreator_forms'         => 'id',
                            ],
                        ],
                    ],
                ]
            ));
        }

        // Run migration
        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        /** @var Form $form */
        $form = getItemByTypeName(Form::class, 'Test form migration for targets');
        $destination = current(array_filter(
            $form->getDestinations(),
            fn($destination) => $destination->getConcreteDestinationItem() instanceof FormDestinationTicket
        ));

        $this->assertNotFalse($destination);

        /** @var FormDestinationTicket $itil_destination */
        $itil_destination = $destination->getConcreteDestinationItem();
        $itil_destination->getConfigurableFieldByKey($field_key)
        ->getConfig($form, $destination->getConfig())
        ->jsonSerialize();
        $this->assertEquals(
            (is_callable($field_config) ? $field_config($migration, $form) : $field_config)->jsonSerialize(),
            $itil_destination->getConfigurableFieldByKey($field_key)
                ->getConfig($form, $destination->getConfig())
                ->jsonSerialize()
        );
    }
}
