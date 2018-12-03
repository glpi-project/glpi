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

namespace Glpi\Console\Migration;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CommonDBTM;
use Computer;
use ComputerModel;
use Datacenter;
use DB;
use DCRoom;
use Item_Rack;
use Monitor;
use MonitorModel;
use NetworkEquipment;
use NetworkEquipmentModel;
use Peripheral;
use PeripheralModel;
use Plugin;
use Pdu;
use PduModel;
use Rack;
use RackModel;
use RackType;
use State;
use Toolbox;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RacksPluginToCoreCommand extends AbstractCommand {

   /**
    * Error code returned if plugin version or plugin data is invalid.
    *
    * @var integer
    */
   const ERROR_PLUGIN_VERSION_OR_DATA_INVALID = 1;

   /**
    * Error code returned if import failed.
    *
    * @var integer
    */
   const ERROR_PLUGIN_IMPORT_FAILED = 1;

   /**
    * Version of Racks plugin required for this migration.
    * @var string
    */
   const RACKS_REQUIRED_VERSION = '1.8.0';

   /**
    * Choice value for other type: ignore.
    * @var string
    */
   const OTHER_TYPE_CHOICE_IGNORE = 'i';

   /**
    * Choice value for other type: computer.
    * @var string
    */
   const OTHER_TYPE_CHOICE_COMPUTER = 'c';

   /**
    * Choice value for other type: network equipment.
    * @var string
    */
   const OTHER_TYPE_CHOICE_NETWORKEQUIPEMENT = 'n';

   /**
    * Choice value for other type: peripheral.
    * @var string
    */
   const OTHER_TYPE_CHOICE_PERIPHERAL = 'p';

   /**
    * Choice value for other type: pdu.
    * @var string
    */
   const OTHER_TYPE_CHOICE_PDU = 'u';

   /**
    * Choice value for other type: monitor.
    * @var string
    */
   const OTHER_TYPE_CHOICE_MONITOR = 'm';

   /**
    * Datacenter on which rooms will be created.
    *
    * @var integer
    */
   private $datacenter_id;

   /**
    * Room on which racks will be placed if no corresponding room found.
    *
    * @var integer
    */
   private $fallback_room_id;

   /**
    * Imported elements mapping.
    *
    * @var array
    */
   private $elements_mapping;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:racks_plugin_to_core');
      $this->setDescription(__('Migrate Racks plugin data into GLPI core tables'));

      $this->addOption(
         'ignore-other-elements',
         'i',
         InputOption::VALUE_NONE,
         __('Ignore "PluginRacksOther" models and elements')
      );

      $this->addOption(
         'skip-errors',
         's',
         InputOption::VALUE_NONE,
         __('Do not exit on import errors')
      );

      $this->addOption(
         'truncate',
         't',
         InputOption::VALUE_NONE,
         __('Remove existing core data')
      );

      $this->addOption(
         'update-plugin',
         'u',
         InputOption::VALUE_NONE,
         sprintf(
            __('Run Racks plugin update (you need version %s files to do this)'),
            self::RACKS_REQUIRED_VERSION
         )
      );

      $this->addOption(
         'without-plugin',
         'w',
         InputOption::VALUE_NONE,
         sprintf(
            __('Enable migration without plugin files (we cannot validate that plugin data are compatible with supported %s version)'),
            self::RACKS_REQUIRED_VERSION
         )
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $this->elements_mapping = []; // Clear elements mapping

      $no_interaction = $input->getOption('no-interaction');

      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)
         $output->writeln(
            [
               __('You are about to launch migration of Racks plugin data into GLPI core tables.'),
               __('It is better to make a backup of your existing data before continuing.')
            ]
         );

         /** @var QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
               '<comment>' . __('Do you want to launch migration ?') . ' [yes/No]</comment>',
               false
            )
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Migration aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      if (!$this->checkPlugin()) {
         return self::ERROR_PLUGIN_VERSION_OR_DATA_INVALID;
      }

      if ($input->getOption('truncate')) {
         $this->cleanCoreTables();
      }

      if (!$this->migratePlugin()) {
         return self::ERROR_PLUGIN_IMPORT_FAILED;
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');

      return 0; // Success
   }

   /**
    * Check that plugin state and existing data are OK for migration.
    *
    * @throws LogicException
    *
    * @return boolean
    */
   private function checkPlugin() {

      $check_version = !$this->input->getOption('without-plugin');

      if ($check_version) {
         $this->output->writeln(
            '<comment>' . __('Checking plugin version...') . '</comment>',
            OutputInterface::VERBOSITY_VERBOSE
         );

         $plugin = new Plugin();
         $plugin->checkPluginState('racks');

         if (!$plugin->getFromDBbyDir('racks')) {
            $message  = __('Racks plugin is not part of GLPI plugin list. It has never been installed or has been cleaned.')
               . ' '
               . sprintf(
                  __('You have to install Racks plugin files in version %s to be able to continue.'),
                  self::RACKS_REQUIRED_VERSION
               );
            $this->output->writeln(
               [
                  '<error>' . $message . '</error>',
               ],
               OutputInterface::VERBOSITY_QUIET
            );
            return false;
         }

         $is_version_ok = '1.8.0' === $plugin->fields['version'];
         if (!$is_version_ok) {
            $message  = sprintf(
               __('You have to install Racks plugin files in version %s to be able to continue.'),
               self::RACKS_REQUIRED_VERSION
            );
            $this->output->writeln(
               '<error>' . $message . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            return false;
         }

         $is_installable = in_array(
            $plugin->fields['state'],
            [
               Plugin::TOBECLEANED, // Can be in this state if check was done without the plugin dir
               Plugin::NOTINSTALLED, // Can be not installed if plugin has been cleaned in plugin list
               Plugin::NOTUPDATED, // Plugin 1.8.0 version has never been installed
            ]
         );
         if ($is_installable) {
            if ($this->input->getOption('update-plugin')) {
               $message  = sprintf(
                  __('Migrating plugin to %s version...'),
                  self::RACKS_REQUIRED_VERSION
               );
               $this->output->writeln(
                  '<info>' . $message . '</info>',
                  OutputInterface::VERBOSITY_NORMAL
               );

               ob_start();
               $plugin->install($plugin->fields['id']);
               ob_end_clean();

               // Reload and check migration result
               $plugin->getFromDB($plugin->fields['id']);
               if (!in_array($plugin->fields['state'], [Plugin::TOBECONFIGURED, Plugin::NOTACTIVATED])) {
                  $message  = sprintf(
                     __('Plugin migration to %s version failed.'),
                     self::RACKS_REQUIRED_VERSION
                  );
                  $this->output->writeln(
                     '<error>' . $message . '</error>',
                     OutputInterface::VERBOSITY_QUIET
                  );
                  return false;
               }
            } else {
               $message = sprintf(
                  __('Racks plugin data has to be updated to %s version. It can be done using the --update-plugin option.'),
                  self::RACKS_REQUIRED_VERSION
               );
               $this->output->writeln(
                  '<comment>' . $message . '</comment>',
                  OutputInterface::VERBOSITY_QUIET
               );
               return false;
            }
         }

         $is_state_ok   = in_array(
            $plugin->fields['state'],
            [
               Plugin::ACTIVATED, // Should not be possible as 1.8.0 is not compatible with 9.3
               Plugin::TOBECONFIGURED, // Should not be possible as check_config of plugin returns always true
               Plugin::NOTACTIVATED,
            ]
         );
         if (!$is_state_ok) {
            // Should not happens as installation should put plugin in awaited state
            throw new LogicException('Unexpected plugin state.');
         }
      }

      $rack_tables = [
         'glpi_plugin_racks_itemspecifications',
         'glpi_plugin_racks_others',
         'glpi_plugin_racks_othermodels',
         'glpi_plugin_racks_racks',
         'glpi_plugin_racks_racks_items',
         'glpi_plugin_racks_rackmodels',
         'glpi_plugin_racks_racktypes',
         'glpi_plugin_racks_rackstates',
         'glpi_plugin_racks_roomlocations',
      ];
      $missing_tables = false;
      foreach ($rack_tables as $table) {
         if (!$this->db->tableExists($table)) {
            $this->output->writeln(
               '<error>' . sprintf(__('Racks plugin table "%s" is missing.'), $table) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $missing_tables = true;
         }
      }
      if ($missing_tables) {
         $this->output->writeln(
            '<error>' . __('Migration cannot be done.') . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return false;
      }

      return true;
   }

   /**
    * Clean data from core tables.
    *
    * @throws RuntimeException
    */
   private function cleanCoreTables() {

      $core_tables = [
         'glpi_datacenters',
         'glpi_dcrooms',
         'glpi_items_racks',
         'glpi_pdus',
         'glpi_racks',
         'glpi_rackmodels',
         'glpi_racktypes',
      ];

      foreach ($core_tables as $table) {
         $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

         if (!$result) {
            throw new RuntimeException(
               sprintf('Unable to truncate table "%s"', $table)
            );
         }
      }
   }


   private  function migratePlugin() {

      $no_interaction = $this->input->getOption('no-interaction');

      $skip_errors = $this->input->getOption('skip-errors');

      // Create datacenter
      $this->datacenter_id = $this->createDatacenter();
      if (null === $this->datacenter_id && !$skip_errors) {
         return false;
      }

      if (!$this->input->getOption('ignore-other-elements')) {
         if ($no_interaction) {
            $this->output->writeln(
               '<comment>' . __('Other models and items cannot be migrated when --no-interaction option is used.') . '</comment>',
               OutputInterface::VERBOSITY_NORMAL
            );
         } else {
            if (!$this->importOtherElements() && !$skip_errors) {
               return false;
            }
         }
      }

      $failure = (!$this->importItemsSpecifications() && !$skip_errors)
         || (!$this->importRackModels() && !$skip_errors)
         || (!$this->importRackTypes() && !$skip_errors)
         || (!$this->importRackStates() && !$skip_errors)
         || (!$this->importRooms() && !$skip_errors)
         || (!$this->importRacks() && !$skip_errors)
         || (!$this->importRackItems() && !$skip_errors);

      return !$failure;
   }

   /**
    * Create temporary datacenter.
    *
    * @return null|integer Datacenter id, or null in case of failure
    */
   private function createDatacenter() {

      $this->output->writeln(
         '<comment>' . __('Creating datacenter...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );

      $dc = new Datacenter();
      $dc_fields = [
         'name' => 'Temp Datacenter (from racks plugin migration script)',

      ];

      if (!($dc_id = $dc->getFromDBByCrit($dc_fields))) {
         $dc_id = $dc->add($dc_fields);
      }

      if (false === $dc_id) {
         $this->outputImportError(
            '<error>' . __('Unable to create datacenter.') . '</error>'
         );
         return null;
      }

      return $dc_id;
   }

   /**
    * Import other models and items.
    *
    * @throws LogicException
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importOtherElements() {

      $has_errors = false;

      // Import other models
      $this->output->writeln(
         '<comment>' . __('Importing other models...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );

      $othermodels_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_othermodels'
         ]
      );

      if ($count_othermodels = $othermodels_iterator->count()) {
         $this->output->writeln(
            [
               '<comment>' . __('Other items do not exist in GLPI core.') . '</comment>',
               sprintf(
                  __('We found %d models for other items. For each, we will ask you where you want to import it.'),
                  $count_othermodels
               ),
            ],
            OutputInterface::VERBOSITY_QUIET
         );

         foreach ($othermodels_iterator as $othermodel) {
            $model_label = $othermodel['name'];
            if (strlen($othermodel['comment'])) {
               $model_label .= ' (' . $othermodel['comment'] . ')';
            }

            /** @var QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $answer = $question_helper->ask(
               $this->input,
               $this->output,
               new ChoiceQuestion(
                  sprintf(__('Where do you want to import "%s" ?'), $model_label),
                  [
                     self::OTHER_TYPE_CHOICE_COMPUTER          => __('Computer'),
                     self::OTHER_TYPE_CHOICE_NETWORKEQUIPEMENT => __('Network device'),
                     self::OTHER_TYPE_CHOICE_PERIPHERAL        => __('Peripheral'),
                     self::OTHER_TYPE_CHOICE_PDU               => __('Pdu'),
                     self::OTHER_TYPE_CHOICE_MONITOR           => __('Monitor'),
                     self::OTHER_TYPE_CHOICE_IGNORE            => __('Ignore (default)'),
                  ],
                  self::OTHER_TYPE_CHOICE_IGNORE
               )
            );

            if (self::OTHER_TYPE_CHOICE_IGNORE === $answer) {
               continue;
            }

            $new_itemtype       = null;
            $new_model_itemtype = null;
            switch ($answer) {
               case self::OTHER_TYPE_CHOICE_COMPUTER:
                  $new_itemtype       = Computer::class;
                  $new_model_itemtype = ComputerModel::class;
                  break;
               case self::OTHER_TYPE_CHOICE_NETWORKEQUIPEMENT:
                  $new_itemtype       = NetworkEquipment::class;
                  $new_model_itemtype = NetworkEquipmentModel::class;
                  break;
               case self::OTHER_TYPE_CHOICE_PERIPHERAL:
                  $new_itemtype       = Peripheral::class;
                  $new_model_itemtype = PeripheralModel::class;
                  break;
               case self::OTHER_TYPE_CHOICE_PDU:
                  $new_itemtype       = Pdu::class;
                  $new_model_itemtype = PduModel::class;
                  break;
               case self::OTHER_TYPE_CHOICE_MONITOR:
                  $new_itemtype       = Monitor::class;
                  $new_model_itemtype = MonitorModel::class;
                  break;
            }

            if (null === $new_model_itemtype) {
               throw new LogicException(
                  sprintf('Answer "%s" has no corresponding itemtype.', $answer)
               );
            }

            $new_model = new $new_model_itemtype();
            $new_model_fields = Toolbox::sanitize([
               'name'    => $othermodel['name'],
               'comment' => $othermodel['comment'],
            ]);

            if (!($new_model_id = $new_model->getFromDBByCrit($new_model_fields))
                && !($new_model_id = $new_model->add($new_model_fields))) {
               $has_errors = true;

               $message = sprintf(__('Unable to import other model "%s".'), $model_label);
               $this->outputImportError($message);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }

            $this->addElementToMapping(
               'PluginRacksOtherModel',
               $othermodel['id'],
               $new_model_itemtype,
               $new_model_id
            );

            // Import items from model
            $message = sprintf(__('Importing items from model "%s"...'), $model_label);
            $this->output->writeln(
               '<comment>' . $message . '</comment>',
               OutputInterface::VERBOSITY_NORMAL
            );

            $otheritems_iterator = $this->db->request(
               [
                  'FROM'  => 'glpi_plugin_racks_others',
                  'WHERE' => [
                     'plugin_racks_othermodels_id' => $othermodel['id'],
                  ],
               ]
            );

            if ($otheritems_iterator->count()) {
               $progress_bar = new ProgressBar($this->output, $otheritems_iterator->count());
               $progress_bar->start();

               $fk_new_model = getForeignKeyFieldForItemType($new_model_itemtype);

               foreach ($otheritems_iterator as $otheritem) {
                  $progress_bar->advance(1);

                  $new_item_fields = Toolbox::sanitize([
                     'name'        => strlen($otheritem['name'])
                                       ? $otheritem['name']
                                       : $otheritem['id'],
                     'entities_id' => $otheritem['entities_id'],
                     $fk_new_model => $new_model_id
                  ]);

                  $new_item = new $new_itemtype();

                  if (!($new_item_id = $new_item->add($new_item_fields))) {
                     $has_errors = true;

                     $message = sprintf(
                        __('Unable to import other item "%s".'),
                        $new_item_fields['name']
                     );
                     $this->outputImportError($message, $progress_bar);
                     if ($this->input->getOption('skip-errors')) {
                        continue;
                     } else {
                        return false;
                     }
                  }

                  $this->addElementToMapping(
                     'PluginRacksOther',
                     $otheritem['id'],
                     $new_itemtype,
                     $new_item_id
                  );
               }

               $progress_bar->finish();
               $this->output->write(PHP_EOL);
            } else {
               $this->output->writeln(
                  '<comment>' . __('No items found.') . '</comment>',
                  OutputInterface::VERBOSITY_NORMAL
               );
            }
         }
      }

      return !$has_errors;
   }

   /**
    * Import items specifications.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importItemsSpecifications() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing items specifications...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $specs_iterator = $this->db->request(
         [
            'FROM'  => 'glpi_plugin_racks_itemspecifications',
            'ORDER' => 'id ASC'
         ]
      );

      if ($specs_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $specs_iterator->count());
         $progress_bar->start();

         foreach ($specs_iterator as $spec) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing specifications for model %s (%s)...'),
               $spec['itemtype'],
               $spec['model_id']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $model = $this->getCorrespondingItem($spec['itemtype'], $spec['model_id']);

            if (null === $model) {
               $message = sprintf(
                  __('Model %s (%s) not found.'),
                  $spec['itemtype'],
                  $spec['model_id']
               );
               $this->writelnOutputWithProgressBar(
                  $message,
                  $progress_bar,
                  OutputInterface::VERBOSITY_VERBOSE
               );
               continue;
            }

            $model_input = [
               'id'                => $model->fields['id'],
               'required_units'    => $spec['size'],
               'depth'             => ($spec['length'] == 1 ? 1 : 0.5),
               'weight'            => $spec['weight'],
               'is_half_rack'      => 0,
               'power_connections' => $spec['nb_alim'],
            ];

            if (!$model->update($model_input)) {
               $has_errors = true;

               $message = sprintf(
                  __('Unable to update model %s (%s).'),
                  $spec['itemtype'],
                  $spec['model_id']
               );
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No items specifications found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import rack models.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRackModels() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing rack models...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $models_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_rackmodels',
         ]
      );

      if ($models_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $models_iterator->count());
         $progress_bar->start();

         foreach ($models_iterator as $old_model) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing rack model "%s"...'),
               $old_model['name']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $rackmodel = new RackModel();
            $rackmodel_fields = Toolbox::sanitize(
               [
                  'name'    => $old_model['name'],
                  'comment' => $old_model['comment'],
               ]
            );

            if (!($rackmodel_id = $rackmodel->getFromDBByCrit($rackmodel_fields))
                && !($rackmodel_id = $rackmodel->add($rackmodel_fields))) {
               $has_errors = true;

               $message = sprintf(__('Unable to import rack model "%s".'), $old_model['name']);
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }

            $this->addElementToMapping(
               'PluginRacksRackModel',
               $old_model['id'],
               RackModel::class,
               $rackmodel_id
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No rack models found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import rack types.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRackTypes() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing rack types...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $types_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_racktypes',
         ]
      );

      if ($types_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $types_iterator->count());
         $progress_bar->start();

         foreach ($types_iterator as $old_type) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing rack type "%s"...'),
               $old_type['name']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $racktype = new RackType();
            $racktype_fields = Toolbox::sanitize(
               [
                  'name'         => $old_type['name'],
                  'entities_id'  => $old_type['entities_id'],
                  'is_recursive' => $old_type['is_recursive'],
                  'comment'      => $old_type['comment'],
               ]
            );

            if (!($racktype_id = $racktype->getFromDBByCrit($racktype_fields))
                && !($racktype_id = $racktype->add($racktype_fields))) {
               $has_errors = true;

               $message = sprintf(__('Unable to import rack type "%s".'), $old_type['name']);
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }

            $this->addElementToMapping(
               'PluginRacksRackType',
               $old_type['id'],
               RackType::class,
               $racktype_id
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No rack models found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import rack states.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRackStates() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing rack states...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $states_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_rackstates',
         ]
      );

      if ($states_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $states_iterator->count());
         $progress_bar->start();

         foreach ($states_iterator as $old_state) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing rack state "%s"...'),
               $old_state['name']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $state = new State();
            $state_fields = Toolbox::sanitize(
               [
                  'name'      => $old_state['name'],
                  'states_id' => 0,
               ]
            );

            if (!($state_id = $state->getFromDBByCrit($state_fields))) {
               $state_fields['comment']      = $old_state['comment'];
               $state_fields['entities_id']  = $old_state['entities_id'];
               $state_fields['is_recursive'] = $old_state['is_recursive'];

               if (!($state_id = $state->add($state_fields))) {
                  $has_errors = true;

                  $message = sprintf(__('Unable to import rack state "%s".'), $old_state['name']);
                  $this->outputImportError($message, $progress_bar);
                  if ($this->input->getOption('skip-errors')) {
                     continue;
                  } else {
                     return false;
                  }
               }
            }

            $this->addElementToMapping(
               'PluginRacksRackState',
               $old_state['id'],
               State::class,
               $state_id
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No rack states found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import rooms.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRooms() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing rooms...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $rooms_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_roomlocations',
         ]
      );

      if ($rooms_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $rooms_iterator->count());
         $progress_bar->start();

         foreach ($rooms_iterator as $old_room) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing room "%s"...'),
               $old_room['completename']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $room = new DCRoom();
            $room_fields = Toolbox::sanitize(
               [
                  'name'           => $old_room['completename'],
                  'entities_id'    => $old_room['entities_id'],
                  'is_recursive'   => 1,
                  'datacenters_id' => $this->datacenter_id,
                  'vis_cols'       => 10,
                  'vis_rows'       => 10,
               ]
            );

            if (!($room_id = $room->getFromDBByCrit($room_fields))
                && !($room_id = $room->add($room_fields))) {
               $has_errors = true;

               $message = sprintf(__('Unable to import room "%s".'), $old_room['completename']);
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }

            $this->addElementToMapping(
               'PluginRacksRoomLocation',
               $old_room['id'],
               DCRoom::class,
               $room_id
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No rooms found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import racks.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRacks() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing racks...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $racks_iterator = $this->db->request(
         [
            'FROM' => 'glpi_plugin_racks_racks',
         ]
      );

      if ($racks_iterator->count()) {
         $i = 0;

         $progress_bar = new ProgressBar($this->output, $racks_iterator->count());
         $progress_bar->start();

         foreach ($racks_iterator as $old_rack) {
            $progress_bar->advance(1);

            $message = sprintf(
               __('Importing rack "%s"...'),
               $old_rack['name']
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $rackmodel = $this->getCorrespondingItem(
               'PluginRacksRackModel',
               $old_rack['plugin_racks_rackmodels_id']
            );
            $racktype  = $this->getCorrespondingItem(
               'PluginRacksRackType',
               $old_rack['plugin_racks_racktypes_id']
            );
            $rackstate = $this->getCorrespondingItem(
               'PluginRacksRackState',
               $old_rack['plugin_racks_rackstates_id']
            );
            $room      = $this->getCorrespondingItem(
               'PluginRacksRackState',
               $old_rack['plugin_racks_rackstates_id']
            );
            if (null !== $room) {
               $room_id = $room->fields['id'];
            } else {
               $room_id = $this->getFallbackRoomId();
               if (0 == $room_id && !$this->input->getOption('skip-errors')) {
                  return false;
               }
            }

            $rack = new Rack();
            $rack_fields = Toolbox::sanitize(
               [
                  'name'             => $old_rack['name'],
                  'comment'          => "Imported from rack plugin",
                  'entities_id'      => $old_rack['entities_id'],
                  'is_recursive'     => $old_rack['is_recursive'],
                  'locations_id'     => $old_rack['locations_id'],
                  'serial'           => $old_rack['serial'],
                  'rackmodels_id'    => null !== $rackmodel ? $rackmodel->fields['id'] : 0,
                  'manufacturers_id' => $old_rack['manufacturers_id'],
                  'racktypes_id'     => null !== $racktype ? $racktype->fields['id'] : 0,
                  'states_id'        => null !== $rackstate ? $rackstate->fields['id'] : 0,
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
                  'dcrooms_id'       => $room_id,
                  'bgcolor'          => "#FEC95C",
               ]
            );

            if (!($rack_id = $rack->getFromDBByCrit($rack_fields))) {
               $rack_fields['position'] = "9999999999999,-" . (++$i);

               if (!($rack_id = $rack->add($rack_fields))) {
                  $has_errors = true;

                  $message = sprintf(__('Unable to import rack "%s".'), $old_rack['name']);
                  $this->outputImportError($message, $progress_bar);
                  if ($this->input->getOption('skip-errors')) {
                     continue;
                  } else {
                     return false;
                  }
               }
            }

            $this->addElementToMapping(
               'PluginRacksRack',
               $old_rack['id'],
               Rack::class,
               $rack_id
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No racks found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Import rack items.
    *
    * @return boolean True in case of success, false in case of errors.
    */
   private function importRackItems() {

      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing rack items...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $items_iterator = $this->db->request(
         [
            'FROM'  => 'glpi_plugin_racks_racks_items',
            'ORDER' => 'id'
         ]
      );

      if ($items_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $items_iterator->count());
         $progress_bar->start();

         foreach ($items_iterator as $old_item) {
            $progress_bar->advance(1);

            $itemtype = str_replace('Model', '', $old_item['itemtype']); // Plugin was storing model type as itemtype
            $items_id = $old_item['items_id'];

            $message = sprintf(
               __('Importing rack item %s (%s)...'),
               $itemtype,
               $items_id
            );
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $item = $this->getCorrespondingItem($itemtype, $items_id);
            if (null === $item) {
               $message = sprintf(__('Item %s (%s) not found.'), $itemtype, $items_id);
               $this->writelnOutputWithProgressBar(
                  $message,
                  $progress_bar,
                  OutputInterface::VERBOSITY_VERBOSE
               );
               continue;
            }

            $item_input = [
               'itemtype' => $item->getType(),
               'items_id' => $item->fields['id'],
            ];

            $item_rack = new Item_Rack();
            if ($item_rack->getFromDBByCrit($item_input)) {
               $message = sprintf(
                  __('Skipping item %s (%s) which is already linked to a rack.'),
                  $itemtype,
                  $items_id
               );
               $this->writelnOutputWithProgressBar(
                  $message,
                  $progress_bar,
                  OutputInterface::VERBOSITY_VERBOSE
               );
               continue;
            }

            $required_units = 1;
            $modeltype = $item->getType() . 'Model';
            if (class_exists($modeltype)) {
               $model_fkey = getForeignKeyFieldForTable($modeltype::getTable());
               if (array_key_exists($model_fkey, $item->fields)
                  && null !== ($model = $this->getCorrespondingItem($modeltype, $item->fields[$model_fkey]))) {
                  $required_units = $model->fields['required_units'];
               }
            }

            $position = $old_item['position'] - $required_units + 1;

            $rack = $this->getCorrespondingItem(
               'PluginRacksRack',
               $old_item['plugin_racks_racks_id']
            );

            $item_input = $item_input + [
               'racks_id'    => null !== $rack ? $rack->fields['id'] : 0,
               'position'    => $position,
               'hpos'        => 0,
               'bgcolor'     => '#69CEBA',
               'orientation' => ($old_item['faces_id'] == 1 ? Rack::FRONT : Rack::REAR),
            ];

            if (!$item_rack->add($item_input)) {
               $has_errors = true;

               $message = sprintf(
                  __('Unable to import rack item %s (%s).'),
                  $itemtype,
                  $items_id
               );
               $this->outputImportError($message, $progress_bar);
               if ($this->input->getOption('skip-errors')) {
                  continue;
               } else {
                  return false;
               }
            }
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No rack items found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Add an element to mapping.
    *
    * @param string  $old_itemtype
    * @param integer $old_id
    * @param string  $new_itemtype
    * @param integer $new_id
    *
    * @return void
    */
   private function addElementToMapping($old_itemtype, $old_id, $new_itemtype, $new_id) {

      if (!array_key_exists($old_itemtype, $this->elements_mapping)) {
         $this->elements_mapping[$old_itemtype] = [];
      }
      $this->elements_mapping[$old_itemtype][$old_id] = [
         'itemtype' => $new_itemtype,
         'id'       => $new_id,
      ];
   }

   /**
    * Returns item corresponding to itemtype and id.
    * If item has been migrated to another itemtype, il will return the new item.
    *
    * @param string  $itemtype
    * @param integer $id
    *
    * @return null|CommonDBTM
    */
   private function getCorrespondingItem($itemtype, $id) {

      if (array_key_exists($itemtype, $this->elements_mapping)
          && array_key_exists($id, $this->elements_mapping[$itemtype])) {
         // Element exists in mapping, get new element
         $mapping  = $this->elements_mapping[$itemtype][$id];
         $id       = $mapping['id'];
         $itemtype = $mapping['itemtype'];
      }

      if (!class_exists($itemtype)) {
         return null;
      }

      $item = new $itemtype();
      if (!$item->getFromDB($id)) {
         return null;
      }

      return $item;
   }

   /**
    * Returns fallback room id.
    *
    * @return number
    */
   private function getFallbackRoomId() {

      if (null === $this->fallback_room_id) {
         $room = new DCRoom();
         $room_fields = [
            'name'           => 'Temp room (from plugin racks migration script)',
            'entities_id'    => 0,
            'is_recursive'   => 1,
            'datacenters_id' => $this->datacenter_id,
            'vis_cols'       => 10,
            'vis_rows'       => 10,
         ];

         if (!($room_id = $room->getFromDBByCrit($room_fields))
             && !($room_id = $room->add($room_fields))) {

            $this->outputImportError( __('Unable to create default room.'));

            $room_id = 0;
         }

         $this->fallback_room_id = $room_id;
      }

      return $this->fallback_room_id;
   }

   /**
    * Returns verbosity level for import errors.
    *
    * @return number
    */
   private function getImportErrorsVerbosity() {

      return $this->input->getOption('skip-errors')
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;
   }

   /**
    * Output import error message.
    *
    * @param string           $message
    * @param ProgressBar|null $progress_bar
    *
    * @return void
    */
   private function outputImportError($message, ProgressBar $progress_bar = null) {

      $skip_errors = $this->input->getOption('skip-errors');

      $verbosity = $skip_errors
         ? OutputInterface::VERBOSITY_NORMAL
         : OutputInterface::VERBOSITY_QUIET;

      $message = '<error>' . $message . '</error>';

      if ($skip_errors && $progress_bar instanceof ProgressBar) {
         $this->writelnOutputWithProgressBar(
            $message,
            $progress_bar,
            $verbosity
         );
      } else {
         if (!$skip_errors && $progress_bar instanceof ProgressBar) {
            $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
         }
         $this->output->writeln(
            $message,
            $verbosity
         );
      }
   }
}
