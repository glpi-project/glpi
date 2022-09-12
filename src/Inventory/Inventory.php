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
use CommonDBTM;
use Glpi\Inventory\Asset\InventoryAsset;
use Glpi\Inventory\Asset\MainAsset;
use Lockedfield;
use RefusedEquipment;
use Session;
use SNMPCredential;
use Toolbox;

/**
 * Handle inventory request
 */
class Inventory
{
    public const FULL_MODE = 0;
    public const INCR_MODE = 1;

    /** @var integer */
    protected $mode;
    /** @var \stdClass */
    protected $raw_data = null;
    /** @var array */
    protected $data = [];
    /** @var array */
    private $metadata = [];
    /** @var array */
    private $errors = [];
    /** @var CommonDBTM */
    protected $item;
    /** @var Agent */
    private $agent;
    /** @var InventoryAsset[] */
    protected $assets = [];
    /** @var Conf */
    protected $conf;
    /** @var array */
    private $benchs = [];
    /** @var string|false */
    private $inventory_tmpfile = false;
    /** @var string */
    private $inventory_content;
    /** @var integer */
    private $inventory_format;
    /** @var MainAsset */
    private $mainasset;
    /** @var string */
    private $request_query;
    /** @var bool */
    private bool $is_discovery = false;

    /**
     * @param mixed   $data   Inventory data, optional
     * @param integer $mode   One of self::*_MODE
     * @param integer $format One of Request::*_MODE
     */
    public function __construct($data = null, $mode = self::FULL_MODE, $format = Request::JSON_MODE)
    {
        $this->mode = $mode;
        $this->conf = new Conf();

        if (null !== $data) {
            $this->setData($data, $format);
            $this->doInventory();
        }
    }

