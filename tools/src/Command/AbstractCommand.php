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

namespace Glpi\Tools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command extending this class don't have a native way to interact with the GLPI core.
 * If you need to interact with it or its database, use the `Glpi\Console\AbstractCommand` instead.
 */
abstract class AbstractCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $io;

    #[Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        if ($this->isRequiringPluginOption() && $input->getOption('plugin') === null) {
            throw new InvalidOptionException('The "--plugin" option is required for this command.');
        }
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        if ($this->isPluginOptionAvailable()) {
            $this->addOption(
                'plugin',
                'p',
                InputOption::VALUE_REQUIRED,
                'Plugin name'
            );
        }
    }

    public function isPluginCommand(): bool
    {
        return $this->input->hasOption('plugin') && $this->input->getOption('plugin') !== null;
    }

    public function getPluginName(): string
    {
        if (!$this->isPluginCommand()) {
            throw new \LogicException('This command is not a plugin command.');
        }
        return $this->input->getOption('plugin');
    }

    protected function getPluginDirectory(): string
    {
        $plugin_name = $this->getPluginName();
        $directory = \Plugin::getPhpDir($plugin_name);
        if (!$directory) {
            throw new \RuntimeException(sprintf('Plugin directory for "%s" not found.', $plugin_name));
        }
        return $directory;
    }

    /**
     * Declare whether the command supports --plugin.
     * @return bool
     */
    protected function isPluginOptionAvailable(): bool
    {
        return $this->isRequiringPluginOption();
    }

    /**
     * Declare the command supporting plugin and require it to be set.
     * @return bool
     */
    protected function isRequiringPluginOption(): bool
    {
        return false;
    }
}
