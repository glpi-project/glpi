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

namespace Glpi\Application\View;

use GLPI;
use Glpi\Application\ErrorHandler;
use Glpi\Application\View\Extension\ConfigExtension;
use Glpi\Application\View\Extension\DataHelpersExtension;
use Glpi\Application\View\Extension\DocumentExtension;
use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Glpi\Application\View\Extension\I18nExtension;
use Glpi\Application\View\Extension\IllustrationExtension;
use Glpi\Application\View\Extension\ItemtypeExtension;
use Glpi\Application\View\Extension\PhpExtension;
use Glpi\Application\View\Extension\PluginExtension;
use Glpi\Application\View\Extension\RoutingExtension;
use Glpi\Application\View\Extension\SearchExtension;
use Glpi\Application\View\Extension\SecurityExtension;
use Glpi\Application\View\Extension\SessionExtension;
use Glpi\Application\View\Extension\TeamExtension;
use Glpi\Debug\Profiler;
use Plugin;
use Session;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

/**
 * @since 10.0.0
 */
class TemplateRenderer
{
    private static ?Environment $environment = null;

    public function __construct()
    {
        if (self::$environment) {
            return;
        }

        throw new \RuntimeException('Must not create TemplateRenderer without Dependency Injection');
    }

    /**
     * Return singleton instance of self.
     *
     * @return TemplateRenderer
     */
    public static function getInstance(): TemplateRenderer
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public static function setEnvironment(Environment $environment): void
    {
        self::$environment = $environment;
    }

    /**
     * Return Twig environment used to handle templates.
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        $environment = self::$environment;

        if (!$environment) {
            throw new \RuntimeException('Twig environment was not properly set in ' . self::class);
        }

        return $environment;
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
            return self::getEnvironment()->load($template)->render($variables);
        } catch (\Twig\Error\Error $e) {
            ErrorHandler::getInstance()->handleTwigError($e);
        } finally {
            Profiler::getInstance()->stop($template);
        }
        return '';
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
            self::getEnvironment()->load($template)->display($variables);
        } catch (\Twig\Error\Error $e) {
            ErrorHandler::getInstance()->handleTwigError($e);
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
            return self::getEnvironment()->createTemplate($template)->render($variables);
        } catch (\Twig\Error\Error $e) {
            ErrorHandler::getInstance()->handleTwigError($e);
        } finally {
            Profiler::getInstance()->stop($template);
        }
        return '';
    }
}
