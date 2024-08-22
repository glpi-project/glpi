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

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetModel;
use Glpi\Asset\AssetType;
use Glpi\Controller\DropdownController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Glpi\Controller\DropdownFormController;

final readonly class LegacyDropdownRouteListener implements EventSubscriberInterface
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

        if ($class = $this->findDropdownClass($request)) {
            $is_form = \str_ends_with($request->getPathInfo(), '.form.php');

            $request->attributes->set('_controller', $is_form ? DropdownFormController::class : DropdownController::class);
            $request->attributes->set('class', $class);
        }
    }

    public function findDropdownClass(Request $request): ?string
    {
        $path_info = $request->getPathInfo();

        if ($model_class = $this->findPluginClass($path_info)) {
            return $this->normalizeClass($model_class);
        }

        if ($model_class = $this->findGenericClass($path_info)) {
            return $this->normalizeClass($model_class);
        }

        if ($device_class = $this->findDeviceClass($request)) {
            return $this->normalizeClass($device_class);
        }

        if ($asset_model_class = $this->findAssetModelclass($request)) {
            return $this->normalizeClass($asset_model_class);
        }

        if ($asset_type_class = $this->findAssetTypeclass($request)) {
            return $this->normalizeClass($asset_type_class);
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
            && \is_subclass_of($class, \CommonDropdown::class)
        ) {
            return $class;
        }

        $namespacedClass = \preg_replace_callback('~\\\([a-z])~Uu', static fn($i) => '\\' . \ucfirst($i[1]), 'Glpi\\' . \str_replace('/', '\\', $class));

        if (
            $namespacedClass
            && \class_exists($namespacedClass)
            && \is_subclass_of($namespacedClass, \CommonDropdown::class)
        ) {
            return $namespacedClass;
        }

        return null;
    }

    private function findDeviceClass(Request $request): ?string
    {
        $device_paths = [
            '/front/devicetype.php',
            '/front/devicemodel.php',
            '/front/device.php',
            '/front/devicetype.form.php',
            '/front/devicemodel.form.php',
            '/front/device.form.php',
        ];

        if (!\in_array($request->getPathInfo(), $device_paths, true)) {
            return null;
        }

        $item_type = $request->query->get('itemtype') ?: $request->request->get('itemtype');

        if (!$item_type || !\class_exists($item_type)) {
            throw new \RuntimeException(
                'Missing or incorrect device type called!'
            );
        }

        $class = \getItemForItemtype($item_type) ?: null;
        if (!$class) {
            return null;
        }

        return \get_class($class);
    }

    private function findAssetModelclass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/asset/assetmodel(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null) {
            $asset = AssetModel::getById($id);
            $classname = $asset::class;
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getAssetModelClassName();
            }
        }

        return $classname;
    }

    private function findAssetTypeclass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/asset/assettype(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null) {
            $asset = AssetType::getById($id);
            $classname = $asset::class;
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getAssetTypeClassName();
            }
        }

        return $classname;
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
            throw new \RuntimeException('Could not extract basename from URL to match legacy dropdowns.');
        }

        $basename = $matches['basename'];
        $plugin = $matches['plugin'];
        if (!$this->isPluginActive($plugin)) {
            return null;
        }

        // PluginMyPluginDropdown -> /plugins/myplugin/front/dropdown.php
        $legacy_classname = (new \DbUtils())->fixItemtypeCase(\sprintf('Plugin%s%s', ucfirst($plugin), ucfirst($basename)));
        if (is_a($legacy_classname, \CommonDropdown::class, true)) {
            return $legacy_classname;
        }

        // GlpiPlugin\MyPlugin\Dropdown -> /plugins/myplugin/front/dropdown.php
        $namespaced_classname = (new \DbUtils())->fixItemtypeCase(\sprintf('GlpiPlugin\%s\%s', ucfirst($plugin), ucfirst($basename)));
        if (is_a($namespaced_classname, \CommonDropdown::class, true)) {
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
