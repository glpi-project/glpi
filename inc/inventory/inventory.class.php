<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Inventory;

use Agent;
use CommonDBTM;
use Glpi\Inventory\Asset\InventoryAsset;
use Lockedfield;
use RefusedEquipment;
use Session;
use Toolbox;

/**
 * Handle inventory request
 */
class Inventory
{
   const FULL_MODE = 0;
   const INCR_MODE = 1;

   /** @var integer */
   protected $mode;
   /** @var \stdClass */
   protected $raw_data = null;
   /** @var array */
   protected $data = [];
   /** @var array */
   private $metadata;
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
   /** @var string */
   private $inventory_id;
   /** @var integer */
   private $inventory_format;
   /** @var InventoryAsset */
   private $mainasset;

   /**
    * @param mixed   $data   Inventory data, optionnal
    * @param integer $mode   One of self::*_MODE
    * @param integer $format One of Request::*_MODE
    */
   public function __construct($data = null, $mode = self::FULL_MODE, $format = Request::JSON_MODE) {
      $this->mode = $mode;
      $this->conf = new Conf();
      $this->inventory_id = Toolbox::getRandomString(30);

      if (null !== $data) {
         $this->setData($data, $format);
         $this->doInventory();
      }
   }

   public function setMode($mode = self::FULL_MODE): Inventory {
      $this->mode = $mode;
      return $this;
   }

   /**
    * Set data, and convert them if we're using legacy format
    *
    * @param mixed   $data   Inventory data, optionnal
    * @param integer $format One of self::*_FORMAT
    *
    * @return boolean
    */
   public function setData($data, $format = Request::JSON_MODE) :bool {

      // Write inventory file
      $dir = GLPI_INVENTORY_DIR . '/';
      if (!is_dir($dir)) {
         mkdir($dir);
      }

      $converter = new Converter;
      if (Request::XML_MODE === $format) {
         $this->inventory_format = Request::XML_MODE;
         file_put_contents($dir . '/'. $this->inventory_id . '.xml', $data->asXML());
         //convert legacy format
         $data = $converter->convert($data->asXML());
      } else {
         file_put_contents($dir . '/'. $this->inventory_id . '.json', json_encode(json_decode($data), JSON_PRETTY_PRINT));
      }

      try {
         $converter->validate($data);
      } catch (\RuntimeException $e) {
         $this->errors[] = $e->getMessage();
         return false;
      }

      $this->raw_data = json_decode($data);
      $this->extractMetadata();
      return true;
   }

   /**
    * Prepare inventory data
    *
    * @return array
    */
   public function extractMetadata() :array {
      //check
      if ($this->inError()) {
         throw new \RuntimeException(print_r($this->getErrors(), true));
      }

      $this->metadata = [
           'deviceid'  => $this->raw_data->deviceid,
           'version'   => $this->raw_data->content->versionclient,
           'itemtype'   => $this->raw_data->itemtype ?? 'Computer'
       ];

      // Get tag if defined
      if (property_exists($this->raw_data->content, 'accountinfo')) {
         $ainfos = $this->raw_data->content->accountinfo;
         if (property_exists($ainfos, 'keyname')
            && $ainfos->keyname == 'TAG'
            && property_exists($ainfos, 'keyvalue')
            && $ainfos->keyvalue != ''
         ) {
            $this->metadata['tag'] = $ainfos->keyvalue;
         }
      }

      return $this->metadata;
   }

