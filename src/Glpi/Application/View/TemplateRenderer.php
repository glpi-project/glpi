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
use Glpi\Application\View\Extension\ConfigExtension;
use Glpi\Application\View\Extension\DataHelpersExtension;
use Glpi\Application\View\Extension\DocumentExtension;
use Glpi\Application\View\Extension\FrontEndAssetsExtension;
use Glpi\Application\View\Extension\HtmlExtension;
use Glpi\Application\View\Extension\I18nExtension;
use Glpi\Application\View\Extension\IllustrationExtension;
use Glpi\Application\View\Extension\ItemtypeExtension;
use Glpi\Application\View\Extension\PhpExtension;
use Glpi\Application\View\Extension\PluginExtension;
use Glpi\Application\View\Extension\RoutingExtension;
use Glpi\Application\View\Extension\SearchExtension;
use Glpi\Application\View\Extension\SecurityExtension;
use Glpi\Application\View\Extension\SessionExtension;
use Glpi\Application\View\Extension\SuperGlobalsExtension;
use Glpi\Application\View\Extension\TeamExtension;
use Glpi\Debug\Profiler;
use Glpi\Kernel\Kernel;
use Plugin;
use Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

/**
 * @since 10.0.0
 */
class TemplateRenderer
{
    private TwigEnvironment $environment;

    public function __construct(string $rootdir = GLPI_ROOT, ?string $cachedir = null)
    {
        $container = $this->getKernelContainer();

        if ($container !== null && $container->has('twig')) {
            // Kernel is available: use the fully-configured DI twig service.
            // All GLPI extensions, superglobals, ComponentLexer, ComponentRuntime, etc.
            // are already set up by TwigBundle + Autowiring
            $this->environment = $container->get('twig');
            $this->mountPluginPaths($rootdir);
            return;
        }

        // Fallback: CLI / install / upgrade path — build env manually.
        $this->buildStandaloneEnvironment($rootdir, $cachedir);
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
            if (is_dir($path) && !\in_array($path, $loader->getPaths($plugin_key), true)) {
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

    /**
     * Build a standalone Twig environment for contexts where the DI kernel is not available
     * (CLI tools, install/upgrade scripts, early bootstrap).
     */
    private function buildStandaloneEnvironment(string $rootdir, ?string $cachedir): void
    {
        if ($cachedir === null) {
            $cachedir = Kernel::getCacheRootDir();
        }

        $loader = new FilesystemLoader($rootdir . '/templates', $rootdir);

        $glpi_environment = GLPIEnvironment::get();
        $env_params = [
            'debug' => $glpi_environment->shouldEnableExtraDevAndDebugTools() || ($_SESSION['glpi_use_mode'] ?? null) === Session::DEBUG_MODE,
            'auto_reload' => $glpi_environment->shouldExpectResourcesToChange(),
            'strict_variables' => GLPI_STRICT_ENV,
        ];

        $tpl_cachedir = $cachedir . '/templates';
        if (
            (file_exists($tpl_cachedir) && !is_writable($tpl_cachedir))
            || (!file_exists($tpl_cachedir) && !is_writable($cachedir))
        ) {
            trigger_error(sprintf('Cache directory "%s" is not writeable.', $tpl_cachedir), E_USER_WARNING);
        } else {
            $env_params['cache'] = $tpl_cachedir;
        }

        $this->environment = new TwigEnvironment($loader, $env_params);

        $this->mountPluginPaths($rootdir);

        // Vendor extensions
        $this->environment->addExtension(new DebugExtension());
        $this->environment->addExtension(new StringExtension());
        // GLPI extensions
        $this->environment->addExtension(new ConfigExtension());
        $this->environment->addExtension(new SecurityExtension());
        $this->environment->addExtension(new DataHelpersExtension());
        $this->environment->addExtension(new DocumentExtension());
        $this->environment->addExtension(new FrontEndAssetsExtension());
        $this->environment->addExtension(new HtmlExtension());
        $this->environment->addExtension(new I18nExtension());
        $this->environment->addExtension(new IllustrationExtension());
        $this->environment->addExtension(new ItemtypeExtension());
        $this->environment->addExtension(new PhpExtension());
        $this->environment->addExtension(new PluginExtension());
        $this->environment->addExtension(new RoutingExtension());
        $this->environment->addExtension(new SearchExtension());
        $this->environment->addExtension(new SessionExtension());
        $this->environment->addExtension(new TeamExtension());

        // Superglobals
        $this->environment->addExtension(new SuperGlobalsExtension());
    }

    private function getKernelContainer(): ?ContainerInterface
    {
        global $kernel;

        if (!$kernel instanceof Kernel) {
            return null;
        }

        try {
            $container = $kernel->getContainer();
        } catch (\Throwable) {
            return null;
        }

        return $container instanceof ContainerInterface ? $container : null;
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
