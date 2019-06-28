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

namespace Glpi\Console\Plugin;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Plugin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateCommand extends AbstractPluginCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:plugin:activate');
      $this->setAliases(['plugin:activate']);
      $this->setDescription('Activate plugin(s)');
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $this->normalizeInput($input);

      $directories   = $input->getArgument('directory');

      foreach ($directories as $directory) {
         $output->writeln(
            '<info>' . sprintf(__('Processing plugin "%s"...'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );

         if (!$this->canRunActivateMethod($directory)) {
            continue;
         }

         $plugin = new Plugin();
         $plugin->checkPluginState($directory); // Be sure that plugin informations are up to date in DB
         if (!$plugin->getFromDBByCrit(['directory' => $directory])) {
            $this->output->writeln(
               '<error>' . sprintf(__('Unable to load plugin "%s" informations.'), $directory) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            continue;
         }

         if (!$plugin->activate($plugin->fields['id'])) {
            $this->output->writeln(
               '<error>' . sprintf(__('Plugin "%s" activation failed.'), $directory) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $this->outputSessionBufferedMessages([WARNING, ERROR]);
            continue;
         }

         $output->writeln(
            '<info>' . sprintf(__('Plugin "%1$s" has been activated.'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return 0; // Success
   }

   /**
    * Check if activate method can be run for given plugin.
    *
    * @param string  $directory
    *
    * @return boolean
    */
   private function canRunActivateMethod($directory) {

      $plugin = new Plugin();

      // Check that directory is valid
      $informations = $plugin->getInformationsFromDirectory($directory);
      if (empty($informations)) {
         $this->output->writeln(
            '<error>' . sprintf(__('Invalid plugin directory "%s".'), $directory) . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return false;
      }

      // Check current plugin state
      $is_already_known = $plugin->getFromDBByCrit(['directory' => $directory]);
      if (!$is_already_known) {
         $this->output->writeln(
            '<error>' . sprintf(__('Plugin "%s" is not yet installed.'), $directory) . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return false;
      }

      if ($plugin->fields['state'] == Plugin::ACTIVATED) {
         $this->output->writeln(
            '<info>' . sprintf(__('Plugin "%s" is already active.'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );
         return false;
      }

      if (Plugin::NOTACTIVATED != $plugin->fields['state']) {
         $this->output->writeln(
            '<error>' . sprintf(__('Plugin "%s" have to be installed and configured prior to activation.'), $directory) . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return false;
      }

      return true;
   }

   protected function getDirectoryChoiceQuestion() {

      return __('Which plugin(s) do you want to activate (comma separated values) ?');
   }

   protected function getDirectoryChoiceChoices() {

      $choices = [];
      $plugin_iterator = $this->db->request(
         [
            'FROM'  => Plugin::getTable(),
            'WHERE' => [
               'state' => Plugin::NOTACTIVATED
            ]
         ]
      );
      foreach ($plugin_iterator as $plugin) {
         $choices[$plugin['directory']] = $plugin['name'];
      }

      ksort($choices, SORT_STRING);

      return $choices;
   }
}
