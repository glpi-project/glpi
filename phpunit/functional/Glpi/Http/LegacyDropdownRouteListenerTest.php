<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Http;

use Glpi\Controller\DropdownController;
use Glpi\Controller\DropdownFormController;
use Glpi\Http\LegacyDropdownRouteListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class LegacyDropdownRouteListenerTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        parent::setUp();
    }

    #[DataProvider('provideDropdownClasses')]
    public function testFindDropdownClass(string $path_info, string $expected_class_name): void
    {
        $listener = new LegacyDropdownRouteListener();
        $request = $this->createRequest($path_info);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        if (\str_contains($path_info, '.form.php')) {
            self::assertSame(DropdownFormController::class, $request->attributes->get('_controller'));
        } else {
            self::assertSame(DropdownController::class, $request->attributes->get('_controller'));
        }
        self::assertSame($expected_class_name, $request->attributes->get('class'));
    }

    public static function provideDropdownClasses(): \Generator
    {
        $list = [
            '/front/applianceenvironment.form.php' => \ApplianceEnvironment::class,
            '/front/applianceenvironment.php' => \ApplianceEnvironment::class,
            '/front/appliancetype.form.php' => \ApplianceType::class,
            '/front/appliancetype.php' => \ApplianceType::class,
            '/front/autoupdatesystem.form.php' => \AutoUpdateSystem::class,
            '/front/autoupdatesystem.php' => \AutoUpdateSystem::class,
            '/front/blacklist.form.php' => \Blacklist::class,
            '/front/blacklist.php' => \Blacklist::class,
            '/front/blacklistedmailcontent.form.php' => \BlacklistedMailContent::class,
            '/front/blacklistedmailcontent.php' => \BlacklistedMailContent::class,
            '/front/budgettype.form.php' => \BudgetType::class,
            '/front/budgettype.php' => \BudgetType::class,
            '/front/businesscriticity.form.php' => \BusinessCriticity::class,
            '/front/businesscriticity.php' => \BusinessCriticity::class,
            '/front/cablestrand.form.php' => \CableStrand::class,
            '/front/cablestrand.php' => \CableStrand::class,
            '/front/cabletype.form.php' => \CableType::class,
            '/front/cabletype.php' => \CableType::class,
            '/front/calendar.form.php' => \Calendar::class,
            '/front/calendar.php' => \Calendar::class,
            '/front/cartridgeitemtype.form.php' => \CartridgeItemType::class,
            '/front/cartridgeitemtype.php' => \CartridgeItemType::class,
            '/front/certificatetype.form.php' => \CertificateType::class,
            '/front/certificatetype.php' => \CertificateType::class,
            '/front/changetemplate.form.php' => \ChangeTemplate::class,
            '/front/changetemplate.php' => \ChangeTemplate::class,
            '/front/clustertype.form.php' => \ClusterType::class,
            '/front/clustertype.php' => \ClusterType::class,
            '/front/computermodel.form.php' => \ComputerModel::class,
            '/front/computermodel.php' => \ComputerModel::class,
            '/front/computertype.form.php' => \ComputerType::class,
            '/front/computertype.php' => \ComputerType::class,
            '/front/consumableitemtype.form.php' => \ConsumableItemType::class,
            '/front/consumableitemtype.php' => \ConsumableItemType::class,
            '/front/contacttype.form.php' => \ContactType::class,
            '/front/contacttype.php' => \ContactType::class,
            '/front/contracttype.form.php' => \ContractType::class,
            '/front/contracttype.php' => \ContractType::class,
            '/front/databaseinstancecategory.form.php' => \DatabaseInstanceCategory::class,
            '/front/databaseinstancecategory.php' => \DatabaseInstanceCategory::class,
            '/front/databaseinstancetype.form.php' => \DatabaseInstanceType::class,
            '/front/databaseinstancetype.php' => \DatabaseInstanceType::class,
            '/front/documentcategory.form.php' => \DocumentCategory::class,
            '/front/documentcategory.php' => \DocumentCategory::class,
            '/front/documenttype.form.php' => \DocumentType::class,
            '/front/documenttype.php' => \DocumentType::class,
            '/front/domainrecordtype.form.php' => \DomainRecordType::class,
            '/front/domainrecordtype.php' => \DomainRecordType::class,
            '/front/domainrelation.form.php' => \DomainRelation::class,
            '/front/domainrelation.php' => \DomainRelation::class,
            '/front/domaintype.form.php' => \DomainType::class,
            '/front/domaintype.php' => \DomainType::class,
            '/front/enclosuremodel.form.php' => \EnclosureModel::class,
            '/front/enclosuremodel.php' => \EnclosureModel::class,
            '/front/entity.form.php' => \Entity::class,
            '/front/entity.php' => \Entity::class,
            '/front/fieldblacklist.form.php' => \Fieldblacklist::class,
            '/front/fieldblacklist.php' => \Fieldblacklist::class,
            '/front/fieldunicity.form.php' => \FieldUnicity::class,
            '/front/filesystem.form.php' => \Filesystem::class,
            '/front/filesystem.php' => \Filesystem::class,
            '/front/fqdn.form.php' => \FQDN::class,
            '/front/fqdn.php' => \FQDN::class,
            '/front/holiday.form.php' => \Holiday::class,
            '/front/holiday.php' => \Holiday::class,
            '/front/imageformat.form.php' => \ImageFormat::class,
            '/front/imageformat.php' => \ImageFormat::class,
            '/front/imageresolution.form.php' => \ImageResolution::class,
            '/front/imageresolution.php' => \ImageResolution::class,
            '/front/interfacetype.form.php' => \InterfaceType::class,
            '/front/interfacetype.php' => \InterfaceType::class,
            '/front/ipnetwork.form.php' => \IPNetwork::class,
            '/front/ipnetwork.php' => \IPNetwork::class,
            '/front/itilcategory.form.php' => \ITILCategory::class,
            '/front/itilcategory.php' => \ITILCategory::class,
            '/front/itilfollowuptemplate.form.php' => \ITILFollowupTemplate::class,
            '/front/itilfollowuptemplate.php' => \ITILFollowupTemplate::class,
            '/front/itilvalidationtemplate.form.php' => \ITILValidationTemplate::class,
            '/front/itilvalidationtemplate.php' => \ITILValidationTemplate::class,
            '/front/knowbaseitemcategory.form.php' => \KnowbaseItemCategory::class,
            '/front/knowbaseitemcategory.php' => \KnowbaseItemCategory::class,
            '/front/lineoperator.form.php' => \LineOperator::class,
            '/front/lineoperator.php' => \LineOperator::class,
            '/front/linetype.form.php' => \LineType::class,
            '/front/linetype.php' => \LineType::class,
            '/front/location.form.php' => \Location::class,
            '/front/location.php' => \Location::class,
            '/front/manufacturer.form.php' => \Manufacturer::class,
            '/front/manufacturer.php' => \Manufacturer::class,
            '/front/monitormodel.form.php' => \MonitorModel::class,
            '/front/monitormodel.php' => \MonitorModel::class,
            '/front/monitortype.form.php' => \MonitorType::class,
            '/front/monitortype.php' => \MonitorType::class,
            '/front/network.form.php' => \Network::class,
            '/front/network.php' => \Network::class,
            '/front/networkequipmentmodel.form.php' => \NetworkEquipmentModel::class,
            '/front/networkequipmentmodel.php' => \NetworkEquipmentModel::class,
            '/front/networkequipmenttype.form.php' => \NetworkEquipmentType::class,
            '/front/networkequipmenttype.php' => \NetworkEquipmentType::class,
            '/front/networkinterface.form.php' => \NetworkInterface::class,
            '/front/networkinterface.php' => \NetworkInterface::class,
            '/front/networkportfiberchanneltype.form.php' => \NetworkPortFiberchannelType::class,
            '/front/networkportfiberchanneltype.php' => \NetworkPortFiberchannelType::class,
            '/front/networkporttype.form.php' => \NetworkPortType::class,
            '/front/networkporttype.php' => \NetworkPortType::class,
            '/front/operatingsystem.form.php' => \OperatingSystem::class,
            '/front/operatingsystem.php' => \OperatingSystem::class,
            '/front/operatingsystemarchitecture.form.php' => \OperatingSystemArchitecture::class,
            '/front/operatingsystemarchitecture.php' => \OperatingSystemArchitecture::class,
            '/front/operatingsystemedition.form.php' => \OperatingSystemEdition::class,
            '/front/operatingsystemedition.php' => \OperatingSystemEdition::class,
            '/front/operatingsystemkernel.form.php' => \OperatingSystemKernel::class,
            '/front/operatingsystemkernel.php' => \OperatingSystemKernel::class,
            '/front/operatingsystemkernelversion.form.php' => \OperatingSystemKernelVersion::class,
            '/front/operatingsystemkernelversion.php' => \OperatingSystemKernelVersion::class,
            '/front/operatingsystemservicepack.form.php' => \OperatingSystemServicePack::class,
            '/front/operatingsystemservicepack.php' => \OperatingSystemServicePack::class,
            '/front/operatingsystemversion.form.php' => \OperatingSystemVersion::class,
            '/front/operatingsystemversion.php' => \OperatingSystemVersion::class,
            '/front/passivedcequipmentmodel.form.php' => \PassiveDCEquipmentModel::class,
            '/front/passivedcequipmentmodel.php' => \PassiveDCEquipmentModel::class,
            '/front/passivedcequipmenttype.form.php' => \PassiveDCEquipmentType::class,
            '/front/passivedcequipmenttype.php' => \PassiveDCEquipmentType::class,
            '/front/pcivendor.form.php' => \PCIVendor::class,
            '/front/pcivendor.php' => \PCIVendor::class,
            '/front/pdumodel.form.php' => \PDUModel::class,
            '/front/pdumodel.php' => \PDUModel::class,
            '/front/pdutype.form.php' => \PDUType::class,
            '/front/pdutype.php' => \PDUType::class,
            '/front/pendingreason.form.php' => \PendingReason::class,
            '/front/pendingreason.php' => \PendingReason::class,
            '/front/peripheralmodel.form.php' => \PeripheralModel::class,
            '/front/peripheralmodel.php' => \PeripheralModel::class,
            '/front/peripheraltype.form.php' => \PeripheralType::class,
            '/front/peripheraltype.php' => \PeripheralType::class,
            '/front/phonemodel.form.php' => \PhoneModel::class,
            '/front/phonemodel.php' => \PhoneModel::class,
            '/front/phonepowersupply.form.php' => \PhonePowerSupply::class,
            '/front/phonepowersupply.php' => \PhonePowerSupply::class,
            '/front/phonetype.form.php' => \PhoneType::class,
            '/front/phonetype.php' => \PhoneType::class,
            '/front/planningeventcategory.form.php' => \PlanningEventCategory::class,
            '/front/planningeventcategory.php' => \PlanningEventCategory::class,
            '/front/planningexternaleventtemplate.form.php' => \PlanningExternalEventTemplate::class,
            '/front/planningexternaleventtemplate.php' => \PlanningExternalEventTemplate::class,
            '/front/plug.form.php' => \Plug::class,
            '/front/plug.php' => \Plug::class,
            '/front/printermodel.form.php' => \PrinterModel::class,
            '/front/printermodel.php' => \PrinterModel::class,
            '/front/printertype.form.php' => \PrinterType::class,
            '/front/printertype.php' => \PrinterType::class,
            '/front/problemtemplate.form.php' => \ProblemTemplate::class,
            '/front/problemtemplate.php' => \ProblemTemplate::class,
            '/front/projectstate.form.php' => \ProjectState::class,
            '/front/projectstate.php' => \ProjectState::class,
            '/front/projecttasktemplate.form.php' => \ProjectTaskTemplate::class,
            '/front/projecttasktemplate.php' => \ProjectTaskTemplate::class,
            '/front/projecttasktype.form.php' => \ProjectTaskType::class,
            '/front/projecttasktype.php' => \ProjectTaskType::class,
            '/front/projecttype.form.php' => \ProjectType::class,
            '/front/projecttype.php' => \ProjectType::class,
            '/front/rackmodel.form.php' => \RackModel::class,
            '/front/rackmodel.php' => \RackModel::class,
            '/front/racktype.form.php' => \RackType::class,
            '/front/racktype.php' => \RackType::class,
            '/front/recurrentchange.form.php' => \RecurrentChange::class,
            '/front/recurrentchange.php' => \RecurrentChange::class,
            '/front/requesttype.form.php' => \RequestType::class,
            '/front/requesttype.php' => \RequestType::class,
            '/front/rulerightparameter.form.php' => \RuleRightParameter::class,
            '/front/rulerightparameter.php' => \RuleRightParameter::class,
            '/front/socketmodel.form.php' => \Glpi\SocketModel::class,
            '/front/socketmodel.php' => \Glpi\SocketModel::class,
            '/front/softwarecategory.form.php' => \SoftwareCategory::class,
            '/front/softwarecategory.php' => \SoftwareCategory::class,
            '/front/softwarelicensetype.form.php' => \SoftwareLicenseType::class,
            '/front/softwarelicensetype.php' => \SoftwareLicenseType::class,
            '/front/solutiontemplate.form.php' => \SolutionTemplate::class,
            '/front/solutiontemplate.php' => \SolutionTemplate::class,
            '/front/solutiontype.form.php' => \SolutionType::class,
            '/front/solutiontype.php' => \SolutionType::class,
            '/front/ssovariable.form.php' => \SsoVariable::class,
            '/front/ssovariable.php' => \SsoVariable::class,
            '/front/state.form.php' => \State::class,
            '/front/state.php' => \State::class,
            '/front/suppliertype.form.php' => \SupplierType::class,
            '/front/suppliertype.php' => \SupplierType::class,
            '/front/taskcategory.form.php' => \TaskCategory::class,
            '/front/taskcategory.php' => \TaskCategory::class,
            '/front/tasktemplate.form.php' => \TaskTemplate::class,
            '/front/tasktemplate.php' => \TaskTemplate::class,
            '/front/ticketrecurrent.form.php' => \TicketRecurrent::class,
            '/front/ticketrecurrent.php' => \TicketRecurrent::class,
            '/front/tickettemplate.form.php' => \TicketTemplate::class,
            '/front/tickettemplate.php' => \TicketTemplate::class,
            '/front/usbvendor.form.php' => \USBVendor::class,
            '/front/usbvendor.php' => \USBVendor::class,
            '/front/usercategory.form.php' => \UserCategory::class,
            '/front/usercategory.php' => \UserCategory::class,
            '/front/usertitle.form.php' => \UserTitle::class,
            '/front/usertitle.php' => \UserTitle::class,
            '/front/virtualmachinestate.form.php' => \VirtualMachineState::class,
            '/front/virtualmachinestate.php' => \VirtualMachineState::class,
            '/front/virtualmachinesystem.form.php' => \VirtualMachineSystem::class,
            '/front/virtualmachinesystem.php' => \VirtualMachineSystem::class,
            '/front/virtualmachinetype.form.php' => \VirtualMachineType::class,
            '/front/virtualmachinetype.php' => \VirtualMachineType::class,
            '/front/vlan.form.php' => \Vlan::class,
            '/front/vlan.php' => \Vlan::class,
            '/front/wifinetwork.form.php' => \WifiNetwork::class,
            '/front/wifinetwork.php' => \WifiNetwork::class,
            '/front/webhookcategory.form.php' => \WebhookCategory::class,
            '/front/webhookcategory.php' => \WebhookCategory::class,
        ];

        foreach ($list as $path => $class) {
            yield $path => [$path, $class];
        }

        $devices_paths = [
            '/front/device.php',
            '/front/devicemodel.php',
            '/front/devicetype.php',
            '/front/device.form.php',
            '/front/devicemodel.form.php',
            '/front/devicetype.form.php',
        ];

        $devices_names = [
            \DeviceBattery::class,
            \DeviceCamera::class,
            \DeviceCase::class,
            \DeviceControl::class,
            \DeviceDrive::class,
            \DeviceFirmware::class,
            \DeviceGeneric::class,
            \DeviceGraphicCard::class,
            \DeviceHardDrive::class,
            \DeviceMemory::class,
            \DeviceMotherboard::class,
            \DeviceNetworkCard::class,
            \DevicePci::class,
            \DevicePowerSupply::class,
            \DeviceProcessor::class,
            \DeviceSensor::class,
            \DeviceSimcard::class,
            \DeviceSoundCard::class,
        ];

        foreach ($devices_names as $device) {
            foreach ($devices_paths as $path) {
                $fullPath = $path . '?itemtype=' . $device;
                yield $fullPath => [$fullPath, $device];
            }
        }
    }

    #[RunInSeparateProcess]
    #[DataProvider('provideDropdownClassesForPlugin')]
    public function testFindDropdownClassForPlugin(string $path_info, string $class): void
    {
        $listener = new LegacyDropdownRouteListener();
        $request = $this->createRequest($path_info);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame(DropdownController::class, $request->attributes->get('_controller'));
        self::assertSame($class, $request->attributes->get('class'));
    }

    public static function provideDropdownClassesForPlugin(): array
    {
        return [
            '/plugins/tester/front/mylegacyclass.php' => [
                '/plugins/tester/front/mylegacyclass.php',
                \PluginTesterMyLegacyClass::class,
            ],
            '/plugins/tester/front/mypseudopsr4class.php' => [
                '/plugins/tester/front/mypseudopsr4class.php',
                \PluginTesterMyPseudoPsr4Class::class,
            ],
            '/plugins/tester/front/mypsr4class.php' => [
                '/plugins/tester/front/mypsr4class.php',
                \GlpiPlugin\Tester\MyPsr4Class::class,
            ],
        ];
    }

    private function createRequest(string $path_info): Request
    {
        $req = Request::create($path_info);

        $req->server->set('REQUEST_URI', $path_info);
        $req->server->set('PATH_INFO', $path_info);

        return $req;
    }
}