    public function setMode($mode = self::FULL_MODE): Inventory
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Set data, and convert them if we're using legacy format
     *
     * @param mixed   $data   Inventory data, optional
     * @param integer $format One of self::*_FORMAT
     *
     * @return boolean
     */
    public function setData($data, $format = Request::JSON_MODE): bool
    {

        // Write inventory file
        if (!is_dir(GLPI_INVENTORY_DIR)) {
            mkdir(GLPI_INVENTORY_DIR);
        }

        $converter = new Converter();
        if (method_exists($this, 'getSchemaExtraProps')) {
            $converter->setExtraProperties($this->getSchemaExtraProps());
        }

        if (method_exists($this, 'getSchemaExtraSubProps')) {
            $converter->setExtraSubProperties($this->getSchemaExtraSubProps());
        }

        if (Request::XML_MODE === $format) {
            $this->inventory_format = Request::XML_MODE;
            $this->inventory_tmpfile = tempnam(GLPI_INVENTORY_DIR, 'xml_');
            $contentdata = $data->asXML();
            //convert legacy format
            $data = json_decode($converter->convert($contentdata));
        } else {
            $this->inventory_tmpfile = tempnam(GLPI_INVENTORY_DIR, 'json_');
            $contentdata = json_encode($data);
        }

        try {
            $converter->validate($data);
        } catch (\RuntimeException $e) {
            $this->errors[] = preg_replace(
                '|\$ref\[file~2//.*/vendor/glpi-project/inventory_format/inventory.schema.json\]|',
                '$ref[inventory.schema.json]',
                $e->getMessage()
            );
            if ($this->inventory_tmpfile !== false && file_exists($this->inventory_tmpfile)) {
                unlink($this->inventory_tmpfile);
            }
            return false;
        } finally {
            $this->raw_data = $data;
        }

        if ($this->inventory_tmpfile !== false) {
            file_put_contents($this->inventory_tmpfile, $contentdata);
        } else {
            //fallback to in-memory storage if tempnam() call returned false
            $this->inventory_content = $contentdata;
        }

        $this->extractMetadata();
        return true;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setRequestQuery(string $query): self
    {
        $this->request_query = $query;
        return $this;
    }

    /**
     * Prepare inventory data
     *
     * @return array
     */
    public function extractMetadata(): array
    {
        //check
        if ($this->inError()) {
            throw new \RuntimeException(print_r($this->getErrors(), true));
        }

        $this->metadata = [
            'deviceid' => $this->raw_data->deviceid,
            'version' => $this->raw_data->version ?? $this->raw_data->content->versionclient ?? null,
            'itemtype' => $this->raw_data->itemtype ?? 'Computer',
        ];

        if (property_exists($this->raw_data, 'content') && property_exists($this->raw_data->content, 'versionprovider')) {
            $this->metadata['provider'] = [];
            foreach ($this->raw_data->content->versionprovider as $property => $content) {
                $this->metadata['provider'][$property] = $content;
            }
        }

        $expecteds = ['action', 'name', 'installed-tasks', 'enabled-tasks', 'tag'];
        foreach ($expecteds as $expected) {
            if (property_exists($this->raw_data, $expected)) {
                $this->metadata[$expected] = $this->raw_data->{$expected};
            }
        }

        return $this->metadata;
    }

    /**
     * CONTACT request from agent
     */
    public function contact($data)
    {
        $this->raw_data = $data;
        $this->extractMetadata();
        //create/load agent
        $this->agent = new Agent();
        $this->agent->handleAgent($this->metadata);
    }

    /**
     * Do inventory
     *
     * @param boolean $test_rules Only to test rules, do not store anything
     *
     * @return array
     */
    public function doInventory($test_rules = false)
    {
        global $DB;

        //check
        if ($this->inError()) {
            throw new \RuntimeException(print_r($this->getErrors(), true));
        }

        \Log::useQueue();

        if (!isset($_SESSION['glpiinventoryuserrunning'])) {
            $_SESSION['glpiinventoryuserrunning'] = 'inventory';
        }

        try {
            //bench
            $main_start = microtime(true);
            if (!$DB->inTransaction()) {
                $DB->beginTransaction();
            }

            $converter = new Converter();
            $schema = $converter->buildSchema();

            $properties = array_keys((array)$schema->properties->content->properties);
            $properties = array_filter(
                $properties,
                function ($property_name) {
                    return !in_array($property_name, ['versionclient', 'versionprovider']); //already handled in extractMetadata
                }
            );
            if (method_exists($this, 'getSchemaExtraProps')) {
                $properties = array_merge(
                    $properties,
                    $this->getSchemaExtraProps()
                );
            }
            $contents = $this->raw_data->content;
            $all_props = get_object_vars($contents);
            unset($all_props['versionclient'], $all_props['versionprovider']); //already handled in extractMetadata

            $data = [];
            //parse schema properties and handle if it exists in raw_data
            //it is important to keep schema order, changes may have side effects
            foreach ($properties as $property) {
                if (property_exists($contents, $property)) {
                    $data[$property] = $contents->$property;
                }
            }

            $unhandled_data = array_diff_key($all_props, $data);
            if (count($unhandled_data)) {
                Session::addMessageAfterRedirect(
                    sprintf(
                        __('Following keys has been ignored during process: %1$s'),
                        implode(
                            ', ',
                            array_keys($unhandled_data)
                        )
                    ),
                    true,
                    WARNING
                );
            }

            $this->data = $data;

            //create/load agent
            $this->agent = new Agent();
            $this->agent->handleAgent($this->metadata);

            $this->item = new $this->agent->fields['itemtype']();

            if (!empty($this->agent->fields['items_id'])) {
                $this->item->getFromDB($this->agent->fields['items_id']);
            }

            $main_class = $this->getMainClass();
            $main = new $main_class($this->item, $this->raw_data);
            $main
                ->setDiscovery($this->is_discovery)
                ->setRequestQuery($this->request_query)
                ->setAgent($this->getAgent())
                ->setExtraData($this->data);

            $item_start = microtime(true);
            $main->prepare();
            $this->addBench($this->item->getType(), 'prepare', $item_start);

            $this->mainasset = $main;
            if (isset($this->data['hardware'])) {
                //hardware is handled in inventoried item, but may be used outside
                $this->data['hardware'] = $main->getHardware();
            }

            if ($test_rules === false) {
                $this->processInventoryData();
                $this->handleItem();

                if (!$this->mainasset->isNew()) {
                    \Log::handleQueue();
                } else {
                    \Log::resetQueue();
                }

                if (!defined('TU_USER')) {
                    $DB->commit();
                }
            }
        } catch (\Exception $e) {
            $DB->rollback();
            throw $e;
        } finally {
            unset($_SESSION['glpiinventoryuserrunning']);
            $this->handleInventoryFile();
            if (isset($this->mainasset)) {
                // * For benchs
                $id = $this->item->fields['id'] ?? 0;
                $items = $this->mainasset->getInventoried() + $this->mainasset->getRefused();
                $extra = null;
                if (count($items)) {
                    $extra = 'Inventoried assets: ';
                    foreach ($items as $item) {
                        $extra .= $item->getType() . ' #' . $item->getId() . ', ';
                    }
                    $extra = rtrim($extra, ', ') . "\n";
                }
                $this->addBench($this->item->getType(), 'full', $main_start, $extra);
                $this->printBenchResults();
            }
        }

        return [];
    }

    /**
     * Get inventoried items
     *
     * @return array
     */
    public function getItems(): array
    {
        $items = $this->mainasset->getInventoried();

        foreach ($this->mainasset->getRefused() as $refused) {
            $items[] = $refused;
        }

        return $items;
    }


    /**
     * Get rawdata
     *
     * @return array
     */
    public function getRawData(): ?object
    {
        return $this->raw_data;
    }


    /**
     * Handle inventory file
     *
     * @return void
     */
    private function handleInventoryFile()
    {
        if (isset($this->mainasset)) {
            $ext = (Request::XML_MODE === $this->inventory_format ? 'xml' : 'json');
            $items = $this->getItems();

            foreach ($items as $item) {
                $itemtype = $item->getType();
                if (!isset($item->fields['id']) || empty($item->fields['id'])) {
                    throw new \RuntimeException('Item ID is missing :(');
                }
                $id = $item->fields['id'];

                $filename = GLPI_INVENTORY_DIR . '/' . $this->conf->buildInventoryFileName($itemtype, $id, $ext);
                $subdir = dirname($filename);
                if (!is_dir($subdir)) {
                    mkdir($subdir, 0755, true);
                }
                if ($this->inventory_tmpfile !== false) {
                    copy($this->inventory_tmpfile, $filename);
                } elseif (isset($this->inventory_content)) {
                    file_put_contents($filename, $this->inventory_content);
                }
            }
        }

        if ($this->inventory_tmpfile !== false && file_exists($this->inventory_tmpfile)) {
            unlink($this->inventory_tmpfile);
        }
    }

    /**
     * Get error
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if errors has been thrown
     *
     * @return boolean
     */
    public function inError(): bool
    {
        return (bool)count($this->errors);
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight(Conf::$rightname, Conf::IMPORTFROMFILE)) {
            return false;
        }

        $classes = [
            Agent::class,
            Lockedfield::class,
            RefusedEquipment::class,
            SNMPCredential::class
        ];
        $links = [];
        foreach ($classes as $class) {
            $entry = "<i class=\"" . $class::getIcon() . " pointer\" title=\"" . $class::getTypeName(Session::getPluralNumber()) .
            "\"></i><span class=\"d-none d-xxl-block\">" . $class::getTypeName(Session::getPluralNumber()) . "</span>";
            $links[$entry] = $class::getSearchURL(false);
        }

        $menu = [
            'title'   => __('Inventory'),
            'page'    => '/front/inventory.conf.php',
            'icon'    => static::getIcon(),
            'options' => [],
            'links'   => $links,
        ];

        if (Session::haveRight(Agent::$rightname, READ)) {
            $menu['options']['agent'] = [
                'icon'  => Agent::getIcon(),
                'title' => Agent::getTypeName(Session::getPluralNumber()),
                'page'  => Agent::getSearchURL(false),
                'links' => [
                    'search' => '/front/agent.php',
                ] + $links
            ];
        }

        if (Session::haveRight(Lockedfield::$rightname, READ)) {
            $menu['options']['lockedfield'] = [
                'icon'  => Lockedfield::getIcon(),
                'title' => Lockedfield::getTypeName(Session::getPluralNumber()),
                'page'  => Lockedfield::getSearchURL(false),
                'links' => [
                    "<i class=\"ti ti-plus\" title=\"" . __('Add global lock') . "\"></i><span class='d-none d-xxl-block'>" . __('Add global lock') . "</span>" => Lockedfield::getFormURL(false)
                ] + $links
            ];
        }

        if (Session::haveRight(RefusedEquipment::$rightname, READ)) {
            $menu['options']['refusedequipment'] = [
                'icon'  => RefusedEquipment::getIcon(),
                'title' => RefusedEquipment::getTypeName(Session::getPluralNumber()),
                'page'  => RefusedEquipment::getSearchURL(false),
                'links' => $links
            ];
        }

        if (Session::haveRight(SNMPCredential::$rightname, READ)) {
            $menu['options']['snmpcredential'] = [
                'icon'  => SNMPCredential::getIcon(),
                'title' => SNMPCredential::getTypeName(Session::getPluralNumber()),
                'page'  => SNMPCredential::getSearchURL(false),
                'links' => [
                    'add' => '/front/snmpcredential.form.php',
                    'search' => '/front/snmpcredential.php',
                ] + $links
            ];
        }

        if (count($menu['options'])) {
            return $menu;
        }
        return false;
    }

