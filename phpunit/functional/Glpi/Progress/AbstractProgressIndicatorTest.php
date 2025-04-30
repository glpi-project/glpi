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

namespace tests\units\Glpi\Log;

use DateTimeImmutable;
use Glpi\Message\MessageType;
use Glpi\Progress\AbstractProgressIndicator;
use GLPITestCase;

class AbstractProgressIndicatorTest extends GLPITestCase
{
    public function testConstructor(): void
    {
        // Arrange
        $date = new DateTimeImmutable();

        // Act
        $instance = $this->getInstance();

        // Assert
        $this->assertEquals($instance->getStartedAt(), $instance->getUpdatedAt());

        $this->assertGreaterThanOrEqual($date, $instance->getStartedAt());
        $this->assertLessThanOrEqual(new DateTimeImmutable(), $instance->getStartedAt());

        $this->assertGreaterThanOrEqual($date, $instance->getUpdatedAt());
        $this->assertLessThanOrEqual(new DateTimeImmutable(), $instance->getUpdatedAt());
    }

    public function testFinish(): void
    {
        // Arrange
        $instance = $this->getInstance();

        // Act
        $instance->finish();

        // Assert
        $this->assertEquals(1, $instance->updates_count); // update has been triggered once

        $this->assertEquals(false, $instance->hasFailed());

        $this->assertEquals($instance->getUpdatedAt(), $instance->getEndedAt());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getUpdatedAt());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getEndedAt());

        $this->assertEquals(1, $instance->updates_count); // update has not been re-triggered by getters
    }

    public function testFailed(): void
    {
        // Arrange
        $instance = $this->getInstance();

        // Act
        $instance->fail();

        // Assert
        $this->assertEquals(1, $instance->updates_count); // update has been triggered once

        $this->assertEquals(true, $instance->hasFailed());

        $this->assertEquals($instance->getUpdatedAt(), $instance->getEndedAt());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getUpdatedAt());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getEndedAt());

        $this->assertEquals(1, $instance->updates_count); // update has not been re-triggered by getters
    }

    public function testMaxStepsAccessors(): void
    {
        // Arrange
        $instance = $this->getInstance();

        // Act
        $instance->setMaxSteps($steps = \rand(1, 100));

        // Assert
        $this->assertEquals(1, $instance->updates_count); // update has been triggered once

        $this->assertEquals($steps, $instance->getMaxSteps());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getUpdatedAt());

        $this->assertEquals(1, $instance->updates_count); // update has not been re-triggered by getters
    }

    public function testCurrentStepsAccessors(): void
    {
        // Arrange
        $instance = $this->getInstance();

        // Act
        $instance->setCurrentStep($step = \rand(1, 100));
        $instance->advance();
        $instance->advance(3);

        // Assert
        $this->assertEquals(3, $instance->updates_count); // update has been triggered each time the current step changed

        $this->assertEquals($step + 1 + 3, $instance->getCurrentStep());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getUpdatedAt());

        $this->assertEquals(3, $instance->updates_count); // update has not been re-triggered by getters
    }

    public function testProgressBarMessageAccessors(): void
    {
        // Arrange
        $instance = $this->getInstance();

        // Act
        $instance->setProgressBarMessage($message = 'Processing XYZ...');

        // Assert
        $this->assertEquals(1, $instance->updates_count); // update has been triggered once

        $this->assertEquals($message, $instance->getProgressBarMessage());
        $this->assertGreaterThan($instance->getStartedAt(), $instance->getUpdatedAt());

        $this->assertEquals(1, $instance->updates_count); // update has not been re-triggered by getters
    }

    /**
     * Get an instance of a void implementation of the AbstractProgressIndicator class.
     */
    private function getInstance(): AbstractProgressIndicator
    {
        return new class extends AbstractProgressIndicator {
            public int $updates_count = 0;

            public function addMessage(MessageType $type, string $message): void
            {
                // void
            }

            public function update(): void
            {
                $this->updates_count++;
            }
        };
    }
}
