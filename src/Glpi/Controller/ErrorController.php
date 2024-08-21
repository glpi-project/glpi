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
use Symfony\Component\ErrorHandler\Error\OutOfMemoryError;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function __invoke(Request $request, ?\Throwable $exception = null): Response
    {
        if (!$exception) {
            return new Response('Error controller was not called properly.', 500);
        }

        ErrorHandler::getInstance()->handleException($exception, true);

        $status_code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        return new StreamedResponse(fn() => $this->renderErrorPage($exception), $status_code);
    }

    private function renderErrorPage(\Throwable $exception): void
    {
        $message = sprintf("%s\nIn %s::%s", $exception->getMessage(), $exception->getFile(), $exception->getLine());

        if ($exception instanceof OutOfMemoryError) {
            /** @var \Laminas\I18n\Translator\TranslatorInterface $TRANSLATE */
            global $TRANSLATE;
            // Disable translation for error pages because it consumes too much memory
            $TRANSLATE = null;

            \Html::simpleHeader('Error');
            // Note: FatalError has no stack trace, we can only get filename and line.
        } else {
            \Html::header('Error');
            if (GLPI_ENVIRONMENT_TYPE === 'development') {
                $message .= "\n" . $this->getTraceAsString($exception);
            }
        }

        if ($exception instanceof HttpExceptionInterface) {
            $message = 'HTTP Error ' . $exception->getStatusCode() . ": " . $message;
        }

        $renderer = TemplateRenderer::getInstance();
        $renderer->display('display_and_die.html.twig', [
            'message' => $message,
            'link'    => \Html::getBackUrl(),
        ]);

        \Html::footer(true);
    }

    private function getTraceAsString(\Throwable $exception): string
    {
        return FlattenException::createFromThrowable($exception)->getTraceAsString();
    }
}
