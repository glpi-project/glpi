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

namespace Glpi\Controller\Traits;

use Glpi\Progress\StoredProgressIndicator;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Safe\fastcgi_finish_request;
use function Safe\ini_set;
use function Safe\ob_end_clean;
use function Safe\session_write_close;

trait AsyncOperationProgressControllerTrait
{
    /**
     * Return the response to be used by the `ProgressIndicator` js module to be able to follow the operation progress.
     *
     * @param callable $operation_callable  The callable corresponding to the operation to execute.
     */
    protected function getProgressInitResponse(
        StoredProgressIndicator $progress_indicator,
        callable $operation_callable
    ): StreamedResponse {
        ini_set('max_execution_time', '300'); // Allow up to 5 minutes to prevent unexpected timeout
        session_write_close(); // Prevent the session file lock to block the progress check requests

        // Be sure to disable the output buffering.
        // It is necessary to make the `flush()` works as expected.
        while (\ob_get_level() > 0) {
            ob_end_clean();
        }

        return new StreamedResponse(
            function () use ($progress_indicator, $operation_callable) {
                echo $progress_indicator->getStorageKey();

                // Send headers and content.
                // The browser will consider that the response is complete due to the `Connection: close` header
                // and will not have to wait for operation to finish to consider the request as ended.
                \flush();

                if (\function_exists('fastcgi_finish_request')) {
                    // In PHP-FPM context, it indicates to the client (Apache, Nginx, ...)
                    // that the request is finished.
                    fastcgi_finish_request();
                }

                // Prevent the request to be terminated by the client.
                \ignore_user_abort(true);

                $operation_callable();
            },
            headers: [
                'Content-Type'   => 'text/html',
                'Content-Length' => \strlen($progress_indicator->getStorageKey()),
                'Cache-Control'  => 'no-cache,no-store',
                'Pragma'         => 'no-cache',
                'Connection'     => 'close',
            ]
        );
    }
}
