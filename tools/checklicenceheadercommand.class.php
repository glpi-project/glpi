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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLicenceHeaderCommand extends Command {

   /**
    * Result code returned when some headers are missing or are outdated.
    *
    * @var integer
    */
   const ERROR_FOUND_MISSING_OR_OUTDATED = 1;

   /**
    * Result code returned when some files cannot be updated.
    *
    * @var integer
    */
   const ERROR_UNABLE_TO_FIX_FILES = 2;

   /**
    * Header lines.
    *
    * @var array
    */
   private $header_lines;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:tools:licence_header_check');
      $this->setAliases(['tools:header_check']);
      $this->setDescription('Check licence header in code source files.');

      $this->addOption(
         'fix',
         'f',
         InputOption::VALUE_NONE,
         'Fix missing and outdated headers'
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $files = $this->getFilesToParse();

      $output->writeln(
         '<comment>' . sprintf('%s files to process.', count($files)) . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );

      $missing_found   = 0;
      $missing_errors  = 0;
      $outdated_found  = 0;
      $outdated_errors = 0;

      foreach ($files as $filename) {
         $output->writeln(
            '<comment>' . sprintf('Processing "%s".', $filename) . '</comment>',
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         switch (pathinfo($filename, PATHINFO_EXTENSION)) {
            case 'pl':
            case 'sh':
            case 'sql':
            case 'yaml':
            case 'yml':
               $header_line_prefix     = '# ';
               $header_prepend_line    = "#\n";
               $header_append_line     = "#\n";
               $header_start_pattern   = '/^#( \/\*\*)?$/'; // older headers were starting by "# /**"
               $header_content_pattern = '/^#/';
               break;
            default:
               $header_line_prefix     = ' * ';
               $header_prepend_line    = "/**\n";
               $header_append_line     = " */\n";
               $header_start_pattern   = '/^\/\*\*?$/';
               $header_content_pattern = '/^\s*\*/';
               break;
         }

         $header_found         = false;
         $header_missing       = false;
         $is_header_line       = false;
         $pre_header_lines     = [];
         $current_header_lines = [];
         $post_header_lines    = [];

         if (($file_lines = file($filename)) === false) {
            throw new \Exception(sprintf('Unable to read file.', $filename));
         }

         foreach ($file_lines as $line) {
            if (!$header_found && !$header_missing) {
               if (preg_match($header_start_pattern, $line)) {
                  // Line matches header opening line
                  $header_found = true;
                  $is_header_line = true;
               } else if (!$this->shouldLineBeLocatedBeforeHeader($line)) {
                  // Line does not match allowed lines before header,
                  // consider that header is missing.
                  $header_missing = true;
               }
            } else if ($is_header_line && !preg_match($header_content_pattern, $line)) {
               // Line does not match header, so it is the first line after licence header
               $is_header_line = false;
            }

            if ($header_missing || ($header_found && !$is_header_line)) {
               $post_header_lines[] = $line;
            } else if ($is_header_line) {
               $current_header_lines[] = $line;
            } else {
               $pre_header_lines[] = $line;
            }
         }

         $updated_header_lines = $this->getLicenceHeaderLines(
            $header_line_prefix,
            $header_prepend_line,
            $header_append_line
         );

         $header_outdated = $updated_header_lines !== $current_header_lines;

         if (!$header_missing && !$header_outdated) {
            continue;
         }

         if ($header_missing) {
            $output->writeln(
               '<info>' . sprintf('Missing licence header in file "%s".', $filename) . '</info>',
               OutputInterface::VERBOSITY_NORMAL
            );
            $missing_found++;
         } else {
            $output->writeln(
               '<info>' . sprintf('Licence header outdated in file "%s".', $filename) . '</info>',
               OutputInterface::VERBOSITY_NORMAL
            );
            $outdated_found++;
         }

         if ($input->getOption('fix')) {
            $file_contents = implode('', $this->stripEmptyLines($pre_header_lines, false, true))
               . implode('', $updated_header_lines) . "\n"
               . implode('', $this->stripEmptyLines($post_header_lines, true, false));
            if (strlen($file_contents) !== file_put_contents($filename, $file_contents)) {
               $output->writeln(
                  '<error>' . sprintf('Unable to update licence header in file "%s".', $filename) . '</error>',
                  OutputInterface::VERBOSITY_QUIET
               );
               if ($header_missing) {
                  $missing_errors++;
               } else {
                  $outdated_errors++;
               }
            }
         }
      }

      if ($missing_found === 0 && $outdated_found === 0) {
         $output->writeln('<info>Files headers are valid.</info>', OutputInterface::VERBOSITY_QUIET);
         return 0; // Success
      }

      if (!$input->getOption('fix')) {
         $msg = sprintf(
            'Found %d file(s) without header and %d file(s) with outdated header. Use --fix option to fix these files.',
            $missing_found,
            $outdated_found
         );
         $output->writeln('<error>' . $msg . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_FOUND_MISSING_OR_OUTDATED;
      }

      $msg = sprintf(
         'Fixed %d file(s) without header and %d file(s) with outdated header.',
         $missing_found - $missing_errors,
         $outdated_found - $outdated_errors
      );
      $output->writeln('<info>' . $msg . '</info>', OutputInterface::VERBOSITY_QUIET);

      if ($missing_errors > 0 || $outdated_errors > 0) {
         $output->writeln(
            '<error>' . sprintf('%s file(s) cannot be updated.', $missing_errors + $outdated_errors) . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_UNABLE_TO_FIX_FILES;
      }

      return 0; // Success
   }

   /**
    * Get lincence header lines.
    *
    * @param string $line_prefix
    * @param string $prepend_line
    * @param string $append_line
    *
    * @return array
    */
   private function getLicenceHeaderLines(string $line_prefix, string $prepend_line, string $append_line): array {
      if ($this->header_lines === null) {
         if (($lines = file(GLPI_ROOT . '/tools/HEADER')) === false) {
            throw new \Exception('Unable to read header file.');
         }
         $this->header_lines = $lines;
      }

      $lines = [];
      $lines[] = $prepend_line;
      foreach ($this->header_lines as $line) {
         $lines[] = (preg_match('/^\s+$/', $line) ? rtrim($line_prefix) : $line_prefix) . $line;
      }
      $lines[] = $append_line;

      return $this->stripEmptyLines($lines, true, true);
   }

   /**
    * Return files to parse.
    *
    * @return array
    */
   private function getFilesToParse(): array {
      $dir_iterator = new RecursiveIteratorIterator(
         new RecursiveDirectoryIterator(GLPI_ROOT),
         RecursiveIteratorIterator::SELF_FIRST
      );

      $excluded_elements = [
         '\.dependabot',
         '\.git',
         '\.github',
         'config',
         'files',
         'lib',
         'marketplace',
         'node_modules',
         'plugins',
         'public\/lib',
         'tests\/config_db\.php',
         'tests\/files',
         'vendor',
      ];

      $exclude_pattern = '/^'
         . preg_quote(GLPI_ROOT . DIRECTORY_SEPARATOR, '/')
         . '(' . implode('|', $excluded_elements) . ')'
         . '/';

      $files = [];

      /** @var SplFileInfo $file */
      foreach ($dir_iterator as $file) {
         if (!$file->isFile()
             || preg_match($exclude_pattern, $file->getRealPath())
             || !preg_match('/^(css|js|php|pl|scss|sql|ya?ml)$/', $file->getExtension())) {
            continue;
         }

         $files[] = $file->getRealPath();
      }

      return $files;
   }

   /**
    * Indicates if a line can/should be located before licence header.
    *
    * @param string $line
    *
    * @return bool
    */
   private function shouldLineBeLocatedBeforeHeader(string $line): bool {
      // PHP opening tag
      if (rtrim($line) === '<?php') {
         return true;
      }

      // Shebang
      if (preg_match('/^#!/', $line)) {
         return true;
      }

      // File generated by bootstap
      if (rtrim($line) === '/******/ (() => { // webpackBootstrap') {
         return true;
      }

      // Empty line
      if (trim($line) === '') {
         return true;
      }

      return false;
   }

   /**
    * Strip empty top/bottom lines from an array.
    *
    * @param array $lines
    * @param bool $strip_top_lines
    * @param bool $strip_bottom_lines
    *
    * @return array
    */
   private function stripEmptyLines(array $lines, bool $strip_top_lines, bool $strip_bottom_lines): array {
      // Remove empty lines from top of an array
      $strip_top_fct = function (array $values): array {
         $filtered_values = [];
         $found_not_empty = false;

         foreach ($values as $value) {
            if (!$found_not_empty && empty(trim($value))) {
               continue;
            }
            $found_not_empty = true;
            $filtered_values[] = $value;
         }

         return $filtered_values;
      };

      if ($strip_top_lines) {
         $lines = $strip_top_fct($lines);
      }

      if ($strip_bottom_lines) {
         $lines = array_reverse($lines);
         $lines = $strip_top_fct($lines);
         $lines = array_reverse($lines);
      }

      return $lines;
   }
}
