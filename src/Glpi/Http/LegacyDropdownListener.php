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
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LegacyDropdownListener implements EventSubscriberInterface
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
            $request->attributes->set('_controller', DropdownController::class);
            $request->attributes->set('class', $class);
            $request->attributes->set('is_form', \str_ends_with($request->getPathInfo(), '.form.php'));
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

        if ($asset_class = $this->findAssetclass($request)) {
            return $this->normalizeClass($asset_class);
        }

        if ($edge_case_class = $this->findEdgeCaseClass($request)) {
            return $this->normalizeClass($edge_case_class);
        }

        return null;
    }

    private function findGenericClass(string $path_info): ?string
    {
        $path_regex = '~^/front/(asset/)?(?<basename>.+)(?<form>\.form)?.php~isUu';

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

    private function findAssetclass(Request $request): ?string
    {
        if (!\preg_match('~^/front/asset/asset(?<param>model|type)(?<is_form>\.form)?.php$~i', $request->getPathInfo(), $matches)) {
            return null;
        }

        $param = $matches['param'];

        $is_form = !empty($matches['is_form']);

        return $is_form
            ? $this->findAssetFormClass($request, $param === 'model')
            : $this->findModelFormClass($request, $param === 'model');
    }

    private function findAssetFormClass(Request $request, bool $is_model): string
    {
        $classname = null;

        if ($id = $request->query->get('id') ?: $request->request->get('id')) {
            $asset = $is_model ? AssetModel::getById($id) : AssetType::getById($id);
            $classname = \get_class($asset);
        } else {
            $definition = new AssetDefinition();
            if ($request->query->has('class')  && $definition->getFromDBBySystemName((string) $request->query->get('class'))) {
                $classname = $is_model
                    ? $definition->getAssetModelClassName()
                    : $definition->getAssetTypeClassName();
            }
        }

        if (!$classname) {
            throw new BadRequestException('Bad request');
        }

        return $classname;
    }

    private function findModelFormClass(Request $request, bool $is_model): ?string
    {
        $definition = new AssetDefinition();
        $classname = null;
        if (
            array_key_exists('class', $request->query->all())
            && $definition->getFromDBBySystemName($request->query->getString('class'))
        ) {
            $classname = $is_model
                ? $definition->getAssetModelClassName()
                : $definition->getAssetTypeClassName();
        }

        if (!$classname) {
            throw new BadRequestException('Bad request');
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

    private function findEdgeCaseClass(Request $request): ?string
    {
        if ($request->getPathInfo() === 'entity.form.php') {
            // Root entity : no delete
            if ($request->query->getString('id') === '0') {
                $request->attributes->set(DropdownController::OPTIONS_KEY, [
                    'canedit' => true,
                    'candel'  => false
                ]);
            }

            return \Entity::class;
        }

        return null;
    }

    private function findPluginClass(string $path_info): ?string
    {
        $path_regex = '~^/(?<type>plugins|marketplace)/(?<plugin>[^/]+)/front/(?<basename>.+)(?<form>\.form)?.php~isUu';

        if (!\preg_match($path_regex, $path_info, $matches)) {
            return null;
        }

        if (!$matches['basename']) {
            throw new \RuntimeException('Could not extract basename from URL to match legacy dropdowns.');
        }

        $basename = $matches['basename'];
        $type = $matches['type'];
        $plugin = $matches['plugin'];
        if (!$this->isPluginActive($plugin)) {
            return null;
        }

        $class = (new \DbUtils())->fixItemtypeCase($basename);

        $raw_namespaced_class = \sprintf(
            'Glpi%s\%s\%s',
            ucfirst(preg_replace('~s$~i', '', $type)),
            $plugin,
            \str_replace('/', '\\', $class),
        );
        $psr4_namespaced_class = \preg_replace_callback('~\\\([a-z])~Uu', static fn($i) => '\\' . \ucfirst($i[1]), $raw_namespaced_class);

        if (
            \class_exists($raw_namespaced_class)
            && \is_subclass_of($raw_namespaced_class, \CommonDropdown::class)
        ) {
            return $raw_namespaced_class;
        }
        if (
            \class_exists($psr4_namespaced_class)
            && \is_subclass_of($psr4_namespaced_class, \CommonDropdown::class)
        ) {
            return $psr4_namespaced_class;
        }

        return null;
    }

    private function isPluginActive(string $plugin_name): bool
    {
        $plugin = new \Plugin();

        return $plugin->isInstalled($plugin_name) && $plugin->isActivated($plugin_name);
    }
}
