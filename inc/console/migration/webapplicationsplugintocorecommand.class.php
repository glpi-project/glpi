<?php

namespace Glpi\Console\Migration;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Appliance;
use ApplianceType;
use ApplianceEnvironment;
use Appliance_Item;
use Appliance_Item_Relation;
use Change_Item;
use Contract_Item;
use DB;
use Document_Item;
use Domain;
use Infocom;
use Location;
use Network;
use Toolbox;
use Glpi\Console\AbstractCommand;
use Item_Problem;
use Item_Project;
use Item_Ticket;
use KnowbaseItem_Item;
use Log;
use PluginWebapplicationsAppliance;
use Profile;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Migrates data from "webapplications" plugin to core appliances.
 *
 * References:
 * * GLPI's glpi:migration:appliances_plugin_to_core command:
 *   https://github.com/glpi-project/glpi/blob/708c896ed30a518a1b4fae7382cf4550ce987666/inc/console/migration/appliancesplugintocorecommand.class.php
 * * webapplications plugin's migration code from v3.0.0:
 *   https://github.com/InfotelGLPI/webapplications/blob/28f5b137d67ae603125a51014c21231d2d5ee218/front/webapplication.php
 */
class WebapplicationsPluginToCoreCommand extends AbstractCommand {

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
   const ERROR_PLUGIN_IMPORT_FAILED = 2;

   /**
    * list of useful plugin tables and fields
    *
    * @var array
    */
   const PLUGIN_WEBAPPLICATIONS_TABLES = [
      'glpi_plugin_webapplications_appliances' => [
         'id',
         'appliances_id',
         'webapplicationtypes_id',
         'webapplicationservertypes_id',
         'webapplicationtechnics_id',
         'address',
         'backoffice',
      ],
      'glpi_plugin_webapplications_webapplications' => [
         'id',
         'entities_id',
         'is_recursive',
         'name',
         'address',
         'backoffice',
         'plugin_webapplications_webapplicationtypes_id',
         'plugin_webapplications_webapplicationservertypes_id',
         'plugin_webapplications_webapplicationtechnics_id',
         'version',
         'users_id_tech',
         'groups_id_tech',
         'suppliers_id',
         'manufacturers_id',
         'locations_id',
         'date_mod',
         'is_helpdesk_visible',
         'comment',
         'is_deleted',
      ],
      'glpi_plugin_webapplications_webapplications_items' => [
         'id',
         'plugin_webapplications_webapplications_id',
         'items_id',
         'itemtype',
      ],
      'glpi_plugin_webapplications_webapplicationservertypes' => [
         'id',
         'name',
         'comment',
      ],
      'glpi_plugin_webapplications_webapplicationtechnics' => [
         'id',
         'name',
         'comment',
      ],
      'glpi_plugin_webapplications_webapplicationtypes' => [
         'id',
         'entities_id',
         'name',
         'comment',
         'is_recursive',
      ],
   ];

   /**
    * itemtype corresponding to appliance in plugin
    *
    * @var string
    */
   const PLUGIN_WEBAPPLICATION_ITEMTYPE = "PluginWebapplicationsWebapplication";

   /**
    * itemtype corresponding to appliance in core
    *
    * @var string
    */
   const CORE_APPLIANCE_ITEMTYPE = "Appliance";

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:webapplications_plugin_to_core');
      $this->setDescription(__('Migrate Webapplications plugin data into GLPI core tables'));

      $this->addOption(
         'skip-errors',
         's',
         InputOption::VALUE_NONE,
         __('Do not exit on import errors')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      $no_interaction = $input->getOption('no-interaction');
      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)
         $output->writeln([
               __('You are about to launch migration of Webapplications plugin data into GLPI core tables.'),
               __('Any previous appliance created in core will be lost.'),
               __('It is better to make a backup of your existing data before continuing.')
            ]
         );

         /**
          * @var QuestionHelper $question_helper
          */
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

      if (!$this->migratePlugin()) {
         return self::ERROR_PLUGIN_IMPORT_FAILED;
      }

