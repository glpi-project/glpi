<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

// Ensure current directory when run from crontab
chdir(__DIR__);

include ('../../inc/includes.php');

$out = "";
getArgs();
confirmMigration();
checkPlugin();
TruncateCoreTables();
migratePlugin();
printOutput();
exit (0);

function getArgs() {
   if (isset($_SERVER['argv'])) {
      for ($i=1; $i<$_SERVER['argc']; $i++) {
         $it    = explode("=", $_SERVER['argv'][$i], 2);
         $it[0] = preg_replace('/^--/', '', $it[0]);

         $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
      }
   }

   if ((isset($_SERVER['argv']) && in_array('help', $_SERVER['argv']))
       || isset($_GET['help'])) {
      echo "Usage: php -q -f racks_plugin.php\n";
      echo "skip_error: Don't exit on import error\n";
      echo "truncate: Remove existing core data\n";
      echo "update_plugin: force plugin migration (you need at least version 1.8.0 files to do this ) \n";
      exit (0);
   }
}

function confirmMigration() {
   echo "\nYou're about to launch migration of plugin rack data into the core of glpi!\n";
   echo "It's better to make a backup of your existing data before.\n";
   echo textYellow("Do you want to lauch migration? (Y)es, (N)o").": ";

   $confirmation = readAnswer();
   if (!in_array($confirmation, ['y', 'yes'])) {
      exit (0);
   }

   echo textGreen("Here we go\n");
}

function checkPluginVersion($verbose = true) {
   global $out;

   $plugin = new Plugin;
   $plugin->getFromDBbyDir('racks');

   if ($verbose) {
      $out .= "- ".$plugin->fields['name']." - ".
              $plugin->fields['version']." (".
              Plugin::getState($plugin->fields['state'])."): ";
   }

   if ($plugin->fields['version'] == "1.8.0"
       && in_array($plugin->fields['state'], [Plugin::TOBECONFIGURED, Plugin::NOTACTIVATED])) {
      $out .= textGreen("OK")."\n";
      return true;
   }

   $out .= textRed("KO")."\n";
   printOutput();

   return false;
}

function checkPlugin() {
   global $DB, $out;

   $error = false;

   $out .= "\n## Check plugin version:\n\n";
   $plugin = new Plugin;
   $plugin->getFromDBbyDir('racks');
   if (!checkPluginVersion()) {
      $out .= textRed("You need at least version 1.8.0 to migrate your data")."\n";

      // try to migrate plugin
      if (isset($_GET['update_plugin'])) {
         $out.= "- Migrate plugin to last version:";
         ob_start();
         $plugin->install($plugin->fields['id']);
         ob_end_clean();

         $plugin->getFromDBbyDir('racks');
         if (!checkPluginVersion(false)) {
            exit (1);
         }

         // clean message from plugin activation
         $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      } else {
         if ($plugin->fields['version'] == "1.8.0") {
            $out .= textRed("You can try option --update_plugin\n");
         }
         printOutput();
         exit (1);
      }
   }

   $out .= "\n## Check presence of racks plugin tables\n\n";
   $rack_tables = [
      'glpi_plugin_racks_itemspecifications',
      'glpi_plugin_racks_rackmodels',
      'glpi_plugin_racks_racktypes',
      'glpi_plugin_racks_rackstates',
      'glpi_plugin_racks_roomlocations',
      'glpi_plugin_racks_racks',
      'glpi_plugin_racks_racks_items',
   ];
   foreach ($rack_tables as $table) {
      $out.= "- $table: ";
      $exist = $DB->tableExists($table);
      $error = !checkResult($exist);
   }

   checkError($error);

   return $error;
}

function TruncateCoreTables() {
   global $DB;

   if (isset($_GET['truncate'])) {
      $core_tables = [
         'glpi_rackmodels',
         'glpi_racktypes',
         'glpi_dcrooms',
         'glpi_racks',
         'glpi_items_racks',
         'glpi_pdus',
      ];

      foreach ($core_tables as $table) {
         $DB->query("TRUNCATE $table");
      }
   }
}

