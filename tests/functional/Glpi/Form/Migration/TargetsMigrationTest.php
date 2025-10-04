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

namespace tests\units\Glpi\Form\Migration;

use DbTestCase;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\AssigneeFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\EntityField;
use Glpi\Form\Destination\CommonITILField\EntityFieldConfig;
use Glpi\Form\Destination\CommonITILField\EntityFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILTaskField;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldStrategy;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsField;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldConfig;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldStrategyConfig;
use Glpi\Form\Destination\CommonITILField\LocationField;
use Glpi\Form\Destination\CommonITILField\LocationFieldConfig;
use Glpi\Form\Destination\CommonITILField\LocationFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\ObserverFieldConfig;
use Glpi\Form\Destination\CommonITILField\OLATTOField;
use Glpi\Form\Destination\CommonITILField\OLATTOFieldConfig;
use Glpi\Form\Destination\CommonITILField\OLATTRField;
use Glpi\Form\Destination\CommonITILField\OLATTRFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestSourceField;
use Glpi\Form\Destination\CommonITILField\RequestSourceFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestSourceFieldStrategy;
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldStrategy;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\SLATTOField;
use Glpi\Form\Destination\CommonITILField\SLATTOFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLATTRField;
use Glpi\Form\Destination\CommonITILField\SLATTRFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
use Glpi\Form\Destination\CommonITILField\StatusField;
use Glpi\Form\Destination\CommonITILField\TemplateField;
use Glpi\Form\Destination\CommonITILField\TemplateFieldConfig;
use Glpi\Form\Destination\CommonITILField\TemplateFieldStrategy;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\CommonITILField\UrgencyField;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldConfig;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ValidationField;
use Glpi\Form\Destination\CommonITILField\ValidationFieldConfig;
use Glpi\Form\Destination\CommonITILField\ValidationFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ValidationFieldStrategyConfig;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\Migration\FormMigration;
use Glpi\Migration\PluginMigrationResult;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\ExternalIDField;
use GlpiPlugin\Tester\Form\ExternalIDFieldConfig;
use GlpiPlugin\Tester\Form\ExternalIDFieldStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;

