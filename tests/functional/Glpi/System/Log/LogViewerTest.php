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

namespace tests\units\Glpi\System\Log;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\System\Log\LogViewer;
use Glpi\Tests\DbTestCase;

class LogViewerTest extends DbTestCase
{
    public function testAjaxRefreshRequiresSystemLogsRight(): void
    {
        // Arrange: a user that can read the items history but not the system logs
        $this->login();
        $_SESSION['glpiactiveprofile']['logs'] = READ;
        $_SESSION['glpiactiveprofile'][LogViewer::$rightname] = 0;

        $_POST['action']   = 'refresh_log_file';
        $_POST['filepath'] = 'php-errors.log';

        // Act/Assert: the log file content is not returned
        $this->expectException(AccessDeniedHttpException::class);
        try {
            ob_start();
            include GLPI_ROOT . '/ajax/logviewer.php';
        } finally {
            ob_end_clean();
            unset($_POST['action'], $_POST['filepath']);
        }
    }
}
