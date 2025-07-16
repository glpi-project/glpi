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
use LogicException;
use RuntimeException;

use function Safe\fclose;
use function Safe\fflush;
use function Safe\flock;
use function Safe\fopen;
use function Safe\fread;
use function Safe\ftruncate;
use function Safe\fwrite;
use function Safe\session_id;

/**
 * @final
 */
class ProgressStorage
{
    private readonly string $storage_dir;

    /**
     * Indicators related to operations executed by the current PHP process.
     * @var StoredProgressIndicator[]
     */
    private array $current_process_indicators = [];

    /**
     * Callbacks to execute on operation failures.
     * @var array<string, callable>
     */
    private array $failure_callbacks = [];

    public function __construct(string $storage_dir = GLPI_TMP_DIR)
    {
        $this->storage_dir = $storage_dir;
    }

    /**
     * Spawn a progress indicator to be stored in the current storage.
     */
    public function spawnProgressIndicator(?callable $failure_callback = null): StoredProgressIndicator
    {
        $progress_indicator = new StoredProgressIndicator($this->getUniqueStorageKey());
        $progress_indicator->setProgressStorage($this);

        $this->current_process_indicators[] = $progress_indicator;

        $this->save($progress_indicator);

        return $progress_indicator;
    }

    /**
     * Register a failure callback to be called if the operation fails.
     */
    public function registerFailureCallback(string $storage_key, callable $failure_callback): void
    {
        $this->failure_callbacks[$storage_key] = $failure_callback;
    }

    /**
     * Mark all the progress indicators spawned in the current PHP process as failed.
     * This function is expected to be used by the application error handler, to handle both
     * non recoverable errors (execution timeouts or memory errors)
     * and errors that were not caught.
     */
    public function failCurrentProcessIndicators(): void
    {
        foreach ($this->current_process_indicators as $progress_indicator) {
            if ($progress_indicator->isFinished()) {
                // The operation is already finished, it status should not be changed.
                continue;
            }

            $progress_indicator->addMessage(MessageType::Error, __('An unexpected error occurred'));
            $progress_indicator->fail();

            if (\array_key_exists($progress_indicator->getStorageKey(), $this->failure_callbacks)) {
                $this->failure_callbacks[$progress_indicator->getStorageKey()]();
            }
        }
    }

    public function getProgressIndicator(string $storage_key): ?StoredProgressIndicator
    {
        if (!$this->canAccessProgressIndicator($storage_key)) {
            return null;
        }

        $path = $this->getStorageFilePath($storage_key);

        if (!\file_exists($path)) {
            return null;
        }

        $handle = fopen($path, 'rb');

        flock($handle, LOCK_EX); // lock the file

        $file_contents = '';
        while (!\feof($handle)) {
            $file_contents .= fread($handle, 8192);
        }

        flock($handle, LOCK_UN); // unlock the file

        fclose($handle);

        $progress = \unserialize($file_contents);

        if (!$progress instanceof StoredProgressIndicator) {
            throw new RuntimeException(\sprintf('Invalid data stored for key `%s`.', $storage_key));
        }

        return $progress;
    }

    public function save(StoredProgressIndicator $progress_indicator): void
    {
        $storage_key = $progress_indicator->getStorageKey();

        if (!$this->canAccessProgressIndicator($storage_key)) {
            throw new LogicException();
        }

        $path = $this->getStorageFilePath($storage_key);

        $handle = fopen($path, 'c');

        flock($handle, LOCK_EX); // lock the file

        ftruncate($handle, 0);
        fwrite($handle, \serialize($progress_indicator));
        fflush($handle);

        flock($handle, LOCK_UN); // unlock the file

        fclose($handle);
    }

    private function getUniqueStorageKey(): string
    {
        $session_id = session_id();

        do {
            $storage_key = $session_id . \substr(
                \str_shuffle(
                    str_repeat('0123456789abcdef', 10)
                ),
                0,
                16
            );
        } while (\file_exists($this->getStorageFilePath($storage_key)));

        return $storage_key;
    }

    private function canAccessProgressIndicator(string $storage_key): bool
    {
        return \str_starts_with($storage_key, session_id());
    }

    private function getStorageFilePath(string $storage_key): string
    {
        return \sprintf(
            '%s/%s.progress',
            $this->storage_dir,
            $storage_key
        );
    }
}