final class TargetsMigrationTest extends DbTestCase
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

    public static function provideFormMigrationWithTargets(): iterable
    {
        yield 'Test form migration for targets' => [
            'form_name'             => 'Test form migration for targets',
            'expected_destinations' => [
                [
                    'itemtype'     => FormDestinationTicket::class,
                    'name'         => 'Test form migration for target ticket',
                    'is_mandatory' => false,
                    'fields'       => [
                        TitleField::getKey()           => new SimpleValueConfig(
                            'Test form migration for target ticket'
                        ),
                        TemplateField::getKey()        => new TemplateFieldConfig(
                            strategy: TemplateFieldStrategy::DEFAULT_TEMPLATE
                        ),
                        ITILCategoryField::getKey()    => new ITILCategoryFieldConfig(
                            strategy: ITILCategoryFieldStrategy::LAST_VALID_ANSWER
                        ),
                        EntityField::getKey()          => new EntityFieldConfig(
                            strategy: EntityFieldStrategy::FORM_FILLER
                        ),
                        LocationField::getKey()        => new LocationFieldConfig(
                            strategy: LocationFieldStrategy::FROM_TEMPLATE
                        ),
                        AssociatedItemsField::getKey() => new AssociatedItemsFieldConfig(
                            strategies: [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
                        ),
                        ITILFollowupField::getKey()    => new ITILFollowupFieldConfig(
                            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
                        ),
                        RequestSourceField::getKey()   => new RequestSourceFieldConfig(
                            strategy: RequestSourceFieldStrategy::FROM_TEMPLATE
                        ),
                        ValidationField::getKey()      => new ValidationFieldConfig([
                            new ValidationFieldStrategyConfig(
                                strategy: ValidationFieldStrategy::NO_VALIDATION
                            ),
                        ]),
                        ITILTaskField::getKey()        => new ITILTaskFieldConfig(
                            strategy: ITILTaskFieldStrategy::NO_TASK
                        ),
                        RequesterField::getKey()       => new RequesterFieldConfig(
                            strategies: [ITILActorFieldStrategy::FORM_FILLER]
                        ),
                        ObserverField::getKey()        => new ObserverFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        AssigneeField::getKey()        => new AssigneeFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        SLATTOField::getKey()          => new SLATTOFieldConfig(
                            strategy: SLMFieldStrategy::FROM_TEMPLATE
                        ),
                        SLATTRField::getKey()          => new SLATTRFieldConfig(
                            strategy: SLMFieldStrategy::FROM_TEMPLATE
                        ),
                        OLATTOField::getKey()          => new OLATTOFieldConfig(
                            strategy: SLMFieldStrategy::FROM_TEMPLATE
                        ),
                        OLATTRField::getKey()          => new OLATTRFieldConfig(
                            strategy: SLMFieldStrategy::FROM_TEMPLATE
                        ),
                        UrgencyField::getKey()        => new UrgencyFieldConfig(
                            strategy: UrgencyFieldStrategy::FROM_TEMPLATE
                        ),
                        RequestTypeField::getKey()    => new RequestTypeFieldConfig(
                            strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                            specific_request_type: Ticket::INCIDENT_TYPE
                        ),
                        StatusField::getKey()         => new SimpleValueConfig(
                            StatusField::DEFAULT_STATUS
                        ),
                        LinkedITILObjectsField::getKey() => new LinkedITILObjectsFieldConfig([
                            new LinkedITILObjectsFieldStrategyConfig(
                                strategy: null, // No specific strategy for linked ITIL objects
                            ),
                        ]),

                        // Tester plugin fields
                        ExternalIDField::getKey() => new ExternalIDFieldConfig(
                            strategy: ExternalIDFieldStrategy::NO_EXTERNAL_ID
                        ),
                    ],
                ],
                [
                    'itemtype' => FormDestinationChange::class,
                    'name'     => 'Test form migration for target change',
                    'fields'   => [
                        TitleField::getKey()           => new SimpleValueConfig(
                            'Test form migration for target change'
                        ),
                        TemplateField::getKey()        => new TemplateFieldConfig(
                            strategy: TemplateFieldStrategy::DEFAULT_TEMPLATE
                        ),
                        ITILCategoryField::getKey()    => new ITILCategoryFieldConfig(
                            strategy: ITILCategoryFieldStrategy::LAST_VALID_ANSWER
                        ),
                        EntityField::getKey()          => new EntityFieldConfig(
                            strategy: EntityFieldStrategy::FORM_FILLER
                        ),
                        LocationField::getKey()        => new LocationFieldConfig(
                            strategy: LocationFieldStrategy::LAST_VALID_ANSWER
                        ),
                        AssociatedItemsField::getKey() => new AssociatedItemsFieldConfig(
                            strategies: [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
                        ),
                        ITILFollowupField::getKey()    => new ITILFollowupFieldConfig(
                            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
                        ),
                        RequestSourceField::getKey()   => new RequestSourceFieldConfig(
                            strategy: RequestSourceFieldStrategy::FROM_TEMPLATE
                        ),
                        ValidationField::getKey()      => new ValidationFieldConfig([
                            new ValidationFieldStrategyConfig(
                                strategy: ValidationFieldStrategy::NO_VALIDATION
                            ),
                        ]),
                        ITILTaskField::getKey()        => new ITILTaskFieldConfig(
                            strategy: ITILTaskFieldStrategy::NO_TASK
                        ),
                        RequesterField::getKey()       => new RequesterFieldConfig(
                            strategies: [ITILActorFieldStrategy::FORM_FILLER]
                        ),
                        ObserverField::getKey()        => new ObserverFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        AssigneeField::getKey()        => new AssigneeFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        UrgencyField::getKey()        => new UrgencyFieldConfig(
                            strategy: UrgencyFieldStrategy::FROM_TEMPLATE
                        ),
                        LinkedITILObjectsField::getKey() => new LinkedITILObjectsFieldConfig([
                            new LinkedITILObjectsFieldStrategyConfig(
                                strategy: null, // No specific strategy for linked ITIL objects
                            ),
                        ]),
                    ],
                ],
                [
                    'itemtype' => FormDestinationProblem::class,
                    'name'     => 'Test form migration for target problem',
                    'fields'   => [
                        TitleField::getKey()           => new SimpleValueConfig(
                            'Test form migration for target problem'
                        ),
                        TemplateField::getKey()        => new TemplateFieldConfig(
                            strategy: TemplateFieldStrategy::DEFAULT_TEMPLATE
                        ),
                        ITILCategoryField::getKey()    => new ITILCategoryFieldConfig(
                            strategy: ITILCategoryFieldStrategy::LAST_VALID_ANSWER
                        ),
                        EntityField::getKey()          => new EntityFieldConfig(
                            strategy: EntityFieldStrategy::FORM_FILLER
                        ),
                        LocationField::getKey()        => new LocationFieldConfig(
                            strategy: LocationFieldStrategy::LAST_VALID_ANSWER
                        ),
                        AssociatedItemsField::getKey() => new AssociatedItemsFieldConfig(
                            strategies: [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
                        ),
                        ITILFollowupField::getKey()    => new ITILFollowupFieldConfig(
                            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
                        ),
                        RequestSourceField::getKey()   => new RequestSourceFieldConfig(
                            strategy: RequestSourceFieldStrategy::FROM_TEMPLATE
                        ),
                        ValidationField::getKey()      => new ValidationFieldConfig([
                            new ValidationFieldStrategyConfig(
                                strategy: ValidationFieldStrategy::NO_VALIDATION
                            ),
                        ]),
                        ITILTaskField::getKey()        => new ITILTaskFieldConfig(
                            strategy: ITILTaskFieldStrategy::NO_TASK
                        ),
                        RequesterField::getKey()       => new RequesterFieldConfig(
                            strategies: [ITILActorFieldStrategy::FORM_FILLER]
                        ),
                        ObserverField::getKey()        => new ObserverFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        AssigneeField::getKey()        => new AssigneeFieldConfig(
                            strategies: [ITILActorFieldStrategy::FROM_TEMPLATE]
                        ),
                        UrgencyField::getKey()        => new UrgencyFieldConfig(
                            strategy: UrgencyFieldStrategy::FROM_TEMPLATE
                        ),
                        LinkedITILObjectsField::getKey() => new LinkedITILObjectsFieldConfig([
                            new LinkedITILObjectsFieldStrategyConfig(
                                strategy: null, // No specific strategy for linked ITIL objects
                            ),
                        ]),
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideFormMigrationWithTargets')]
    public function testAllDestinationFieldsAreChecked($form_name, $expected_destinations): void
    {
        foreach ($expected_destinations as $expected_destination) {
            $destination = new $expected_destination['itemtype']();
            foreach ($destination->getConfigurableFields() as $field) {
                if ($field instanceof ContentField) {
                    continue;
                }

                $this->assertArrayHasKey(
                    $field::getKey(),
                    $expected_destination['fields']
                );
            }
        }
    }

    #[DataProvider('provideFormMigrationWithTargets')]
    public function testFormMigrationWithTargets($form_name, $expected_destinations): void
    {
        global $DB;

        $migration = new FormMigration($DB, FormAccessControlManager::getInstance());
        $this->setPrivateProperty($migration, 'result', new PluginMigrationResult());
        $this->assertTrue($this->callPrivateMethod($migration, 'processMigration'));

        /** @var Form $form */
        $form = getItemByTypeName(Form::class, $form_name);
        $destinations = $form->getDestinations();
        foreach ($destinations as $destination) {
            /** @var AbstractCommonITILFormDestination $itil_destination */
            $itil_destination = $destination->getConcreteDestinationItem();

            // Find the matching expected destination
            $expected_destination = current(array_filter(
                $expected_destinations,
                function ($expected_destination) use ($itil_destination, $destination) {
                    if ((new $expected_destination['itemtype']())->getTarget()::class !== $itil_destination->getTarget()::class) {
                        return false;
                    }

                    if ($expected_destination['name'] !== $destination->fields['name']) {
                        return false;
                    }

                    return true;
                }
            ));
            $this->assertNotFalse($expected_destination);

            // Check the fields of the destination
            if ($itil_destination instanceof AbstractCommonITILFormDestination) {
                foreach ($expected_destination['fields'] as $field_key => $expected_field) {
                    $field = $itil_destination->getConfigurableFieldByKey($field_key);

                    $this->assertEquals(
                        $expected_field->jsonSerialize(),
                        $field->getConfig(
                            $form,
                            $destination->getConfig()
                        )->jsonSerialize()
                    );
                }
            }
        }

        $this->assertCount(
            count($expected_destinations),
            $destinations,
            'The number of destinations is not the expected one'
        );
    }
}
