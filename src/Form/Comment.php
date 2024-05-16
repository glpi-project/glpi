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

namespace Glpi\Form;

use CommonDBChild;
use Glpi\Application\View\TemplateRenderer;
use Glpi\ContentTemplates\TemplateManager;
use Log;
use Override;

/**
 * Comment of a given helpdesk form's section
 */
final class Comment extends CommonDBChild
{
    public static $itemtype = Section::class;
    public static $items_id = 'forms_sections_id';

    public $dohistory = true;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Comment', 'Comments', $nb);
    }

    #[Override]
    public function post_addItem()
    {
        // Report logs to the parent form
        $this->logCreationInParentForm();
    }

    #[Override]
    public function post_updateItem($history = true)
    {
        // Report logs to the parent form
        $this->logUpdateInParentForm($history);
    }

    #[Override]
    public function post_deleteFromDB()
    {
        // Report logs to the parent form
        $this->logDeleteInParentForm();
    }

    public function displayBlockForEditor(): void
    {
        TemplateRenderer::getInstance()->display('pages/admin/form/form_comment.html.twig', [
            'form'       => $this->getForm(),
            'comment'    => $this,
            'section'    => $this->getItem(),
            'can_update' => $this->getForm()->canUpdate(),
        ]);
    }

    /**
     * Get the parent form of this question
     *
     * @return Form
     */
    public function getForm(): Form
    {
        return $this->getItem()->getItem();
    }

    /**
     * Manually update logs of the parent form item
     *
     * @return void
     */
    protected function logCreationInParentForm(): void
    {
        if ($this->input['_no_history'] ?? false) {
            return;
        }

        $form = $this->getForm();
        $changes = [
            '0',
            '',
            $this->getHistoryNameForItem($form, 'add'),
        ];

        // Report logs to the parent form
        Log::history(
            $form->getID(),
            $form->getType(),
            $changes,
            $this->getType(),
            static::$log_history_add
        );

        parent::post_addItem();
    }

    /**
     * Manually update logs of the parent form item
     *
     * @param bool $history
     *
     * @return void
     */
    protected function logUpdateInParentForm($history = true): void
    {
        if ($this->input['_no_history'] ?? false) {
            return;
        }

        $form = $this->getForm();

        $oldvalues = $this->oldvalues;
        unset($oldvalues[static::$itemtype]);
        unset($oldvalues[static::$items_id]);

        foreach (array_keys($oldvalues) as $field) {
            if (in_array($field, $this->getNonLoggedFields())) {
                continue;
            }
            $changes = $this->getHistoryChangeWhenUpdateField($field);
            if ((!is_array($changes)) || (count($changes) != 3)) {
                continue;
            }

            Log::history(
                $form->getID(),
                $form->getType(),
                $changes,
                $this->getType(),
                static::$log_history_update
            );
        }

        parent::post_updateItem($history);
    }

    /**
     * Manually update logs of the parent form item
     *
     * @return void
     */
    protected function logDeleteInParentForm(): void
    {
        if ($this->input['_no_history'] ?? false) {
            return;
        }

        $form = $this->getForm();
        $changes = [
            '0',
            '',
            $this->getHistoryNameForItem($form, 'delete'),
        ];

        Log::history(
            $form->getID(),
            $form->getType(),
            $changes,
            $this->getType(),
            static::$log_history_delete
        );

        parent::post_deleteFromDB();
    }
}
