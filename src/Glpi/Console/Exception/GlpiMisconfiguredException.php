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

namespace Glpi\Console\Exception;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\ExceptionWithResponseInterface;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GlpiMisconfiguredException extends \RuntimeException implements ExceptionWithResponseInterface
{
    public function __construct()
    {
        $message = "GLPI seems to not be configured properly.\n" .
            sprintf('Database configuration file "%s" is missing or is corrupted.', GLPI_CONFIG_DIR . '/config_db.php') . "\n" .
            "You have to either restart the install process, either restore this file.\n";

        parent::__construct($message, 1);
    }

    public function getResponse(): Response
    {
        return new StreamedResponse($this->display(...), 500);
    }

    private function display(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (isCommandLine()) {
            throw new \RuntimeException(\sprintf('The "%s::%s" method should not be used in a CLI context.', self::class, 'getResponse'));
        }

        // Prevent inclusion of debug information in footer, as they are based on vars that are not initialized here.
        $debug_mode = $_SESSION['glpi_use_mode'];
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        Html::nullHeader('Missing configuration', $CFG_GLPI["root_doc"]);
        $twig_params = [
            'config_db' => GLPI_CONFIG_DIR . '/config_db.php',
            'install_exists' => file_exists(GLPI_ROOT . '/install/install.php'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="container-fluid mb-4">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-sm-12">
                        <h2>GLPI seems to not be configured properly.</h2>
                        <p class="mt-2 mb-n2 alert alert-warning">
                            Database configuration file "{{ config_db }}" is missing or is corrupted.
                            You have to either restart the install process, either restore this file.
                            <br />
                            <br />
                            {% if install_exists %}
                                <a class="btn btn-primary" href="{{ path('install/install.php') }}">Go to install page</a>
                            {% endif %}
                        </p>
                    </div>
                </div>
            </div>
        TWIG, $twig_params);
        Html::nullFooter();
        $_SESSION['glpi_use_mode'] = $debug_mode;
    }
}
