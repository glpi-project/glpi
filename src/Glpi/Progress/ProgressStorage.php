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

use Session;

/**
 * @final
 */
class ProgressStorage
{
    public function hasProgress(string $key): bool
    {
        return isset($_SESSION['progress'][$key]) && $_SESSION['progress'][$key] instanceof StoredProgressIndicator;
    }

    public function getCurrentProgress(string $key): StoredProgressIndicator
    {
        if (!$this->hasProgress($key)) {
            throw new \RuntimeException(\sprintf(
                "Cannot find a progress bar for key \"%s\".",
                $key,
            ));
        }

        $progress = $_SESSION['progress'][$key];

        if (!$progress instanceof StoredProgressIndicator) {
            throw new \RuntimeException(\sprintf(
                "Stored progress bar value with key \"%s\" is invalid.",
                $key,
            ));
        }

        return $progress;
    }

    public function save(StoredProgressIndicator $progress): void
    {
        // Mandatory here:
        // If you execute "save($progress)" several times, PHP will send a "Cookie: ..." HTTP header.
        // Use it thousands of times and you will have thousands of "Cookie: ..." HTTP header lines,
        // resulting in a "Header too big" or "File too big" HTTP error response.
        @ini_set('session.use_cookies', 0);

        // Restart the session that may have been closed by a previous call to the current method.
        Session::start();

        $_SESSION['progress'][$progress->getStorageKey()] = $progress;

        // Close the session to release the lock on its storage file.
        // This is required to not block the execution of concurrent requests.
        session_write_close();
    }
}
