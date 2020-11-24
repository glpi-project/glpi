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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\TemplateRenderer;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTwigTemplatesSyntaxCommand extends AbstractCommand {

   /**
    * Error code returned when some templates have invalid yntax.
    *
    * @var integer
    */
   const ERROR_INVALID_TEMPLATES = 1;

   protected $requires_db = false;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:tools:check_twig_templates_syntax');
      $this->setAliases(['tools:check_twig_templates_syntax']);
      $this->setDescription(__('Check Twig templates syntax.'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $environment = TemplateRenderer::getInstance()->getEnvironment();

      $error_messages = [];

      $tpl_dir = realpath(GLPI_ROOT . '/templates');
      $tpl_files_iterator = new RegexIterator(
         new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(GLPI_ROOT . '/templates', FilesystemIterator::UNIX_PATHS)
         ),
         '/\.twig$/i'
      );
      /* @var SplFileInfo $tpl_file */
      foreach ($tpl_files_iterator as $tpl_file) {
         $tpl_path = str_replace($tpl_dir . '/', '', $tpl_file->getPathname());
         $source = $environment->getLoader()->getSourceContext($tpl_path);
         try {
            $token_stream = $environment->tokenize($source);
            $environment->parse($token_stream);
         } catch (\Twig\Error\Error $e) {
            $error_messages[] = sprintf(
               '"%s" in template "%s" at line %s',
               $e->getRawMessage(),
               $source->getPath(),
               $e->getTemplateLine()
            );
         }
      }

      if (count($error_messages) > 0) {
         $output->writeln('<comment>Error found while parsing Twig templates:</comment>', OutputInterface::VERBOSITY_QUIET);
         foreach ($error_messages as $msg) {
            $output->writeln('<error>‣ ' . $msg . '</error>', OutputInterface::VERBOSITY_QUIET);
         }
         return self::ERROR_INVALID_TEMPLATES;
      }

      $output->writeln('<info>No error found while parsing Twig templates.</info>', OutputInterface::VERBOSITY_QUIET);
      return 0; // Success
   }
}
