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
use finfo;
use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\StateInterface;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\ArrayNormalizer;
use Glpi\Toolbox\FileInfo;
use GLPIKey;
use Item_Disk;
use Item_Environment;
use Item_Process;
use ItemAntivirus;
use ItemVirtualMachine;
use Monitor;
use NetworkPort;
use NetworkPortType;
use OAuthClient;
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
    /** @var array<string, mixed> */
    private array $currents = [];

    public const STALE_AGENT_ACTION_CLEAN = 0;

    public const STALE_AGENT_ACTION_STATUS = 1;

    public const STALE_AGENT_ACTION_TRASHBIN = 2;

    public const NO_AUTH = 'none';

    public const CLIENT_CREDENTIALS = 'client_credentials';

    public const BASIC_AUTH = 'basic_auth';

    public static string $rightname = 'inventory';

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
     * @return string[]
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
     * @param FileInfo[]|array<string, string> $files Files to import. Passing an array of
     *                                                `filename => filepath` is deprecated since 12.0.0,
     *                                                use an array of {@see FileInfo} instead.
     *
     * @return array<string, ImportResult> Results indexed by file name
     */
    public function importFiles($files): array
    {
        $result = [];

        foreach ($files as $key => $file) {
            if ($file instanceof FileInfo) {
                $filename = $file->getFilename();
                $filepath = $file->getFilepath();
            } else {
                // deprecated v12.0.0
                Toolbox::deprecated(
                    'Passing an array of "filename => filepath" to Conf::importFiles() is deprecated. '
                    . 'Use an array of ' . FileInfo::class . ' instead.'
                );
                $filename = $key;
                $filepath = $file;
            }

            if (UnifiedArchive::canOpen($filepath) && $archive = UnifiedArchive::open($filepath)) {
                $unarchived_files = $archive->getFiles();
                foreach ($unarchived_files as $inventory_file) {
                    $entry_name = $filename . '/' . basename($inventory_file);
                    if ($this->isInventoryFile($inventory_file)) {
                        $contents = $archive->getFileContent($inventory_file);
                        $result[$entry_name] = $this->importContentFile($entry_name, null, $contents);
                    } else {
                        $result[$entry_name] = new ImportResult(
                            filename: $inventory_file,
                            success: false,
                            message: sprintf(
                                __('File `%s` has not been imported.') . ' (%s)',
                                $entry_name,
                                sprintf('`%s` format is not supported', pathinfo($inventory_file, PATHINFO_EXTENSION))
                            ),
                        );
                    }
                }
            } elseif ($this->isInventoryFile($filename)) {
                $result[$filename] = $this->importContentFile($filename, $filepath, file_get_contents($filepath));
            } else {
                $result[$filename] = new ImportResult(
                    filename: $filename,
                    success: false,
                    message: sprintf(
                        __('File `%s` has not been imported.') . ' (%s)',
                        $filename,
                        sprintf('`%s` format is not supported', pathinfo($filename, PATHINFO_EXTENSION))
                    ),
                );
            }
        }

        return $result;
    }

    /**
     * Is an inventory known file
     *
     * @param string $name
     *
     * @return bool
     */
    public function isInventoryFile($name): bool
    {
        return (bool) preg_match('/\.(' . implode('|', $this->knownInventoryExtensions()) . ')/i', $name);
    }

    /**
     * Import contents of a file
     *
     * @param string  $filename          File name (used to label the result)
     * @param ?string $path              File path
     * @param string  $contents          File contents
     *
     * @return ImportResult
     */
    protected function importContentFile($filename, $path, $contents): ImportResult
    {
        $inventory_request = new Request();
        $success = false;
        $message = null;
        $items = [];

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
                $message = sprintf(__('File has not been imported: `%s`.'), $response);
            } else {
                $success = true;
                $message = __('File has been successfully imported.');
                $items   = $inventory_request->getInventory()->getItems();
            }
        } catch (Throwable $e) {
            $success = false;
            $message = sprintf(__('An error occurs during import: `%s`.'), $e->getMessage());
            $items   = $inventory_request->getInventory()->getItems();
        }

        return new ImportResult(
            filename: $filename,
            success: $success,
            message: $message,
            items: $items,
            request: $inventory_request,
        );
    }

    /**
     * Get possible actions for stale agents
     *
     * @return array<int, string>
     */
    public static function getStaleAgentActions(): array
    {
        return [
            self::STALE_AGENT_ACTION_CLEAN  => __('Clean agents'),
            self::STALE_AGENT_ACTION_STATUS => __('Change the status'),
            self::STALE_AGENT_ACTION_TRASHBIN => __('Put asset in trashbin'),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, string>
     */
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
                $tabs[1] = self::createTabEntry(__('Configuration'), 0, $item::class);
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
        if ($item instanceof self) {
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

        $config  = Config::getConfigurationValues('inventory');
        $canedit = Config::canUpdate();
        $plural  = Session::getPluralNumber();

        // Boolean import options (field name => label)
        $import_toggles = [
            'import_volume'            => Item_Disk::getTypeName($plural),
            'component_networkdrive'   => __('Network drives'),
            'component_removablemedia' => __('Removable drives'),
            'import_software'          => Software::getTypeName($plural),
            'import_monitor'           => Monitor::getTypeName($plural),
            'import_printer'           => Printer::getTypeName($plural),
            'import_peripheral'        => Peripheral::getTypeName($plural),
            'import_antivirus'         => ItemAntivirus::getTypeName($plural),
            'import_process'           => Item_Process::getTypeName($plural),
            'import_env'               => Item_Environment::getTypeName($plural),
            'import_unmanaged'         => Unmanaged::getTypeName($plural),
        ];

        // Boolean component options (field name => label)
        $component_toggles = [
            'component_processor'          => DeviceProcessor::getTypeName($plural),
            'component_harddrive'          => DeviceHardDrive::getTypeName($plural),
            'component_memory'             => DeviceMemory::getTypeName($plural),
            'component_soundcard'          => DeviceSoundCard::getTypeName($plural),
            'component_networkcard'        => DeviceNetworkCard::getTypeName($plural),
            'component_networkcardvirtual' => __('Virtual network cards'),
            'component_graphiccard'        => DeviceGraphicCard::getTypeName($plural),
            'component_simcard'            => DeviceSimcard::getTypeName($plural),
            'component_drive'              => DeviceDrive::getTypeName($plural),
            'component_powersupply'        => DevicePowerSupply::getTypeName($plural),
            'component_control'            => DeviceControl::getTypeName($plural),
            'component_battery'            => DeviceBattery::getTypeName($plural),
        ];

        // Authorization header options
        $auth_required_options = [
            ''                       => Dropdown::EMPTY_VALUE,
            self::CLIENT_CREDENTIALS => __('OAuth - Client credentials'),
            self::BASIC_AUTH         => __('Basic Authentication'),
            self::NO_AUTH            => __('None (not recommended)'),
        ];

        // Currently selected stale agents actions
        $action = self::getDefaults()['stale_agents_action'];
        if (isset($config['stale_agents_action'])) {
            $action = $config['stale_agents_action'];
        }

        // State dropdowns share a visibility condition computed from inventoried itemtypes.
        // They are rendered here (instead of through the Twig field macros) to keep the exact
        // field names expected by saveConf() and avoid extra "_defined" helper inputs.
        $condition = [];
        foreach ($CFG_GLPI['inventory_types'] as $inv_type) {
            $inv_item = getItemForItemtype($inv_type);
            if ($inv_item instanceof StateInterface) {
                $condition[] = $inv_item->getStateVisibilityCriteria();
            }
        }
        $stale_status_condition_dropdown = State::dropdown([
            'name'      => 'stale_agents_status_condition[]',
            'value'     => importArrayFromDB($config['stale_agents_status_condition'] ?? json_encode(['all'])),
            'multiple'  => true,
            'toadd'     => ['all' => __('All')],
            'condition' => $condition,
            'display'   => false,
        ]);
        $stale_status_dropdown = State::dropdown([
            'name'      => 'stale_agents_status',
            'value'     => $config['stale_agents_status'] ?? 0,
            'entity'    => $_SESSION['glpiactive_entity'],
            'toadd'     => [-1 => __('No change')],
            'condition' => $condition,
            'display'   => false,
        ]);

        // Fields provided by plugins through the stale agent config hook
        $plugin_fields = [];
        $plugin_actions = $PLUGIN_HOOKS[Hooks::STALE_AGENT_CONFIG] ?? [];
        /**
         * @var string $plugin
         * @phpstan-var array{label: string, item_action: bool, render_callback: callable, action_callback: callable}[] $actions
         */
        foreach ($plugin_actions as $plugin => $actions) {
            if (is_array($actions) && Plugin::isPluginActive($plugin)) {
                foreach ($actions as $action_def) {
                    if (!is_callable($action_def['render_callback'] ?? null)) {
                        trigger_error(
                            sprintf('Invalid plugin "%s" render callback for "%s" hook.', $plugin, Hooks::STALE_AGENT_CONFIG),
                            E_USER_WARNING
                        );
                        continue;
                    }
                    $field = $action_def['render_callback']($config);
                    if (!empty($field)) {
                        $plugin_fields[] = [
                            'label' => $action_def['label'] ?? '',
                            'html'  => $field, // trusted HTML provided by the plugin
                        ];
                    }
                }
            }
        }

        // Links to related configuration screens
        $related_configs = [
            [
                'url'   => Rule::getSearchURL(),
                'label' => Rule::getTypeName($plural),
                'icon'  => Rule::getIcon(),
            ],
            [
                'url'   => NetworkPortType::getSearchURL(),
                'label' => NetworkPortType::getTypeName($plural),
                'icon'  => NetworkPort::getIcon(),
            ],
        ];

        TemplateRenderer::getInstance()->display('pages/admin/inventory/conf/config_form.html.twig', [
            'canedit'                         => $canedit,
            'config'                          => $config,
            'form_path'                       => $CFG_GLPI['root_doc'] . '/Inventory/Configuration/Store',
            'basic_auth_password'             => (new GLPIKey())->decrypt($config['basic_auth_password'] ?? ''),
            'auth_required_options'           => $auth_required_options,
            'import_toggles'                  => $import_toggles,
            'component_toggles'               => $component_toggles,
            'stale_agents_actions'            => self::getStaleAgentActions(),
            'stale_agents_action_values'      => importArrayFromDB($action),
            'stale_status_condition_dropdown' => $stale_status_condition_dropdown,
            'stale_status_dropdown'           => $stale_status_dropdown,
            'plugin_fields'                   => $plugin_fields,
            'related_configs'                 => $related_configs,
            'oauth_client_form_url'           => OAuthClient::getFormURL(),
            'vm_section_title'                => ItemVirtualMachine::getTypeName($plural),
            'vm_type_label'                   => ComputerType::getTypeName(1),
            'components_section_title'         => CommonDevice::getTypeName($plural),
            'CLIENT_CREDENTIALS'              => self::CLIENT_CREDENTIALS,
            'BASIC_AUTH'                      => self::BASIC_AUTH,
            'NO_AUTH'                         => self::NO_AUTH,
            'STALE_AGENT_ACTION_STATUS'       => self::STALE_AGENT_ACTION_STATUS,
        ]);

        return true;
    }

    /**
     * Save configuration
     *
     * @param array<string, mixed> $values Configuration values
     *
     * @return bool
     */
    public function saveConf(array $values)
    {
        if (!Config::canUpdate()) {
            return false;
        }

        $defaults = self::getDefaults();

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

        $enabled_inventory = (int) ($values['enabled_inventory'] ?? $defaults['enabled_inventory']) === 1;
        if ($enabled_inventory) {
            $allowed_auth_required = [
                self::CLIENT_CREDENTIALS,
                self::BASIC_AUTH,
                self::NO_AUTH,
            ];
            $auth_required = $values['auth_required'] ?? null;
            if (!is_string($auth_required) || !in_array($auth_required, $allowed_auth_required, true)) {
                Session::addMessageAfterRedirect(
                    __s('Inventory is enabled. Please select a valid authorization header method.'),
                    false,
                    ERROR
                );
                return false;
            }
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

    /**
     * @param string $interface
     *
     * @return array<int, string|array<string, string>>
     */
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

    /**
     * @return array<string, int|string>
     */
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
            'auth_required'                  => '',
            'basic_auth_login'               => '',
            'basic_auth_password'            => '',
        ];
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-adjustments";
    }
}
