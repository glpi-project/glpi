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

use CommonDevice;
use CommonDropdown;
use CommonGLPI;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetModel;
use Glpi\Asset\AssetType;
use Glpi\Controller\GenericFormController;
use Glpi\Controller\GenericListController;
use Glpi\Controller\DropdownFormController;
use Glpi\Dropdown\Dropdown;
use Glpi\Dropdown\DropdownDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LegacyItemtypeRouteListener implements EventSubscriberInterface
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

        if ($class = $this->findClass($request)) {
            $is_form = \str_ends_with($request->getPathInfo(), '.form.php');

            if (\is_a($class, CommonDropdown::class, true)) {
                $request->attributes->set('_controller', $is_form ? DropdownFormController::class : GenericListController::class);
                $request->attributes->set('class', $class);
            } else {
                $request->attributes->set('_controller', $is_form ? GenericFormController::class : GenericListController::class);
                $request->attributes->set('class', $class);
            }
        }
    }

    /**
     * @phpstan-return class-string<CommonGLPI>|null
     */
    private function findClass(Request $request): ?string
    {
        $path_info = $request->getPathInfo();

        if ($plugin_class = $this->findPluginClass($path_info)) {
            return $plugin_class;
        }

        if ($asset_class = $this->findCustomAssetClass($request)) {
            return $asset_class;
        }

        if ($asset_model_class = $this->findAssetModelclass($request)) {
            return $asset_model_class;
        }

        if ($asset_type_class = $this->findAssetTypeclass($request)) {
            return $asset_type_class;
        }

        if ($dropdown_class = $this->findCustomDropdownClass($request)) {
            return $dropdown_class;
        }

        if ($device_class = $this->findDeviceClass($request)) {
            return $device_class;
        }

        if ($model_class = $this->findGenericClass($path_info)) {
            return $model_class;
        }

        return null;
    }

    /**
     * @phpstan-return class-string<Asset>|null
     */
    private function findCustomAssetClass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/asset/asset(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null && !Asset::isNewId($id)) {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset) {
                $classname = $asset::class;
            }
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getAssetClassName();
            }
        }

        return $classname;
    }

    /**
     * @phpstan-return class-string<Dropdown>|null
     */
    private function findCustomDropdownClass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/dropdown/dropdown(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null && !Dropdown::isNewId($id)) {
            $dropdown = Dropdown::getById($id);
            if ($dropdown instanceof Dropdown) {
                $classname = $dropdown::class;
            }
        } else {
            $definition = new DropdownDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getDropdownClassName();
            }
        }

        return $classname;
    }

    /**
     * @phpstan-return class-string<CommonGLPI>|null
     */
    private function findGenericClass(string $path_info): ?string
    {
        $path_regex = '~^/front/(?<itemtype>.+)(?<form>\.form)?\.php~isUu';

        $matches = [];
        if (!\preg_match($path_regex, $path_info, $matches)) {
            return null;
        }

        $itemtype = $matches['itemtype'];

        $item = \getItemForItemtype($itemtype);

        if ($item instanceof CommonGLPI) {
            return $item::class;
        }

        $namespaced_itemtype = \preg_replace_callback(
            '~\\\([a-z])~Uu',
            static fn($i) => '\\' . \ucfirst($i[1]),
            'Glpi\\' . \str_replace('/', '\\', $itemtype)
        );

        $namespaced_item = \getItemForItemtype($namespaced_itemtype);

        if ($namespaced_item instanceof CommonGLPI) {
            return $namespaced_item::class;
        }

        return null;
    }

    /**
     * @phpstan-return class-string<CommonDevice>|null
     */
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

        $itemtype = $request->query->get('itemtype') ?: $request->request->get('itemtype');

        if ($itemtype === null) {
            return null;
        }

        $item = \getItemForItemtype($itemtype);
        if ($item instanceof CommonDevice) {
            return $item::class;
        }

        return null;
    }

    /**
     * @return class-string<AssetModel>|null
     */
    private function findAssetModelclass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/asset/assetmodel(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null && !AssetModel::isNewId($id)) {
            $asset = AssetModel::getById($id);
            if (!$asset) {
                return null;
            }
            $classname = $asset::class;
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getAssetModelClassName();
            }
        }

        return $classname;
    }

    /**
     * @return class-string<AssetType>|null
     */
    private function findAssetTypeclass(Request $request): ?string
    {
        $matches = [];
        if (!\preg_match('~^/front/asset/assettype(?<is_form>\.form)?\.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $is_form = !empty($matches['is_form']);
        $id = $request->query->get('id') ?: $request->request->get('id');

        $classname = null;

        if ($is_form && $id !== null && !AssetType::isNewId($id)) {
            $asset = AssetType::getById($id);
            if (!$asset) {
                return null;
            }
            $classname = $asset::class;
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class') && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $definition->getAssetTypeClassName();
            }
        }

        return $classname;
    }

    /**
     * @phpstan-return class-string<CommonGLPI>|null
     */
    private function findPluginClass(string $path_info): ?string
    {
        $path_regex = '~^/(plugins|marketplace)/(?<plugin>[^/]+)/front/(?<itemtype>.+)(?<form>\.form)?.php~isUu';

        $matches = [];
        if (\preg_match($path_regex, $path_info, $matches) !== 1) {
            return null;
        }

        $itemtype = $matches['itemtype'];
        $plugin = $matches['plugin'];
        if (!$this->isPluginActive($plugin)) {
            return null;
        }

        $item = \getItemForItemtype($itemtype);

        if ($item instanceof CommonGLPI) {
            return $item::class;
        }

        // PluginMyPluginItem -> /plugins/myplugin/front/item.php
        $legacy_item = \getItemForItemtype(\sprintf('Plugin%s%s', ucfirst($plugin), ucfirst($itemtype)));
        if ($legacy_item instanceof CommonGLPI) {
            return $legacy_item::class;
        }

        // GlpiPlugin\MyPlugin\Item -> /plugins/myplugin/front/item.php
        $namespaced_item = \getItemForItemtype(\sprintf('GlpiPlugin\%s\%s', ucfirst($plugin), ucfirst($itemtype)));
        if ($namespaced_item instanceof CommonGLPI) {
            return $namespaced_item::class;
        }

        return null;
    }

    private function isPluginActive(string $plugin_name): bool
    {
        $plugin = new \Plugin();

        return $plugin->isInstalled($plugin_name) && $plugin->isActivated($plugin_name);
    }
}