   /**
    * Do inventory
    *
    * @param boolean $test_rules Only to test rules, do not store anything
    *
    * @return array
    */
   public function doInventory($test_rules = false) {
      global $DB;

      //check
      if ($this->inError()) {
         throw new \RuntimeException(print_r($this->getErrors(), true));
      }

      if (!isset($_SESSION['glpiinventoryuserrunning'])) {
          $_SESSION['glpiinventoryuserrunning'] = 'inventory';
      }

      try {
         //bench
         $main_start = microtime(true);
         if (!$DB->inTransaction()) {
            $DB->beginTransaction();
         }

         $converter = new Converter;
         $schema = json_decode(file_get_contents($converter->getSchemaPath()), true);

         $properties = array_keys($schema['properties']['content']['properties']);
         unset($properties['versionclient']); //already handled in extractMetadata
         if (method_exists($this, 'getSchemaExtraProps')) {
            $properties = array_merge(
               $properties,
               $this->getSchemaExtraProps()
            );
         }
         $contents = $this->raw_data->content;
         $all_props = get_object_vars($contents);

         $data = [];
         //parse schema properties and handle if it exists in raw_data
         foreach ($properties as $property) {
            if (property_exists($contents, $property)) {
               $this->metadata['provider'] = [];

               $sub_properties = [];
               if (isset($schema['properties']['content']['properties'][$property]['properties'])) {
                  $sub_properties = array_keys($schema['properties']['content']['properties'][$property]['properties']);
               }
               if (method_exists($this, 'getSchemaExtraSubProps')) {
                  $sub_properties = array_merge(
                     $sub_properties,
                     $this->getSchemaExtraSubProps($property)
                  );
               }

               switch ($property) {
                  case 'versionprovider':
                     foreach ($sub_properties as $sub_property) {
                        if (property_exists($contents->$property, $sub_property)) {
                           $this->metadata['provider'][$sub_property] = $contents->$property->$sub_property;
                        }
                     }
                     unset($all_props['versionprovider']);
                     break;
                  default:
                     if (count($sub_properties)) {
                        $data[$property] = [];
                        foreach ($sub_properties as $sub_property) {
                           if (property_exists($contents->$property, $sub_property)) {
                              $data[$property][$sub_property] = $contents->$property->$sub_property;
                           }
                        }
                     } else {
                        $data[$property] = $contents->$property;
                     }
                     break;
               }
            }
         }

         $this->unhandled_data = array_diff_key($all_props, $data);
         if (count($this->unhandled_data)) {
            Session::addMessageAfterRedirect(
               sprintf(
                  __('Following keys has been ignored during process: %1$s'),
                  implode(
                     ', ',
                     array_keys($this->unhandled_data)
                  )
               ),
               true,
               WARNING
            );
            Toolbox::logDebug(
               $this->unhandled_data
            );
         }

         $this->data = $data;

         //create/load agent
         $this->agent = new Agent();
         $this->agent->handleAgent($this->metadata);

         $this->item = new $this->agent->fields['itemtype'];

         if (!empty($this->agent->fields['items_id'])) {
            $this->item->getFromDB($this->agent->fields['items_id']);
         }

         $main_class = $this->getMainClass();
         $main = new $main_class($this->item, $this->raw_data);
         $main->setAgent($this->getAgent());
         $main->setExtraData($this->data);

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

            if (!defined('TU_USER')) {
               $DB->commit();
            }
         }
      } catch (\Exception $e) {
         Toolbox::logError($e);
         $DB->rollback();
         throw $e;
      } finally {
         unset($_SESSION['glpiinventoryuserrunning']);
         $this->handleInventoryFile();
         // * For benchs
         $id = $this->item->fields['id'] ?? 0;
         $this->addBench($this->item->getType() . ' #' . $id, 'full', $main_start);
         $this->printBenchResults();
      }

