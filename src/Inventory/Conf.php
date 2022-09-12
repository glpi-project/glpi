<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use CommonDevice;
use CommonGLPI;
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
use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;
use Html;
use Monitor;
use NetworkPortType;
use Printer;
use Session;
use State;
use Toolbox;
use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * Inventory configuration
 * @property int $import_software
 * @property int $import_volume
 * @property int $import_antivirus
 * @property int $import_registry
 * @property int $import_process
 * @property int $import_vm
 * @property int $import_monitor_on_partial_sn
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
 *
 */
class Conf extends CommonGLPI
{
    private $currents = [];
    public static $defaults = [
        'import_software'                => 1,
        'import_volume'                  => 1,
        'import_antivirus'               => 1,
        'import_registry'                => 1,
        'import_process'                 => 1,
        'import_vm'                      => 1,
        'import_monitor_on_partial_sn'   => 0,
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
        'stale_agents_action'            => 0,
        'stale_agents_status'            => 0,
    ];

    public const STALE_AGENT_ACTION_CLEAN = 0;

    public const STALE_AGENT_ACTION_STATUS = 1;

    public static $rightname = 'inventory';

    const IMPORTFROMFILE     = 1024;
    const UPDATECONFIG       = 2048;

    /**
     * Display form for import the XML
     *
     * @return void
     */
    public function showUploadForm()
    {
        TemplateRenderer::getInstance()->display('pages/admin/inventory/upload_form.html.twig', [
            'inventory_extensions' => $this->knownInventoryExtensions()
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
            'ocs'
        ];
    }

    /**
     * Import inventory file
     *
     * @param array $files $_FILES
     *
     * @return Request
     */
    public function importFile($files): Request
    {
        $path = $files['inventory_file']['tmp_name'];
        $name = $files['inventory_file']['name'];

        $inventory_request = new Request();

        if ($this->isInventoryFile($name)) {
           //knwon standalone file type, try to import.
            $contents = file_get_contents($path);
            $this->importContentFile($inventory_request, $path, $contents);
            return $inventory_request;
        }

       //was not a known file, maybe an archive
        $archive = UnifiedArchive::open($path);
        if ($archive === null) {
           //nay, not an archive neither
            Session::addMessageAfterRedirect(
                __('No file to import!'),
                false,
                ERROR
            );
            return $inventory_request;
        }

       //process archive
        $files = $archive->getFileNames();
        foreach ($files as $file) {
            if ($this->isInventoryFile($file)) {
                $contents = $archive->getFileContent($file);
                $this->importContentFile($inventory_request, null, $contents);
            }
        }

        return $inventory_request;
    }

    /**
     * Is an inventory known file
     *
     * @return boolean
     */
    public function isInventoryFile($name): bool
    {
        return preg_match('/\.(' . implode('|', $this->knownInventoryExtensions()) . ')/i', $name);
    }

    /**
     * Import contents of a file
     *
     * @param Request $inventory_request Inventory request instance
     * @param string  $path              File path
     * @param string  $contents          File contents
     *
     * @return void
     */
    protected function importContentFile(Request $inventory_request, $path, $contents)
    {
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = ($path === null ? $finfo->buffer($contents) : $finfo->file($path));
            switch ($mime) {
                case 'text/xml':
                    $mime = 'application/xml';
                    break;
            }

            $inventory_request->handleContentType($mime);
            $inventory_request->handleRequest($contents);
            if ($inventory_request->inError()) {
                $response = $inventory_request->getResponse();
                if ($inventory_request->getMode() === Request::JSON_MODE) {
                    $json = json_decode($inventory_request->getResponse());
                    $response = $json->message;
                } else {
                    $xml = simplexml_load_string($response);
                    $response = $xml->ERROR;
                }
                $response = str_replace('&nbsp;', ' ', $response);
                Session::addMessageAfterRedirect(
                    __('File has not been imported:') . " " . Sanitizer::encodeHtmlSpecialChars($response),
                    true,
                    ERROR
                );
            } else {
                Session::addMessageAfterRedirect(
                    __('File has been successfully imported!'),
                    true,
                    INFO
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get possible actions for stale agents
     *
     * @return string
     */
    public static function getStaleAgentActions(): array
    {
        return [
            self::STALE_AGENT_ACTION_CLEAN  => __('Clean agents'),
            self::STALE_AGENT_ACTION_STATUS => __('Change the status'),
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case __CLASS__:
                $tabs = [];
                if (Session::haveRight(self::$rightname, self::UPDATECONFIG)) {
                    $tabs[1] = __('Configuration');
                }
                if (Session::haveRight(self::$rightname, self::IMPORTFROMFILE)) {
                    $tabs[2] = __('Import from file');
                }
                return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            /** @var self $item */
            switch ($tabnum) {
                case 1:
                    $item->showConfigForm();
                    break;

                case 2:
                    $item->showUploadForm();
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
        global $CFG_GLPI;

        $config = \Config::getConfigurationValues('inventory');
        $canedit = \Config::canUpdate();
        $rand = mt_rand();

        if ($canedit) {
            echo "<form name='form' action='" . $CFG_GLPI['root_doc'] . "/front/inventory.conf.php' method='post'>";
        }

        echo "<div class='center spaced' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr>";
        echo "<th colspan='4'>";
        echo __('Import options');
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='import_volume'>";
        echo \Item_Disk::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td width='360'>";
        Html::showCheckbox([
            'name'      => 'import_volume',
            'id'        => 'import_volume',
            'checked'   => $config['import_volume']
        ]);
        echo "</td>";

        echo "<td>";
        echo "<label for='import_software'>";
        echo \Software::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_software',
            'id'        => 'import_software',
            'checked'   => $config['import_software']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='import_monitor'>";
        echo Monitor::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_monitor',
            'id'        => 'import_monitor',
            'checked'   => $config['import_monitor']
        ]);
        echo "</td>";

        echo "</td>";
        echo "<td>";
        echo "<label for='import_printer'>";
        echo Printer::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_printer',
            'id'        => 'import_printer',
            'checked'   => $config['import_printer']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='import_peripheral'>";
        echo \Peripheral::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_peripheral',
            'id'        => 'import_peripheral',
            'checked'   => $config['import_peripheral']
        ]);
        echo "</td>";

        echo "</td>";
        echo "<td>";
        echo "<label for='import_antivirus'>";
        echo \ComputerAntivirus::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_antivirus',
            'id'        => 'import_antivirus',
            'checked'   => $config['import_antivirus']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='dropdown_states_id_default$rand'>";
        echo __('Default status');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        \Dropdown::show(
            'State',
            [
                'name'   => 'states_id_default',
                'id'     => 'states_id_default',
                'value'  => $config['states_id_default'],
                'rand' => $rand
            ]
        );
        echo "</td>";

        echo "<td><label for='dropdown_inventory_frequency$rand'>" . __('Inventory frequency (in hours)') .
            "</label></td><td>";
        \Dropdown::showNumber(
            "inventory_frequency",
            [
                'value' => $config['inventory_frequency'],
                'min' => 1,
                'max' => 240,
                'rand' => $rand
            ]
        );

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='import_monitor_on_partial_sn'>";
        echo __('Import monitor on serial partial match');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_monitor_on_partial_sn',
            'id'        => 'import_monitor_on_partial_sn',
            'checked'   => $config['import_monitor_on_partial_sn']
        ]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>";
        echo __('Related configurations');
        echo "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";

        foreach (['Asset', 'Entity'] as $col_name) {
            $col_class = 'RuleImport' . $col_name . 'Collection';
            $collection = new $col_class();
            $rules = $collection->getRuleClass();
            echo "<td colspan='2'>";
            echo sprintf(
                "<a href='%s'>%s</a>",
                $rules::getSearchURL(),
                $collection->getTitle()
            );
            echo "</td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo sprintf(
            "<a href='%s'>%s</a>",
            NetworkPortType::getSearchURL(),
            NetworkPortType::getTypeName()
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>";
        echo \ComputerVirtualMachine::getTypeName(Session::getPluralNumber());
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='import_vm'>";
        echo __('Import virtual machines');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'import_vm',
            'id'        => 'import_vm',
            'checked'   => $config['import_vm']
        ]);
        echo "</td>";
        echo "<td>";
        echo "<label for='dropdown_vm_type$rand'>";
        echo \ComputerType::getTypeName(1);
        echo "</label>";
        echo "</td>";
        echo "<td>";
        \Dropdown::show(
            'ComputerType',
            [
                'name'   => 'vm_type',
                'id'     => 'vm_type',
                'value'  => $config['vm_type'],
                'rand' => $rand
            ]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='vm_as_computer'>";
        echo __('Create computer for virtual machines');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'vm_as_computer',
            'id'        => 'vm_as_computer',
            'checked'   => $config['vm_as_computer']
        ]);
        echo "</td>";
        echo "<td>";
        echo "<label for='vm_components'>";
        echo __('Create components for virtual machines');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'vm_components',
            'id'        => 'vm_components',
            'checked'   => $config['vm_components']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4'>";
        echo "<span class='red'>" . __('Will attempt to create components from VM information sent from host, do not use if you plan to inventory any VM directly!') . "</span>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>";
        echo CommonDevice::getTypeName(Session::getPluralNumber());
        echo "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_processor'>";
        echo DeviceProcessor::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_processor',
            'id'        => 'component_processor',
            'checked'   => $config['component_processor']
        ]);
        echo "</td>";

        echo "<td>";
        echo "<label for='component_harddrive'>";
        echo DeviceHardDrive::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_harddrive',
            'id'        => 'component_harddrive',
            'checked'   => $config['component_harddrive']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_memory'>";
        echo DeviceMemory::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_memory',
            'id'        => 'component_memory',
            'checked'   => $config['component_memory']
        ]);
        echo "</td>";

        echo "<td>";
        echo "<label for='component_soundcard'>";
        echo DeviceSoundCard::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_soundcard',
            'id'        => 'component_soundcard',
            'checked'   => $config['component_soundcard']
        ]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_networkcard'>";
        echo DeviceNetworkCard::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_networkcard',
            'id'        => 'component_networkcard',
            'checked'   => $config['component_networkcard']
        ]);
        echo "</td>";

        echo "<td>";
        echo "<label for='component_networkcardvirtual'>";
        echo __('Virtual network cards');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_networkcardvirtual',
            'id'        => 'component_networkcardvirtual',
            'checked'   => $config['component_networkcardvirtual']
        ]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_graphiccard'>";
        echo DeviceGraphicCard::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_graphiccard',
            'id'        => 'component_graphiccard',
            'checked'   => $config['component_graphiccard']
        ]);
        echo "</td>";

        echo "<td>";
        echo "<label for='component_simcard'>";
        echo DeviceSimcard::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_simcard',
            'id'        => 'component_simcard',
            'checked'   => $config['component_simcard']
        ]);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_drive'>";
        echo DeviceDrive::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_drive',
            'id'        => 'component_drive',
            'checked'   => $config['component_drive']
        ]);
        echo "</td>";

        echo "</td>";
        echo "<td>";
        echo "<label for='component_networkdrive'>";
        echo __('Network drives');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_networkdrive',
            'id'        => 'component_networkdrive',
            'checked'   => $config['component_networkdrive']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_drive'>";
        echo __('Removable drives');
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_removablemedia',
            'id'        => 'component_removablemedia',
            'checked'   => $config['component_removablemedia']
        ]);
        echo "</td>";
        echo "<td>";
        echo "<label for='component_powersupply'>";
        echo DevicePowerSupply::getTypeName();
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_powersupply',
            'id'        => 'component_powersupply',
            'checked'   => $config['component_powersupply']
        ]);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo "<label for='component_control'>";
        echo DeviceControl::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_control',
            'id'        => 'component_control',
            'checked'   => $config['component_control']
        ]);
        echo "</td>";

        echo "</td>";
        echo "<td>";
        echo "<label for='component_battery'>";
        echo DeviceBattery::getTypeName(Session::getPluralNumber());
        echo "</label>";
        echo "</td>";
        echo "<td>";
        Html::showCheckbox([
            'name'      => 'component_battery',
            'id'        => 'component_battery',
            'checked'   => $config['component_battery']
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan=4 >" . __('Agent cleanup') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Update agents who have not contacted the server for (in days)') . "</td>";
        echo "<td width='20%'>";
        Dropdown::showNumber(
            'stale_agents_delay',
            [
                'value' => $config['stale_agents_delay'] ?? 0,
                'min'   => 1,
                'max'   => 1000,
                'toadd' => ['0' => __('Disabled')]
            ]
        );
        echo "</td>";
        echo "<td>" . _n('Action', 'Actions', 1) . "</td>";
        echo "<td width='20%'>";
        //action
        $rand = Dropdown::showFromArray(
            'stale_agents_action',
            self::getStaleAgentActions(),
            [
                'value' => $config['stale_agents_action'] ?? self::STALE_AGENT_ACTION_CLEAN,
                'on_change' => 'changestatus();',
            ]
        );
        //if action == action_status => show blocation else hide blocaction
        echo Html::scriptBlock("
         function changestatus() {
            if ($('#dropdown_stale_agents_action$rand').val() != 0) {
               $('#blocaction1').show();
               $('#blocaction2').show();
            } else {
               $('#blocaction1').hide();
               $('#blocaction2').hide();
            }
         }
         changestatus();

      ");
        echo "</td>";
        echo "</tr>";
        //blocaction with status
        echo "<tr class='tab_bg_1'><td colspan=2></td>";
        echo "<td>";
        echo "<span id='blocaction1' style='display:none'>";
        echo __('Change the status');
        echo "</span>";
        echo "</td>";
        echo "<td width='20%'>";
        echo "<span id='blocaction2' style='display:none'>";
        State::dropdown(
            [
                'name'   => 'stale_agents_status',
                'value'  => $config['stale_agents_status'] ?? -1,
                'entity' => $_SESSION['glpiactive_entity']
            ]
        );
        echo "</span>";
        echo "</td>";
        echo "</tr>";

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
        if (!\Config::canUpdate()) {
            return false;
        }

        $defaults = self::$defaults;
        unset($values['_glpi_csrf_token']);

        $unknown = array_diff_key($values, $defaults);
        if (count($unknown)) {
            $msg = sprintf(
                __('Some properties are not known: %1$s'),
                implode(', ', array_keys($unknown))
            );
            trigger_error($msg, E_USER_WARNING);
            Session::addMessageAfterRedirect(
                $msg,
                false,
                WARNING
            );
        }
        $to_process = [];
        foreach ($defaults as $prop => $default_value) {
            $to_process[$prop] = $values[$prop] ?? $default_value;
        }
        \Config::setConfigurationValues('inventory', $to_process);
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
            $config = \Config::getConfigurationValues('inventory');
            $this->currents = $config;
        }
        if (in_array($name, array_keys(self::$defaults))) {
            return $this->currents[$name];
        } else if ($name == 'fields') {
           //no fields here
            return;
        } else {
            $msg = sprintf(
                __('Property %1$s does not exists!'),
                $name
            );
            trigger_error($msg, E_USER_WARNING);
            Session::addMessageAfterRedirect(
                $msg,
                false,
                WARNING
            );
        }
    }

    public function getRights($interface = 'central')
    {
        $values = [ READ => __('Read')];
        $values[self::IMPORTFROMFILE] = ['short' => __('Import'),
            'long'  => __('Import from file')
        ];
        $values[self::UPDATECONFIG] = ['short' => __('Configure'),
            'long'  => __('Import configuration')
        ];

        return $values;
    }

    /**
     * Build inventroy file name
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
}
