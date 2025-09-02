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

namespace Glpi\Inventory;

use Agent;
use CommonDBTM;
use CronTask;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Inventory\Asset\Antivirus;
use Glpi\Inventory\Asset\Battery;
use Glpi\Inventory\Asset\Bios;
use Glpi\Inventory\Asset\Camera;
use Glpi\Inventory\Asset\Cartridge;
use Glpi\Inventory\Asset\Controller;
use Glpi\Inventory\Asset\DatabaseInstance;
use Glpi\Inventory\Asset\Drive;
use Glpi\Inventory\Asset\Environment;
use Glpi\Inventory\Asset\Firmware;
use Glpi\Inventory\Asset\GraphicCard;
use Glpi\Inventory\Asset\InventoryAsset;
use Glpi\Inventory\Asset\Memory;
use Glpi\Inventory\Asset\Monitor;
use Glpi\Inventory\Asset\NetworkCard;
use Glpi\Inventory\Asset\NetworkPort;
use Glpi\Inventory\Asset\OperatingSystem;
use Glpi\Inventory\Asset\Peripheral;
use Glpi\Inventory\Asset\PowerSupply;
use Glpi\Inventory\Asset\Printer;
use Glpi\Inventory\Asset\Process;
use Glpi\Inventory\Asset\Processor;
use Glpi\Inventory\Asset\RemoteManagement;
use Glpi\Inventory\Asset\Sensor;
use Glpi\Inventory\Asset\Simcard;
use Glpi\Inventory\Asset\Software;
use Glpi\Inventory\Asset\SoundCard;
use Glpi\Inventory\Asset\VirtualMachine;
use Glpi\Inventory\Asset\Volume;
use Glpi\Inventory\MainAsset\Itemtype;
use Glpi\Inventory\MainAsset\MainAsset;
use Lockedfield;
use Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RefusedEquipment;
use RegexIterator;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Session;
use SNMPCredential;
use stdClass;
use Throwable;
use Toolbox;

use function Safe\copy;
use function Safe\file_put_contents;
use function Safe\filemtime;
use function Safe\glob;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\mkdir;
use function Safe\preg_replace;
use function Safe\tempnam;
use function Safe\unlink;

/**
 * Handle inventory request
 */
class Inventory
{
    public const FULL_MODE = 0;
    public const INCR_MODE = 1;

    /** @var integer */
    protected $mode;
    /** @var stdClass */
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
    /** @var ?string */
    private $inventory_content;
    /** @var integer */
    private $inventory_format;
    /** @var ?MainAsset */
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

        $schema = $converter->getSchema();

        $schema->setExtraItemtypes($this->getExtraItemtypes());

        if (method_exists($this, 'getSchemaExtraProps')) {
            $schema->setExtraProperties($this->getSchemaExtraProps());
        }

        if (method_exists($this, 'getSchemaExtraSubProps')) {
            $schema->setExtraSubProperties($this->getSchemaExtraSubProps());
        }

        $tempnam_ext = 'json_';
        if (Request::XML_MODE === $format) {
            $this->inventory_format = Request::XML_MODE;
            $tempnam_ext = 'xml_';
            $contentdata = $data->asXML();
            //convert legacy format
            $data = json_decode($converter->convert($contentdata));
        } else {
            $contentdata = json_encode($data, JSON_PRETTY_PRINT);
        }

        try {
            $this->inventory_tmpfile = tempnam(GLPI_INVENTORY_DIR, $tempnam_ext);
        } catch (FilesystemException $e) {
            global $PHPLOGGER;
            $PHPLOGGER->error($e->getMessage(), ['exception' => $e]);
            $this->inventory_tmpfile = false;
        }

        try {
            $schema->validate($data);
        } catch (RuntimeException $e) {
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
            throw new RuntimeException(print_r($this->getErrors(), true));
        }

