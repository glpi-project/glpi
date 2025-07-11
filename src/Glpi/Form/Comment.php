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

namespace Glpi\Form;

use CommonDBChild;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionableVisibilityInterface;
use Glpi\Form\Condition\ConditionableVisibilityTrait;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\Form\Condition\ConditionHandler\VisibilityConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Log;
use Override;
use Ramsey\Uuid\Uuid;
use RuntimeException;

use function Safe\json_encode;

/**
 * Comment of a given helpdesk form's section
 */
final class Comment extends CommonDBChild implements
    BlockInterface,
    ConditionableVisibilityInterface,
    UsedAsCriteriaInterface
{
    use ConditionableVisibilityTrait;

    public const TRANSLATION_KEY_NAME = 'comment_name';
    public const TRANSLATION_KEY_DESCRIPTION = 'comment_description';

    public static $itemtype = Section::class;
    public static $items_id = 'forms_sections_id';

    public $dohistory = true;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Comment', 'Comments', $nb);
    }

    #[Override]
    public function getUUID(): string
    {
        return $this->fields['uuid'];
    }

    #[Override]
    public function post_addItem()
    {
        // Report logs to the parent form
        $this->logCreationInParentForm();
    }

    #[Override]
    public function post_updateItem(bool $history = true): void
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

    #[Override]
    public function prepareInputForAdd($input)
    {
        if (!isset($input['uuid'])) {
            $input['uuid'] = Uuid::uuid4();
        }

        // JSON fields must have a value when created to prevent SQL errors
        if (!isset($input['conditions'])) {
            $input['conditions'] = json_encode([]);
        }

        $input = $this->prepareInput($input);
        return parent::prepareInputForUpdate($input);
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input);
        return parent::prepareInputForUpdate($input);
    }

    private function prepareInput($input): array
    {
        // Set parent UUID
        if (
            isset($input['forms_sections_id'])
            && !isset($input['forms_sections_uuid'])
        ) {
            $section = Section::getById($input['forms_sections_id']);
            $input['forms_sections_uuid'] = $section->fields['uuid'];
        }

        if (isset($input['_conditions'])) {
            $input['conditions'] = json_encode($input['_conditions']);
            unset($input['_conditions']);
        }

        return $input;
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s: %s', self::getTypeName(), $this->getName());
        $handlers = [];

        if (!empty($this->fields['name'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_NAME,
                name: __('Comment title'),
                value: $this->fields['name'],
            );
        }

        if (!empty($this->fields['description'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_DESCRIPTION,
                name: __('Comment description'),
                value: $this->fields['description'],
                is_rich_text: true,
            );
        }

        return $handlers;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return [new VisibilityConditionHandler()];
    }

    #[Override]
    public function displayBlockForEditor(): void
    {
        TemplateRenderer::getInstance()->display('pages/admin/form/form_comment.html.twig', [
            'form'       => $this->getForm(),
            'comment'    => $this,
            'section'    => $this->getItem(),
            'can_update' => $this->getForm()->canUpdate(),
        ]);
    }

    #[Override]
    public function getUntitledLabel(): string
    {
        return __('Untitled comment');
    }

    /**
     * Get the parent form of this question
     *
     * @return Form
     */
    public function getForm(): Form
    {
        $section = $this->getItem();
        if (!($section instanceof Section)) {
            throw new RuntimeException("Can't load parent section");
        }

        $form = $section->getItem();
        if (!($form instanceof Form)) {
            throw new RuntimeException("Can't load parent form");
        }

        return $form;
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
            if (count($changes) != 3) {
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
