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

namespace Glpi\Http;

use Glpi\Controller\GenericListController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LegacySearchRouteListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::LEGACY_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (
            $request->attributes->get('_controller') !== null
            || $event->getResponse() !== null
        ) {
            // A controller or a response has already been defined for this request, do not override them.
            return;
        }

        if ($class = $this->findDbClass($request)) {
            $is_form = \str_ends_with($request->getPathInfo(), '.form.php');

            // @TODO: handle forms too.
            $request->attributes->set('_controller', $is_form ? null : GenericListController::class);
            $request->attributes->set('class', $class);
        }
    }

    public function findDbClass(Request $request): ?string
    {
        $path_info = $request->getPathInfo();

        if ($model_class = $this->findPluginClass($path_info)) {
            return $this->normalizeClass($model_class);
        }

        if ($model_class = $this->findGenericClass($path_info)) {
            return $this->normalizeClass($model_class);
        }

        return null;
    }

    private function findGenericClass(string $path_info): ?string
    {
        $path_regex = '~^/front/(?<basename>.+)(?<form>\.form)?\.php~isUu';

        $matches = [];
        if (!\preg_match($path_regex, $path_info, $matches)) {
            return null;
        }

        if (!$matches['basename']) {
            throw new \RuntimeException('Could not extract basename from URL to match legacy dropdowns.');
        }

        $basename = $matches['basename'];

        $class = (new \DbUtils())->fixItemtypeCase($basename);

        if (
            $class
            && \class_exists($class)
            && \is_subclass_of($class, \CommonDBTM::class)
        ) {
            return $class;
        }

        $namespacedClass = \preg_replace_callback('~\\\([a-z])~Uu', static fn($i) => '\\' . \ucfirst($i[1]), 'Glpi\\' . \str_replace('/', '\\', $class));

        if (
            $namespacedClass
            && \class_exists($namespacedClass)
            && \is_subclass_of($namespacedClass, \CommonDBTM::class)
        ) {
            return $namespacedClass;
        }

        return null;
    }

    private function normalizeClass(string $class): string
    {
        if (!\class_exists($class)) {
            throw new \RuntimeException('Class "$class" does not exist.');
        }

        return (new \ReflectionClass($class))->getName();
    }

    private function findPluginClass(string $path_info): ?string
    {
        $path_regex = '~^/(plugins|marketplace)/(?<plugin>[^/]+)/front/(?<basename>.+)(?<form>\.form)?.php~isUu';

        $matches = [];
        if (\preg_match($path_regex, $path_info, $matches) !== 1) {
            return null;
        }

        if (!$matches['basename']) {
            throw new \RuntimeException('Could not extract basename from URL to match legacy database classes.');
        }

        $basename = $matches['basename'];
        $plugin = $matches['plugin'];
        if (!$this->isPluginActive($plugin)) {
            return null;
        }

        // PluginMyPluginMyobject -> /plugins/myplugin/front/myobject.php
        $legacy_classname = (new \DbUtils())->fixItemtypeCase(\sprintf('Plugin%s%s', ucfirst($plugin), ucfirst($basename)));
        if (is_a($legacy_classname, \CommonDBTM::class, true)) {
            return $legacy_classname;
        }

        // GlpiPlugin\MyPlugin\Myobject -> /plugins/myplugin/front/myobject.php
        $namespaced_classname = (new \DbUtils())->fixItemtypeCase(\sprintf('GlpiPlugin\%s\%s', ucfirst($plugin), ucfirst($basename)));
        if (is_a($namespaced_classname, \CommonDBTM::class, true)) {
            return $namespaced_classname;
        }

        return null;
    }

    private function isPluginActive(string $plugin_name): bool
    {
        $plugin = new \Plugin();

        return $plugin->isInstalled($plugin_name) && $plugin->isActivated($plugin_name);
    }
}
