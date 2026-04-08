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

use Glpi\Application\Environment as GLPIEnvironment;
use Glpi\Debug\Profiler;
use Glpi\Kernel\Kernel;
use Plugin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

/**
 * @since 10.0.0
 */
class TemplateRenderer
{
    private TwigEnvironment $environment;

    public function __construct(string $rootdir = GLPI_ROOT)
    {
        $container = $this->getKernelContainer();

        if ($container !== null && $container->has('twig')) {
            // Kernel is available: use the fully-configured DI twig service.
            // All GLPI extensions, superglobals, ComponentLexer, ComponentRuntime, etc.
            // are already set up by TwigBundle + Autowiring
            $twig = $container->get('twig');
            if (!$twig instanceof TwigEnvironment) {
                throw new \RuntimeException('Twig service is not a TwigEnvironment instance.');
            }
            $this->environment = $twig;
            $this->mountPluginPaths($rootdir);
            return;
        }

        throw new \RuntimeException('Kernel and Twig environment not available.');
    }

    /**
     * Mount plugin template namespaces and test templates onto the DI twig env's loader.
     *
     * The TwigBundle filesystem loader already has the main templates/ path.
     * We extend it at runtime with per-plugin namespaces and (in test mode) the test namespace.
     *
     * Otherwise, the Sf components won't be usable in plugins templates.
     */
    private function mountPluginPaths(string $rootdir): void
    {
        $loader = $this->environment->getLoader();
        if (!$loader instanceof FilesystemLoader) {
            return;
        }

        $active_plugins = Plugin::getPlugins();
        foreach ($active_plugins as $plugin_key) {
            $path = Plugin::getPhpDir($plugin_key . '/templates');
            if ($path !== false && is_dir($path) && !\in_array($path, $loader->getPaths($plugin_key), true)) {
                $loader->addPath($path, $plugin_key);
            }
        }

        $glpi_environment = GLPIEnvironment::get();
        if (
            $glpi_environment->shouldEnableTestResources()
            && \file_exists("$rootdir/tests/templates")
            && !\in_array("$rootdir/tests/templates", $loader->getPaths('test'), true)
        ) {
            $loader->addPath($rootdir . '/tests/templates', 'test');
        }
    }

    private function getKernelContainer(): ?ContainerInterface
    {
        /** @var Kernel|null $kernel */
        global $kernel;

        if (!$kernel instanceof Kernel) {
            return null;
        }

        try {
            $container = $kernel->getContainer();
        } catch (\Throwable) {
            return null;
        }

        return $container;
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

    /**
     * Return Twig environment used to handle templates.
     *
     * @return TwigEnvironment
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
