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
use Glpi\Application\Environment;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\Console\Command\GlpiCommandInterface;
use Glpi\Console\Exception\EarlyExitException;
use Glpi\Error\ErrorDisplayHandler\ConsoleErrorDisplayHandler;
use Glpi\Kernel\Kernel;
use Glpi\System\Requirement\RequirementInterface;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\Filesystem;
use Session;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Toolbox;
use Update;

use function Safe\preg_replace;

class Application extends BaseApplication
{
    /**
     * Error code returned when system requirements are missing.
     *
     * @var integer
     */
    public const ERROR_MISSING_REQUIREMENTS = 128; // start application codes at 128 be sure to be different from commands codes

    /**
     * Error code returned if write access to configuration files is denied.
     *
     * @var integer
     */
    public const ERROR_CONFIG_WRITE_ACCESS_DENIED = 129;

    /**
     * Error code returned when DB is not available.
     *
     * @var integer
     */
    public const ERROR_DB_UNAVAILABLE = 130;

    /**
     * Error code returned when DB is not up-to-date.
     *
     * @var integer
     */
    public const ERROR_DB_OUTDATED = 131;

    /**
     * Pointer to $CFG_GLPI.
     * @var array
     */
    private $config;

    private ?DBmysql $db = null;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(private Kernel $kernel)
    {
        global $DB, $CFG_GLPI;

        parent::__construct('GLPI CLI', GLPI_VERSION);

        $this->kernel->boot();
        $this->setDispatcher($this->kernel->getContainer()->get('event_dispatcher'));

        $this->db = $DB;
        $this->config = &$CFG_GLPI;

        // Force the current "username"
        $_SESSION['glpiname'] = 'cli';

        $this->computeAndLoadOutputLang();

        $loader = new CommandLoader(include_plugins: $this->db instanceof DBmysql && $this->db->connected);
        $this->setCommandLoader($loader);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $env_values = Environment::getValues();

        $definition = [
            new InputArgument(
                'command',
                InputArgument::REQUIRED,
                __('The command to execute')
            ),

            new InputOption(
                '--help',
                '-h',
                InputOption::VALUE_NONE,
                __('Display this help message')
            ),
            new InputOption(
                '--quiet',
                '-q',
                InputOption::VALUE_NONE,
                __('Do not output any message')
            ),
            new InputOption(
                '--verbose',
                '-v|vv|vvv',
                InputOption::VALUE_NONE,
                __('Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug')
            ),
            new InputOption(
                '--version',
                '-V',
                InputOption::VALUE_NONE,
                __('Display this application version')
            ),
            new InputOption(
                '--ansi',
                null,
                InputOption::VALUE_NONE,
                __('Force ANSI output')
            ),
            new InputOption(
                '--no-ansi',
                null,
                InputOption::VALUE_NONE,
                __('Disable ANSI output')
            ),
            new InputOption(
                '--no-interaction',
                '-n',
                InputOption::VALUE_NONE,
                __('Do not ask any interactive question')
            ),
            new InputOption(
                '--env',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(__('Environment to use, possible values are: %s'), '`' . implode('`, `', $env_values) . '`'),
                suggestedValues: $env_values
            ),
            new InputOption(
                '--config-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Configuration directory to use. Deprecated option')
            ),
            new InputOption(
                '--lang',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Output language (default value is existing GLPI "language" configuration or "en_GB")')
            ),
        ];

        if (
            in_array('--allow-superuser', $_SERVER['argv'], true)
            || (\function_exists('posix_geteuid') && \posix_geteuid() === 0)
        ) {
            // Prevent the `The "--allow-superuser" option does not exist.` error when executing the console as a superuser.
            $definition[] = new InputOption(
                name: '--allow-superuser',
                description: __('Allow the console to be executed by the root user'),
            );
        }

