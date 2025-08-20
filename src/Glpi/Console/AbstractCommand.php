<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DBmysql;
use Glpi\Console\Command\GlpiCommandInterface;
use Glpi\Console\Exception\EarlyExitException;
use Glpi\System\RequirementsManager;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function Safe\preg_replace;

abstract class AbstractCommand extends Command implements GlpiCommandInterface
{
    /**
     * @var DBmysql|null
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

    /**
     * Current progress bar.
     *
     * @var ?ProgressBar
     */
    protected $progress_bar;

    #[Override]
    public function getSpecificMandatoryRequirements(): array
    {
        return [];
    }

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

        /** @var DBmysql|null $DB */
        global $DB;

        if ($this->requires_db && !\DBConnection::isDbAvailable()) {
            throw new EarlyExitException(
                '<error>' . __('Unable to connect to database.') . '</error>',
                Application::ERROR_DB_UNAVAILABLE
            );
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

        $db = $this->db;

        $requirements_manager = new RequirementsManager();
        $core_requirements = $requirements_manager->getCoreRequirementList(
            $db instanceof DBmysql && $db->connected ? $db : null
        );
        if ($core_requirements->hasMissingOptionalRequirements()) {
            $message = __('Some optional system requirements are missing.')
            . ' '
            . sprintf(__('Run the "%1$s" command for more details.'), 'php bin/console system:check_requirements');
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
            $question_helper = new QuestionHelper();
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
            throw new EarlyExitException(
                '<comment>' . __('Aborted.') . '</comment>',
                0 // Success code
            );
        }
    }

    /**
     * Tell user that execution time can be long.
     *
     * @return void
     */
    protected function warnAboutExecutionTime(): void
    {
        $this->output->writeln(
            '<comment>' . __('Command execution may take a long time and should not be interrupted.') . '</comment>',
            OutputInterface::VERBOSITY_QUIET
        );
    }

    /**
     * Iterate on given iterable and display a progress bar (unless on quiet mode).
     * Progress bar message can be customized.
     *
     * @param iterable $iterable
     * @param callable $message_callback
     * @param int|null $count
     *
     * @return iterable
     */
    final protected function iterate(
        iterable $iterable,
        ?callable $message_callback = null,
        ?int $count = null
    ): iterable {
        // Redefine formats
        $formats = [
            ProgressBar::FORMAT_NORMAL,
            ProgressBar::FORMAT_NORMAL . '_nomax',
            ProgressBar::FORMAT_VERBOSE,
            ProgressBar::FORMAT_VERBOSE . '_nomax',
            ProgressBar::FORMAT_VERY_VERBOSE,
            ProgressBar::FORMAT_VERY_VERBOSE . '_nomax',
            ProgressBar::FORMAT_DEBUG,
            ProgressBar::FORMAT_DEBUG . '_nomax',
        ];
        $original_formats_definitions = [];
        if (is_callable($message_callback)) {
            foreach ($formats as $format) {
                $original_formats_definitions[$format] = ProgressBar::getFormatDefinition($format);
                // Put message directly in progress bar template
                ProgressBar::setFormatDefinition(
                    $format,
                    $original_formats_definitions[$format] . PHP_EOL . ' <comment>%message%</comment>' . PHP_EOL
                );
            }
        }

        // Init progress bar
        $this->progress_bar = new ProgressBar($this->output);
        $this->progress_bar->setMessage(''); // Empty message on iteration start
        $this->progress_bar->start(
            !is_null($count)
            ? $count
            : (is_countable($iterable) ? \count($iterable) : 0)
        );

        // Iterate on items
        foreach ($iterable as $key => $value) {
            if (is_callable($message_callback)) {
                $this->progress_bar->setMessage($message_callback($value, $key));
                $this->progress_bar->display();
            }

            yield $key => $value;

            $this->progress_bar->advance();
        }

        // Finish progress bar
        $this->progress_bar->setMessage(''); // Remove last message
        $this->progress_bar->finish();
        $this->progress_bar = null;

        // Restore formats
        if (is_callable($message_callback)) {
            foreach ($formats as $format) {
                ProgressBar::setFormatDefinition($format, $original_formats_definitions[$format]);
            }
        }
    }

    /**
     * Output a message.
     * This method handles displaying of messages in the middle of progress bar iteration.
     *
     * @param string $message
     * @param int $verbosity
     */
    final protected function outputMessage(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        if ($this->progress_bar !== null) {
            $this->writelnOutputWithProgressBar($message, $this->progress_bar, $verbosity);
        } else {
            $this->output->writeln(
                $message,
                $verbosity
            );
        }
    }
}
