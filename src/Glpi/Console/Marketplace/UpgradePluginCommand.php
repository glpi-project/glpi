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
use Plugin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Install and activate a single plugin. Spawned as a subprocess by `marketplace:upgrade`.
 *
 * @internal
 */
class UpgradePluginCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('marketplace:upgrade:plugin');
        $this->setHidden();

        $this->addArgument(
            'plugin_key',
            InputArgument::REQUIRED,
            __('Directory of the plugin to install and activate')
        );

        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            __('Name of user used during installation script (among other things to set plugin admin rights)')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getOption('username');
        if ($username !== null) {
            $this->loadUserSession($username);
        }

        $plugin_key = $input->getArgument('plugin_key');

        $plugin = new Plugin();
        if (!$plugin->getFromDBbyDir($plugin_key)) {
            $output->writeln(sprintf('<error>%s</error>', sprintf(__('Plugin "%s" not found.'), $plugin_key)));
            return self::FAILURE;
        }
        $plugin_id = $plugin->fields['id'];

        Plugin::forcePluginsExecution(true); // Temporarly force the plugins execution

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
            $output->writeln('<error>' . sprintf(__('Plugin "%s" installation failed.'), $plugin_key) . '</error>');
            $this->outputSessionBufferedMessages([WARNING, ERROR]);
            return self::FAILURE;
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
            $output->writeln('<error>' . sprintf(__('Plugin "%s" activation failed.'), $plugin_key) . '</error>');
            $this->outputSessionBufferedMessages([WARNING, ERROR]);
            return self::FAILURE;
        }

        $output->writeln('<info>' . sprintf(__('Plugin "%1$s" has been updated and reactivated.'), $plugin_key) . '</info>');

        return self::SUCCESS;
    }
}
