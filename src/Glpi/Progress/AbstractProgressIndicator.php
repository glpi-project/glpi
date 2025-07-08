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

use DateTimeInterface;
use Glpi\Message\MessageType;
use Safe\DateTimeImmutable;

abstract class AbstractProgressIndicator
{
    /**
     * Operation start datetime.
     */
    protected readonly DateTimeInterface $started_at;

    /**
     * Operation last update datetime.
     */
    protected DateTimeInterface $updated_at;

    /**
     * Operation end datetime.
     */
    protected ?DateTimeInterface $ended_at = null;

    /**
     * Indicates whether the operation failed.
     */
    protected bool $failed = false;

    /**
     * Current step.
     */
    protected int $current_step = 0;

    /**
     * Max steps.
     */
    protected int $max_steps = 0;

    /**
     * Progress bar message.
     */
    protected string $progress_bar_message = '';

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->started_at = clone $now;
        $this->updated_at = clone $now;
    }

    /**
     * Mark the operation as ended.
     */
    final public function finish(): void
    {
        $now = new DateTimeImmutable();

        $this->updated_at = $now;
        $this->ended_at = $now;

        $this->update();
    }

    /**
     * Mark the operation as failed.
     */
    final public function fail(): void
    {
        $this->failed = true;
        $this->finish();
    }

    /**
     * Get the operation start datetime.
     */
    final public function getStartedAt(): DateTimeInterface
    {
        return $this->started_at;
    }

    /**
     * Get the operation last update datetime.
     */
    final public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Get the operation end datetime.
     */
    final public function getEndedAt(): ?DateTimeInterface
    {
        return $this->ended_at;
    }

    /**
     * Indicates whether the operation is finished.
     */
    final public function isFinished(): bool
    {
        return $this->ended_at !== null;
    }

    /**
     * Indicates whether the operation failed.
     */
    final public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Get the current step.
     */
    final public function getCurrentStep(): int
    {
        return $this->current_step;
    }

    /**
     * Define the current step.
     */
    final public function setCurrentStep(int $current_step): void
    {
        $this->current_step = $current_step;

        $this->triggerUpdate();
    }

    /**
     * Advances the progress by the given number of steps.
     */
    final public function advance(int $steps = 1): void
    {
        $this->current_step += $steps;

        $this->triggerUpdate();
    }

    /**
     * Define the max steps count.
     */
    final public function setMaxSteps(int $max_steps): void
    {
        $this->max_steps = $max_steps;

        $this->triggerUpdate();
    }

    /**
     * Get the max steps count.
     */
    final public function getMaxSteps(): int
    {
        return $this->max_steps;
    }

    /**
     * Define the progress bar message.
     */
    final public function setProgressBarMessage(string $progress_bar_message): void
    {
        $this->progress_bar_message = $progress_bar_message;

        $this->triggerUpdate();
    }

    /**
     * Get the progress bar message.
     */
    final public function getProgressBarMessage(): string
    {
        return $this->progress_bar_message;
    }

    /**
     * Add a message.
     */
    abstract public function addMessage(MessageType $type, string $message): void;

    /**
     * Trigger the progress indicator update.
     */
    private function triggerUpdate(): void
    {
        $this->updated_at = new DateTimeImmutable();

        $this->update();
    }

    /**
     * Update the progress indicator.
     */
    abstract protected function update(): void;
}
