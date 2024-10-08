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

namespace Glpi\Twig;

use Glpi\DependencyInjection\PublicService;
use Plugin;
use Session;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

final readonly class TwigEnvironmentConfigurator implements PublicService
{
    public function __construct(
        #[Autowire(param: 'kernel.cache_dir')] private string $cacheDir,
        #[Autowire(param: 'kernel.project_dir')] private string $projectDir,
        private RequestStack $requestStack,
    ) {
    }

    public function configure(Environment $twig): void
    {
        $this->configureDebug($twig);
        $this->configureCache($twig);
        $this->configureExtensions($twig);
        $this->addPluginTemplatesLoader($twig);
        $this->configureSuperglobals($twig);
    }

    public function addPluginTemplatesLoader(Environment $twig): void
    {
        $twig_loader = $twig->getLoader();
        if (!$twig_loader instanceof ChainLoader) {
            // If Twig is configured differently, it will still enforce a ChainLoader,
            // allowing us to add more loaders at runtime just for Plugins.
            $overriden_loader = new ChainLoader();
            $overriden_loader->addLoader($twig_loader);
            $twig_loader = $overriden_loader;
        }

        $active_plugins = Plugin::getPlugins();
        foreach ($active_plugins as $plugin_key) {
            // Add a dedicated namespace for each active plugin, so templates would be loadable using
            // `@my_plugin/path/to/template.html.twig` where `my_plugin` is the plugin key and `path/to/template.html.twig`
            // is the path of the template inside the `/templates` directory of the plugin.
            $plugin_path = Plugin::getPhpDir($plugin_key . '/templates');
            $loader = new FilesystemLoader([\str_replace($this->projectDir, '', $plugin_path)], $plugin_path);
            $loader->addPath($plugin_path, $plugin_key);
            $twig_loader->addLoader($loader);
        }

        $twig->setLoader($twig_loader);
    }

    public function configureDebug(Environment $twig): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest && $currentRequest->hasSession()) {
            // Forwards-compatible with Symfony's Session
            $session = $currentRequest->getSession()->all();
        } else {
            $session = $_SESSION ?? [];
        }

        if (($session['glpi_use_mode'] ?? null) === Session::DEBUG_MODE) {
            $twig->enableDebug();
        } else {
            $twig->disableDebug();
        }
    }

    private function configureCache(Environment $twig): void
    {
        $tpl_cachedir = $this->cacheDir . '/templates';

        if (
            (\file_exists($tpl_cachedir) && !\is_writable($tpl_cachedir))
            || (!\file_exists($tpl_cachedir) && !\is_writable($this->cacheDir))
        ) {
            \trigger_error(sprintf('Cache directory "%s" is not writeable.', $tpl_cachedir), \E_USER_WARNING);
            return;
        }

        $twig->setCache($tpl_cachedir);
    }

    private function configureSuperglobals(Environment $twig): void
    {
        $twig->addGlobal('_post', $_POST);
        $twig->addGlobal('_get', $_GET);
        $twig->addGlobal('_request', $_REQUEST);
    }

    private function configureExtensions(Environment $twig): void
    {
        $twig->addExtension(new StringExtension());
    }
}
