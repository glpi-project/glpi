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
use Glpi\Tests\GLPITestCase;
use Glpi\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CustomIllustrationControllerTest extends GLPITestCase
{
    private IllustrationManager $manager;

    private string $illustration_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new IllustrationManager();
        $this->illustration_id = 'test_custom_illustration_' . uniqid() . '.svg';

        // `saveCustomIllustration()` moves its source file into place, so the
        // temporary file must be created first and then handed over to it.
        $tmp_file = GLPI_TMP_DIR . '/' . uniqid('custom_illustration_test_') . '.svg';
        file_put_contents($tmp_file, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->manager->saveCustomIllustration($this->illustration_id, $tmp_file);
    }

    protected function tearDown(): void
    {
        $this->manager->deleteCustomIllustrationFile($this->illustration_id);

        parent::tearDown();
    }

    public function testResponseIsCacheableByTheBrowser(): void
    {
        $controller = new CustomIllustrationController($this->manager);

        $response = $controller->__invoke($this->illustration_id);

        // The bug being fixed: a bare `BinaryFileResponse` has no Cache-Control
        // directive of its own, forcing the browser to re-fetch the illustration
        // (with a full authenticated round-trip) on every single page load.
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertTrue(
            $response->headers->hasCacheControlDirective('private'),
            'Response must stay private since the route requires authentication.'
        );
        $this->assertSame(
            '604800',
            $response->headers->getCacheControlDirective('max-age'),
            'Response must be cacheable by the browser for a meaningful duration.'
        );
        $this->assertNotEmpty(
            $response->getEtag(),
            'Response must expose an ETag so the browser can revalidate cheaply.'
        );
        $this->assertNotNull(
            $response->getLastModified(),
            'Response must expose a Last-Modified date so the browser can revalidate cheaply.'
        );
    }

    public function testUnknownIllustrationStillReturnsBadRequest(): void
    {
        $controller = new CustomIllustrationController($this->manager);

        $this->expectException(\Glpi\Exception\Http\BadRequestHttpException::class);
        $controller->__invoke('this_id_does_not_exist.svg');
    }
}
