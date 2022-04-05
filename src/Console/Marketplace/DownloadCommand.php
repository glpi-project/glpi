<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Marketplace;

use Glpi\Console\AbstractCommand;
use Glpi\Marketplace\Controller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:marketplace:download');
        $this->setAliases(['marketplace:download']);
        $this->setDescription('Download plugin from the GLPI marketplace');

        $this->addArgument('plugins', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The internal plugin name(s)');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force download even if the plugin is already downloaded');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugins = $input->getArgument('plugins');

        foreach ($plugins as $plugin) {
            if (!empty(trim($plugin))) {
                // If the plugin is already downloaded, refuse to download it again
                if (!$input->getOption('force') && is_dir(GLPI_MARKETPLACE_DIR . '/' . $plugin)) {
                    $error_msg = sprintf('Plugin "%s" is already downloaded. Use --force to force it to re-download.', $plugin);
                    $output->writeln("<error>$error_msg</error>");
                    continue;
                }
                $controller = new Controller($plugin);
                if ($controller->canBeDownloaded()) {
                    $result = $controller->downloadPlugin(true);
                    $success_msg = sprintf(__("Plugin %s downloaded successfully"), $plugin);
                    $error_msg = sprintf(__("Plugin %s could not be downloaded"), $plugin);
                    if ($result) {
                        $output->writeln("<info>$success_msg</info>");
                    } else {
                        $output->writeln("<error>$error_msg</error>");
                    }
                } else {
                    throw new \RuntimeException(sprintf(__('Plugin "%s" cannot be downloaded'), $plugin));
                }
            }
        }

        return 0; // Success
    }
}
