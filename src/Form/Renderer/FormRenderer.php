<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Form\Renderer;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;

/**
 * Utility class used to easily render a form
 * TODO: could be a singleton to hightlight its role as a service and support
 * DI in the future
 */
final class FormRenderer
{
    /**
     * Render a form using the `render_form.html.twig` template
     *
     * @param Form $form Form to be displayed
     *
     * @return string
     */
    public function render(Form $form): string
    {
        // Note: the "form_renderer_controller" must not be loaded here as this code
        // may be called multiple times using AJAX requests, thus trying to load the
        // javascript "GlpiFormRendererController" class multiple times and causing an
        // error.
        // Each pages that call this method through AJAX must instead include the
        // JS controller themselves.

        // Load template
        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/render_form.html.twig', [
            'form' => $form,
        ]);
    }
}
