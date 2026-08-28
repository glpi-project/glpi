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

namespace Glpi\Api\HL\Middleware;

use Glpi\Api\HL\FileUpload\HashedUploadedFile;
use Glpi\Application\Environment;
use Glpi\Http\Request;
use GuzzleHttp\Psr7\Utils;
use Riverline\MultiPartParser\Converters\PSR7;

use function Safe\finfo_open;
use function Safe\sha1_file;

class MultipartFormDataRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        $content_type = $input->request->getHeaderLine('Content-Type');
        if (!str_starts_with($content_type, 'multipart/form-data')) {
            $next($input);
            return;
        }

        if ($this->hasPHPParsedMultipartData($input->request)) {
            // PHP already parsed the request but didn't do it the way we want
            $uploaded_files = $this->getUploadedFilesFromSuperglobal();
            /** @var Request $request */
            $request = $input->request->withUploadedFiles($uploaded_files);
            $input->request = $request;
            $next($input);
            return;
        }

        $doc = PSR7::convert($input->request);

        if (!$doc->isMultipart()) {
            $next($input);
            return;
        }
        // Destroy the original request body to free memory
        $input->request->getBody()->close();

        // Load the fields and files into the request data
        $uploaded_files = [];
        foreach ($doc->getParts() as $part) {
            $part_name = $part->getName();

            if (empty($part_name)) {
                continue;
            }

            $is_array_field = str_ends_with($part_name, '[]');
            if ($is_array_field) {
                $part_name = substr($part_name, 0, -2);
            }

            if ($part->isFile()) {
                //get mime and sha1 now since we already have the full file in memory to avoid having to read it again.
                $file_body = $part->getBody();
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($file_body);
                $sha1 = sha1($file_body);

                // move file contents to a memory stream to avoid writing to disk unless the file is too large.
                $file_stream = Utils::streamFor($file_body);
                $default_filename = 'file_' . $sha1;
                $uploaded_files[$part_name][] = new HashedUploadedFile(
                    streamOrFile: $file_stream,
                    size: $file_stream->getSize(),
                    errorStatus: UPLOAD_ERR_OK,
                    clientFilename: $part->getFileName() ?: $default_filename,
                    clientMediaType: $mime ?: $part->getMimeType(),
                    hash_algo: 'sha1',
                    hash: $sha1
                );
            } else {
                $input->request->setParameter($part_name, $part->getBody());
            }
        }

        /** @var Request $request */
        $request = $input->request->withUploadedFiles($uploaded_files);
        $input->request = $request;
        $next($input);
    }

    /**
     * @return array<string, HashedUploadedFile[]>
     */
    private function getUploadedFilesFromSuperglobal(): array
    {
        $uploaded_files = [];

        // Handle $_FILES superglobal to create HashedUploadedFile instances. Note that each field can have multiple files.
        foreach ($_FILES as $field_name => $file_info) {
            if (str_ends_with($field_name, '[]')) {
                $field_name = substr($field_name, 0, -2);
            }
            if (is_array($file_info['name'])) {
                // Multiple files for this field
                foreach ($file_info['name'] as $index => $name) {
                    if ($file_info['error'][$index] === UPLOAD_ERR_OK) {
                        $detected_mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_info['tmp_name'][$index]) ?: $file_info['type'][$index];

                        $uploaded_files[$field_name][] = new HashedUploadedFile(
                            streamOrFile: $file_info['tmp_name'][$index],
                            size: $file_info['size'][$index],
                            errorStatus: $file_info['error'][$index],
                            clientFilename: $name,
                            clientMediaType: $detected_mime,
                            hash_algo: 'sha1',
                            hash: sha1_file($file_info['tmp_name'][$index])
                        );
                    }
                }
            } else {
                // Single file for this field
                if ($file_info['error'] === UPLOAD_ERR_OK) {
                    $detected_mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_info['tmp_name']) ?: $file_info['type'];

                    $uploaded_files[$field_name][] = new HashedUploadedFile(
                        streamOrFile: $file_info['tmp_name'],
                        size: $file_info['size'],
                        errorStatus: $file_info['error'],
                        clientFilename: $file_info['name'],
                        clientMediaType: $detected_mime,
                        hash_algo: 'sha1',
                        hash: sha1_file($file_info['tmp_name'])
                    );
                }
            }
        }

        return $uploaded_files;
    }

    private function hasPHPParsedMultipartData(Request $request): bool
    {
        return $request->getMethod() === 'POST' && Environment::get() !== Environment::TESTING;
    }
}
