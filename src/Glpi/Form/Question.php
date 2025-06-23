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
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Condition\ConditionableValidationTrait;
use Glpi\Form\Condition\ConditionableVisibilityInterface;
use Glpi\Form\Condition\ConditionableVisibilityTrait;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportData;
use Glpi\Form\QuestionType\QuestionTypeInterface;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\QuestionType\TranslationAwareQuestionType;
use Glpi\ItemTranslation\Context\TranslationHandler;
use JsonException;
use Log;
use Override;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use RuntimeException;

/**
 * Question of a given helpdesk form's section
 */
final class Question extends CommonDBChild implements BlockInterface, ConditionableVisibilityInterface
{
    use ConditionableVisibilityTrait;
    use ConditionableValidationTrait;

    public const TRANSLATION_KEY_NAME          = 'question_name';
    public const TRANSLATION_KEY_DESCRIPTION   = 'question_description';
    public const TRANSLATION_KEY_DEFAULT_VALUE = 'question_default_value';

    public static $itemtype = Section::class;
    public static $items_id = 'forms_sections_id';

    public $dohistory = true;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Question', 'Questions', $nb);
    }

    #[Override]
    public function getUUID(): string
    {
        return $this->fields['uuid'];
    }

    #[Override]
    public function isEntityAssign()
    {
        return false;
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

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s: %s', self::getTypeName(), $this->getName());
        $handlers = [];
        if (!empty($this->fields['name'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_NAME,
                name: __('Question name'),
                value: $this->fields['name'],
            );
        }

        if (!empty($this->fields['description'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_DESCRIPTION,
                name: __('Question description'),
                value: $this->fields['description'],
                is_rich_text: true,
            );
        }

        $question_type = $this->getQuestionType();
        if ($question_type instanceof TranslationAwareQuestionType) {
            $handlers[$key] = array_merge(
                $handlers[$key] ?? [],
                array_values($question_type->listTranslationsHandlers($this))
            );
        }

        return $handlers;
    }

    #[Override]
    public function displayBlockForEditor(): void
    {
        TemplateRenderer::getInstance()->display('pages/admin/form/form_question.html.twig', [
            'form'                         => $this->getForm(),
            'question'                     => $this,
            'question_type'                => $this->getQuestionType(),
            'question_types_manager'       => QuestionTypesManager::getInstance(),
            'section'                      => $this->getItem(),
            'can_update'                   => $this->getForm()->canUpdate(),
            'allow_unauthenticated_access' => FormAccessControlManager::getInstance()->allowUnauthenticatedAccess($this->getForm()),
        ]);
    }

    #[Override]
    public function getUntitledLabel(): string
    {
        return __('Untitled question');
    }

    /**
     * Get type object for the current object.
     *
     * @return QuestionTypeInterface|null
     */
    public function getQuestionType(): ?QuestionTypeInterface
    {
        $type = $this->fields['type'] ?? "";

        if (
            !is_a($type, QuestionTypeInterface::class, true)
            || (new ReflectionClass($type))->isAbstract()
        ) {
            return null;
        }

        return new $type();
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

    public function getEndUserInputName(): string
    {
        return (new EndUserInputNameProvider())->getEndUserInputName($this);
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

        if (!isset($input['validation_conditions'])) {
            $input['validation_conditions'] = json_encode([]);
        }

        $input = $this->prepareInput($input);
        return parent::prepareInputForAdd($input);
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input);
        return parent::prepareInputForUpdate($input);
    }

    public function setDefaultValueFromParameters(array $get): void
    {
        $uuid = $this->fields['uuid'];

        // Apply value if defined
        if (isset($get[$uuid])) {
            $type = $this->getQuestionType();
            $value = $type->formatPredefinedValue($get[$uuid]);

            if ($value !== null) {
                $this->fields['default_value'] = $value;
            }
        }
    }

    public function exportDynamicData(): DynamicExportData
    {
        $type = $this->getQuestionType();

        $extra_data = $this->getExtraDataConfig();
        $default_value = $this->getDefaultValue();

        $data = new DynamicExportData();
        $data->addField('extra_data', $type->exportDynamicExtraData($extra_data));
        $data->addField('default_value', $type->exportDynamicDefaultValue(
            $extra_data,
            $default_value,
        ));

        return $data;
    }

    public static function prepareDynamicImportData(
        QuestionTypeInterface $type,
        array $input,
        DatabaseMapper $mapper,
    ): array {
        $input['extra_data'] = $type->prepareDynamicExtraDataForImport(
            $input['extra_data'],
            $mapper,
        );
        $input['default_value'] = $type->prepareDynamicDefaultValueForImport(
            $input['extra_data'],
            $input['default_value'],
            $mapper,
        );

        return $input;
    }

    private function prepareInput($input): array
    {
        $is_creating = ($input['id'] ?? 0) === 0;

        // Set parent UUID
        if (
            isset($input['forms_sections_id'])
            && !isset($input['forms_sections_uuid'])
        ) {
            $section = Section::getById($input['forms_sections_id']);
            $input['forms_sections_uuid'] = $section->fields['uuid'];
        }

        // Set horizontal rank to null if not set
        if (!isset($input['horizontal_rank'])) {
            $input['horizontal_rank'] = 'NULL';
        }

        if (isset($input['_conditions'])) {
            $input['conditions'] = json_encode($input['_conditions']);
            unset($input['_conditions']);
        }

        if (isset($input['_validation_conditions'])) {
            $input['validation_conditions'] = json_encode($input['_validation_conditions']);
            unset($input['_validation_conditions']);
        }

        $question_type = $this->getQuestionType();

        // The question type can be null when the question is created
        // We need to instantiate the question type to format and validate attributes
        if (
            isset($input['type'])
            && is_a($input['type'], QuestionTypeInterface::class, true)
        ) {
            $question_type = new $input['type']();
        }

        if ($question_type) {
            if (isset($input['default_value'])) {
                $input['default_value'] = $question_type->formatDefaultValueForDB($input['default_value']);
            }

            if (isset($input['extra_data']) || $is_creating) {
                $extra_data = $input['extra_data'] ?? [];
                if (is_string($extra_data)) {
                    if (empty($extra_data)) {
                        $extra_data = [];
                    } else {
                        // Decode extra data as JSON
                        $extra_data = json_decode($extra_data, true);
                    }
                }

                $is_extra_data_valid = $question_type->validateExtraDataInput($extra_data);

                if (!$is_extra_data_valid) {
                    throw new \InvalidArgumentException("Invalid extra data for question");
                }

                // Prepare extra data
                $extra_data = $question_type->prepareExtraData($extra_data);

                // Save extra data as JSON
                if (!empty($extra_data)) {
                    $input['extra_data'] = json_encode($extra_data);
                }
            }
        }

        return $input;
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

    private function getExtraDataConfig(): ?JsonFieldInterface
    {
        $data = $this->fields['extra_data'];

        // Empty config
        if ($data === null) {
            return null;
        }

        // Specific config
        try {
            $decoded_data = json_decode(
                $data,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            // Invalid format, fallback to null config
            return null;
        }

        return $this->getQuestionType()->getExtraDataConfig($decoded_data);
    }

    private function getDefaultValue(): array|int|float|bool|string|null
    {
        $default_value = $this->fields['default_value'];

        // Empty config
        if ($default_value === null) {
            return null;
        }

        // Specific value that is not a string (raw numbers or bool)
        if (!is_string($default_value)) {
            return $default_value;
        }

        // Specific value that may be a string OR a json encoded object/array
        try {
            return json_decode(
                $default_value,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            // Specific value is a simple string
            return $default_value;
        }
    }
}
