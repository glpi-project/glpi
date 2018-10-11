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

namespace Glpi\Console\Database;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SebastianBergmann\Diff\Differ;

class CheckCommand extends AbstractCommand {

   /**
    * Error code returned when failed to read empty SQL file.
    *
    * @var integer
    */
   const ERROR_UNABLE_TO_READ_EMPTYSQL = 1;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:database:check');
      $this->setAliases(['db:check']);
      $this->setDescription(__('Check for schema differences between current database and installation file.'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $differ = new Differ();

      if (false === ($empty_file = realpath(GLPI_ROOT . '/install/mysql/glpi-empty.sql'))
          || false === ($empty_sql = file_get_contents($empty_file))) {
         $message = sprintf(__('Unable to read installation file "%s".'), $empty_file);
         $output->writeln(
            '<error>' . $message . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_UNABLE_TO_READ_EMPTYSQL;
      }

      $matches = [];
      preg_match_all('/CREATE TABLE `(.+)`[^;]+/', $empty_sql, $matches);
      $empty_tables_names   = $matches[1];
      $empty_tables_schemas = $matches[0];

      foreach ($empty_tables_schemas as $index => $table_schema) {
         $table_name = $empty_tables_names[$index];

         $output->writeln(
            sprintf(__('Processing table "%s"...'), $table_name),
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $base_table_struct     = $this->db->getTableSchema($table_name, $table_schema);
         $existing_table_struct = $this->db->getTableSchema($table_name);

         if ($existing_table_struct['schema'] != $base_table_struct['schema']) {
            $message = sprintf(__('Table schema differs for table "%s".'), $table_name);
            $output->writeln(
               '<info>' . $message . '</info>',
               OutputInterface::VERBOSITY_QUIET
            );
            $output->write(
               $differ->diff($base_table_struct['schema'], $existing_table_struct['schema'])
            );
         }
      }

      return 0; // Success
   }
}
