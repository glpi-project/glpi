<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Html;

/**
 * Utility class used to easily render a form
 */
final class FormRenderer
{
    /**
     * Singleton instance.
     */
    protected static ?FormRenderer $instance = null;

    /**
     * Singleton constructor.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance.
     *
     * @return FormRenderer
     */
    public static function getInstance(): FormRenderer
    {
        if (!isset(static::$instance)) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Render the given form.
     *
     * @param Form $form
     *
     * @return string
     */
    public function render(Form $form): string
    {
        // Load JS controller
        $html = Html::script("js/form_renderer_controller.js");

        // Load template
        $twig = TemplateRenderer::getInstance();
        $html .= $twig->render('pages/form_renderer.html.twig', [
            'form' => $form,
        ]);

        return $html;
    }
}
