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

use Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateCommand extends AbstractPluginCommand
{
    /**
     * Error code returned when a plugin activation failed.
     *
     * @var integer
     */
    public const ERROR_PLUGIN_ACTIVATION_FAILED = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName('plugin:activate');
        $this->setDescription('Activate plugin(s)');
        $this->setAliases(['plugin:enable']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->normalizeInput($input);

        $directories   = $input->getArgument('directory');

        $failed = false;

        foreach ($directories as $directory) {
            $output->writeln(
                '<info>' . sprintf(__('Processing plugin "%s"...'), $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );

            $plugin = new Plugin();
            $plugin->checkPluginState($directory); // Be sure that plugin information are up to date in DB

            if (!$this->canRunActivateMethod($directory)) {
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

            if (!$plugin->activate($plugin->fields['id'])) {
                $this->output->writeln(
                    '<error>' . sprintf(__('Plugin "%s" activation failed.'), $directory) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $this->outputSessionBufferedMessages([WARNING, ERROR]);
                $failed = true;
                continue;
            }

            $output->writeln(
                '<info>' . sprintf(__('Plugin "%1$s" has been activated.'), $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );
        }

        if ($failed) {
            return self::ERROR_PLUGIN_ACTIVATION_FAILED;
        }

        return 0; // Success
    }

    /**
     * Check if activate method can be run for given plugin.
     *
     * @param string  $directory
     *
     * @return boolean
     */
    private function canRunActivateMethod($directory)
    {

        $plugin = new Plugin();

        // Check that directory is valid
        if (!$plugin->isLoadable($directory)) {
            $this->output->writeln(
                '<error>' . sprintf(__('Invalid plugin directory "%s".'), $directory) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        // Check current plugin state
        $is_already_known = $plugin->getFromDBByCrit(['directory' => $directory]);
        if (!$is_already_known) {
            $this->output->writeln(
                '<error>' . sprintf(__('Plugin "%s" is not yet installed.'), $directory) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        if ($plugin->fields['state'] == Plugin::ACTIVATED) {
            $this->output->writeln(
                '<info>' . sprintf(__('Plugin "%s" is already active.'), $directory) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );
            return false;
        }

        if (Plugin::NOTACTIVATED != $plugin->fields['state']) {
            $this->output->writeln(
                '<error>' . sprintf(__('Plugin "%s" have to be installed and configured prior to activation.'), $directory) . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return false;
        }

        return true;
    }

    protected function getDirectoryChoiceQuestion()
    {

        return __('Which plugin(s) do you want to activate (comma separated values)?');
    }

    protected function getDirectoryChoiceChoices()
    {

        $choices = [];
        $plugin_iterator = $this->db->request(
            [
                'FROM'  => Plugin::getTable(),
                'WHERE' => [
                    'state' => Plugin::NOTACTIVATED,
                ],
            ]
        );
        foreach ($plugin_iterator as $plugin) {
            $choices[$plugin['directory']] = $plugin['name'];
        }

        ksort($choices, SORT_STRING);

        return $choices;
    }
}
