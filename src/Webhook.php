<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\Controller\CustomAssetController;
use Glpi\Api\HL\Controller\ITILController;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Router;
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\AssetDefinition;
use Glpi\ContentTemplates\TemplateManager;
use Glpi\Error\ErrorHandler;
use Glpi\Features\Clonable;
use Glpi\Http\Request;
use Glpi\Search\FilterableInterface;
use Glpi\Search\FilterableTrait;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Twig\Extra\Markdown\MarkdownExtension;

use function Safe\json_decode;
use function Safe\json_encode;

class Webhook extends CommonDBTM implements FilterableInterface
{
    use Clonable;
    use FilterableTrait;

    public static $rightname         = 'config';

    // From CommonDBTM
    public $dohistory                = true;

    public static $undisclosedFields = [
        'secret', 'clientsecret',
    ];

    public function getCloneRelations(): array
    {
        return [
            Notepad::class,
        ];
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb([
            QueuedWebhook::class,
        ]);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Webhook', 'Webhooks', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public static function canDelete(): bool
    {
        return static::canUpdate();
    }

    public function defineTabs($options = [])
    {
        $parent_tabs = parent::defineTabs();
        $tabs = [
            // Main tab retrieved from parents
            array_keys($parent_tabs)[0] => array_shift($parent_tabs),
            array_keys($parent_tabs)[0] => array_shift($parent_tabs),
        ];

        $this->addStandardTab(self::class, $tabs, $options);
        // Add common tabs
        $tabs = array_merge($tabs, $parent_tabs);
        $this->addStandardTab(Log::class, $tabs, $options);

        // Final order of tabs: main, filter, payload editor, queries, test, historical
        return $tabs;
    }

    public function rawSearchOptions()
    {

        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => self::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => self::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => self::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => self::getTable(),
            'field'              => 'event',
            'name'               => _n('Event', 'Events', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => [
                'itemtype',
            ],
            'searchtype'         => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => 'glpi_webhookcategories',
            'field'              => 'completename',
            'name'               => WebhookCategory::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'itemtype':
                if (isset($values[$field]) && class_exists($values[$field])) {
                    return htmlescape($values[$field]::getTypeName(0));
                }
                break;
            case 'event':
                if (!empty($values['itemtype'])) {
                    $label = NotificationEvent::getEventName($values['itemtype'], $values[$field]);
                    if ($label === NOT_AVAILABLE) {
                        return htmlescape(self::getDefaultEventsListLabel($values[$field]));
                    }
                    return htmlescape($label);
                }
                break;
            case 'http_method':
                return htmlescape(self::getHttpMethod()[$values[$field]]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'itemtype':
                return Dropdown::showFromArray(
                    $name,
                    self::getItemtypesDropdownValues(),
                    [
                        'display'             => false,
                        'display_emptychoice' => true,
                        'value'               => $values[$field],
                    ]
                );
            case 'event':
                $recursive_search = static function ($list_itemtype) use (&$recursive_search) {
                    /**
                     * @var array<class-string<CommonDBTM>, string> $list_itemtype
                     */
                    $events = [];
                    foreach ($list_itemtype as $itemtype => $itemtype_label) {
                        if (is_array($itemtype_label)) {
                            $events += $recursive_search($itemtype_label);
                        } else {
                            if (isset($itemtype) && class_exists($itemtype)) {
                                $target = NotificationTarget::getInstanceByType($itemtype);
                                if ($target) {
                                    $events[$itemtype::getTypeName(0)] = $target->getAllEvents();
                                } else {
                                    //return standard CRUD
                                    $events[$itemtype::getTypeName(0)] = self::getDefaultEventsList();
                                }
                            }
                        }
                    }
                    return $events;
                };

                $events = $recursive_search(self::getItemtypesDropdownValues());
                return Dropdown::showFromArray(
                    $name,
                    $events,
                    [
                        'display'             => false,
                        'display_emptychoice' => true,
                        'value'               => $values[$field],
                    ]
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Return a list of GLPI events that are valid for an itemtype.
     *
     * @param class-string<CommonDBTM>|null $itemtype
     * @return array
     */
    public static function getGlpiEventsList(?string $itemtype): array
    {
        if ($itemtype !== null && class_exists($itemtype)) {
            return self::getDefaultEventsList();
        } else {
            return [];
        }
    }

    /**
    * Return a list of default events.
    *
    * @return array
    */
    public static function getDefaultEventsList(): array
    {
        return [
            'new' => __("New"),
            'update' => __('Update'),
            'delete' => __("Delete"),
        ];
    }

    /**
     * Return default event name.
     *
     * @param string $event_name
     * @return string
     */
    public static function getDefaultEventsListLabel($event_name): string
    {
        $events = static::getDefaultEventsList();
        return $events[$event_name] ?? NOT_AVAILABLE;
    }

    /**
    * Return a list of HTTP methods.
    *
    * @return array
    */
    public static function getHttpMethod(): array
    {
        return [
            'post'      => 'POST',
            'get'       => 'GET',
            'update'    => 'UPDATE',
            'patch'     => 'PATCH',
            'put'       => 'PUT',
        ];
    }

    /**
    * Return status icon
    *
    * @return string
    */
    public static function getStatusIcon($status): string
    {
        if ($status) {
            return '<i class="ti ti-alert-triangle icon-pulse fs-2" style="color: #ff0000;"></i>';
        } else {
            return '<i class="ti ti-circle-check icon-pulse fs-2" style="color: #36d601;"></i>';
        }
    }

    /**
     * @return array<class-string<AbstractController>, array{
     *     main: array<class-string<CommonDBTM>, array{name: string}>,
     *     subtypes?: array<class-string<CommonDBTM>, array{name: string, parent: class-string<CommonDBTM>}|array{}>
     * }>
     */
    public static function getAPIItemtypeData(): array
    {
        static $supported = null;

        if ($supported === null || Environment::get()->shouldExpectResourcesToChange()) {
            $supported = [
                AssetController::class => [
                    'main' => AssetController::getAssetTypes(),
                ],
                CustomAssetController::class => [
                    'main' => array_map(
                        static fn($c) => AssetDefinition::getCustomObjectNamespace() . '\\' . $c . AssetDefinition::getCustomObjectClassSuffix(),
                        CustomAssetController::getCustomAssetTypes()
                    ),
                ],
                ITILController::class => [
                    'main' => [Ticket::class, Change::class, Problem::class],
                    'subtypes' => [
                        TicketTask::class => ['parent' => Ticket::class],
                        ChangeTask::class => ['parent' => Change::class],
                        ProblemTask::class => ['parent' => Problem::class],
                        ITILFollowup::class => [], // All main types can be the parent
                        ITILSolution::class => [],
                        TicketValidation::class => ['parent' => Ticket::class],
                    ],
                ],
                ManagementController::class => [
                    'main' => [
                        Appliance::class, Budget::class, Certificate::class, Cluster::class, Contact::class,
                        Contract::class, Database::class, Datacenter::class, Document::class, Domain::class,
                        SoftwareLicense::class, Line::class, Supplier::class,
                    ],
                    'subtypes' => [
                        Document_Item::class => ['parent' => Document::class],
                    ],
                ],
            ];

            /**
             * @param class-string<CommonDBTM> $itemtype
             * @param array $schemas
             * @return array|null
             * @phpstan-return array{name: string, schema: array}|null
             */
            $fn_get_schema_by_itemtype = static function (string $itemtype, array $schemas) {
                $match = null;
                foreach ($schemas as $schema_name => $schema) {
                    if (isset($schema['x-itemtype']) && $schema['x-itemtype'] === $itemtype) {
                        $match = [
                            'name' => $schema_name,
                            'schema' => $schema,
                        ];
                        break;
                    }
                }
                return $match;
            };

            /**
             * @phpstan-var class-string<AbstractController> $controller
             */
            foreach ($supported as $controller => $categories) {
                // TODO Allow pinning webhooks to specific API versions
                $schemas = $controller::getKnownSchemas(Router::API_VERSION);
                foreach ($categories as $category => $itemtypes) {
                    if ($category === 'main') {
                        foreach ($itemtypes as $i => $supported_itemtype) {
                            $schema = $fn_get_schema_by_itemtype($supported_itemtype, $schemas);
                            if ($schema) {
                                $supported[$controller][$category][$supported_itemtype] = [
                                    'name' => $schema['name'],
                                ];
                            }
                            unset($supported[$controller][$category][$i]);
                        }
                    } elseif ($controller === ITILController::class) {
                        foreach (array_keys($itemtypes) as $supported_itemtype) {
                            $supported[$controller][$category][$supported_itemtype]['name'] = $controller::getFriendlyNameForSubtype($supported_itemtype);
                        }
                    }
                }
            }
        }

        return $supported;
    }

    /**
    * Return a list of GLPI itemtypes available through HL API.
    *
    * @return array<array>
    */
    public static function getItemtypesDropdownValues(): array
    {
        $values = [];
        $supported = self::getAPIItemtypeData();

        $values[__('Assets')] = array_keys([...$supported[AssetController::class]['main'], ...$supported[CustomAssetController::class]['main']]);
        $values[__('Assistance')] = array_merge(
            array_keys($supported[ITILController::class]['main']),
            array_keys($supported[ITILController::class]['subtypes'])
        );
        $values[__('Management')] = array_keys($supported[ManagementController::class]['main']);

        // Move leaf values to the keys and make the value the ::getTypeName
        foreach ($values as $category => $itemtypes) {
            foreach ($itemtypes as $i => $itemtype) {
                $values[$category][$itemtype] = $itemtype::getTypeName(1);
                unset($values[$category][$i]);
            }
        }

        return $values;
    }

    public static function getSubItemForAssistance(): array
    {
        $sub_item = [
            'ITILFollowup' => ITILFollowup::getTypeName(0),
            'Document_Item' => Document_Item::getTypeName(0),
            'ITILSolution' => ITILSolution::getTypeName(0),
        ];

        $itil_types = [Ticket::class, Change::class, Problem::class];
        foreach ($itil_types as $itil_type) {
            if ($itil_type::getValidationClassInstance() !== null) {
                $sub_item[$itil_type::getValidationClassInstance()::class] = $itil_type::getValidationClassInstance()::class::getTypeName(0);
            }

            if ($itil_type::getTaskClass() !== null) {
                $sub_item[$itil_type::getTaskClass()] = $itil_type::getTaskClass()::getTypeName(0);
            }
        }
        return $sub_item;
    }

    private function getAPIResponse(string $path): array
    {
        $router = Router::getInstance();
        $router->registerAuthMiddleware(new InternalAuthMiddleware());
        $path = rtrim($path, '/');
        $request = new Request('GET', $path);
        $response = Session::callAsSystem(static fn() => $router->handleRequest($request));
        if ($response->getStatusCode() === 200) {
            $body = (string) $response->getBody();
            try {
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Failed to decode API response from ' . $path . ': ' . $e->getMessage(), $e->getCode(), $e);
            }
            return $data;
        }
        throw new RuntimeException('Failed to get API response from ' . $path . ': HTTP ' . $response->getStatusCode());
    }

    /**
     * Get the body of a webhook request for the given event and API data.
     * In some cases, the provided itemtype and items_id may be used to inject some information about the parent item into a top-level 'parent_item' object.
     * @param string $event The event to use in the payload.
     * @param array $api_data The data to use in the payload.
     * @param string $itemtype The related itemtype.
     * @param int $items_id The related items_id.
     * @param bool $raw_output Whether to return the raw JSON or process it through the payload template.
     * @return string|null
     */
    private function getWebhookBody(string $event, array $api_data, string $itemtype, int $items_id, bool $raw_output = false): ?string
    {
        $data = [
            'item' => $api_data,
            'event' => $event,
        ];
        $this->addParentItemData($data, $itemtype, $items_id);
        if ($raw_output) {
            return json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $payload_template = $this->fields['payload'] ?? null;
            if ($this->fields['use_default_payload'] === 1) {
                $payload_template = null;
            }
            if (!empty($payload_template)) {
                $fn_desanitize = static function ($value) use (&$fn_desanitize) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            $value[$k] = $fn_desanitize($v);
                        }
                    } elseif (is_string($value)) {
                        // slash double quotes
                        $value = str_replace('"', '\\"', $value);
                    }
                    return $value;
                };
                $data = $fn_desanitize($data);
                try {
                    return TemplateManager::render($payload_template, $data, false, [new MarkdownExtension()]);
                } catch (Throwable $e) {
                    return null;
                }
            } else {
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        }
    }

    /**
     * @param string $itemtype The itemtype to get the parent item schema for.
     * @return array
     */
    private static function getParentItemSchema(string $itemtype): array
    {
        $supported = self::getAPIItemtypeData();
        $parent_itemtypes = [];
        foreach ($supported as $controller => $categories) {
            if (isset($categories['subtypes']) && array_key_exists($itemtype, $categories['subtypes'])) {
                $parent_itemtypes = $categories['main'];
                break;
            }
        }

        if (count($parent_itemtypes) === 0) {
            return [];
        }

        if (count($parent_itemtypes) === 1) {
            $parent_itemtype = array_key_first($parent_itemtypes);
            return self::getAPISchemaBySupportedItemtype($parent_itemtype);
        }
        $schema = [
            'type' => 'object',
            'x-subtypes' => [],
            'properties' => [],
        ];
        foreach ($parent_itemtypes as $parent_itemtype => $parent_itemtype_data) {
            $parent_schema = self::getAPISchemaBySupportedItemtype($parent_itemtype);
            $schema['x-subtypes'][] = [
                'itemtype' => $parent_itemtype,
                'schema_name' => $parent_itemtype_data['name'],
            ];
            foreach ($parent_schema['properties'] as $property_name => $property_data) {
                // Save current parents
                $existing_parents = $schema['properties'][$property_name]['x-parent-itemtype'] ?? [];
                // Add the 'new' property (or overwrite)
                $schema['properties'][$property_name] = $property_data;
                // Merge the existing parents with the new one
                $schema['properties'][$property_name]['x-parent-itemtype'] = array_merge($existing_parents, [$parent_itemtype]);
            }
        }
        return $schema;
    }

    /**
     * @param array $data
     * @param class-string<CommonDBTM> $itemtype
     * @param int $items_id
     * @return void
     */
    private function addParentItemData(array &$data, string $itemtype, int $items_id): void
    {
        if (is_subclass_of($itemtype, CommonDBChild::class)) {
            $parent_itemtype = $data['item']['itemtype'];
            $parent_id = $data['item']['items_id'];
        } elseif (is_subclass_of($itemtype, CommonITILTask::class)) {
            /** @var class-string<CommonDBTM> $parent_itemtype */
            $parent_itemtype = str_replace('Task', '', $itemtype);
            $parent_id = $data['item'][$parent_itemtype::getForeignKeyField()];
        } else {
            return;
        }
        $parent_schema = self::getParentItemSchema($itemtype);
        // filter properties in parent schema by the resolved parent itemtype (checks the x-parent-itemtype property)
        foreach ($parent_schema['properties'] as $property_name => $property_data) {
            if (in_array($parent_itemtype, $property_data['x-parent-itemtype'] ?? [], true)) {
                $parent_schema['properties'][$property_name] = $property_data;
            } else {
                unset($parent_schema['properties'][$property_name]);
            }
        }
        $parent_schema['x-itemtype'] = $parent_itemtype;
        unset($parent_schema['x-subtypes']);
        $parent_result = ResourceAccessor::getOneBySchema($parent_schema, [
            'itemtype' => $parent_itemtype,
            'id' => $parent_id,
        ], []);
        $result = json_decode((string) $parent_result->getBody(), true);
        if (is_array($result)) {
            $data['parent_item'] = $result;
        }
    }

    /**
     * Get a result from the API for a given path.
     * In some cases, the provided itemtype and items_id may be used to inject some information about the parent item into a top-level 'parent_item' object.
     * @param string $path The API path to get the data from.
     * @param string $event The event to use in the payload.
     * @param class-string<CommonDBTM> $itemtype The itemtype related to the path.
     * @param int $items_id The items_id related to the path.
     * @param bool $raw_output Whether to return the raw JSON or process it through the payload template.
     * @return string|null
     */
    public function getResultForPath(string $path, string $event, string $itemtype, int $items_id, bool $raw_output = false): ?string
    {
        $data = $this->getAPIResponse($path);
        return $this->getWebhookBody($event, $data, $itemtype, $items_id, $raw_output);
    }

    public function getApiPath(CommonDBTM $item): string
    {
        $itemtype = $item->getType();
        $id = $item->getID();
        $itemtypes = self::getAPIItemtypeData();

        $controller = null;
        $api_name = null;
        $parent_itemtype = null;
        $parent_name = null;
        foreach ($itemtypes as $controller_class => $categories) {
            if (array_key_exists($itemtype, $categories['main'])) {
                $controller = $controller_class;
                if ($controller === CustomAssetController::class) {
                    $api_name = str_replace('CustomAsset_', '', $categories['main'][$itemtype]['name']);
                } else {
                    $api_name = $categories['main'][$itemtype]['name'];
                }
                break;
            }

            if (isset($categories['subtypes']) && array_key_exists($itemtype, $categories['subtypes'])) {
                $api_name = $categories['subtypes'][$itemtype]['name'];
                $controller = $controller_class;
                // Use the specified parent itemtype or the first main one if none is specified (all work)
                $parent_itemtype = $categories['subtypes'][$itemtype]['parent'] ?? array_key_first($categories['main']);
                break;
            }
        }

        if ($parent_itemtype !== null) {
            $parent_name = $itemtypes[$controller]['main'][$parent_itemtype]['name'];
        }

        $path = match ($controller) {
            AssetController::class => '/Assets/',
            CustomAssetController::class => '/Assets/Custom/',
            ITILController::class => '/Assistance/',
            ManagementController::class => '/Management/',
            default => '/_404/' // Nonsense path to trigger a 404
        };

        if ($parent_name !== null) {
            if ($item instanceof CommonDBChild) {
                $itemtype_field = $item::$itemtype;
                if ($itemtype_field === 'itemtype') {
                    $itemtype_value = $item->fields[$itemtype_field];
                } else {
                    $itemtype_value = $itemtype_field;
                }
                $parent_name = $itemtypes[$controller]['main'][$itemtype_value]['name'];
                $parent_id = $item->fields[$item::$items_id];
            } elseif ($item instanceof CommonDBRelation) {
                $itemtype_field = $item::$itemtype_2;
                if (str_starts_with($itemtype_field, "itemtype")) {
                    $itemtype_value = $item->fields[$itemtype_field];
                } else {
                    $itemtype_value = $itemtype_field;
                }
                $items_id_value = $item->fields[$item::$items_id_2];
                $parent_name = $itemtypes[$controller]['main'][$itemtype_value]['name'];
                $parent_id = $items_id_value;
            } elseif ($item instanceof CommonITILTask) {
                $parent_itemtype = $item::getItilObjectItemType();
                $parent_name = $itemtypes[$controller]['main'][$parent_itemtype]['name'];
                $parent_id = $item->fields[$parent_itemtype::getForeignKeyField()];
            }

            $path .= $parent_name . '/' . ($parent_id ?? 0) . '/';

            if ($controller === ITILController::class) {
                $path .= 'Timeline/';
            }
        }

        $path .= $api_name . '/' . $id;

        return $path;
    }

    public function showForm($id, array $options = [])
    {
        if (!empty($id)) {
            $this->getFromDB($id);

            //validate CRA if needed
            if (GLPI_WEBHOOK_CRA_MANDATORY || (isset($this->fields['use_cra_challenge']) && $this->fields['use_cra_challenge'])) {
                $response = self::validateCRAChallenge($this->fields['url'], 'validate_cra_challenge', $this->fields['secret']);
                if (!$response['status']) {
                    $this->fields['is_cra_challenge_valid'] = false;
                    $this->update($this->fields);
                }
            }
        } else {
            $this->getEmpty();
        }
        $this->initForm($id, $options);

        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhook.html.twig', [
            'item' => $this,
            'response_schema' => self::getMonacoSuggestions($this->fields['itemtype']),
        ]);

        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof self) {
            throw new RuntimeException("This tab is only available for Webhooks items");
        }

        $headers_count = count($item->fields['custom_headers']);
        if ($headers_count > 0) {
            // If there are custom headers, we will include the static ones in the count.
            // Otherwise, we won't show a count at all.
            $headers_count += 2;
        }

        $queries_count = 0;
        $params = $item->getSentQueriesSearchParams();
        $params['export_all'] = true;
        $data = Search::getDatas(QueuedWebhook::class, $params);
        if (isset($data['data']['totalcount'])) {
            $queries_count = $data['data']['totalcount'];
        }

        return [
            1 => self::createTabEntry(__('Security'), 0, $item::getType(), 'ti ti-shield-lock'),
            2 => self::createTabEntry(__('Payload editor'), 0, $item::getType(), 'ti ti-code-dots'),
            3 => self::createTabEntry(_n('Custom header', 'Custom headers', Session::getPluralNumber()), $headers_count, $item::getType(), 'ti ti-code-plus'),
            4 => self::createTabEntry(_n('Query log', 'Queries log', Session::getPluralNumber()), $queries_count, $item::getType(), 'ti ti-mail-forward'),
            5 => self::createTabEntry(__('Preview'), 0, $item::getType(), 'ti ti-eye-exclamation'),
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof self) {
            return false;
        }

        if ($tabnum === 1) {
            $item->showSecurityForm();
            return true;
        }

        if ($tabnum === 2) {
            $item->showPayloadEditor();
            return true;
        }

        if ($tabnum === 3) {
            $item->showCustomHeaders();
            return true;
        }

        if ($tabnum === 4) {
            $item->showSentQueries();
            return true;
        }

        if ($tabnum === 5) {
            $item->showPreviewForm();
            return true;
        }

        return false;
    }