    /**
     * Retrieve main inventoried object class
     *
     * @return string
     */
    public function getMainClass()
    {
        $agent = $this->getAgent();
        $main_class = '\Glpi\Inventory\Asset\\' . $agent->fields['itemtype'];
        return $main_class;
    }

    /**
     * Process and enhance data
     *
     * @return void
     */
    final public function processInventoryData()
    {
       //map existing keys in inventory format to their respective Inventory\Asset class if needed.
        foreach ($this->data as $key => &$value) {
            $assettype = false;

            switch ($key) {
                case 'accesslog': //not used
                case 'autoupdatesystems_id': //set on host, ignore - not present in specs
                case 'envs': //not used
                case 'local_groups': //not used
                case 'local_users': //not used
                case 'physical_volumes': //not used
                case 'volume_groups': //not used
                case 'logical_volumes': //not used
                case 'ports': //not used
                case 'processes': //not used
                case 'slots': //not used
                case 'versionclient': //not used
                case 'versionprovider': //not provided see doInventory
                case 'licenseinfos': //not used - implemented in FI only
                case 'modems': //not used - implemented in FI only
                case 'accountinfo': //no longer existing, see tag at upper level
                case 'firewalls': //not used
                case 'hardware': //handled from Asset\Computer
                case 'inputs': //handled from Asset\Peripheral
                case 'users': //handled from Asset\Computer
                case 'network_device': //handled from Asset\NetworkEquipment
                case 'network_components': //handled from Asset\NetworkEquipment
                case 'pagecounters': //handled from Asset\Printer
                    break;
                case 'cpus':
                    $assettype = '\Glpi\Inventory\Asset\Processor';
                    break;
                case 'drives':
                    $assettype = '\Glpi\Inventory\Asset\Volume';
                    break;
                case 'memories':
                    $assettype = '\Glpi\Inventory\Asset\Memory';
                    break;
                case 'monitors':
                    $assettype = '\Glpi\Inventory\Asset\Monitor';
                    break;
                case 'networks':
                    $assettype = '\Glpi\Inventory\Asset\NetworkCard';
                    break;
                case 'operatingsystem':
                    $assettype = '\Glpi\Inventory\Asset\OperatingSystem';
                    break;
                case 'printers':
                    $assettype = '\Glpi\Inventory\Asset\Printer';
                    break;
                case 'softwares':
                    $assettype = '\Glpi\Inventory\Asset\Software';
                    break;
                case 'sounds':
                    $assettype = '\Glpi\Inventory\Asset\SoundCard';
                    break;
                case 'storages':
                    $assettype = '\Glpi\Inventory\Asset\Drive';
                    break;
                case 'usbdevices':
                    $assettype = '\Glpi\Inventory\Asset\Peripheral';
                    break;
                case 'antivirus':
                    $assettype = '\Glpi\Inventory\Asset\Antivirus';
                    break;
                case 'bios':
                    $assettype = '\Glpi\Inventory\Asset\Bios';
                    break;
                case 'firmwares':
                    $assettype = '\Glpi\Inventory\Asset\Firmware';
                    break;
                case 'batteries':
                    $assettype = '\Glpi\Inventory\Asset\Battery';
                    break;
                case 'controllers':
                    $assettype = '\Glpi\Inventory\Asset\Controller';
                    break;
                case 'videos':
                    $assettype = '\Glpi\Inventory\Asset\GraphicCard';
                    break;
                case 'simcards':
                    $assettype = '\Glpi\Inventory\Asset\Simcard';
                    break;
                case 'virtualmachines':
                    $assettype = '\Glpi\Inventory\Asset\VirtualMachine';
                    break;
                case 'sensors':
                    $assettype = '\Glpi\Inventory\Asset\Sensor';
                    break;
                case 'network_ports':
                    $assettype = '\Glpi\Inventory\Asset\NetworkPort';
                    break;
                case 'cartridges':
                    $assettype = '\Glpi\Inventory\Asset\Cartridge';
                    break;
                case 'remote_mgmt':
                    $assettype = '\Glpi\Inventory\Asset\RemoteManagement';
                    break;
                case 'cameras':
                    $assettype = '\Glpi\Inventory\Asset\Camera';
                    break;
                case 'databases_services':
                    $assettype = '\Glpi\Inventory\Asset\DatabaseInstance';
                    break;
                case 'powersupplies':
                    $assettype = '\Glpi\Inventory\Asset\PowerSupply';
                    break;
                default:
                    if (method_exists($this, 'processExtraInventoryData')) {
                        $assettype = $this->processExtraInventoryData($key);
                    }
                    if ($assettype === false) {
                     //unhandled
                        throw new \RuntimeException("Unhandled schema entry $key");
                    }
                    break;
            }

            if ($assettype !== false) {
               //handle if asset type has been found.
                $asset = new $assettype($this->item, (array)$value);
                if ($asset->checkConf($this->conf)) {
                    $asset->setMainAsset($this->mainasset);
                    $asset->setAgent($this->getAgent());
                    $asset->setExtraData($this->data);
                    $asset->setEntityID($this->mainasset->getEntityID());
                    $asset->prepare();
                    $value = $asset->handleLinks();
                    $this->assets[$assettype][] = $asset;
                } else {
                    unset($this->data[$key]);
                }
            }
        }
    }

