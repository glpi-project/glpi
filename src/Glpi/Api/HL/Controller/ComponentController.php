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

namespace Glpi\Api\HL\Controller;

use CommonDevice;
use DeviceBattery;
use DeviceBatteryModel;
use DeviceBatteryType;
use DeviceCamera;
use DeviceCameraModel;
use DeviceCase;
use DeviceCaseModel;
use DeviceCaseType;
use DeviceControl;
use DeviceControlModel;
use DeviceDrive;
use DeviceDriveModel;
use DeviceFirmware;
use DeviceFirmwareModel;
use DeviceFirmwareType;
use DeviceGeneric;
use DeviceGenericModel;
use DeviceGenericType;
use DeviceGraphicCard;
use DeviceGraphicCardModel;
use DeviceHardDrive;
use DeviceHardDriveModel;
use DeviceMemory;
use DeviceMemoryModel;
use DeviceMemoryType;
use DeviceMotherboard;
use DeviceMotherboardModel;
use DeviceNetworkCard;
use DeviceNetworkCardModel;
use DevicePci;
use DevicePciModel;
use DevicePowerSupply;
use DevicePowerSupplyModel;
use DeviceProcessor;
use DeviceProcessorModel;
use DeviceSensor;
use DeviceSensorModel;
use DeviceSensorType;
use DeviceSimcard;
use DeviceSimcardType;
use DeviceSoundCard;
use DeviceSoundCardModel;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use InterfaceType;
use Item_DeviceBattery;
use Item_DeviceCamera;
use Item_DeviceCase;
use Item_DeviceControl;
use Item_DeviceDrive;
use Item_DeviceFirmware;
use Item_DeviceGeneric;
use Item_DeviceGraphicCard;
use Item_DeviceHardDrive;
use Item_DeviceMemory;
use Item_DeviceMotherboard;
use Item_DeviceNetworkCard;
use Item_DevicePci;
use Item_DevicePowerSupply;
use Item_DeviceProcessor;
use Item_DeviceSensor;
use Item_DeviceSimcard;
use Item_DeviceSoundCard;
use Line;
use Location;
use Manufacturer;
use RuntimeException;
use State;
use User;

