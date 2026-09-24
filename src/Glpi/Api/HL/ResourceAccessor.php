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

namespace Glpi\Api\HL;

use CommonDBTM;
use CommonGLPI;
use CommonITILObject;
use Document;
use Document_Item;
use Entity;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\FileUpload\FileManager;
use Glpi\Api\HL\FileUpload\FileUploadException;
use Glpi\Api\HL\FileUpload\HashedUploadedFile;
use Glpi\Api\HL\RSQL\RSQLException;
use Glpi\Api\HL\Search\SearchContext;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use Glpi\Toolbox\ArrayPathAccessor;
use RuntimeException;
use Safe\DateTime;
use Session;
use Throwable;

use function Safe\preg_match;

/**
 * Class contaning methods for accessing GLPI resources (items) from the HL API via schemas.
 * @phpstan-type RollbackJournal array{documents: Document[], files: array<int, array{filepath: string, sha1sum: string}>, pictures: string[], deferred_picture_deletions: string[]}
 * @todo v3 Separate methods related to input handling into a new class that can be instantiated for each create/update request. This would allow for better handling of uploaded files and other request-specific data.
 */
final class ResourceAccessor
{
    /**
     * @param array $schema
     * @return class-string<CommonGLPI>|null
     */
    private static function getItemtypeFromSchema(array $schema): ?string
    {
        if (isset($schema['x-itemtype'])) {
            return $schema['x-itemtype'];
        } elseif (isset($schema['x-table'])) {
            return getItemTypeForTable($schema['x-table']);
        }
        return null;
    }
    /**
     * Get the related itemtype for the given schema.
     * @param array $schema
     */
    private static function getItemFromSchema(array $schema): CommonDBTM
    {
        $itemtype = self::getItemtypeFromSchema($schema);
        if ($itemtype === null) {
            throw new RuntimeException('Schema has no x-table or x-itemtype');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new RuntimeException('Invalid itemtype');
        }
        return new $itemtype();
    }

