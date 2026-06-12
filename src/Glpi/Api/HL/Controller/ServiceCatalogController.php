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

use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Form\Form;
use Glpi\Helpdesk\Tile\ExternalPageTile;
use Glpi\Helpdesk\Tile\FormTile;
use Glpi\Helpdesk\Tile\GlpiPageTile;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GraphQL\Type\Definition\ResolveInfo;
use Session;
use stdClass;

#[Route(path: '/ServiceCatalog', priority: 1, tags: ['ServiceCatalog'])]
final class ServiceCatalogController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'GLPIPageTile' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-resolver' => null,
                'x-itemtype' => GlpiPageTile::class,
                'properties' => [
                    '_tile_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => ['GLPIPageTile'],
                    ],
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'title' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'description' => ['type' => Doc\Schema::TYPE_STRING],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'page' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                        'enum' => [
                            GlpiPageTile::PAGE_SERVICE_CATALOG, GlpiPageTile::PAGE_FAQ, GlpiPageTile::PAGE_RESERVATION,
                            GlpiPageTile::PAGE_APPROVAL, GlpiPageTile::PAGE_ALL_TICKETS,
                        ],
                    ],
                ],
            ],
            'ExternalPageTile' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-resolver' => null,
                'x-itemtype' => ExternalPageTile::class,
                'properties' => [
                    '_tile_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => ['ExternalPageTile'],
                    ],
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'title' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'description' => ['type' => Doc\Schema::TYPE_STRING],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'url' => ['type' => Doc\Schema::TYPE_STRING],
                ],
            ],
            'FormTile' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-graphql-resolver' => null,
                'x-itemtype' => FormTile::class,
                'properties' => [
                    '_tile_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => ['FormTile'],
                    ],
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'title' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'description' => ['type' => Doc\Schema::TYPE_STRING],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'weight' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'form' => self::getDropdownTypeSchema(Form::class),
                ],
            ],
            'ServiceCatalogInfo' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'readOnly' => true,
                'x-graphql-resolver' => self::graphQLResolveServiceCatalogInfo(...),
                'x-singleton' => true,
                'properties' => [
                    'helpdesk_home_title' => ['type' => Doc\Schema::TYPE_STRING],
                    'helpdesk_home_search_enabled' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                    'tiles' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'anyOf' => ['GLPIPageTile', 'ExternalPageTile', 'FormTile'],
                            'discriminator' => [
                                'propertyName' => '_tile_type',
                                'mapping' => [
                                    'GLPIPageTile' => 'GLPIPageTile',
                                    'ExternalPageTile' => 'ExternalPageTile',
                                    'FormTile' => 'FormTile',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function getTilesForCurrentSession(): array
    {
        $session_info = Session::getCurrentSessionInfo();
        if ($session_info === null) {
            return [];
        }
        $tiles = (TilesManager::getInstance())->getVisibleTilesForSession($session_info);
        $tiles_info = [];
        foreach ($tiles as $tile) {
            $tile_info = [
                'id' => $tile->getID(),
                'title' => $tile->getTitle(),
                'description' => $tile->getDescription(),
                'illustration' => $tile->getIllustration(),
                'weight' => $tile->getWeight(),
            ];
            if ($tile instanceof GlpiPageTile) {
                $tile_info['_tile_type'] = 'GLPIPageTile';
                $tile_info['page'] = $tile->getPage();
            } elseif ($tile instanceof ExternalPageTile) {
                $tile_info['_tile_type'] = 'ExternalPageTile';
                $tile_info['url'] = $tile->getTileUrl();
            } elseif ($tile instanceof FormTile) {
                $tile_info['_tile_type'] = 'FormTile';
                $tile_info['form'] = [
                    'id' => $tile->getForm()->getID(),
                    'name' => $tile->getForm()->getName(),
                ];
            }
            $tiles_info[] = $tile_info;
        }
        return $tiles_info;
    }

    /**
     * @return array{helpdesk_home_title?: string, helpdesk_home_search_enabled?: bool, tiles?: list<array<string, mixed>>}
     */
    private static function getServiceCatalogInfoForCurrentUser(bool $include_tiles = true): array
    {
        $session_info = Session::getCurrentSessionInfo();
        if ($session_info === null) {
            return [];
        }
        $entity = Entity::getById($session_info->getCurrentEntityId());
        if ($entity === false) {
            return [];
        }
        $info = [
            'helpdesk_home_title' => $entity->getHelpdeskHomeTitle(),
            'helpdesk_home_search_enabled' => $entity->isHelpdeskSearchBarEnabled(),
        ];
        if ($include_tiles) {
            $info['tiles'] = self::getTilesForCurrentSession();
        }
        return $info;
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return mixed
     */
    private static function graphQLResolveServiceCatalogInfo(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        if ($context->fullyResolved ?? false) {
            return $source[$info->fieldName];
        }
        $fields = $info->getFieldSelection();
        $catalog_info = self::getServiceCatalogInfoForCurrentUser(include_tiles: array_key_exists('tiles', $fields));
        $context->fullyResolved = true;
        return $catalog_info;
    }

    #[Route(path: '/My', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4')]
    #[Doc\GetRoute(
        schema_name: 'ServiceCatalogInfo',
        description: 'Get the service catalog information for the current user.',
    )]
    public function getServiceCatalog(Request $request): Response
    {
        return new JSONResponse(self::getServiceCatalogInfoForCurrentUser());
    }
}
