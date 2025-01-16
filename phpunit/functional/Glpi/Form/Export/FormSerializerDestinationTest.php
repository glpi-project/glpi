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

namespace tests\units\Glpi\Form;

use Glpi\DBAL\JsonFieldInterface;
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
use Glpi\Form\Destination\CommonITILField\SLATTRFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategy;
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
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Destination\FormDestinationTypeManager;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use SLM;
use Ticket;

use function PHPUnit\Framework\assertEquals;

final class FormSerializerDestinationTest extends \DbTestCase
{
    use FormTesterTrait;

    private static FormSerializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new FormSerializer();
        parent::setUpBeforeClass();
    }

    public static function getFieldDestinationsWithSpecificValueProvider(): iterable
    {
        yield 'TitleField' => [
            'field_key' => TitleField::getKey(),
            'config'    => new SimpleValueConfig(value: 'My title')
        ];

        yield 'ContentField' => [
            'field_key' => ContentField::getKey(),
            'config'    => new SimpleValueConfig(value: 'My content')
        ];

        yield 'TemplateField' => [
            'field_key'        => TemplateField::getKey(),
            'config_fn'        => fn($created_items) => new TemplateFieldConfig(
                strategy: TemplateFieldStrategy::SPECIFIC_TEMPLATE,
                specific_template_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \TicketTemplate::class,
                    'input'    => [
                        'name'        => 'My ticket template',
                    ]
                ]
            ]
        ];

        yield 'ITILCategoryField' => [
            'field_key'        => ITILCategoryField::getKey(),
            'config_fn'        => fn($created_items) => new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                specific_itilcategory_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \ITILCategory::class,
                    'input'    => [
                        'name'        => 'My ITIL category',
                    ]
                ]
            ]
        ];

        yield 'EntityField' => [
            'field_key'        => EntityField::getKey(),
            'config_fn'        => fn($created_items) => new EntityFieldConfig(
                strategy: EntityFieldStrategy::SPECIFIC_VALUE,
                specific_entity_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \Entity::class,
                    'input'    => [
                        'name' => 'My entity',
                    ]
                ]
            ]
        ];

        yield 'LocationField' => [
            'field_key'        => LocationField::getKey(),
            'config_fn'        => fn($created_items) => new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_VALUE,
                specific_location_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \Location::class,
                    'input'    => [
                        'name' => 'My location',
                    ]
                ]
            ]
        ];

        yield 'AssociatedItemsField' => [
            'field_key'        => AssociatedItemsField::getKey(),
            'config_fn'        => fn($created_items) => new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
                specific_associated_items: [
                    \Computer::class => [$created_items[0]->getId()],
                    \Monitor::class  => [$created_items[1]->getId()],
                ]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \Computer::class,
                    'input'    => [
                        'name' => 'My computer',
                    ]
                ],
                [
                    'itemtype' => \Monitor::class,
                    'input'    => [
                        'name' => 'My monitor',
                    ]
                ]
            ],
            'keys_to_ignore' => ['specific_question_ids']
        ];

        yield 'ITILFollowupField' => [
            'field_key'        => ITILFollowupField::getKey(),
            'config_fn'        => fn($created_items) => new ITILFollowupFieldConfig(
                strategy: ITILFollowupFieldStrategy::SPECIFIC_VALUES,
                specific_itilfollowuptemplates_ids: [$created_items[0]->getId()]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \ITILFollowupTemplate::class,
                    'input'    => [
                        'name' => 'My ITIL followup template',
                    ]
                ]
            ]
        ];

        yield 'RequestSourceField' => [
            'field_key'        => RequestSourceField::getKey(),
            'config_fn'        => fn($created_items) => new RequestSourceFieldConfig(
                strategy: RequestSourceFieldStrategy::SPECIFIC_VALUE,
                specific_request_source: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \RequestType::class,
                    'input'    => [
                        'name' => 'My request source',
                    ]
                ]
            ]
        ];

        yield 'ValidationField' => [
            'field_key'        => ValidationField::getKey(),
            'config_fn'        => fn($created_items) => new ValidationFieldConfig(
                strategies: [ValidationFieldStrategy::SPECIFIC_ACTORS],
                specific_actors: [
                    getForeignKeyFieldForItemType(\User::class) . '-' . $created_items[0]->getId(),
                    getForeignKeyFieldForItemType(\Group::class) . '-' . $created_items[1]->getId(),
                ]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \User::class,
                    'input'    => [
                        'name' => 'My user',
                    ]
                ],
                [
                    'itemtype' => \Group::class,
                    'input'    => [
                        'name' => 'My group',
                    ]
                ]
            ],
            'check_fn' => function ($imported_destinations, $created_items) {
                assertEquals(
                    array_diff_key(
                        (new ValidationFieldConfig(
                            strategies: [ValidationFieldStrategy::SPECIFIC_ACTORS],
                            specific_actors: [
                                \User::class => [$created_items[0]->getId()],
                                \Group::class => [$created_items[1]->getId()],
                            ]
                        ))->jsonSerialize(),
                        ['specific_question_ids' => '']
                    ),
                    current($imported_destinations)->getConfig()[ValidationField::getKey()]
                );
            }
        ];

        yield 'ITILTaskField' => [
            'field_key' => ITILTaskField::getKey(),
            'config_fn' => fn($created_items) => new ITILTaskFieldConfig(
                strategy: ITILTaskFieldStrategy::SPECIFIC_VALUES,
                specific_itiltasktemplates_ids: [$created_items[0]->getId()]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \TaskTemplate::class,
                    'input'    => [
                        'name' => 'My ITIL task',
                    ]
                ]
            ]
        ];

        yield 'RequesterField' => [
            'field_key' => RequesterField::getKey(),
            'config_fn' => fn($created_items) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    getForeignKeyFieldForItemType(\User::class) . '-' . $created_items[0]->getId(),
                    getForeignKeyFieldForItemType(\Group::class) . '-' . $created_items[1]->getId(),
                    getForeignKeyFieldForItemType(\Supplier::class) . '-' . $created_items[2]->getId(),
                ]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \User::class,
                    'input'    => [
                        'name' => 'My user',
                    ]
                ],
                [
                    'itemtype' => \Group::class,
                    'input'    => [
                        'name' => 'My group',
                    ]
                ],
                [
                    'itemtype' => \Supplier::class,
                    'input'    => [
                        'name' => 'My supplier',
                    ]
                ]
            ],
            'check_fn' => function ($imported_destinations, $created_items) {
                assertEquals(
                    array_diff_key(
                        (new RequesterFieldConfig(
                            strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                            specific_itilactors_ids: [
                                \User::class => [$created_items[0]->getId()],
                                \Group::class => [$created_items[1]->getId()],
                                \Supplier::class => [$created_items[2]->getId()],
                            ]
                        ))->jsonSerialize(),
                        ['specific_question_ids' => '']
                    ),
                    current($imported_destinations)->getConfig()[RequesterField::getKey()]
                );
            }
        ];

        yield 'ObserverField' => [
            'field_key' => ObserverField::getKey(),
            'config_fn' => fn($created_items) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    getForeignKeyFieldForItemType(\User::class) . '-' . $created_items[0]->getId(),
                    getForeignKeyFieldForItemType(\Group::class) . '-' . $created_items[1]->getId(),
                ]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \User::class,
                    'input'    => [
                        'name' => 'My user',
                    ]
                ],
                [
                    'itemtype' => \Group::class,
                    'input'    => [
                        'name' => 'My group',
                    ]
                ]
            ],
            'check_fn' => function ($imported_destinations, $created_items) {
                assertEquals(
                    array_diff_key(
                        (new ObserverFieldConfig(
                            strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                            specific_itilactors_ids: [
                                \User::class => [$created_items[0]->getId()],
                                \Group::class => [$created_items[1]->getId()],
                            ]
                        ))->jsonSerialize(),
                        ['specific_question_ids' => '']
                    ),
                    current($imported_destinations)->getConfig()[ObserverField::getKey()]
                );
            }
        ];

        yield 'AssigneeField' => [
            'field_key' => AssigneeField::getKey(),
            'config_fn' => fn($created_items) => new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    getForeignKeyFieldForItemType(\User::class) . '-' . $created_items[0]->getId(),
                    getForeignKeyFieldForItemType(\Group::class) . '-' . $created_items[1]->getId(),
                ]
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \User::class,
                    'input'    => [
                        'name' => 'My user',
                    ]
                ],
                [
                    'itemtype' => \Group::class,
                    'input'    => [
                        'name' => 'My group',
                    ]
                ]
            ],
            'check_fn' => function ($imported_destinations, $created_items) {
                assertEquals(
                    array_diff_key(
                        (new AssigneeFieldConfig(
                            strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                            specific_itilactors_ids: [
                                \User::class => [$created_items[0]->getId()],
                                \Group::class => [$created_items[1]->getId()],
                            ]
                        ))->jsonSerialize(),
                        ['specific_question_ids' => '']
                    ),
                    current($imported_destinations)->getConfig()[AssigneeField::getKey()]
                );
            }
        ];

        yield 'SLATTOField' => [
            'field_key' => SLATTOField::getKey(),
            'config_fn' => fn($created_items) => new SLATTOFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \SLA::class,
                    'input'    => [
                        'name'            => 'My SLA',
                        'type'            => SLM::TTO,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ]
                ]
            ]
        ];

        yield 'SLATTRField' => [
            'field_key' => SLATTOField::getKey(),
            'config_fn' => fn($created_items) => new SLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \SLA::class,
                    'input'    => [
                        'name'            => 'My SLA',
                        'type'            => SLM::TTR,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ]
                ]
            ]
        ];

        yield 'OLATTRField' => [
            'field_key' => OLATTRField::getKey(),
            'config_fn' => fn($created_items) => new OLATTRFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \OLA::class,
                    'input'    => [
                        'name'            => 'My OLA',
                        'type'            => SLM::TTR,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ]
                ]
            ]
        ];

        yield 'OLATTOField' => [
            'field_key' => OLATTOField::getKey(),
            'config_fn' => fn($created_items) => new OLATTOFieldConfig(
                strategy: SLMFieldStrategy::SPECIFIC_VALUE,
                specific_slm_id: $created_items[0]->getId()
            ),
            'items_to_create'  => [
                [
                    'itemtype' => \OLA::class,
                    'input'    => [
                        'name'            => 'My OLA',
                        'type'            => SLM::TTO,
                        'number_time'     => 4,
                        'definition_time' => 'hour',
                    ]
                ]
            ]
        ];

        yield 'UrgencyField' => [
            'field_key' => UrgencyField::getKey(),
            'config' => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::SPECIFIC_VALUE,
                specific_urgency_value: 5
            ),
        ];

        yield 'RequestTypeField' => [
            'field_key' => RequestTypeField::getKey(),
            'config' => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: Ticket::DEMAND_TYPE
            )
        ];
    }

    #[DataProvider('getFieldDestinationsWithSpecificValueProvider')]
    public function testImportAndExportForDestinationFieldWithSpecificValueStrategy(
        string $field_key,
        JsonFieldInterface $config = null,
        callable $config_fn = null,
        array $items_to_create = [],
        callable $check_fn = null,
        array $keys_to_ignore = []
    ) {
        $this->login();

        $created_items = [];
        foreach ($items_to_create as $item) {
            if ((new $item['itemtype']())->isEntityAssign()) {
                $item['input']['entities_id'] = $this->getTestRootEntity(only_id: true);
            }

            $created_items[] = $this->createItem(
                $item['itemtype'],
                $item['input']
            );
        }

        if ($config === null) {
            $config = $config_fn($created_items);
        }

        $imported_form = $this->checkImportAndExport(
            $field_key,
            $config
        );
        $imported_destinations = $imported_form->getDestinations();

        if ($check_fn === null) {
            $this->assertEquals(
                array_diff_key(
                    $config->jsonSerialize(),
                    array_flip($keys_to_ignore)
                ),
                current($imported_destinations)->getConfig()[$field_key]
            );
        } else {
            $check_fn($imported_destinations, $created_items);
        }
    }

    public static function getFieldDestinationsWithSpecificQuestionProvider(): iterable
    {
        yield 'ITILCategoryField' => [
            'field_key' => ITILCategoryField::getKey(),
            'config_fn'    => fn($questions) => new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: current($questions)->getId(),
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeItemDropdown::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \ITILCategory::class
                    ))->jsonSerialize())
                ]
            ],
        ];

        yield 'EntityField' => [
            'field_key' => EntityField::getKey(),
            'config_fn'    => fn($questions) => new EntityFieldConfig(
                strategy: EntityFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: current($questions)->getId(),
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeItem::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \Entity::class
                    ))->jsonSerialize())
                ]
            ],
        ];

        yield 'LocationField' => [
            'field_key' => LocationField::getKey(),
            'config_fn'    => fn($questions) => new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: current($questions)->getId(),
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeItemDropdown::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \Location::class
                    ))->jsonSerialize())
                ]
            ],
        ];

        yield 'AssociatedItemsField' => [
            'field_key' => AssociatedItemsField::getKey(),
            'config_fn'    => fn($questions) => new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    current($questions)->getId(),
                    next($questions)->getId(),
                ],
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeItem::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \Computer::class
                    ))->jsonSerialize())
                ],
                [
                    'type' => QuestionTypeUserDevice::class
                ]
            ],
            'question_id_key' => 'specific_question_ids'
        ];

        yield 'ValidationField' => [
            'field_key' => ValidationField::getKey(),
            'config_fn'    => fn($questions) => new ValidationFieldConfig(
                strategies: [ValidationFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    current($questions)->getId(),
                    next($questions)->getId(),
                    next($questions)->getId(),
                    next($questions)->getId(),
                ],
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeItem::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \User::class
                    ))->jsonSerialize())
                ],
                [
                    'type' => QuestionTypeItem::class,
                    'extra_data' => json_encode((new QuestionTypeItemExtraDataConfig(
                        itemtype: \Group::class
                    ))->jsonSerialize())
                ],
                [
                    'type' => QuestionTypeObserver::class
                ],
                [
                    'type' => QuestionTypeAssignee::class
                ]
            ],
            'question_id_key' => 'specific_question_ids'
        ];

        yield 'RequesterField' => [
            'field_key' => RequesterField::getKey(),
            'config_fn' => fn($questions) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    current($questions)->getId(),
                    next($questions)->getId(),
                ],
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeRequester::class,
                ],
                [
                    'type' => QuestionTypeRequester::class,
                    'extra_data' => json_encode((new QuestionTypeActorsExtraDataConfig(
                        is_multiple_actors: true
                    ))->jsonSerialize())
                ],
            ],
            'question_id_key' => 'specific_question_ids'
        ];

        yield 'ObserverField' => [
            'field_key' => ObserverField::getKey(),
            'config_fn' => fn($questions) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    current($questions)->getId(),
                    next($questions)->getId(),
                ],
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeObserver::class,
                ],
                [
                    'type' => QuestionTypeObserver::class,
                    'extra_data' => json_encode((new QuestionTypeActorsExtraDataConfig(
                        is_multiple_actors: true
                    ))->jsonSerialize())
                ],
            ],
            'question_id_key' => 'specific_question_ids'
        ];

        yield 'AssigneeField' => [
            'field_key' => AssigneeField::getKey(),
            'config_fn' => fn($questions) => new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    current($questions)->getId(),
                    next($questions)->getId(),
                ],
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeAssignee::class,
                ],
                [
                    'type' => QuestionTypeAssignee::class,
                    'extra_data' => json_encode((new QuestionTypeActorsExtraDataConfig(
                        is_multiple_actors: true
                    ))->jsonSerialize())
                ],
            ],
            'question_id_key' => 'specific_question_ids'
        ];

        yield 'UrgencyField' => [
            'field_key' => UrgencyField::getKey(),
            'config_fn'    => fn($questions) => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: current($questions)->getId(),
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeUrgency::class
                ]
            ],
        ];

        yield 'RequestTypeField' => [
            'field_key' => RequestTypeField::getKey(),
            'config_fn'    => fn($questions) => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: current($questions)->getId(),
            ),
            'questions_to_create' => [
                [
                    'type' => QuestionTypeRequestType::class
                ]
            ],
        ];
    }

    #[DataProvider('getFieldDestinationsWithSpecificQuestionProvider')]
    public function testImportAndExportForDestinationFieldWithSpecificQuestionStrategy(
        string $field_key,
        JsonFieldInterface $config = null,
        callable $config_fn = null,
        array $questions_to_create = [],
        string $question_id_key = 'specific_question_id'
    ) {
        $this->login();

        $question_names = [];
        $form_builder = $this->getFormBuilderWithTicketDestination();
        foreach ($questions_to_create as $question_data) {
            $question_names[] = sprintf('%s - %s', $question_data['type'], count($question_names));
            $form_builder->addQuestion(
                $question_names[count($question_names) - 1],
                $question_data['type'],
                $question_data['default_value'] ?? '',
                $question_data['extra_data'] ?? ''
            );
        }
        $form = $this->createForm($form_builder);

        if ($config === null) {
            $config = $config_fn($form->getQuestions());
        }

        $imported_form = $this->checkImportAndExport(
            $field_key,
            $config,
            $form
        );
        $imported_destinations = $imported_form->getDestinations();

        $this->assertNotEquals(
            $config->jsonSerialize()[$question_id_key],
            current($imported_destinations)->getConfig()[$field_key][$question_id_key]
        );

        if (is_array($config->jsonSerialize()[$question_id_key])) {
            foreach ($config->jsonSerialize()[$question_id_key] as $key => $question_id) {
                // Retrieve the question id from the imported form
                $this->assertEquals(
                    $form->getQuestions()[$question_id]->getName(),
                    $imported_form->getQuestions()[current($imported_destinations)->getConfig()[$field_key][$question_id_key][$key]]->getName()
                );
            }
        } else {
            $this->assertEquals(
                $form->getQuestions()[$config->jsonSerialize()[$question_id_key]]->getName(),
                $imported_form->getQuestions()[current($imported_destinations)->getConfig()[$field_key][$question_id_key]]->getName()
            );
        }
    }

    public function testAllConfigFieldsImplementConfigWithForeignKeysInterface()
    {
        $configs_to_exclude = [
            SimpleValueConfig::class,
            RequestSourceFieldConfig::class,
        ];

        /** @var AbstractCommonITILFormDestination[] $destination_types */
        $destination_types = array_filter(
            FormDestinationTypeManager::getInstance()->getDestinationTypes(),
            fn($destination_type) => $destination_type instanceof AbstractCommonITILFormDestination
        );
        $destination_fields = array_merge(...array_map(
            fn($destination_type) => $destination_type->getConfigurableFields(),
            $destination_types
        ));
        $destination_config_fields = array_unique(array_map(
            fn($field) => $field->getConfigClass(),
            $destination_fields
        ));

        // Check that all config fields implement ConfigWithForeignKeysInterface
        foreach ($destination_config_fields as $config_field) {
            if (in_array($config_field, $configs_to_exclude)) {
                continue;
            }

            $this->assertTrue(
                is_subclass_of($config_field, ConfigWithForeignKeysInterface::class),
                sprintf(
                    "Config field %s must implement ConfigWithForeignKeysInterface",
                    $config_field
                )
            );
        }
    }

    /**
     * Check the import and export process for a given key
     *
     * @param string $key
     * @param JsonFieldInterface $config
     * @param string[] $keys_to_skip
     * @param Form|null $form
     * @return Form The imported form
     */
    private function checkImportAndExport(
        string $key,
        JsonFieldInterface $config,
        Form $form = null
    ): Form {
        if ($form === null) {
            $form = $this->createForm($this->getFormBuilderWithTicketDestination());
        }

        // Insert config
        $destinations = $form->getDestinations();
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [$key => $config->jsonSerialize()]],
            ["config"],
        );

        // Export and import process
        $imported_form = $this->exportAndImportForm($form);
        $imported_destinations = $imported_form->getDestinations();

        $this->assertCount(1, $imported_destinations);

        return $imported_form;
    }

    private function getFormBuilderWithTicketDestination(): FormBuilder
    {
        $builder = new FormBuilder();
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $builder;
    }
}
