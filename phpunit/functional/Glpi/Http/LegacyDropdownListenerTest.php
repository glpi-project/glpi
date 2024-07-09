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

namespace tests\units\Glpi\Http;

use Glpi\Controller\DropdownController;
use Glpi\Http\LegacyDropdownListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class LegacyDropdownListenerTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        parent::setUp();
    }

    #[DataProvider('provide_dropdown_classes')]
    public function test_find_dropdown_class(string $path_info, string $expected_class_name): void
    {
        $listener = new LegacyDropdownListener();
        $request = $this->createRequest($path_info);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame(DropdownController::class, $request->attributes->get('_controller'));
        self::assertSame($expected_class_name, $request->attributes->get('class'));
        self::assertSame(\str_contains($path_info, '.form.php'), $request->attributes->get('is_form'));
    }

    public static function provide_dropdown_classes(): \Generator
    {
        $list = include __DIR__ . '/dropdowns_list.php';

        foreach ($list as $path => $class) {
            yield $path => [$path, $class];
        }

        $base_paths = [
            '/front/device.php',
            '/front/devicemodel.php',
            '/front/devicetype.php',
            '/front/device.form.php',
            '/front/devicemodel.form.php',
            '/front/devicetype.form.php',
        ];

        $devices_names = [
            \DeviceBattery::class,
            \DeviceCamera::class,
            \DeviceCase::class,
            \DeviceControl::class,
            \DeviceDrive::class,
            \DeviceFirmware::class,
            \DeviceGeneric::class,
            \DeviceGraphicCard::class,
            \DeviceHardDrive::class,
            \DeviceMemory::class,
            \DeviceMotherboard::class,
            \DeviceNetworkCard::class,
            \DevicePci::class,
            \DevicePowerSupply::class,
            \DeviceProcessor::class,
            \DeviceSensor::class,
            \DeviceSimcard::class,
            \DeviceSoundCard::class,
        ];

        foreach ($base_paths as $path) {
            foreach ($devices_names as $device) {
                $fullPath = $path . '?itemtype=' . $device;
                yield $fullPath => [$fullPath, $device];
            }
        }
    }

    private function createRequest(string $path_info): Request
    {
        $req = Request::create($path_info);

        $req->server->set('REQUEST_URI', $path_info);
        $req->server->set('PATH_INFO', $path_info);

        return $req;
    }
}
