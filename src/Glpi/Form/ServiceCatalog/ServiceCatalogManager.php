<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\ServiceCatalog;

use Glpi\Form\Form;

final class ServiceCatalogManager
{
    /**
     * Return all available forms for the given user.
     *
     * Forms names may be altered to ensure uniqueness.
     * This is done by by adding suffixes to forms with the same name :
     * "My form", "My form (1)", "My form (2)", ...
     *
     * This is required to comply with accessibility requirements (<sections>
     * names must be unique).
     *
     * @return Form[]
     */
    public function getForms(): array
    {
        $forms = $this->getFormsFromDatabase();
        $forms = $this->addSuffixesToIdenticalFormNames($forms);

        return $forms;
    }

    /** @return Form[] */
    private function getFormsFromDatabase(): array
    {
        $forms = [];
        $raw_forms = (new Form())->find([
            'is_active' => 1,
        ], ['name']);

        foreach ($raw_forms as $raw_form) {
            $form = new Form();
            $form->getFromResultSet($raw_form);
            $form->post_getFromDB();
            $forms[] = $form;
        };

        return $forms;
    }

    /** @return Form[] */
    private function addSuffixesToIdenticalFormNames(array $forms): array
    {
        // Group forms by names
        $forms_grouped_by_names = [];
        foreach ($forms as $form) {
            if (!isset($forms_grouped_by_names[$form->fields['name']])) {
                $forms_grouped_by_names[$form->fields['name']] = [];
            }

            $forms_grouped_by_names[$form->fields['name']][] = $form;
        }

        // Add suffixes to forms with identical names
        $forms_with_unique_names = [];
        foreach ($forms_grouped_by_names as $forms) {
            foreach ($forms as $i => $form) {
                if ($i == 0) {
                    $forms_with_unique_names[] = $form;
                } else {
                    $form->fields['name'] = "{$form->fields['name']} ($i)";
                    $forms_with_unique_names[] = $form;
                }
            }
        }

        return $forms_with_unique_names;
    }
}
