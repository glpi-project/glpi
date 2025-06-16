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

namespace Glpi\Form\ServiceCatalog\Provider;

use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\FuzzyMatcher\FuzzyMatcher;
use Glpi\FuzzyMatcher\PartialMatchStrategy;
use Override;

/** @implements LeafProviderInterface<\Glpi\Form\Form> */
final class FormProvider implements LeafProviderInterface
{
    private FormAccessControlManager $access_manager;
    private FuzzyMatcher $matcher;

    public function __construct()
    {
        $this->access_manager = FormAccessControlManager::getInstance();
        $this->matcher = new FuzzyMatcher(new PartialMatchStrategy());
    }

    #[Override]
    public function getItems(ItemRequest $item_request): array
    {
        $category = $item_request->getCategory();
        $filter = $item_request->getFilter();
        $parameters = $item_request->getFormAccessParameters();

        $entity_restriction = getEntitiesRestrictCriteria(
            table: Form::getTable(),
            value: $parameters->getSessionInfo()->getCurrentEntityId(),
            is_recursive: true,
        );

        $forms = [];
        $raw_forms = (new Form())->find([
            'is_active'           => 1,
            'is_deleted'          => 0,
            'forms_categories_id' => $category ? $category->getID() : 0,
        ] + $entity_restriction, ['name']);

        foreach ($raw_forms as $raw_form) {
            $form = new Form();
            $form->getFromResultSet($raw_form);
            $form->post_getFromDB();

            // Fuzzy matching
            $name = $form->fields['name'] ?? "";
            $description = $form->fields['description'] ?? "";
            if (
                !$this->matcher->match($name, $filter)
                && !$this->matcher->match($description, $filter)
            ) {
                continue;
            }

            /// Note: this is in theory less performant than applying the parameters
            // directly to the SQL query (which would require more complicated code).
            // However, the number of forms is expected to be low, so this is acceptable.
            // If performance becomes an issue, we can revisit this and/or add a cache.
            if (!$this->access_manager->canAnswerForm($form, $parameters)) {
                continue;
            }

            $forms[] = $form;
        }

        return $forms;
    }
}