function migratePlugin() {
   global $DB, $out;

   $error = false;
   $out .= "\n## Migrate plugin data\n\n";

   // create fake DC/Room in core to import Racks from plugin
   $out.= "- Create Datacenter: ";
   $dc = new Datacenter;
   $dc_fields = [
      'name' => 'Temp Datacenter (from plugin racks migration script)',

   ];
   if (!$dc_id = $dc->getFromDBByCrit($dc_fields)) {
      $dc_id = $dc->add($dc_fields);
   }
   $error = !checkResult($dc_id);
   checkError($error);

   // migrate others models and items
   $out.= "- Import other models:\n\n";
   $iterator_othermodels = $DB->request([
      'FROM' => 'glpi_plugin_racks_othermodels'
   ]);
   $old_othermodel = [];
   if ($nb_othermodels = count($iterator_othermodels)) {
      $out.= textYellow("  Other items don't exist in GLPI core.\n");
      $out.= "  We found $nb_othermodels models for other items. For each, we'll ask you where you want to import it.\n";
      $out.= "  You need to answer: \n";
      $out.= "   - (C)omputer,\n";
      $out.= "   - (N)etworkEquipment,\n";
      $out.= "   - (P)eripheral,\n";
      $out.= "   - Pd(U),\n";
      $out.= "   - (M)onitor,\n";
      $out.= "   - Or (I)gnore.\n\n";

      foreach ($iterator_othermodels as $othermodel) {
         $model_lbl = "  * ".$othermodel['name'];
         if (strlen($othermodel['comment'])) {
            $model_lbl.= " (".$othermodel['comment'].")";
         }
         $model_lbl.=": ";
         $out.= textYellow($model_lbl);
         $answer = readAnswer();
         $out.= "    ";

         // transform input choice
         $new_model_itemtype = false;
         if (in_array($answer, ['c', 'computer'])) {
            $new_model_itemtype = "ComputerModel";
         } else if (in_array($answer, ['n', 'networkequipment'])) {
            $new_model_itemtype = "NetworkEquipmentModel";
         } else if (in_array($answer, ['p', 'peripheral'])) {
            $new_model_itemtype = "PeripheralModel";
         } else if (in_array($answer, ['u', 'pdu'])) {
            $new_model_itemtype = "PduModel";
         } else if (in_array($answer, ['m', 'monitor'])) {
            $new_model_itemtype = "MonitorModel";
         }

         // import model
         if ($new_model_itemtype !== false) {
            $new_model = new $new_model_itemtype;
            $new_model_fields = Toolbox::sanitize([
               'name'    => $othermodel['name'],
               'comment' => $othermodel['comment'],
            ]);
            if (!$new_model->getFromDBByCrit($new_model_fields)
                || !$newmodel_id = $new_model->getID()) {
               $newmodel_id = $new_model->add($new_model_fields);
            }

            if (!checkResult($newmodel_id, true)) {
               $error = true;
            } else {
               $old_othermodel[$othermodel['id']] = "$new_model_itemtype:$newmodel_id";

               // replace itemtype in specifications
               $DB->update("glpi_plugin_racks_itemspecifications", [
                  'itemtype' => $new_model_itemtype,
                  'model_id' => $newmodel_id,
               ], [
                  'itemtype' => 'PluginRacksOtherModel',
                  'model_id' => $othermodel['id']
               ]);
            }
         } else {
            $out.= "\n";
         }
      }

      // import items (linked to other imported models)
      $iterator_others = $DB->request([
         'FROM' => 'glpi_plugin_racks_others'
      ]);
      if (count($iterator_others)) {
         $out.= "- Import other items:\n\n";
         foreach ($iterator_others as $other) {
            // pass import if other model previously ignored
            if (!isset($old_othermodel[$other['plugin_racks_othermodels_id']])) {
               continue;
            }

            $old_model = $old_othermodel[$other['plugin_racks_othermodels_id']];
            list($new_model_itemtype, $new_models_id) = explode(':', $old_model);
            $new_itemtype = str_replace('Model', '', $new_model_itemtype);
            $fk_new_model = getForeignKeyFieldForItemType($new_model_itemtype);

            $out.= "   * $new_itemtype - ".$other['name'].": ";

            $new_item = new $new_itemtype;
            $new_item_fields = Toolbox::sanitize([
               'name'        => strlen($other['name'])
                                 ? $other['name']
                                 : $other['id'],
               'entities_id' => $other['entities_id'],
               $fk_new_model => $new_models_id
            ]);

            $new_items_id = $new_item->add($new_item_fields);

            if (!checkResult($new_items_id, true)) {
               $error = true;
            } else {
               // replace itemtype in racks items
               $DB->update("glpi_plugin_racks_racks_items", [
                  'itemtype' => $new_model_itemtype,
                  'items_id' => $new_items_id,
               ], [
                  'itemtype' => 'PluginRacksOtherModel',
                  'items_id' => $other['id']
               ]);
            }
         }
      }
   } else {
      $out.= "  None found\n";
   }
   checkError($error);

   // migrate specifications
   $out.= "- Import specifications:\n";
   $old_specs = [];
   $iterator_specs = $DB->request([
      'FROM'  => 'glpi_plugin_racks_itemspecifications',
      'ORDER' => 'id ASC'
   ]);
   foreach ($iterator_specs as $spec) {
      $out.= "   * ".$spec['itemtype']." (".$spec['model_id']."): ";
      if (class_exists($spec['itemtype'])) {
         $model = new $spec['itemtype'];

         if (!$model->getFromDB($spec['model_id'])) {
            $out.= textYellow("Not found\n");
            continue;
         }

         $model_updated = $model->update([
            'id'                => $spec['model_id'],
            'required_units'    => $spec['size'],
            'depth'             => ($spec['length'] == 1
                                       ? 1
                                       : 0.5),
            'weight'            => $spec['weight'],
            'is_half_rack'      => 0,
            'power_connections' => $spec['nb_alim'],
            //'power_consumption' => $spec['amps'], // TODO amps to power conversion
         ]);

         if (!checkResult($model->getID(), true)) {
            $error = true;
         }
         $old_specs[$spec['id']] = $model->fields;
      } else {
         $out.= textYellow("Not found\n");
      }
   }
   checkError($error);

   //migrate rack models
   $out.= "- Import rack models:\n";
   $old_models = [];
   $iterator_rackmodels = $DB->request([
      'FROM' => 'glpi_plugin_racks_rackmodels'
   ]);
   foreach ($iterator_rackmodels as $old_model) {
      $rackmodel = new RackModel;
      $out.= "   * ".$old_model['name'].": ";
      $rackmodel_fields = Toolbox::sanitize([
         'name'    => $old_model['name'],
         'comment' => $old_model['comment'],
      ]);
      if (!$rackmodel->getFromDBByCrit($rackmodel_fields)
          || !$rackmodel_id = $rackmodel->getID()) {
         $rackmodel_id = $rackmodel->add($rackmodel_fields);
      }
      if (!checkResult($rackmodel_id, true)) {
         $error = true;
      }
      $old_models[$old_model['id']] = $rackmodel_id;
   }
   checkError($error);

   // migrate types
   $out.= "- Import rack types:\n";
   $old_types = [];
   $iterator_types = $DB->request([
      'FROM' => 'glpi_plugin_racks_racktypes'
   ]);
   foreach ($iterator_types as $old_type) {
      $type = new RackType;
      $out.= "   * ".$old_type['name'].": ";
      $type_fields = Toolbox::sanitize([
         'name'         => $old_type['name'],
         'entities_id'  => $old_type['entities_id'],
         'is_recursive' => $old_type['is_recursive'],
         'comment'      => $old_type['comment'],
      ]);
      if (!$type->getFromDBByCrit($type_fields)
          || !$types_id = $type->getID()) {
         $types_id = $type->add($type_fields);
      }
      if (!checkResult($types_id, true)) {
         $error = true;
      }
      $old_types[$old_type['id']] = $types_id;
   }
   checkError($error);

   //migrate states
   $out.= "- Import rack states:\n";
   $old_states = [];
   $iterator_states = $DB->request([
      'FROM' => 'glpi_plugin_racks_rackstates'
   ]);
   foreach ($iterator_states as $old_state) {
      $state = new State;
      $out.= "   * ".$old_state['name'].": ";
      $state_fields = Toolbox::sanitize([
         'name'         => $old_state['name'],
         'states_id'    => 0,
      ]);
      if (!$state->getFromDBByCrit($state_fields)
          || !$state_id = $state->getID()) {
         $state_fields['comment']      = $old_state['comment'];
         $state_fields['entities_id']  = $old_state['entities_id'];
         $state_fields['is_recursive'] = $old_state['is_recursive'];
         $state_id = $state->add($state_fields);
      }
      if (!checkResult($state_id, true)) {
         $error = true;
      }
      $old_states[$old_state['id']] = $state_id;
   }
   checkError($error);

   // migrate room
   $out.= "- Import rooms:\n";
   $old_rooms = [];
   $iterator_room = $DB->request([
      'FROM' => 'glpi_plugin_racks_roomlocations'
   ]);
   foreach ($iterator_room as $old_room) {
      $room = new DCRoom;
      $out.= "   * ".$old_room['completename'].": ";
      $room_fields = Toolbox::sanitize([
         'name'           => $old_room['completename'],
         'entities_id'    => $old_room['entities_id'],
         'is_recursive'   => 1,
         'datacenters_id' => $dc_id,
         'vis_cols'       => 10,
         'vis_rows'       => 10,
      ]);
      if (!$room->getFromDBByCrit($room_fields)
          || !$room_id = $room->getID()) {
         $room_id = $room->add($room_fields);
      }
      if (!checkResult($room_id, true)) {
         $error = true;
      }
      $old_rooms[$old_room['id']] = $room_id;
   }
   checkError($error);

   // create a temp room if needed
   if (count($DB->request([
      'FROM'  => 'glpi_plugin_racks_racks',
      'WHERE' => [
         'plugin_racks_roomlocations_id' => 0
      ]
   ]))) {
      $room = new DCRoom;
      $room_fields = [
         'name'           => 'Temp room (from plugin racks migration script)',
         'entities_id'    => 0,
         'is_recursive'   => 1,
         'datacenters_id' => $dc_id,
         'vis_cols'       => 10,
         'vis_rows'       => 10,
      ];
      if (!$room->getFromDBByCrit($room_fields)
          || !$tmp_room_id = $room->getID()) {
         $tmp_room_id = $room->add($room_fields);
      }
   }

   // migrate racks
   $out.= "- Import racks:\n";
   $old_racks = [];
   $iterator_racks = $DB->request([
      'FROM' => 'glpi_plugin_racks_racks'
   ]);
   $i = 0;
   foreach ($iterator_racks as $old_rack) {
      $rack = new Rack;
      $out.= "   * ".$old_rack['name'].": ";
      $rack_fields = Toolbox::sanitize([
         'name'             => $old_rack['name'],
         'comment'          => "Imported from rack plugin",
         'entities_id'      => $old_rack['entities_id'],
         'is_recursive'     => $old_rack['is_recursive'],
         'locations_id'     => $old_rack['locations_id'],
         'serial'           => $old_rack['serial'],
         'rackmodels_id'    => getNewID($old_models, $old_rack['plugin_racks_rackmodels_id']),
         'manufacturers_id' => $old_rack['manufacturers_id'],
         'racktypes_id'     => getNewID($old_types, $old_rack['plugin_racks_racktypes_id']),
         'states_id'        => getNewID($old_states, $old_rack['plugin_racks_rackstates_id']),
         'users_id_tech'    => $old_rack['users_id_tech'],
         'groups_id_tech'   => $old_rack['groups_id_tech'],
         'width'            => (int) $old_rack['width'],
         'height'           => (int) $old_rack['height'],
         'depth'            => (int) $old_rack['depth'],
         'max_weight'       => (int) $old_rack['weight'],
         'number_units'     => $old_rack['rack_size'],
         'is_template'      => $old_rack['is_template'],
         'template_name'    => $old_rack['template_name'],
         'is_deleted'       => $old_rack['is_deleted'],
         'dcrooms_id'       => getNewID($old_rooms,
                                        $old_rack['plugin_racks_roomlocations_id'],
                                        $tmp_room_id),
         'bgcolor'          => "#FEC95C",
      ]);
      if (!$rack->getFromDBByCrit($rack_fields)
          || !$rack_id = $rack->getID()) {
         $rack_fields['position'] = "9999999999999,-".(++$i);
         $rack_id = $rack->add($rack_fields);
      }
      if (!checkResult($rack_id, true)) {
         $error = true;
      }
      $old_racks[$old_rack['id']] = $rack_id;
   }
   checkError($error);

   // migrate rack items
   $out.= "- Import rack items:\n";
   $iterator_rackitems = $DB->request([
      'FROM' => 'glpi_plugin_racks_racks_items',
      'ORDER' => 'id'
   ]);
   $item_rack = new Item_Rack;
   foreach ($iterator_rackitems as $old_itemrack) {
      $item_rack->getEmpty();

      $itemtype = str_replace('Model', '', $old_itemrack['itemtype']);
      $out.= "   * $itemtype (".$old_itemrack['items_id']."): ";

      if (!class_exists($old_itemrack['itemtype'])) {
         $out.= textYellow("Type not found\n");
         continue;
      }

      $model = new $old_itemrack['itemtype'];
      $item = new $itemtype;
      if (!$item->getFromDB($old_itemrack['items_id'])) {
         $out.= textYellow("Not found\n");
         continue;
      }

      // compute position:
      // - plugin defines it by top of the item
      // - core defines it by bottom
      $required_units = 1;
      $models_id = $item->fields[getForeignKeyFieldForTable($model::getTable())];
      if ($model->getFromDB($models_id)) {
         $required_units = $model->fields['required_units'];
      }
      $position = $old_itemrack['position'] - $required_units + 1;

      $itemrack_fields = Toolbox::sanitize([
         'racks_id'    => getNewID($old_racks, $old_itemrack['plugin_racks_racks_id']),
         'itemtype'    => $itemtype,
         'items_id'    => $old_itemrack['items_id'],
         'position'    => $position,
         'hpos'        => 0,
         'bgcolor'     => '#69CEBA',
         'orientation' => ($old_itemrack['faces_id'] == 1
                              ? Rack::FRONT
                              : Rack::REAR),
      ]);
      if (!$item_rack->getFromDBByCrit($itemrack_fields)
          || !$rack_items_id = $item_rack->getID()) {
         $rack_items_id = $item_rack->add($itemrack_fields);
      }
      if (!checkResult($rack_items_id, true)) {
         $error = true;
      }
   }
   checkError($error);

   $out.= "Everything seems OK\n";
   printOutput();
}


