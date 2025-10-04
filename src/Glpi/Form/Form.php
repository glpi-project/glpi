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

use AbstractRightsDropdown;
use Change_Item;
use CommonDBTM;
use CommonGLPI;
use CronTask;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Features\Clonable;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\ControlTypeInterface;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Clone\FormCloneHelper;
use Glpi\Form\Condition\CommentData;
use Glpi\Form\Condition\ConditionableVisibilityInterface;
use Glpi\Form\Condition\ConditionableVisibilityTrait;
use Glpi\Form\Condition\FormData;
use Glpi\Form\Condition\QuestionData;
use Glpi\Form\Condition\SectionData;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeInterface;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\ServiceCatalog\ServiceCatalog;
use Glpi\Form\ServiceCatalog\ServiceCatalogLeafInterface;
use Glpi\Helpdesk\Tile\FormTile;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\Toolbox\UuidStore;
use Glpi\UI\IllustrationManager;
use Html;
use InvalidArgumentException;
use Item_Problem;
use Item_Ticket;
use Log;
use MassiveAction;
use Override;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use RuntimeException;
use Session;
use Throwable;
use Ticket;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\strtotime;

/**
 * Helpdesk form
 */
final class Form extends CommonDBTM implements
    ServiceCatalogLeafInterface,
    ProvideTranslationsInterface,
    ConditionableVisibilityInterface
{
    use ConditionableVisibilityTrait;
    use Clonable {
        Clonable::prepareInputForClone as parentPrepareInputForClone;
        Clonable::post_clone as parentPostClone;
    }

    public const TRANSLATION_KEY_NAME = 'form_name';
    public const TRANSLATION_KEY_HEADER = 'form_header';
    public const TRANSLATION_KEY_DESCRIPTION = 'form_description';

    public static $rightname = 'form';

    public $dohistory = true;

    public $history_blacklist = [
        'date_mod',
    ];

    /**
     * Lazy loaded array of sections
     * Should always be accessed through getSections()
     * @var Section[]|null
     */
    private ?array $sections = null;

    /** @var ?FormAccessControl[] */
    private ?array $access_controls = null;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Form', 'Forms', $nb);
    }

    #[Override]
    public function getUUID(): string
    {
        return $this->fields['uuid'];
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-forms";
    }

    #[Override]
    public static function getSectorizedDetails(): array
    {
        return ['admin', self::class];
    }

    #[Override]
    public function defineTabs($options = [])
    {
        $tabs = parent::defineTabs();
        $this->addStandardTab(ServiceCatalog::class, $tabs, $options);
        if (Item_Ticket::countForItemAndLinked($this) > 0) {
            $this->addStandardTab(Item_Ticket::class, $tabs, $options);
        }
        if (Change_Item::countForItemAndLinked($this) > 0) {
            $this->addStandardTab(Change_Item::class, $tabs, $options);
        }
        if (Item_Problem::countForItemAndLinked($this) > 0) {
            $this->addStandardTab(Item_Problem::class, $tabs, $options);
        }
        $this->addStandardTab(FormAccessControl::class, $tabs, $options);
        $this->addStandardTab(FormDestination::class, $tabs, $options);
        $this->addStandardTab(FormTranslation::class, $tabs, $options);
        $this->addStandardTab(Log::class, $tabs, $options);
        $tabs['no_all_tab'] = true;
        return $tabs;
    }

    #[Override]
    public function showForm($id, array $options = [])
    {
        if (!empty($id)) {
            $this->getFromDB($id);
        } else {
            $this->getEmpty();
        }
        $this->initForm($id, $options);

        $types_manager = QuestionTypesManager::getInstance();

        // Use the uuid store to prevent condition data from fetching the same
        // questions over and over
        $store = UuidStore::getInstance();
        foreach ($this->getSections() as $section) {
            $store->addToStore($section->fields['uuid'], $section);
        }
        foreach ($this->getQuestions() as $question) {
            $store->addToStore($question->fields['uuid'], $question);
        }
        foreach ($this->getFormComments() as $comment) {
            $store->addToStore($comment->fields['uuid'], $comment);
        }

        // Render twig template
        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/admin/form/form_editor.html.twig', [
            'item'                         => $this,
            'can_update'                   => $this->canUpdate(),
            'params'                       => $options,
            'question_types_manager'       => $types_manager,
            'invalid_questions'            => $this->getInvalidQuestions(),
            'allow_unauthenticated_access' => FormAccessControlManager::getInstance()->allowUnauthenticatedAccess($this),
        ]);
        $store->purge();
        return true;
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if (!$item instanceof Category) {
            return "";
        }

        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(self::getTable(), [
                'forms_categories_id' => $item->getID(),
            ]);
        }

        return self::createTabEntry(
            self::getTypeName(Session::getPluralNumber()),
            $nb,
        );
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ): bool {
        if (!$item instanceof Category) {
            return false;
        }

        self::displayList([
            [
                'link'       => 'AND',
                'field'      => 6,  // Service catalog category
                'searchtype' => 'equals',
                'value'      => $item->getID(),
            ],
        ], 1 /* Sort by name */);
        return true;
    }

    #[Override]
    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'            => '2',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number',
        ];
        $search_options[] = [
            'id'            => '80',
            'table'         => Entity::getTable(),
            'field'         => 'completename',
            'name'          => Entity::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
        ];
        $search_options[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];
        $search_options[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $search_options[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];
        $search_options[] = [
            'id'            => '6',
            'table'         => Category::getTable(),
            'field'         => 'completename',
            'name'          => Category::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => true,
        ];

        return $search_options;
    }

    #[Override]
    public function post_getFromDB()
    {
        // Clear lazy loaded data
        $this->clearLazyLoadedData();
    }

    #[Override]
    public function post_addItem()
    {
        $from_import    = $this->input['_from_import']    ?? false;
        $from_migration = $this->input['_from_migration'] ?? false;
        if ($from_import || $from_migration) {
            // Do not run extra process as it is not a real item creation
            return;
        }

        // Automatically create the first form section unless specified otherwise
        $init_sections = $this->input['_init_sections'] ?? true;
        if ($init_sections) {
            $this->createFirstSection();
        }

        // Add the default destinations
        $add_default_destinations = $this->input['_init_destinations'] ?? true;
        if ($add_default_destinations) {
            $this->addDefaultDestinations();
        }

        // Add the default access policies unless specified otherwise
        $init_policies = $this->input['_init_access_policies'] ?? true;
        if ($init_policies) {
            $this->addDefaultAccessPolicies();
        }
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        if (!isset($input['uuid'])) {
            $input['uuid'] = Uuid::uuid4();
        }

        // JSON fields must have a value when created to prevent SQL errors
        if (!isset($input['submit_button_conditions'])) {
            $input['submit_button_conditions'] = json_encode([]);
        }

        $input = $this->prepareInput($input);
        return parent::prepareInputForAdd($input);
    }

    #[Override]
    public function prepareInputForUpdate($input): array
    {
        // Insert date_mod even if the framework would handle it by itself
        // This avoid "empty" updates when the form itself is not modified but
        // its questions are
        $input['date_mod'] = $_SESSION['glpi_currenttime'];

        $input = $this->prepareInput($input);
        return parent::prepareInputForUpdate($input);
    }

    private function prepareInput($input): array
    {
        if (isset($input['_conditions'])) {
            $input['submit_button_conditions'] = json_encode($input['_conditions']);
            unset($input['_submit_button_conditions']);
        }

        return $input;
    }

    #[Override]
    public function post_updateItem($history = true)
    {
        global $DB;

        $DB->beginTransaction();
        try {
            // Update questions and sections
            $this->updateExtraFormData();
            $DB->commit();
        } catch (Throwable $e) {
            // Delete the "Item sucessfully updated" message if it exist
            Session::deleteMessageAfterRedirect(
                $this->formatSessionMessageAfterAction(__('Item successfully updated'))
            );

            // Do not keep half updated data
            $DB->rollback();

            // Propagate exception to ensure the server return an error code
            throw $e;
        }
    }

    #[Override]
    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Section::class,
                FormDestination::class,
                FormAccessControl::class,
                FormTile::class,
                FormTranslation::class,
                Item_Ticket::class,
            ]
        );
    }

    #[Override]
    public function getSpecificMassiveActions($checkitem = null): array
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        $key = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . "export";
        $icon = '<i class="ti ti-file-arrow-right"></i>';
        $label = __s('Export form');
        $actions[$key] = $icon . $label;

        return $actions;
    }

    #[Override]
    public static function showMassiveActionsSubForm(MassiveAction $ma): bool
    {
        global $CFG_GLPI;

        $ids = array_values($ma->getItems()[Form::class]);
        $export_url = $CFG_GLPI['url_base'];
        $export_url .= "/Form/Export?" . http_build_query(['ids' => $ids]);

        $label = __s("Click here to download the exported forms...");
        echo "<a href=\"" . \htmlescape($export_url) . "\">$label</a>";
        echo Html::scriptBlock("window.location.href = '" . \jsescape($export_url) . "';");

        return true;
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s_%d', self::getType(), $this->getID());
        $category_name = __('Form properties');
        $handlers = [];
        if (!empty($this->fields['name'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_NAME,
                name: __('Form title'),
                value: $this->fields['name'],
                is_rich_text: false,
                category: $category_name
            );
        }

        if (!empty($this->fields['header'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_HEADER,
                name: __('Form description'),
                value: $this->fields['header'],
                is_rich_text: true,
                category: $category_name
            );
        }

        if (!empty($this->fields['description'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_DESCRIPTION,
                name: __('Service catalog description'),
                value: $this->fields['description'],
                is_rich_text: true,
                category: $category_name
            );
        }

        $sections_handlers = array_map(
            fn($section) => $section->listTranslationsHandlers(),
            $this->getSections()
        );

        return array_merge($handlers, ...$sections_handlers);
    }

    protected function getVisibilityStrategyFieldName(): string
    {
        return 'submit_button_visibility_strategy';
    }

    protected function getConditionsFieldName(): string
    {
        return 'submit_button_conditions';
    }

    public static function getAdditionalMenuLinks(): array
    {
        $links = [];

        if (self::canCreate()) {
            $links['view_form_categories'] = Category::getSearchURL(false);
            $links['import_forms'] = '/Form/Import';
        }

        return $links;
    }

    /**
     * Give cron information
     *
     * @param string $name  Task's name
     *
     * @return array Array of information
     **/
    public static function cronInfo($name)
    {
        return [
            'description' => __('Purge old form drafts'),
            'parameter'   => __('Form drafts retention period (in days)'),
        ];
    }

    /**
     * Cron action to purge old form drafts
     *
     * @param CronTask $task
     *
     * @return int
     * @used-by CronTask
     */
    public static function cronPurgeDraftForms(CronTask $task): int
    {
        $delay = (int) $task->fields['param'];
        $form = new Form();
        $form->deleteByCriteria([
            'is_draft' => 1,
            'date_mod' => ['<', date('Y-m-d H:i:s', strtotime(sprintf('-%d day', $delay)))],
        ], true);

        return 1;
    }

    /**
     * Get sections of this form
     *
     * @return Section[]
     */
    public function getSections(): array
    {
        // Lazy loading
        if ($this->sections === null) {
            $this->sections = [];

            // Read from database
            $sections_data = (new Section())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'rank ASC',
            );

            foreach ($sections_data as $row) {
                $section = new Section();
                $section->getFromResultSet($row);
                $section->post_getFromDB();
                $section->setForm($this);
                $this->sections[$row['id']] = $section;
            }
        }

        return $this->sections;
    }

    /**
     * Get all questions for this form
     *
     * @return Question[]
     */
    public function getQuestions(): array
    {
        $questions = [];
        foreach ($this->getSections() as $section) {
            // Its important to use the "+" operator here and not array_merge
            // because the keys must be preserved
            $questions += $section->getQuestions();
        }
        return $questions;
    }

    /**  @return Question[] */
    public function getInvalidQuestions(): array
    {
        $invalid_questions = [];
        foreach ($this->getSections() as $section) {
            // Its important to use the "+" operator here and not array_merge
            // because the keys must be preserved
            $questions_data = (new Question())->find(
                [$section::getForeignKeyField() => $section->fields['id']],
                'vertical_rank ASC, horizontal_rank ASC',
            );
            foreach ($questions_data as $row) {
                $question = new Question();
                $question->getFromResultSet($row);
                $question->post_getFromDB();
                if ($question->getQuestionType() === null) {
                    $invalid_questions[] = $question;
                }
            }
        }
        return $invalid_questions;
    }

    /**
     * Get all comments for this form
     *
     * @return Comment[]
     */
    public function getFormComments(): array
    {
        $comments = [];
        foreach ($this->getSections() as $section) {
            // Its important to use the "+" operator here and not array_merge
            // because the keys must be preserved
            $comments += $section->getFormComments();
        }
        return $comments;
    }

    /**
     * Get all defined destinations of this form
     *
     * @return FormDestination[]
     */
    public function getDestinations(): array
    {
        $destinations = [];
        $destinations_data = (new FormDestination())->find(
            [self::getForeignKeyField() => $this->fields['id']],
        );

        foreach ($destinations_data as $row) {
            $destination = new FormDestination();
            $destination->getFromResultSet($row);
            $destination->post_getFromDB();
            $destinations[$row['id']] = $destination;
        }

        return $destinations;
    }

    /**
     * @return FormAccessControl[]
     */
    public function getAccessControls(): array
    {
        if ($this->access_controls === null) {
            $controls = [];

            $raw_controls = (new FormAccessControl())->find([
                Form::getForeignKeyField() => $this->getID(),
            ]);

            // Make sure all returned data are valid (some data might come from
            // disabled plugins).
            foreach ($raw_controls as $row) {
                if (!$this->isValidAccessControlType($row['strategy'])) {
                    continue;
                }

                $control = new FormAccessControl();
                $control->getFromResultSet($row);
                $control->post_getFromDB();
                $controls[] = $control;
            }

            $this->access_controls = $controls;
        }

        return $this->access_controls;
    }

    /**
     * Get questions of this form that match the given types.
     *
     * @param string[] $types
     * @return Question[]
     */
    public function getQuestionsByTypes(array $types): array
    {
        foreach ($types as $type) {
            if (!$this->isValidQuestionType($type)) {
                throw new InvalidArgumentException("Invalid question type: $type");
            }
        }

        return array_filter(
            $this->getQuestions(),
            function (Question $question) use ($types) {
                $type = $question->getQuestionType() !== null ? get_class($question->getQuestionType()) : self::class;
                return in_array($type, $types);
            }
        );
    }

    /**
     * Get questions of this form that match the given type.
     *
     * @param string $type
     * @return Question[]
     */
    public function getQuestionsByType(string $type): array
    {
        return $this->getQuestionsByTypes([$type]);
    }

    /** @return SectionData[] */
    public function getSectionsStateForConditionEditor(): array
    {
        return FormData::createFromForm($this)->getSectionsData();
    }

    /** @return QuestionData[] */
    public function getQuestionsStateForConditionEditor(): array
    {
        return FormData::createFromForm($this)->getQuestionsData();
    }

    /** @return CommentData[] */
    public function getCommentsStateForConditionEditor(): array
    {
        return FormData::createFromForm($this)->getCommentsData();
    }

    public function getCloneRelations(): array
    {
        return [
            Section::class,
            FormAccessControl::class,
            FormDestination::class,
            FormTranslation::class,
        ];
    }
    public function prepareInputForClone($input): array
    {
        $input = $this->parentPrepareInputForClone($input);
        return FormCloneHelper::getInstance()->prepareFormInputForClone($input);
    }

    public function post_clone($source, $history): void
    {
        FormCloneHelper::getInstance()->postFormClone($this);
    }

    /**
     * Update extra form data found in other tables (sections and questions)
     *
     * @return void
     */
    protected function updateExtraFormData(): void
    {
        // We must update sections first, as questions depend on them.
        // However, they must only be deleted after questions have been updated.
        // This prevents cascade deletion to delete their questions that might
        // have been moved to another section.
        $this->updateSections();
        $this->updateQuestions();
        $this->updateComments();
        $this->deleteMissingSections();
        $this->deleteMissingQuestions();
        $this->deleteMissingComments();
    }

    /**
     * Clear lazy loaded data
     *
     * @return void
     */
    protected function clearLazyLoadedData(): void
    {
        $this->sections = null;
        $this->access_controls = null;
    }

    /**
     * Create the first section of a form
     *
     * @return void
     */
    protected function createFirstSection(): void
    {
        $section = new Section();
        $section->add([
            'forms_forms_id' => $this->fields['id'],
            'name'           => __("First section"),
            'rank'           => 0,
        ]);
    }

    /**
     * Update form's sections using the special data found in
     * $this->input['_sections']
     *
     * @return void
     */
    protected function updateSections(): void
    {
        $sections = $this->input['_sections'] ?? [];

        // Keep track of sections found
        $found_sections = [];

        // Parse each submitted section
        foreach ($sections as $form_data) {
            // Read UUID
            $uuid = $form_data['uuid'] ?? '';
            if (empty($uuid)) {
                throw new RuntimeException(
                    "UUID is missing: " . json_encode($form_data)
                );
            }

            $section = Section::getByUuid($uuid);
            if ($section === null) {
                // Add new section
                $section = new Section();
                $id = $section->add($form_data);

                if (!$id) {
                    throw new RuntimeException("Failed to add section");
                }
            } else {
                // Update existing section
                $form_data['id'] = $section->getID();

                $success = $section->update($form_data);
                if (!$success) {
                    throw new RuntimeException("Failed to update section");
                }
                $id = $section->getID();
            }

            // Keep track of its id
            $found_sections[] = $id;
        }

        // Deletion will be handled in a separate method
        $this->input['_found_sections'] = $found_sections;

        // Special input has been handled, it can be deleted
        unset($this->input['_sections']);
    }

    /**
     * Delete sections that were not found in the submitted data
     *
     * @return void
     */
    protected function deleteMissingSections(): void
    {
        // We can't run this code if we don't have the list of updated sections
        if (!isset($this->input['_found_sections'])) {
            return;
        }
        $found_sections = $this->input['_found_sections'];

        // Safety check to avoid deleting all sections if some code run an update
        // without the _sections keys.
        // Deletion is only done if the special "_delete_missing_sections" key
        // is present
        $delete_missing_sections = $this->input['_delete_missing_sections'] ?? false;
        if ($delete_missing_sections) {
            // Avoid empty IN clause
            if (empty($found_sections)) {
                $found_sections = [-1];
            }

            $missing_sections = (new Section())->find([
                // Is part of this form
                'forms_forms_id' => $this->fields['id'],

                // Was not found in the submitted data
                'id' => ['NOT IN', $found_sections],
            ]);

            foreach ($missing_sections as $row) {
                $section = new Section();
                $success = $section->delete($row);
                if (!$success) {
                    throw new RuntimeException("Failed to delete section");
                }
            }
        }

        unset($this->input['_found_sections']);
    }

    /**
     * Update form's questions using the special data found in
     * $this->input['_questions']
     *
     * @return void
     */
    protected function updateQuestions(): void
    {
        $questions = $this->input['_questions'] ?? [];

        // Keep track of questions found
        $found_questions = [];

        // Parse each submitted question
        foreach ($questions as $question_data) {
            $question = new Question();

            // Read uuids
            $uuid = $question_data['uuid'] ?? '';
            if (empty($uuid)) {
                throw new RuntimeException(
                    "UUID is missing: " . json_encode($question_data)
                );
            }
            $section_uuid = $question_data['forms_sections_uuid'] ?? '';
            if (empty($section_uuid)) {
                throw new RuntimeException(
                    "Parent section UUID is missing: " . json_encode($question_data)
                );
            }

            // Get parent id
            $section = Section::getByUuid($section_uuid);
            if ($section === null) {
                throw new RuntimeException("Parent section not found: $section_uuid");
            }
            $question_data['forms_sections_id'] = $section->getID();

            $question = Question::getByUuid($uuid);
            if ($question === null) {
                // Add new question
                $question = new Question();
                $id = $question->add($question_data);

                if (!$id) {
                    throw new RuntimeException("Failed to add question");
                }
            } else {
                // Update existing question
                $question_data['id'] = $question->getID();

                $success = $question->update($question_data);
                if (!$success) {
                    throw new RuntimeException("Failed to update question");
                }
                $id = $question->getID();
            }

            // Keep track of its id
            $found_questions[] = $id;
        }

        // Deletion will be handled in a separate method
        $this->input['_found_questions'] = $found_questions;

        // Special input has been handled, it can be deleted
        unset($this->input['_questions']);
    }

    /**
     * Delete sections that were not found in the submitted data
     *
     * @return void
     */
    protected function deleteMissingQuestions(): void
    {
        // We can't run this code if we don't have the list of updated sections
        if (!isset($this->input['_found_questions'])) {
            return;
        }
        $found_questions = $this->input['_found_questions'];

        // Safety check to avoid deleting all questions if some code run an update
        // without the _questions keys.
        // Deletion is only done if the special "_delete_missing_questions" key
        // is present
        $delete_missing_questions = $this->input['_delete_missing_questions'] ?? false;
        if ($delete_missing_questions) {
            // Avoid empty IN clause
            if (empty($found_questions)) {
                $found_questions = [-1];
            }

            $missing_questions = (new Question())->find([
                // Is part of this form
                'forms_sections_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => Section::getTable(),
                    'WHERE'  => [
                        'forms_forms_id' => $this->fields['id'],
                    ],
                ]),
                // Was not found in the submitted data
                'id' => ['NOT IN', $found_questions],
            ]);

            foreach ($missing_questions as $row) {
                $question = new Question();
                $success = $question->delete($row);
                if (!$success) {
                    throw new RuntimeException("Failed to delete question");
                }
            }
        }

        unset($this->input['_found_questions']);
    }

    /**
     * Update form's comments using the special data found in
     * $this->input['_comments']
     *
     * @return void
     */
    protected function updateComments(): void
    {
        $comments = $this->input['_comments'] ?? [];

        // Keep track of comments found
        $found_comments = [];

        // Parse each submitted comment
        foreach ($comments as $comment_data) {
            $comment = new Comment();

            // Read uuids
            $uuid = $comment_data['uuid'] ?? '';
            if (empty($uuid)) {
                throw new RuntimeException(
                    "UUID is missing: " . json_decode($comment_data)
                );
            }

            $section_uuid = $comment_data['forms_sections_uuid'] ?? '';
            if (empty($section_uuid)) {
                throw new RuntimeException(
                    "Parent section UUID is missing: " . json_decode($comment_data)
                );
            }

            // Get parent id
            $section = Section::getByUuid($section_uuid);
            if ($section === null) {
                throw new RuntimeException("Parent section not found: $section_uuid");
            }
            $comment_data['forms_sections_id'] = $section->getID();

            $comment = Comment::getByUuid($uuid);
            if ($comment === null) {
                // Add new question
                $comment = new Comment();
                $id = $comment->add($comment_data);

                if (!$id) {
                    throw new RuntimeException("Failed to add comment");
                }
            } else {
                // Update existing comment
                $comment_data['id'] = $comment->getID();

                $success = $comment->update($comment_data);
                if (!$success) {
                    throw new RuntimeException("Failed to update comment");
                }
                $id = $comment->getID();
            }
            // Keep track of its id
            $found_comments[] = $id;
        }

        // Deletion will be handled in a separate method
        $this->input['_found_comments'] = $found_comments;

        // Special input has been handled, it can be deleted
        unset($this->input['_comments']);
    }

    /**
     * Delete comments that were not found in the submitted data
     *
     * @return void
     */
    protected function deleteMissingComments(): void
    {
        // We can't run this code if we don't have the list of updated comments
        if (!isset($this->input['_found_comments'])) {
            return;
        }
        $found_comments = $this->input['_found_comments'];

        // Safety check to avoid deleting all comments if some code run an update
        // without the _comments keys.
        // Deletion is only done if the special "_delete_missing_comments" key
        // is present
        $delete_missing_comments = $this->input['_delete_missing_comments'] ?? false;
        if ($delete_missing_comments) {
            // Avoid empty IN clause
            if (empty($found_comments)) {
                $found_comments = [-1];
            }

            $missing_comments = (new Comment())->find([
                // Is part of this form
                'forms_sections_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => Section::getTable(),
                    'WHERE'  => [
                        'forms_forms_id' => $this->fields['id'],
                    ],
                ]),
                // Was not found in the submitted data
                'id' => ['NOT IN', $found_comments],
            ]);

            foreach ($missing_comments as $row) {
                $comment = new Comment();
                $success = $comment->delete($row);
                if (!$success) {
                    throw new RuntimeException("Failed to delete comment");
                }
            }
        }

        unset($this->input['_found_comments']);
    }

    /**
     * Check if the given class is a valid access control type.
     *
     * @param string $class
     *
     * @return bool
     */
    protected function isValidAccessControlType(string $class): bool
    {
        return
            is_a($class, ControlTypeInterface::class, true)
            && !(new ReflectionClass($class))->isAbstract()
        ;
    }

    protected function isValidQuestionType(string $class): bool
    {
        return
            is_a($class, QuestionTypeInterface::class, true)
            && !(new ReflectionClass($class))->isAbstract()
        ;
    }

    #[Override]
    public function getServiceCatalogItemTitle(): string
    {
        return FormTranslation::translate(
            $this,
            static::TRANSLATION_KEY_NAME
        ) ?? '';
    }

    #[Override]
    public function getServiceCatalogItemDescription(): string
    {
        return FormTranslation::translate(
            $this,
            static::TRANSLATION_KEY_DESCRIPTION
        ) ?? '';
    }

    #[Override]
    public function getServiceCatalogItemIllustration(): string
    {
        return $this->fields['illustration'] ?: IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function isServiceCatalogItemPinned(): bool
    {
        return $this->fields['is_pinned'] ?? false;
    }

    #[Override]
    public function getServiceCatalogLink(): string
    {
        return Html::getPrefixedUrl("/Form/Render/" . $this->getID());
    }

    /**
     * Get the number of times this form has been used
     *
     * @return int
     */
    public function getUsageCount(): int
    {
        return $this->fields['usage_count'] ?? 0;
    }

    private function addDefaultDestinations(): void
    {
        $destination = new FormDestination();
        $id = $destination->add([
            self::getForeignKeyField() => $this->getId(),
            'itemtype'                 => FormDestinationTicket::class,
            'name'                     => Ticket::getTypeName(1),
        ]);
        if (!$id) {
            throw new RuntimeException("Failed to initialize destinations");
        }
    }

    private function addDefaultAccessPolicies(): void
    {
        $policy = new FormAccessControl();
        $policy->add([
            Form::getForeignKeyField() => $this->getID(),
            'strategy' => AllowList::class,
            '_config'   => new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            'is_active' => 1,
        ]);
    }
}
