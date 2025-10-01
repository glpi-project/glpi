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

namespace tests\units\Glpi\Api\HL\Middleware;

use Glpi\Api\HL\Middleware\DebugRequestMiddleware;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Http\Request;

class DebugRequestMiddlewareTest extends \DbTestCase
{
    public function testDebugModeEnabled()
    {
        $middleware = new DebugRequestMiddleware();
        $input = new MiddlewareInput(
            new Request('GET', '/', [
                'X-Debug-Mode' => 'true',
            ]),
            new RoutePath('', '', '', ['GET'], 1, Route::SECURITY_AUTHENTICATED, ''),
            null
        );
        // User not authenticated, so should fail permission check
        $middleware->process(
            $input,
            function () {
                $this->assertEquals(\Session::NORMAL_MODE, $_SESSION['glpi_use_mode']);
            }
        );

        $this->login('tech', 'tech');
        // This user doesn't have permission to use debug mode
        $middleware->process(
            $input,
            function () {
                $this->assertEquals(\Session::NORMAL_MODE, $_SESSION['glpi_use_mode']);
            }
        );

        $this->login();
        // This user has permission to use debug mode
        $middleware->process(
            $input,
            function () {
                $this->assertEquals(\Session::DEBUG_MODE, $_SESSION['glpi_use_mode']);
            }
        );
    }
}
