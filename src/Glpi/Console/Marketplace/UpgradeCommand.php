<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Console\Marketplace;

use Glpi\Console\AbstractCommand;
use Glpi\Marketplace\Api\Plugins;
use Glpi\Marketplace\Controller;
use GLPINetwork;
use Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class UpgradeCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('marketplace:upgrade');
        $this->setDescription(__('Download and update all plugins to their latest compatible versions, then reactivate active ones.'));

        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            __('Name of user used during installation script (among other things to set plugin admin rights)')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Controller::isCLIAllowed()) {
            $output->writeln("<error>" . __('Access to the marketplace CLI commands is disallowed by the GLPI configuration') . "</error>");
            return self::FAILURE;
        }

        if (!GLPINetwork::isRegistered()) {
            $output->writeln("<error>" . __("The GLPI Network registration key is missing or invalid") . "</error>");
            return self::FAILURE;
        }

        $username = $input->getOption('username');
        if ($username !== null) {
            $this->loadUserSession($username);
        }

        $has_errors = false;

        $plugins_manager = new Plugin();
        $plugins_api     = new Plugins();

        $local_plugins_data = $plugins_manager->find();
        $local_versions     = \array_column($local_plugins_data, 'version', 'directory');
        $active_plugins     = \array_column(
            \array_filter($local_plugins_data, fn($plugin_data) => $plugin_data['state'] === Plugin::ACTIVATED),
            'id',
            'directory'
        );

        // Update all plugins sources, to be sure that all plugins have the latest version.
        $updated_plugins = [];
        foreach ($local_versions as $plugin_key => $local_version) {
            if (!\file_exists(GLPI_MARKETPLACE_DIR . '/' . $plugin_key)) {
                $msg = '<comment>'
                    . sprintf(__('Plugin "%s" was installed manually and therefore has not been updated automatically.'), $plugin_key)
                    . '</comment>'
                ;
                $output->writeln($msg);
                continue;
            }

            $exists_on_filesystem = $plugins_manager->isLoadable($plugin_key);

            $plugin_info = $plugins_api->getPlugin($plugin_key);

            if ($plugin_info === []) {
                $msg = '<comment>'
                    . sprintf(__('Plugin "%s" is not present in the marketplace.'), $plugin_key)
                    . '</comment>'
                ;
                $output->writeln($msg);
                continue;
            }

            $latest_version = $plugin_info['version'] ?? null;

            if ($latest_version === null) {
                $msg = '<comment>'
                    . sprintf(__('Plugin "%s" is not available for your GLPI version.'), $plugin_key)
                    . '</comment>'
                ;
                $output->writeln($msg);
                continue;
            }

            if ($exists_on_filesystem && \version_compare($local_version, $latest_version, '<') === false) {
                $msg = '<comment>'
                    . sprintf(__('Plugin "%s" is already up-to-date.'), $plugin_key)
                    . '</comment>'
                ;
                $output->writeln($msg);
                continue;
            }

            $controller = new Controller($plugin_key);
            if (!$controller->canBeOverwritten()) {
                if ($controller::hasVcsDirectory($plugin_key)) {
                    $msg = '<comment>'
                        . sprintf(__('Plugin "%s" has a local source versioning directory.'), $plugin_key)
                        . ' '
                        . __('To avoid overwriting a potential branch under development, downloading is disabled.')
                        . '</comment>'
                    ;
                } else {
                    $msg = '<comment>'
                        . sprintf(__('Plugin "%s" has an available update but its directory is not writable.'), $plugin_key)
                        . '</comment>'
                    ;
                }

                $output->writeln($msg);
                continue;
            }

            $result = $controller->downloadPlugin(false, $latest_version);
            if ($result) {
                $updated_plugins[] = $plugin_key;
                $output->writeln('<info>' . sprintf(__('Plugin "%s" downloaded successfully'), $plugin_key) . '</info>');
            } else {
                $has_errors = true;
                $output->writeln(
                    '<error>' . sprintf(__('Plugin "%s" could not be downloaded'), $plugin_key) . '</error>',
                    OutputInterface::VERBOSITY_QUIET
                );
                $this->outputSessionBufferedMessages([WARNING, ERROR]);
            }
        }

        // Automatically process plugin update and reactivation of active plugins.
        if (count($active_plugins) > 0) {
            \asort($active_plugins);

            Plugin::forcePluginsExecution(true); // Temporarly force the plugins execution
            foreach ($active_plugins as $plugin_key => $plugin_id) {
                if (!\in_array($plugin_key, $updated_plugins, true)) {
                    continue;
                }

                $plugin = new Plugin();

                try {
                    $plugin->install($plugin_id);
                    $installed = \in_array($plugin->fields['state'], [Plugin::NOTACTIVATED, Plugin::TOBECONFIGURED]);
                } catch (Throwable $e) {
                    global $PHPLOGGER;
                    $PHPLOGGER->error(
                        sprintf('Error while installing plugin `%s`, error was: `%s`.', $plugin_key, $e->getMessage()),
                        ['exception' => $e]
                    );

                    $installed = false;
                }
                if (!$installed) {
                    $has_errors = true;
                    $output->writeln(
                        '<error>' . sprintf(__('Plugin "%s" installation failed.'), $plugin_key) . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $this->outputSessionBufferedMessages([WARNING, ERROR]);
                    continue;
                }

                try {
                    $activated = $plugin->activate($plugin_id);
                } catch (Throwable $e) {
                    global $PHPLOGGER;
                    $PHPLOGGER->error(
                        sprintf('Error while activating plugin `%s`, error was: `%s`.', $plugin_key, $e->getMessage()),
                        ['exception' => $e]
                    );

                    $activated = false;
                }

                if (!$activated) {
                    $has_errors = true;
                    $output->writeln(
                        '<error>' . sprintf(__('Plugin "%s" activation failed.'), $plugin_key) . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    $this->outputSessionBufferedMessages([WARNING, ERROR]);
                    continue;
                }

                $output->writeln('<info>' . sprintf(__('Plugin "%1$s" has been updated and reactivated.'), $plugin_key) . '</info>', );
            }
            Plugin::forcePluginsExecution(false);
        }

        return $has_errors ? self::FAILURE : self::SUCCESS;
    }
}
