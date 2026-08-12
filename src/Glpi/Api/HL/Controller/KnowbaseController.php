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
use Entity_KnowbaseItem;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\UI\IllustrationManager;
use Group;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Comment;
use KnowbaseItem_Item;
use KnowbaseItem_KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_Revision;
use KnowbaseItem_User;
use KnowbaseItemTranslation;
use Profile;
use User;

#[Route(path: '/Knowledgebase', requirements: [
    'article_id' => '\d+',
    'id' => '\d+',
], tags: ['Knowledgebase'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'article_id',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'language',
            schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
        ),
    ]
)]
class KnowbaseController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        $schemas = [
            'KBArticle' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItem::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    //TODO support FULLTEXT search instead of LIKE?
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'answer',
                    ],
                    // BC: categories are articles now, so this exposes the same parent articles as the `parents` property
                    'categories' => [
                        'x-version-removed' => '3.0.0',
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'KBCategory',
                            'x-join' => self::getParentArticlesJoin(),
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                            ],
                        ],
                    ],
                    'parents' => [
                        'x-version-introduced' => '3.0.0',
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'KBArticle',
                            'x-join' => self::getParentArticlesJoin(),
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                            ],
                        ],
                    ],
                    'is_faq' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'views' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-field' => 'view',
                    ],
                    'show_in_service_catalog' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'description' => [
                        //TODO same as content
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'description' => 'Short description of the article which may be shown in the service catalog. If null, the content field will be used as description.',
                    ],
                    'illustration' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Name of the illustration to show in the service catalog.',
                        'enum' => (new IllustrationManager())->getAllIconsIds(),
                    ],
                    'is_pinned' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'description' => 'Whether the article is pinned in the service catalog.',
                        'default' => false,
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_begin' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'description' => 'The date and time when the article becomes visible. If null, the article is visible immediately.',
                        'x-field' => 'begin_date',
                    ],
                    'date_end' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'description' => 'The date and time when the article is no longer visible. If null, the article is visible indefinitely.',
                        'x-field' => 'end_date',
                    ],
                    'revisions' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'KBArticleRevision',
                            'x-join' => [
                                'table' => KnowbaseItem_Revision::getTable(),
                                'fkey' => 'id',
                                'field' => KnowbaseItem::getForeignKeyField(),
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'revision' => ['type' => Doc\Schema::TYPE_INTEGER],
                                'language' => [
                                    'type' => Doc\Schema::TYPE_STRING,
                                    'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                                ],
                                'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                            ],
                        ],
                    ],
                    'translations' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'KBArticleTranslation',
                            'x-join' => [
                                'table' => KnowbaseItemTranslation::getTable(),
                                'fkey' => 'id',
                                'field' => KnowbaseItem::getForeignKeyField(),
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                    'readOnly' => true,
                                ],
                                'language' => [
                                    'type' => Doc\Schema::TYPE_STRING,
                                    'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                                ],
                                'name' => ['type' => Doc\Schema::TYPE_STRING],
                            ],
                        ],
                    ],
                ],
            ],
            'KBCategory' => [
                'x-version-introduced' => '2.2.0',
                'x-version-removed' => '3.0.0',
                // BC: categories are articles now. `x-table` is used instead of `x-itemtype` since the
                // `KBArticle` schema is already the schema for the `KnowbaseItem` itemtype.
                'x-table' => KnowbaseItem::getTable(),
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [
                    'read' // ensure only articles that are parents to other articles are returned as they are the closest representation to what categories were
                    => static fn() => [
                        'INNER JOIN' => [
                            KnowbaseItem_KnowbaseItem::getTable() . ' AS kc' => [
                                'ON' => [
                                    '_' => 'id',
                                    'kc' => 'knowbaseitems_id_parent',
                                ],
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'completename' => [
                        //BC: No completename for articles, so it is computed from the ancestors names
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                        'x-mapped-from' => 'id',
                        'x-mapper' => static function ($id) {
                            // Slow BC (would be much better if there was a native completename field)
                            $names = [];
                            foreach ([(int) $id, ...self::getArticleAncestors((int) $id)] as $article_id) {
                                $article = KnowbaseItem::getById($article_id);
                                if ($article === false) {
                                    continue;
                                }
                                array_unshift($names, $article->getName());
                            }
                            return implode(' > ', $names);
                        },
                    ],
                    'comment' => [
                        //BC: No comment for articles and the comment data from categories was not saved at all during the migration to articles
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                        'x-mapped-from' => 'id',
                        'x-mapper' => static fn($v) => '',
                    ],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'parent' => [
                        //BC: Articles may have multiple parents, so only the first one is exposed here
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'nullable' => true,
                        'readOnly' => true,
                        'x-full-schema' => 'KBCategory',
                        'x-mapped-from' => 'id',
                        'x-mapper' => static function ($id) {
                            $parent_id = self::getArticleAncestors((int) $id)[0] ?? null;
                            $parent = $parent_id !== null ? KnowbaseItem::getById($parent_id) : false;
                            return $parent !== false ? ['id' => $parent->getID(), 'name' => $parent->getName()] : null;
                        },
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                            ],
                            'name' => ['type' => Doc\Schema::TYPE_STRING, 'readOnly' => true],
                        ],
                    ],
                    'level' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Level',
                        'readOnly' => true,
                        'x-mapped-from' => 'id',
                        // Tree dropdown levels were 1-based, so the level of an article without any parent is 1
                        'x-mapper' => static fn($id) => count(self::getArticleAncestors((int) $id)) + 1,
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'KBArticleComment' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItem_Comment::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'language' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                    ],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'parent' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'x-full-schema' => 'KBArticleComment',
                        'x-field' => 'parent_comment_id',
                        'x-itemtype' => KnowbaseItem_Comment::class,
                        'x-join' => [
                            'table' => KnowbaseItem_Comment::getTable(),
                            'fkey' => 'parent_comment_id',
                            'field' => 'id',
                        ],
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                                'readOnly' => true,
                            ],
                        ],
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'KBArticleRevision' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItem_Revision::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'revision' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'answer',
                    ],
                    'language' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                    ],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'date' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'KBArticleTranslation' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItemTranslation::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'language' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Language code (POSIX compliant format e.g. en_US or fr_FR)',
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'content' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'answer',
                    ],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
            'KBArticle_EntityTarget' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Entity_KnowbaseItem::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                ],
            ],
            'KBArticle_GroupTarget' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Group_KnowbaseItem::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'group' => self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    //TODO What does the no_entity_restriction field do and is it possible to set it anywhere?
                ],
            ],
            'KBArticle_ProfileTarget' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItem_Profile::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'profile' => self::getDropdownTypeSchema(class: Profile::class, full_schema: 'Profile'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    //TODO What does the no_entity_restriction field do and is it possible to set it anywhere?
                ],
            ],
            'KBArticle_UserTarget' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => KnowbaseItem_User::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                ],
            ],
            'KBArticle_Item' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => KnowbaseItem_Item::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'kbarticle' => self::getDropdownTypeSchema(class: KnowbaseItem::class, full_schema: 'KBArticle'),
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
        ];

        return $schemas;
    }

    /**
     * Join definition used to expose the direct parents of an article.
     *
     * Shared by the `parents` property and its `categories` backward compatible counterpart.
     *
     * @return array{table: string, fkey: string, field: string, 'ref-join': array{table: string, fkey: string, field: string}}
     */
    private static function getParentArticlesJoin(): array
    {
        return [
            'table' => KnowbaseItem::getTable(),
            'fkey' => 'knowbaseitems_id_parent',
            'field' => 'id',
            'ref-join' => [
                'table' => KnowbaseItem_KnowbaseItem::getTable(),
                'fkey' => 'id',
                'field' => 'knowbaseitems_id',
            ],
        ];
    }

    /**
     * Get the ancestors of an article, from the closest one to the root one.
     *
     * Only used to emulate the properties of the removed knowledge base categories.
     * As an article may have multiple parents, only the first one is followed.
     *
     * @return list<int>
     */
    private static function getArticleAncestors(int $article_id): array
    {
        $ancestors = [];
        $seen = [$article_id => true];
        $relation = new KnowbaseItem_KnowbaseItem();

        while (true) {
            $links = $relation->find(['knowbaseitems_id' => $article_id], ['knowbaseitems_id_parent ASC'], 1);
            $link = reset($links);
            if ($link === false) {
                break;
            }
            $article_id = (int) $link['knowbaseitems_id_parent'];
            if (isset($seen[$article_id])) {
                // Should not happen as cycles are rejected, but make sure we can't loop forever
                break;
            }
            $seen[$article_id] = true;
            $ancestors[] = $article_id;
        }

        return $ancestors;
    }

    #[Route(path: '/Article', methods: ['POST'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(schema_name: 'KBArticle')]
    public function createKBArticle(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('KBArticle', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getKBArticle'],
            ['id' => 'article_id'],
        );
    }

    #[Route(path: '/Article', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(schema_name: 'KBArticle')]
    public function searchKBArticles(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('KBArticle', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(schema_name: 'KBArticle')]
    public function getKBArticle(Request $request): Response
    {
        $request->setAttribute('id', $request->getAttribute('article_id'));
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('KBArticle', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute('KBArticle')]
    public function updateKBArticle(Request $request): Response
    {
        $request->setAttribute('id', $request->getAttribute('article_id'));
        return ResourceAccessor::updateBySchema($this->getKnownSchema('KBArticle', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute('KBArticle')]
    public function deleteKBArticle(Request $request): Response
    {
        $request->setAttribute('id', $request->getAttribute('article_id'));
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('KBArticle', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Comment', methods: ['POST'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(schema_name: 'KBArticleComment')]
    public function createKBArticleComment(Request $request): Response
    {
        // comments do not use normal rights management, so we cannot use the ResourceAccessor
        $article = new KnowbaseItem();
        if (!$article->getFromDB((int) $request->getAttribute('article_id')) || !$article->canComment()) {
            return AbstractController::getAccessDeniedErrorResponse();
        }

        $request->setParameter('kbarticle', (int) $request->getAttribute('article_id'));

        $input = ResourceAccessor::getInputParamsBySchema(
            $this->getKnownSchema('KBArticleComment', $this->getAPIVersion($request)),
            $request->getParameters()
        );
        $item = new KnowbaseItem_Comment();
        $items_id = $item->add($input);

        return AbstractController::getCRUDCreateResponse($items_id, self::getAPIPathForRouteFunction(self::class, 'getKBArticleComment', [
            'article_id' => $request->getAttribute('article_id'),
            'id' => $items_id,
        ]));
    }

    #[Route(path: '/Article/{article_id}/Comment', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(schema_name: 'KBArticleComment')]
    public function searchKBArticleComments(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('KBArticleComment', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Comment/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(schema_name: 'KBArticleComment')]
    public function getKBArticleComment(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('KBArticleComment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Comment/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute('KBArticleComment')]
    public function updateKBArticleComment(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('KBArticleComment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Comment/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute('KBArticleComment')]
    public function deleteKBArticleComment(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('KBArticleComment', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Revision', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(schema_name: 'KBArticleRevision')]
    public function searchKBArticleDefaultLangRevisions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $filters .= ';language=empty=';
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('KBArticleRevision', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/Revision/{revision}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(schema_name: 'KBArticleRevision')]
    public function getKBArticleDefaultLangRevision(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $filters .= ';language=empty=';
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('KBArticleRevision', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            'revision'
        );
    }

    #[Route(path: '/Article/{article_id}/{language}/Revision', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(schema_name: 'KBArticleRevision')]
    public function searchKBArticleRevisions(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $filters .= ';language==' . $request->getAttribute('language');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('KBArticleRevision', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Article/{article_id}/{language}/Revision/{revision}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(schema_name: 'KBArticleRevision')]
    public function getKBArticleRevision(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';kbarticle.id==' . $request->getAttribute('article_id');
        $filters .= ';language==' . $request->getAttribute('language');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('KBArticleRevision', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            'revision'
        );
    }

    #[Route(path: '/Category', methods: ['POST'])]
    #[RouteVersion(introduced: '2.2', removed: '3.0.0')]
    #[Doc\CreateRoute(schema_name: 'KBCategory')]
    public function createKBCategory(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('KBCategory', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getKBCategory'],
        );
    }

    #[Route(path: '/Category', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2', removed: '3.0.0')]
    #[Doc\SearchRoute(schema_name: 'KBCategory')]
    public function searchKBCategory(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('KBCategory', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Category/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2', removed: '3.0.0')]
    #[Doc\GetRoute(schema_name: 'KBCategory')]
    public function getKBCategory(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema($this->getKnownSchema('KBCategory', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Category/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.2', removed: '3.0.0')]
    #[Doc\UpdateRoute('KBCategory')]
    public function updateKBCategory(Request $request): Response
    {
        return ResourceAccessor::updateBySchema($this->getKnownSchema('KBCategory', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Category/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.2', removed: '3.0.0')]
    #[Doc\DeleteRoute('KBCategory')]
    public function deleteKBCategory(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema($this->getKnownSchema('KBCategory', $this->getAPIVersion($request)), $request->getAttributes(), $request->getParameters());
    }
}
