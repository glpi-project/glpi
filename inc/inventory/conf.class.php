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

use CommonDevice;
use CommonGLPI;
use DeviceBattery;
use DeviceControl;
use DeviceDrive;
use DeviceGraphicCard;
use DeviceHardDrive;
use DeviceMemory;
use DeviceProcessor;
use DeviceSimcard;
use DeviceSoundCard;
use Html;
use NetworkPortType;
use Session;
use Toolbox;
use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * Inventory configuration
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
      'vm_as_computer'                 => 0
   ];


   /**
    * Display form for import the XML
    *
    * @return void
    */
   function showUploadForm() {
      echo "<form action='' method='post' enctype='multipart/form-data'>";
      echo "<table class='tab_cadre'>";
      echo "<tr>";
      echo "<th>";
      echo __('Import inventory file');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo sprintf(
         __("You can use this menu to upload any inventory file. The file must have a known extension (%1\$s).\n"),
         implode(', ', $this->knownInventoryExtensions())
      );
      echo '<br/>'.__('It is also possible to upload a compressed archive directly with a collection of inventory files.');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td align='center'>";
      echo "<input type='file' name='importfile' value=''/>";
      echo "&nbsp;<input type='submit' value='".__('Import')."' class='submit'/>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";

      Html::closeForm();
   }

   /**
    * Accepted file extension for inventories
    *
    * @return array
    */
   public function knownInventoryExtensions() :array {
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
   public function importFile($files): Request {
      ini_set("memory_limit", "-1");
      ini_set("max_execution_time", "0");

      $path = $files['importfile']['tmp_name'];
      $name = $files['importfile']['name'];

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
   public function isInventoryFile($name): bool {
      return preg_match('/\.('.implode('|', $this->knownInventoryExtensions()).')/i', $name);
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
   protected function importContentFile(Request $inventory_request, $path, $contents) {
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
            Session::addMessageAfterRedirect(
               __('File has not been imported:') . " " . $inventory_request->getResponse(),
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

   function defineTabs($options = []) {
      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case __CLASS__ :
            $tabs = [
               1 => __('Configuration'),
               2 => __('Import from file')
            ];
            return $tabs;
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showConfigForm();
               break;

            case 2 :
               $item->showUploadForm();
               break;
         }
      }
      return true;
   }

   /**
    * Print the config form for display
    *
    * @return void
   **/
   public function showConfigForm() {
      global $CFG_GLPI;

      $config = \Config::getConfigurationValues('Inventory');
      $canedit = \Config::canUpdate();

      if ($canedit) {
         echo "<form name='form' action='".$CFG_GLPI['root_doc']."/front/inventory.conf.php' method='post'>";
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
      echo "<label for='states_id_default'>";
      echo __('Default status');
      echo "</label>";
      echo "</td>";
      echo "<td>";
      \Dropdown::show(
         'State', [
            'name'   => 'states_id_default',
            'id'     => 'states_id_default',
            'value'  => $config['states_id_default']
         ]);
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
      //echo \Rule::getTypeName(Session::getPluralNumber());
      echo __('Related configurations');
      echo "</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";

      foreach (['Asset', 'Entity'] as $col_name) {
         $col_class = 'RuleImport' . $col_name . 'Collection';
         $collection = new $col_class;
         $rules = $collection->getRuleClass();
         echo "<td colspan='2'>";
         echo sprintf(
             "<a href='%s'>%s</a>",
             $rules->getSearchURL(),
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
      echo "<label for='vm_type'>";
      echo \ComputerType::getTypeName(1);
      echo "</label>";
      echo "</td>";
      echo "<td>";
      \Dropdown::show(
         'ComputerType', [
            'name'   => 'vm_type',
            'id'     => 'vm_type',
            'value'  => $config['vm_type']
         ]);
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
      echo "<label for='import_vm'>";
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
      echo "<span class='red'>".__('Will attempt to create components from VM information sent from host, do not use if you plan to inventory any VM directly!')."</span>";
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

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='7' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
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
   public function saveConf(array $values) {
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
         Toolbox::logWarning($msg);
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
   public function __get($name) {
      if (!count($this->currents)) {
         $config = \Config::getConfigurationValues('Inventory');
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
         Toolbox::logWarning($msg);
         Session::addMessageAfterRedirect(
            $msg,
            false,
            WARNING
         );
      }
   }

   function getRights($interface = 'central') {
      return [ READ => __('Read')];
   }

}
