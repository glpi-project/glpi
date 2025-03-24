<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class TemplateRenderer
{
    /**
     * Templates files rendering environment.
     */
    private ?Environment $files_environment = null;

    /**
     * Inline templates rendering environment.
     */
    private ?Environment $inline_environment = null;

    /**
     * Templates loader instance.
     */
    private LoaderInterface $templates_loader;

    /**
     * Templates environments parameters.
     */
    private array $environment_params;

    /**
     * Twig extensions.
     * @var list<\Twig\Extension\ExtensionInterface>
     */
    private array $extensions;

    /**
     * Twig globals.
     * @var array<string, mixed>
     */
    private array $globals;

    public function __construct(string $rootdir = GLPI_ROOT, string $cachedir = GLPI_CACHE_DIR)
    {
        // Initialize the loader
        $this->templates_loader = new FilesystemLoader($rootdir . '/templates', $rootdir);

        $active_plugins = Plugin::getPlugins();
        foreach ($active_plugins as $plugin_key) {
           // Add a dedicated namespace for each active plugin, so templates would be loadable using
           // `@my_plugin/path/to/template.html.twig` where `my_plugin` is the plugin key and `path/to/template.html.twig`
           // is the path of the template inside the `/templates` directory of the plugin.
            $this->templates_loader->addPath(Plugin::getPhpDir($plugin_key . '/templates'), $plugin_key);
        }

        // Compute environment parameters
        $this->environment_params = [
            'debug'       => $_SESSION['glpi_use_mode'] ?? null === Session::DEBUG_MODE,
            'auto_reload' => GLPI_ENVIRONMENT_TYPE !== GLPI::ENV_PRODUCTION,
        ];

        $tpl_cache_dir = $cachedir . '/templates';
        if (
            (file_exists($tpl_cache_dir) && !is_writable($tpl_cache_dir))
            || (!file_exists($tpl_cache_dir) && !is_writable($cachedir))
        ) {
            trigger_error(sprintf('Cache directory "%s" is not writeable.', $tpl_cache_dir), E_USER_WARNING);
        } else {
            $this->environment_params['cache'] = $tpl_cache_dir;
        }

        // Initialize the extensions
        $this->extensions = [
            // Vendor extensions
            new DebugExtension(),
            new StringExtension(),

            // GLPI extensions
            new ConfigExtension(),
            new SecurityExtension(),
            new DataHelpersExtension(),
            new DocumentExtension(),
            new FrontEndAssetsExtension(),
            new I18nExtension(),
            new IllustrationExtension(),
            new ItemtypeExtension(),
            new PhpExtension(),
            new PluginExtension(),
            new RoutingExtension(),
            new SearchExtension(),
            new SessionExtension(),
            new TeamExtension(),
        ];

        // Initialize the global variables
        $this->globals = [
            '_post'    => $_POST,
            '_get'     => $_GET,
            '_request' => $_REQUEST,
        ];
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
     * Return Twig environment used to handle templates files.
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        if ($this->files_environment === null) {
            $this->files_environment = $this->getNewEnvironment(with_translation_functions: true);
        }
        return $this->files_environment;
    }

    /**
     * Return Twig environment used to handle inlined templates.
     *
     * @return Environment
     */
    private function getInlineEnvironment(): Environment
    {
        if ($this->inline_environment === null) {
            $this->inline_environment = $this->getNewEnvironment(with_translation_functions: false);
        }
        return $this->inline_environment;
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
            return $this->getEnvironment()->load($template)->render($variables);
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
            $this->getEnvironment()->load($template)->display($variables);
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
            return $this->getInlineEnvironment()->createTemplate($template)->render($variables);
        } finally {
            Profiler::getInstance()->stop($template);
        }
    }

    /**
     * Create a new template rendering environment.
     */
    private function getNewEnvironment(bool $with_translation_functions): Environment
    {
        $environment = new Environment(
            $this->templates_loader,
            $this->environment_params
        );

        foreach ($this->extensions as $extension) {
            $environment->addExtension($extension);
        }

        foreach ($this->globals as $name => $value) {
            $environment->addGlobal($name, $value);
        }

        if ($with_translation_functions) {
            $environment->addFunction(new TwigFunction('__', '__'));
            $environment->addFunction(new TwigFunction('_n', '_n'));
            $environment->addFunction(new TwigFunction('_x', '_x'));
            $environment->addFunction(new TwigFunction('_nx', '_nx'));
        }

        return $environment;
    }
}
