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

use Config;
use DBConnection;
use Glpi\Application\Environment;
use Glpi\Error\ErrorUtils;
use Html;
use Session;
use Symfony\Component\ErrorHandler\Error\OutOfMemoryError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorController extends AbstractController
{
    public function __invoke(Request $request, ?Throwable $exception = null): Response
    {
        if ($exception === null) {
            return new Response('', 500);
        }

        $status_code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $title      = _n('Error', 'Errors', 1);
        $message    = __('An unexpected error occurred');
        $link_text  = null;
        $link_url   = null;

        if ($exception instanceof HttpExceptionInterface) {
            // Default messages.
            switch (true) {
                case ($exception instanceof AccessDeniedHttpException || $exception->getStatusCode() === 403):
                    $title   = __('Access denied');
                    $message = __('You don\'t have permission to perform this action.');
                    break;
                case ($exception instanceof BadRequestHttpException || $exception->getStatusCode() === 400):
                    $title   = __('Invalid request');
                    $message = __('Invalid request parameters.');
                    break;
                case ($exception instanceof NotFoundHttpException || $exception->getStatusCode() === 404):
                    $title   = __('Item not found');
                    $message = __('The requested item has not been found.');
                    break;
                case ($exception->getStatusCode() >= 400 && $exception->getStatusCode() < 500):
                    // Generic message indicating that the issue is located in the request parameter/context.
                    $title   = __('Invalid request');
                    $message = __('The request is invalid and cannot be processed.');
                    break;
            }

            if (
                $exception instanceof \Glpi\Exception\Http\HttpExceptionInterface
                && ($custom_message = $exception->getMessageToDisplay()) !== null
            ) {
                $message = $custom_message;
            }
            if ($exception instanceof \Glpi\Exception\Http\HttpExceptionInterface) {
                $link_text = $exception->getLinktext();
                $link_url  = $exception->getLinkUrl();
            }
        }

        $trace = null;
        if (
            (
                Environment::get()->shouldEnableExtraDevAndDebugTools()
                || isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
            )
        ) {
            $trace = sprintf(
                "%s\nIn %s(%s)",
                $this->cleanPaths($exception->getMessage() ?: $exception::class),
                $this->cleanPaths($exception->getFile()),
                $exception->getLine()
            );

            if (!($exception instanceof OutOfMemoryError)) {
                // Note: OutOfMemoryError has no stack trace, we can only get filename and line.
                $trace .= "\n" . $this->cleanPaths($exception->getTraceAsString());
            }

            $current = $exception;
            $depth   = 0;
            while ($depth < 10 && $previous = $current->getPrevious()) {
                $trace .= sprintf(
                    "\n\nPrevious: %s\nIn %s(%s)",
                    $this->cleanPaths($previous->getMessage() ?: $previous::class),
                    $this->cleanPaths($previous->getFile()),
                    $previous->getLine()
                );
                $trace .= "\n" . $this->cleanPaths($previous->getTraceAsString());

                $current = $previous;
                $depth++;
            }
        }

        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse(
                data: [
                    'error'   => true,
                    'title'   => $title,
                    'message' => $message,
                    'trace'   => $trace,
                ],
                status: $status_code
            );
        }

        $error_block_params = [
            'message'   => $message,
            'trace'     => $trace,
            'link_url'  => null,
            'link_text' => null,
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render(
                'error_block.html.twig',
                $error_block_params,
                new Response(status: $status_code)
            );
        }

        $header_method = match (true) {
            // A minimal page is displayed as we do not have enough memory available to display the full page.
            $exception instanceof OutOfMemoryError => 'simpleHeader',

            // Only the error message should be shown if the DB is ot available or the config not loaded.
            // Trying to display a full header is not possible.
            !DBConnection::isDbAvailable() || !Config::isLegacyConfigurationLoaded() => 'nullHeader',

            Session::getCurrentInterface() === 'central' => 'header',
            Session::getCurrentInterface() === 'helpdesk' => 'helpHeader',
            default => 'nullHeader',
        };

        return $this->render(
            'error_page.html.twig',
            [
                'header_method' => $header_method,
                'page_title'    => $title,
                'link_url'      => $link_url ?? Html::getBackUrl(),
                'link_text'     => $link_text ?? __('Return to previous page'),
            ] + $error_block_params,
            new Response(status: $status_code)
        );
    }

    private function cleanPaths(string $message): string
    {
        return ErrorUtils::cleanPaths($message);
    }
}
