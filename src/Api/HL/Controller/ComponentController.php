<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Search;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Doc\Route(
    parameters: [
        [
            'name' => 'asset_itemtype',
            'description' => 'Asset type',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_STRING]
        ],
        [
            'name' => 'asset_id',
            'description' => 'The ID of the Asset',
            'location' => Doc\Parameter::LOCATION_PATH,
            'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
        ]
    ]
)]
class ComponentController extends AbstractController
{
    use CRUDControllerTrait;

    protected static function getRawKnownSchemas(): array
    {
        $common_device_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'x-readonly' => true,
            ],
            'designation' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'manufacturer' => self::getDropdownTypeSchema(\Manufacturer::class),
            'entity' => self::getDropdownTypeSchema(\Entity::class),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
        ];
        $common_item_device_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'x-readonly' => true,
            ],
            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
            'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            'entity' => self::getDropdownTypeSchema(\Entity::class),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'serial' => ['type' => Doc\Schema::TYPE_STRING],
            'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
            'location' => self::getDropdownTypeSchema(\Location::class),
            'status' => self::getDropdownTypeSchema(\State::class),
            'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
        ];
        return [
            'Battery' => [
                'x-itemtype' => \DeviceBattery::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'voltage' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'type' => self::getDropdownTypeSchema(\DeviceBatteryType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceBatteryModel::class),
                    ]
            ],
            'Camera' => [
                'x-itemtype' => \DeviceCamera::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'flash' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'flashunit'],
                        'lens_facing' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'lensfacing'],
                        'orientation' => ['type' => Doc\Schema::TYPE_STRING],
                        'focal_length' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'focallength'],
                        'sensor_size' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'sensorsize'],
                        'model' => self::getDropdownTypeSchema(\DeviceCameraModel::class),
                        'support' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'Case' => [
                'x-itemtype' => \DeviceCase::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'type' => self::getDropdownTypeSchema(\DeviceCaseType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceCaseModel::class),
                    ]
            ],
            'Controller' => [
                'x-itemtype' => \DeviceControl::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'is_raid' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'interface' => self::getDropdownTypeSchema(\InterfaceType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceControlModel::class),
                    ]
            ],
            'Drive' => [
                'x-itemtype' => \DeviceDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'is_writer' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'speed' => ['type' => Doc\Schema::TYPE_STRING],
                        'interface' => self::getDropdownTypeSchema(\InterfaceType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceDriveModel::class),
                    ]
            ],
            'Firmware' => [
                'x-itemtype' => \DeviceFirmware::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                        'version' => ['type' => Doc\Schema::TYPE_STRING],
                        'type' => self::getDropdownTypeSchema(\DeviceFirmwareType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceFirmwareModel::class),
                    ]
            ],
            'GenericDevice' => [
                'x-itemtype' => \DeviceGeneric::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'type' => self::getDropdownTypeSchema(\DeviceGenericType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceGenericModel::class),
                        'location' => self::getDropdownTypeSchema(\Location::class),
                        'state' => self::getDropdownTypeSchema(\State::class),
                    ]
            ],
            'GraphicCard' => [
                'x-itemtype' => \DeviceGraphicCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'chipset' => ['type' => Doc\Schema::TYPE_STRING],
                        'memory_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'interface' => self::getDropdownTypeSchema(\InterfaceType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceGraphicCardModel::class),
                    ]
            ],
            'HardDrive' => [
                'x-itemtype' => \DeviceHardDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'rpm' => ['type' => Doc\Schema::TYPE_STRING],
                        'cache' => ['type' => Doc\Schema::TYPE_STRING],
                        'capacity_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'interface' => self::getDropdownTypeSchema(\InterfaceType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceHardDriveModel::class),
                    ]
            ],
            'Memory' => [
                'x-itemtype' => \DeviceMemory::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'frequency' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'frequence'],
                        'size_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'type' => self::getDropdownTypeSchema(\DeviceMemoryType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceMemoryModel::class),
                    ]
            ],
            'NetworkCard' => [
                'x-itemtype' => \DeviceNetworkCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'bandwidth' => ['type' => Doc\Schema::TYPE_STRING],
                        'mac_default' => ['type' => Doc\Schema::TYPE_STRING],
                        'model' => self::getDropdownTypeSchema(\DeviceNetworkCardModel::class),
                    ]
            ],
            'PCIDevice' => [
                'x-itemtype' => \DevicePci::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'model' => self::getDropdownTypeSchema(\DevicePciModel::class),
                    ]
            ],
            'PowerSupply' => [
                'x-itemtype' => \DevicePowerSupply::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'power' => ['type' => Doc\Schema::TYPE_STRING],
                        'is_atx' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'model' => self::getDropdownTypeSchema(\DevicePowerSupplyModel::class),
                    ]
            ],
            'Processor' => [
                'x-itemtype' => \DeviceProcessor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'frequency' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'frequence'],
                        'frequency_default' => ['type' => Doc\Schema::TYPE_STRING],
                        'nbcores_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'nbthreads_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'model' => self::getDropdownTypeSchema(\DeviceProcessorModel::class),
                    ]
            ],
            'Sensor' => [
                'x-itemtype' => \DeviceSensor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'type' => self::getDropdownTypeSchema(\DeviceSensorType::class),
                        'model' => self::getDropdownTypeSchema(\DeviceSensorModel::class),
                        'location' => self::getDropdownTypeSchema(\Location::class),
                        'state' => self::getDropdownTypeSchema(\State::class),
                    ]
            ],
            'SIMCard' => [
                'x-itemtype' => \DeviceSimcard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'voltage' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'allow_voip' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'type' => self::getDropdownTypeSchema(\DeviceSimcardType::class),
                    ]
            ],
            'SoundCard' => [
                'x-itemtype' => \DeviceSoundCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'model' => self::getDropdownTypeSchema(\DeviceSoundCardModel::class),
                    ]
            ],
            'Systemboard' => [
                'x-itemtype' => \DeviceMotherboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                        'chipset' => ['type' => Doc\Schema::TYPE_STRING],
                        'model' => self::getDropdownTypeSchema(\DeviceMotherboardModel::class),
                    ]
            ],
            'BatteryItem' => [
                'x-itemtype' => \Item_DeviceBattery::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'battery' => self::getDropdownTypeSchema(\DeviceBattery::class, null, 'designation'),
                        'date_manufacture' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'format' => Doc\Schema::FORMAT_STRING_DATE,
                            'x-field' => 'manufacturing_date'
                        ],
                        'real_capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    ]
            ],
            'CameraItem' => [
                'x-itemtype' => \Item_DeviceCamera::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => array_filter($common_item_device_properties + [
                        'camera' => self::getDropdownTypeSchema(\DeviceCamera::class, null, 'designation'),
                    ], static fn($key) => !in_array($key, ['status', 'location', 'serial', 'otherserial']), ARRAY_FILTER_USE_KEY) // Cameras don't follow the general schema of the others
            ],
            'CaseItem' => [
                'x-itemtype' => \Item_DeviceCase::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'case' => self::getDropdownTypeSchema(\DeviceCase::class, null, 'designation'),
                    ]
            ],
            'ControllerItem' => [
                'x-itemtype' => \Item_DeviceControl::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'controller' => self::getDropdownTypeSchema(\DeviceControl::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'DriveItem' => [
                'x-itemtype' => \Item_DeviceDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'drive' => self::getDropdownTypeSchema(\DeviceDrive::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'FirmwareItem' => [
                'x-itemtype' => \Item_DeviceFirmware::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'firmware' => self::getDropdownTypeSchema(\DeviceFirmware::class, null, 'designation'),
                    ]
            ],
            'GenericDeviceItem' => [
                'x-itemtype' => \Item_DeviceGeneric::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'generic_device' => self::getDropdownTypeSchema(\DeviceGeneric::class, null, 'designation'),
                    ]
            ],
            'GraphicCardItem' => [
                'x-itemtype' => \Item_DeviceGraphicCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'graphic_card' => self::getDropdownTypeSchema(\DeviceGraphicCard::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                        'memory' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    ]
            ],
            'HardDriveItem' => [
                'x-itemtype' => \Item_DeviceHardDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'hard_drive' => self::getDropdownTypeSchema(\DeviceHardDrive::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                        'capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    ]
            ],
            'MemoryItem' => [
                'x-itemtype' => \Item_DeviceMemory::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'memory' => self::getDropdownTypeSchema(\DeviceMemory::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                        'size' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    ]
            ],
            'NetworkCardItem' => [
                'x-itemtype' => \Item_DeviceNetworkCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'network_card' => self::getDropdownTypeSchema(\DeviceNetworkCard::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                        'mac' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'PCIDeviceItem' => [
                'x-itemtype' => \Item_DevicePci::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'pci_device' => self::getDropdownTypeSchema(\DevicePci::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'PowerSupplyItem' => [
                'x-itemtype' => \Item_DevicePowerSupply::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'power_supply' => self::getDropdownTypeSchema(\DevicePowerSupply::class, null, 'designation'),
                    ]
            ],
            'ProcessorItem' => [
                'x-itemtype' => \Item_DeviceProcessor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'processor' => self::getDropdownTypeSchema(\DeviceProcessor::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                        'frequency' => ['type' => Doc\Schema::TYPE_STRING],
                        'nbcores' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                        'nbthreads' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    ]
            ],
            'SensorItem' => [
                'x-itemtype' => \Item_DeviceSensor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'sensor' => self::getDropdownTypeSchema(\DeviceSensor::class, null, 'designation'),
                    ]
            ],
            'SIMCardItem' => [
                'x-itemtype' => \Item_DeviceSimcard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'sim_card' => self::getDropdownTypeSchema(\DeviceSimcard::class, null, 'designation'),
                        'pin' => ['type' => Doc\Schema::TYPE_STRING],
                        'pin2' => ['type' => Doc\Schema::TYPE_STRING],
                        'puk' => ['type' => Doc\Schema::TYPE_STRING],
                        'puk2' => ['type' => Doc\Schema::TYPE_STRING],
                        'msin' => ['type' => Doc\Schema::TYPE_STRING],
                        'line' => self::getDropdownTypeSchema(\Line::class),
                        'user' => self::getDropdownTypeSchema(\User::class),
                        'group' => self::getDropdownTypeSchema(\Group::class),
                    ]
            ],
            'SoundCardItem' => [
                'x-itemtype' => \Item_DeviceSoundCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'sound_card' => self::getDropdownTypeSchema(\DeviceSoundCard::class, null, 'designation'),
                        'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
            ],
            'SystemboardItem' => [
                'x-itemtype' => \Item_DeviceMotherboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                        'systemboard' => self::getDropdownTypeSchema(\DeviceMotherboard::class, null, 'designation'),
                    ]
            ],
        ];
    }

    #[Route(path: '/Components', methods: ['GET'], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get all available component types',
        methods: ['GET'],
        responses: [
            '200' => [
                'description' => 'List of component types',
                'schema' => [
                    'type' => Doc\Schema::TYPE_ARRAY,
                    'items' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                            'href' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                ]
            ]
        ]
    )]
    public function index(Request $request): Response
    {
        global $CFG_GLPI;

        $supported_types = [];
        $schemas = self::getRawKnownSchemas();

        foreach ($CFG_GLPI['device_types'] as $device_type) {
            $is_supported = count(array_filter($schemas, static function ($schema) use ($device_type) {
                    return $schema['x-itemtype'] === $device_type;
                })) === 1;
            if ($is_supported) {
                $supported_types[] = [
                    'itemtype' => $device_type,
                    'name' => $device_type::getTypeName(1),
                    'href' => self::getAPIPathForRouteFunction(self::class, 'getComponentTypes', ['component_type' => $device_type]),
                ];
            }
        }

        return new JSONResponse($supported_types);
    }

    #[Route(path: '/Components/{component_type}', methods: ['GET'], requirements: [
        'component_type' => '\w*'
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get the component definitions of the specified type',
    )]
    public function getComponentTypes(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return Search::searchBySchema($this->getKnownSchema($component_type), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['GET'], requirements: [
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a specific component definition',
    )]
    public function getComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return Search::getOneBySchema($this->getKnownSchema($component_type), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}', methods: ['POST'], requirements: [
        'component_type' => '\w*'
    ], tags: ['Components'])]
    #[Doc\Route(
        description: 'Create a component definition of the specified type',
    )]
    public function createComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        // Needed to determine the correct URL for getComponentsOfType route
        $request->setParameter('component_type', $component_type);
        return Search::createBySchema($this->getKnownSchema($component_type), $request->getParameters(), [self::class, 'getComponentType']);
    }

    #[Route(path: '/Components/{component_type}/{id}/Items', methods: ['GET'], requirements: [
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get the components of a specific component definition',
    )]
    public function getComponentsOfType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        $component_id = $request->getAttribute('id');
        $item_schema = $this->getKnownSchema($component_type . 'Item');
        // Find property that links to the component type
        $component_property = null;

        $component_table = ($this->getKnownSchema($component_type)['x-itemtype'])::getTable();
        foreach ($item_schema['properties'] as $property => $property_schema) {
            if (isset($property_schema['x-join']) && $property_schema['x-join']['table'] === $component_table) {
                $component_property = $property;
                break;
            }
        }

        if ($component_property === null) {
            throw new \RuntimeException('Invalid component type');
        }

        $request->setParameter($component_property, $component_id);
        return Search::searchBySchema($item_schema, $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['PATCH'], requirements: [
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Components'])]
    #[Doc\Route(
        description: 'Update a component definition of the specified type',
    )]
    public function updateComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return Search::updateBySchema($this->getKnownSchema($component_type), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['DELETE'], requirements: [
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Components'])]
    #[Doc\Route(
        description: 'Delete a component definition of the specified type',
    )]
    public function deleteComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return Search::deleteBySchema($this->getKnownSchema($component_type), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/Items/{id}', methods: ['GET'], requirements: [
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a specific component',
    )]
    public function getComponent(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        $item_schema = $this->getKnownSchema($component_type . 'Item');

        return Search::getOneBySchema($item_schema, $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Assets/{itemtype}/{id}/Component/{component_type}', methods: ['GET'], requirements: [
        'itemtype' => '\w*',
        'component_type' => '\w*',
        'id' => '\d+'
    ], tags: ['Assets', 'Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get all components for an asset',
    )]
    public function getAssetComponentsByType(Request $request): Response
    {
        // Set itemtype and items_id filters
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype');
        $filters .= ';items_id==' . $request->getAttribute('id');
        $request->setParameter('filter', $filters);

        $component_type = $request->getAttribute('component_type');
        $item_schema = $this->getKnownSchema($component_type . 'Item');

        return Search::searchBySchema($item_schema, $request->getParameters());
    }
}
