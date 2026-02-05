<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Api\HL\Controller;

use Alert;
use AutoUpdateSystem;
use Budget;
use BudgetType;
use BusinessCriticity;
use Cluster;
use ClusterType;
use CommonDBTM;
use CommonITILObject;
use Contact;
use ContactType;
use Contract;
use Contract_Item;
use ContractType;
use Database;
use DatabaseInstance;
use DatabaseInstanceCategory;
use DatabaseInstanceType;
use Datacenter;
use Document;
use Document_Item;
use DocumentCategory;
use Domain;
use Domain_Item;
use DomainRecord;
use DomainRecordType;
use DomainRelation;
use DomainType;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group_Item;
use Item_Line;
use Line;
use LineOperator;
use LineType;
use Location;
use Manufacturer;
use SoftwareLicense;
use SoftwareLicenseType;
use State;
use Supplier;
use SupplierType;
use User;

#[Route(path: '/Management', tags: ['Management'])]
final class ManagementController extends AbstractController
{
    /**
     * @param bool $schema_names_only If true, only the schema names are returned.
     * @return array<class-string<CommonDBTM>, array>
     */
    public static function getManagementTypes(bool $schema_names_only = true): array
    {
        static $management_types = null;

        if ($management_types === null) {
            $management_types = [
                Budget::class => [
                    'schema_name' => 'Budget',
                    'label' => Budget::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Cluster::class => [
                    'schema_name' => 'Cluster',
                    'label' => Cluster::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Contact::class => [
                    'schema_name' => 'Contact',
                    'label' => Contact::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Contract::class => [
                    'schema_name' => 'Contract',
                    'label' => Contract::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Database::class => [
                    'schema_name' => 'Database',
                    'label' => Database::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                DatabaseInstance::class => [
                    'schema_name' => 'DatabaseInstance',
                    'label' => DatabaseInstance::getTypeName(1),
                    'version_introduced' => '2.2',
                ],
                Datacenter::class => [
                    'schema_name' => 'DataCenter',
                    'label' => Datacenter::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Document::class => [
                    'schema_name' => 'Document',
                    'label' => Document::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Domain::class => [
                    'schema_name' => 'Domain',
                    'label' => Domain::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                SoftwareLicense::class => [
                    'schema_name' => 'License',
                    'label' => SoftwareLicense::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Line::class => [
                    'schema_name' => 'Line',
                    'label' => Line::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
                Supplier::class => [
                    'schema_name' => 'Supplier',
                    'label' => Supplier::getTypeName(1),
                    'version_introduced' => '2.0',
                ],
            ];
        }
        return $schema_names_only ? array_column($management_types, 'schema_name') : $management_types;
    }

    /**
     * @return string[]
     */
    public static function getManagementEndpointTypes20(): array
    {
        return [
            'Budget', 'Cluster', 'Contact', 'Contract', 'Database', 'DataCenter', 'Document',
            'Domain', 'License', 'Line', 'Supplier',
        ];
    }

    /**
     * @return string[]
     */
    public static function getManagementEndpointTypes22(): array
    {
        return [
            'DatabaseInstance',
        ];
    }

    /**
     * @return string[]
     */
    public static function getManagementEndpointTypes23(): array
    {
        return [
            'DomainRecord',
        ];
    }

    protected static function getRawKnownSchemas(): array
    {
        global $CFG_GLPI;

        $fn_get_assignable_restriction = static function (string $itemtype) {
            if (method_exists($itemtype, 'getAssignableVisiblityCriteria')) {
                $criteria = $itemtype::getAssignableVisiblityCriteria('_');
                if (count($criteria) === 1 && isset($criteria[0]) && is_numeric((string) $criteria[0])) {
                    // Return true for QueryExpression('1') and false for QueryExpression('0') to support fast pass/fail
                    return (bool) $criteria[0];
                }
                return ['WHERE' => $criteria];
            }
            return true;
        };
        $fn_get_group_property = (static fn(string $itemtype) => [
            'type' => Doc\Schema::TYPE_ARRAY,
            'items' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-full-schema' => 'Group',
                'x-join' => [
                    'table' => 'glpi_groups', // The table with the desired data
                    'fkey' => 'groups_id',
                    'field' => 'id',
                    'ref-join' => [
                        'table' => 'glpi_groups_items',
                        'fkey' => 'id',
                        'field' => 'items_id',
                        'condition' => [
                            'itemtype' => $itemtype,
                            'type' => Group_Item::GROUP_TYPE_NORMAL,
                        ],
                    ],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
        ]);
        $fn_get_group_tech_property = (static fn(string $itemtype) => [
            'type' => Doc\Schema::TYPE_ARRAY,
            'items' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-full-schema' => 'Group',
                'x-join' => [
                    'table' => 'glpi_groups', // The table with the desired data
                    'fkey' => 'groups_id',
                    'field' => 'id',
                    'ref-join' => [
                        'table' => 'glpi_groups_items',
                        'fkey' => 'id',
                        'field' => 'items_id',
                        'condition' => [
                            'itemtype' => $itemtype,
                            'type' => Group_Item::GROUP_TYPE_TECH,
                        ],
                    ],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'description' => 'ID',
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
        ]);

        $schemas = [
            'Budget' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Budget::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'type' => self::getDropdownTypeSchema(class: BudgetType::class, full_schema: 'BudgetType'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'value' => [
                        'type' => Doc\Schema::TYPE_NUMBER,
                    ],
                    'date_begin' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-field' => 'begin_date',
                    ],
                    'date_end' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-field' => 'end_date',
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'Cluster' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Cluster::class,
                'x-rights-conditions' => [
                    'read' => static fn() => $fn_get_assignable_restriction(Cluster::class),
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'status' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: ClusterType::class, full_schema: 'ClusterType'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group_tech' => $fn_get_group_tech_property(Cluster::class),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'group' => $fn_get_group_property(Cluster::class),
                    'uuid' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::PATTERN_UUIDV4,
                        'readOnly' => true,
                    ],
                    'autoupdatesystem' => self::getDropdownTypeSchema(class: AutoUpdateSystem::class, full_schema: 'AutoUpdateSystem'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                ],
            ],
            'Contact' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Contact::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: ContactType::class, full_schema: 'ContactType'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                ],
            ],
            'Contract' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Contract::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'status' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: ContractType::class, full_schema: 'ContractType'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'number' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'num',
                    ],
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location') + ['x-version-introduced' => '2.3.0'],
                    'date_begin' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-field' => 'begin_date',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'duration' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'description' => 'Duration in months',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'notice_period' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'description' => 'Notice period in months',
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'notice',
                    ],
                    'renewal_period' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'description' => 'Renewal period in months',
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'periodicity',
                    ],
                    'invoice_period' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'description' => 'Invoice period in months',
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'billing',
                    ],
                    'accounting_number' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'week_begin_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Week begin hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'week_end_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Week end hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'saturday_begin_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Saturday begin hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'saturday_end_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Saturday end hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'sunday_begin_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Sunday begin hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'sunday_end_hour' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::FORMAT_STRING_TIME,
                        'description' => 'Sunday end hour in HH:MM:SS format (RFC3339 partial-time)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'use_saturday' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                        'default' => false,
                    ],
                    'use_sunday' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                        'default' => false,
                    ],
                    'max_links_allowed' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'description' => 'Maximum number of items that can be linked to this contract (0 = unlimited)',
                        'x-version-introduced' => '2.3.0',
                    ],
                    'alert' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                        'x-version-introduced' => '2.3.0',
                        'enum' => [
                            0,
                            2 ** Alert::END,
                            2 ** Alert::NOTICE,
                            (2 ** Alert::END) + (2 ** Alert::NOTICE),
                            2 ** Alert::PERIODICITY,
                            (2 ** Alert::PERIODICITY) + (2 ** Alert::NOTICE),
                        ],
                        'description' => <<<EOT
                        The alert type for this contract
                        - 0: No alert
                        - 4: Alert on end date
                        - 8: Alert on notice date
                        - 12: Alert on end date and notice date
                        - 16: Periodic alert
                        - 24: Periodic alert and alert on notice date
EOT,
                    ],
                    'renewal_type' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'x-field' => 'renewal',
                        'x-version-introduced' => '2.3.0',
                        'enum' => [0, 1, 2],
                        'description' => <<<EOT
                        The renewal type for this contract
                        - 0: No renewal
                        - 1: Tacit renewal (automatic)
                        - 2: Explicit renewal (manual)
EOT,
                    ],
                    'template_name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'is_template' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                    ],
                ],
            ],
            'Database' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Database::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'instance' => self::getDropdownTypeSchema(class: DatabaseInstance::class, full_schema: 'DatabaseInstance') + [
                        'x-version-introduced' => '2.2',
                    ],
                ],
            ],
            'DatabaseInstance' => [
                'x-version-introduced' => '2.2',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => DatabaseInstance::class,
                'x-rights-conditions' => [
                    'read' => static fn() => $fn_get_assignable_restriction(DatabaseInstance::class),
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'entity' => self::getDropdownTypeSchema(Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'version' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'port' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 10], // Why is this a string instead of integer?
                    'path' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'type' => self::getDropdownTypeSchema(class: DatabaseInstanceType::class, full_schema: 'DatabaseInstanceType'),
                    'category' => self::getDropdownTypeSchema(class: DatabaseInstanceCategory::class, full_schema: 'DatabaseInstanceCategory'),
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'state' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                    'is_onbackup' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_lastboot' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_lastbackup' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'database' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'Database',
                            'x-join' => [
                                'table' => Database::getTable(), // The table with the desired data
                                'fkey' => 'id',
                                'field' => DatabaseInstance::getForeignKeyField(),
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'name' => ['type' => Doc\Schema::TYPE_STRING],
                            ],
                        ],
                    ],
                ],
            ],
            'DataCenter' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Datacenter::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                ],
            ],
            'Document' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Document::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'filename' => ['type' => Doc\Schema::TYPE_STRING],
                    'filepath' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'id',
                        'x-mapper' => static fn($v) => $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v,
                        'readOnly' => true,
                    ],
                    'mime' => ['type' => Doc\Schema::TYPE_STRING],
                    'sha1sum' => ['type' => Doc\Schema::TYPE_STRING],
                    'category' => self::getDropdownTypeSchema(class: DocumentCategory::class, full_schema: 'DocumentCategory') + ['x-version-introduced' => '2.3.0'],
                    'link' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255, 'x-version-introduced' => '2.3.0'],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User') + ['x-version-introduced' => '2.3.0'],
                    'checksum_sha1' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 40,
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'sha1sum',
                    ],
                    'is_import_denied' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'is_blacklisted',
                    ],
                    'tag' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                        'x-version-introduced' => '2.3.0',
                    ],
                ],
            ],
            'Domain' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Domain::class,
                'x-rights-conditions' => [
                    'read' => static fn() => $fn_get_assignable_restriction(Domain::class),
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: DomainType::class, full_schema: 'DomainType'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group_tech' => $fn_get_group_tech_property(Domain::class),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'group' => $fn_get_group_property(Domain::class),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'date_domain_creation' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-version-introduced' => '2.3.0',
                        'x-field' => 'date_domaincreation',
                    ],
                    'date_expiration' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'template_name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'is_template' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'is_active' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                    ],
                    'is_recursive' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'x-version-introduced' => '2.3.0',
                    ],
                ],
            ],
            'License' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => SoftwareLicense::class,
                'x-rights-conditions' => [
                    'read' => static fn() => $fn_get_assignable_restriction(SoftwareLicense::class),
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'status' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-version-introduced' => '2.1.0', 'readOnly' => true,],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: SoftwareLicenseType::class, full_schema: 'LicenseType'),
                    'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group_tech' => $fn_get_group_tech_property(SoftwareLicense::class),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'group' => $fn_get_group_property(SoftwareLicense::class),
                    'contact' => ['type' => Doc\Schema::TYPE_STRING],
                    'contact_num' => ['type' => Doc\Schema::TYPE_STRING],
                    'serial' => ['type' => Doc\Schema::TYPE_STRING],
                    'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'completename' => [
                        'x-version-introduced' => '2.1.0',
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'level' => [
                        'x-version-introduced' => '2.1.0',
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                ],
            ],
            'Line' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Line::class,
                'x-rights-conditions' => [
                    'read' => static fn() => $fn_get_assignable_restriction(Line::class),
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'status' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: LineType::class, full_schema: 'LineType'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group_tech' => $fn_get_group_tech_property(Line::class),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'group' => $fn_get_group_property(Line::class),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'caller_num' => [
                        'x-version-introduced' => '2.3.0',
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                    ],
                    'caller_name' => [
                        'x-version-introduced' => '2.3.0',
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                    ],
                    'operator' => self::getDropdownTypeSchema(class: LineOperator::class, full_schema: 'LineOperator') + ['x-version-introduced' => '2.3.0'],
                ],
            ],
            'Supplier' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Supplier::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'type' => self::getDropdownTypeSchema(class: SupplierType::class, full_schema: 'SupplierType'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                ],
            ],
        ];

        $schemas['Document_Item'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Document_Item::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'documents_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'document' => self::getDropdownTypeSchema(class: Document::class, full_schema: 'Document') + ['x-version-introduced' => '2.3.0'],
                'filepath' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'documents_id',
                    'x-mapper' => static fn($v) => $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v,
                    'readOnly' => true,
                ],
                'timeline_position' => [
                    'x-version-introduced' => '2.1.0',
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'enum' => [
                        CommonITILObject::NO_TIMELINE,
                        CommonITILObject::TIMELINE_NOTSET,
                        CommonITILObject::TIMELINE_LEFT,
                        CommonITILObject::TIMELINE_MIDLEFT,
                        CommonITILObject::TIMELINE_MIDRIGHT,
                        CommonITILObject::TIMELINE_RIGHT,
                    ],
                    'description' => <<<EOT
                        The position in the timeline.
                        - 0: No timeline
                        - 1: Not set
                        - 2: Left
                        - 3: Mid left
                        - 4: Mid right
                        - 5: Right
                        EOT,
                ],
            ],
        ];

        $schemas['Infocom'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => 'Infocom',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_buy' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'buy_date',
                ],
                'date_use' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'use_date',
                ],
                'date_order' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'order_date',
                ],
                'date_delivery' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'delivery_date',
                ],
                'date_inventory' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'inventory_date',
                ],
                'date_warranty' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'warranty_date',
                ],
                'date_decommission' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'decommission_date',
                ],
                'warranty_info' => ['type' => Doc\Schema::TYPE_STRING],
                'warranty_value' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'warranty_duration' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Warranty duration in months',
                ],
                'budget' => self::getDropdownTypeSchema(Budget::class),
                'supplier' => self::getDropdownTypeSchema(Supplier::class),
                'order_number' => ['type' => Doc\Schema::TYPE_STRING],
                'delivery_number' => ['type' => Doc\Schema::TYPE_STRING],
                'immo_number' => ['type' => Doc\Schema::TYPE_STRING],
                'invoice_number' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'bill'],
                'value' => ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_FLOAT],
                'amortization_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [0, 1, 2],
                    'description' => <<<EOT
                        The amortization type:
                        - 0: No amortization
                        - 1: Decreasing
                        - 2: Linear
                        EOT,
                    'x-field' => 'sink_type',
                ],
                'amortization_time' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Amortization duration in years',
                    'x-field' => 'sink_time',
                ],
                'amortization_coeff' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'description' => 'Amortization coefficient',
                    'x-field' => 'sink_coeff',
                ],
                'business_criticity' => self::getDropdownTypeSchema(BusinessCriticity::class),
            ],
        ];

        $schemas['Domain_Item'] = [
            'x-version-introduced' => '2.3',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Domain_Item::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'domain' => self::getDropdownTypeSchema(class: Domain::class, full_schema: 'Domain'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                'relation' => self::getDropdownTypeSchema(class: DomainRelation::class, full_schema: 'DomainRelation'),
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ],
        ];

        $schemas['DomainRecord'] = [
            'x-version-introduced' => '2.3',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DomainRecord::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'data' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'The raw data part of a DNS record',
                    'example' => '10 mail.example.com',
                ],
                'data_obj' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'JSON-encoded data object for the record',
                    'example' => '{"priority":10,"server":"mail.example.com"}',
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'domain' => self::getDropdownTypeSchema(class: Domain::class, full_schema: 'Domain'),
                'type' => self::getDropdownTypeSchema(class: DomainRecordType::class, full_schema: 'DomainRecordType'),
                'ttl' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT32,
                    'description' => 'Time to live in seconds',
                ],
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Item_Line'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => Item_Line::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'line' => self::getDropdownTypeSchema(class: Line::class, full_schema: 'Line'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            ],
        ];

        $schemas['Contract_Item'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => Contract_Item::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'contract' => self::getDropdownTypeSchema(class: Contract::class, full_schema: 'Contract'),
                'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            ],
        ];

        return $schemas;
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available management types',
        methods: ['GET'],
        responses: [
            new Doc\Response(new Doc\Schema(
                type: Doc\Schema::TYPE_ARRAY,
                items: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'itemtype' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'name' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                        'href' => new Doc\Schema(Doc\Schema::TYPE_STRING),
                    ]
                )
            )),
        ]
    )]
    public function index(Request $request): Response
    {
        $management_types = self::getManagementTypes(false);
        $v20_types = self::getManagementEndpointTypes20();
        $management_paths = [];
        foreach ($management_types as $m_class => $m_data) {
            $management_paths[] = [
                'itemtype'  => $m_class,
                'name'      => $m_data['label'],
                'href'      => self::getAPIPathForRouteFunction(
                    self::class,
                    in_array($m_data['schema_name'], $v20_types, true) ? 'search20' : 'search22',
                    ['itemtype' => $m_data['schema_name']]
                ),
            ];
        }
        return new JSONResponse($management_paths);
    }

    #[Route(path: '/Document/{id}/Download', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Download a document',
        parameters: [
            new Doc\Parameter(name: 'HTTP_IF_NONE_MATCH', schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING), location: Doc\Parameter::LOCATION_HEADER),
            new Doc\Parameter(name: 'HTTP_IF_MODIFIED_SINCE', schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING), location: Doc\Parameter::LOCATION_HEADER),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('Document'), media_type: 'application/octet-stream'),
        ]
    )]
    public function downloadDocument(Request $request): Response
    {
        $document = new Document();
        if ($document->getFromDB($request->getAttribute('id'))) {
            if ($document->canViewFile()) {
                $symfony_response = $document->getAsResponse();
                return new Response($symfony_response->getStatusCode(), $symfony_response->headers->all(), $symfony_response->getContent());
            }
            return self::getAccessDeniedErrorResponse();
        }
        return self::getNotFoundErrorResponse();
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search management items'
    )]
    public function searchItems20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing management item'
    )]
    public function getItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new management item'
    )]
    public function createItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem20']
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing management item'
    )]
    public function updateItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a management item'
    )]
    public function deleteItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search management items'
    )]
    public function searchItems22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing management item'
    )]
    public function getItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new management item'
    )]
    public function createItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem22']
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing management item'
    )]
    public function updateItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a management item'
    )]
    public function deleteItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes23'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search management items'
    )]
    public function searchItems23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes23'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing management item'
    )]
    public function getItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes23'],
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a new management item'
    )]
    public function createItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema($itemtype, $this->getAPIVersion($request)),
            $request->getParameters() + ['itemtype' => $itemtype],
            [self::class, 'getItem23']
        );
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing management item'
    )]
    public function updateItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getManagementEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a management item'
    )]
    public function deleteItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Domain', methods: ['POST'], requirements: [
        'itemtype' => 'DatabaseInstance|Database',
        'items_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: 'Domain_Item',
        description: 'Assign a domain to an item'
    )]
    public function createDomainItemLink(Request $request): Response
    {
        $request->setParameter('itemtype', $request->getAttribute('itemtype'));
        $request->setParameter('items_id', $request->getAttribute('items_id'));
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('Domain_Item', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getDomainItemLink'],
            [
                'mapped' => [
                    'itemtype' => $request->getAttribute('itemtype'),
                    'items_id' => $request->getAttribute('items_id'),
                ],
            ],
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Domain', methods: ['GET'], requirements: [
        'itemtype' => 'DatabaseInstance|Database',
        'items_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: 'Domain_Item',
        description: 'List or search domain links'
    )]
    public function searchDomainItemLinks(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Domain_Item', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Domain/{id}', methods: ['GET'], requirements: [
        'itemtype' => 'DatabaseInstance|Database',
        'items_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: 'Domain_Item',
        description: 'Get a specific domain link'
    )]
    public function getDomainItemLink(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Domain_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Domain/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => 'DatabaseInstance|Database',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: 'Domain_Item',
        description: 'Update a specific domain link'
    )]
    public function updateDomainItemLink(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Domain_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Domain/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => 'DatabaseInstance|Database',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: 'Domain_Item',
        description: 'Delete a specific domain link'
    )]
    public function deleteDomainItemLink(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Domain_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Certificate', methods: ['POST'], requirements: [
        'itemtype' => 'Domain|DatabaseInstance',
        'items_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: 'Certificate_Item',
        description: 'Assign a certificate to an item'
    )]
    public function createCertificateItemLink(Request $request): Response
    {
        $request->setParameter('itemtype', $request->getAttribute('itemtype'));
        $request->setParameter('items_id', $request->getAttribute('items_id'));
        return ResourceAccessor::createBySchema(
            (new AssetController())->getKnownSchema('Certificate_Item', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getCertificateItemLink'],
            [
                'mapped' => [
                    'itemtype' => $request->getAttribute('itemtype'),
                    'items_id' => $request->getAttribute('items_id'),
                ],
            ],
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Certificate', methods: ['GET'], requirements: [
        'itemtype' => 'Domain|DatabaseInstance',
        'items_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: 'Certificate_Item',
        description: 'List or search certificate links'
    )]
    public function searchCertificateItemLinks(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema((new AssetController())->getKnownSchema('Certificate_Item', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Certificate/{id}', methods: ['GET'], requirements: [
        'itemtype' => 'Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: 'Certificate_Item',
        description: 'Get a specific certificate link'
    )]
    public function getCertificateItemLink(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema((new AssetController())->getKnownSchema('Certificate_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Certificate/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => 'Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: 'Certificate_Item',
        description: 'Update a specific certificate link'
    )]
    public function updateCertificateItemLink(Request $request): Response
    {
        return ResourceAccessor::updateBySchema((new AssetController())->getKnownSchema('Certificate_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Certificate/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => 'Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: 'Certificate_Item',
        description: 'Delete a specific certificate link'
    )]
    public function deleteCertificateItemLink(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema((new AssetController())->getKnownSchema('Certificate_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Budget/{items_id}/KBArticle', methods: ['POST'], requirements: [
        'items_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: 'KBArticle_Item',
        description: 'Assign a KB article to an item'
    )]
    public function createKBArticleItemLink(Request $request): Response
    {
        $request->setParameter('itemtype', 'Budget');
        $request->setParameter('items_id', $request->getAttribute('items_id'));
        return ResourceAccessor::createBySchema(
            (new KnowbaseController())->getKnownSchema('KBArticle_Item', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getKBArticleItemLink'],
            [
                'mapped' => [
                    'items_id' => $request->getAttribute('items_id'),
                ],
            ],
        );
    }

    #[Route(path: '/Budget/{items_id}/KBArticle', methods: ['GET'], requirements: [
        'items_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: 'KBArticle_Item',
        description: 'List or search KB article links'
    )]
    public function searchKBArticleItemLinks(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==Budget;items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema((new KnowbaseController())->getKnownSchema('KBArticle_Item', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Budget/{items_id}/KBArticle/{id}', methods: ['GET'], requirements: [
        'items_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: 'KBArticle_Item',
        description: 'Get a specific KB article link'
    )]
    public function getKBArticleItemLink(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==Budget;items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema((new KnowbaseController())->getKnownSchema('KBArticle_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Budget/{items_id}/KBArticle/{id}', methods: ['PATCH'], requirements: [
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: 'KBArticle_Item',
        description: 'Update a specific KB article link'
    )]
    public function updateKBArticleItemLink(Request $request): Response
    {
        return ResourceAccessor::updateBySchema((new KnowbaseController())->getKnownSchema('KBArticle_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Budget/{items_id}/KBArticle/{id}', methods: ['DELETE'], requirements: [
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: 'KBArticle_Item',
        description: 'Delete a specific KB article link'
    )]
    public function deleteKBArticleItemLink(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema((new KnowbaseController())->getKnownSchema('KBArticle_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Contract', methods: ['POST'], requirements: [
        'itemtype' => 'Line|Cluster|Domain|DatabaseInstance',
        'items_id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: 'Contract_Item',
        description: 'Assign a contract to an item'
    )]
    public function createContractItemLink(Request $request): Response
    {
        $request->setParameter('itemtype', $request->getAttribute('itemtype'));
        $request->setParameter('items_id', $request->getAttribute('items_id'));
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('Contract_Item', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getContractItemLink'],
            [
                'mapped' => [
                    'itemtype' => $request->getAttribute('itemtype'),
                    'items_id' => $request->getAttribute('items_id'),
                ],
            ],
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Contract', methods: ['GET'], requirements: [
        'itemtype' => 'Line|Cluster|Domain|DatabaseInstance',
        'items_id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: 'Contract_Item',
        description: 'List or search contract links'
    )]
    public function searchContractItemLinks(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Contract_Item', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Contract/{id}', methods: ['GET'], requirements: [
        'itemtype' => 'Line|Cluster|Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: 'Contract_Item',
        description: 'Get a specific contract link'
    )]
    public function getContractItemLink(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('Contract_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Contract/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => 'Line|Cluster|Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: 'Contract_Item',
        description: 'Update a specific contract link'
    )]
    public function updateContractItemLink(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('Contract_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Contract/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => 'Line|Cluster|Domain|DatabaseInstance',
        'items_id' => '\d+',
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: 'Contract_Item',
        description: 'Delete a specific contract link'
    )]
    public function deleteContractItemLink(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('Contract_Item', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
