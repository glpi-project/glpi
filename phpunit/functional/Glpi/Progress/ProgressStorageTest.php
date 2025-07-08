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

namespace tests\units\Glpi\Progress;

use Glpi\Progress\ProgressStorage;
use Glpi\Progress\StoredProgressIndicator;
use GLPITestCase;
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
        $this->assertEquals(\serialize($progress_indicator), $stored_content);
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

        // Act
        $storage->save($progress_indicator);
        $fetched_progress_indicator = $storage->getProgressIndicator($storage_key);
        $fetched_progress_indicator->setProgressStorage($storage); // to simplify comparison, this would be the only diff

        // Assert
        $this->assertEquals($progress_indicator, $fetched_progress_indicator);
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
                ->setContent(\serialize($progress_indicator));
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