        return new InputDefinition($definition);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {

        global $CFG_GLPI;

        $this->output = $output;
        ConsoleErrorDisplayHandler::setOutput($output);

        parent::configureIO($input, $output);

        // Trigger error on invalid lang. This is not done before as error handler would not be set.
        $lang = $input->getParameterOption('--lang', null, true);
        if (null !== $lang && !array_key_exists($lang, $CFG_GLPI['languages'])) {
            throw new RuntimeException(
                sprintf(__('Invalid "--lang" option value "%s".'), $lang)
            );
        }

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            Toolbox::setDebugMode(Session::DEBUG_MODE);
        }
    }

    /**
     * Returns output handler.
     *
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    protected function getCommandName(InputInterface $input): ?string
    {
        $name = parent::getCommandName($input);
        if ($name !== null) {
            // strip `glpi:` prefix that was used before GLPI 10.0.6
            // FIXME Deprecate usage of `glpi:` prefix in GLPI 11.0.
            $name = preg_replace('/^glpi:/', '', $name);
        }
        return $name;
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        $begin_time = microtime(true);

        if (\function_exists('posix_geteuid') && \posix_geteuid() === 0) {
            // This message cannot be mutualized with the message displayed in the top of the `bin/console` script:
            // - when the execution as root IS NOT allowed, we must exit before the kernel instantiation, to prevent any cache file creation;
            // - when the execution as root IS allowed, we must display this message after the kernel instantiation,
            //   to prevent a `Session cannot be started after headers have already been sent` warning.
            $output->writeln([
                '<bg=yellow;fg=black;options=bold> WARNING: running as root is discouraged. </>',
                '<bg=yellow;fg=black;options=bold> You should run the script as the same user that your web server runs as to avoid file permissions being ruined. </>',
                '',
            ]);
        }

        $is_db_available = $this->db instanceof DBmysql && $this->db->connected;

        if (
            $is_db_available
            && GLPI_SKIP_UPDATES
            && (!($command instanceof GlpiCommandInterface) || $command->requiresUpToDateDb())
            && !Update::isDbUpToDate()
        ) {
            $output->writeln(
                '<bg=yellow;fg=black;options=bold> '
                . __("You are bypassing a needed update")
                . ' </>'
            );
        } elseif (
            $is_db_available
            && $command instanceof GlpiCommandInterface
            && $command->requiresUpToDateDb()
            && !Update::isDbUpToDate()
        ) {
            $output->writeln(
                '<error>'
                . __('The GLPI codebase has been updated. The update of the GLPI database is necessary.')
                . '</error>'
                . PHP_EOL
                . '<error>'
                . sprintf(__('Run the "%1$s" command to process to the update.'), 'php bin/console database:update')
                . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_DB_OUTDATED;
        }

        if (
            $command instanceof GlpiCommandInterface && $command->mustCheckMandatoryRequirements()
            && !$this->checkCoreMandatoryRequirements(
                $command->getSpecificMandatoryRequirements()
            )
        ) {
            return self::ERROR_MISSING_REQUIREMENTS;
        }

        if (!$this->checkConfigWriteAccess($command, $input)) {
            return self::ERROR_CONFIG_WRITE_ACCESS_DENIED;
        }

        try {
            $result = parent::doRunCommand($command, $input, $output);
        } catch (EarlyExitException $e) {
            $result = $e->getCode();
            $output->writeln($e->getMessage(), OutputInterface::VERBOSITY_QUIET);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->writeln(
                sprintf(
                    __('Time elapsed: %s.'),
                    Helper::formatTime(microtime(true) - $begin_time)
                )
            );
            $output->writeln(
                sprintf(
                    __('Memory usage: %s.'),
                    Helper::formatMemory(memory_get_peak_usage(true))
                )
            );
        }

        return $result;
    }

    /**
     * Compute and load output language.
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private function computeAndLoadOutputLang()
    {

        // 1. Check in command line arguments
        $input = new ArgvInput();
        $lang = $input->getParameterOption('--lang', null, true);

        if (null !== $lang && !$this->isLanguageValid($lang)) {
            // Unset requested lang if invalid
            $lang = null;
        }

        // 2. Check in GLPI configuration
        if (
            null === $lang && array_key_exists('language', $this->config)
            && $this->isLanguageValid($this->config['language'])
        ) {
            $lang = $this->config['language'];
        }

        // 3. Use default value
        if (null === $lang) {
            $lang = 'en_GB';
        }

        if ($lang !== $_SESSION['glpilanguage']) {
            $_SESSION['glpilanguage'] = $lang;

            Session::loadLanguage();
        }
    }

    /**
     * Check if a language is valid.
     *
     * @param string $language
     *
     * @return boolean
     */
    private function isLanguageValid($language)
    {
        return is_array($this->config)
         && array_key_exists('languages', $this->config)
         && array_key_exists($language, $this->config['languages']);
    }

    /**
     * Check if core mandatory requirements are OK.
     *
     * @param RequirementInterface[] $command_specific_requirements
     *
     * @return boolean  true if requirements are OK, false otherwise
     */
    private function checkCoreMandatoryRequirements(
        array $command_specific_requirements
    ): bool {
        $requirements_manager = new RequirementsManager();
        $core_requirements = $requirements_manager->getCoreRequirementList(
            $this->db instanceof DBmysql && $this->db->connected ? $this->db : null
        );

        // Some commands might specify some extra requirements
        if (count($command_specific_requirements)) {
            $core_requirements->add(...$command_specific_requirements);
        }

        if ($core_requirements->hasMissingMandatoryRequirements()) {
            $message = __('Some mandatory system requirements are missing:');
            $this->output->writeln(
                '<error>' . $message . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );

            foreach ($core_requirements->getErrorMessages() as $message) {
                $this->output->writeln(
                    '<error> - ' . $message . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
            }

            $message = sprintf(
                __('Run the "%1$s" command for more details.'),
                'php bin/console system:check_requirements'
            );
            $this->output->writeln(
                '<info>' . $message . '</info>',
                OutputInterface::VERBOSITY_QUIET
            );

            return false;
        }

        return true;
    }

    /**
     * Check potentially required write access to configuration files.
     *
     * @param Command $command
     * @param InputInterface $input
     *
     * @return bool
     */
    private function checkConfigWriteAccess(Command $command, InputInterface $input): bool
    {
        if (!($command instanceof ConfigurationCommandInterface)) {
            return true;
        }

        $config_files_to_update = array_map(
            fn($path) => GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . $path,
            $command->getConfigurationFilesToUpdate($input)
        );
        if (!Filesystem::canWriteFiles($config_files_to_update)) {
            $this->output->writeln(
                [
                    '<error>' . sprintf(__('A temporary write access to the following files is required: %s.'), '`' . implode('`, `', $config_files_to_update) . '`') . '</error>',
                    '<error>' . __('Write access to these files can be removed once the operation is finished.') . '</error>',
                ],
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        return true;
    }

    public function extractNamespace(string $name, ?int $limit = null): string
    {
        $parts = explode(':', $name);

        if ($limit === 1 && count($parts) >= 2 && $parts[0] === 'plugins') {
            // Force grouping plugin commands
            $limit = 2;
        }

        return implode(':', null === $limit ? $parts : \array_slice($parts, 0, $limit));
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * This method is required by most of the commands provided by the `symfony/framework-bundle`.
     *
     * @see \Symfony\Bundle\FrameworkBundle\Console\Application::getKernel()
     */
    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }
}
