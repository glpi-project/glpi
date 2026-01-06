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

namespace tests\units\Glpi\Progress;

use Glpi\Message\MessageType;
use Glpi\Progress\ProgressStorage;
use Glpi\Progress\StoredProgressIndicator;
use Glpi\Tests\GLPITestCase;
use org\bovigo\vfs\vfsStream;

class ProgressStorageTest extends GLPITestCase
{
    public function testSpawnProgressIndicator(): void
    {
        // Arrange
        vfsStream::setup(
            'glpi',
            null,
            [
                'files' => [
                    '_tmp' => [
                    ],
                ],
            ]
        );
        $storage = new ProgressStorage(vfsStream::url('glpi/files/_tmp'));

        // Act
        $progress_indicator = $storage->spawnProgressIndicator();

        // Assert
        $this->assertInstanceOf(StoredProgressIndicator::class, $progress_indicator);
        $expected_file_path = vfsStream::url('glpi/files/_tmp/' . $progress_indicator->getStorageKey() . '.progress');
        $this->assertFileExists($expected_file_path);

        $stored_content = \file_get_contents($expected_file_path);
        $this->assertEquals(\Safe\json_encode($progress_indicator), $stored_content);
    }

    public function testSaveAndGetProgressIndicator(): void
    {
        // Arrange
        $storage_key = \session_id() . 'abcdef0123456789';

        vfsStream::setup(
            'glpi',
            null,
            [
                'files' => [
                    '_tmp' => [
                    ],
                ],
            ]
        );
        $storage = new ProgressStorage(vfsStream::url('glpi/files/_tmp'));
        $progress_indicator = new StoredProgressIndicator($storage_key);
        $progress_indicator->setProgressStorage($storage);
        $progress_indicator->setProgressBarMessage('Doing something...');
        $progress_indicator->setCurrentStep(10);
        $progress_indicator->setMaxSteps(84);

        // Act
        $storage->save($progress_indicator);
        $fetched_progress_indicator = $storage->getProgressIndicator($storage_key);

        // Assert
        $this->assertEquals($progress_indicator->getStartedAt()->format('c'), $fetched_progress_indicator->getStartedAt()->format('c'));
        $this->assertEquals($progress_indicator->getUpdatedAt()->format('c'), $fetched_progress_indicator->getUpdatedAt()->format('c'));
        $this->assertNull($fetched_progress_indicator->getEndedAt());
        $this->assertFalse($fetched_progress_indicator->isFinished());
        $this->assertFalse($fetched_progress_indicator->hasFailed());
        $this->assertEquals(10, $fetched_progress_indicator->getCurrentStep());
        $this->assertEquals(84, $fetched_progress_indicator->getMaxSteps());
        $this->assertEquals('Doing something...', $fetched_progress_indicator->getProgressBarMessage());
        $this->assertEquals([], $fetched_progress_indicator->getMessages());

        // Arrange again
        $progress_indicator->addMessage(MessageType::Notice, "I don't say Blah blah blah");
        $progress_indicator->addMessage(MessageType::Error, 'Something goes wrong');
        $progress_indicator->fail();
        $progress_indicator->setProgressBarMessage('');

        // Act again
        $storage->save($progress_indicator);
        $fetched_progress_indicator = $storage->getProgressIndicator($storage_key);

        // Assert again
        $this->assertEquals($progress_indicator->getEndedAt()->format('c'), $fetched_progress_indicator->getEndedAt()->format('c'));
        $this->assertTrue($fetched_progress_indicator->isFinished());
        $this->assertTrue($fetched_progress_indicator->hasFailed());
        $this->assertEquals(10, $fetched_progress_indicator->getCurrentStep());
        $this->assertEquals(84, $fetched_progress_indicator->getMaxSteps());
        $this->assertEquals('', $fetched_progress_indicator->getProgressBarMessage());
        $this->assertEquals(
            [
                ['type' => MessageType::Notice, 'message' => "I don't say Blah blah blah"],
                ['type' => MessageType::Error, 'message' => 'Something goes wrong'],
            ],
            $fetched_progress_indicator->getMessages()
        );
    }

    public function testGetProgressIndicatorThatDoesNotExists(): void
    {
        // Arrange
        $storage_key = \session_id() . 'abcdef0123456789';

        vfsStream::setup(
            'glpi',
            null,
            [
                'files' => [
                    '_tmp' => [
                    ],
                ],
            ]
        );
        $storage = new ProgressStorage(vfsStream::url('glpi/files/_tmp'));

        // Act
        $fetched_progress_indicator = $storage->getProgressIndicator($storage_key);

        // Assert
        $this->assertNull($fetched_progress_indicator);
    }

    public function testGetProgressIndicatorFromAnotherSession(): void
    {
        // Arrange
        $another_sess_id = '55066aedc96e8e49533b45362d124840';
        \assert($another_sess_id !== \session_id());

        $storage_key_suffix = 'abcdef0123456789';

        $structure = vfsStream::setup(
            'glpi',
            null,
            [
                'files' => [
                    '_tmp' => [
                    ],
                ],
            ]
        );
        $storage = new ProgressStorage(vfsStream::url('glpi/files/_tmp'));

        foreach ([$another_sess_id, \session_id()] as $prefix) {
            $progress_indicator = new StoredProgressIndicator($prefix . $storage_key_suffix);
            $progress_indicator->setProgressStorage($storage);

            vfsStream::newFile($prefix . $storage_key_suffix . '.progress')
                ->at($structure->getChild('files/_tmp'))
                ->setContent(\json_encode($progress_indicator));
        }

        // Act
        $another_session_progress_indicator = $storage->getProgressIndicator($another_sess_id . $storage_key_suffix);
        $current_session_progress_indicator = $storage->getProgressIndicator(\session_id() . $storage_key_suffix);

        // Assert
        $this->assertNull($another_session_progress_indicator, 'Progress indicator from another session should not be readable.');
        $this->assertInstanceOf(StoredProgressIndicator::class, $current_session_progress_indicator);
    }

    public function testSaveProgressIndicatorFromAnotherSession(): void
    {
        // Arrange
        $another_sess_id = '55066aedc96e8e49533b45362d124840';
        \assert($another_sess_id !== \session_id());

        $storage_key = $another_sess_id . 'abcdef0123456789';

        vfsStream::setup(
            'glpi',
            null,
            [
                'files' => [
                    '_tmp' => [
                    ],
                ],
            ]
        );
        $storage = new ProgressStorage(vfsStream::url('glpi/files/_tmp'));
        $progress_indicator = new StoredProgressIndicator($storage_key);
        $progress_indicator->setProgressStorage($storage);

        // Act
        $exception = null;
        try {
            $storage->save($progress_indicator);
        } catch (\Throwable $e) {
            $exception = $e;
        }
        $fetched_progress_indicator = $storage->getProgressIndicator($storage_key);

        // Assert
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertNull($fetched_progress_indicator, 'Progress indicator from another session should not be readable.');

        $expected_file_path = vfsStream::url('glpi/files/_tmp/' . $progress_indicator->getStorageKey() . '.progress');
        $this->assertFileDoesNotExist($expected_file_path);
    }
}
