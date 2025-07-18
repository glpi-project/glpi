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

namespace Glpi\Console\Marketplace;

use Glpi\Marketplace\Controller;
use GLPINetwork;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends AbstractMarketplaceCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('marketplace:download');
        $this->setDescription(__('Download plugin from the GLPI marketplace'));

        $this->addArgument('plugins', InputArgument::REQUIRED | InputArgument::IS_ARRAY, __('The plugin key'));
        $this->addOption('force', 'f', InputOption::VALUE_NONE, __('Force download even if the plugin is already downloaded'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Controller::isCLIAllowed()) {
            $output->writeln("<error>" . __('Access to the marketplace CLI commands is disallowed by the GLPI configuration') . "</error>");
            return 1;
        }

        if (!GLPINetwork::isRegistered()) {
            $output->writeln("<error>" . __("The GLPI Network registration key is missing or invalid") . "</error>");
        }

        $plugins = $input->getArgument('plugins');

        $plugin_versions = [];
        foreach ($plugins as $plugin) {
            if (empty(trim($plugin))) {
                continue;
            }
            if (str_contains($plugin, ':')) {
                [$plugin, $version] = explode(':', $plugin);
                $plugin_versions[$plugin] = $version;
            } else {
                $plugin_versions[$plugin] = null;
            }
        }

        foreach ($plugin_versions as $plugin => $version) {
            // If the plugin is already downloaded, refuse to download it again
            if (!$input->getOption('force') && is_dir(GLPI_MARKETPLACE_DIR . '/' . $plugin)) {
                if (Controller::hasVcsDirectory($plugin)) {
                    $error_msg = sprintf(__('Plugin "%s" as a local source versioning directory.'), $plugin);
                    $error_msg .=  "\n" . __('To avoid overwriting a potential branch under development, downloading is disabled.');
                } else {
                    $error_msg = sprintf(__('Plugin "%s" is already downloaded. Use --force to force it to re-download.'), $plugin);
                }
                $output->writeln("<error>$error_msg</error>");
                continue;
            }
            $controller = new Controller($plugin);
            if ($controller->canBeDownloaded($version)) {
                $result = $controller->downloadPlugin(false, $version);
                $success_msg = sprintf(__("Plugin %s downloaded successfully"), $plugin);
                $error_msg = sprintf(__("Plugin %s could not be downloaded"), $plugin);
                if ($result) {
                    $output->writeln("<info>$success_msg</info>");
                } else {
                    $output->writeln("<error>$error_msg</error>");
                }
            } else {
                $output->writeln('<error>' . sprintf(__('Plugin "%s" cannot be downloaded'), $plugin) . '</error>');
                return 1;
            }
        }

        return 0; // Success
    }

    protected function getPluginChoiceQuestion(): string
    {
        return __('Which plugin(s) do you want to download (comma separated values)?');
    }
}
