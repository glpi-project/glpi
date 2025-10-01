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
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

namespace Glpi\Inventory;

use Agent;
use CommonDevice;
use CommonGLPI;
use Computer;
use ComputerType;
use Config;
use DeviceBattery;
use DeviceControl;
use DeviceDrive;
use DeviceGraphicCard;
use DeviceHardDrive;
use DeviceMemory;
use DeviceNetworkCard;
use DevicePowerSupply;
use DeviceProcessor;
use DeviceSimcard;
use DeviceSoundCard;
use Dropdown;
use Entity;
use finfo;
use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\StateInterface;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\ArrayNormalizer;
use GLPIKey;
use Html;
use Item_Disk;
use Item_Environment;
use Item_Process;
use ItemAntivirus;
use ItemVirtualMachine;
use Monitor;
use NetworkPort;
use NetworkPortType;
use Peripheral;
use Plugin;
use Printer;
use Rule;
use Session;
use Software;
use State;
use Throwable;
use Toolbox;
use Unmanaged;
use wapmorgan\UnifiedArchive\UnifiedArchive;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\simplexml_load_string;

/**
 * Inventory configuration
 * @property int $import_software
 * @property int $import_volume
 * @property int $import_antivirus
 * @property int $import_registry
 * @property int $import_process
 * @property int $import_vm
 * @property int $import_monitor_on_partial_sn
 * @property int $import_unmanaged
 * @property int $component_processor
 * @property int $component_memory
 * @property int $component_harddrive
 * @property int $component_networkcard
 * @property int $component_graphiccard
 * @property int $component_soundcard
 * @property int $component_drive
 * @property int $component_networkdrive
 * @property int $component_networkcardvirtual
 * @property int $component_control
 * @property int $component_battery
 * @property int $component_simcard
 * @property int $states_id_default
 * @property int $entities_id_default
 * @property int $location
 * @property int $group
 * @property int $vm_type
 * @property int $vm_components
 * @property int $vm_as_computer
 * @property int $component_removablemedia
 * @property int $component_powersupply
 * @property int $inventory_frequency
 * @property int $import_monitor
 * @property int $import_printer
 * @property int $import_peripheral
 * @property int $import_env
 * @property string $auth_required
 * @property bool $enabled_inventory
 */
class Conf extends CommonGLPI
{
    private $currents = [];

    public const STALE_AGENT_ACTION_CLEAN = 0;

    public const STALE_AGENT_ACTION_STATUS = 1;

    public const STALE_AGENT_ACTION_TRASHBIN = 2;

    public const CLIENT_CREDENTIALS = 'client_credentials';

    public const BASIC_AUTH = 'basic_auth';

    public static $rightname = 'inventory';

    public const IMPORTFROMFILE     = 1024;
    public const UPDATECONFIG       = 2048;

    /**
     * Display form for import the XML
     *
     * @return void
     */
    public function showUploadForm()
    {
        TemplateRenderer::getInstance()->display('pages/admin/inventory/upload_form.html.twig', [
            'inventory_extensions' => $this->knownInventoryExtensions(),
        ]);
    }

    /**
     * Accepted file extension for inventories
     *
     * @return array
     */
    public function knownInventoryExtensions(): array
    {
        return [
            'json',
            'xml',
            'ocs',
        ];
    }

    /**
     * Import inventory files
     *
     * @param array $files[filename => filepath] Files to import
     *
     * @return array [filename => [success => bool, message => string, asset => CommonDBTM]]
     */
    public function importFiles($files): array
    {
        $result = [];

        foreach ($files as $filename => $filepath) {
            if (UnifiedArchive::canOpen($filepath) && $archive = UnifiedArchive::open($filepath)) {
                $unarchived_files = $archive->getFiles();
                foreach ($unarchived_files as $inventory_file) {
                    if ($this->isInventoryFile($inventory_file)) {
                        $contents = $archive->getFileContent($inventory_file);
                        $result[$filename . '/' . basename($inventory_file)] = $this->importContentFile(null, $contents);
                    }
                }
            } elseif ($this->isInventoryFile($filename)) {
                $result[$filename] = $this->importContentFile($filepath, file_get_contents($filepath));
            } else {
                $result[$filename] = [
                    'success' => false,
                    'message' => sprintf(
                        __('File has not been imported: `%s`.'),
                        sprintf('`%s` format is not supported', pathinfo($filename, PATHINFO_EXTENSION))
                    ),
                    'items'   => [],
                    'request' => null,
                ];
            }
        }

        return $result;
    }