      return [];
   }

   /**
    * Handle inventory file
    *
    * @return void
    */
   private function handleInventoryFile() {
      $ext = (Request::XML_MODE === $this->inventory_format ? 'xml' : 'json');
      $tmpfile = sprintf('%s/%s.%s', GLPI_INVENTORY_DIR, $this->inventory_id, $ext);

      $items = $this->mainasset->getInventoried();

      foreach ($this->mainasset->getRefused() as $refused) {
         $items[] = $refused;
      }

      foreach ($items as $item) {
         $itemtype = $item->getType();
         if (!isset($item->fields['id']) || empty($item->fields['id'])) {
            throw new \RuntimeException('Item ID is missing :(');
         }
         $id = $item->fields['id'];

         $dir = GLPI_INVENTORY_DIR . '/' . Toolbox::slugify($itemtype);
         if (!is_dir($dir)) {
            mkdir($dir);
         }
         $filename = sprintf('%s/%s.%s', $dir, $id, $ext);
         copy($tmpfile, $filename);
      }

      if (file_exists($tmpfile)) {
         //Toolbox::logWarning('Nothing to do, inventory temp file will be removed');
         unlink($tmpfile);
      }
   }

   /**
    * Get error
    *
    * @return array
    */
   public function getErrors() :array {
      return $this->errors;
   }

   /**
    * Check if erorrs has been throwed
    *
    * @return boolean
    */
   public function inError() :bool {
      return (bool)count($this->errors);
   }

   static function getMenuContent() {
      $classes = [
         Agent::class,
         Lockedfield::class,
         RefusedEquipment::class
      ];
      $links = [];
      foreach ($classes as $class) {
         $entry = "<i class=\"". $class::getIcon() . " pointer\" title=\"" . $class::getTypeName(Session::getPluralNumber()) .
         "\"></i><span class=\"sr-only\">" . $class::getTypeName(Session::getPluralNumber()). "</span>";
         $links[$entry] = $class::getSearchURL(false);
      }

      $menu = [
         'title'  => __('Inventory'),
         'page'   => '/front/inventory.conf.php',
         'icon'   => static::getIcon(),
         'options'   => [
            'agent' => [
               'title' => Agent::getTypeName(Session::getPluralNumber()),
               'page'  => Agent::getSearchURL(false),
               'links' => [
                  'add'    => '/front/agent.form.php',
                  'search' => '/front/agent.php',
               ] + $links
               ],
            'lockedfield' => [
               'title' => Lockedfield::getTypeName(Session::getPluralNumber()),
               'page'  => Lockedfield::getSearchURL(false),
               'links' => $links
            ],
            'refusedequipment' => [
               'title' => RefusedEquipment::getTypeName(Session::getPluralNumber()),
               'page'  => RefusedEquipment::getSearchURL(false),
               'links' => $links
            ],
         ],
         'links' => $links,
      ];

      if (count($menu)) {
         return $menu;
      }
      return false;
   }

   /**
    * Retrieve main inventoried object class
    *
    * @return string
    */
   public function getMainClass() {
      $agent = $this->getAgent();
      $main_class = '\Glpi\Inventory\Asset\\' . $agent->fields['itemtype'];
      return $main_class;
   }

   /**
    * Process and enhance data
    *
    * @return void
    */
   public final function processInventoryData() {
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
            case 'remote_mgmt': //not used - implemented in FI only
            case 'slots': //not used
            case 'versionclient': //not used
            case 'versionprovider': //not provided see doInventory
            case 'licenseinfos': //not used - implemented in FI only
            case 'modems': //not used - implemented in FI only
            case 'accountinfo': //handled from Asset\Computer
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
            $asset = new $assettype($this->item, $value);
            $asset->withHistory($this->mainasset->withHistory());
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
    * @return void;
    */
   public function handleItem() {
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
   public function getAgent() {
      return $this->agent;
   }

   /**
    * Add bench value
    *
    * @param string  $asset Asset
    * @param string  $type Either prepare or handle
    * @param integer $start Start time
    *
    * @return void
    */
   protected function addBench($asset, $type, $start) {
      $exec_time = round(microtime(true) - $start, 5);
      $this->benchs[$asset][$type] = [
         'exectime'  => $exec_time,
         'mem'       => memory_get_usage(),
         'mem_real'  => memory_get_usage(true),
         'mem_peak'  => memory_get_peak_usage()

      ];
   }

   /**
    * Display bench results
    *
    * @return void
    */
   public function printBenchResults() {
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
               } else {
                  $output .= Toolbox::getSize($value);
               }
               $output .= "\n";
            }
         }
      }

      Toolbox::logInFile(
         "bench_inventory",
         $output
      );
   }

   static function getIcon() {
      return "fas fa-cloud-download-alt";
   }

   public function getMetadata(): array {
      return $this->metadata;
   }

   public function getMainAsset(): InventoryAsset {
      return $this->mainasset;
   }

   public function getItem(): CommonDBTM {
      return $this->item;
   }

   static function cronInfo($name) {
      switch ($name) {
         case 'cleantemp' :
            return ['description' => __('Clean temporary files created from inventories')];

         case 'cleanorphans' :
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
   static public function cronCleantemp($task) {
      $cron_status = 0;

      $conf = new Conf();
      $temp_files = glob(GLPI_INVENTORY_DIR . '/*.{' .implode(',', $conf->knownInventoryExtensions()) . '}', GLOB_BRACE);

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
   static public function cronCleanorphans($task) {
      global $DB;

      $cron_status = 0;

      $conf = new Conf();
      $existing_types = glob(GLPI_INVENTORY_DIR . '/*', GLOB_ONLYDIR);

      foreach ($existing_types as $existing_type) {
         $itemtype = str_replace(GLPI_INVENTORY_DIR . '/', '', $existing_type);
         //$invnetoryfiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('path/to/folder'));
         $inventory_files = new \RegexIterator(
            new \RecursiveIteratorIterator(
               new \RecursiveDirectoryIterator($existing_type)
            ),
            "/\\.(".implode('|', $conf->knownInventoryExtensions()).")\$/i"
         );

         $ids = [];
         foreach ($inventory_files as $inventory_file) {
            $ids[preg_replace("/\\.(".implode('|', $conf->knownInventoryExtensions()).")\$/i", '', $inventory_file->getFileName())] = $inventory_file;
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
            unlink($dropfile);
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

}
