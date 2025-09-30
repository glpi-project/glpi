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

namespace tests\units\Glpi\Kernel\Listener\RequestListener;

use Glpi\Asset\AssetDefinition;
use Glpi\Controller\DropdownFormController;
use Glpi\Controller\GenericListController;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Event;
use Glpi\Form\Form;
use Glpi\Kernel\Listener\RequestListener\LegacyItemtypeRouteListener;
use Glpi\Socket;
use Glpi\SocketModel;
use GlpiPlugin\Tester\MyPsr4Class;
use GlpiPlugin\Tester\MyPsr4Dropdown;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

final class LegacyItemtypeRouteListenerTest extends TestCase
{
    #[DataProvider('provideItemtypes')]
    public function testFindGlpiClass(string $path_info, string $expected_class_name): void
    {
        $listener = new LegacyItemtypeRouteListener($this->getUrlMatcherMock());
        $request = $this->createRequest($path_info);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        if (\str_contains($path_info, '.form.php')) {
            self::assertSame(DropdownFormController::class, $request->attributes->get('_controller'));
        } else {
            self::assertSame(GenericListController::class, $request->attributes->get('_controller'));
        }
        self::assertSame($expected_class_name, $request->attributes->get('class'));
    }

    public static function provideItemtypes(): iterable
    {
        $list = [
            '/front/agent.php' => \Agent::class,
            '/front/allassets.php' => \AllAssets::class,
            '/front/appliance.php' => \Appliance::class,
            '/front/applianceenvironment.form.php' => \ApplianceEnvironment::class,
            '/front/applianceenvironment.php' => \ApplianceEnvironment::class,
            '/front/appliancetype.form.php' => \ApplianceType::class,
            '/front/appliancetype.php' => \ApplianceType::class,
            '/front/asset/assetdefinition.php' => AssetDefinition::class,
            '/front/authldap.php' => \AuthLDAP::class,
            '/front/authmail.php' => \AuthMail::class,
            '/front/autoupdatesystem.form.php' => \AutoUpdateSystem::class,
            '/front/autoupdatesystem.php' => \AutoUpdateSystem::class,
            '/front/blacklist.form.php' => \Blacklist::class,
            '/front/blacklist.php' => \Blacklist::class,
            '/front/blacklistedmailcontent.form.php' => \BlacklistedMailContent::class,
            '/front/blacklistedmailcontent.php' => \BlacklistedMailContent::class,
            '/front/budget.php' => \Budget::class,
            '/front/budgettype.form.php' => \BudgetType::class,
            '/front/budgettype.php' => \BudgetType::class,
            '/front/businesscriticity.form.php' => \BusinessCriticity::class,
            '/front/businesscriticity.php' => \BusinessCriticity::class,
            '/front/cable.php' => \Cable::class,
            '/front/cablestrand.form.php' => \CableStrand::class,
            '/front/cablestrand.php' => \CableStrand::class,
            '/front/cabletype.form.php' => \CableType::class,
            '/front/cabletype.php' => \CableType::class,
            '/front/calendar.form.php' => \Calendar::class,
            '/front/calendar.php' => \Calendar::class,
            '/front/cartridgeitem.php' => \CartridgeItem::class,
            '/front/cartridgeitemtype.form.php' => \CartridgeItemType::class,
            '/front/cartridgeitemtype.php' => \CartridgeItemType::class,
            '/front/certificate.php' => \Certificate::class,
            '/front/certificatetype.form.php' => \CertificateType::class,
            '/front/certificatetype.php' => \CertificateType::class,
            '/front/change.php' => \Change::class,
            '/front/changetemplate.form.php' => \ChangeTemplate::class,
            '/front/changetemplate.php' => \ChangeTemplate::class,
            '/front/cluster.php' => \Cluster::class,
            '/front/clustertype.form.php' => \ClusterType::class,
            '/front/clustertype.php' => \ClusterType::class,
            '/front/computer.php' => \Computer::class,
            '/front/computermodel.form.php' => \ComputerModel::class,
            '/front/computermodel.php' => \ComputerModel::class,
            '/front/computertype.form.php' => \ComputerType::class,
            '/front/computertype.php' => \ComputerType::class,
            '/front/consumableitemtype.form.php' => \ConsumableItemType::class,
            '/front/consumableitemtype.php' => \ConsumableItemType::class,
            '/front/contact.php' => \Contact::class,
            '/front/contacttype.form.php' => \ContactType::class,
            '/front/contacttype.php' => \ContactType::class,
            '/front/contract.php' => \Contract::class,
            '/front/contracttype.form.php' => \ContractType::class,
            '/front/contracttype.php' => \ContractType::class,
            '/front/crontask.php' => \CronTask::class,
            '/front/database.php' => \Database::class,
            '/front/databaseinstance.php' => \DatabaseInstance::class,
            '/front/databaseinstancecategory.form.php' => \DatabaseInstanceCategory::class,
            '/front/databaseinstancecategory.php' => \DatabaseInstanceCategory::class,
            '/front/databaseinstancetype.form.php' => \DatabaseInstanceType::class,
            '/front/databaseinstancetype.php' => \DatabaseInstanceType::class,
            '/front/datacenter.php' => \Datacenter::class,
            '/front/dcroom.php' => \DCRoom::class,
            '/front/defaultfilter.php' => \DefaultFilter::class,
            '/front/document.php' => \Document::class,
            '/front/documentcategory.form.php' => \DocumentCategory::class,
            '/front/documentcategory.php' => \DocumentCategory::class,
            '/front/documenttype.form.php' => \DocumentType::class,
            '/front/documenttype.php' => \DocumentType::class,
            '/front/domain.php' => \Domain::class,
            '/front/domainrecord.php' => \DomainRecord::class,
            '/front/domainrecordtype.form.php' => \DomainRecordType::class,
            '/front/domainrecordtype.php' => \DomainRecordType::class,
            '/front/domainrelation.form.php' => \DomainRelation::class,
            '/front/domainrelation.php' => \DomainRelation::class,
            '/front/domaintype.form.php' => \DomainType::class,
            '/front/domaintype.php' => \DomainType::class,
            '/front/dropdown/dropdowndefinition.php' => DropdownDefinition::class,
            '/front/enclosure.php' => \Enclosure::class,
            '/front/enclosuremodel.form.php' => \EnclosureModel::class,
            '/front/enclosuremodel.php' => \EnclosureModel::class,
            '/front/entity.form.php' => \Entity::class,
            '/front/entity.php' => \Entity::class,
            '/front/event.php' => Event::class,
            '/front/fieldblacklist.form.php' => \Fieldblacklist::class,
            '/front/fieldblacklist.php' => \Fieldblacklist::class,
            '/front/fieldunicity.form.php' => \FieldUnicity::class,
            '/front/fieldunicity.php' => \FieldUnicity::class,
            '/front/filesystem.form.php' => \Filesystem::class,
            '/front/filesystem.php' => \Filesystem::class,
            '/front/form/form.php' => Form::class,
            '/front/fqdn.form.php' => \FQDN::class,
            '/front/fqdn.php' => \FQDN::class,
            '/front/group.php' => \Group::class,
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
            '/front/line.php' => \Line::class,
            '/front/lineoperator.form.php' => \LineOperator::class,
            '/front/lineoperator.php' => \LineOperator::class,
            '/front/linetype.form.php' => \LineType::class,
            '/front/linetype.php' => \LineType::class,
            '/front/link.php' => \Link::class,
            '/front/location.form.php' => \Location::class,
            '/front/location.php' => \Location::class,
            '/front/lockedfield.php' => \Lockedfield::class,
            '/front/mailcollector.php' => \MailCollector::class,
            '/front/manufacturer.form.php' => \Manufacturer::class,
            '/front/manufacturer.php' => \Manufacturer::class,
            '/front/monitor.php' => \Monitor::class,
            '/front/monitormodel.form.php' => \MonitorModel::class,
            '/front/monitormodel.php' => \MonitorModel::class,
            '/front/monitortype.form.php' => \MonitorType::class,
            '/front/monitortype.php' => \MonitorType::class,
            '/front/network.form.php' => \Network::class,
            '/front/network.php' => \Network::class,
            '/front/networkequipment.php' => \NetworkEquipment::class,
            '/front/networkequipmentmodel.form.php' => \NetworkEquipmentModel::class,
            '/front/networkequipmentmodel.php' => \NetworkEquipmentModel::class,
            '/front/networkequipmenttype.form.php' => \NetworkEquipmentType::class,
            '/front/networkequipmenttype.php' => \NetworkEquipmentType::class,
            '/front/networkinterface.form.php' => \NetworkInterface::class,
            '/front/networkinterface.php' => \NetworkInterface::class,
            '/front/networkname.php' => \NetworkName::class,
            '/front/networkportfiberchanneltype.form.php' => \NetworkPortFiberchannelType::class,
            '/front/networkportfiberchanneltype.php' => \NetworkPortFiberchannelType::class,
            '/front/networkporttype.form.php' => \NetworkPortType::class,
            '/front/networkporttype.php' => \NetworkPortType::class,
            '/front/notification.php' => \Notification::class,
            '/front/notificationtemplate.php' => \NotificationTemplate::class,
            '/front/notimportedemail.php' => \NotImportedEmail::class,
            '/front/oauthclient.php' => \OAuthClient::class,
            '/front/ola.php' => \OLA::class,
            '/front/olalevel.php' => \OlaLevel::class,
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
            '/front/passivedcequipment.php' => \PassiveDCEquipment::class,
            '/front/passivedcequipmentmodel.form.php' => \PassiveDCEquipmentModel::class,
            '/front/passivedcequipmentmodel.php' => \PassiveDCEquipmentModel::class,
            '/front/passivedcequipmenttype.form.php' => \PassiveDCEquipmentType::class,
            '/front/passivedcequipmenttype.php' => \PassiveDCEquipmentType::class,
            '/front/pcivendor.form.php' => \PCIVendor::class,
            '/front/pcivendor.php' => \PCIVendor::class,
            '/front/pdu.php' => \PDU::class,
            '/front/pdumodel.form.php' => \PDUModel::class,
            '/front/pdumodel.php' => \PDUModel::class,
            '/front/pdutype.form.php' => \PDUType::class,
            '/front/pdutype.php' => \PDUType::class,
            '/front/pendingreason.form.php' => \PendingReason::class,
            '/front/pendingreason.php' => \PendingReason::class,
            '/front/peripheral.php' => \Peripheral::class,
            '/front/peripheralmodel.form.php' => \PeripheralModel::class,
            '/front/peripheralmodel.php' => \PeripheralModel::class,
            '/front/peripheraltype.form.php' => \PeripheralType::class,
            '/front/peripheraltype.php' => \PeripheralType::class,
            '/front/phone.php' => \Phone::class,
            '/front/phonemodel.form.php' => \PhoneModel::class,
            '/front/phonemodel.php' => \PhoneModel::class,
            '/front/phonepowersupply.form.php' => \PhonePowerSupply::class,
            '/front/phonepowersupply.php' => \PhonePowerSupply::class,
            '/front/phonetype.form.php' => \PhoneType::class,
            '/front/phonetype.php' => \PhoneType::class,
            '/front/planningeventcategory.form.php' => \PlanningEventCategory::class,
            '/front/planningeventcategory.php' => \PlanningEventCategory::class,
            '/front/planningexternalevent.php' => \PlanningExternalEvent::class,
            '/front/planningexternaleventtemplate.form.php' => \PlanningExternalEventTemplate::class,
            '/front/planningexternaleventtemplate.php' => \PlanningExternalEventTemplate::class,
            '/front/plug.form.php' => \Plug::class,
            '/front/plug.php' => \Plug::class,
            '/front/printer.php' => \Printer::class,
            '/front/printermodel.form.php' => \PrinterModel::class,
            '/front/printermodel.php' => \PrinterModel::class,
            '/front/printertype.form.php' => \PrinterType::class,
            '/front/printertype.php' => \PrinterType::class,
            '/front/problem.php' => \Problem::class,
            '/front/problemtemplate.form.php' => \ProblemTemplate::class,
            '/front/problemtemplate.php' => \ProblemTemplate::class,
            '/front/profile.php' => \Profile::class,
            '/front/project.php' => \Project::class,
            '/front/projectstate.form.php' => \ProjectState::class,
            '/front/projectstate.php' => \ProjectState::class,
            '/front/projecttask.php' => \ProjectTask::class,
            '/front/projecttasktemplate.form.php' => \ProjectTaskTemplate::class,
            '/front/projecttasktemplate.php' => \ProjectTaskTemplate::class,
            '/front/projecttasktype.form.php' => \ProjectTaskType::class,
            '/front/projecttasktype.php' => \ProjectTaskType::class,
            '/front/projecttype.form.php' => \ProjectType::class,
            '/front/projecttype.php' => \ProjectType::class,
            '/front/queuednotification.php' => \QueuedNotification::class,
            '/front/queuedwebhook.php' => \QueuedWebhook::class,
            '/front/rack.php' => \Rack::class,
            '/front/rackmodel.form.php' => \RackModel::class,
            '/front/rackmodel.php' => \RackModel::class,
            '/front/racktype.form.php' => \RackType::class,
            '/front/racktype.php' => \RackType::class,
            '/front/recurrentchange.form.php' => \RecurrentChange::class,
            '/front/recurrentchange.php' => \RecurrentChange::class,
            '/front/refusedequipment.php' => \RefusedEquipment::class,
            '/front/reminder.php' => \Reminder::class,
            '/front/requesttype.form.php' => \RequestType::class,
            '/front/requesttype.php' => \RequestType::class,
            '/front/rssfeed.php' => \RSSFeed::class,
            '/front/rulerightparameter.form.php' => \RuleRightParameter::class,
            '/front/rulerightparameter.php' => \RuleRightParameter::class,
            '/front/sla.php' => \SLA::class,
            '/front/slalevel.php' => \SlaLevel::class,
            '/front/slm.php' => \SLM::class,
            '/front/snmpcredential.php' => \SNMPCredential::class,
            '/front/socket.php' => Socket::class,
            '/front/socketmodel.form.php' => SocketModel::class,
            '/front/socketmodel.php' => SocketModel::class,
            '/front/software.php' => \Software::class,
            '/front/softwarecategory.form.php' => \SoftwareCategory::class,
            '/front/softwarecategory.php' => \SoftwareCategory::class,
            '/front/softwarelicense.php' => \SoftwareLicense::class,
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
            '/front/supplier.php' => \Supplier::class,
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
            '/front/transfer.php' => \Transfer::class,
            '/front/unmanaged.php' => \Unmanaged::class,
            '/front/usbvendor.form.php' => \USBVendor::class,
            '/front/usbvendor.php' => \USBVendor::class,
            '/front/user.php' => \User::class,
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
            '/front/webhook.php' => \Webhook::class,
            '/front/webhookcategory.form.php' => \WebhookCategory::class,
            '/front/webhookcategory.php' => \WebhookCategory::class,
            '/front/wifinetwork.form.php' => \WifiNetwork::class,
            '/front/wifinetwork.php' => \WifiNetwork::class,
        ];

        foreach ($list as $path => $class) {
            yield $path => [$path, $class];
        }

        $devices_classes = [
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

        foreach ($devices_classes as $device_class) {
            $devices_paths = [
                sprintf('/front/device.php?itemtype=%s', $device_class)      => $device_class,
                sprintf('/front/device.form.php?itemtype=%s', $device_class) => $device_class,
            ];

            $model_class = $device_class . 'Model';
            if (\class_exists($model_class)) {
                $devices_paths[sprintf('/front/devicemodel.php?itemtype=%s', $model_class)] = $model_class;
                $devices_paths[sprintf('/front/devicemodel.form.php?itemtype=%s', $model_class)] = $model_class;
            }

            $type_class = $device_class . 'Type';
            if (\class_exists($type_class)) {
                $devices_paths[sprintf('/front/devicetype.php?itemtype=%s', $type_class)] = $type_class;
                $devices_paths[sprintf('/front/devicetype.form.php?itemtype=%s', $type_class)] = $type_class;
            }

            foreach ($devices_paths as $path => $class) {
                yield $path => [$path, $class];
            }
        }
    }

    #[RunInSeparateProcess]
    #[DataProvider('provideClassesForPlugin')]
    public function testFindClassForPlugin(string $path_info, string $class): void
    {
        $listener = new LegacyItemtypeRouteListener($this->getUrlMatcherMock());
        $request = $this->createRequest($path_info);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame(GenericListController::class, $request->attributes->get('_controller'));
        self::assertSame($class, $request->attributes->get('class'));
    }

    public static function provideClassesForPlugin(): iterable
    {
        yield [
            'path_info' => '/plugins/tester/front/mylegacyclass.php',
            'class'     => \PluginTesterMyLegacyClass::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/mylegacydropdown.php',
            'class'     => \PluginTesterMyLegacyDropdown::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/mypseudopsr4class.php',
            'class'     => \PluginTesterMyPseudoPsr4Class::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/mypseudopsr4dropdown.php',
            'class'     => \PluginTesterMyPseudoPsr4Dropdown::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/mypsr4class.php',
            'class'     => MyPsr4Class::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/mypsr4dropdown.php',
            'class'     => MyPsr4Dropdown::class,
        ];
        yield [
            'path_info' => '/plugins/tester/front/computer.php',
            'class'     => \GlpiPlugin\Tester\Computer::class,
        ];
    }

    private function createRequest(string $path_info): Request
    {
        $req = Request::create($path_info);

        $req->server->set('REQUEST_URI', $path_info);
        $req->server->set('PATH_INFO', $path_info);

        return $req;
    }

    private function getUrlMatcherMock(): UrlMatcherInterface
    {
        $mock = $this->createMock(UrlMatcherInterface::class);
        $mock->method('match')->willThrowException(new \Exception());

        return $mock;
    }
}