    /**
     * Main item handling, including links
     *
     * @return void;
     */
    public function handleItem()
    {
       //inject converted assets
        $this->mainasset->setExtraData($this->data);
        $this->mainasset->setAssets($this->assets);
        $this->mainasset->checkConf($this->conf);
        $item_start = microtime(true);
        $this->mainasset->handle();
        $this->item = $this->mainasset->getItem();
        $this->addBench($this->item->getType(), 'handle', $item_start);
        return;
    }

    /**
     * Get agent
     *
     * @return \Agent
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Add bench value
     *
     * @param string        $asset Asset
     * @param string        $type Either prepare or handle
     * @param float|integer $start Start time
     * @param string        $extra Extra value to be used as label
     *
     * @return void
     */
    protected function addBench($asset, $type, $start, $extra = null)
    {
        $exec_time = round(microtime(true) - $start, 5);
        $this->benchs[$asset][$type] = [
            'exectime'  => $exec_time,
            'mem'       => memory_get_usage(),
            'mem_real'  => memory_get_usage(true),
            'mem_peak'  => memory_get_peak_usage(),
            'extra'     => $extra

        ];
    }

    /**
     * Display bench results
     *
     * @return void
     */
    public function printBenchResults()
    {
        $output = '';
        foreach ($this->benchs as $asset => $types) {
            $output .= "$asset:\n";
            foreach ($types as $type => $data) {
                $output .= "\t$type:\n";
                foreach ($data as $key => $value) {
                    $label = $key;
                    switch ($label) {
                        case 'exectime':
                            $output .= "\t\tExecution time:      ";
                            break;
                        case 'mem':
                            $output .= "\t\tMemory usage:        ";
                            break;
                        case 'mem_real':
                             $output .= "\t\tMemory usage (real): ";
                            break;
                        case 'mem_peak':
                             $output .= "\t\tMemory peak:         ";
                            break;
                    }

                    if ($key == 'exectime') {
                        $output .= sprintf(
                            _n('%s second', '%s seconds', $value),
                            $value
                        );
                    } else if ($key != 'extra') {
                        $output .= Toolbox::getSize($value);
                    }
                    $output .= "\n";
                }
                if (isset($data['extra'])) {
                    $output .= $data['extra'];
                }
            }
        }

        if (isCommandLine() && !defined('TU_USER')) {
            echo $output . "\n";
        } else {
            Toolbox::logInFile(
                "bench_inventory",
                $output
            );
        }
    }

