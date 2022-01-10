<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
use DB;
use DomainType;
use Domain;
use Domain_Item;
use Plugin;
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

class DomainsPluginToCoreCommand extends AbstractCommand {

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
    * Version of Domains plugin required for this migration.
    * @var string
    */
   const DOMAINS_REQUIRED_VERSION = '2.1.0';

   /**
    * Imported elements mapping.
    *
    * @var array
    */
   private $elements_mapping;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:domains_plugin_to_core');
      $this->setDescription(__('Migrate Domains plugin data into GLPI core tables'));

      $this->addOption(
         'update-plugin',
         'u',
         InputOption::VALUE_NONE,
         sprintf(
            __('Run Domains plugin update (you need version %s files to do this)'),
            self::DOMAINS_REQUIRED_VERSION
         )
      );

      $this->addOption(
         'without-plugin',
         'w',
         InputOption::VALUE_NONE,
         sprintf(
            __('Enable migration without plugin files (we cannot validate that plugin data are compatible with supported %s version)'),
            self::DOMAINS_REQUIRED_VERSION
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
               __('You are about to launch migration of Domains plugin data into GLPI core tables.'),
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
         $plugin->checkPluginState('domains');

         if (!$plugin->getFromDBbyDir('domains')) {
            $message  = __('Domains plugin is not part of GLPI plugin list. It has never been installed or has been cleaned.')
               . ' '
               . sprintf(
                  __('You have to install Domains plugin files in version %s to be able to continue.'),
                  self::DOMAINS_REQUIRED_VERSION
               );
            $this->output->writeln(
               [
                  '<error>' . $message . '</error>',
               ],
               OutputInterface::VERBOSITY_QUIET
            );
            return false;
         }

         $is_version_ok = self::DOMAINS_REQUIRED_VERSION === $plugin->fields['version'];
         if (!$is_version_ok) {
            $message  = sprintf(
               __('You have to install Domains plugin files in version %s to be able to continue.'),
               self::DOMAINS_REQUIRED_VERSION
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
                  self::DOMAINS_REQUIRED_VERSION
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
                     self::DOMAINS_REQUIRED_VERSION
                  );
                  $this->output->writeln(
                     '<error>' . $message . '</error>',
                     OutputInterface::VERBOSITY_QUIET
                  );
                  return false;
               }
            } else {
               $message = sprintf(
                  __('Domains plugin data has to be updated to %s version. It can be done using the --update-plugin option.'),
                  self::DOMAINS_REQUIRED_VERSION
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

      $domains_tables = [
         'glpi_plugin_domains_configs',
         'glpi_plugin_domains_domains',
         'glpi_plugin_domains_domains_items',
         'glpi_plugin_domains_domaintypes',
      ];
      $missing_tables = false;
      foreach ($domains_tables as $table) {
         if (!$this->db->tableExists($table)) {
            $this->output->writeln(
               '<error>' . sprintf(__('Domains plugin table "%s" is missing.'), $table) . '</error>',
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


   private  function migratePlugin() {

      $failure = !$this->importDomainTypes()
         || !$this->importDomains()
         || !$this->importDomainItems();

      return !$failure;
   }

   /**
    * Migrate domain types
    *
    * @return boolean
    */
   protected function importDomainTypes() {
      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing domains types...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $types_iterator = $this->db->request([
         'FROM'   => 'glpi_plugin_domains_domaintypes',
         'ORDER'  => 'id ASC'
      ]);

      $core_types = [];
      $coret_iterator = $this->db->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => DomainType::getTable()
      ]);
      while ($row = $coret_iterator->next()) {
         $core_types[$row['name']] = $row['id'];
      }

      if ($types_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $types_iterator->count());
         $progress_bar->start();

         foreach ($types_iterator as $typ) {
            $progress_bar->advance(1);
            $core_type = null;

            if (isset($core_types[$typ['name']])) {
               $core_type = $core_types[$typ['name']];
               $message = sprintf(
                  __('Updating existing domain type %s...'),
                  $typ['name']
               );
            } else {
               $message = sprintf(
                  __('Importing domain type %s...'),
                  $typ['name']
               );
            }
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $type_input = [
               'name'         => $typ['name'],
               'entities_id'  => $typ['entities_id'],
               'comment'      => $typ['comment'],
            ];
            $type_input = Toolbox::addslashes_deep($type_input);

            $domaintype = new DomainType();
            if ($core_type !== null) {
               $res = (bool)$domaintype->update($type_input + ['id' => $core_type]);
            } else {
               $new_tid = (int)$domaintype->add($type_input);
               $res = $new_tid > 0;
               if ($res) {
                  $core_types[$typ['name']] = $new_tid;
               }
            }

            if (!$res) {
               $has_errors = true;

               $message = sprintf(
                  $core_type === null ?
                     __('Unable to add domain type %s.') :
                     __('Unable to update domain type %s.'),
                  $typ['name']
               );
               $this->outputImportError($message, $progress_bar);
               return false;
            }

            $this->addElementToMapping(
               'PluginDomainsDomaintype',
               $typ['id'],
               'DomainType',
               $new_tid ?? $core_type
            );
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No domains types found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Migrate domains
    *
    * @throws LogicException
    *
    * @return boolean
    */
   protected function importDomains() {
      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing domains...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $domains_iterator = $this->db->request([
         'FROM'   => 'glpi_plugin_domains_domains',
         'ORDER'  => 'id ASC'
      ]);

      $core_domains = [];
      $cored_iterator = $this->db->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => Domain::getTable()
      ]);
      while ($row = $cored_iterator->next()) {
         $core_domains[$row['name']] = $row['id'];
      }

      if ($domains_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $domains_iterator->count());
         $progress_bar->start();

         foreach ($domains_iterator as $dom) {
            $progress_bar->advance(1);
            $core_dom = null;

            if (isset($core_domains[$dom['name']])) {
               $core_dom = $core_domains[$dom['name']];
               $message = sprintf(
                  __('Updating existing domain %s...'),
                  $dom['name']
               );
            } else {
               $message = sprintf(
                  __('Importing domain %s...'),
                  $dom['name']
               );
            }
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            $mapped_type = $this->getCorrespondingItem('PluginDomainsDomaintype', $dom['plugin_domains_domaintypes_id']);
            if ($dom['plugin_domains_domaintypes_id'] != 0 && $mapped_type === null) {
               $message = sprintf(
                  __('Unable to find mapping for type %s.'),
                  $dom['plugin_domains_domaintypes_id']
               );
               $this->outputImportError($message, $progress_bar);
               return false;
            }
            $types_id = $mapped_type !== null ? $mapped_type->fields['id'] : 0;
            $domain_input = [
               'name'                  => $dom['name'],
               'entities_id'           => $dom['entities_id'],
               'is_recursive'          => $dom['is_recursive'],
               'domaintypes_id'        => $types_id,
               'date_creation'         => $dom['date_creation'],
               'date_expiration'       => $dom['date_expiration'],
               'users_id_tech'         => $dom['users_id_tech'],
               'groups_id_tech'        => $dom['groups_id_tech'],
               //suppliers_id not present in core
               'comment'               => $dom['comment'],
               'others'                => $dom['others'],
               'is_helpdesk_visible'   => $dom['is_helpdesk_visible'],
               'date_mod'              => $dom['date_mod'],
               'is_deleted'            => $dom['is_deleted']
            ];
            $domain_input = Toolbox::addslashes_deep($domain_input);

            $domain = new Domain();
            if ($core_dom !== null) {
               $res = (bool)$domain->update($domain_input + ['id' => $core_dom]);
            } else {
               $new_did = (int)$domain->add($domain_input);
               $res = $new_did > 0;
               if ($res) {
                  $core_domains[$dom['name']] = $new_did;
               }
            }

            if (!$res) {
               $has_errors = true;

               $message = sprintf(
                  $core_dom === null ?
                     __('Unable to add domain %s.') :
                     __('Unable to update domain %s.'),
                  $dom['name']
               );
               $this->outputImportError($message, $progress_bar);
               return false;
            }

            $this->addElementToMapping(
               'PluginDomainsDomains',
               $dom['id'],
               'Domain',
               $new_did ?? $core_dom
            );

            //handle infocoms
            $infocom = new \Infocom();
            $infocom_input = [
               'itemtype'     => 'Domain',
               'items_id'     => $new_did ?? $core_dom,
               'suppliers_id' => $dom['suppliers_id'],
               'entities_id'  => $dom['entities_id'],
               'is_recursive' => $dom['is_recursive']
            ];
            if ($core_dom === null) {
               $infocom->add($infocom_input);
            } else {
               $found = $infocom->getFromDBByCrit([
                  'itemtype'  => 'Domain',
                  'items_id'  => $core_dom
               ]);
               if ($found) {
                  $infocom_input['id'] = $infocom->fields['id'];
                  $infocom->update($infocom_input);
               } else {
                  $infocom->add($infocom_input);
               }
            }
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No domains found.') . '</comment>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return !$has_errors;
   }

   /**
    * Migrate domain items
    *
    * @return boolean
    */
   protected function importDomainItems() {
      $has_errors = false;

      $this->output->writeln(
         '<comment>' . __('Importing domains items...') . '</comment>',
         OutputInterface::VERBOSITY_NORMAL
      );

      $items_iterator = $this->db->request([
         'FROM'   => 'glpi_plugin_domains_domains_items',
         'ORDER'  => 'id ASC'
      ]);

      $core_items = [];
      $coreitems_iterator = $this->db->request([
         'FROM'   => Domain_Item::getTable()
      ]);
      while ($row = $coreitems_iterator->next()) {
         $core_items[$row['domains_id'].$row['itemtype'].$row['items_id']] = $row['id'];
      }

      if ($items_iterator->count()) {
         $progress_bar = new ProgressBar($this->output, $items_iterator->count());
         $progress_bar->start();

         foreach ($items_iterator as $itm) {
            $progress_bar->advance(1);
            $core_item = null;
            $mapped_domain = $this->getCorrespondingItem('PluginDomainsDomains', $itm['plugin_domains_domains_id']);
            if ($mapped_domain === null) {
               $message = sprintf(
                  __('Unable to find corresponding domain for item %s (%s).'),
                  $itm['itemtype'],
                  $itm['items_id']
               );
               $this->outputImportError($message, $progress_bar);
               // Do not block migration as this error is probably resulting in presence of obsolete data in DB
               continue;
            }
            $domains_id = $mapped_domain->fields['id'];

            if (isset($core_items[$domains_id.$itm['itemtype'].$itm['items_id']])) {
               $core_item = $core_items[$domains_id.$itm['itemtype'].$itm['items_id']];
               $message = sprintf(
                  __('Skip existing domain item %s...'),
                  $domains_id . ' ' . $itm['itemtype'] . ' ' . $itm['items_id']
               );
            } else {
               $message = sprintf(
                  __('Importing domain item %s...'),
                  $domains_id . ' ' . $itm['itemtype'] . ' ' . $itm['items_id']
               );
            }
            $this->writelnOutputWithProgressBar(
               $message,
               $progress_bar,
               OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            if ($core_item !== null) {
               //if it already exist in DB, there is nothing to change
               continue;
            }

            $item_input = [
               'domains_id'            => $domains_id,
               'itemtype'              => $itm['itemtype'],
               'items_id'              => $itm['items_id'],
               'domainrelations_id'    => 0
            ];
            $item_input = Toolbox::addslashes_deep($item_input);

            $item = new Domain_Item();
            $new_iid = (int)$item->add($item_input);
            $res = $new_iid > 0;
            if ($res) {
               $core_items[$domains_id.$itm['itemtype'].$itm['items_id']] = $new_iid;
            }

            if (!$res) {
               $has_errors = true;

               $message = sprintf(
                  $core_item === null ?
                     __('Unable to add domain item %s.') :
                     __('Unable to update domain item %s.'),
                  $domains_id . ' ' . $itm['itemtype'] . ' ' . $itm['items_id']
               );
               $this->outputImportError($message, $progress_bar);
               return false;
            }
         }

         $progress_bar->finish();
         $this->output->write(PHP_EOL);
      } else {
         $this->output->writeln(
            '<comment>' . __('No domains items found.') . '</comment>',
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
      if ($id !== 0 && !$item->getFromDB($id)) {
         return null;
      }

      return $item;
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

      $verbosity = OutputInterface::VERBOSITY_QUIET;

      $message = '<error>' . $message . '</error>';

      if ($progress_bar instanceof ProgressBar) {
         $this->writelnOutputWithProgressBar(
            $message,
            $progress_bar,
            $verbosity
         );
      } else {
         if ($progress_bar instanceof ProgressBar) {
            $this->output->write(PHP_EOL); // Keep progress bar last state and go to next line
         }
         $this->output->writeln(
            $message,
            $verbosity
         );
      }
   }
}
