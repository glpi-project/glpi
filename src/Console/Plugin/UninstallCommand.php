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

namespace Glpi\Console\Plugin;

use Auth;
use Plugin;
use Session;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use User;

class UninstallCommand extends AbstractPluginCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:plugin:uninstall');
        $this->setAliases(['plugin:uninstall']);
        $this->setDescription('Run plugin(s) uninstallation script');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            '<error>' . __("This action will permanently delete data") . '</error>',
            OutputInterface::VERBOSITY_NORMAL
        );
        $this->askForConfirmation();

        // Handle special inputs like directory=*
        $this->normalizeInput($input);

        // Read input
        $directories = $input->getArgument('directory');

        // Default command status: success until at least one plugin failed
        $failed = false;

        foreach ($directories as $directory) {
            $output->writeln(
                '<info>' . sprintf(__('Processing plugin "%s"...'), $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );

            $plugin = new Plugin();
            $plugin->checkPluginState($directory); // Be sure that plugin information are up to date in DB

            if (!$this->canRunUninstallMethod($directory)) {
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
            $plugin->uninstall($plugin->fields['id']);

            // Check state after uninstallation
            if ($plugin->fields['state'] != Plugin::NOTINSTALLED) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Plugin "%s" uninstallation failed.'), $directory) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $this->outputSessionBufferedMessages([WARNING, ERROR]);
                $failed = true;
                continue;
            }

            $message = __('Plugin "%1$s" has been uninstalled.');

            $output->writeln(
                '<info>' . sprintf($message, $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    protected function getDirectoryChoiceQuestion()
    {
        return __('Which plugin(s) do you want to uninstall (comma separated values)?');
    }

    protected function getDirectoryChoiceChoices()
    {
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

            if ($this->isInstalled($directory)) {
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
     * Check if plugin is installed.
     *
     * @param string $directory
     *
     * @return array
     */
    private function isInstalled($directory)
    {
        $plugin = new Plugin();
        $is_already_known = $plugin->getFromDBByCrit(['directory' => $directory]);
        return $is_already_known && $plugin->fields['state'] != Plugin::NOTINSTALLED;
    }

    /**
     * Check if uninstall method can be run for given plugin.
     *
     * @param string  $directory
     *
     * @return boolean
     */
    private function canRunUninstallMethod($directory)
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
        if (!$this->isInstalled($directory)) {
            $message = sprintf(
                __('Plugin "%s" is not installed.'),
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
        $function = 'plugin_' . $directory . '_uninstall';
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

        return true;
    }
}