        $this->metadata = [
            'deviceid' => $this->raw_data->deviceid,
            'version' => $this->raw_data->version ?? $this->raw_data->content->versionclient ?? null,
            'itemtype' => $this->raw_data->itemtype ?? 'Computer',
            'port'      => $this->raw_data->{'httpd-port'} ?? null,
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
            throw new RuntimeException(print_r($this->getErrors(), true));
        }

        Log::useQueue();

        if (!isset($_SESSION['glpiinventoryuserrunning'])) {
            $_SESSION['glpiinventoryuserrunning'] = 'inventory';
        }

        if (!isset($_SESSION['glpiname'])) {
            $_SESSION['glpiname'] = $_SESSION['glpiinventoryuserrunning'];
        }

        $main_start = microtime(true); //bench
        try {
            if (!defined('TU_USER')) {
                $DB->beginTransaction();
            }

            $converter = new Converter();
            $schema = $converter->getSchema()->build();

            $properties = array_keys((array) $schema->properties->content->properties);
            $properties = array_filter(
                $properties,
                function ($property_name) {
                    return !in_array($property_name, ['versionclient', 'versionprovider']); //already handled in extractMetadata
                }
            );
            if (method_exists($this, 'getSchemaExtraProps')) {
                $properties = array_merge(
                    $properties,
                    array_keys($this->getSchemaExtraProps())
                );
            }
            $contents = $this->raw_data->content;
            $all_props = get_object_vars($contents);
            unset($all_props['versionclient'], $all_props['versionprovider']); //already handled in extractMetadata

            $empty_props = [];
            if (
                (!property_exists($this->raw_data, 'itemtype') || $this->raw_data->itemtype == 'Computer')
                && (!property_exists($this->raw_data, 'partial') || !$this->raw_data->partial)
            ) {
                //if inventory is not partial, we consider following properties are empty if not present; so they'll be removed
                $empty_props = [
                    'virtualmachines',
                    'remote_mgmt',
                    'monitors',
                    'antivirus',
                ];
            }

            $data = [];
            //parse schema properties and handle if it exists in raw_data
            //it is important to keep schema order, changes may have side effects
            foreach ($properties as $property) {
                if (property_exists($contents, $property)) {
                    $data[$property] = $contents->$property;
                } elseif (in_array($property, $empty_props)) {
                    $data[$property] = [];
                }
            }

            $unhandled_data = array_diff_key($all_props, $data);
            if (count($unhandled_data)) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('Following keys has been ignored during process: %1$s'),
                        implode(
                            ', ',
                            array_keys($unhandled_data)
                        )
                    )),
                    true,
                    WARNING
                );
            }

            $this->data = $data;

            //process itemtype definition from rules engine
            $itemtype_string = $this->metadata['itemtype'] ?? 'Computer';
            $main_itemtype = new Itemtype($this->raw_data);

            $main_itemtype
                ->setDiscovery($this->is_discovery)
                ->setRequestQuery($this->request_query)
                ->setMetadata($this->metadata)
                //->setAgent($this->getAgent())
                ->setExtraData($this->data);
            $main_itemtype->prepare();
            $data_itemtype = $main_itemtype->defineItemtype($itemtype_string);

            if (isset($data_itemtype['new_itemtype']) && $data_itemtype['new_itemtype'] != -1) {
                $this->metadata['itemtype'] = $data_itemtype['new_itemtype'];
            }

            //create/load agent
            $this->agent = new Agent();
            $this->agent->handleAgent($this->metadata);

            $this->item = getItemForItemtype($this->agent->fields['itemtype']);

            //load existing itemtype, if any
            if (!empty($this->agent->fields['items_id'])) {
                $this->item->getFromDB($this->agent->fields['items_id']);
            }

            //instanciate inventory main asset class, and proceed
            $main_class = $this->getMainClass();
            if (!is_subclass_of($main_class, MainAsset::class, true)) {
                throw new RuntimeException(
                    sprintf(
                        'Main asset class %s is not a valid MainAsset class',
                        $main_class
                    )
                );
            }
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
                    Log::handleQueue();
                } else {
                    Log::resetQueue();
                }

                if (!defined('TU_USER')) {
                    $DB->commit();
                }
            }
        } catch (Throwable $e) {
            if (!defined('TU_USER')) {
                $DB->rollback();
            }
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
        if ($this->mainasset === null) {
            return [];
        }

        $items = $this->mainasset->getInventoried();

        foreach ($this->mainasset->getRefused() as $refused) {
            $items[] = $refused;
        }

        return $items;
    }


    /**
     * Get raw data
     *
     * @return object|null
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
                    throw new RuntimeException('Item ID is missing :(');
                }
                $id = $item->fields['id'];

                $filename = GLPI_INVENTORY_DIR . '/' . $this->conf->buildInventoryFileName($itemtype, $id, $ext);
                $subdir = dirname($filename);
                if (!is_dir($subdir)) {
                    mkdir($subdir, 0o755, true);
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
        return (bool) count($this->errors);
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
            SNMPCredential::class,
        ];
        $links = [];
        foreach ($classes as $class) {
            $entry = "<i class=\"" . \htmlescape($class::getIcon()) . " pointer\" title=\"" . \htmlescape($class::getTypeName(Session::getPluralNumber()))
            . "\"></i><span class=\"d-none d-xxl-block\">" . \htmlescape($class::getTypeName(Session::getPluralNumber())) . "</span>";
            $links[$entry] = $class::getSearchURL(false);
        }

        $menu = [
            'title'   => __('Inventory'),
            'page'    => '/front/inventory.conf.php',
            'icon'    => static::getIcon(),
            'options' => [],
            'links'   => $links,
        ];

        $links['lists'] = ''; // Add `Lists` button for subitems

        if (Session::haveRight(Agent::$rightname, READ)) {
            $menu['options'][Agent::class] = [
                'icon'  => Agent::getIcon(),
                'title' => Agent::getTypeName(Session::getPluralNumber()),
                'page'  => Agent::getSearchURL(false),
                'links' => [
                    'search' => '/front/agent.php',
                ] + $links,
                'lists_itemtype' => Agent::class,
            ];
        }

        if (Session::haveRight(Lockedfield::$rightname, UPDATE)) {
            $menu['options'][Lockedfield::class] = [
                'icon'  => Lockedfield::getIcon(),
                'title' => Lockedfield::getTypeName(Session::getPluralNumber()),
                'page'  => Lockedfield::getSearchURL(false),
                'links' => [
                    "<i class=\"ti ti-plus\" title=\"" . __s('Add global lock') . "\"></i><span class='d-none d-xxl-block'>" . __s('Add global lock') . "</span>" => Lockedfield::getFormURL(false),
                ] + $links,
                'lists_itemtype' => Lockedfield::class,
            ];
        }

        if (Session::haveRight(RefusedEquipment::$rightname, READ)) {
            $menu['options'][RefusedEquipment::class] = [
                'icon'  => RefusedEquipment::getIcon(),
                'title' => RefusedEquipment::getTypeName(Session::getPluralNumber()),
                'page'  => RefusedEquipment::getSearchURL(false),
                'links' => $links,
                'lists_itemtype' => RefusedEquipment::class,
            ];
        }

        if (Session::haveRight(SNMPCredential::$rightname, READ)) {
            $menu['options'][SNMPCredential::class] = [
                'icon'  => SNMPCredential::getIcon(),
                'title' => SNMPCredential::getTypeName(Session::getPluralNumber()),
                'page'  => SNMPCredential::getSearchURL(false),
                'links' => [
                    'add' => '/front/snmpcredential.form.php',
                    'search' => '/front/snmpcredential.php',
                ] + $links,
                'lists_itemtype' => SNMPCredential::class,
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
        $class_ns = '\Glpi\Inventory\MainAsset\\';
        $main_class = $class_ns . $this->item::class;
        if (class_exists($main_class)) {
            return $main_class;
        }

        //not found, so we have a generic asset. Let's retrieve its MainAsset class
        if ($this->item instanceof Asset) {
            $main_class = $this->item
                ->getDefinition()
                ->getCapacityConfiguration(IsInventoriableCapacity::class)
                ->getValue('inventory_mainasset');
        }
        if ($main_class === null || !class_exists($main_class)) {
            $main_class = $class_ns . 'GenericAsset';
        }
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
            $assettype = null;

            switch ($key) {
                case 'accesslog': //not used
                case 'autoupdatesystems_id': //set on host, ignore - not present in specs
                case 'local_groups': //not used
                case 'local_users': //not used
                case 'physical_volumes': //not used
                case 'volume_groups': //not used
                case 'logical_volumes': //not used
                case 'ports': //not used
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
                    $assettype = Processor::class;
                    break;
                case 'drives':
                    $assettype = Volume::class;
                    break;
                case 'memories':
                    $assettype = Memory::class;
                    break;
                case 'monitors':
                    $assettype = Monitor::class;
                    break;
                case 'networks':
                    $assettype = NetworkCard::class;
                    break;
                case 'operatingsystem':
                    $assettype = OperatingSystem::class;
                    break;
                case 'printers':
                    $assettype = Printer::class;
                    break;
                case 'softwares':
                    $assettype = Software::class;
                    break;
                case 'sounds':
                    $assettype = SoundCard::class;
                    break;
                case 'storages':
                    $assettype = Drive::class;
                    break;
                case 'usbdevices':
                    $assettype = Peripheral::class;
                    break;
                case 'antivirus':
                    $assettype = Antivirus::class;
                    break;
                case 'bios':
                    $assettype = Bios::class;
                    break;
                case 'firmwares':
                    $assettype = Firmware::class;
                    break;
                case 'batteries':
                    $assettype = Battery::class;
                    break;
                case 'controllers':
                    $assettype = Controller::class;
                    break;
                case 'videos':
                    $assettype = GraphicCard::class;
                    break;
                case 'simcards':
                    $assettype = Simcard::class;
                    break;
                case 'virtualmachines':
                    $assettype = VirtualMachine::class;
                    break;
                case 'processes':
                    $assettype = Process::class;
                    break;
                case 'envs':
                    $assettype = Environment::class;
                    break;
                case 'sensors':
                    $assettype = Sensor::class;
                    break;
                case 'network_ports':
                    $assettype = NetworkPort::class;
                    break;
                case 'cartridges':
                    $assettype = Cartridge::class;
                    break;
                case 'remote_mgmt':
                    $assettype = RemoteManagement::class;
                    break;
                case 'cameras':
                    $assettype = Camera::class;
                    break;
                case 'databases_services':
                    $assettype = DatabaseInstance::class;
                    break;
                case 'powersupplies':
                    $assettype = PowerSupply::class;
                    break;
                default:
                    if (method_exists($this, 'processExtraInventoryData')) {
                        $assettype = $this->processExtraInventoryData($key);
                    }
                    if (!\is_string($value) || !is_a($assettype, InventoryAsset::class, true)) {
                        //unhandled
                        throw new RuntimeException("Unhandled schema entry $key");
                    }
                    break;
            }

            if ($assettype !== null) {
                //handle if asset type has been found.
                $asset = new $assettype($this->item, (array) $value);
                $asset->setMainAsset($this->mainasset);
                if ($asset->checkConf($this->conf)) {
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
     * @return void
     */
    public function handleItem()
    {
        if ($this->mainasset->checkConf($this->conf)) {
            //inject converted assets
            $this->mainasset->setExtraData($this->data);
            $this->mainasset->setAssets($this->assets);
            $item_start = microtime(true);
            $this->mainasset->handle();
            $this->item = $this->mainasset->getItem();
            $this->addBench($this->item->getType(), 'handle', $item_start);
        }
        return;
    }

    /**
     * Get agent
     *
     * @return Agent
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
            'extra'     => $extra,

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
                    } elseif ($key != 'extra') {
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
            /**
             * Safe CLI context.
             * @psalm-taint-escape html
             * @psalm-taint-escape has_quotes
             */
            $out = $output . PHP_EOL;

            echo $out;
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

    public function getAssets()
    {
        return $this->assets;
    }

    public function getMainAsset(): MainAsset
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
     * @param CronTask $task CronTask instance
     *
     * @return int
     **/
    public static function cronCleantemp($task)
    {
        $conf = new Conf();
        $temp_files = glob(GLPI_INVENTORY_DIR . '/*.{' . implode(',', $conf->knownInventoryExtensions()) . '}', GLOB_BRACE);

        $time_limit = 60 * 60 * 12;//12 hours
        foreach ($temp_files as $temp_file) {
            //drop only inventory files that have been created more than 12 hours ago
            if (time() - filemtime($temp_file) >= $time_limit) {
                try {
                    unlink($temp_file);
                    $message = sprintf(__('File %1$s has been removed'), $temp_file);
                } catch (FilesystemException $e) {
                    $message = sprintf(__('Unable to remove file %1$s'), $temp_file);
                }
                if ($task) {
                    $task->log($message);
                    $task->addVolume(1);
                } else {
                    Session::addMessageAfterRedirect(htmlescape($message));
                }
            }
        }

        return 1;
    }

    /**
     * Clean orphan inventory files
     *
     * @param CronTask $task CronTask instance
     *
     * @return int
     **/
    public static function cronCleanorphans($task)
    {
        global $DB;

        $conf = new Conf();
        $existing_types = glob(GLPI_INVENTORY_DIR . '/*', GLOB_ONLYDIR);

        foreach ($existing_types as $existing_type) {
            /** @var class-string<CommonDBTM> $itemtype */
            $itemtype = str_replace(GLPI_INVENTORY_DIR . '/', '', $existing_type);
            // use `getItemForItemtype` to fix classname case (i.e. `refusedequipement` -> `RefusedEquipement`)
            $itemtype = getItemForItemtype($itemtype)::getType();
            $inventory_files = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($existing_type)
                ),
                "/\\.(" . implode('|', $conf->knownInventoryExtensions()) . ")\$/i"
            );

            $ids = [];
            foreach ($inventory_files as $inventory_file) {
                $ids[preg_replace("/\\.(" . implode('|', $conf->knownInventoryExtensions()) . ")\$/i", '', $inventory_file->getFileName())] = $inventory_file;
            }

            if (!count($ids)) {
                //no files, we're done
                return -1;
            }

            $iterator = $DB->request([
                'SELECT'  => 'id',
                'FROM'    => $itemtype::getTable(),
                'WHERE'   => ['id' => array_keys($ids)],
            ]);

            if (count($iterator) === count($ids)) {
                //all assets are still present, we're done
                return -1;
            }

            //find missing assets
            $orphans = array_diff(
                array_keys($ids),
                array_keys(iterator_to_array($iterator))
            );

            foreach ($orphans as $orphan) {
                $dropfile = $ids[$orphan];
                try {
                    unlink($dropfile->getRealPath());
                    $message = sprintf(
                        __('File %1$s %2$s has been removed'),
                        $itemtype,
                        $dropfile->getFileName()
                    );
                } catch (FilesystemException $e) {
                    global $PHPLOGGER;
                    $PHPLOGGER->error(
                        sprintf('Unable to remove file %1$s', $dropfile->getRealPath()),
                        ['exception' => $e]
                    );
                    $message = sprintf(
                        __('File %1$s %2$s has not been removed'),
                        $itemtype,
                        $dropfile->getFileName()
                    );
                }
                if ($task) {
                    $task->log($message);
                    $task->addVolume(1);
                } else {
                    Session::addMessageAfterRedirect(htmlescape($message));
                }
            }
        }

        return 1;
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

    protected function getExtraItemtypes(): array
    {
        $definitions = AssetDefinitionManager::getInstance()->getDefinitions(true);
        $itemtypes = [];
        foreach ($definitions as $definition) {
            if ($definition->hasCapacityEnabled(new IsInventoriableCapacity())) {
                $itemtypes[] = $definition->getAssetClassName();
            }
        }
        return $itemtypes;
    }
}
