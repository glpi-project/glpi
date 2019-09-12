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

class DeactivateCommand extends AbstractPluginCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:plugin:deactivate');
      $this->setAliases(['plugin:deactivate']);
      $this->setDescription('Deactivate plugin(s)');
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $this->normalizeInput($input);

      $directories   = $input->getArgument('directory');

      foreach ($directories as $directory) {
         $output->writeln(
            '<info>' . sprintf(__('Processing plugin "%s"...'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );

         if (!$this->canRunDeactivateMethod($directory)) {
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

         if (!$plugin->unactivate($plugin->fields['id'])) {
            $this->output->writeln(
               '<error>' . sprintf(__('Plugin "%s" deactivation failed.'), $directory) . '</error>',
               OutputInterface::VERBOSITY_QUIET
            );
            $this->outputSessionBufferedMessages([WARNING, ERROR]);
            continue;
         }

         $output->writeln(
            '<info>' . sprintf(__('Plugin "%1$s" has been deactivated.'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );
      }

      return 0; // Success
   }

   /**
    * Check if deactivate method can be run for given plugin.
    *
    * @param string  $directory
    *
    * @return boolean
    */
   private function canRunDeactivateMethod($directory) {

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

      if (Plugin::ACTIVATED != $plugin->fields['state']) {
         $this->output->writeln(
            '<info>' . sprintf(__('Plugin "%s" is already inactive.'), $directory) . '</info>',
            OutputInterface::VERBOSITY_NORMAL
         );
         return false;
      }

      return true;
   }

   protected function getDirectoryChoiceQuestion() {

      return __('Which plugin(s) do you want to deactivate (comma separated values) ?');
   }

   protected function getDirectoryChoiceChoices() {

      $choices = [];
      $plugin_iterator = $this->db->request(
         [
            'FROM'  => Plugin::getTable(),
            'WHERE' => [
               'state' => Plugin::ACTIVATED
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