function getNewID($matches_collection, $old_id, $default = 0) {
   if (isset($matches_collection[$old_id])) {
      return $matches_collection[$old_id];
   }
   return $default;
}

function printOutput() {
   global $out;

   if (!isCommandLine()) {
      $out = nlbr($out);
   }
   echo $out;
   $out = "";
}

function checkError($error = false) {
   printOutput();
   if ($error
       && (!isset($_GET['skip_error']) || !$_GET['skip_error'])) {
      echo textRed("\nSome errors triggered, aborting!\n");
      if (count($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
         var_dump($_SESSION['MESSAGE_AFTER_REDIRECT']);
      }
      exit (1);
   }
   flush();
}

function checkResult($result = 0, $print_result = false) {
   global $out;

   $error = false;

   if ($result !== false && $result > 0) {
      $out.= textGreen("OK");
      if ($print_result) {
         $out.= " (".$result.")";
      }
   } else {
      $out.= textRed("KO");
      if ($print_result) {
         if ($result === false) {
            $result = "false";
         }
         $out.= " (".$result.")";
      }
      $error = true;
   }
   $out.= "\n";
   printOutput();

   return !$error;
}

function textGreen($text = "") {
   if (isCommandLine()) {
      return "\033[32m$text\033[0m";
   } else {
      return "<span style='color=green;'>$text</span>";
   }
}

function textRed($text = "") {
   if (isCommandLine()) {
      return "\033[31m$text\033[0m";
   } else {
      return "<span style='color=red;'>$text</span>";
   }
}

function textYellow($text = "") {
   if (isCommandLine()) {
      return "\033[33m$text\033[0m";
   } else {
      return "<span style='color=yellow;'>$text</span>";
   }
}

function readAnswer() {
   printOutput();
   return strtolower(trim(fgets(STDIN)));
}