    private function showSecurityForm(): void
    {
        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhook_security.html.twig', [
            'item' => $this,
            'params' => [
                'candel' => false,
                'formfooter' => false,
            ],
        ]);
    }

    private function showCustomHeaders(): void
    {
        $schema = self::getAPISchemaBySupportedItemtype($this->fields['itemtype']);
        $item_fields = Doc\Schema::flattenProperties($schema['properties'], 'item.');
        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhook_headers.html.twig', [
            'item' => $this,
            'item_fields' => $item_fields,
            'response_schema' => self::getMonacoSuggestions($this->fields['itemtype']),
            'params' => [
                'candel' => false,
                'formfooter' => false,
            ],
        ]);
    }

    private function showPreviewForm(): void
    {
        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhooktest.html.twig', [
            'item' => $this,
            'params' => [
                'canedit' => false,
                'candel' => false,
                'formfooter' => false,
            ],
        ]);
    }

    /**
     * @param array $schema The API schema used to generate the payload
     * @return string The default payload as a twig template
     */
    private function getDefaultPayloadAsTwigTemplate(array $schema): string
    {
        $default_payload = [
            'event' => '{{ event }}',
            'item' => [],
        ];

        // default payload should follow the same nested structure as the original $schema['properties'] but the values should be replaced with a twig tag of the key
        $fn_append_properties = function ($schema_arr, $prefix_keys = []) use (&$fn_append_properties) {
            $result = [];
            foreach ($schema_arr as $key => $value) {
                $new_prefix_keys = array_merge($prefix_keys, [$key]);
                if ($value['type'] === Doc\Schema::TYPE_OBJECT) {
                    $result = array_merge($result, $fn_append_properties($value['properties'], $new_prefix_keys));
                } else {
                    // walk through the result array for each prefix key (creating if needed) and set the value to the twig tag
                    $current = &$result;
                    foreach ($prefix_keys as $prefix_key) {
                        if (!isset($current[$prefix_key])) {
                            $current[$prefix_key] = [];
                        }
                        $current = &$current[$prefix_key];
                    }
                    $current[$key] = "{{ item." . implode('.', $new_prefix_keys) . " }}";
                }
            }
            return $result;
        };
        $default_payload['item'] = $fn_append_properties($schema['properties']);

        $default_payload_str = json_encode($default_payload, JSON_PRETTY_PRINT);

        return $default_payload_str;
    }

    /**
     * @param class-string<CommonDBTM> $itemtype The itemtype to get the schema for
     * @return array|null
     */
    public static function getAPISchemaBySupportedItemtype(string $itemtype): ?array
    {
        $controller_class = null;
        $schema_name = null;
        $supported = self::getAPIItemtypeData();

        foreach ($supported as $controller => $categories) {
            if (array_key_exists($itemtype, $categories['main'])) {
                $schema_name = $categories['main'][$itemtype]['name'];
                $controller_class = $controller;
                break;
            }

            if (isset($categories['subtypes']) && array_key_exists($itemtype, $categories['subtypes'])) {
                $controller_class = $controller;
                $schema_name = $categories['subtypes'][$itemtype]['name'];

                if (
                    array_key_exists('parent', $categories['subtypes'][$itemtype])
                    && array_key_exists($categories['subtypes'][$itemtype]['parent'], $categories['main'])
                    && array_key_exists('name', $categories['main'][$categories['subtypes'][$itemtype]['parent']])
                ) {
                    $schema_name = $categories['main'][$categories['subtypes'][$itemtype]['parent']]['name'] . $schema_name;
                }

                break;
            }
        }

        if ($controller_class === null || $schema_name === null) {
            echo __s('This itemtype is not supported by the API. Maybe a plugin is missing/disabled?');
            return null;
        }
        // TODO Allow pinning webhooks to specific API versions
        return $controller_class::getKnownSchemas(Router::API_VERSION)[$schema_name] ?? null;
    }

    public static function getMonacoSuggestions(?string $itemtype): array
    {
        if (empty($itemtype)) {
            return [];
        }
        $schema = self::getAPISchemaBySupportedItemtype($itemtype);
        if (is_null($schema)) {
            return [];
        }
        $props = Doc\Schema::flattenProperties($schema['properties'], 'item.');
        $parent_schema = self::getParentItemSchema($itemtype);
        $parent_props = $parent_schema !== [] ? Doc\Schema::flattenProperties($parent_schema['properties'], 'parent_item.') : [];

        $response_schema = [
            [
                'name' => 'event',
                'type' => 'Variable',
            ],
        ];

        $subtype_labels = [];
        if (isset($parent_schema['x-subtypes'])) {
            foreach ($parent_schema['x-subtypes'] as $subtype) {
                $subtype_labels[$subtype['itemtype']] = $subtype['itemtype']::getTypeName(1);
            }
        }
        foreach ($props as $prop_name => $prop_data) {
            $response_schema[] = [
                'name' => $prop_name,
                'type' => 'Variable',
            ];
        }

        foreach ($parent_props as $prop_name => $prop_data) {
            $suggestion = [
                'name' => $prop_name,
                'type' => 'Variable',
            ];

            $applicable_types = array_intersect($prop_data['x-parent-itemtype'] ?? [], array_keys($subtype_labels));
            if ($applicable_types !== array_keys($subtype_labels) && count($applicable_types)) {
                //Note: In cases of child properties, there may not be any applicable types listed. They are handled at the top level only.
                $suggestion['detail'] = '[' . implode(', ', array_map(static fn($type) => $subtype_labels[$type], $applicable_types)) . ']';
            }

            $response_schema[] = $suggestion;
        }
        return $response_schema;
    }

    private function showPayloadEditor(): void
    {
        $schema = self::getAPISchemaBySupportedItemtype($this->fields['itemtype']);
        $response_schema = self::getMonacoSuggestions($this->fields['itemtype']);

        TemplateRenderer::getInstance()->display('pages/setup/webhook/payload_editor.html.twig', [
            'item' => $this,
            'params' => [
                'canedit' => $this->canUpdateItem(),
                'candel' => false,
            ],
            'response_schema' => $response_schema,
            'default_payload' => $this->getDefaultPayloadAsTwigTemplate($schema),
        ]);
    }

    private function getSentQueriesSearchParams(): array
    {
        return [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 22,
                    'searchtype' => 'equals',
                    'value' => $this->fields['id'],
                ],
            ],
            // Sort by creation date descending by default
            'sort' => [16],
            'order' => ['DESC'],
            'forcetoview' => [80, 2, 20, 21, 31, 7, 30, 16],
            'is_deleted' => 0,
            'as_map' => 0,
            'browse' => 0,
            'push_history' => 0,
            'hide_controls' => 1,
            'showmassiveactions' => 0,
            'usesession' => 0, // Don't save the search criteria in session or use any criteria currently saved
        ];
    }

    private function showSentQueries(): void
    {
        // Show embeded search engine for QueuedWebhook with the criteria for the current webhook ID
        $params = $this->getSentQueriesSearchParams();
        Search::showList(QueuedWebhook::class, $params);
    }

    public static function getSignature($data, $secret): string
    {
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Validate Challenge Response Answer
     */
    public static function validateCRAChallenge(string $url, string $body, string $secret): array
    {
        $challenge_response = [];
        $options = [
            'base_uri'        => $url,
            'connect_timeout' => 1,
        ];

        // init guzzle client with base options
        $httpClient = Toolbox::getGuzzleClient($options);
        try {
            //prepare query / body
            $response = $httpClient->request('GET', '', [
                'query' => ['crc_token' => self::getSignature($body, $secret)],
            ]);

            if ($response->getStatusCode() == 200) {
                $response_challenge = $response->getBody()->getContents();
                //check response
                if ($response_challenge == hash_hmac('sha256', self::getSignature($body, $secret), $secret)) {
                    $challenge_response = [
                        'status' => true,
                        'message' => __('Challenge–response authentication validated'),
                    ];
                } else {
                    $challenge_response = [
                        'status' => false,
                        'message' => __('Challenge–response authentication failed, the answer returned by target is different'),
                    ];
                }
            } else {
                $challenge_response = [
                    'status' => false,
                    'message' => $response->getReasonPhrase(),
                ];
            }
        } catch (ClientException|RequestException $e) {
            $challenge_response['status'] = false;
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $challenge_response['message'] = $response->getReasonPhrase();
                $challenge_response['status_code'] = $response->getStatusCode();
            } else {
                $challenge_response['message'] = $e->getMessage();
                $challenge_response['status_code'] = 503;
            }
        } catch (GuzzleException $e) {
            $challenge_response['status'] = false;
            $challenge_response['message'] = $e->getMessage();
        }

        return $challenge_response;
    }

    /**
     * Raise an event for an item to trigger related outgoing webhooks
     * @param string $event The event being raised
     * @param CommonDBTM $item The item the event is being raised for
     * @return void
     */
    public static function raise(string $event, CommonDBTM $item): void
    {
        global $DB;

        try {
            // Ignore raising if the table doesn't exist (happens during install/update)
            if (!$DB->tableExists(self::getTable())) {
                return;
            }

            $supported = self::getAPIItemtypeData();
            $supported_types = [];
            foreach ($supported as $categories) {
                foreach ($categories as $types) {
                    $supported_types = array_merge($supported_types, array_keys($types));
                }
            }

            // Ignore raising if the item type is not supported
            if (!in_array($item->getType(), $supported_types, true)) {
                return;
            }

            $it = $DB->request([
                'SELECT' => ['id', 'entities_id', 'is_recursive'],
                'FROM' => self::getTable(),
                'WHERE' => [
                    'event' => $event,
                    'itemtype' => $item->getType(),
                    'is_active' => 1,
                ],
            ]);
            if ($it->count() === 0) {
                return;
            }

            // Get data from the API once for all the webhooks
            $webhook = new self();
            $path = $webhook->getApiPath($item);

            foreach ($it as $webhook_data) {
                $match_entity = false;
                if ($item->isEntityAssign()) {
                    if ($webhook_data['is_recursive']) {
                        $parent_entities = getAncestorsOf(Entity::getTable(), $item->getEntityID());
                        if (in_array($webhook_data['entities_id'], $parent_entities, true)) {
                            $match_entity = true;
                        }
                    }
                    if ($item->getEntityID() === $webhook_data['entities_id']) {
                        $match_entity = true;
                    }
                } elseif ($webhook_data['entities_id'] === 0) {
                    $match_entity = true;
                }
                if (!$match_entity) {
                    continue;
                }
                $webhook->getFromDB($webhook_data['id']);

                $api_data = $webhook->getAPIResponse($path);
                $body = $webhook->getWebhookBody($event, $api_data, $item::class, $item->getID());
                // Check if the item matches the webhook filters
                if (!$webhook->itemMatchFilter($item)) {
                    continue;
                }
                $timestamp = time();
                $headers = [
                    'X-GLPI-signature' => self::getSignature($body . $timestamp, $webhook->fields['secret']),
                    'X-GLPI-timestamp' => $timestamp,
                ];

                $api_data = [
                    'event' => $event,
                    'item' => $api_data,
                ];
                $webhook->addParentItemData($api_data, $item::getType(), $item->getID());

                $custom_headers = $webhook->fields['custom_headers'];
                foreach ($custom_headers as $key => $value) {
                    try {
                        $custom_headers[$key] = TemplateManager::render($value, $api_data, false);
                    } catch (Exception $e) {
                        // Header will not be sent
                    }
                }
                $headers = array_merge($headers, $custom_headers);

                $webhook->fields['url'] = TemplateManager::render($webhook->fields['url'], $api_data, false);

                $data = $webhook->fields;
                $data['items_id'] = $item->getID();
                $data['body'] = $body;
                $data['headers'] = json_encode($headers);
                self::send($data);
            }

        } catch (Throwable $e) {
            //webhooks errors must not be blockers
            ErrorHandler::logCaughtException($e);
            Session::addMessageAfterRedirect(
                htmlescape(
                    sprintf(
                        __('An error occurred raising "%1$s" webhook for item %2$s (ID %3$s)'),
                        static::getDefaultEventsListLabel($event),
                        $item->getTypeName(1),
                        $item->getID()
                    )
                ),
                true,
                ERROR
            );
        }
    }

    /**
     * Send a webhook to the queue
     * @param array $data The data for the webhook
     * @return void
     */
    public static function send(array $data): void
    {
        $queued_webhook = new QueuedWebhook();
        $queued_webhook->add([
            'itemtype' => $data['itemtype'],
            'items_id' => $data['items_id'],
            'entities_id' => $data['entities_id'],
            'webhooks_id' => $data['id'],
            'url' => $data['url'],
            'body' => $data['body'],
            'event' => $data['event'],
            'headers' => $data['headers'],
            'http_method' => $data['http_method'],
            'save_response_body' => $data['save_response_body'],
        ]);
    }

    public function prepareInputForAdd($input)
    {
        return $this->handleInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->handleInput($input);
    }

    public function post_getFromDB()
    {
        if (!empty($this->fields['secret'])) {
            $this->fields['secret'] = (new GLPIKey())->decrypt($this->fields['secret']);
        }
        if (!empty($this->fields['clientsecret'])) {
            $this->fields['clientsecret'] = (new GLPIKey())->decrypt($this->fields['clientsecret']);
        }
        $this->fields['custom_headers'] = importArrayFromDB($this->fields['custom_headers']);
    }

    public static function generateRandomSecret()
    {
        return Toolbox::getRandomString(40);
    }

    public function handleInput($input)
    {
        $valid_input = true;

        $static_headers = ['X-GLPI-signature', 'X-GLPI-timestamp'];
        if (isset($input['header_name'], $input['header_value'])) {
            $custom_headers = array_combine($input['header_name'], $input['header_value']);
            foreach ($static_headers as $static_header) {
                unset($custom_headers[$static_header]);
            }
            $input['custom_headers'] = exportArrayToDB($custom_headers);
        } elseif (isset($input['custom_headers']) && is_array($input['custom_headers'])) {
            $input['custom_headers'] = exportArrayToDB($input['custom_headers']);
        }
        unset($input['header_name'], $input['header_value']);
        if (isset($input["itemtype"]) && !$input["itemtype"]) {
            Session::addMessageAfterRedirect(__s('An item type is required'), false, ERROR);
            $valid_input = false;
        }

        if (isset($input["event"]) && !$input["event"]) {
            Session::addMessageAfterRedirect(__s('An event is required'), false, ERROR);
            $valid_input = false;
        }

        if (!$valid_input) {
            return false;
        }

        if ((empty($input['secret']) && empty($this->fields['secret'])) || isset($input['_regenerate_secret'])) {
            //generate random secret if needed or if empty
            $input['secret'] = self::generateRandomSecret();
        }

        if (!empty($input['secret'])) {
            $input['secret'] = (new GLPIKey())->encrypt($input['secret']);
        }

        if (!empty($input['clientsecret'])) {
            $input['clientsecret'] = (new GLPIKey())->encrypt($input['clientsecret']);
        }

        if (isset($input['use_cra_challenge'])) {
            $input['use_cra_challenge'] = (int) $input['use_cra_challenge'];
        }

        return $input;
    }

    public function post_getEmpty()
    {
        $this->fields['is_cra_challenge_valid'] = 0;
    }

    public static function getMenuContent()
    {
        $menu = [];
        if (Webhook::canView()) {
            $menu = [
                'title'    => _n('Webhook', 'Webhooks', Session::getPluralNumber()),
                'page'     => '/front/webhook.php',
                'icon'     => static::getIcon(),
            ];
            $menu['links']['search'] = '/front/webhook.php';
            $menu['links']['add'] = '/front/webhook.form.php';

            $mp_icon     = htmlescape(QueuedWebhook::getIcon());
            $mp_title    = htmlescape(QueuedWebhook::getTypeName());
            $queuedwebhook = "<i class='$mp_icon pointer' title='$mp_title'></i><span class='d-none d-xxl-block'>$mp_title</span>";
            $menu['links'][$queuedwebhook] = '/front/queuedwebhook.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }

    public static function getIcon()
    {
        return "ti ti-webhook";
    }

    public function getItemtypeToFilter(): string
    {
        return $this->fields['itemtype'];
    }

    public function getItemtypeField(): ?string
    {
        return 'itemtype';
    }

    public function getInfoTitle(): string
    {
        return __('Webhook target filter');
    }

    public function getInfoDescription(): string
    {
        return __("Webhooks will only be sent for items that match the defined filter.");
    }
}
