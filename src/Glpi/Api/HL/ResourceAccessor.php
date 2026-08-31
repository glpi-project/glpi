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
use Document;
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
     * Map the request parameters to the format required for the GLPI add/update methods.
     * Only top-level properties are mapped.
     * Nested properties which would represent relations are not supported.
     * Creating/updating relations should be done using the appropriate endpoints.
     * @param array $schema
     * @param array $request_params
     * @param CommonDBTM|null $existing_item The existing item for update operations.
     * @return array
     */
    public static function getInputParamsBySchema(array $schema, array $request_params, ?CommonDBTM $existing_item = null): array
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

            if (isset($prop['x-file-removal-options'])) {
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

        foreach ($flattened_properties as $key => $prop) {
            $file_upload_options = null;
            if (isset($prop['x-file-upload-options'])) {
                $file_upload_options = $prop['x-file-upload-options'];
            } elseif (($prop['type'] ?? null) === Doc\Schema::TYPE_ARRAY && isset($prop['items']['x-file-upload-options'])) {
                $file_upload_options = $prop['items']['x-file-upload-options'];
            }

            if ($file_upload_options !== null && isset($uploaded_files[$key])) {
                foreach ($uploaded_files[$key] as $file) {
                    // Validate file upload options

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
     * @return array<string, mixed> The modified input parameters with inline images handled
     */
    private static function handleRichTextInputs(array $schema, array $input, array &$created_documents): array
    {
        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        foreach ($flattened_properties as $prop_name => $prop) {
            if (isset($prop['format']) && $prop['format'] === Doc\Schema::FORMAT_STRING_HTML) {
                if (isset($prop['x-supports-inline-images'])) {
                    // Need to extract base64 data uris from img tags and upload them as documents, replacing the src with the document URL
                    $html = ArrayPathAccessor::getElementByArrayPath($input, $prop_name);
                    if ($html !== null && ($html = FileManager::handleInlineImagesInHTML($html, $created_documents)) !== false) {
                        ArrayPathAccessor::setElementByArrayPath($input, $prop_name, $html);
                    }
                }
            }
        }
        return $input;
    }

    /**
     * Handles any actions that should happen after the creation or update of an item is successful.
     *
     * @param CommonDBTM $item The item that was created or updated
     * @param array<string, mixed> $schema The schema of the item
     * @param array<string, mixed> $request_params The request parameters used for the creation or update
     * @param array<string, mixed> $input The input parameters that were used for the creation or update.
     * May also include some internal-only fields that were added during the input parameter mapping process that are required for post-action handling.
     * @return void
     */
    private static function handlePostCreateOrUpdate(CommonDBTM $item, array $schema, array $request_params, array $input): void
    {
        $new_input = [];

        $flattened_properties = Doc\Schema::flattenProperties($schema['properties']);
        $joins = Doc\Schema::getJoins($schema['properties']);
        $writable_props = array_filter($flattened_properties, static function ($v, $k) use ($joins) {
            $base_k = strstr($k, '.', true) ?: $k;
            return !isset($joins[$base_k]);
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($writable_props as $prop_name => $prop) {
            $internal_name = self::resolveInternalFieldNameForProperty($prop_name, $prop);

            // Handle single-file removals
            if (
                ($prop['format'] ?? null) === Doc\Schema::FORMAT_STRING_BINARY
                && ArrayPathAccessor::getElementByArrayPath($request_params, $prop_name) === ''
                && !empty($item?->fields[$internal_name])
            ) {
                $upload_as = $prop['x-file-upload-options']['upload_as'] ?? FileManager::UPLOAD_AS_DOCUMENT;

                if ($upload_as === FileManager::UPLOAD_AS_PICTURE) {
                    if (FileManager::deletePicture($item->fields[$internal_name])) {
                        $new_input[$internal_name] = null;
                    } else {
                        throw new FileUploadException($prop_name, 'File removal failed', 0, null, null, 'file_removal_failed');
                    }
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
            /** @var HashedUploadedFile $file */
            foreach ($files as $file) {
                $is_array_of_files = $file_prop !== null
                    && $file_prop['type'] === Doc\Schema::TYPE_ARRAY
                    && isset($file_prop['items']['x-file-upload-options']);
                $file_upload_options = $is_array_of_files ? $file_prop['items']['x-file-upload-options'] : $file_prop['x-file-upload-options'] ?? null;

                if ($file_prop === null || $file_upload_options === null) {
                    continue;
                }
                $input_name = ($is_array_of_files ? ($file_prop['items']['x-input-field'] ?? $field) : ($file_prop['x-input-field']) ?? $field);
                $upload_as = $file_upload_options['upload_as'] ?? FileManager::UPLOAD_AS_DOCUMENT;

                if ($upload_as === 'file') {
                    $result = FileManager::uploadFile($file);
                    if (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        $new_input = array_merge($new_input, $result);
                    }
                } elseif ($upload_as === 'document') {
                    $mime = $file->getClientMediaType();
                    $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                    if (!FileManager::isDocumentUploadAllowed($mime, $ext)) {
                        throw new FileUploadException($field, 'File upload failed: Document could not be created', UPLOAD_ERR_CANT_WRITE);
                    }
                    $result = FileManager::uploadAsDocument($file);
                    if ($result === null) {
                        throw new FileUploadException($field, 'File upload failed: Document could not be created', UPLOAD_ERR_CANT_WRITE);
                    } elseif (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        $document_id = $result->getID();
                        if ($is_array_of_files) {
                            if (!is_array($new_input[$input_name] ?? null)) {
                                // Should never happen but needed for PHPStan to be happy
                                $new_input[$input_name] = [];
                            }
                            $new_input[$input_name][] = $document_id;
                        } else {
                            $new_input[$input_name] = $document_id;
                        }
                    }
                } elseif ($upload_as === 'picture') {
                    $result = FileManager::uploadAsPicture($file);
                    if (is_int($result)) {
                        throw new FileUploadException($field, 'File upload failed with error code ' . $result, $result);
                    } else {
                        if ($is_array_of_files) {
                            if (!is_array($new_input[$input_name] ?? null)) {
                                // Should never happen but needed for PHPStan to be happy
                                $new_input[$input_name] = [];
                            }
                            $new_input[$input_name][] = $result['filepath'];
                        } else {
                            $new_input[$input_name] = $result['filepath'];
                        }
                    }
                }
            }
        }

        if ($new_input !== []) {
            $new_input['id'] = $item->getID();
            if (!$item->update($new_input, false)) {
                throw new RuntimeException('Failed to handle post-create/update actions');
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
        global $DB;

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

        $input = self::getInputParamsBySchema($schema, $request_params, $item);
        $input['id'] = $items_id;

        $DB->beginTransaction();
        /** @var Document[] $created_documents */
        $created_documents = [];
        $input = self::handleRichTextInputs($schema, $input, $created_documents);
        $result = $item->update($input);

        if ($result === false) {
            $DB->rollBack();
            foreach ($created_documents as $doc) {
                // The actual DB records are handled by the rollback, but the actual files still need cleaned manually.
                // Ideally, we should almost never get here as the HLAPI should catch potential input issues before the item update is attempted.
                $doc->cleanFile();
            }
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_UPDATE);
        }

        try {
            self::handlePostCreateOrUpdate($item, $schema, $request_params, $input);
        } catch (Throwable $e) {
            $DB->rollBack();
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        $DB->commit();

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
        global $DB;

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

        $DB->beginTransaction();
        /** @var Document[] $created_documents */
        $created_documents = [];
        $input = self::handleRichTextInputs($schema, $input, $created_documents);
        $items_id = $item->add($input);

        if ($items_id) {
            try {
                self::handlePostCreateOrUpdate($item, $schema, $request_params, $input);
            } catch (Throwable $e) {
                $DB->rollBack();
                $message = (new APIException())->getUserMessage();
                $detail = null;
                if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                    $detail = $e->getMessage();
                }
                return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
            }
        } else {
            $DB->rollBack();
            foreach ($created_documents as $doc) {
                // The actual DB records are handled by the rollback, but the actual files still need cleaned manually.
                // Ideally, we should almost never get here as the HLAPI should catch potential input issues before the item creation is attempted.
                $doc->cleanFile();
            }
            return AbstractController::getCRUDErrorResponse(AbstractController::CRUD_ACTION_CREATE);
        }
        $DB->commit();

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
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
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
            $message = (new APIException())->getUserMessage();
            $detail = null;
            if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
                $detail = $e->getMessage();
            }
            return new JSONResponse(AbstractController::getErrorResponseBody(AbstractController::ERROR_GENERIC, $message, $detail), 500);
        }
        if (count($results['results']) === 0) {
            return AbstractController::getNotFoundErrorResponse();
        }
        return new JSONResponse($results['results'][0]);
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
