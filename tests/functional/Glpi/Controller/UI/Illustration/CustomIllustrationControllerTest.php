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

namespace tests\units\Glpi\Controller\UI\Illustration;

use Glpi\Controller\UI\Illustration\CustomIllustrationController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use Glpi\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\Request;

final class CustomIllustrationControllerTest extends DbTestCase
{
    private function saveFixtureIllustration(string $fixture, string $id): void
    {
        $tmp_path = GLPI_TMP_DIR . "/$id";
        copy(GLPI_ROOT . "/tests/fixtures/uploads/$fixture", $tmp_path);
        (new IllustrationManager())->saveCustomIllustration($id, $tmp_path);
    }

    public function testResponseIsCacheableForOneYear(): void
    {
        $id = 'custom-illustration-controller-test.png';
        $this->saveFixtureIllustration('foo.png', $id);

        $controller = new CustomIllustrationController(new IllustrationManager());
        $response = $controller->__invoke($id, new Request());

        // private: route is authenticated, must not be cached by shared/proxy caches
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('immutable'));
        $this->assertEquals(
            60 * 60 * 24 * 365,
            (int) $response->headers->getCacheControlDirective('max-age')
        );
        $this->assertNotEmpty($response->getEtag());
        $this->assertNotNull($response->getLastModified());
    }

    public function testUnknownIdThrowsBadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $controller = new CustomIllustrationController(new IllustrationManager());
        $controller->__invoke('unknown-illustration-id.png', new Request());
    }
}
