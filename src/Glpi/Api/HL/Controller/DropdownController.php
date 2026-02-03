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

use ApplianceEnvironment;
use ApplianceType;
use AutoUpdateSystem;
use Blacklist;
use BlacklistedMailContent;
use BudgetType;
use BusinessCriticity;
use CableStrand;
use CableType;
use Calendar;
use CartridgeItemType;
use CertificateType;
use ChangeTemplate;
use ClusterType;
use CommonDBTM;
use ComputerModel;
use ComputerType;
use ConsumableItemType;
use ContactType;
use ContractType;
use DatabaseInstanceCategory;
use DatabaseInstanceType;
use DeviceHardDriveType;
use DocumentCategory;
use DocumentType;
use DomainRecordType;
use DomainRelation;
use DomainType;
use DropdownVisibility;
use EnclosureModel;
use Entity;
use Filesystem;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Holiday;
use ITILCategory;
use ITILFollowupTemplate;
use ITILValidationTemplate;
use KnowbaseItemCategory;
use LineOperator;
use LineType;
use Location;
use Manufacturer;
use MonitorModel;
use MonitorType;
use Network;
use NetworkEquipmentModel;
use NetworkEquipmentType;
use NetworkPortFiberchannelType;
use NetworkPortType;
use PCIVendor;
use PeripheralModel;
use PeripheralType;
use PhoneModel;
use PhoneType;
use Planning;
use PlanningEventCategory;
use PrinterModel;
use PrinterType;
use ProblemTemplate;
use ProjectTaskType;
use ProjectType;
use RequestType;
use SoftwareLicenseType;
use SolutionTemplate;
use SolutionType;
use State;
use SupplierType;
use TaskCategory;
use TaskTemplate;
use TicketTemplate;
use Toolbox;
use USBVendor;
use User;
use ValidationStep;
use VirtualMachineState;
use VirtualMachineSystem;
use VirtualMachineType;
use WifiNetwork;