    /**
     * Get the primary ID field given some other unique field.
     * @param array $schema The schema
     * @param string $field The unique field name
     * @param mixed $value The unique field value
     * @return int|null The ID or null if not found
     */
    public static function getIDForOtherUniqueFieldBySchema(array $schema, string $field, mixed $value): ?int
    {
        global $DB;

        if (!isset($schema['properties'][$field])) {
            throw new RuntimeException('Invalid primary key');
        }
        $prop = $schema['properties'][$field];
        $pk_sql_name = $prop['x-field'] ?? $field;
        $context = new SearchContext($schema, []);
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $context->getSchemaTable(),
            'WHERE' => [
                $pk_sql_name => $value,
            ],
        ]);
        if (count($iterator) === 0) {
            return null;
        }
        return $iterator->current()['id'];
    }

    /**
     * @param string $prop_name
     * @param array<string, mixed> $prop
     * @return string
     */
    private static function resolveInternalFieldNameForProperty(string $prop_name, array $prop): string
    {
        // Field resolution priority: x-field -> x-join.fkey -> property name
        if (isset($prop['x-input-field'])) {
            $internal_name = $prop['x-input-field'];
        } elseif (isset($prop['x-field'])) {
            $internal_name = $prop['x-field'];
        } elseif (isset($prop['x-join']['fkey'])) {
            $internal_name = $prop['x-join']['fkey'] ?? $prop_name;
        } else {
            $internal_name = $prop_name;
        }

        return $internal_name;
    }

    /**
     * Resolve the file upload declaration of a top-level schema property.
     *
     * {@link Doc\Schema::flattenProperties()} replaces array types by their `items` definition, which makes it
     * impossible to tell an array of files apart from a single file. Anything dealing with file uploads must
     * therefore read the raw (non-flattened) schema properties through this method.
     *
     * @param array<string, mixed> $prop The raw schema property definition
     * @return array{options: array<string, mixed>, definition: array<string, mixed>, is_array: bool}|null
     *      Null if the property doesn't accept file uploads. Otherwise, the file upload options, the definition
     *      carrying them (the `items` definition for arrays) and whether the property accepts multiple files.
     */
    private static function getFileUploadSpecification(array $prop): ?array
    {
        $is_array = ($prop['type'] ?? null) === Doc\Schema::TYPE_ARRAY;
        $definition = $is_array ? ($prop['items'] ?? []) : $prop;
        if (!isset($definition['x-file-upload-options'])) {
            return null;
        }
        return [
            'options' => $definition['x-file-upload-options'],
            'definition' => $definition,
            'is_array' => $is_array,
        ];
    }

    /**
     * Map the request parameters to the format required for the GLPI add/update methods.
     * Only top-level properties are mapped.
     * Nested properties which would represent relations are not supported.
     * Creating/updating relations should be done using the appropriate endpoints.
     * @param array $schema
     * @param array $request_params
     * @return array
     */
    public static function getInputParamsBySchema(array $schema, array $request_params): array
    {
        $params = [];
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $joins = Doc\Schema::getJoins($schema['properties']);
        $writable_props = array_filter($flattened_properties, static function ($v, $k) use ($joins) {
            $base_k = strstr($k, '.', true) ?: $k;
            $is_join = isset($joins[$base_k]);
            $is_dropdown_identifier = preg_match('/^(\w+)\.id$/', $k);
            return $is_dropdown_identifier || !$is_join;
        }, ARRAY_FILTER_USE_BOTH);
        foreach ($writable_props as $prop_name => $prop) {
            $is_dropdown_identifier = preg_match('/^(\w+)\.id$/', $prop_name);
            if ($is_dropdown_identifier) {
                // This is a dropdown identifier, we need to get the id from the request
                $prop_name = (string) strstr($prop_name, '.', true);
                $prop = $schema['properties'][$prop_name];
            } else {
                if ($prop['readOnly'] ?? false) {
                    // Ignore properties marked as read-only
                    continue;
                }
            }

            if (isset($prop['x-file-upload-options'])) {
                // File uploads and removals are handled elsewhere. Skipping for file uploads here also prevents user's from specifying existing documents/files which is not desired at this point or validated for permissions.
                continue;
            }

            $internal_name = self::resolveInternalFieldNameForProperty($prop_name, $prop);

            if (array_key_exists('format', $prop) && $prop['format'] === Doc\Schema::FORMAT_STRING_DATE_TIME) {
                // convert RFC 3339 to YYYY-MM-DD HH:MM:SS
                if (ArrayPathAccessor::hasElementByArrayPath($request_params, $prop_name)) {
                    $dt = new DateTime(ArrayPathAccessor::getElementByArrayPath($request_params, $prop_name));
                    $params[$internal_name] = $dt->format('Y-m-d H:i:s');
                }
                continue;
            }

            // Modify the request params to support setting a dropdown value by its id as expected from the OpenAPI schema
            foreach ($request_params as $key => $value) {
                if (is_array($value) && array_key_exists('id', $value)) {
                    $request_params[$key] = $value['id'];
                }
            }

            if (ArrayPathAccessor::hasElementByArrayPath($request_params, $prop_name)) {
                $params[$internal_name] = ArrayPathAccessor::getElementByArrayPath($request_params, $prop_name);
            }
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $input
     * @param bool $is_create_input Whether the input is for a create or update action.
     * @return array<string, array{error: string, message: string}[]>
     */
    private static function validateInputParamsBySchema(array $schema, array $input, bool $is_create_input): array
    {
        global $CFG_GLPI;

        $max_file_size_bytes = $CFG_GLPI['document_max_size'] * 1024 * 1024;
        $errors = [];
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $uploaded_files = Router::getInstance()->getFinalRequest()?->getUploadedFiles() ?? [];

        if ($is_create_input) {
            // Check required properties
            foreach ($flattened_properties as $prop_name => $prop) {
                if (($prop['required'] ?? false) && !ArrayPathAccessor::hasElementByArrayPath($input, $prop_name)) {
                    $errors[$prop_name][] = [
                        'error' => 'required',
                        'message' => 'This field is required',
                    ];
                }
            }
        }

        foreach ($input as $key => $value) {
            if (!isset($flattened_properties[$key])) {
                continue;
            }
            $prop = $flattened_properties[$key];

            if (isset($prop['maxLength']) && is_string($value) && strlen($value) > $prop['maxLength']) {
                $errors[$key][] = [
                    'error' => 'maxLength',
                    'message' => "This field must be at most {$prop['maxLength']} characters long",
                    'maxLength' => $prop['maxLength'],
                ];
            }

            $min = $prop['minimum'] ?? null;
            $max = $prop['maximum'] ?? null;
            if ($min !== null && $max !== null && is_numeric($value) && ($value < $min || $value > $max)) {
                $errors[$key][] = [
                    'error' => 'range',
                    'message' => "This field must be between {$prop['minimum']} and {$prop['maximum']}",
                    'minimum' => $prop['minimum'] ?? null,
                    'maximum' => $prop['maximum'] ?? null,
                ];
            } elseif ($min !== null && is_numeric($value) && $value < $min) {
                $errors[$key][] = [
                    'error' => 'minimum',
                    'message' => "This field must be at least {$prop['minimum']}",
                    'minimum' => $prop['minimum'] ?? null,
                ];
            } elseif ($max !== null && is_numeric($value) && $value > $max) {
                $errors[$key][] = [
                    'error' => 'maximum',
                    'message' => "This field must be at most {$prop['maximum']}",
                    'maximum' => $prop['maximum'] ?? null,
                ];
            }
            if (isset($prop['pattern']) && is_string($value) && !preg_match('/' . $prop['pattern'] . '/', $value)) {
                $errors[$key][] = [
                    'error' => 'pattern',
                    'message' => "This field must match the pattern {$prop['pattern']}",
                    'pattern' => $prop['pattern'],
                ];
            }
        }

        // File uploads must be read from the raw schema properties. See self::getFileUploadSpecification().
        foreach ($schema['properties'] as $key => $prop) {
            $file_upload_spec = self::getFileUploadSpecification($prop);
            if ($file_upload_spec === null || !isset($uploaded_files[$key])) {
                continue;
            }

            if (!$file_upload_spec['is_array'] && count($uploaded_files[$key]) > 1) {
                $errors[$key][] = [
                    'error' => 'maxItems',
                    'message' => 'This field accepts at most one uploaded file',
                    'maxItems' => 1,
                ];
                continue;
            }

            $file_upload_options = $file_upload_spec['options'];

            foreach ($uploaded_files[$key] as $file) {
                // Validate file upload options

                $upload_error = $file->getError();
                if ($upload_error !== UPLOAD_ERR_OK) {
                    // The transfer itself failed, so there is nothing left to validate for this file.
                    $errors[$key][] = match ($upload_error) {
                        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => [
                            'error' => 'file_size_exceeded',
                            'message' => "The uploaded file exceeds the maximum allowed size of {$CFG_GLPI['document_max_size']} MB.",
                            'max_file_size_bytes' => $max_file_size_bytes,
                        ],
                        default => [
                            'error' => 'file_upload_failed',
                            'message' => 'The file could not be uploaded.',
                        ],
                    };
                    continue;
                }

                if ($file->getSize() > $max_file_size_bytes) {
                    $errors[$key][] = [
                        'error' => 'file_size_exceeded',
                        'message' => "The uploaded file exceeds the maximum allowed size of {$CFG_GLPI['document_max_size']} MB.",
                        'max_file_size_bytes' => $max_file_size_bytes,
                    ];
                }

                $file_mime = $file->getClientMediaType();
                $file_extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

                if (
                    isset($file_upload_options['allowed_specifiers'])
                    && !in_array(strtolower($file_mime), $file_upload_options['allowed_specifiers'], true)
                    && !in_array(strtolower($file_extension), $file_upload_options['allowed_specifiers'], true)
                ) {
                    $errors[$key][] = [
                        'error' => 'invalid_file_type',
                        'message' => 'This file type is not allowed for upload as a picture.',
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Handle rich text inputs that may contain inline images.
     * This is intended to be called after the input array is mapped from the request params, after the permission checks, but before the item is initially added/updated in the DB.
     * By handling inline images before the item is added/updated, we can ensure the large base64 data is not stored in the DB which could cause errors if it causes the field to be too large for the column.
     *
     * This separation is also to prevent abuse of the inline image handling, which could be used to upload files without proper permission checks.
     * Separate logic to clean up files after a failed create/update should be implemented to prevent orphaned files.
     * There is already an automatic action that can clean orphaned documents but it is not enabled by default and should not be relied upon for normal operation.
     *
     * @param array<string, mixed> $schema The schema
     * @param array<string, mixed> $input The input parameters
     * @param Document[] $created_documents An array to store the created documents. Useful for implementing cleanup logic if needed.
     * @param RollbackJournal $rollback_journal
     * @return array<string, mixed> The modified input parameters with inline images handled
     */
    private static function handleRichTextInputs(array $schema, array $input, array &$created_documents, array &$rollback_journal): array
    {
        $entities_id = $input['entities_id'] ?? Session::getActiveEntity();
        $is_recursive = (bool) ($input['is_recursive'] ?? false);

        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        foreach ($flattened_properties as $prop_name => $prop) {
            if (isset($prop['format']) && $prop['format'] === Doc\Schema::FORMAT_STRING_HTML) {
                if (isset($prop['x-supports-inline-images'])) {
                    $field_name = self::resolveInternalFieldNameForProperty($prop_name, $prop);
                    // Need to extract base64 data uris from img tags and upload them as documents, replacing the src with the document URL
                    $html = $input[$field_name] ?? null;
                    if ($html !== null) {
                        $html = FileManager::handleInlineImagesInHTML($html, $entities_id, $is_recursive, $created_documents, $rollback_journal);
                        if ($html === false) {
                            throw new FileUploadException(
                                $prop_name,
                                'One or more inline images could not be uploaded.',
                                UPLOAD_ERR_CANT_WRITE
                            );
                        }
                        $input[$field_name] = $html;
                    }
                }
            }
        }
        return $input;
    }

    private static function getFileUploadErrorResponse(FileUploadException $exception): Response
    {
        return new JSONResponse(
            AbstractController::getErrorResponseBody(
                AbstractController::ERROR_INVALID_PARAMETER,
                'Invalid input parameters',
                [
                    $exception->getPropertyName() => [[
                        'error' => $exception->getErrorName(),
                        'message' => $exception->getUserMessage(),
                    ]],
                ]
            ),
            400
        );
    }

    /**
     * Build the generic 500 error response used whenever an unexpected {@link Throwable} is caught.
     * The exception message is only included in the response when running in debug mode.
     * @param Throwable $e
     * @return Response
     */
    private static function getGenericErrorResponse(Throwable $e): Response
    {
        $message = (new APIException())->getUserMessage();
        $detail = null;
        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $detail = $e->getMessage();
        }
        return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
    }

    /**
     * Handles any actions that should happen after the creation or update of an item is successful.
     *
     * @param CommonDBTM $item The item that was created or updated
     * @param array<string, mixed> $schema The schema of the item
     * @param array<string, mixed> $request_params The request parameters used for the creation or update
     * @param array<string, mixed> $input The input parameters that were used for the creation or update.
     * May also include some internal-only fields that were added during the input parameter mapping process that are required for post-action handling.
     * @param RollbackJournal $rollback_journal
     * @return void
     */
    private static function handlePostCreateOrUpdate(CommonDBTM $item, array $schema, array $request_params, array $input, array &$rollback_journal): void
    {
        $new_input = [];

        // Handle single-file removals.
        // Properties accepting multiple files are excluded here as they are removed selectively through their
        // own "<field>_remove" property instead of by sending an empty value.
        // The raw schema properties are used as flattening them would hide that distinction. See self::getFileUploadSpecification().
        foreach ($schema['properties'] as $prop_name => $prop) {
            $file_upload_spec = self::getFileUploadSpecification($prop);
            if ($file_upload_spec === null || $file_upload_spec['is_array']) {
                continue;
            }
            $internal_name = self::resolveInternalFieldNameForProperty($prop_name, $prop);

            if (
                ArrayPathAccessor::getElementByArrayPath($request_params, $prop_name) === ''
                && !empty($item->fields[$internal_name])
            ) {
                $upload_as = $file_upload_spec['options']['upload_as'] ?? FileManager::UPLOAD_AS_DOCUMENT;

                if ($upload_as === FileManager::UPLOAD_AS_PICTURE) {
                    $picture_path = FileManager::normalizePictureClientValue((string) $item->fields[$internal_name]);
                    if ($picture_path === null) {
                        throw new FileUploadException($prop_name, 'File removal failed', 0, null, null, 'file_removal_failed');
                    }
                    $rollback_journal['deferred_picture_deletions'][] = $picture_path;
                    $new_input[$internal_name] = null;
                }
            }
        }

        //TODO v3 Refactor ResourceAccessor to accept the Request itself instead of indivudual params for parameters and attributes.
        // This way we can have access to uploaded files as well
        $uploaded_files = Router::getInstance()->getFinalRequest()?->getUploadedFiles() ?? [];

        foreach ($uploaded_files as $field => $files) {
            if (str_ends_with($field, '[]')) {
                $field = substr($field, 0, -2);
            }
            $file_prop = $schema['properties'][$field] ?? null;
            $file_upload_spec = $file_prop !== null ? self::getFileUploadSpecification($file_prop) : null;
            if ($file_upload_spec === null) {
                continue;
            }
            $is_array_of_files = $file_upload_spec['is_array'];
            $input_name = $file_upload_spec['definition']['x-input-field'] ?? $field;
            $upload_as = $file_upload_spec['options']['upload_as'] ?? FileManager::UPLOAD_AS_DOCUMENT;

            if (!$is_array_of_files && count($files) > 1) {
                // Throwing exception here because this should of been caught in the validation step.
                throw new RuntimeException("Expected at most one uploaded file for '{$field}'");
            }

            /** @var HashedUploadedFile $file */
            foreach ($files as $file) {
                if ($upload_as === FileManager::UPLOAD_AS_FILE) {
                    $result = FileManager::uploadFile($file, $rollback_journal);
                    if (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        $new_input = array_merge($new_input, $result);
                    }
                } elseif ($upload_as === FileManager::UPLOAD_AS_DOCUMENT) {
                    $mime = $file->getClientMediaType();
                    $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                    if (!FileManager::isDocumentUploadAllowed($mime, $ext)) {
                        throw new FileUploadException($field, 'File upload failed: Document could not be created', UPLOAD_ERR_CANT_WRITE);
                    }
                    $result = FileManager::uploadAsDocument($file, $item->getEntityID() > 0 ? $item->getEntityID() : 0, $item->isRecursive(), $rollback_journal);
                    if (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        self::assignUploadedFileInputValue($new_input, $input_name, $result->getID(), $is_array_of_files);
                    }
                } elseif ($upload_as === FileManager::UPLOAD_AS_PICTURE) {
                    $result = FileManager::uploadAsPicture($file, $rollback_journal);
                    if (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        if (!$is_array_of_files) {
                            $existing_picture_path = FileManager::normalizePictureClientValue((string) ($item->fields[$input_name] ?? ''));
                            if ($existing_picture_path !== null && $existing_picture_path !== $result['filepath']) {
                                $rollback_journal['deferred_picture_deletions'][] = $existing_picture_path;
                            }
                        }
                        self::assignUploadedFileInputValue($new_input, $input_name, $result['filepath'], $is_array_of_files);
                    }
                }
            }
        }

        if ($new_input !== []) {
            $new_input['id'] = $item->getID();
            if (!$item->update($new_input)) {
                throw new RuntimeException('Failed to handle post-create/update actions');
            }
        }
    }

    /**
     * Assign an uploaded file's resulting input value onto the "new input" array used to update the item after
     * upload handling, merging it into the existing array when the target property accepts multiple files.
     * @param array<string, mixed> $new_input The input array to update, passed by reference
     * @param string $input_name The internal field name to assign the value to
     * @param mixed $value The value to assign (e.g. a document ID or a picture file path)
     * @param bool $is_array Whether the target property accepts multiple files
     * @return void
     */
    private static function assignUploadedFileInputValue(array &$new_input, string $input_name, mixed $value, bool $is_array): void
    {
        if ($is_array) {
            if (!is_array($new_input[$input_name] ?? null)) {
                // Should never happen but needed for PHPStan to be happy
                $new_input[$input_name] = [];
            }
            $new_input[$input_name][] = $value;
        } else {
            $new_input[$input_name] = $value;
        }
    }

    /**
     * Delete the files and pictures created during a create/update that has since been rolled back.
     *
     * The rollback takes care of the DB records, but the files written to disk are outside the transaction and have
     * to be removed explicitly or they are left orphaned.
     *
     * @param RollbackJournal $rollback_journal
     * @return void
     */
    private static function cleanRolledBackUploads(array $rollback_journal): void
    {
        foreach ($rollback_journal['documents'] as $doc) {
            $doc->cleanFile();
        }

        foreach ($rollback_journal['files'] as $file_info) {
            FileManager::cleanUploadedFile($file_info['filepath'], $file_info['sha1sum']);
        }

        foreach (array_unique($rollback_journal['pictures']) as $picture_path) {
            FileManager::deletePicture($picture_path);
        }
    }

    /**
     * Delete pictures that were removed from existing items only after their database changes have committed.
     *
     * @param RollbackJournal $rollback_journal
     * @return void
     */
    private static function cleanCommittedPictureDeletions(array $rollback_journal): void
    {
        foreach (array_unique($rollback_journal['deferred_picture_deletions']) as $picture_path) {
            if (!FileManager::deletePicture($picture_path)) {
                trigger_error(sprintf('Failed to delete the picture %s', GLPI_PICTURE_DIR . '/' . $picture_path), E_USER_WARNING);
            }
        }
    }

    /**
     * Build a fresh, empty rollback journal.
     * @return RollbackJournal
     */
    private static function newRollbackJournal(): array
    {
        return [
            'documents' => [],
            'files' => [],
            'pictures' => [],
            'deferred_picture_deletions' => [],
        ];
    }

    /**
     * Run a create or update action inside a DB transaction, taking care of the commit/rollback bookkeeping and
     * uploaded file cleanup shared by {@link self::createBySchema()} and {@link self::updateBySchema()}.
     *
     * @param callable(array &$rollback_journal): (int|Response) $action Callable performing the actual add/update and
     *      any subsequent handling. It receives the rollback journal by reference and must return either:
     *      - A {@link Response}, to short-circuit with an error response. The transaction is then rolled back and
     *        any uploaded files/pictures created so far are cleaned up.
     *      - The ID of the created/updated item, once the action completed successfully. The transaction is then
     *        committed and any deferred picture deletions are applied.
     * @return int|Response The item ID returned by $action on success, or the Response it returned on failure.
     */
    private static function runCreateOrUpdateTransaction(callable $action): int|Response
    {
        global $DB;

        $DB->beginTransaction();
        $rollback_journal = self::newRollbackJournal();
        $must_roll_back = true;
        try {
            $result = $action($rollback_journal);
            if ($result instanceof Response) {
                return $result;
            }

            $DB->commit();
            self::cleanCommittedPictureDeletions($rollback_journal);
            $must_roll_back = false;

            return $result;
        } catch (FileUploadException $e) {
            return self::getFileUploadErrorResponse($e);
        } catch (Throwable $e) {
            return self::getGenericErrorResponse($e);
        } finally {
            if ($must_roll_back) {
                $DB->rollBack();
                self::cleanRolledBackUploads($rollback_journal);
            }
        }
    }

    /**
     * @param Document[] $created_documents
     * @param class-string<CommonDBTM> $itemtype
     * @return void
     */
    private static function linkCreatedDocumentsToItem(array $created_documents, int $items_id, string $itemtype): void
    {
        foreach ($created_documents as $doc) {
            $doc_item = new Document_Item();
            if (!$doc_item->add([
                'documents_id' => $doc->getID(),
                'items_id' => $items_id,
                'itemtype' => $itemtype,
                'timeline_position' => CommonITILObject::NO_TIMELINE,
            ])) {
                throw new RuntimeException('Failed to link uploaded document to item');
            }
        }
    }

    /**
     * Filter the schema properties based on the read restrictions.
     * @param array<string, mixed> $schema The schema
     * @param bool $is_graphql_mode Whether the schema is being used in GraphQL mode. If false, the x-graphql-only properties are filtered out.
     * @return array<string, mixed> The filtered schema
     */
    public static function applyFieldReadRestrictions(array $schema, bool $is_graphql_mode = false): array
    {
        $filtered_schema = $schema;

        if (!$is_graphql_mode) {
            foreach ($filtered_schema['properties'] as $key => $prop) {
                if ($prop['x-graphql-only'] ?? false) {
                    unset($filtered_schema['properties'][$key]);
                }
            }
        }

        return $filtered_schema;
    }

    /**
     * @param CommonDBTM $item
     * @param array<string, string[]> $headers
     * @return array<string, string> Array of failed preconditions. Empty array if all preconditions passed.
     * @throws \DateMalformedStringException
     */
    private static function validatePreconditions(CommonDBTM $item, array $headers): array
    {
        $failures = [];

        $item_date_mod = $item->fields['date_mod'] ?? null;

        if ($item_date_mod !== null && isset($headers['If-Unmodified-Since'])) {
            $if_unmodified_since = $headers['If-Unmodified-Since'];
            if (is_array($if_unmodified_since)) {
                $if_unmodified_since = $if_unmodified_since[0];
            }
            $item_last_update_dt = new DateTime($item_date_mod);
            $if_unmodified_since_dt = new DateTime($if_unmodified_since);
            if ($item_last_update_dt > $if_unmodified_since_dt) {
                $failures['If-Unmodified-Since'] = 'The item has been modified since the specified date';
            }
        } elseif ($item_date_mod !== null && isset($headers['If-Modified-Since'])) {
            $if_modified_since = $headers['If-Modified-Since'];
            if (is_array($if_modified_since)) {
                $if_modified_since = $if_modified_since[0];
            }
            $item_last_update_dt = new DateTime($item_date_mod);
            $if_modified_since_dt = new DateTime($if_modified_since);
            if ($item_last_update_dt <= $if_modified_since_dt) {
                $failures['If-Modified-Since'] = 'The item has not been modified since the specified date';
            }
        }
        return $failures;
    }

    /**
     * Update an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function updateBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $schema = self::applyFieldReadRestrictions($schema);
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        // Ignore entity updates. This needs to be done through the Transfer process
        // TODO This should probably be handled in a more generic way (support other fields that can be used during creation but not updates)
        if (array_key_exists('entity', $request_attrs)) {
            unset($request_attrs['entity']);
        }
        $errors = self::validateInputParamsBySchema($schema, $request_params, false);
        if ($errors !== []) {
            return new JSONResponse(
                AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, 'Invalid input parameters', $errors),
                400
            );
        }
        $item = self::getItemFromSchema($schema);
        if (!$item->getFromDB($items_id)) {
            return AbstractController::getNotFoundErrorResponse();
        }

        // Update permission checks do not use the $input parameter so we can check before even converting the input parameters
        if (!$item->can($items_id, UPDATE)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }

        $final_request = Router::getInstance()->getFinalRequest();
        if ($final_request !== null) {
            $precondition_failures = self::validatePreconditions($item, $final_request->getHeaders());
            if ($precondition_failures !== []) {
                return new JSONResponse(
                    AbstractController::getErrorResponseBody(AbstractController::ERROR_PRECONDITION_FAILED, 'Precondition failed', $precondition_failures),
                    412
                );
            }
        }

        $input = self::getInputParamsBySchema($schema, $request_params);
        $input['id'] = $items_id;

        $transaction_result = self::runCreateOrUpdateTransaction(static function (array &$rollback_journal) use ($schema, $item, $items_id, $request_params, $input): int|Response {
            /** @var Document[] $created_documents */
            $created_documents = [];
            $input_for_rich_text_handling = $input;
            if (!($item instanceof Entity) && $item->isEntityAssign()) {
                $input_for_rich_text_handling['entities_id'] = $item->getEntityID();
                $input_for_rich_text_handling['is_recursive'] = $item->isRecursive();
            }
            $input = self::handleRichTextInputs($schema, $input_for_rich_text_handling, $created_documents, $rollback_journal);
            $result = $item->update($input);

            if ($result === false) {
                return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_UPDATE);
            }

            self::handlePostCreateOrUpdate($item, $schema, $request_params, $input, $rollback_journal);
            self::linkCreatedDocumentsToItem($created_documents, $items_id, $item::class);

            return $items_id;
        });

        if ($transaction_result instanceof Response) {
            return $transaction_result;
        }

        // We should return the updated item but we NEVER return the GLPI item fields directly. Need to use special API methods.
        return self::getOneBySchema($schema, $request_attrs + ['id' => $items_id], $request_params);
    }

    /**
     * Create an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_params The request parameters
     * @param array $get_route The GET route to use to get the created item. This should be an array containing the controller class and method.
     * @phpstan-param array{0: class-string<AbstractController>, 1: string} $get_route
     * @param array $extra_get_route_params Additional parameters needed to generate the GET route. This should only be needed for complex routes.
     *      This is used to re-map the parameters to the GET route.
     *      The array can contain an 'id' property which is the name of the parameter that the resulting ID is set to ('id' by default).
     *      The array may also contain a 'mapped' property which is an array of parameter names and static values.
     *      For example ['mapped' => ['subitem_type' => 'Followup']] would set the 'subitem_type' parameter to 'Followup'.
     * @return Response
     */
    public static function createBySchema(array $schema, array $request_params, array $get_route, array $extra_get_route_params = []): Response
    {
        $schema = self::applyFieldReadRestrictions($schema);
        if (!isset($request_params['entity']) && isset($_SESSION['glpiactive_entity'])) {
            $request_params['entity'] = $_SESSION['glpiactive_entity'];
        }
        $errors = self::validateInputParamsBySchema($schema, $request_params, true);
        if ($errors !== []) {
            return new JSONResponse(
                AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, 'Invalid input parameters', $errors),
                400
            );
        }

        $input = self::getInputParamsBySchema($schema, $request_params);
        $item = self::getItemFromSchema($schema);
        // Check permissions now that we have the main input parameters. Inline images in HTML content are handled later but should not affect permissions.
        if (!$item->can($item->getID(), CREATE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }

        $transaction_result = self::runCreateOrUpdateTransaction(static function (array &$rollback_journal) use ($schema, $item, $request_params, $input): int|Response {
            /** @var Document[] $created_documents */
            $created_documents = [];
            $input = self::handleRichTextInputs($schema, $input, $created_documents, $rollback_journal);
            $items_id = $item->add($input);

            if (!$items_id) {
                return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_CREATE);
            }

            self::handlePostCreateOrUpdate($item, $schema, $request_params, $input, $rollback_journal);
            self::linkCreatedDocumentsToItem($created_documents, $items_id, $item::class);

            return $items_id;
        });

        if ($transaction_result instanceof Response) {
            return $transaction_result;
        }

        $items_id = $transaction_result;

        [$controller, $method] = $get_route;

        $id_field = $extra_get_route_params['id'] ?? 'id';
        $request_params[$id_field] = $items_id;
        if (array_key_exists('mapped', $extra_get_route_params)) {
            foreach ($extra_get_route_params['mapped'] as $key => $value) {
                $request_params[$key] = $value;
            }
        }

        return AbstractController::getCRUDCreateResponse($items_id, $controller::getAPIPathForRouteFunction($controller, $method, $request_params));
    }


    /**
     * Search items using the given schema and request parameters.
     * Public entry point for {@link Search::getSearchResultsBySchema()} method.
     * @param array $schema
     * @param array $request_params
     * @return Response
     */
    public static function searchBySchema(array $schema, array $request_params): Response
    {
        $schema = self::applyFieldReadRestrictions($schema);
        $itemtype = self::getItemtypeFromSchema($schema);
        // No item-level checks done here. They are handled when generating the SQL using the x-rights-condtions schema property
        if (($itemtype !== null) && !$itemtype::canView()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        if (isset($schema['x-subtypes'])) {
            // For this case, we need to filter out the schemas that the user doesn't have read rights on
            $schemas = $schema['x-subtypes'];
            $schemas = array_filter($schemas, static function ($v) {
                $itemtype = $v['itemtype'];
                if (class_exists($itemtype) && is_subclass_of($itemtype, CommonDBTM::class)) {
                    return $itemtype::canView();
                }
                return false;
            });
            $schema['x-subtypes'] = $schemas;
            if (empty($schema['x-subtypes'])) {
                // No right on any subtypes. Could be useful to return an access denied error here instead of an empty list
                return AbstractController::getAccessDeniedErrorResponse();
            }
        }
        try {
            $results = Search::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getMessage(), $e->getDetails()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage(), $e->getDetails()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            return self::getGenericErrorResponse($e);
        }
        $has_more = $results['start'] + $results['limit'] < $results['total'];
        $end = max(0, ($results['start'] + $results['limit'] - 1));
        if ($end > $results['total']) {
            $end = $results['total'] - 1;
        }
        return new JSONResponse($results['results'], $has_more ? 206 : 200, [
            'Content-Range' => $results['start'] . '-' . $end . '/' . $results['total'],
        ]);
    }

    /**
     * Get a single item of the given schema, request data and unique field.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     * @see ResourceAccessor::searchBySchema()
     */
    public static function getOneBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $schema = self::applyFieldReadRestrictions($schema);
        $itemtype = self::getItemtypeFromSchema($schema);
        // No item-level checks done here. They are handled when generating the SQL using the x-rights-condtions schema property
        if (($itemtype !== null) && !$itemtype::canView()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        // Shortcut implementation using the search functionality with an injected RSQL filter and returning the first result.
        // This shouldn't have much if any unneeded overhead as the filter would be mapped to a SQL condition.
        $filters = $request_params['filter'] ?? '';
        $filters .= ';' . $field . '==' . $request_attrs[$field];
        $request_params['filter'] = $filters;
        $request_params['limit'] = 1;
        unset($request_params['start']);
        try {
            $results = Search::getSearchResultsBySchema($schema, $request_params);
        } catch (RSQLException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_INVALID_PARAMETER, $e->getUserMessage()), 400);
        } catch (APIException $e) {
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $e->getUserMessage()), $e->getCode() ?: 400);
        } catch (Throwable $e) {
            return self::getGenericErrorResponse($e);
        }
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }

        $result = $results['results'][0];

        $final_request = Router::getInstance()->getFinalRequest();
        if ($final_request !== null && !empty($result['date_mod'])) {
            $item = self::getItemFromSchema($schema);
            $item->fields['date_mod'] = $result['date_mod'];
            $precondition_failures = self::validatePreconditions($item, $final_request->getHeaders());
            if ($precondition_failures !== []) {
                return new JSONResponse(
                    AbstractController::getErrorResponseBody(AbstractController::ERROR_PRECONDITION_FAILED, 'Precondition failed', $precondition_failures),
                    array_key_exists('If-Modified-Since', $precondition_failures) ? 304 : 412
                );
            }
        }

        return new JSONResponse($result);
    }

    /**
     * Delete an item of the given schema using the given request parameters.
     * @param array $schema The schema
     * @param array $request_attrs The request attributes
     * @param array $request_params The request parameters
     * @param string $field The unique field to match on. Defaults to ID. If different, the ID is resolved from the given other unique field.
     * The field must be present in the route path (request attributes).
     * @return Response
     * @see self::getIDForOtherUniqueFieldBySchema()
     */
    public static function deleteBySchema(array $schema, array $request_attrs, array $request_params, string $field = 'id'): Response
    {
        $items_id = $field === 'id' ? $request_attrs['id'] : self::getIDForOtherUniqueFieldBySchema($schema, $field, $request_attrs[$field]);
        $item = self::getItemFromSchema($schema);
        $force = filter_var($request_params['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $input = ['id' => (int) $items_id];
        $purge = !$item->maybeDeleted() || $force;
        if (!$item->can($items_id, $purge ? PURGE : DELETE, $input)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        $result = $item->delete($input, $purge);

        if ($result === false) {
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_DELETE);
        }
        return new JSONResponse(null, 204);
    }
}
