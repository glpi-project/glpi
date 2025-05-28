<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Application\View\Extension;

use Glpi\Application\View\Extension\RoutingExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class RoutingExtensionTest extends TestCase
{
    public static function provideNoRouterPaths(): \Generator
    {
        yield ['path', 'test', [], '/test'];
        yield ['path', 'foobar', [], '/foobar'];
        yield ['path', 'test', ['foo' => 'bar'], '/test?foo=bar'];
        yield ['path', 'foobar', ['foo' => 'bar'], '/foobar?foo=bar'];
        yield ['url', 'test', [], 'http://localhost/test'];
        yield ['url', 'foobar', [], 'http://localhost/foobar'];
        yield ['url', 'test', ['foo' => 'bar'], 'http://localhost/test?foo=bar'];
        yield ['url', 'foobar', ['foo' => 'bar'], 'http://localhost/foobar?foo=bar'];
    }

    #[DataProvider('provideNoRouterPaths')]
    public function test_method_with_no_router(string $method, string $resource, array $params, string $expected): void
    {
        $extension = new RoutingExtension();

        $path = $extension->$method($resource, $params);

        self::assertSame($expected, $path);
    }

    public static function provideWithRouterPaths(): \Generator
    {
        yield ['path', 'test', [], '/from-router/test'];
        yield ['path', 'foobar', [], '/from-router/foobar'];
        yield ['path', 'test', ['foo' => 'bar'], '/from-router/test?foo=bar'];
        yield ['path', 'foobar', ['foo' => 'bar'], '/from-router/foobar?foo=bar'];
        yield ['url', 'test', [], 'http://localhost/from-router/test'];
        yield ['url', 'foobar', [], 'http://localhost/from-router/foobar'];
        yield ['url', 'test', ['foo' => 'bar'], 'http://localhost/from-router/test?foo=bar'];
        yield ['url', 'foobar', ['foo' => 'bar'], 'http://localhost/from-router/foobar?foo=bar'];
    }

    #[DataProvider('provideWithRouterPaths')]
    public function test_path_with_custom_router(string $method, string $resource, array $params, string $expected): void
    {
        $extension = new RoutingExtension($this->getUrlGeneratorStub());

        $path = $extension->$method($resource, $params);

        self::assertSame($expected, $path);
    }

    public function getUrlGeneratorStub(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            public function setContext(RequestContext $context): void {}

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }

            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                $url = '/from-router/' . $name;
                if ($referenceType === self::ABSOLUTE_URL) {
                    $url = 'http://localhost' . $url;
                }
                if ($parameters) {
                    $url .= '?' . \http_build_query($parameters);
                }
                return $url;
            }
        };
    }
}
