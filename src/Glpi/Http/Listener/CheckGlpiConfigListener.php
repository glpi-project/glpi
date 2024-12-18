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

namespace Glpi\Http\Listener;

use Config;
use DBmysql;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CheckGlpiConfigListener implements EventSubscriberInterface
{
    private static bool $skip_db_checks = false;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public static function skipDbChecks(): bool
    {
        return self::$skip_db_checks;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        /** @var ?DBmysql $DB */
        global $DB;

        if (!$event->isMainRequest()) {
            return;
        }

        $root_doc = $event->getRequest()->getBasePath();
        $request_uri = $event->getRequest()->getRequestUri();

        self::$skip_db_checks = false;
        if ($event->getRequest()->server->has('REQUEST_URI')) {
            if (preg_match('#^' . $root_doc . '/front/(css|locale).php#', $request_uri) === 1) {
                self::$skip_db_checks  = true;
            }

            $no_db_checks_scripts = [
                '#^' . $root_doc . '/install/#i',
            ];
            foreach ($no_db_checks_scripts as $pattern) {
                if (preg_match($pattern, $request_uri) === 1) {
                    self::$skip_db_checks = true;
                    break;
                }
            }
        }

        if (
            self::$skip_db_checks
            || isset($_SESSION['is_installing'])
        ) {
            return;
        }

        $response = null;
        if (!($DB instanceof DBmysql)) {
            $show_link = file_exists($this->projectDir . '/install/install.php');

            $response = $this->getErrorResponse(
                message: sprintf(
                    __('The database configuration file "%s" is missing or is corrupted. You have to either restart the install process, or restore this file.'),
                    GLPI_CONFIG_DIR . '/config_db.php'
                ),
                link_url: $show_link ? $event->getRequest()->getBasePath() . '/install/install.php' : null,
                link_text: $show_link ? __('Go to install page') : null,
            );
        } elseif (!$DB->connected) {
            $response = $this->getErrorResponse(
                message: __('The connection to the SQL server could not be established. Please check your configuration.'),
            );
        } elseif (!Config::isLegacyConfigurationLoaded()) {
            $response = $this->getErrorResponse(
                message: __('Unable to load the GLPI configuration from the database.'),
            );
        }

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    private function getErrorResponse(string $message, ?string $link_url = null, ?string $link_text = null): Response
    {
        $content = TemplateRenderer::getInstance()->render(
            'error_page.html.twig',
            [
                'header_method' => 'nullHeader',
                'page_title'    => _n('Error', 'Errors', 1),
                'message'       => $message,
                'link_url'      => $link_url,
                'link_text'     => $link_text,
            ]
        );

        return new Response($content, 500);
    }
}
