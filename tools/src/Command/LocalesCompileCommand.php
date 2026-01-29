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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class LocalesCompileCommand extends AbstractCommand
{
    #[Override]
    protected function isPluginOptionAvailable(): bool
    {
        return true;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('tools:locales:compile');
        $this->setDescription('Compile MO files from PO files.');
        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_REQUIRED,
            'Source directory containing the locales folder with PO files.'
        );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getOption('directory');
        if ($directory !== null) {
            $working_dir = $directory;
        } elseif ($this->isPluginCommand()) {
            $working_dir = $this->getPluginDirectory();
        } else {
            $working_dir = dirname(__DIR__, 3); // glpi
        }

        $success = $this->compile($working_dir);
        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    private function compile(string $dir): bool
    {
        $locales_dir = $dir . '/locales';
        $this->io->section("Compiling MO files...");
        $this->io->writeln(" <info>Locales dir: $locales_dir</info>", OutputInterface::VERBOSITY_VERBOSE);

        if (!is_dir($locales_dir)) {
            $this->io->error("Locales dir '$locales_dir' does not exist.");
            return false;
        }

        $files = glob($locales_dir . '/*.po');
        if (empty($files)) {
            $this->io->error("No .po files found in $locales_dir");
            return false;
        }

        // Check msgfmt
        $finder = new ExecutableFinder();
        if (!$finder->find('msgfmt')) {
            $this->io->error("msgfmt executable not found!");
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            $mo = preg_replace('/\.po$/', '.mo', $file);
            $basename = basename($file);

            $proc = new Process(['msgfmt', $file, '-o', $mo]);
            $proc->run();

            if (!$proc->isSuccessful()) {
                $success = false;
                $this->io->writeln(" <error>Failed to compile $basename</error>");
            } else {
                $this->io->writeln(" <info>Compiled $basename</info>");
            }
        }
        return $success;
    }
}
