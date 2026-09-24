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

namespace tests\units\Glpi\Api\HL\Middleware;

use Glpi\Api\HL\FileUpload\HashedUploadedFile;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Api\HL\Middleware\MultipartFormDataRequestMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Http\Request;
use Glpi\Tests\DbTestCase;

/**
 * Tests for the $_FILES branch of the middleware, which is the one used in production for POST requests.
 * The body parsing branch, used for the other methods, is covered by the controller tests which send real
 * multipart bodies.
 */
class MultipartFormDataRequestMiddlewareTest extends DbTestCase
{
    /** @var string[] */
    private array $tmp_files = [];

    public function tearDown(): void
    {
        foreach ($this->tmp_files as $tmp_file) {
            if (file_exists($tmp_file)) {
                unlink($tmp_file);
            }
        }
        $this->tmp_files = [];
        $_FILES = [];
        parent::tearDown();
    }

    /**
     * Copy an upload fixture to a temporary location to act as the file PHP moved to its upload directory.
     */
    private function getTempUpload(string $fixture): string
    {
        $tmp_file = tempnam(sys_get_temp_dir(), 'glpi_upload_test');
        copy(GLPI_ROOT . '/tests/fixtures/uploads/' . $fixture, $tmp_file);
        $this->tmp_files[] = $tmp_file;
        return $tmp_file;
    }

    private function processRequest(?Request $request = null): Request
    {
        $input = new MiddlewareInput(
            $request ?? new Request('POST', '/', ['Content-Type' => 'multipart/form-data; boundary=---boundary'], ''),
            new RoutePath('', '', '', ['POST'], 1, Route::SECURITY_AUTHENTICATED, ''),
            null
        );
        $called = false;
        (new MultipartFormDataRequestMiddleware())->process($input, function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);

        return $input->request;
    }

    public function testSingleFile(): void
    {
        $tmp_file = $this->getTempUpload('bar.png');
        $_FILES = [
            'file' => [
                // The client file name must be reduced to its base name and the client media type must be ignored
                // in favor of the type detected from the actual content.
                'name' => '../../evil/bar.png',
                'type' => 'text/plain',
                'tmp_name' => $tmp_file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmp_file),
            ],
        ];

        $uploaded_files = $this->processRequest()->getUploadedFiles();

        $this->assertArrayHasKey('file', $uploaded_files);
        $this->assertCount(1, $uploaded_files['file']);
        $file = $uploaded_files['file'][0];
        $this->assertInstanceOf(HashedUploadedFile::class, $file);
        $this->assertEquals('bar.png', $file->getClientFilename());
        $this->assertEquals('image/png', $file->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals(filesize($tmp_file), $file->getSize());
        $this->assertEquals('sha1', $file->getHashAlgo());
        $this->assertEquals(sha1_file($tmp_file), $file->getHash());
        $this->assertEquals(file_get_contents($tmp_file), (string) $file->getStream());
    }

    public function testMultipleFilesWithArraySuffix(): void
    {
        $first_tmp_file = $this->getTempUpload('bar.png');
        $second_tmp_file = $this->getTempUpload('foo.png');
        $_FILES = [
            // The "[]" suffix must be stripped so the field matches the schema property name
            'pictures_upload[]' => [
                'name' => ['bar.png', 'foo.png'],
                'type' => ['image/png', 'image/png'],
                'tmp_name' => [$first_tmp_file, $second_tmp_file],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [filesize($first_tmp_file), filesize($second_tmp_file)],
            ],
        ];

        $uploaded_files = $this->processRequest()->getUploadedFiles();

        $this->assertArrayNotHasKey('pictures_upload[]', $uploaded_files);
        $this->assertArrayHasKey('pictures_upload', $uploaded_files);
        $this->assertCount(2, $uploaded_files['pictures_upload']);
        $this->assertEquals('bar.png', $uploaded_files['pictures_upload'][0]->getClientFilename());
        $this->assertEquals(sha1_file($first_tmp_file), $uploaded_files['pictures_upload'][0]->getHash());
        $this->assertEquals('foo.png', $uploaded_files['pictures_upload'][1]->getClientFilename());
        $this->assertEquals(sha1_file($second_tmp_file), $uploaded_files['pictures_upload'][1]->getHash());
    }

    /**
     * A file that could not be transferred must still be reported so the request isn't handled as if no file
     * had been sent at all.
     */
    public function testErroredFilesArePropagated(): void
    {
        $tmp_file = $this->getTempUpload('bar.png');
        $_FILES = [
            'too_big' => [
                'name' => 'bar.png',
                'type' => 'image/png',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_INI_SIZE,
                'size' => 0,
            ],
            'partial' => [
                'name' => ['bar.png'],
                'type' => ['image/png'],
                'tmp_name' => [''],
                'error' => [UPLOAD_ERR_PARTIAL],
                'size' => [0],
            ],
            'ok' => [
                'name' => 'bar.png',
                'type' => 'image/png',
                'tmp_name' => $tmp_file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmp_file),
            ],
        ];

        $uploaded_files = $this->processRequest()->getUploadedFiles();

        $this->assertCount(1, $uploaded_files['too_big']);
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $uploaded_files['too_big'][0]->getError());
        $this->assertEquals('bar.png', $uploaded_files['too_big'][0]->getClientFilename());
        // No content was received, so the client-declared media type is all we have
        $this->assertEquals('image/png', $uploaded_files['too_big'][0]->getClientMediaType());
        $this->assertCount(1, $uploaded_files['partial']);
        $this->assertEquals(UPLOAD_ERR_PARTIAL, $uploaded_files['partial'][0]->getError());
        $this->assertCount(1, $uploaded_files['ok']);
        $this->assertEquals(UPLOAD_ERR_OK, $uploaded_files['ok'][0]->getError());
    }

    /**
     * An empty file input is not an error and must simply be ignored.
     */
    public function testFieldsWithoutAnyFileAreIgnored(): void
    {
        $_FILES = [
            'file' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0,
            ],
        ];

        $this->assertEquals([], $this->processRequest()->getUploadedFiles());
    }

    public function testNonMultipartRequestIsUntouched(): void
    {
        $tmp_file = $this->getTempUpload('bar.png');
        $_FILES = [
            'file' => [
                'name' => 'bar.png',
                'type' => 'image/png',
                'tmp_name' => $tmp_file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmp_file),
            ],
        ];

        $input = new MiddlewareInput(
            new Request('POST', '/', ['Content-Type' => 'application/json'], '{}'),
            new RoutePath('', '', '', ['POST'], 1, Route::SECURITY_AUTHENTICATED, ''),
            null
        );
        (new MultipartFormDataRequestMiddleware())->process($input, static function () {});

        $this->assertEquals([], $input->request->getUploadedFiles());
    }

    public function testRepeatedNonFileArrayFieldsAreCollected(): void
    {
        $request = new Request('PATCH', '/', ['Content-Type' => 'multipart/form-data; boundary=---boundary'], <<<EOT
-----boundary
Content-Disposition: form-data; name="pictures_remove[]"

first-picture
-----boundary
Content-Disposition: form-data; name="pictures_remove[]"

second-picture
-----boundary
Content-Disposition: form-data; name="pictures_remove[]"

third-picture
-----boundary
Content-Disposition: form-data; name="name"

updated-name
-----boundary--
EOT);

        $request = $this->processRequest($request);

        $this->assertSame(['first-picture', 'second-picture', 'third-picture'], $request->getParameter('pictures_remove'));
        $this->assertSame('updated-name', $request->getParameter('name'));
    }
}