    public static function getIcon()
    {
        return "ti ti-cloud-download";
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMainAsset(): InventoryAsset
    {
        return $this->mainasset;
    }

    public function getItem(): CommonDBTM
    {
        return $this->item;
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'cleantemp':
                return ['description' => __('Clean temporary files created from inventories')];

            case 'cleanorphans':
                return ['description' => __('Clean inventories orphaned files')];
        }
        return [];
    }

    /**
     * Clean temporary inventory files
     *
     * @param \CronTask $task CronTask instance
     *
     * @return void
     **/
    public static function cronCleantemp($task)
    {
        $cron_status = 0;

        $conf = new Conf();
        $temp_files = glob(GLPI_INVENTORY_DIR . '/*.{' . implode(',', $conf->knownInventoryExtensions()) . '}', GLOB_BRACE);

        $time_limit = 60 * 60 * 12;//12 hours
        foreach ($temp_files as $temp_file) {
           //drop only inventory files that have been created more than 12 hours ago
            if (time() - filemtime($temp_file) >= $time_limit) {
                unlink($temp_file);
                $message = sprintf(__('File %1$s has been removed'), $temp_file);
                if ($task) {
                    $task->log($message);
                    $task->addVolume(1);
                } else {
                    Session::addMessageAfterRedirect($message);
                }
            }
        }

        $cron_status = 1;

        return $cron_status;
    }

