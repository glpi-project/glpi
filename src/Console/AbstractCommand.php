<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Console;

use DB;
use Glpi\Console\Command\GlpiCommandInterface;
use Glpi\System\RequirementsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractCommand extends Command implements GlpiCommandInterface
{
    /**
     * @var DB
     */
    protected $db;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Flag to indicate if command requires a DB connection.
     *
     * @var boolean
     */
    protected $requires_db = true;

    /**
     * Flag to indicate if command requires an up-to-date DB.
     *
     * @var boolean
     */
    protected $requires_db_up_to_date = true;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        $this->input = $input;
        $this->output = $output;

        $this->initDbConnection();
    }

    /**
     * Check database connection.
     *
     * @throws RuntimeException
     *
     * @return void
     */
    protected function initDbConnection()
    {

        global $DB;

        if ($this->requires_db && (!($DB instanceof DB) || !$DB->connected)) {
            throw new \Symfony\Component\Console\Exception\RuntimeException(__('Unable to connect to database.'));
        }

        $this->db = $DB;
    }

    /**
     * Correctly write output messages when a progress bar is displayed.
     *
     * @param string|array $messages
     * @param ProgressBar  $progress_bar
     * @param integer      $verbosity
     *
     * @return void
     */
    protected function writelnOutputWithProgressBar(
        $messages,
        ProgressBar $progress_bar,
        $verbosity = OutputInterface::VERBOSITY_NORMAL
    ) {

        if ($verbosity > $this->output->getVerbosity()) {
            return; // Do nothing if message will not be output due to its too high verbosity
        }

        $progress_bar->clear();
        $this->output->writeln(
            $messages,
            $verbosity
        );
        $progress_bar->display();
    }

    /**
     * Output session buffered messages.
     *
     * @param array $levels_to_output
     *
     * @return void
     */
    protected function outputSessionBufferedMessages($levels_to_output = [INFO, WARNING, ERROR])
    {

        if (empty($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            return;
        }

        $msg_levels = [
            INFO    => [
                'tag'       => 'info',
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            ],
            WARNING => [
                'tag'       => 'comment',
                'verbosity' => OutputInterface::VERBOSITY_NORMAL,
            ],
            ERROR   => [
                'tag'       => 'error',
                'verbosity' => OutputInterface::VERBOSITY_QUIET,
            ],
        ];

        foreach ($msg_levels as $key => $options) {
            if (!in_array($key, $levels_to_output)) {
                continue;
            }

            if (!array_key_exists($key, $_SESSION['MESSAGE_AFTER_REDIRECT'])) {
                continue;
            }

            foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'][$key] as $message) {
                $message = strip_tags(preg_replace('/<br\s*\/?>/', ' ', $message)); // Output raw text
                $this->output->writeln(
                    "<{$options['tag']}>{$message}</{$options['tag']}>",
                    $options['verbosity']
                );
            }
        }
    }

    /**
     * Output a warning in an optionnal requirement is missing.
     *
     * @return void
     */
    protected function outputWarningOnMissingOptionnalRequirements()
    {
        if ($this->output->isQuiet()) {
            return;
        }

        $db = property_exists($this, 'db') ? $this->db : null;

        $requirements_manager = new RequirementsManager();
        $core_requirements = $requirements_manager->getCoreRequirementList(
            $db instanceof \DBmysql && $db->connected ? $db : null
        );
        if ($core_requirements->hasMissingOptionalRequirements()) {
            $message = __('Some optional system requirements are missing.')
            . ' '
            . __('Run "php bin/console glpi:system:check_requirements" for more details.');
            $this->output->writeln(
                '<comment>' . $message . '</comment>',
                OutputInterface::VERBOSITY_NORMAL
            );
        }
    }

    public function mustCheckMandatoryRequirements(): bool
    {

        return true;
    }

    public function requiresUpToDateDb(): bool
    {

        return $this->requires_db && $this->requires_db_up_to_date;
    }

    /**
     * Ask for user confirmation before continuing command execution.
     *
     * @param bool $default_to_yes
     *
     * @return void
     */
    protected function askForConfirmation(bool $default_to_yes = true): void
    {
        $abort = false;
        if (!$this->input->getOption('no-interaction')) {
            $question_helper = $this->getHelper('question');
            $run = $question_helper->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion(
                    __('Do you want to continue?') . ($default_to_yes ? ' [Yes/no]' : ' [yes/No]'),
                    $default_to_yes
                )
            );
            $abort = !$run;
        } else {
            $abort = !$default_to_yes;
        }

        if ($abort) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<comment>' . __('Aborted.') . '</comment>',
                0 // Success code
            );
        }
    }
}
