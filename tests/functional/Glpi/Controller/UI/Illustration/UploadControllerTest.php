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

use Glpi\Controller\UI\Illustration\UploadController;
use Glpi\Tests\DbTestCase;
use Glpi\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\Request;

final class UploadControllerTest extends DbTestCase
{
    private function copyFixtureToTmpDir(string $fixture, string $tmp_name): string
    {
        $tmp_path = GLPI_TMP_DIR . "/$tmp_name";
        copy(GLPI_ROOT . "/tests/fixtures/uploads/$fixture", $tmp_path);
        return $tmp_path;
    }

    public function testUploadedIdDoesNotReuseClientSuppliedFilename(): void
    {
        $this->copyFixtureToTmpDir('foo.png', 'logo.png');

        $controller = new UploadController(new IllustrationManager());
        $request = Request::create('/UI/Illustration/Upload', 'POST', ['filename' => 'logo.png']);
        $response = $controller->__invoke($request);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('file', $data);
        $this->assertNotEquals('logo.png', $data['file']);
        $this->assertStringEndsWith('.png', $data['file']);
    }

    public function testUploadingSameOriginalFilenameTwiceDoesNotOverwritePreviousIllustration(): void
    {
        $this->copyFixtureToTmpDir('foo.png', 'logo.png');
        $controller = new UploadController(new IllustrationManager());
        $first_response = $controller->__invoke(
            Request::create('/UI/Illustration/Upload', 'POST', ['filename' => 'logo.png'])
        );
        $first_id = json_decode($first_response->getContent(), true)['file'];

        $this->copyFixtureToTmpDir('bar.png', 'logo.png');
        $second_response = $controller->__invoke(
            Request::create('/UI/Illustration/Upload', 'POST', ['filename' => 'logo.png'])
        );
        $second_id = json_decode($second_response->getContent(), true)['file'];

        $manager = new IllustrationManager();
        $this->assertNotEquals($first_id, $second_id);
        $this->assertEquals(
            md5_file(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'),
            md5_file($manager->getCustomIllustrationFile($first_id))
        );
        $this->assertEquals(
            md5_file(GLPI_ROOT . '/tests/fixtures/uploads/bar.png'),
            md5_file($manager->getCustomIllustrationFile($second_id))
        );
    }
}