    /**
     * Is an inventory known file
     *
     * @return boolean
     */
    public function isInventoryFile($name): bool
    {
        return (bool) preg_match('/\.(' . implode('|', $this->knownInventoryExtensions()) . ')/i', $name);
    }

    /**
     * Import contents of a file
     *
     * @param ?string $path              File path
     * @param string  $contents          File contents
     *
     * @return array [success => bool, message => ?string, items => CommonDBTM[], request => Glpi\Inventory\Request]
     */
    protected function importContentFile($path, $contents): array
    {
        $inventory_request = new Request();
        $result = [
            'success' => false,
            'message' => null,
            'items'   => [],
            'request' => null,
        ];

        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = ($path === null ? $finfo->buffer($contents) : $finfo->file($path));
            switch ($mime) {
                case 'text/xml':
                    $mime = 'application/xml';
                    break;
            }

            $inventory_request->handleContentType($mime);
            $inventory_request->setLocal()->handleRequest($contents);
            if ($inventory_request->inError()) {
                $response = $inventory_request->getResponse();
                if ($inventory_request->getMode() === AbstractRequest::JSON_MODE) {
                    $json = json_decode($inventory_request->getResponse());
                    $response = $json->message;
                } else {
                    $xml = simplexml_load_string($response);
                    $response = $xml->ERROR;
                }
                $response = str_replace('&nbsp;', ' ', $response);
                $result['message'] = sprintf(__('File has not been imported: `%s`.'), $response);
            } else {
                $result = [
                    'success' => true,
                    'message' => __('File has been successfully imported.'),
                    'items'   => $inventory_request->getInventory()->getItems(),
                ];
            }
        } catch (Throwable $e) {
            $result = [
                'success' => false,
                'message' => sprintf(__('An error occurs during import: `%s`.'), $e->getMessage()),
                'items'   => $inventory_request->getInventory()->getItems(),
            ];
        }

