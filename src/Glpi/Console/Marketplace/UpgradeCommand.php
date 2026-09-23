<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class UpgradeCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('marketplace:upgrade');
        $this->setDescription(__('Download all plugins to their latest compatible versions, update all active plugins and reactivate those that were active.'));

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

        // Each plugin is processed in its own subprocess so its loaded code doesn't accumulate in memory.
        if ($this->upgradeActivePlugins($active_plugins, $updated_plugins, $username, $output)) {
            $has_errors = true;
        }

        return $has_errors ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param array<string, int> $active_plugins Plugin ids indexed by plugin directory.
     * @param string[] $updated_plugins Directories of the plugins to process.
     * @return bool `true` if at least one plugin could not be upgraded.
     */
    protected function upgradeActivePlugins(
        array $active_plugins,
        array $updated_plugins,
        ?string $username,
        OutputInterface $output
    ): bool {
        $has_errors = false;

        if (count($active_plugins) === 0) {
            return $has_errors;
        }

        \asort($active_plugins);

        foreach ($active_plugins as $plugin_key => $plugin_id) {
            if (!\in_array($plugin_key, $updated_plugins, true)) {
                continue;
            }

            if (!$this->upgradePlugin($plugin_key, $username, $output)) {
                $has_errors = true;
            }
        }

        return $has_errors;
    }

    /**
     * Install and activate the given plugin in a dedicated subprocess.
     */
    protected function upgradePlugin(string $plugin_key, ?string $username, OutputInterface $output): bool
    {
        $php_binary = (new PhpExecutableFinder())->find();

        $command = [$php_binary, GLPI_ROOT . '/bin/console', 'marketplace:upgrade:plugin', $plugin_key];
        if ($username !== null) {
            $command[] = "--username={$username}";
        }

        $process = new Process($command);
        $process->setTimeout(null);
        $process->run(static function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return $process->isSuccessful();
    }
}
