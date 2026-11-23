<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Application\View;

use Glpi\Debug\Profiler;
use Glpi\DependencyInjection\PublicService;
use Glpi\Kernel\Kernel;
use Twig\Environment as TwigEnvironment;

/**
 * @since 10.0.0
 */
class TemplateRenderer implements PublicService
{
    private TwigEnvironment $environment;

    public function __construct(TwigEnvironment $twig)
    {
        $this->environment = $twig;
    }

    /**
     * Return singleton instance of self.
     *
     * @return TemplateRenderer
     */
    public static function getInstance(): TemplateRenderer
    {
        /** @var Kernel $kernel */
        global $kernel;

        $renderer = $kernel->getContainer()->get(static::class);
        if (!$renderer instanceof TemplateRenderer) {
            throw new \RuntimeException('TemplateRenderer is expected to be registered as a service.');
        }

        return $renderer;
    }

    /**
     * Return Twig environment used to handle templates.
     *
     * @return TwigEnvironment
     * @internal
     */
    public function getEnvironment(): TwigEnvironment
    {
        return $this->environment;
    }

    /**
     * Renders a template.
     *
     * @param string $template
     * @param array  $variables
     *
     * @return string
     */
    public function render(string $template, array $variables = []): string
    {
        try {
            Profiler::getInstance()->start($template, Profiler::CATEGORY_TWIG);
            return $this->environment->load($template)->render($variables);
        } finally {
            Profiler::getInstance()->stop($template);
        }
    }

    /**
     * Displays a template.
     *
     * @param string $template
     * @param array  $variables
     *
     * @return void
     */
    public function display(string $template, array $variables = []): void
    {
        try {
            Profiler::getInstance()->start($template, Profiler::CATEGORY_TWIG);
            $this->environment->load($template)->display($variables);
        } finally {
            Profiler::getInstance()->stop($template);
        }
    }

    /**
     * Renders a template from a string.
     *
     * @param string $template
     * @param array  $variables
     *
     * @return string
     */
    public function renderFromStringTemplate(string $template, array $variables = []): string
    {
        try {
            Profiler::getInstance()->start($template, Profiler::CATEGORY_TWIG);
            return $this->environment->createTemplate($template)->render($variables);
        } finally {
            Profiler::getInstance()->stop($template);
        }
    }
}