        $result['request'] = $inventory_request;
        return $result;
    }

    /**
     * Get possible actions for stale agents
     *
     * @return array
     */
    public static function getStaleAgentActions(): array
    {
        return [
            self::STALE_AGENT_ACTION_CLEAN  => __('Clean agents'),
            self::STALE_AGENT_ACTION_STATUS => __('Change the status'),
            self::STALE_AGENT_ACTION_TRASHBIN => __('Put asset in trashbin'),
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof self) {
            $tabs = [];
            if (Session::haveRight(self::$rightname, self::UPDATECONFIG)) {
                $tabs[1] = self::createTabEntry(__('Configuration'), 0, $item::getType());
            }
            if ($item->enabled_inventory && Session::haveRight(self::$rightname, self::IMPORTFROMFILE)) {
                $tabs[2] = self::createTabEntry(__('Import from file'), icon: 'ti ti-upload');
            }
            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == self::class) {
            /** @var self $item */
            switch ($tabnum) {
                case 1:
                    $item->showConfigForm();
                    break;

                case 2:
                    if ($item->enabled_inventory) {
                        $item->showUploadForm();
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Print the config form for display
     *
     * @return true (Always true)
     * @copyright 2010-2022 by the FusionInventory Development Team. (Agent cleanup section)
     **/
    public function showConfigForm()
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        $config = Config::getConfigurationValues('inventory');
        $canedit = Config::canUpdate();
        $rand = mt_rand();

        if ($canedit) {
            echo "<form name='form' action='" . htmlescape($CFG_GLPI['root_doc']) . "/Inventory/Configuration/Store' method='post'>";
        }

        echo "<div class='center spaced' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr>";

        echo "<th>";
        echo "<label for='enabled_inventory'>";
        echo __s('Enable inventory');
        echo "</label>";
        echo "</th>";
        echo "<td colspan='3'>";
        echo '<div class="row">';
        echo '<div class="col-md-auto d-flex align-items-center">';
        Html::showCheckbox([
            'name'      => 'enabled_inventory',
            'id'        => 'enabled_inventory',
            'checked'   => $config['enabled_inventory'],
        ]);
        echo '</div>';
        echo '<div class="col">';
        if ($config['enabled_inventory'] == false) {
            echo "<div class='alert alert-info d-flex align-items-center m-0' role='alert'>";
            echo "<span class='ms-2'>";
            echo "<i class='ti ti-info-circle'> </i> &nbsp;";
            echo __s("The inventory is disabled, remember to activate it if necessary");
            echo "</span>";
            echo "</div>";
        } else {
            echo '</div>';
            echo '</div>';
            echo "</td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<i class='ti ti-cloud-lock me-2'></i>";
            echo "<label for='auth'>" . __s('Authorization header') . "</label>";
            echo "</td>";
            echo "<td>";
            Dropdown::showFromArray('auth_required', [
                'none' => __('None'),
                self::CLIENT_CREDENTIALS => __s('OAuth - Client credentials'),
                self::BASIC_AUTH => __s('Basic Authentication'),
            ], [
                'value' => $config['auth_required'] ?? 'none',
            ]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1' id='basic_auth_login_row'>";
            echo "<td>";
            echo "<i class='ti ti-abc me-2'></i>";
            echo "<label for='basic_auth_login'>" . __s('Login') . "</label>";
            echo "<span class='required'>*</span>";
            echo "</label>";
            echo "</td>";
            echo "<td>";
            echo Html::input("basic_auth_login", [
                "value" => $config["basic_auth_login"],
            ]);
            echo "</td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1' id='basic_auth_password_row'>";
            echo "<td>";
            echo "<i class='ti ti-password me-2'></i>";
            echo "<label for='basic_auth_password'>" . __s('Password') . "</label>";
            echo "<span class='required'>*</span>";
            echo "</label>";
            echo "</td>";
            echo "<td>";
            echo Html::input("basic_auth_password", [
                "value" => (new GLPIKey())->decrypt($config['basic_auth_password']),
                "type" => "password",
            ]);
            echo "</td>";
            echo "</tr>";
            echo Html::scriptBlock("
                function toggleDisplayLoginInputs(select) {
                    let displayedInputs = false;
                    const selectedValue = $(select).val();
                    if (selectedValue == '" . self::BASIC_AUTH . "') {
                        displayedInputs = true;
                    }
                    $('#basic_auth_login_row').toggle(displayedInputs);
                    $('#basic_auth_password_row').toggle(displayedInputs);
                }

                const selectAuthHeader = $(`select[name='auth_required']`);
                selectAuthHeader.change(function() {
                    toggleDisplayLoginInputs(this);
                });

                toggleDisplayLoginInputs(selectAuthHeader);
            ");
            echo "<tr>";
            echo "<th colspan='4'>";
            echo __s('Import options');
            echo "</th>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_volume'>";
            echo Item_Disk::createTabEntry(Item_Disk::getTypeName(Session::getPluralNumber()), 0, Item_Disk::getType());
            echo "</label>";
            echo "</td>";
            echo "<td width='360'>";
            Html::showCheckbox([
                'name'      => 'import_volume',
                'id'        => 'import_volume',
                'checked'   => $config['import_volume'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_networkdrive'>";
            echo DeviceDrive::createTabEntry(__('Network drives'), 0, DeviceDrive::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_networkdrive',
                'id'        => 'component_networkdrive',
                'checked'   => $config['component_networkdrive'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_drive'>";
            echo DeviceDrive::createTabEntry(__('Removable drives'), 0, DeviceDrive::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_removablemedia',
                'id'        => 'component_removablemedia',
                'checked'   => $config['component_removablemedia'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='import_software'>";
            echo Software::createTabEntry(Software::getTypeName(Session::getPluralNumber()), 0, Software::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_software',
                'id'        => 'import_software',
                'checked'   => $config['import_software'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_monitor'>";
            echo Monitor::createTabEntry(Monitor::getTypeName(Session::getPluralNumber()), 0, Monitor::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_monitor',
                'id'        => 'import_monitor',
                'checked'   => $config['import_monitor'],
            ]);
            echo "</td>";

            echo "</td>";
            echo "<td>";
            echo "<label for='import_printer'>";
            echo Printer::createTabEntry(Printer::getTypeName(Session::getPluralNumber()), 0, Printer::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_printer',
                'id'        => 'import_printer',
                'checked'   => $config['import_printer'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_peripheral'>";
            echo Peripheral::createTabEntry(Peripheral::getTypeName(Session::getPluralNumber()), 0, Peripheral::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_peripheral',
                'id'        => 'import_peripheral',
                'checked'   => $config['import_peripheral'],
            ]);
            echo "</td>";

            echo "</td>";
            echo "<td>";
            echo "<label for='import_antivirus'>";
            echo ItemAntivirus::createTabEntry(ItemAntivirus::getTypeName(Session::getPluralNumber()), 0, ItemAntivirus::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_antivirus',
                'id'        => 'import_antivirus',
                'checked'   => $config['import_antivirus'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_process'>";
            echo Item_Process::createTabEntry(Item_Process::getTypeName(Session::getPluralNumber()), 0, Item_Process::getType());
            echo "</label>";
            echo "</td>";
            echo "<td width='360'>";
            Html::showCheckbox([
                'name'      => 'import_process',
                'id'        => 'import_process',
                'checked'   => $config['import_process'],
            ]);
            echo "</td>";
            echo "<td>";
            echo "<label for='import_env'>";
            echo Item_Environment::createTabEntry(Item_Environment::getTypeName(Session::getPluralNumber()), 0, Item_Environment::getType());
            echo "</label>";
            echo "</td>";
            echo "<td width='360'>";
            Html::showCheckbox([
                'name'      => 'import_env',
                'id'        => 'import_env',
                'checked'   => $config['import_env'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_unmanaged'>";
            echo Unmanaged::createTabEntry(Unmanaged::getTypeName(Session::getPluralNumber()), 0, Unmanaged::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_unmanaged',
                'id'        => 'import_unmanaged',
                'checked'   => $config['import_unmanaged'] ?? 1,
            ]);
            echo "</td>";

            echo "</td>";
            echo "<td>";
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='dropdown_states_id_default$rand'>";
            echo State::createTabEntry(__('Default status'), 0, State::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";

            Dropdown::show(
                'State',
                [
                    'name'   => 'states_id_default',
                    'id'     => 'states_id_default',
                    'value'  => $config['states_id_default'],
                    'toadd'  => ['-1' => __('Do not change')],
                    'rand' => $rand,
                ]
            );
            echo "</td>";

            echo "<td><label for='dropdown_inventory_frequency$rand'>";
            echo self::createTabEntry(__('Inventory frequency (in hours)'), 0, self::getType());
            echo "</label></td><td>";
            Dropdown::showNumber(
                "inventory_frequency",
                [
                    'value' => $config['inventory_frequency'],
                    'min' => 1,
                    'max' => 240,
                    'rand' => $rand,
                ]
            );

            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";

            echo "<td>";
            echo "<label for='dropdown_entities_id_default$rand'>";
            echo Entity::createTabEntry(__('Default entity'), 0, Entity::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";

            Dropdown::show(
                'Entity',
                [
                    'name'   => 'entities_id_default',
                    'id'     => 'entities_id_default',
                    'value'  => $config['entities_id_default'] ?? 0,
                    'rand' => $rand,
                ]
            );
            echo "</td>";

            echo "<td>";
            echo "<label for='import_monitor_on_partial_sn'>";
            echo Monitor::createTabEntry(__('Import monitor on serial partial match'), 0, Monitor::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_monitor_on_partial_sn',
                'id'        => 'import_monitor_on_partial_sn',
                'checked'   => $config['import_monitor_on_partial_sn'],
            ]);

            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='4'>";
            echo __s('Related configurations');
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo sprintf(
                '<a href="%s">%s</a>',
                htmlescape(Rule::getSearchURL()),
                Rule::createTabEntry(Rule::getTypeName(Session::getPluralNumber()), 0, Rule::getType())
            );
            echo "</td>";
            echo "<td colspan='2'>";

            echo sprintf(
                '<a href="%s">%s</a>',
                htmlescape(NetworkPortType::getSearchURL()),
                NetworkPort::createTabEntry(NetworkPortType::getTypeName(), 0, NetworkPort::getType())
            );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='4'>";
            echo htmlescape(ItemVirtualMachine::getTypeName(Session::getPluralNumber()));
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='import_vm'>";
            echo ItemVirtualMachine::createTabEntry(__('Import virtual machines'), 0, ItemVirtualMachine::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'import_vm',
                'id'        => 'import_vm',
                'checked'   => $config['import_vm'],
            ]);
            echo "</td>";
            echo "<td>";
            echo "<label for='dropdown_vm_type$rand'>";
            echo ComputerType::createTabEntry(ComputerType::getTypeName(1), 0, ComputerType::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Dropdown::show(
                'ComputerType',
                [
                    'name'   => 'vm_type',
                    'id'     => 'vm_type',
                    'value'  => $config['vm_type'],
                    'rand' => $rand,
                ]
            );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='vm_as_computer'>";
            echo Computer::createTabEntry(__('Create computer for virtual machines'), 0, Computer::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'vm_as_computer',
                'id'        => 'vm_as_computer',
                'checked'   => $config['vm_as_computer'],
            ]);
            echo "</td>";
            echo "<td>";
            echo "<label for='vm_components'>";
            echo ItemVirtualMachine::createTabEntry(__('Create components for virtual machines'), 0, ItemVirtualMachine::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'vm_components',
                'id'        => 'vm_components',
                'checked'   => $config['vm_components'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' style='text-align:right;'>";
            echo "<span class='red'>" . __s('Will attempt to create components from VM information sent from host, do not use if you plan to inventory any VM directly!') . "</span>";
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='4'>";
            echo htmlescape(CommonDevice::getTypeName(Session::getPluralNumber()));
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_processor'>";
            echo DeviceProcessor::createTabEntry(DeviceProcessor::getTypeName(Session::getPluralNumber()), 0, DeviceProcessor::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_processor',
                'id'        => 'component_processor',
                'checked'   => $config['component_processor'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_harddrive'>";
            echo DeviceHardDrive::createTabEntry(DeviceHardDrive::getTypeName(Session::getPluralNumber()), 0, DeviceHardDrive::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_harddrive',
                'id'        => 'component_harddrive',
                'checked'   => $config['component_harddrive'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_memory'>";
            echo DeviceMemory::createTabEntry(DeviceMemory::getTypeName(Session::getPluralNumber()), 0, DeviceMemory::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_memory',
                'id'        => 'component_memory',
                'checked'   => $config['component_memory'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_soundcard'>";
            echo DeviceSoundCard::createTabEntry(DeviceSoundCard::getTypeName(Session::getPluralNumber()), 0, DeviceSoundCard::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_soundcard',
                'id'        => 'component_soundcard',
                'checked'   => $config['component_soundcard'],
            ]);

            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_networkcard'>";
            echo DeviceNetworkCard::createTabEntry(DeviceNetworkCard::getTypeName(Session::getPluralNumber()), 0, DeviceNetworkCard::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_networkcard',
                'id'        => 'component_networkcard',
                'checked'   => $config['component_networkcard'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_networkcardvirtual'>";
            echo DeviceNetworkCard::createTabEntry(__('Virtual network cards'), 0, DeviceNetworkCard::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_networkcardvirtual',
                'id'        => 'component_networkcardvirtual',
                'checked'   => $config['component_networkcardvirtual'],
            ]);

            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_graphiccard'>";
            echo DeviceGraphicCard::createTabEntry(DeviceGraphicCard::getTypeName(Session::getPluralNumber()), 0, DeviceGraphicCard::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_graphiccard',
                'id'        => 'component_graphiccard',
                'checked'   => $config['component_graphiccard'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_simcard'>";
            echo DeviceSimcard::createTabEntry(DeviceSimcard::getTypeName(Session::getPluralNumber()), 0, DeviceSimcard::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_simcard',
                'id'        => 'component_simcard',
                'checked'   => $config['component_simcard'],
            ]);
            echo "</td>";

            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_drive'>";
            echo DeviceDrive::createTabEntry(DeviceDrive::getTypeName(Session::getPluralNumber()), 0, DeviceDrive::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_drive',
                'id'        => 'component_drive',
                'checked'   => $config['component_drive'],
            ]);
            echo "</td>";

            echo "<td>";
            echo "<label for='component_powersupply'>";
            echo DevicePowerSupply::createTabEntry(DevicePowerSupply::getTypeName(Session::getPluralNumber()), 0, DevicePowerSupply::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_powersupply',
                'id'        => 'component_powersupply',
                'checked'   => $config['component_powersupply'],
            ]);
            echo "</td>";

            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "<label for='component_control'>";
            echo DeviceControl::createTabEntry(DeviceControl::getTypeName(Session::getPluralNumber()), 0, DeviceControl::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_control',
                'id'        => 'component_control',
                'checked'   => $config['component_control'],
            ]);
            echo "</td>";

            echo "</td>";
            echo "<td>";
            echo "<label for='component_battery'>";
            echo DeviceBattery::createTabEntry(DeviceBattery::getTypeName(Session::getPluralNumber()), 0, DeviceBattery::getType());
            echo "</label>";
            echo "</td>";
            echo "<td>";
            Html::showCheckbox([
                'name'      => 'component_battery',
                'id'        => 'component_battery',
                'checked'   => $config['component_battery'],
            ]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<th colspan=4 >" . __s('Agent cleanup') . "</th></tr>";
            echo "<tr class='tab_bg_1'><td><label for='dropdown_stale_agents_delay$rand'>";
            echo Agent::createTabEntry(__('Update agents who have not contacted the server for (in days)'), 0, Agent::getType());
            echo "</label></td><td width='20%'>";
            Dropdown::showNumber(
                'stale_agents_delay',
                [
                    'value' => $config['stale_agents_delay'] ?? 0,
                    'min'   => 1,
                    'max'   => 1000,
                    'toadd' => ['0' => __('Disabled')],
                    'rand'  => $rand,
                ]
            );
            echo "</td><td><label for='dropdown_stale_agents_action$rand'>";
            echo Agent::createTabEntry(_n('Action', 'Actions', 1), 0, Agent::getType());
            echo "</label></td><td width='20%'>";
            //action
            $action = self::getDefaults()['stale_agents_action'];
            if (isset($config['stale_agents_action'])) {
                $action = $config['stale_agents_action'];
            }
            Dropdown::showFromArray(
                'stale_agents_action',
                self::getStaleAgentActions(),
                [
                    'values' => importArrayFromDB($action),
                    'on_change' => 'changestatus();',
                    'multiple' => true,
                    'rand' => $rand,
                ]
            );
            //if action == action_status => show blocation else hide blocaction
            echo Html::scriptBlock("
                function changestatus() {
                    const display = $('#dropdown_stale_agents_action$rand').val().includes('" . self::STALE_AGENT_ACTION_STATUS . "') ;
                    $('#bloc_status_action1').toggle(display);
                    $('#bloc_status_action2').toggle(display);
                }
                changestatus();
            ");
            echo "</td>";
            echo "</tr>";
            //blocaction with status
            echo "<tr class='tab_bg_1' style='display:none' id='bloc_status_action1'><td colspan=2></td>";
            echo "<td>";
            echo State::createTabEntry(__('If the asset status is'), 0, State::getType());
            echo "</td>";
            echo "<td width='20%'>";
            $condition = [];
            foreach ($CFG_GLPI['inventory_types'] as $inv_type) {
                $inv_item = getItemForItemtype($inv_type);
                if ($inv_item instanceof StateInterface) {
                    $condition[] = $inv_item->getStateVisibilityCriteria();
                }
            }

            State::dropdown(
                [
                    'name'      => 'stale_agents_status_condition[]',
                    'value'     => importArrayFromDB($config['stale_agents_status_condition'] ?? json_encode(['all'])),
                    'multiple'  => true,
                    'toadd'     => ['all' => __('All')],
                    'condition' => $condition,
                ]
            );
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1' style='display:none' id='bloc_status_action2'><td colspan=2></td>";
            echo "<td>";
            echo State::createTabEntry(__('Status to apply'), 0, State::getType());
            echo "</td>";
            echo "<td width='20%'>";
            State::dropdown(
                [
                    'name'      => 'stale_agents_status',
                    'value'     => $config['stale_agents_status'],
                    'entity'    => $_SESSION['glpiactive_entity'],
                    'toadd'     => [-1 => __('No change')],
                    'condition' => $condition,
                ]
            );
            echo "</td>";
            echo "</tr>";

            $plugin_actions = $PLUGIN_HOOKS[Hooks::STALE_AGENT_CONFIG] ?? [];
            $odd = true;
            $in_row = true;
            /**
             * @var string $plugin
             * @phpstan-var array{label: string, item_action: boolean, render_callback: callable, action_callback: callable}[] $actions
             */
            foreach ($plugin_actions as $plugin => $actions) {
                if (is_array($actions) && Plugin::isPluginActive($plugin)) {
                    foreach ($actions as $action) {
                        if (!is_callable($action['render_callback'] ?? null)) {
                            trigger_error(
                                sprintf('Invalid plugin "%s" render callback for "%s" hook.', $plugin, Hooks::STALE_AGENT_CONFIG),
                                E_USER_WARNING
                            );
                            continue;
                        }

                        if ($odd) {
                            echo "<tr class='tab_bg_1'>";
                        }
                        $field = $action['render_callback']($config);
                        if (!empty($field)) {
                            echo "<td>";
                            echo htmlescape($action['label'] ?? '');
                            echo "</td>";
                            echo "<td width='20%'>";
                            echo $field; // $field is expected to be a safe HTML string provided by the plugin
                            echo "</td>";

                            if (!$odd) {
                                echo "</tr>";
                                $in_row = false;
                            }
                            $odd = !$odd;
                        }
                    }
                }
            }
            if ($in_row) {
                echo "</tr>";
            }
        }

        if ($canedit) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='7' class='center'>";
            echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
            echo "</td></tr>";
        }

        echo "</table></div>";
        Html::closeForm();
        return true;
    }

    /**
     * Save configuration
     *
     * @param array $values Configuration values
     *
     * @return boolean
     */
    public function saveConf(array $values)
    {
        if (!Config::canUpdate()) {
            return false;
        }

        $defaults = self::getDefaults();
        unset($values['_glpi_csrf_token']);

        $ext_configs = array_filter($values, static fn($k, $v) => str_starts_with($v, '_'), ARRAY_FILTER_USE_BOTH);

        $unknown = array_diff_key($values, $defaults, $ext_configs);
        if (count($unknown)) {
            $msg = sprintf(
                __('Some properties are not known: %1$s'),
                implode(', ', array_keys($unknown))
            );
            trigger_error($msg, E_USER_WARNING);
            Session::addMessageAfterRedirect(
                htmlescape($msg),
                false,
                WARNING
            );
        }

        if (
            array_key_exists('stale_agents_status_condition', $values)
            && is_array($values['stale_agents_status_condition'])
            && in_array('all', $values['stale_agents_status_condition'])
        ) {
            // keep only the "All" value
            $values['stale_agents_status_condition'] = ['all'];
        }

        if (isset($values['auth_required']) && $values['auth_required'] === Conf::BASIC_AUTH) {
            if (
                !empty($values['basic_auth_password'])
                && !empty($values['basic_auth_login'])
            ) {
                $values['basic_auth_password'] = (new GLPIKey())->encrypt($values['basic_auth_password']);
            } else {
                Session::addMessageAfterRedirect(
                    __s("Basic Authentication is active. The login and/or password fields are missing."),
                    false,
                    ERROR
                );
                return false;
            }
        }

        if (isset($values['auth_required']) && $values['auth_required'] !== Conf::BASIC_AUTH) {
            $values['basic_auth_login'] = null;
            $values['basic_auth_password'] = null;
        }

        $to_process = [];
        foreach ($defaults as $prop => $default_value) {
            $to_process[$prop] = $values[$prop] ?? $default_value;
            if (is_array($to_process[$prop])) {
                if ($prop == 'stale_agents_action') {
                    $to_process[$prop] = ArrayNormalizer::normalizeValues($to_process[$prop], 'intval');
                } elseif ($prop == 'stale_agents_status_condition') {
                    $to_process[$prop] = ArrayNormalizer::normalizeValues(
                        $to_process[$prop],
                        fn(mixed $val) => $val === 'all' ? 'all' : intval($val)
                    );
                }
                $to_process[$prop] = exportArrayToDB($to_process[$prop]);
            }
        }
        $to_process = array_merge($to_process, $ext_configs);

        Config::setConfigurationValues('inventory', $to_process);
        $this->currents = $to_process;
        return true;
    }

    /**
     * Getter for direct access to conf properties
     *
     * @param string $name Property name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!count($this->currents)) {
            $config = Config::getConfigurationValues('inventory');
            $this->currents = $config;
        }
        if (in_array($name, array_keys(self::getDefaults()))) {
            return $this->currents[$name];
        } elseif ($name == 'fields') {
            //no fields here
            return;
        } else {
            $msg = sprintf(
                __('Property %1$s does not exists!'),
                $name
            );
            trigger_error($msg, E_USER_WARNING);
            Session::addMessageAfterRedirect(
                htmlescape($msg),
                false,
                WARNING
            );
        }
    }

    public function getRights($interface = 'central')
    {
        $values = [ READ => __('Read')];
        $values[self::IMPORTFROMFILE] = ['short' => __('Import'),
            'long'  => __('Import from file'),
        ];
        $values[self::UPDATECONFIG] = ['short' => __('Configure'),
            'long'  => __('Import configuration'),
        ];

        return $values;
    }

    /**
     * Build inventory file name
     *
     * @param string $itemtype Item type
     * @param int    $items_id Item ID
     * @param string $ext      File extension
     *
     * @return string
     */
    public function buildInventoryFileName($itemtype, $items_id, $ext): string
    {
        $files_per_dir = 1000;

        return sprintf(
            '%s/%s/%s.%s',
            Toolbox::slugify($itemtype),
            floor($items_id / $files_per_dir),
            $items_id,
            $ext
        );
    }

    public static function getDefaults(): array
    {
        return [
            'enabled_inventory'              => 0,
            'import_software'                => 1,
            'import_volume'                  => 1,
            'import_antivirus'               => 1,
            'import_registry'                => 1,
            'import_process'                 => 1,
            'import_vm'                      => 1,
            'import_monitor_on_partial_sn'   => 0,
            'import_unmanaged'               => 1,
            'component_processor'            => 1,
            'component_memory'               => 1,
            'component_harddrive'            => 1,
            'component_networkcard'          => 1,
            'component_graphiccard'          => 1,
            'component_soundcard'            => 1,
            'component_drive'                => 1,
            'component_networkdrive'         => 1,
            'component_networkcardvirtual'   => 1,
            'component_control'              => 1,
            'component_battery'              => 1,
            'component_simcard'              => 1,
            'states_id_default'              => 0,
            'entities_id_default'            => 0,
            'location'                       => 0,
            'group'                          => 0,
            'vm_type'                        => 0,
            'vm_components'                  => 0,
            'vm_as_computer'                 => 0,
            'component_removablemedia'       => 1,
            'component_powersupply'          => 1,
            'inventory_frequency'            => AbstractRequest::DEFAULT_FREQUENCY,
            'import_monitor'                 => 1,
            'import_printer'                 => 1,
            'import_peripheral'              => 1,
            'stale_agents_delay'             => 0,
            'stale_agents_action'            => exportArrayToDB([0]),
            'stale_agents_status'            => 0,
            'stale_agents_status_condition'  => exportArrayToDB(['all']),
            'import_env'                     => 0,
            'auth_required'                  => 'none',
            'basic_auth_login'                     => '',
            'basic_auth_password'                  => '',
        ];
    }

    public static function getIcon()
    {
        return "ti ti-adjustments";
    }
}
