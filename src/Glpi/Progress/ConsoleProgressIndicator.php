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

namespace Glpi\Progress;

use Glpi\Message\MessageType;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @final
 */
class ConsoleProgressIndicator extends AbstractProgressIndicator
{
    /**
     * Progress bar.
     */
    private readonly ProgressBar $progress_bar;

    /**
     * Progress feedback section.
     */
    private readonly ConsoleSectionOutput $progress_section;

    public function __construct(ConsoleOutputInterface $output)
    {
        parent::__construct();

        $this->progress_bar = new ProgressBar($output->section());
        $this->progress_bar->setFormat('[%bar%] %percent:3s%%' . PHP_EOL . '<comment>%message%</comment>' . PHP_EOL);
        $this->progress_bar->setMessage(''); // Empty message on iteration start
        $this->progress_bar->start();

        $this->progress_section = $output->section();
        $this->progress_section->setMaxHeight(25); // Keep only last 25 lines of progress feedback
    }

    public function addMessage(MessageType $type, string $message): void
    {
        match ($type) {
            MessageType::Error => $this->progress_section->writeln('> <error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET),
            MessageType::Warning => $this->progress_section->writeln('> <comment>' . $message . '</comment>', OutputInterface::VERBOSITY_NORMAL),
            MessageType::Success => $this->progress_section->writeln('> <info>' . $message . '</info>', OutputInterface::VERBOSITY_NORMAL),
            MessageType::Notice => $this->progress_section->writeln('> ' . $message, OutputInterface::VERBOSITY_NORMAL),
            MessageType::Debug => $this->progress_section->writeln('> [DEBUG] ' . $message, OutputInterface::VERBOSITY_VERY_VERBOSE),
        };
    }

    protected function update(): void
    {
        $this->progress_bar->setMaxSteps($this->getMaxSteps());
        $this->progress_bar->setProgress($this->getCurrentStep());
        $this->progress_bar->setMessage($this->getProgressBarMessage());

        if ($this->getEndedAt() !== null) {
            $this->progress_bar->finish();
            // Blank line between the progress feedback messages and the next messages.
            $this->progress_section->writeln('', OutputInterface::VERBOSITY_QUIET);
        }
    }
}
