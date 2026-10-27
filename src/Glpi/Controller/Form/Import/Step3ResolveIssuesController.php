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

namespace Glpi\Controller\Form\Import;

use Glpi\Controller\AbstractController;
use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class Step3ResolveIssuesController extends AbstractController
{
    #[Route("/Form/Import/ResolveIssues", name: "glpi_form_import_resolve_issues", methods: "POST")]
    public function __invoke(Request $request): Response
    {
        if (!Form::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        // Get json, form name and skipped_forms from the request.
        $json          = $request->request->get('json');
        $form_id       = $request->request->get('form_id');
        $skipped_forms = $request->request->all()["skipped_forms"] ?? [];

        $serializer = new FormSerializer();
        $mapper = new DatabaseMapper(Session::getActiveEntities());

        $replacements = $request->request->all()["replacements"] ?? [];
        foreach ($replacements as $replacements_data) {
            $mapper->addMappedItem(
                $replacements_data['itemtype'],
                $replacements_data['original_name'],
                (int) $replacements_data['replacement_id'],
            );
        }

        $issues = $serializer->listIssues($mapper, $json, $skipped_forms)->getIssues()[$form_id];

        // Questions, sections and comments names are not unique, their form
        // name is displayed to help choosing the right replacement.
        $form_items_values = [];
        foreach ($issues as $issue) {
            if (!isset($form_items_values[$issue->itemtype])) {
                $values = $this->getFormItemsDropdownValues(
                    $issue->itemtype,
                    Session::getActiveEntities(),
                );
                if ($values !== []) {
                    $form_items_values[$issue->itemtype] = $values;
                }
            }
        }

        return $this->render("pages/admin/form/import/step3_resolve_issues.html.twig", [
            'form_items_values' => $form_items_values,
            'title'        => __("Resolve issues"),
            'menu'         => ['admin', Form::getType()],
            'issues'       => $issues,
            'json'         => $json,
            'replacements' => $replacements,
            'skipped_forms' => $skipped_forms,
        ]);
    }

    /**
     * Get the questions, sections or comments that can be used as replacement,
     * labelled with the name of their form.
     *
     * @param int[] $entities
     * @return array<int, string>
     */
    public function getFormItemsDropdownValues(string $itemtype, array $entities): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!in_array($itemtype, [Question::class, Section::class, Comment::class], true)) {
            return [];
        }

        $table = $itemtype::getTable();
        $forms_table = Form::getTable();
        $sections_table = Section::getTable();
        $query = [
            'SELECT' => [
                "$table.id",
                "$table.name",
                "$forms_table.name AS form_name",
            ],
            'FROM'   => $table,
            'WHERE'  => [
                "$forms_table.is_deleted" => 0,
                getEntitiesRestrictCriteria(
                    $forms_table,
                    value: $entities,
                    is_recursive: true,
                ),
            ],
        ];
        if ($itemtype === Section::class) {
            $query['INNER JOIN'][$forms_table] = [
                'ON' => [
                    $table       => Form::getForeignKeyField(),
                    $forms_table => 'id',
                ],
            ];
        } else {
            $query['INNER JOIN'][$sections_table] = [
                'ON' => [
                    $table          => Section::getForeignKeyField(),
                    $sections_table => 'id',
                ],
            ];
            $query['INNER JOIN'][$forms_table] = [
                'ON' => [
                    $sections_table => Form::getForeignKeyField(),
                    $forms_table    => 'id',
                ],
            ];
        }

        $values = [];
        foreach ($DB->request($query) as $row) {
            $values[$row['id']] = sprintf('%s > %s', $row['form_name'], $row['name']);
        }
        asort($values);

        return $values;
    }
}