#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'asset_itemtype',
            schema: new Doc\Schema(Doc\Schema::TYPE_STRING),
            description: 'Asset type',
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'asset_id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            description: 'The ID of the Asset',
            location: Doc\Parameter::LOCATION_PATH,
        ),
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
                'readOnly' => true,
            ],
            'designation' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'manufacturer' => self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer'),
            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
        ];
        $common_item_device_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'readOnly' => true,
            ],
            'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
            'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
            'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'serial' => ['type' => Doc\Schema::TYPE_STRING],
            'otherserial' => ['type' => Doc\Schema::TYPE_STRING],
            'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
            'status' => self::getDropdownTypeSchema(class: State::class, full_schema: 'State'),
            'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            'is_dynamic' => ['type' => Doc\Schema::TYPE_BOOLEAN],
        ];
        $common_device_type_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'readOnly' => true,
            ],
            'name' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
            'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
        ];
        $common_device_model_properties = [
            'id' => [
                'type' => Doc\Schema::TYPE_INTEGER,
                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                'readOnly' => true,
            ],
            'name' => ['type' => Doc\Schema::TYPE_STRING],
            'comment' => ['type' => Doc\Schema::TYPE_STRING],
            'product_number' => ['type' => Doc\Schema::TYPE_STRING],
        ];

        return [
            'BatteryType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceBatteryType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'BatteryModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceBatteryModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Battery' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceBattery::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'voltage' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'type' => self::getDropdownTypeSchema(class: DeviceBatteryType::class, full_schema: 'BatteryType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceBatteryModel::class, full_schema: 'BatteryModel'),
                ],
            ],
            'CameraModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceCameraModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Camera' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceCamera::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'flash' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'flashunit'],
                    'lens_facing' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'lensfacing'],
                    'orientation' => ['type' => Doc\Schema::TYPE_STRING],
                    'focal_length' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'focallength'],
                    'sensor_size' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'sensorsize'],
                    'model' => self::getDropdownTypeSchema(class: DeviceCameraModel::class, full_schema: 'CameraModel'),
                    'support' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'CaseType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceCaseType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'CaseModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceCaseModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Case' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceCase::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'type' => self::getDropdownTypeSchema(class: DeviceCaseType::class, full_schema: 'CaseType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceCaseModel::class, full_schema: 'CaseModel'),
                ],
            ],
            'ControllerModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceControlModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Controller' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceControl::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'is_raid' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'interface' => self::getDropdownTypeSchema(class: InterfaceType::class, full_schema: 'InterfaceType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceControlModel::class, full_schema: 'ControllerModel'),
                ],
            ],
            'DriveModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceDriveModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Drive' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'is_writer' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'speed' => ['type' => Doc\Schema::TYPE_STRING],
                    'interface' => self::getDropdownTypeSchema(class: InterfaceType::class, full_schema: 'InterfaceType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceDriveModel::class, full_schema: 'DriveModel'),
                ],
            ],
            'FirmwareType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceFirmwareType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'FirmwareModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceFirmwareModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Firmware' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceFirmware::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'version' => ['type' => Doc\Schema::TYPE_STRING],
                    'type' => self::getDropdownTypeSchema(class: DeviceFirmwareType::class, full_schema: 'FirmwareType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceFirmwareModel::class, full_schema: 'FirmwareModel'),
                ],
            ],
            'GenericDeviceType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceGenericType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'GenericDeviceModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceGenericModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'GenericDevice' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceGeneric::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'type' => self::getDropdownTypeSchema(class: DeviceGenericType::class, full_schema: 'GenericDeviceType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceGenericModel::class, full_schema: 'GenericDeviceModel'),
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                ],
            ],
            'GraphicCardModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceGraphicCardModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'GraphicCard' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceGraphicCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'chipset' => ['type' => Doc\Schema::TYPE_STRING],
                    'memory_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'interface' => self::getDropdownTypeSchema(class: InterfaceType::class, full_schema: 'InterfaceType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceGraphicCardModel::class, full_schema: 'GraphicCardModel'),
                ],
            ],
            'HardDriveModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceHardDriveModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'HardDrive' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceHardDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'rpm' => ['type' => Doc\Schema::TYPE_STRING],
                    'cache' => ['type' => Doc\Schema::TYPE_STRING],
                    'capacity_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'interface' => self::getDropdownTypeSchema(class: InterfaceType::class, full_schema: 'InterfaceType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceHardDriveModel::class, full_schema: 'HardDriveModel'),
                ],
            ],
            'InterfaceType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => InterfaceType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'MemoryType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceMemoryType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'MemoryModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceMemoryModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Memory' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceMemory::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'frequency' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'frequence'],
                    'size_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'type' => self::getDropdownTypeSchema(class: DeviceMemoryType::class, full_schema: 'MemoryType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceMemoryModel::class, full_schema: 'MemoryModel'),
                ],
            ],
            'NetworkCardModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceNetworkCardModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'NetworkCard' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceNetworkCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'bandwidth' => ['type' => Doc\Schema::TYPE_STRING],
                    'mac_default' => ['type' => Doc\Schema::TYPE_STRING],
                    'model' => self::getDropdownTypeSchema(class: DeviceNetworkCardModel::class, full_schema: 'NetworkCardModel'),
                ],
            ],
            'PCIModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DevicePciModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'PCIDevice' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DevicePci::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'model' => self::getDropdownTypeSchema(class: DevicePciModel::class, full_schema: 'PCIModel'),
                ],
            ],
            'PowerSupplyModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DevicePowerSupplyModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'PowerSupply' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DevicePowerSupply::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'power' => ['type' => Doc\Schema::TYPE_STRING],
                    'is_atx' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'model' => self::getDropdownTypeSchema(class: DevicePowerSupplyModel::class, full_schema: 'PowerSupplyModel'),
                ],
            ],
            'ProcessorModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceProcessorModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Processor' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceProcessor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'frequency' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'frequence'],
                    'frequency_default' => ['type' => Doc\Schema::TYPE_STRING],
                    'nbcores_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'nbthreads_default' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'model' => self::getDropdownTypeSchema(class: DeviceProcessorModel::class, full_schema: 'ProcessorModel'),
                ],
            ],
            'SensorType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSensorType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'SensorModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSensorModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'Sensor' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSensor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'type' => self::getDropdownTypeSchema(class: DeviceSensorType::class, full_schema: 'SensorType'),
                    'model' => self::getDropdownTypeSchema(class: DeviceSensorModel::class, full_schema: 'SensorModel'),
                    'location' => self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location'),
                ],
            ],
            'SIMCardType' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSimcardType::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_type_properties,
            ],
            'SIMCard' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSimcard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'voltage' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'allow_voip' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'type' => self::getDropdownTypeSchema(class: DeviceSimcardType::class, full_schema: 'SIMCardType'),
                ],
            ],
            'SoundCardModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSoundCardModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties,
            ],
            'SoundCard' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceSoundCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'model' => self::getDropdownTypeSchema(class: DeviceSoundCardModel::class, full_schema: 'SoundCardModel'),
                ],
            ],
            'SystemboardModel' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceMotherboardModel::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_model_properties + [
                    'chipset' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'Systemboard' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => DeviceMotherboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_device_properties + [
                    'chipset' => ['type' => Doc\Schema::TYPE_STRING],
                    'model' => self::getDropdownTypeSchema(class: DeviceMotherboardModel::class, full_schema: 'SystemboardModel'),
                ],
            ],
            'BatteryItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceBattery::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'battery' => self::getDropdownTypeSchema(class: DeviceBattery::class, name_field: 'designation', full_schema: 'Battery'),
                    'date_manufacture' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE,
                        'x-field' => 'manufacturing_date',
                    ],
                    'real_capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                ],
            ],
            'CameraItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceCamera::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => array_filter($common_item_device_properties + [
                    'camera' => self::getDropdownTypeSchema(class: DeviceCamera::class, name_field: 'designation', full_schema: 'Camera'),
                ], static fn($key) => !in_array($key, ['status', 'location', 'serial', 'otherserial']), ARRAY_FILTER_USE_KEY), // Cameras don't follow the general schema of the others
            ],
            'CaseItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceCase::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'case' => self::getDropdownTypeSchema(class: DeviceCase::class, name_field: 'designation', full_schema: 'Case'),
                ],
            ],
            'ControllerItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceControl::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'controller' => self::getDropdownTypeSchema(class: DeviceControl::class, name_field: 'designation', full_schema: 'Controller'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'DriveItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'drive' => self::getDropdownTypeSchema(class: DeviceDrive::class, name_field: 'designation', full_schema: 'Drive'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'FirmwareItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceFirmware::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'firmware' => self::getDropdownTypeSchema(class: DeviceFirmware::class, name_field: 'designation', full_schema: 'Firmware'),
                ],
            ],
            'GenericDeviceItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceGeneric::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'generic_device' => self::getDropdownTypeSchema(class: DeviceGeneric::class, name_field: 'designation', full_schema: 'GenericDevice'),
                ],
            ],
            'GraphicCardItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceGraphicCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'graphic_card' => self::getDropdownTypeSchema(class: DeviceGraphicCard::class, name_field: 'designation', full_schema: 'GraphicCard'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    'memory' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                ],
            ],
            'HardDriveItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceHardDrive::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'hard_drive' => self::getDropdownTypeSchema(class: DeviceHardDrive::class, name_field: 'designation', full_schema: 'HardDrive'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    'capacity' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                ],
            ],
            'MemoryItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceMemory::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'memory' => self::getDropdownTypeSchema(class: DeviceMemory::class, name_field: 'designation', full_schema: 'Memory'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    'size' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                ],
            ],
            'NetworkCardItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceNetworkCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'network_card' => self::getDropdownTypeSchema(class: DeviceNetworkCard::class, name_field: 'designation', full_schema: 'NetworkCard'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    'mac' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'PCIDeviceItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DevicePci::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'pci_device' => self::getDropdownTypeSchema(class: DevicePci::class, name_field: 'designation', full_schema: 'PCIDevice'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'PowerSupplyItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DevicePowerSupply::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'power_supply' => self::getDropdownTypeSchema(class: DevicePowerSupply::class, name_field: 'designation', full_schema: 'PowerSupply'),
                ],
            ],
            'ProcessorItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceProcessor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'processor' => self::getDropdownTypeSchema(class: DeviceProcessor::class, name_field: 'designation', full_schema: 'Processor'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                    'frequency' => ['type' => Doc\Schema::TYPE_STRING],
                    'nbcores' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                    'nbthreads' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT32],
                ],
            ],
            'SensorItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceSensor::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'sensor' => self::getDropdownTypeSchema(class: DeviceSensor::class, name_field: 'designation', full_schema: 'Sensor'),
                ],
            ],
            'SIMCardItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceSimcard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'sim_card' => self::getDropdownTypeSchema(class: DeviceSimcard::class, name_field: 'designation', full_schema: 'SIMCard'),
                    'pin' => ['type' => Doc\Schema::TYPE_STRING],
                    'pin2' => ['type' => Doc\Schema::TYPE_STRING],
                    'puk' => ['type' => Doc\Schema::TYPE_STRING],
                    'puk2' => ['type' => Doc\Schema::TYPE_STRING],
                    'msin' => ['type' => Doc\Schema::TYPE_STRING],
                    'line' => self::getDropdownTypeSchema(class: Line::class, full_schema: 'Line'),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'user_tech' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User'),
                    'group' => self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group'),
                ],
            ],
            'SoundCardItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceSoundCard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'sound_card' => self::getDropdownTypeSchema(class: DeviceSoundCard::class, name_field: 'designation', full_schema: 'SoundCard'),
                    'busID' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'SystemboardItem' => [
                'x-version-introduced' => '2.0',
                'x-itemtype' => Item_DeviceMotherboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => $common_item_device_properties + [
                    'systemboard' => self::getDropdownTypeSchema(class: DeviceMotherboard::class, name_field: 'designation', full_schema: 'Systemboard'),
                ],
            ],
        ];
    }

    public static function getComponentTypes(): array
    {
        $schemas = self::getKnownSchemas(Router::API_VERSION);
        $component_types = [];
        foreach ($schemas as $name => $data) {
            if (is_subclass_of($data['x-itemtype'], CommonDevice::class)) {
                $component_types[] = $name;
            }
        }
        return $component_types;
    }

    #[Route(path: '/Components', methods: ['GET'], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get all available component types',
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
        $supported_types = [];
        $schemas = self::getRawKnownSchemas();

        foreach (self::getComponentTypes() as $device_type) {
            $device_class = $schemas[$device_type]['x-itemtype'];
            $supported_types[] = [
                'itemtype' => $device_class,
                'name' => $device_class::getTypeName(1),
                'href' => self::getAPIPathForRouteFunction(self::class, 'listComponentTypes', ['component_type' => $device_type]),
            ];
        }

        return new JSONResponse($supported_types);
    }

    #[Route(path: '/Components/{component_type}', methods: ['GET'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{component_type}',
        description: 'List or search for the component definitions of the specified type'
    )]
    public function listComponentTypes(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return ResourceAccessor::searchBySchema($this->getKnownSchema($component_type, $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['GET'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{component_type}',
        description: 'Get an existing component definition of the specified type',
    )]
    public function getComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return ResourceAccessor::getOneBySchema($this->getKnownSchema($component_type, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}', methods: ['POST'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
    ], tags: ['Components'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\CreateRoute(
        schema_name: '{component_type}',
        description: 'Create a new component definition of the specified type'
    )]
    public function createComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        // Needed to determine the correct URL for getComponentsOfType route
        $request->setParameter('component_type', $component_type);
        return ResourceAccessor::createBySchema($this->getKnownSchema($component_type, $this->getAPIVersion($request)), $request->getParameters(), [self::class, 'getComponentType']);
    }

    #[Route(path: '/Components/{component_type}/{id}/Items', methods: ['GET'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{component_type}Item',
        description: 'List or search for the components of a specific component definition'
    )]
    public function getComponentsOfType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        $component_id = $request->getAttribute('id');
        $item_schema = $this->getKnownSchema($component_type . 'Item', $this->getAPIVersion($request));
        // Find property that links to the component type
        $component_property = null;

        $component_table = ($this->getKnownSchema($component_type, $this->getAPIVersion($request))['x-itemtype'])::getTable();
        foreach ($item_schema['properties'] as $property => $property_schema) {
            if (isset($property_schema['x-join']) && $property_schema['x-join']['table'] === $component_table) {
                $component_property = $property;
                break;
            }
        }

        if ($component_property === null) {
            throw new RuntimeException('Invalid component type');
        }

        $request->setParameter($component_property, $component_id);
        return ResourceAccessor::searchBySchema($item_schema, $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['PATCH'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Components'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\UpdateRoute(
        schema_name: '{component_type}',
        description: 'Update an existing component definition of the specified type'
    )]
    public function updateComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return ResourceAccessor::updateBySchema($this->getKnownSchema($component_type, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/{id}', methods: ['DELETE'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Components'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\DeleteRoute(
        schema_name: '{component_type}',
        description: 'Delete a component definition of the specified type',
    )]
    public function deleteComponentType(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        return ResourceAccessor::deleteBySchema($this->getKnownSchema($component_type, $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Components/{component_type}/Items/{id}', methods: ['GET'], requirements: [
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\GetRoute(
        schema_name: '{component_type}Item',
        description: 'Get a specific component'
    )]
    public function getComponent(Request $request): Response
    {
        $component_type = $request->getAttribute('component_type');
        $item_schema = $this->getKnownSchema($component_type . 'Item', $this->getAPIVersion($request));

        return ResourceAccessor::getOneBySchema($item_schema, $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Assets/{itemtype}/{id}/Component/{component_type}', methods: ['GET'], requirements: [
        'itemtype' => [AssetController::class, 'getAssetTypes'],
        'component_type' => [self::class, 'getComponentTypes'],
        'id' => '\d+',
    ], tags: ['Assets', 'Components'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\SearchRoute(
        schema_name: '{component_type}Item',
        description: 'List or search all components for an asset'
    )]
    public function getAssetComponentsByType(Request $request): Response
    {
        // Set itemtype and items_id filters
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype');
        $filters .= ';items_id==' . $request->getAttribute('id');
        $request->setParameter('filter', $filters);

        $component_type = $request->getAttribute('component_type');
        $item_schema = $this->getKnownSchema($component_type . 'Item', $this->getAPIVersion($request));

        return ResourceAccessor::searchBySchema($item_schema, $request->getParameters());
    }
}
