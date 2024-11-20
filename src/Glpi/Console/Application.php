<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Config;
use DB;
use DBmysql;
use GLPI;
use Glpi\Application\ErrorHandler;
use Glpi\Cache\CacheManager;
use Glpi\Console\Command\ConfigurationCommandInterface;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Glpi\Console\Command\GlpiCommandInterface;
use Glpi\System\Requirement\RequirementInterface;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\Filesystem;
use Plugin;
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

class Application extends BaseApplication
{
    /**
     * Error code returned when system requirements are missing.
     *
     * @var integer
     */
    const ERROR_MISSING_REQUIREMENTS = 128; // start application codes at 128 be sure to be different from commands codes

    /**
     * Error code returned if write access to configuration files is denied.
     *
     * @var integer
     */
    const ERROR_CONFIG_WRITE_ACCESS_DENIED = 129;

    /**
     * Error code returned when DB is not up-to-date.
     *
     * @var integer
     */
    const ERROR_DB_OUTDATED = 129;

    /**
     * Pointer to $CFG_GLPI.
     * @var array
     */
    private $config;

    /**
     * @var ErrorHandler
     */
    private $error_handler;

    /**
     * @var DBmysql
     */
    private $db;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct()
    {

        parent::__construct('GLPI CLI', GLPI_VERSION);

        $this->initApplication();
        $this->initCache();
        $this->initDb();
        $this->initSession();
        $this->initConfig();

        $this->computeAndLoadOutputLang();

       // Load core commands only to check if called command prevent or not usage of plugins
       // Plugin commands will be loaded later
        $loader = new CommandLoader(false);
        $this->setCommandLoader($loader);

        if ($this->usePlugins()) {
            $plugin = new Plugin();
            $plugin->init(true);
            $loader->setIncludePlugins(true);
        }
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $env_values = [GLPI::ENV_PRODUCTION, GLPI::ENV_STAGING, GLPI::ENV_TESTING, GLPI::ENV_DEVELOPMENT];

        $definition = new InputDefinition(
            [
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
                    '--no-plugins',
                    null,
                    InputOption::VALUE_NONE,
                    __('Disable GLPI plugins (unless commands forces plugins loading)')
                ),
                new InputOption(
                    '--lang',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    __('Output language (default value is existing GLPI "language" configuration or "en_GB")')
                )
            ]
        );

        return $definition;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->output = $output;
        ErrorHandler::getInstance()->setOutputHandler($output);

        parent::configureIO($input, $output);

       // Trigger error on invalid lang. This is not done before as error handler would not be set.
        $lang = $input->getParameterOption('--lang', null, true);
        if (null !== $lang && !array_key_exists($lang, $CFG_GLPI['languages'])) {
            throw new \Symfony\Component\Console\Exception\RuntimeException(
                sprintf(__('Invalid "--lang" option value "%s".'), $lang)
            );
        }

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);
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

        $is_db_available = $this->db instanceof DBmysql && $this->db->connected;

        if (
            $is_db_available
            && defined('SKIP_UPDATES')
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
        } catch (\Glpi\Console\Exception\EarlyExitException $e) {
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
     * Initalize GLPI.
     *
     * @return void
     */
    private function initApplication()
    {
    }

    /**
     * Initialize database connection.
     *
     * @global DBmysql $DB
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private function initDb()
    {

        if (!class_exists('DB', false) || !class_exists('mysqli', false)) {
            return;
        }

        /** @var \DBmysql $DB */
        global $DB;
        $DB = @new DB();
        $this->db = $DB;

        if (!$this->db->connected) {
            return;
        }

        ob_start();
        $checkdb = Config::displayCheckDbEngine();
        $message = ob_get_clean();
        if ($checkdb > 0) {
            throw new \Symfony\Component\Console\Exception\RuntimeException($message);
        }
    }

    /**
     * Initialize GLPI session.
     * This is mandatory to init cache and load languages.
     *
     * @TODO Do not use session for console.
     *
     * @return void
     */
    private function initSession()
    {

        if (!Session::canWriteSessionFiles()) {
            throw new \Symfony\Component\Console\Exception\RuntimeException(
                sprintf(__('Cannot write in "%s" directory.'), GLPI_SESSION_DIR)
            );
        }

        Session::setPath();
        Session::start();

       // Default value for use mode
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        $_SESSION['glpiname'] = 'cli';
    }

    /**
     * Initialize GLPI cache.
     *
     * @global \Psr\SimpleCache\CacheInterface $GLPI_CACHE
     *
     * @return void
     */
    private function initCache()
    {

        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;
        $cache_manager = new CacheManager();
        $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
    }

    /**
     * Initialize GLPI configuration.
     *
     * @global array $CFG_GLPI
     *
     * @return void
     */
    private function initConfig()
    {

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $this->config = &$CFG_GLPI;

        Config::detectRootDoc();

        if (
            !($this->db instanceof DBmysql)
            || !$this->db->connected
            || !$this->db->tableExists('glpi_configs')
        ) {
            return;
        }

        Config::loadLegacyConfiguration();
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

        $_SESSION['glpilanguage'] = $lang;

        Session::loadLanguage('', $this->usePlugins());
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
     * Whether or not plugins have to be used.
     *
     * @return boolean
     */
    private function usePlugins()
    {
        if (!($this->db instanceof DBmysql) || !$this->db->connected) {
            return false;
        }

        $input = new ArgvInput();

        try {
            $command = $this->find($this->getCommandName($input) ?? '');
            if ($command instanceof ForceNoPluginsOptionCommandInterface) {
                return !$command->getNoPluginsOptionValue();
            }
        } catch (\Symfony\Component\Console\Exception\CommandNotFoundException $e) {
           // Command will not be found at this point if it is a plugin command
            $command = null; // Say hello to CS checker
        }

        return !$input->hasParameterOption('--no-plugins', true);
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
        $db = property_exists($this, 'db') ? $this->db : null;

        $requirements_manager = new RequirementsManager();
        $core_requirements = $requirements_manager->getCoreRequirementList(
            $db instanceof DBmysql && $db->connected ? $db : null
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
            function ($path) {
                return GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . $path;
            },
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
     * @see \Symfony\Bundle\FrameworkBundle\Console\Application::getKernel()
     */
    public function getKernel(): ?KernelInterface
    {
        /** @var KernelInterface|null $kernel */
        global $kernel;

        return $kernel;
    }
}