#[Route(path: '/Dropdowns', priority: 1, tags: ['Dropdowns'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'itemtype',
            schema: new Doc\Schema(Doc\Schema::TYPE_STRING),
            description: 'Dropdown type',
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            description: 'The ID of the dropdown item',
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class DropdownController extends AbstractController
{
    use CRUDControllerTrait;

    protected static function getRawKnownSchemas(): array
    {
        $schemas = [];

        $schemas['Location'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Location::class,
            'description' => Location::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'code' => ['type' => Doc\Schema::TYPE_STRING],
                'alias' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                'level' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'room' => ['type' => Doc\Schema::TYPE_STRING],
                'building' => ['type' => Doc\Schema::TYPE_STRING],
                'address' => ['type' => Doc\Schema::TYPE_STRING],
                'town' => ['type' => Doc\Schema::TYPE_STRING],
                'postcode' => ['type' => Doc\Schema::TYPE_STRING],
                'state' => ['type' => Doc\Schema::TYPE_STRING],
                'country' => ['type' => Doc\Schema::TYPE_STRING],
                'latitude' => ['type' => Doc\Schema::TYPE_STRING],
                'longitude' => ['type' => Doc\Schema::TYPE_STRING],
                'altitude' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['State'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => State::class,
            'description' => State::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
                'level' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'is_visible_helpdesk' => ['x-field' => 'is_helpdesk_visible', 'type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        // Uses static array for BC/stability. Plugins adding new types should use the related hook to modify the API schema
        $state_types = [
            'Computer', 'Monitor', 'NetworkEquipment',
            'Peripheral', 'Phone', 'Printer', 'SoftwareLicense',
            'Certificate', 'Enclosure', 'PDU', 'Line',
            'Rack', 'SoftwareVersion', 'Cluster', 'Contract',
            'Appliance', 'DatabaseInstance', 'Cable', 'Unmanaged', 'PassiveDCEquipment',
        ];
        $visiblities = [];
        foreach ($state_types as $state_type) {
            // Handle any cases where there may be a namespace and also make the property lowercase
            $visiblities[$state_type] = strtolower(str_replace('\\', '_', $state_type));
        }

        $schemas['State_Visibilities'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [],
        ];
        $schemas['State']['properties']['visibilities'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-full-schema' => 'State_Visibilities',
        ];

        foreach ($visiblities as $state_type => $visiblity) {
            $schemas['State_Visibilities']['properties'][$visiblity] = [
                'type' => Doc\Schema::TYPE_BOOLEAN,
                'x-field' => 'is_visible',
                'readOnly' => true,
                'x-join' => [
                    'table' => DropdownVisibility::getTable(),
                    'fkey' => 'id',
                    'field' => 'items_id',
                    'condition' => [
                        'itemtype' => State::class,
                        'visible_itemtype' => $state_type,
                    ],
                ],
            ];
        }
        $schemas['State']['properties']['visibilities']['properties'] = $schemas['State_Visibilities']['properties'];

        $schemas['Manufacturer'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Manufacturer::class,
            'description' => Manufacturer::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Calendar'] = [
            'x-version-introduced' => '2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Calendar::class,
            'description' => Calendar::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['WifiNetwork'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => WifiNetwork::class,
            'x-version-introduced' => '2.2',
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'essid' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'mode' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ITILCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => 'ITILCategory',
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'level' => [
                    'x-version-introduced' => '2.1.0',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: ITILCategory::class, full_schema: 'ITILCategory'),
                'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User') + ['x-version-introduced' => '2.2.0'],
                'group' => self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group') + ['x-version-introduced' => '2.2.0'],
                'code' => ['type' => Doc\Schema::TYPE_STRING, 'x-version-introduced' => '2.2.0'],
                'is_helpdesk_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_helpdeskvisible', 'x-version-introduced' => '2.2.0'],
                'ticket_incident_template' => self::getDropdownTypeSchema(
                    class: TicketTemplate::class,
                    field: 'tickettemplates_id_incident',
                    full_schema: 'TicketTemplate'
                ) + ['x-version-introduced' => '2.2.0'],
                'ticket_request_template' => self::getDropdownTypeSchema(
                    class: TicketTemplate::class,
                    field: 'tickettemplates_id_demand',
                    full_schema: 'TicketTemplate'
                ) + ['x-version-introduced' => '2.2.0'],
                'change_template' => self::getDropdownTypeSchema(
                    class: ChangeTemplate::class,
                    field: 'changetemplates_id',
                    full_schema: 'ChangeTemplate'
                ) + ['x-version-introduced' => '2.2.0'],
                'problem_template' => self::getDropdownTypeSchema(
                    class: ProblemTemplate::class,
                    field: 'problemtemplates_id',
                    full_schema: 'ProblemTemplate'
                ) + ['x-version-introduced' => '2.2.0'],
                'is_incident_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_incident', 'x-version-introduced' => '2.2.0'],
                'is_request_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_request', 'x-version-introduced' => '2.2.0'],
                'is_change_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_change', 'x-version-introduced' => '2.2.0'],
                'is_problem_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_problem', 'x-version-introduced' => '2.2.0'],
                'knowbase_category' => self::getDropdownTypeSchema(
                    class: KnowbaseItemCategory::class,
                    field: 'knowbaseitemcategories_id',
                    full_schema: 'KBCategory'
                ) + ['x-version-introduced' => '2.2.0'],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['TaskCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => TaskCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity') + ['x-version-introduced' => '2.2.0'],
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-version-introduced' => '2.2.0'],
                'completename' => [
                    'x-version-introduced' => '2.1.0',
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'parent' => self::getDropdownTypeSchema(class: TaskCategory::class, full_schema: 'TaskCategory') + ['x-version-introduced' => '2.1.0'],
                'level' => [
                    'x-version-introduced' => '2.1.0',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'is_helpdesk_visible' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'is_helpdeskvisible', 'x-version-introduced' => '2.2.0'],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-version-introduced' => '2.2.0'],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-version-introduced' => '2.2.0'],
            ],
        ];

        $schemas['RequestType'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => RequestType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'is_helpdesk_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_followup_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_mail_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_mailfollowup_default' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_visible_ticket' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'is_ticketheader',
                ],
                'is_visible_followup' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'is_itilfollowup',
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['EventCategory'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PlanningEventCategory::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => PlanningEventCategory::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'color' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'pattern' => Doc\Schema::PATTERN_COLOR_HEX,
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['USBVendor'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => USBVendor::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'vendorid' => ['type' => Doc\Schema::TYPE_STRING],
                'deviceid' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PCIVendor'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PCIVendor::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'vendorid' => ['type' => Doc\Schema::TYPE_STRING],
                'deviceid' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DenyList'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Blacklist::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'type' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [
                        Blacklist::IP, Blacklist::MAC, Blacklist::SERIAL, Blacklist::UUID, Blacklist::EMAIL,
                        Blacklist::MODEL, Blacklist::NAME, Blacklist::MANUFACTURER,
                    ],
                    'description' => <<<EOT
                        The type of denylist entry:
                        - 1: IP Address
                        - 2: MAC Address
                        - 3: Serial Number
                        - 4: UUID
                        - 5: Email Address
                        - 6: Model
                        - 7: Name
                        - 8: Manufacturer
EOT,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'value' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortFiberchannelType'] = [
            'x-version-introduced' => '2.2',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => NetworkPortFiberchannelType::class,
            'description' => NetworkPortFiberchannelType::getTypeName(1),
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DeniedMailContent'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => BlacklistedMailContent::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'content' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CloseTime'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Holiday::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_begin' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-field' => 'begin_date', 'required' => true],
                'date_end' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME, 'x-field' => 'end_date', 'required' => true],
                'is_perpetual' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['BusinessCriticity'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => BusinessCriticity::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'level' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => self::getDropdownTypeSchema(class: BusinessCriticity::class, full_schema: 'BusinessCriticity'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DocumentCategory'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DocumentCategory::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'completename' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'readOnly' => true,
                ],
                'level' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'readOnly' => true,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'parent' => self::getDropdownTypeSchema(class: DocumentCategory::class, full_schema: 'DocumentCategory'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DocumentType'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DocumentType::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'extension' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'ext'],
                'icon' => ['type' => Doc\Schema::TYPE_STRING],
                'mime' => ['type' => Doc\Schema::TYPE_STRING],
                'is_uploadable' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DatabaseInstanceCategory'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DatabaseInstanceCategory::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DatabaseInstanceType'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => DatabaseInstanceType::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['VirtualMachineType'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => VirtualMachineType::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['VirtualMachineModel'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => VirtualMachineSystem::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['VirtualMachineState'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => VirtualMachineState::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CableType'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => CableType::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CableStrand'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => CableStrand::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['AutoUpdateSystem'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => AutoUpdateSystem::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
            ],
        ];

        $schemas['FollowupTemplate'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => ITILFollowupTemplate::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'content' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_HTML,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'request_type' => self::getDropdownTypeSchema(RequestType::class, full_schema: 'RequestType'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['TaskTemplate'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => TaskTemplate::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'content' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_HTML,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'category' => self::getDropdownTypeSchema(class: TaskCategory::class, full_schema: 'TaskCategory'),
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'is_private' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'duration' => ['type' => Doc\Schema::TYPE_INTEGER, 'x-field' => 'actiontime'],
                'state' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'enum' => [
                        Planning::INFO,
                        Planning::TODO,
                        Planning::DONE,
                    ],
                    'description' => <<<EOT
                        The state of the task.
                        - 0: Information
                        - 1: To do
                        - 2: Done
                        EOT,
                ],
                'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                'group_tech' => self::getDropdownTypeSchema(class: Group::class, field: 'groups_id_tech', full_schema: 'Group'),
                'use_current_user' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['SolutionTemplate'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => SolutionTemplate::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'content' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_HTML,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'type' => self::getDropdownTypeSchema(SolutionType::class, full_schema: 'SolutionType'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ValidationTemplate'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => ITILValidationTemplate::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'content' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_HTML,
                ],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'approval_step' => self::getDropdownTypeSchema(ValidationStep::class, full_schema: 'ApprovalStep'),
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['SolutionType'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => SolutionType::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'is_incident' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                'is_request' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                'is_problem' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                'is_change' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => true],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ApprovalStep'] = [
            'x-version-introduced' => '2.2.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => ValidationStep::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'is_default' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'description' => 'Indicates if this step is the default one for new approvals. If you set this to true, any other step previously set as the default will be unset.',
                    'default' => false,
                ],
                'min_required_approval_percent' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-field' => 'minimal_required_validation_percent',
                    'minimum' => 0,
                    'maximum' => 100,
                    'default' => 100,
                ],
            ],
        ];

        $schemas['ComputerModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ComputerModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'rack_units' => [
                    'x-field' => 'required_units',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'minimum' => 1,
                    'default' => 1,
                ],
                'depth' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'default' => 1,
                    'enum' => [0.25, 0.33, 0.5, 1],
                ],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ComputerType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ComputerType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['Filesystem'] = [
            'x-version-introduced' => '2.3.0',
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Filesystem::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['MonitorModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => MonitorModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'rack_units' => [
                    'x-field' => 'required_units',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'minimum' => 1,
                    'default' => 1,
                ],
                'depth' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'default' => 1,
                    'enum' => [0.25, 0.33, 0.5, 1],
                ],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['MonitorType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => MonitorType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkEquipmentModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkEquipmentModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'rack_units' => [
                    'x-field' => 'required_units',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'minimum' => 1,
                    'default' => 1,
                ],
                'depth' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'default' => 1,
                    'enum' => [0.25, 0.33, 0.5, 1],
                ],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkEquipmentType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkEquipmentType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PeripheralModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => PeripheralModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'rack_units' => [
                    'x-field' => 'required_units',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'minimum' => 1,
                    'default' => 1,
                ],
                'depth' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'default' => 1,
                    'enum' => [0.25, 0.33, 0.5, 1],
                ],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PeripheralType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => PeripheralType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PhoneModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => PhoneModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PhoneType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => PhoneType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PrinterModel'] = [
            'x-version-introduced' => '2.0',
            'x-itemtype' => PrinterModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING],
                'picture_front' => [
                    'x-version-introduced' => '2.3.0',
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'x-version-introduced' => '2.3.0',
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'x-version-introduced' => '2.3.0',
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['PrinterType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => PrinterType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['EnclosureModel'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => EnclosureModel::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'product_number' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'weight' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'rack_units' => [
                    'x-field' => 'required_units',
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'minimum' => 1,
                    'default' => 1,
                ],
                'depth' => [
                    'type' => Doc\Schema::TYPE_NUMBER,
                    'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                    'default' => 1,
                    'enum' => [0.25, 0.33, 0.5, 1],
                ],
                'power_connections' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'power_consumption' => ['type' => Doc\Schema::TYPE_INTEGER, 'minimum' => 0, 'default' => 0],
                'is_half_rack' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'picture_front' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_front',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'picture_rear' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'picture_rear',
                    'x-mapper' => static function ($v) {
                        if (empty($v)) {
                            return null;
                        }
                        return Toolbox::getPictureUrl($v);
                    },
                    'readOnly' => true,
                ],
                'pictures' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-mapped-from' => 'pictures',
                        'x-mapper' => static function ($v) {
                            $pictures = is_array($v) ? $v : importArrayFromDB($v);
                            return array_map(static fn($picture) => Toolbox::getPictureUrl($picture) ?? '', $pictures);
                        },
                        'readOnly' => true,
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ApplianceType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ApplianceType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'external_id' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'externalidentifier', 'maxLength' => 255],
            ],
        ];

        $schemas['BudgetType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => BudgetType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CartridgeItemType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => CartridgeItemType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['CertificateType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => CertificateType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ClusterType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ClusterType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ContactType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ContactType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ContractType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ContractType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ConsumableItemType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ConsumableItemType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DomainRecordType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => DomainRecordType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'fields' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'description' => 'JSON encoded array of field definitions. Each field definition is an array containing at least a "key" and "label" string. Optional properties include "placeholder" (string), "quote_value" (boolean), and "is_fqdn" (boolean).',
                ],
            ],
        ];

        $schemas['DomainType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => DomainType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
            ],
        ];

        $schemas['LineType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => LineType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['NetworkPortType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => NetworkPortType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'iftype' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'x-field' => 'value_decimal',
                    'description' => 'The interface type as defined by IANA. See https://www.iana.org/assignments/smi-numbers/smi-numbers.xhtml.',
                    'required' => true,
                ],
                'is_importable' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'instantiation_type' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'enum' => [
                        'NetworkPortEthernet', 'NetworkPortWifi', 'NetworkPortAggregate', 'NetworkPortAlias',
                        'NetworkPortDialup', 'NetworkPortLocal', 'NetworkPortFiberchannel',
                    ],
                ],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ProjectTaskType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ProjectTaskType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['ProjectType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => ProjectType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['LicenseType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => SoftwareLicenseType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'parent' => self::getDropdownTypeSchema(class: SoftwareLicenseType::class, full_schema: 'LicenseType'),
                'level' => ['type' => Doc\Schema::TYPE_INTEGER, 'readOnly' => true],
                'completename' => ['type' => Doc\Schema::TYPE_STRING, 'readOnly' => true],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['SupplierType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => SupplierType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['HardDriveType'] = [
            'x-version-introduced' => '2.3',
            'x-itemtype' => DeviceHardDriveType::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
            ],
        ];

        $schemas['ApplianceEnvironment'] = [
            'x-version-introduced' => '2.3.0',
            'x-itemtype' => ApplianceEnvironment::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
            ],
        ];

        $schemas['Network'] = [
            'x-version-introduced' => '2.3.0',
            'x-itemtype' => Network::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        $schemas['DomainRelation'] = [
            'x-version-introduced' => '2.3.0',
            'x-itemtype' => DomainRelation::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
            ],
        ];

        $schemas['LineOperator'] = [
            'x-version-introduced' => '2.3.0',
            'x-itemtype' => LineOperator::class,
            'type' => Doc\Schema::TYPE_OBJECT,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'readOnly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                'mcc' => ['type' => Doc\Schema::TYPE_INTEGER, 'description' => 'Mobile Country Code'],
                'mnc' => ['type' => Doc\Schema::TYPE_INTEGER, 'description' => 'Mobile Network Code'],
                'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            ],
        ];

        return $schemas;
    }

    /**
     * @param bool $types_only If true, only the type names are returned. If false, the type name => localized name pairs are returned.
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getDropdownTypes(bool $types_only = true): array
    {
        static $dropdowns = null;

        if ($dropdowns === null) {
            $dropdowns = [
                'Location' => Location::getTypeName(1),
                'State' => State::getTypeName(1),
                'Manufacturer' => Manufacturer::getTypeName(1),
                'Calendar' => Calendar::getTypeName(1),
                'WifiNetwork' => WifiNetwork::getTypeName(1),
                'NetworkPortFiberchannelType' => NetworkPortFiberchannelType::getTypeName(1),
                'DatabaseInstanceCategory' => DatabaseInstanceCategory::getTypeName(1),
                'DatabaseInstanceType' => DatabaseInstanceType::getTypeName(1),
                'ITILCategory' => ITILCategory::getTypeName(1),
                'TaskCategory' => TaskCategory::getTypeName(1),
                'RequestType' => RequestType::getTypeName(1),
                'EventCategory' => PlanningEventCategory::getTypeName(1),
                'USBVendor' => USBVendor::getTypeName(1),
                'PCIVendor' => PCIVendor::getTypeName(1),
                'DenyList' => Blacklist::getTypeName(1),
                'DeniedMailContent' => BlacklistedMailContent::getTypeName(1),
                'CloseTime' => Holiday::getTypeName(1),
                'BusinessCriticity' => BusinessCriticity::getTypeName(1),
                'DocumentCategory' => DocumentCategory::getTypeName(1),
                'DocumentType' => DocumentType::getTypeName(1),
                'VirtualMachineType' => VirtualMachineType::getTypeName(1),
                'VirtualMachineModel' => VirtualMachineSystem::getTypeName(1),
                'VirtualMachineState' => VirtualMachineState::getTypeName(1),
                'CableType' => CableType::getTypeName(1),
                'CableStrand' => CableStrand::getTypeName(1),
                'AutoUpdateSystem' => AutoUpdateSystem::getTypeName(1),
                'FollowupTemplate' => ITILFollowupTemplate::getTypeName(1),
                'TaskTemplate' => TaskTemplate::getTypeName(1),
                'SolutionTemplate' => SolutionTemplate::getTypeName(1),
                'ValidationTemplate' => ITILValidationTemplate::getTypeName(1),
                'SolutionType' => SolutionType::getTypeName(1),
                'ApprovalStep' => ValidationStep::getTypeName(1),
                'ComputerModel' => ComputerModel::getTypeName(1),
                'ComputerType' => ComputerType::getTypeName(1),
                'MonitorModel' => MonitorModel::getTypeName(1),
                'MonitorType' => MonitorType::getTypeName(1),
                'NetworkEquipmentModel' => NetworkEquipmentModel::getTypeName(1),
                'NetworkEquipmentType' => NetworkEquipmentType::getTypeName(1),
                'PeripheralModel' => PeripheralModel::getTypeName(1),
                'PeripheralType' => PeripheralType::getTypeName(1),
                'PhoneModel' => PhoneModel::getTypeName(1),
                'PhoneType' => PhoneType::getTypeName(1),
                'PrinterModel' => PrinterModel::getTypeName(1),
                'PrinterType' => PrinterType::getTypeName(1),
                'EnclosureModel' => EnclosureModel::getTypeName(1),
                'ApplianceType' => ApplianceType::getTypeName(1),
                'BudgetType' => BudgetType::getTypeName(1),
                'CartridgeItemType' => CartridgeItemType::getTypeName(1),
                'CertificateType' => CertificateType::getTypeName(1),
                'ClusterType' => ClusterType::getTypeName(1),
                'ContactType' => ContactType::getTypeName(1),
                'ContractType' => ContractType::getTypeName(1),
                'ConsumableItemType' => ConsumableItemType::getTypeName(1),
                'DomainRecordType' => DomainRecordType::getTypeName(1),
                'DomainType' => DomainType::getTypeName(1),
                'LineType' => LineType::getTypeName(1),
                'NetworkPortType' => NetworkPortType::getTypeName(1),
                'ProjectTaskType' => ProjectTaskType::getTypeName(1),
                'ProjectType' => ProjectType::getTypeName(1),
                'LicenseType' => SoftwareLicenseType::getTypeName(1),
                'SupplierType' => SupplierType::getTypeName(1),
                'HardDriveType' => DeviceHardDriveType::getTypeName(1),
                'Filesystem' => Filesystem::getTypeName(1),
                'ApplianceEnvironment' => ApplianceEnvironment::getTypeName(1),
                'Network' => Network::getTypeName(1),
                'DomainRelation' => DomainRelation::getTypeName(1),
            ];
        }
        return $types_only ? array_keys($dropdowns) : $dropdowns;
    }

    /**
     * @return string[]
     */
    public static function getDropdownEndpointTypes20(): array
    {
        return [
            'Location', 'State', 'Manufacturer', 'Calendar',
        ];
    }

    /**
     * @return string[]
     */
    public static function getDropdownEndpointTypes22(): array
    {
        return [
            'WifiNetwork', 'NetworkPortFiberchannelType', 'DatabaseInstanceCategory', 'DatabaseInstanceType', 'ITILCategory', 'TaskCategory',
            'RequestType', 'EventCategory', 'USBVendor', 'PCIVendor', 'DenyList', 'DeniedMailContent', 'CloseTime',
            'BusinessCriticity', 'DocumentCategory', 'DocumentType', 'VirtualMachineType', 'VirtualMachineModel',
            'VirtualMachineState', 'CableType', 'CableStrand', 'AutoUpdateSystem', 'FollowupTemplate',
            'TaskTemplate', 'SolutionTemplate', 'ValidationTemplate', 'SolutionType', 'ApprovalStep',
        ];
    }

    /**
     * @return string[]
     */
    public static function getDropdownEndpointTypes23(): array
    {
        return [
            'ComputerModel', 'ComputerType', 'MonitorModel', 'MonitorType', 'NetworkEquipmentModel', 'NetworkEquipmentType',
            'PeripheralModel', 'PeripheralType', 'PhoneModel', 'PhoneType', 'PrinterModel', 'PrinterType', 'EnclosureModel',
            'ApplianceType', 'BudgetType', 'CartridgeItemType', 'CertificateType', 'ClusterType', 'ContactType',
            'ContractType', 'ConsumableItemType', 'DomainRecordType', 'DomainType', 'LineType', 'NetworkPortType',
            'ProjectTaskType', 'ProjectType', 'LicenseType', 'SupplierType', 'HardDriveType', 'Filesystem',
            'ApplianceEnvironment', 'Network', 'DomainRelation',
        ];
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available dropdown types',
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
        $dropdown_types = self::getDropdownTypes(false);
        $v20_dropdowns = self::getDropdownEndpointTypes20();
        $schemas = self::getRawKnownSchemas();
        $dropdown_paths = [];
        foreach ($dropdown_types as $dropdown_type => $dropdown_name) {
            $dropdown_paths[] = [
                'itemtype'  => $schemas[$dropdown_type]['x-itemtype'],
                'name'      => $dropdown_name,
                'href'      => self::getAPIPathForRouteFunction(
                    self::class,
                    in_array($dropdown_type, $v20_dropdowns, true) ? 'search20' : 'search22',
                    ['itemtype' => $dropdown_type]
                ),
            ];
        }
        return new JSONResponse($dropdown_paths);
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes20'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search dropdowns of a specific type'
    )]
    public function search20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes20'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing dropdown of a specific type'
    )]
    public function getItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes20'],
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a dropdown of a specific type'
    )]
    public function createItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem20']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing dropdown of a specific type'
    )]
    public function updateItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes20'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a dropdown of a specific type',
    )]
    public function deleteItem20(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes22'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search dropdowns of a specific type'
    )]
    public function search22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes22'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing dropdown of a specific type'
    )]
    public function getItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes22'],
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a dropdown of a specific type'
    )]
    public function createItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem22']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing dropdown of a specific type'
    )]
    public function updateItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes22'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a dropdown of a specific type',
    )]
    public function deleteItem22(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes23'],
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(
        schema_name: '{itemtype}',
        description: 'List or search dropdowns of a specific type'
    )]
    public function search23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes23'],
        'id' => '\d+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(
        schema_name: '{itemtype}',
        description: 'Get an existing dropdown of a specific type'
    )]
    public function getItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes23'],
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(
        schema_name: '{itemtype}',
        description: 'Create a dropdown of a specific type'
    )]
    public function createItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::createBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getParameters() + ['itemtype' => $itemtype], [self::class, 'getItem23']);
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(
        schema_name: '{itemtype}',
        description: 'Update an existing dropdown of a specific type'
    )]
    public function updateItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getDropdownEndpointTypes23'],
        'id' => '\d+',
    ])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(
        schema_name: '{itemtype}',
        description: 'Delete a dropdown of a specific type',
    )]
    public function deleteItem23(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($itemtype, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
