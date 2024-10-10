<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Application\ErrorHandler;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\InvalidCsrfHttpException;
use Html;
use Session;
use Symfony\Component\ErrorHandler\Error\OutOfMemoryError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorController extends AbstractController
{
    public function __invoke(Request $request, ?\Throwable $exception = null): Response
    {
        if ($exception === null) {
            return new Response('', 500);
        }

        ErrorHandler::getInstance()->handleException($exception, true);

        $status_code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        return new StreamedResponse(fn() => $this->renderErrorPage($exception), $status_code);
    }

    private function renderErrorPage(\Throwable $exception): void
    {
        $title = __('Error');
        $message = __('An unexpected error has occurred.');

        if ($exception instanceof HttpExceptionInterface) {
            // Default messages.
            switch (true) {
                case ($exception instanceof AccessDeniedHttpException):
                    $title   = __('Access denied');
                    $message = __('You don\'t have permission to perform this action.');
                    break;
                case ($exception instanceof BadRequestHttpException):
                    $title   = __('Invalid request');
                    $message = __('Invalid request parameters.');
                    break;
                case ($exception instanceof InvalidCsrfHttpException):
                    $title   = __('Access denied');
                    $message = __('The action you have requested is not allowed.');
                    break;
                case ($exception instanceof NotFoundHttpException):
                    $title   = __('Item not found');
                    $message = __('The requested item has not been found.');
                    break;
                case ($exception->getStatusCode() >= 400 && $exception->getStatusCode() < 500):
                    // Generic message indicating that the issue is located in the request parameter/context.
                    $title   = __('Invalid request');
                    $message = __('The request is invalid and cannot be processed.');
                    break;
            }

            if (($custom_message = $exception->getMessage()) !== '') {
                // When a custom message exists, it means that it has been manually set in the GLPI code
                // and we expect it to be translated.
                $message = $custom_message;
            }
        }

        if (!Session::getCurrentInterface()) {
            Html::nullHeader($title);
        } else if ($exception instanceof OutOfMemoryError) {
            // A minimal page is displayed as we do not have enough memory available to display the full page.
            Html::simpleHeader($title);
        } else if (Session::getCurrentInterface() === "central") {
            Html::header($title);
        } else if (Session::getCurrentInterface() === "helpdesk") {
            Html::helpHeader($title);
        }

        $trace = '';
        if (
            (
                GLPI_ENVIRONMENT_TYPE === 'development'
                || isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
            )
        ) {
            $trace = sprintf("%s\nIn %s::%s", $exception->getMessage(), $exception->getFile(), $exception->getLine());

            if (!($exception instanceof OutOfMemoryError)) {
                // Note: OutOfMemoryError has no stack trace, we can only get filename and line.
                $trace .= "\n" . $exception->getTraceAsString();
            }
        }

        $renderer = TemplateRenderer::getInstance();
        $renderer->display(
            'display_and_die.html.twig',
            [
                'message' => $message,
                'trace'   => $trace,
                'link'    => Html::getBackUrl(),
            ]
        );

        \Html::footer();
    }
}
