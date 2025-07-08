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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DependencyInjection\PublicService;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController implements PublicService
{
    /**
     * Helper method to get a response containing the content of a rendered
     * twig template.
     *
     * @param string $view Path to a twig template, which will be looked for in
     * the "templates" folder.
     * For example, "my_template.html.twig" will be resolved to `templates/my_template.html.twig`.
     * For plugins, you must use the "@my_plugin_name" prefix.
     * For example, "@formcreator/my_template.html.twig will resolve to
     * `(plugins|marketplace)/formcreator/templates/my_template.html.twig`.
     * @param array $parameters The expected parameters of the twig template.
     * @param Response $response Optional parameter which serves as the "base"
     * response into which the renderer twig content will be inserted.
     * You should only use it if you need to set some specific headers into the
     * response or to set an http return code different than 200.
     *
     * @return Response
     */
    final protected function render(
        string $view,
        array $parameters = [],
        Response $response = new Response(),
    ): Response {
        $twig = TemplateRenderer::getInstance();

        $content = $twig->render($view, $parameters);

        $response->setContent($content);
        return $response;
    }
}