      $output->writeln('<info>' . __('Migration done.') . '</info>');
      return 0; // Success
   }

   /**
    * Check that required tables exists and fields are OK for migration.
    *
    * @return bool
    */
   private function checkPlugin(): bool {
      $missing_tables = false;
      foreach (self::PLUGIN_WEBAPPLICATIONS_TABLES as $table => $fields) {
         if (!$this->db->tableExists($table)) {
            $this->output->writeln(
               '<error>' . sprintf(__('Webapplications plugin table "%s" is missing.'), $table) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $missing_tables = true;
         } else {
            foreach ($fields as $field) {
               if (!$this->db->fieldExists($table, $field)) {
                  $this->output->writeln(
                        '<error>' . sprintf(__('Webapplications plugin field "%s" is missing.'), $table.'.'.$field) . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                  );
                  $missing_tables = true;
               }
            }
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
         Appliance::getTable(),
         ApplianceType::getTable(),
         ApplianceEnvironment::getTable(),
         Appliance_Item::getTable(),
         Appliance_Item_Relation::getTable(),
      ];

      foreach ($core_tables as $table) {
         $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

         if (!$result) {
            throw new RuntimeException(
               sprintf('Unable to truncate table "%s"', $table)
            );
         }
      }

      $table  = Infocom::getTable();
      $result = $this->db->delete($table, [
         'itemtype' => self::CORE_APPLIANCE_ITEMTYPE
      ]);
      if (!$result) {
         throw new RuntimeException(
            sprintf('Unable to clean table "%s"', $table)
         );
      }
   }

   /**
   * Clean data from plugin's tables.
   *
   * @throws RuntimeException
   */
   private function cleanPluginAppliancesTables() {
      $plugins_tables = [
         PluginWebapplicationsAppliance::getTable(),
      ];

      foreach ($plugins_tables as $table) {
         $result = $this->db->query('TRUNCATE ' . DB::quoteName($table));

         if (!$result) {
            throw new RuntimeException(
               sprintf('Unable to truncate table "%s"', $table)
            );
         }
      }
   }

   /**
    * Copy plugin tables to backup tables from plugin to core keeping same ID.
    *
    * @return bool
    */
   private  function migratePlugin(): bool {
      global $CFG_GLPI;

      //prevent infocom creation from general setup
      if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]) {
         $CFG_GLPI['auto_create_infocoms'] = false;
      }
      $this->cleanCoreTables();
      $this->cleanPluginAppliancesTables();

      return $this->createApplianceTypes()
         && $this->createApplianceItems()
         && $this->createAppliances()
         && $this->updateItemtypes()
         && $this->updateProfilesApplianceRights();
   }

   /**
    * Update profile rights (Associable items to a ticket).
    *
    * @return bool
    */
   private function updateProfilesApplianceRights(): bool {
      $this->output->writeln(
         '<comment>' . __('Updating profiles...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $table  = Profile::getTable();
      $result = $this->db->query(
         sprintf(
            "UPDATE %s SET helpdesk_item_type = REPLACE(helpdesk_item_type, '%s', '%s')",
            DB::quoteName($table),
            self::PLUGIN_WEBAPPLICATION_ITEMTYPE,
            self::CORE_APPLIANCE_ITEMTYPE
         )
      );
      if (false === $result) {
         $this->outputImportError(
            sprintf(__('Unable to update "%s" in profiles.'), __('Associable items to a ticket'))
         );
         if (!$this->input->getOption('skip-errors')) {
            return false;
         }
      }

      return true;
   }

   /**
    * Rename itemtype in core tables.
    *
    * @return bool
    */
   private function updateItemtypes(): bool {
      $this->output->writeln(
         '<comment>' . __('Updating GLPI itemtypes...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );
      $itemtypes_tables = [
         Item_Ticket::getTable(),
         Item_Problem::getTable(),
         Change_Item::getTable(),
         Item_Project::getTable(),
         Log::getTable(),
         Infocom::getTable(),
         Document_Item::getTable(),
         Contract_Item::getTable(),
         KnowbaseItem_Item::getTable(),
      ];

      foreach ($itemtypes_tables as $itemtype_table) {
         $result = $this->db->update($itemtype_table, [
            'itemtype' => self::CORE_APPLIANCE_ITEMTYPE,
         ], [
            'itemtype' => self::PLUGIN_WEBAPPLICATION_ITEMTYPE,
         ]);

         if (false === $result) {
            $this->outputImportError(
               sprintf(
                  __('Migration of table "%s" failed with message "(%s) %s".'),
                  $itemtype_table,
                  $this->db->errno(),
                  $this->db->error()
               )
            );
            if (!$this->input->getOption('skip-errors')) {
               return false;
            }
         }
      }

      return true;
   }

   /**
    * Create appliance items.
    *
    * @return bool
    */
   private function createApplianceItems(): bool {
      $this->output->writeln(
         '<comment>' . __('Creating Appliance Items...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $iterator = $this->db->request([
         'FROM' => 'glpi_plugin_webapplications_webapplications_items'
      ]);

      if (!count($iterator)) {
         return true;
      }

      $progress_bar = new ProgressBar($this->output);

      foreach ($progress_bar->iterate($iterator) as $item) {
         $this->writelnOutputWithProgressBar(
            sprintf(
               __('Importing Appliance item "%d"...'),
               (int) $item['id']
            ),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $app_fields = Toolbox::sanitize([
            'id'            => $item['id'],
            'appliances_id' => $item['plugin_webapplications_webapplications_id'],
            'items_id'      => $item['items_id'],
            'itemtype'      => $item['itemtype']
         ]);

         $appi = new Appliance_Item();
         if (!($appi_id = $appi->getFromDBByCrit($app_fields))) {
            $appi_id = $appi->add($app_fields);
         }

         if (false === $appi_id) {
            $this->outputImportError(
               sprintf(__('Unable to create Appliance item %d.'), (int) $item['id']),
               $progress_bar
            );
            if (!$this->input->getOption('skip-errors')) {
               return false;
            }
         }
      }

      $this->output->write(PHP_EOL);

      return true;
   }

   /**
    * Create appliances.
    *
    * @return bool
    */
   private function createAppliances(): bool {
      $this->output->writeln(
         '<comment>'. __('Creating Appliances...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );
      $iterator = $this->db->request([
         'FROM' => 'glpi_plugin_webapplications_webapplications'
      ]);

      if (!count($iterator)) {
         return true;
      }

      $progress_bar = new ProgressBar($this->output);

      // Workaround for plugin version 3.0.0
      // Avoids "PHP Notice:  Undefined index" in /plugins/webapplications/inc/appliance.class.php lines 127-129 for:
      // * plugin_webapplications_webapplicationtypes_id 
      // * plugin_webapplications_webapplicationservertypes_id
      // * plugin_webapplications_webapplicationtechnics_id
      if (version_compare(PLUGIN_WEBAPPLICATIONS_VERSION, '3.0.0', 'eq')) {
         global $PLUGIN_HOOKS;
         $old_add_hooks = $PLUGIN_HOOKS['item_add']['webapplications'];
         $old_update_hooks = $PLUGIN_HOOKS['pre_item_update']['webapplications'];
         $PLUGIN_HOOKS['item_add']['webapplications'] = [];
         $PLUGIN_HOOKS['pre_item_update']['webapplications'] = [];
      }
      
      foreach ($progress_bar->iterate($iterator) as $appliance) {
         $this->writelnOutputWithProgressBar(
            sprintf(
               __('Importing appliance "%s"...'),
               $appliance['name']
            ),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $app_fields = Toolbox::sanitize([
            'id'                  => $appliance['id'],
            'entities_id'         => $appliance['entities_id'],
            'is_recursive'        => $appliance['is_recursive'],
            'name'                => $appliance['name'],
            'is_deleted'          => $appliance['is_deleted'],
            'appliancetypes_id'   => $appliance['plugin_webapplications_webapplicationtypes_id'],
            'comment'             => $appliance['comment'],
            'locations_id'        => $appliance['locations_id'],
            'manufacturers_id'    => '0', // Did not exist in webapplications plugin <3.0.0
            'users_id'            => '0', // Did not exist in webapplications plugin <3.0.0
            'users_id_tech'       => $appliance['users_id_tech'],
            'groups_id'           => '0', // Did not exist in webapplications plugin <3.0.0
            'groups_id_tech'      => $appliance['groups_id_tech'],
            'date_mod'            => $appliance['date_mod'],
            'is_helpdesk_visible' => $appliance['is_helpdesk_visible'],
         ]);

         $app = new Appliance();
         if (!($app_id = $app->getFromDBByCrit($app_fields))) {
            $app_id = $app->add($app_fields);
         }

         if (false === $app_id) {
            $this->outputImportError(
               sprintf(__('Unable to create Appliance %s (%d).'), $appliance['name'], (int) $appliance['id']),
               $progress_bar
            );
            if (!$this->input->getOption('skip-errors')) {
               return false;
            }
         } else {
            // Create the appliance extension in the plugin (to hold specific fields)
            $pluginApplianceExtension_fields = Toolbox::sanitize([
               'appliances_id'                => $app_id,
               'webapplicationtypes_id'       => $appliance['plugin_webapplications_webapplicationtypes_id'],
               'webapplicationservertypes_id' => $appliance['plugin_webapplications_webapplicationservertypes_id'],
               'webapplicationtechnics_id'    => $appliance['plugin_webapplications_webapplicationtechnics_id'],
               'address'                      => $appliance['address'],
               'backoffice'                   => $appliance['backoffice'],
            ]);

            $pluginApplianceExtension = new PluginWebapplicationsAppliance();
            if (!($pluginApplianceExtension_id = $pluginApplianceExtension->getFromDBByCrit($pluginApplianceExtension_fields))) {
               $pluginApplianceExtension_id = $pluginApplianceExtension->add($pluginApplianceExtension_fields);
            }

            if (false === $pluginApplianceExtension_id) {
               $this->outputImportError(
                  sprintf(__(
                     'Unable to create PluginWebapplicationsAppliance for Appliance %s (%d).'),
                     $appliance['name'],
                     (int) $app_id
                  ),
                  $progress_bar
               );
               if (!$this->input->getOption('skip-errors')) {
                  return false;
               }
            }
         }
      }

      // Workaround for plugin version 3.0.0
      if (version_compare(PLUGIN_WEBAPPLICATIONS_VERSION, '3.0.0', 'eq')) {
         // Restore plugin hooks
         $PLUGIN_HOOKS['item_add']['webapplications'] = $old_add_hooks;
         $PLUGIN_HOOKS['pre_item_update']['webapplications'] = $old_update_hooks;
      }

      $this->output->write(PHP_EOL);

      return true;
   }

   /**
    * Create appliance types.
    *
    * @return bool
    */
   private function createApplianceTypes(): bool {
      $this->output->writeln(
         '<comment>' . __('Creating Appliance types...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $iterator = $this->db->request([
         'FROM' => 'glpi_plugin_webapplications_webapplicationtypes'
      ]);

      if (!count($iterator)) {
         return true;
      }

      $progress_bar = new ProgressBar($this->output);

      foreach ($progress_bar->iterate($iterator) as $type) {
         $this->writelnOutputWithProgressBar(
            sprintf(
               __('Importing type "%s"...'),
               $type['name']
            ),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $appt_fields = Toolbox::sanitize([
            'id'           => $type['id'],
            'entities_id'  => $type['entities_id'],
            'is_recursive' => $type['is_recursive'],
            'name'         => $type['name'],
            'comment'      => $type['comment'],
         ]);

         $appt = new ApplianceType();
         if (!($appt_id = $appt->getFromDBByCrit($appt_fields))) {
            $appt_id = $appt->add($appt_fields);
         }

         if (false === $appt_id) {
            $this->outputImportError(
               sprintf(__('Unable to create Appliance environment %s (%d).'), $type['name'], (int) $type['id']),
               $progress_bar
            );
            if (!$this->input->getOption('skip-errors')) {
               return false;
            }
         }
      }

      $this->output->write(PHP_EOL);

      return true;
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
