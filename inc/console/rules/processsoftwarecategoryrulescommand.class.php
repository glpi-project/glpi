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

namespace Glpi\Console\Rules;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessSoftwareCategoryRulesCommand extends AbstractCommand {

   protected function configure() {
      parent::configure();

      $this->setName('glpi:rules:process_software_category_rules');
      $this->setAliases(['rules:process_software_category_rules']);
      $this->setDescription(__('Process software category rules'));

      $this->addOption(
         'all',
         'a',
         InputOption::VALUE_NONE,
         __('Process rule for all softwares, even those having already a defined category')
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $query = [
         'SELECT' => [
            'id',
         ],
         'FROM'   => \Software::getTable(),
      ];
      if (!$input->getOption('all')) {
         $query['WHERE'] = [
            'softwarecategories_id' => 0
         ];
      }

      $software_iterator = $this->db->request($query);

      $sofware_count = $software_iterator->count();
      if ($sofware_count === 0) {
         $output->writeln('<info>' . __('No software to process.') . '</info>');
         return 0; // Success
      }

      $progress_bar = new ProgressBar($output, $sofware_count);
      $progress_bar->start();

      $processed_count = 0;
      foreach ($software_iterator as $data) {
         $progress_bar->advance(1);

         $this->writelnOutputWithProgressBar(
            sprintf(__('Processing software having id "%s".'), $data['id']),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $software = new \Software();

         if (!$software->getFromDB($data['id'])) {
            $this->writelnOutputWithProgressBar(
               sprintf(__('Unable to load software having id "%s".'), $data['id']),
               $progress_bar,
               OutputInterface::VERBOSITY_NORMAL
            );
            continue;
         }

         $rule_collection = new \RuleSoftwareCategoryCollection();
         $input = $rule_collection->processAllRules(
            [],
            $software->fields,
            [
               'name'             => $software->fields['name'],
               'manufacturers_id' => $software->fields['manufacturers_id'],
            ]
         );

         $software->update($input);

         $processed_count++;
      }

      $progress_bar->finish();
      $this->output->write(PHP_EOL);

      $output->writeln(
         '<info>' .sprintf(__('Number of softwares processed: %d.'), $processed_count) . '</info>'
      );

      return 0; // Success
   }
}
