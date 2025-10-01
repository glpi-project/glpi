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

namespace Glpi\Controller;

use Glpi\DependencyInjection\PublicService;
use Glpi\Http\HeaderlessStreamedResponse;
use RuntimeException;
use Safe\Exceptions\OutcontrolException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function Safe\ob_get_clean;
use function Safe\ob_start;

final class LegacyFileLoadController implements PublicService
{
    public const REQUEST_FILE_KEY = '_glpi_file_to_load';

    private ?Request $request = null;

    public function __invoke(Request $request): Response
    {
        $this->request = $request;

        $target_file = $request->attributes->getString(self::REQUEST_FILE_KEY);

        if (!$target_file) {
            throw new RuntimeException('Cannot load legacy controller without specifying a file to load.');
        }

        ob_start();
        $response = require($target_file);
        try {
            $content = ob_get_clean();
        } catch (OutcontrolException) {
            \trigger_error(
                sprintf('The output buffer has been unexpectedly closed in `%s`.', $target_file),
                E_USER_WARNING
            );
            $content = '';
        }

        if ($response instanceof Response) {
            // The legacy file contains a return value that corresponds to a valid Symfony response.
            // This response is returned and any output is discarded.

            if ($content !== '') {
                \trigger_error(
                    sprintf('Unexpected output detected in `%s`.', $target_file),
                    E_USER_WARNING
                );
            }

            return $response;
        }

        if (\headers_sent()) {
            // Headers are already sent, so we have to use a streamed response without headers.
            // This may happen when `flush()`/`ob_flush()`/`ob_clean()`/`ob_end_clean()` functions are used
            // in the legacy script. The script should be fixed to prevent this.

            \trigger_error(
                sprintf('Unexpected output detected in `%s`.', $target_file),
                E_USER_WARNING
            );

            // @phpstan-ignore-next-line method.deprecatedClass (deprecated usage is intended for backward compatibility, this BC layer will be removed in the next GLPI major version)
            return new HeaderlessStreamedResponse(function () use ($content) {
                echo $content;
            });
        }

        // Extract already defined headers to set them in the Symfony response.
        // This is required as Symfony will set default values for some of them if we do not provide them.
        // see `Symfony\Component\HttpFoundation\ResponseHeaderBag`
        $headers = [];
        foreach (\headers_list() as $header_line) {
            [$header_name, $header_value] = \explode(':', $header_line, 2);

            $header_name = \trim($header_name);
            $header_value = \trim($header_value);

            if (!\array_key_exists($header_name, $headers)) {
                $headers[$header_name] = [];
            }

            $headers[$header_name][] = $header_value;
        }
        \header_remove();

        return new Response($content, status: \http_response_code(), headers: $headers);
    }

    /**
     * Method used in `front/dropdown.common.form.php` to get the current request.
     *
     * @phpstan-ignore method.unused
     */
    private function getRequest(): Request
    {
        if ($this->request === null) {
            throw new RuntimeException(\sprintf(
                'Could not find Request in "%s" controller. Did you forget to call "%s"?',
                self::class,
                '__invoke',
            ));
        }

        return $this->request;
    }
}