    /**
     * Clean orphan inventory files
     *
     * @param \CronTask $task CronTask instance
     *
     * @return void
     **/
    public static function cronCleanorphans($task)
    {
        global $DB;

        $cron_status = 0;

        $conf = new Conf();
        $existing_types = glob(GLPI_INVENTORY_DIR . '/*', GLOB_ONLYDIR);

        foreach ($existing_types as $existing_type) {
            /** @var class-string<CommonDBTM> $itemtype */
            $itemtype = str_replace(GLPI_INVENTORY_DIR . '/', '', $existing_type);
           //$invnetoryfiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('path/to/folder'));
            $inventory_files = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($existing_type)
                ),
                "/\\.(" . implode('|', $conf->knownInventoryExtensions()) . ")\$/i"
            );

            $ids = [];
            foreach ($inventory_files as $inventory_file) {
                 $ids[preg_replace("/\\.(" . implode('|', $conf->knownInventoryExtensions()) . ")\$/i", '', $inventory_file->getFileName())] = $inventory_file;
            }

            if (!count($ids)) {
                //no files, we're done
                return;
            }

            $iterator = $DB->request([
                'SELECT'  => 'id',
                'FROM'    => $itemtype::getTable(),
                'WHERE'   => ['id' => array_keys($ids)]
            ]);

            if (count($iterator) === count($ids)) {
                 //all assets are still present, we're done
                 return;
            }

           //find missing assets
            $orphans = array_diff(
                array_keys($ids),
                array_keys(iterator_to_array($iterator))
            );

            foreach ($orphans as $orphan) {
                 $dropfile = $ids[$orphan]->getFileName();
                 @unlink($dropfile);
                 $message = sprintf(__('File %1$s has been removed'), $dropfile);
                if ($task) {
                    $task->log($message);
                    $task->addVolume(1);
                } else {
                    Session::addMessageAfterRedirect($message);
                }
            }
        }

        $cron_status = 1;

        return $cron_status;
    }

    public static function getTypeName($nb = 0)
    {
        return __("Inventory");
    }

    /**
     * Mark as discovery
     *
     * @param bool $disco
     *
     * @return $this
     */
    public function setDiscovery(bool $disco): self
    {
        $this->is_discovery = $disco;
        return $this;
    }
}
