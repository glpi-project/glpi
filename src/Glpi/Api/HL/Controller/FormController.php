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
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Form\Category;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Form;
use Glpi\Form\RenderLayout;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/Form', priority: 1, tags: ['Forms'])]
final class FormController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Form' => [
                //TODO add visibility restrition conditions. FormAccessControlManager currently can only check access once an individual form is loaded, but we need it at the SQL level.
                // If no SQL conditions can be applied, we need to implement a complex fetching strategy to continue supporting RSQL filters and pagination.
                // Probably would involve over-fetching using non-API methods, in-memory filtering, and then using the visible IDs in an API fetch.
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-version-introduced' => '2.4.0',
                'x-itemtype' => Form::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'uuid' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'pattern' => Doc\Schema::PATTERN_UUIDV4,
                        'readOnly' => true,
                    ],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_draft' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_pinned' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'render_layout' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => RenderLayout::cases(),
                        'default' => RenderLayout::STEP_BY_STEP->value,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'form_description' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'header',
                    ],
                    'service_catalog_description' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_HTML,
                        'x-field' => 'description',
                    ],
                    'illustration' => ['type' => Doc\Schema::TYPE_STRING],
                    'category' => self::getDropdownTypeSchema(class: Category::class, full_schema: 'FormCategory'),
                    'usage_count' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'submit_button_visibility_strategy' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'enum' => VisibilityStrategy::cases(),
                        'default' => VisibilityStrategy::ALWAYS_VISIBLE->value,
                    ],
                    'submit_button_conditions' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => <<<EOT
                            JSON encoded array of conditions to apply to the submit button.
                            The order of the conditions is important as it affects the evaluation with the logic_operator value.
                            Each condition will contain an `item_type` and an `item_uuid` field to specify the item the condition is based on and `value_operator` to specify the condition operator.
                            A `logic_operator` field can be set to specify how the condition should be evaluated with the next condition. If not set, it will default to "AND".
                            If the `value_operator` uses a value, a `value` field will also be present to specify the value to compare with.
EOT,
                    ],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
        ];
    }

    #[Route(path: '/', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.4.0')]
    #[Doc\SearchRoute(schema_name: 'Form')]
    public function searchForms(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Form', $this->getAPIVersion($request)), $request->getParameters());
    }
}
