<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Diagnostic;

use Glpi\Console\AbstractCommand;
use Glpi\System\Diagnostic\SourceCodeIntegrityChecker;
use Glpi\Toolbox\VersionParser;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckSourceCodeIntegrityCommand extends AbstractCommand
{
    protected $requires_db = false;

    protected function configure()
    {
        parent::configure();

        $this->setName('diagnostic:check_source_code_integrity');
        $this->setDescription(__('Check GLPI source code file integrity.'));
        $this->addOption(
            'diff',
            'd',
            InputOption::VALUE_NONE,
            __('Show diff of altered files'),
        );
        $this->addOption(
            'allow-download',
            null,
            InputOption::VALUE_NONE,
            __('Allow downloading the GLPI release if needed'),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $diff = $input->getOption('diff');
        $checker = new SourceCodeIntegrityChecker();
        $allow_download = $input->getOption('allow-download');

        // create temporary output buffer to capture output
        $output_buffer = fopen('php://memory', 'r+b');
        $temp_output = new StreamOutput($output_buffer, decorated: true);
        try {
            $summary = $checker->getSummary();
        } catch (\Exception $e) {
            $output->writeln('<error>' . sprintf(__('Failed to validate GLPI source code integrity. Error was: %s'), $e->getMessage()) . '</error>');
            return 1;
        }
        $all_ok = true;
        if (count(array_filter($summary, static fn($status) => $status === SourceCodeIntegrityChecker::STATUS_ALTERED)) > 0) {
            $all_ok = false;
        }
        if (count(array_filter($summary, static fn($status) => $status === SourceCodeIntegrityChecker::STATUS_MISSING)) > 0) {
            $all_ok = false;
        }
        if (count(array_filter($summary, static fn($status) => $status === SourceCodeIntegrityChecker::STATUS_ADDED)) > 0) {
            $all_ok = false;
        }
        if ($all_ok) {
            $temp_output->writeln('<info>' . __('GLPI source code integrity is validated.') . '</info>');
        } else {
            $temp_output->writeln('<error>' . __('GLPI source code integrity is not validated.') . '</error>');

            // @note Keep result untranslated
            $table = new Table($temp_output);
            $table->setHeaders(['File', 'Status']);
            foreach ($summary as $file => $status) {
                $status_label = match ($status) {
                    SourceCodeIntegrityChecker::STATUS_ALTERED => 'Altered',
                    SourceCodeIntegrityChecker::STATUS_MISSING => 'Missing',
                    SourceCodeIntegrityChecker::STATUS_ADDED => 'Added',
                };
                $table->addRow([
                    $file,
                    $status_label
                ]);
            }
            $table->render();
        }
        if (!$diff) {
            // copy lines from the temporary output buffer to the real output
            rewind($output_buffer);
            while (!feof($output_buffer)) {
                $output->write(fread($output_buffer, 4096));
            }
        } else {
            $errors = [];
            if (VersionParser::isDevVersion(GLPI_VERSION)) {
                $output->writeln('<error>' . __('Cannot generate a diff on a development version.') . '</error>');
            } else {
                $code_diff = $checker->getDiff($allow_download, $errors);

                if ($code_diff !== null) {
                    // enumerate lines of the temporary output buffer and add them to the diff prefixed with a # to make them comments
                    $diff_comments = '';
                    rewind($output_buffer);
                    while (!feof($output_buffer)) {
                        $diff_comments .= '# ' . fgets($output_buffer);
                    }

                    // Output with escaping so that style tags like <error> are shown as-is without being interpreted
                    $output->write(OutputFormatter::escape($diff_comments . "\n" . $code_diff));
                }

                if (count($errors) > 0) {
                    $output->writeln('<error>' . __('Errors occurred during diff generation:') . '</error>');
                    foreach ($errors as $error) {
                        $output->writeln('<error>- ' . $error . '</error>');
                    }
                    return 1;
                }
            }
        }

        //cleanup temporary output buffer
        fclose($output_buffer);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // If --allow-download missing, ask if it is OK
        if ($input->getOption('diff') && !$input->getOption('allow-download')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                __('Generating the source code diff could require downloading the GLPI release archive. Do you want to allow this operation?'),
                false
            );
            if ($helper->ask($input, $output, $question)) {
                $input->setOption('allow-download', true);
            }
        }
    }
}
