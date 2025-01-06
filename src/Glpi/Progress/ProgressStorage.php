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

final class ProgressStorage
{
    public function startProgress(string $key, int $max = 0): void
    {
        if (!isset($_SESSION['progress'])) {
            $_SESSION['progress'] = [];
        }

        $progress = new SessionProgress($key, $max);

        $this->save($progress);
    }

    public function hasProgress(string $key): bool
    {
        return isset($_SESSION['progress'][$key]) && $_SESSION['progress'][$key] instanceof SessionProgress;
    }

    public function getCurrentProgress(string $key): SessionProgress
    {
        if (!$this->hasProgress($key)) {
            throw new \RuntimeException(\sprintf(
                "Cannot find a progress bar for key \"%s\".",
                $key,
            ));
        }

        Session::start();

        $progress = $_SESSION['progress'][$key];

        session_write_close();

        if (!$progress instanceof SessionProgress) {
            throw new \RuntimeException(\sprintf(
                "Stored progress bar value with key \"%s\" is invalid.",
                $key,
            ));
        }

        return $progress;
    }

    public function deleteProgress(string $key): void
    {
        Session::start();

        unset($_SESSION['progress'][$key]);

        session_write_close();
    }

    public function endProgress(string $key): void
    {
        $progress = $this->getCurrentProgress($key);
        $progress->finish();
        $this->save($progress);
    }

    public function abortProgress(string $key): void
    {
        $progress = $this->getCurrentProgress($key);
        $progress->fail();
        $this->save($progress);
    }

    public function save(SessionProgress $progress): void
    {
        // Mandatory here:
        // If you execute "save($progress)" several times, PHP will send a "Cookie: ..." HTTP header.
        // Use it thousands of times and you will have thousands of "Cookie: ..." HTTP header lines,
        // resulting in a "Header too big" or "File too big" HTTP error response.
        @ini_set('session.use_cookies', 0);

        Session::start();

        $_SESSION['progress'][$progress->key] = $progress;

        session_write_close();
    }
}
