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

namespace Glpi\Api\HL\Controller;

use CommonDBTM;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Features\KanbanInterface;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Safe\Exceptions\JsonException;
use Session;
use stdClass;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @phpstan-type KanbanViewStateDBFormat = array<int, array{column: int|numeric-string, folded?: "true"|"false", visible?: "true"|"false", cards?: array<int, string>}>
 * @phpstan-type KanbanViewState = array<int, array{column: int, folded?: bool, visible?: bool, cards?: array<int, array{itemtype: string, items_id: int}>}>
 */
#[Route(path: '/Kanban', requirements: [
    'itemtype' => 'Project|Ticket|Change|Problem',
    'items_id' => '\d+',
    'users_id' => '\d+',
], priority: 1, tags: ['Kanban'])]
class KanbanController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'KanbanView' => [
                'x-version-introduced' => '2.4.0',
                'type' => Doc\Schema::TYPE_ARRAY,
                'x-graphql-resolver' => self::graphQLResolverKanbanView(...),
                'x-graphql-query-args' => [
                    'itemtype' => ['type' => Type::string()],
                    'items_id' => ['type' => Type::int()],
                    'users_id' => ['type' => Type::int()],
                ],
                'x-singleton' => true,
                'items' => [
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'properties' => [
                        'column' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                        'folded' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'visible' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                        'cards' => [
                            'type' => Doc\Schema::TYPE_ARRAY,
                            'items' => [
                                'type' => Doc\Schema::TYPE_OBJECT,
                                'properties' => [
                                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param KanbanViewStateDBFormat $state
     * @return KanbanViewState
     */
    private static function convertStateFromDBFormat(array $state): array
    {
        ksort($state);
        $state = array_values($state);
        foreach ($state as &$column) {
            $column['column'] = (int) $column['column'];
            $column['folded'] = filter_var($column['folded'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $column['visible'] = filter_var($column['visible'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $parsed_cards = [];
            foreach ($column['cards'] ?? [] as $card) {
                [$card_itemtype, $card_items_id] = explode('-', $card, 2);
                $parsed_cards[] = [
                    'itemtype' => $card_itemtype,
                    'items_id' => (int) $card_items_id,
                ];
            }
            $column['cards'] = $parsed_cards;
        }
        return $state;
    }

    /**
     * @param KanbanViewState $state
     * @return KanbanViewStateDBFormat
     */
    private static function convertStateToDBFormat(array $state): array
    {
        foreach ($state as &$column) {
            if (isset($column['column'])) {
                $column['column'] = (string) $column['column'];
            }
            if (isset($column['folded'])) {
                $column['folded'] = $column['folded'] ? "true" : "false";
            }
            if (isset($column['visible'])) {
                $column['visible'] = $column['visible'] ? "true" : "false";
            }
            if (isset($column['cards'])) {
                $column['cards'] = array_map(static fn($card) => $card['itemtype'] . '-' . $card['items_id'], $column['cards']);
            }
        }
        unset($column);
        /** @phpstan-ignore-next-line PHPStan not smart enough to see isset($column['folded']) and see we enforce the value to be "true" or "false" and cannot be a bool */
        return $state;
    }

    /**
     * @param string $itemtype
     * @param int $items_id
     * @param int $users_id
     * @return KanbanViewState|null
     * @throws JsonException
     */
    private static function getKanbanViewData(string $itemtype, int $items_id, int $users_id): ?array
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['id', 'state'],
            'FROM'   => 'glpi_items_kanbans',
            'WHERE'  => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
                'users_id' => $users_id,
            ],
            'LIMIT'  => 1,
        ]);
        if (!count($it)) {
            return null;
        }

        $state = json_decode($it->current()['state'], true);
        if (is_array($state)) {
            $state = self::convertStateFromDBFormat($state);
        }
        return $state;
    }

    /**
     * @param class-string<KanbanInterface&CommonDBTM> $itemtype
     * @param int $items_id
     * @param int $users_id
     * @return bool
     */
    private static function canAccessKanbanView(string $itemtype, int $items_id, int $users_id): bool
    {
        /** @phpstan-ignore-next-line This isn't "unrestricted dynamic string" as it is enforced by the router */
        $item = new $itemtype();
        $is_global_view = $items_id <= 0;

        return !((!$is_global_view && $users_id !== Session::getLoginUserID()) || !($item->can($item->getID(), READ)));
    }

    /**
     * @param class-string<KanbanInterface&CommonDBTM> $itemtype
     * @param int $items_id
     * @param int $users_id
     * @param KanbanViewState $state
     * @return void
     */
    private static function saveKanbanView(string $itemtype, int $items_id, int $users_id, array $state): void
    {
        global $DB;

        $DB->updateOrInsert(
            table: 'glpi_items_kanbans',
            params: ['state' => json_encode(self::convertStateToDBFormat($state))],
            where: [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
                'users_id' => $users_id,
            ]
        );
    }

    /**
     * @param class-string<KanbanInterface&CommonDBTM> $itemtype
     * @param int $items_id
     * @param int $users_id
     * @return true|Response
     */
    private static function checkUpdateKanbanView(string $itemtype, int $items_id, int $users_id): true|Response
    {
        /** @phpstan-ignore-next-line This isn't "unrestricted dynamic string" as it is enforced by the router */
        $item = new $itemtype();
        $is_global_view = $items_id <= 0;

        if (!self::canAccessKanbanView($itemtype, $items_id, $users_id)) {
            return self::getAccessDeniedErrorResponse();
        }

        if ($is_global_view && $users_id <= 0) {
            return self::getAccessDeniedErrorResponse('Global kanbans are only allowed to have per-user views.');
        } elseif (!$is_global_view && $users_id > 0) {
            return self::getAccessDeniedErrorResponse('Per-user kanban views are only allowed for global kanbans.');
        } elseif ($users_id > 0 && ($users_id !== Session::getLoginUserID() && !$item->canModifyGlobalState())) {
            return self::getAccessDeniedErrorResponse('You can only modify your own kanban view.');
        }

        if ($items_id > 0 && !$item->canModifyGlobalState()) {
            return self::getAccessDeniedErrorResponse();
        }

        return true;
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return KanbanViewState|null
     */
    private static function graphQLResolverKanbanView(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        if ($context->fullyResolved ?? false) {
            return $source[$info->fieldName];
        }
        /** @var class-string<KanbanInterface&CommonDBTM> $itemtype */
        $itemtype = $args['itemtype'] ?? null;
        $items_id = (int) ($args['items_id'] ?? 0);
        $users_id = (int) ($args['users_id'] ?? 0);

        if (!in_array($itemtype, ['Project', 'Ticket', 'Change', 'Problem'], true)) {
            return null;
        }

        if (!self::canAccessKanbanView($itemtype, $items_id, $users_id)) {
            return null;
        }

        $view = self::getKanbanViewData($itemtype, $items_id, $users_id);
        $context->fullyResolved = true;
        return $view;
    }

    #[Route(path: '/{itemtype}/{items_id}/View/{users_id}', methods: ['GET'])]
    #[RouteVersion(introduced: '2.4')]
    #[Doc\SearchRoute(schema_name: 'KanbanView')]
    public function getKanbanView(Request $request): Response
    {
        /** @var class-string<KanbanInterface&CommonDBTM> $itemtype */
        $itemtype = $request->getAttribute('itemtype');
        $items_id = (int) $request->getAttribute('items_id');
        $users_id = (int) $request->getAttribute('users_id');

        if (!self::canAccessKanbanView($itemtype, $items_id, $users_id)) {
            return self::getAccessDeniedErrorResponse();
        }

        $view = self::getKanbanViewData($itemtype, $items_id, $users_id);
        if ($view === null) {
            return self::getNotFoundErrorResponse();
        }
        return new JSONResponse($view);
    }

    #[Route(path: '/{itemtype}/{items_id}/View/{users_id}/Column/{column_id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.4')]
    #[Doc\Route(parameters: [
        new Doc\Parameter(name: 'folded', schema: new Doc\Schema(type: Doc\Schema::TYPE_BOOLEAN), location: Doc\Parameter::LOCATION_BODY),
        new Doc\Parameter(name: 'visible', schema: new Doc\Schema(type: Doc\Schema::TYPE_BOOLEAN), location: Doc\Parameter::LOCATION_BODY),
        new Doc\Parameter(name: 'position', schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER, format: Doc\Schema::FORMAT_INTEGER_INT64), location: Doc\Parameter::LOCATION_BODY),
    ])]
    public function updateKanbanViewColumn(Request $request): Response
    {
        $itemtype = $request->getAttribute('itemtype');
        $items_id = (int) $request->getAttribute('items_id');
        $users_id = (int) $request->getAttribute('users_id');
        $column_id = (int) $request->getAttribute('column_id');

        $can_update_result = self::checkUpdateKanbanView($itemtype, $items_id, $users_id);
        if ($can_update_result !== true) {
            return $can_update_result;
        }

        $new_state = [];
        if ($request->hasParameter('folded')) {
            $new_state['folded'] = filter_var($request->getParameter('folded'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->hasParameter('visible')) {
            $new_state['visible'] = filter_var($request->getParameter('visible'), FILTER_VALIDATE_BOOLEAN);
        }

        $view = self::getKanbanViewData($itemtype, $items_id, $users_id);
        if ($view === null) {
            $view = [
                'column' => $column_id,
                ...$new_state,
                'cards' => [],
            ];
        } else {
            foreach ($view as &$column) {
                if ($column['column'] === $column_id) {
                    $column = array_replace($column, $new_state);
                    break;
                }
            }
            unset($column);
        }

        // if the position is provided, we need to reorder the columns in the view
        if ($request->hasParameter('position')) {
            $position = (int) $request->getParameter('position');
            $current_index = null;
            foreach ($view as $index => $column) {
                /** @phpstan-ignore-next-line PHPStan not very smart */
                if ($column['column'] === $column_id) {
                    $current_index = (int) $index;
                    break;
                }
            }
            if ($current_index !== null && $current_index !== $position) {
                $column_to_move = $view[$current_index];
                array_splice($view, $current_index, 1);
                $position = max(0, min($position, count($view)));
                array_splice($view, $position, 0, [$column_to_move]);
            }
        }

        /** @phpstan-ignore-next-line PHPStan not very smart */
        self::saveKanbanView($itemtype, $items_id, $users_id, $view);
        return new Response();
    }

    #[Route(path: '/{itemtype}/{items_id}/View/{users_id}/Column/{column_id}/Cards', methods: ['PATCH', 'PUT'])]
    #[RouteVersion(introduced: '2.4')]
    #[Doc\Route(parameters: [
        new Doc\Parameter(name: '_', schema: new Doc\Schema(type: Doc\Schema::TYPE_ARRAY, items: new Doc\Schema(type: Doc\Schema::TYPE_OBJECT, properties: [
            'itemtype' => new Doc\Schema(type: Doc\Schema::TYPE_STRING),
            'items_id' => new Doc\Schema(type: Doc\Schema::TYPE_INTEGER, format: Doc\Schema::FORMAT_INTEGER_INT64),
            'position' => new Doc\Schema(type: Doc\Schema::TYPE_INTEGER, format: Doc\Schema::FORMAT_INTEGER_INT64),
        ])), location: Doc\Parameter::LOCATION_BODY),
    ])]
    public function updateKanbanViewColumnCards(Request $request): Response
    {
        $replace_cards = $request->getMethod() === 'PUT';
        $itemtype = $request->getAttribute('itemtype');
        $items_id = (int) $request->getAttribute('items_id');
        $users_id = (int) $request->getAttribute('users_id');
        $column_id = (int) $request->getAttribute('column_id');
        $cards = $request->getParsedBody();

        $valid_card_types = [
            'Project' => ['Project', 'ProjectTask'],
            'Ticket' => ['Ticket'],
            'Change' => ['Change'],
            'Problem' => ['Problem'],
        ];

        $cards_valid = true;
        if (is_array($cards)) {
            foreach ($cards as $card) {
                if (!is_array($card) || !isset($card['itemtype'], $card['items_id'])) {
                    $cards_valid = false;
                    break;
                }
            }
            $cards = array_filter($cards, static fn($card) => in_array($card['itemtype'], $valid_card_types[$itemtype] ?? [], true));
        } else {
            $cards_valid = false;
        }

        if (!$cards_valid) {
            return self::getInvalidParametersErrorResponse([
                'invalid' => [
                    [
                        'name' => 'cards',
                        'reason' => 'Cards must be an array of objects with itemtype and items_id.',
                    ],
                ],
            ]);
        }

        // PHPStan isn't smart enough to understand the !is_array($cards) -> $cards_valid = false -> return. It thinks cards can be an object or null here...
        /** @var array<int, array{position?: int, itemtype: string, items_id: int}> $cards */

        // sort the cards by position if provided, otherwise keep the order as is
        usort($cards, static function ($a, $b) {
            $pos_a = $a['position'] ?? PHP_INT_MAX;
            $pos_b = $b['position'] ?? PHP_INT_MAX;
            return $pos_a <=> $pos_b;
        });

        $can_update_result = self::checkUpdateKanbanView($itemtype, $items_id, $users_id);
        if ($can_update_result !== true) {
            return $can_update_result;
        }

        $state = self::getKanbanViewData($itemtype, $items_id, $users_id);
        if ($state === null) {
            $state = [
                ['column' => $column_id, 'cards' => []],
            ];
        }

        // Remove the cards in the request from every column in the state to avoid duplicates
        foreach ($state as &$column) {
            $column['cards'] = array_filter($column['cards'] ?? [], static function ($card) use ($cards) {
                foreach ($cards as $new_card) {
                    if ($card['itemtype'] === $new_card['itemtype'] && $card['items_id'] === $new_card['items_id']) {
                        return false;
                    }
                }
                return true;
            });
            // re-index the array to avoid gaps in the keys after filtering
            $column['cards'] = array_values($column['cards']);
        }
        unset($column);

        if ($replace_cards) {
            foreach ($state as &$column) {
                if ($column['column'] === $column_id) {
                    $column['cards'] = $cards;
                    break;
                }
            }
            unset($column);
        } else {
            // Adding new cards requires inserting them at the desired position to shift the existing cards down. If no position is specified, they are added to the end.
            foreach ($cards as $new_card) {
                $position = $new_card['position'] ?? null;
                foreach ($state as &$column) {
                    if ($column['column'] === $column_id) {
                        if ($position === null || (int) $position >= count($column['cards'])) {
                            $column['cards'][] = $new_card;
                        } else {
                            array_splice($column['cards'], (int) $position, 0, [$new_card]);
                        }
                        break;
                    }
                }
                unset($column);
            }
        }

        self::saveKanbanView($itemtype, $items_id, $users_id, $state);
        return new Response();
    }

    #[Route(path: '/{itemtype}/{items_id}/View/{users_id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.4')]
    #[Doc\DeleteRoute(schema_name: 'KanbanView')]
    public function deleteKanbanView(Request $request): Response
    {
        global $DB;

        if (!$DB->delete('glpi_items_kanbans', [
            'itemtype' => $request->getAttribute('itemtype'),
            'items_id' => (int) $request->getAttribute('items_id'),
            'users_id' => (int) $request->getAttribute('users_id'),
        ])) {
            return self::getNotFoundErrorResponse();
        }
        return new JSONResponse(null, 204);
    }
}
