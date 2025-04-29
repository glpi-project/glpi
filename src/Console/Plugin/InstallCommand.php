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

namespace Glpi\Console\Plugin;

use Auth;
use Plugin;
use Session;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use User;

class InstallCommand extends AbstractPluginCommand
{
    /**
     * Error code returned when a plugin installation failed.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_INSTALLATION_FAILED = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName('plugin:install');
        $this->setDescription('Run plugin(s) installation script');

        $this->addOption(
            'param',
            'p',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            __('Additionnal parameters to pass to the plugin install hook function')
            . PHP_EOL
            . __('"-p foo" will set "foo" param value to true')
            . PHP_EOL
            . __('"-p foo=bar" will set "foo" param value to "bar"')
            . PHP_EOL
        );
        $this->addUsage('-p foo=bar -p force myplugin');

        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            __('Name of user used during installation script (among other things to set plugin admin rights)')
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            __('Force execution of installation, even if plugin is already installed')
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {

        parent::interact($input, $output);

        if (null === $input->getOption('username')) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $value = $question_helper->ask(
                $input,
                $output,
                new Question('User to use:')
            );
            $input->setOption('username', $value);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->normalizeInput($input);

        $this->loadUserSession($input->getOption('username'));

        $directories = $input->getArgument('directory');
        $force       = $input->getOption('force');

        $params      = $this->getAdditionnalParameters($input);

        $failed = false;

        foreach ($directories as $directory) {
            $output->writeln(
                '<info>' . sprintf(__('Processing plugin "%s"...'), $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );

            $plugin = new Plugin();
            $plugin->checkPluginState($directory); // Be sure that plugin information are up to date in DB

            if (!$this->canRunInstallMethod($directory, $force)) {
                $failed = true;
                continue;
            }

            if (!$plugin->getFromDBByCrit(['directory' => $directory])) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Unable to load plugin "%s" information.'), $directory) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $failed = true;
                continue;
            }
            $plugin->install($plugin->fields['id'], $params);

            // Check state after installation
            if (!in_array($plugin->fields['state'], [Plugin::NOTACTIVATED, Plugin::TOBECONFIGURED])) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Plugin "%s" installation failed.'), $directory) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $this->outputSessionBufferedMessages([WARNING, ERROR]);
                $failed = true;
                continue;
            }

            $message = Plugin::TOBECONFIGURED == $plugin->fields['state']
            ? __('Plugin "%1$s" has been installed and must be configured.')
            : __('Plugin "%1$s" has been installed and can be activated.');

            $output->writeln(
                '<info>' . sprintf($message, $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );
        }

        if ($failed) {
            return self::ERROR_PLUGIN_INSTALLATION_FAILED;
        }

        return 0; // Success
    }

    protected function getDirectoryChoiceQuestion()
    {

        return __('Which plugin(s) do you want to install (comma separated values)?');
    }

    protected function getDirectoryChoiceChoices()
    {

        $only_not_installed = !$this->input->getOption('force');

        // Fetch directory list
        $directories = [];
        foreach (PLUGINS_DIRECTORIES as $plugins_directory) {
            $directory_handle  = opendir($plugins_directory);
            while (false !== ($filename = readdir($directory_handle))) {
                if (
                    !in_array($filename, ['.svn', '.', '..'])
                    && is_dir($plugins_directory . DIRECTORY_SEPARATOR . $filename)
                ) {
                    $directories[] = $filename;
                }
            }
        }

        // Fetch plugins information
        $choices = [];
        foreach ($directories as $directory) {
            $plugin = new Plugin();
            $informations = $plugin->getInformationsFromDirectory($directory);

            if (empty($informations)) {
                continue; // Ignore directory if not able to load plugin information.
            }

            if (
                $only_not_installed
                && ($this->isAlreadyInstalled($directory)
                 || (array_key_exists('oldname', $informations)
                     && $this->isAlreadyInstalled($informations['oldname'])))
            ) {
                continue;
            }

            $choices[$directory] = array_key_exists('name', $informations)
            ? $informations['name']
            : $directory;
        }

        ksort($choices, SORT_STRING);

        return $choices;
    }

    /**
     * Load user in session.
     *
     * @param string $username
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function loadUserSession($username)
    {

        $user = new User();
        if ($user->getFromDBbyName($username)) {
            // Store computed output parameters
            $lang = $_SESSION['glpilanguage'];
            $session_use_mode = $_SESSION['glpi_use_mode'];

            $auth = new Auth();
            $auth->auth_succeded = true;
            $auth->user = $user;
            Session::init($auth);

            // Force usage of computed output parameters
            $_SESSION['glpilanguage'] = $lang;
            $_SESSION['glpi_use_mode'] = $session_use_mode;
            Session::loadLanguage();
        } else {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('User name defined by --username option is invalid.')
            );
        }
    }

    /**
     * Check if plugin is already installed.
     *
     * @param string $directory
     *
     * @return array
     */
    private function isAlreadyInstalled($directory)
    {

        $plugin = new Plugin();
        $is_already_known = $plugin->getFromDBByCrit(['directory' => $directory]);

        $installed_states = [
            Plugin::ACTIVATED,
            Plugin::TOBECONFIGURED,
            Plugin::NOTACTIVATED,
        ];
        return $is_already_known && in_array($plugin->fields['state'], $installed_states);
    }

    /**
     * Check if install method can be run for given plugin.
     *
     * @param string  $directory
     * @param boolean $allow_reinstall
     *
     * @return boolean
     */
    private function canRunInstallMethod($directory, $allow_reinstall)
    {

        $plugin = new Plugin();

        // Check that directory is valid
        $informations = $plugin->getInformationsFromDirectory($directory);
        if (empty($informations)) {
            $this->output->writeln(
                '<error>' . sprintf(__('Invalid plugin directory "%s".'), $directory) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        // Check if plugin is not already installed
        if (
            !$allow_reinstall
            && ($this->isAlreadyInstalled($directory)
              || (array_key_exists('oldname', $informations)
                  && $this->isAlreadyInstalled($informations['oldname'])))
        ) {
            $message = sprintf(
                __('Plugin "%s" is already installed. Use --force option to force reinstallation.'),
                $directory
            );
            $this->output->writeln(
                '<error>' . $message . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        Plugin::load($directory, true);

        // Check that required functions exists
        $function = 'plugin_' . $directory . '_install';
        if (!function_exists($function)) {
            $message = sprintf(
                __('Plugin "%s" function "%s" is missing.'),
                $directory,
                $function
            );
            $this->output->writeln(
                '<error>' . $message . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        // Check prerequisites
        ob_start();
        $requirements_met = $plugin->checkVersions($directory);
        $check_function   = 'plugin_' . $directory . '_check_prerequisites';
        if ($requirements_met && function_exists($check_function)) {
            $requirements_met = $check_function();
        }
        $ob_contents = ob_get_contents();
        ob_end_clean();
        if (!$requirements_met) {
            $this->output->writeln(
                [
                    '<error>' . sprintf(__('Plugin "%s" requirements not met.'), $directory) . '</error>',
                    '<error>' . $ob_contents . '</error>',
                ],
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        return true;
    }

    /**
     * Extract additionnal parameters from input.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    private function getAdditionnalParameters(InputInterface $input)
    {

        $input_params = $input->getOption('param');

        $params = [];
        foreach ($input_params as $input_param) {
            $parts = explode('=', $input_param);
            $params[$parts[0]] = $parts[1] ?? true;
        }

        return $params;
    }
}
