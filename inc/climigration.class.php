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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extends class Migration to redefine display mode
 **/
class CliMigration extends Migration {

   /**
    * @var OutputInterface
    */
   private $output;

   function __construct($ver) {

      $this->deb = time();
      $this->setVersion($ver);
   }

   function setVersion($ver) {

      $this->version = $ver;
   }

   function setOutput(OutputInterface $output) {

      $this->output = $output;
   }

   function displayMessage ($msg) {

      $msg .= " (".Html::clean(Html::timestampToString(time()-$this->deb)).")";

      $this->writeToOutput(
         str_pad($msg, 100),
         OutputInterface::VERBOSITY_VERY_VERBOSE
      );
   }

   function displayTitle($title) {

      $this->writeToOutput(
         '<info>' . str_pad(" $title ", 100, '=', STR_PAD_BOTH) . '</info>',
         OutputInterface::VERBOSITY_NORMAL
      );
   }

   function addNewMessageArea($id) {

   }

   function displayWarning($msg, $red = false) {

      if ($red) {
         $msg = "** $msg";
      }
      $this->writeToOutput(
         '<comment>' . str_pad($msg, 100) . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
   }

   private function writeToOutput($line, $verbosity) {
      if ($this->output instanceof OutputInterface) {
         $this->output->writeln($line, $verbosity);
      }
   }
}
