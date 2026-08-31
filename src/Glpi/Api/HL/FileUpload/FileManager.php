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

namespace Glpi\Api\HL\FileUpload;

use Document;
use DOMDocument;
use DOMElement;
use Safe\Exceptions\FilesystemException;
use Toolbox;

use function Safe\base64_decode;
use function Safe\finfo_open;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\mkdir;
use function Safe\preg_match;
use function Safe\rewind;

/**
 * Slim file upload manager designed specifically for the High-Level API.
 * This class provides helper methods for handling the different types of files in GLPI including raw file fields (the file field in the Document type), creating documents, and saving pictures.
 */
final class FileManager
{
    /** @var string Upload type unique to the file input for Documents. */
    public const UPLOAD_AS_FILE = 'file';
    /** @var string Upload the file by creating a new Document */
    public const UPLOAD_AS_DOCUMENT = 'document';
    /** @var string Upload the file by saving it as a picture */
    public const UPLOAD_AS_PICTURE = 'picture';

    /**
     * @var array<string, string> Mapping of image mime types to their corresponding file extensions.
     * This is used for handling inline images in HTML content and for saving pictures.
     */
    private static array $image_mime_to_extension_map = [
        'image/jpeg' => 'jpg',
        'image/bmp' => 'bmp',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Returns an array of file specifiers (extensions or mime types) that are allowed to be uploaded as Documents.
     * Similar to {@link DocumentType::getUploadableFilePattern()} but returns an array and prefers mime types over extensions.
     * @return string[]
     */
    public static function getUploadableFileSpecifiers(): array
    {
        global $DB;

        static $specifiers = null;

        if ($specifiers === null) {
            $specifiers = [];
            $it = $DB->request([
                'SELECT' => ['ext', 'mime'],
                'FROM' => 'glpi_documenttypes',
                'WHERE' => [
                    'is_uploadable' => 1,
                ],
            ]);
            foreach ($it as $row) {
                $specifier = $row['mime'] ?: $row['ext'];
                $specifiers[] = strtolower($specifier);
            }
            $specifiers = array_unique($specifiers);
        }

        return $specifiers;
    }

    public static function isDocumentUploadAllowed(string $file_mime, string $extension): bool
    {
        $specifiers = self::getUploadableFileSpecifiers();
        return in_array(strtolower($file_mime), $specifiers, true) || in_array(strtolower($extension), $specifiers, true);
    }

    /**
     * Uploads a file for use in a Document and returns the input parameters required to associate it with a Document.
     * @param HashedUploadedFile $uploaded_file
     * @return array{filename: string, sha1sum: string, filepath: string}|int The input parameters for Document creation or an error status
     */
    public static function uploadFile(HashedUploadedFile $uploaded_file): array|int
    {
        if ($uploaded_file->getError() !== UPLOAD_ERR_OK) {
            return $uploaded_file->getError();
        }
        $stream = $uploaded_file->getStream();
        if (!$stream->isReadable()) {
            return UPLOAD_ERR_NO_FILE;
        }

        $ext = pathinfo($uploaded_file->getClientFilename(), PATHINFO_EXTENSION);
        if (!preg_match('/^[a-zA-Z0-9]+$/', $ext)) {
            return UPLOAD_ERR_CANT_WRITE;
        }
        $dest = Document::getUploadFileValidLocationName(strtoupper($ext), $uploaded_file->getHash());
        try {
            $uploaded_file->moveTo(GLPI_DOC_DIR . '/' . $dest);
        } catch (\RuntimeException $e) {
            return UPLOAD_ERR_CANT_WRITE;
        }

        return [
            'filename' => $uploaded_file->getClientFilename(),
            'sha1sum' => $uploaded_file->getHash(),
            'filepath' => $dest,
        ];
    }

    /**
     * Creates a Document with the uploaded file.
     * This function assumes the file is authorized to be uploaded and meets the GLPI file size requirements.
     * @param HashedUploadedFile $uploaded_file The file to upload
     * @return Document|int|null An array containing the ID of the created Document, an error status, or null if the Document could not be created
     */
    public static function uploadAsDocument(HashedUploadedFile $uploaded_file): Document|int|null
    {
        $result = self::uploadFile($uploaded_file);
        if (is_int($result)) {
            return $result;
        }
        $input['filename'] = $result['filename'];
        $input['sha1sum'] = $result['sha1sum'];
        $input['filepath'] = $result['filepath'];
        $document = new Document();
        $documents_id = $document->add($input);
        return $documents_id !== false ? $document : null;
    }

    /**
     * @param HashedUploadedFile $uploaded_file
     * @return array{filepath: string}|int The input parameters for picture saving or an error status
     */
    public static function uploadAsPicture(HashedUploadedFile $uploaded_file): array|int
    {
        if ($uploaded_file->getError() !== UPLOAD_ERR_OK) {
            return $uploaded_file->getError();
        }
        $stream = $uploaded_file->getStream();
        if (!$stream->isReadable()) {
            return UPLOAD_ERR_NO_FILE;
        }

        $ext = self::$image_mime_to_extension_map[$uploaded_file->getClientMediaType()] ?? '';
        if ($ext === '') {
            return UPLOAD_ERR_CANT_WRITE;
        }
        $unique_name = uniqid('', true) . '.' . $ext;
        $subdir = substr($unique_name, 0, 2);
        $dest = GLPI_PICTURE_DIR . '/' . $subdir . '/' . $unique_name;
        if (!is_dir(GLPI_PICTURE_DIR . '/' . $subdir)) {
            try {
                mkdir(GLPI_PICTURE_DIR . '/' . $subdir);
            } catch (FilesystemException $e) {
                return UPLOAD_ERR_CANT_WRITE;
            }
        }
        try {
            $uploaded_file->moveTo($dest);
        } catch (\RuntimeException $e) {
            return UPLOAD_ERR_CANT_WRITE;
        }
        return [
            'filepath' => $subdir . '/' . $unique_name,
        ];
    }

    /**
     * Deletes a picture from the GLPI picture directory.
     * The path should already be validated and protected against directory traversal.
     * This function only normalizes the picture path and then attempts to delete it.
     * @param string $picture_path
     * @return bool
     */
    public static function deletePicture(string $picture_path): bool
    {
        $path = self::normalizePictureClientValue($picture_path);
        if ($path === null) {
            return false;
        }
        return Toolbox::deletePicture($path);
    }

    /**
     * Extracts base64-encoded inline images from HTML content, saves them as documents, and replaces the inline images with document references.
     * @param string $html_content
     * @param Document[] $created_documents An array to store the created documents. Useful for implementing cleanup logic if needed.
     * @return false|string The modified HTML content with inline images replaced by document references
     */
    public static function handleInlineImagesInHTML(string $html_content, array &$created_documents = []): false|string
    {
        global $CFG_GLPI;

        $dom = new DOMDocument();
        @$dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');

        if ($images->length === 0) {
            // Return input as-is if there are no images to process to avoid unnecessarily changing a plaintext value into HTML
            return $html_content;
        }

        $max_image_size = $CFG_GLPI['document_max_size'] * 1024 * 1024;

        /** @var DOMElement $img */
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (preg_match('/^data:(image\/[a-zA-Z]+);base64,(.*)$/', $src, $matches)) {
                $mime_type = (string) $matches[1];
                $extension = self::$image_mime_to_extension_map[$mime_type] ?? '';
                $base64_data = $matches[2];
                // Rough estimate of the decoded size (won't be more than this) to avoid decoding large images into memory unnecessarily
                $estimated_size = ceil(strlen($base64_data) * 3 / 4);
                if ($estimated_size > $max_image_size) {
                    // completely remove the image if it exceeds the maximum size
                    $img->parentNode?->removeChild($img);
                    continue;
                }

                $image_data = base64_decode($base64_data);
                unset($base64_data);
                $image_data_size = strlen($image_data);
                $image_data_hash = sha1($image_data);
                $detected_mime_type = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $image_data);

                if ($detected_mime_type === false || strtolower($mime_type) !== strtolower($detected_mime_type) || !self::isDocumentUploadAllowed($mime_type, $extension)) {
                    // completely remove the image if the upload is not allowed
                    $img->parentNode?->removeChild($img);
                    continue;
                }

                $image_stream = fopen('php://memory', 'r+b');
                fwrite($image_stream, $image_data);
                unset($image_data);
                rewind($image_stream);

                $file_name = uniqid('inline_image_', true);
                if ($extension !== '') {
                    $file_name .= '.' . $extension;
                }

                // Create a HashedUploadedFile instance
                $uploaded_file = new HashedUploadedFile(
                    streamOrFile: $image_stream,
                    size: $image_data_size,
                    errorStatus: UPLOAD_ERR_OK,
                    clientFilename: $file_name,
                    clientMediaType: $mime_type,
                    hash_algo: 'sha1',
                    hash: $image_data_hash
                );

                // Upload the image as a document
                $upload_result = self::uploadAsDocument($uploaded_file);
                if ($upload_result instanceof Document) {
                    // Replace the inline image with a reference to the document
                    $img->setAttribute('src', '/front/document.send.php?docid=' . $upload_result->getID());
                    $created_documents[] = $upload_result;
                }
            }
        }

        return $dom->saveHTML();
    }

    public static function normalizeClientFileValue(string $value, string $upload_as): ?string
    {
        return match ($upload_as) {
            self::UPLOAD_AS_PICTURE => self::normalizePictureClientValue($value),
            self::UPLOAD_AS_DOCUMENT,
            self::UPLOAD_AS_FILE => $value,
            default => null,
        };
    }

    /**
     * @param string $value
     * @return string|null
     * @phpstan-return ($value is '' ? null : string)
     */
    private static function normalizePictureClientValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $value = strtolower(urldecode($value));
        if (str_contains($value, '_pictures/')) {
            $pos = strpos($value, '_pictures/');
            return substr($value, $pos + strlen('_pictures/'));
        }

        return $value;
    }
}
