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
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Features\CloneWithoutNameSuffix;
use Glpi\Form\Clone\FormCloneHelper;
use Glpi\Form\Condition\ConditionableVisibilityInterface;
use Glpi\Form\Condition\ConditionableVisibilityTrait;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Condition\ConditionHandler\VisibilityConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use LogicException;
use Override;
use Ramsey\Uuid\Uuid;
use RuntimeException;

use function Safe\json_encode;

/**
 * Section of a given helpdesk form
 */
#[CloneWithoutNameSuffix]
final class Section extends CommonDBChild implements ConditionableVisibilityInterface, ProvideTranslationsInterface, UsedAsCriteriaInterface
{
    use ConditionableVisibilityTrait;

    public const TRANSLATION_KEY_NAME = 'section_name';
    public const TRANSLATION_KEY_DESCRIPTION = 'section_description';

    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    /**
     * Lazy loaded array of questions
     * Should always be accessed through getQuestions()
     * @var Question[]|null
     */
    protected ?array $questions = null;

    /**
     * Lazy loaded array of comments
     * Should always be accessed through getFormComments()
     * @var Comment[]|null
     */
    protected ?array $comments = null;

    private ?Form $form = null;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Section', 'Sections', $nb);
    }

    #[Override]
    public function getUUID(): string
    {
        return $this->fields['uuid'];
    }

    #[Override]
    public function post_getFromDB()
    {
        // Clear any lazy loaded data
        $this->clearLazyLoadedData();
    }

    #[Override]
    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Question::class,
                Comment::class,
                FormTranslation::class,
            ]
        );
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
        return parent::prepareInputForAdd($input);
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input);
        return parent::prepareInputForUpdate($input);
    }

    private function prepareInput($input): array
    {
        if (isset($input['_conditions'])) {
            $input['conditions'] = json_encode($input['_conditions']);
            unset($input['_conditions']);
        }

        return $input;
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $form = $this->getItem();
        if (!$form instanceof Form) {
            throw new LogicException('Section must be attached to a form');
        }

        $handlers = [];
        $key = sprintf('%s_%d', self::getType(), $this->getID());
        $category_name = sprintf('%s: %s', self::getTypeName(), $this->getName());
        if (count($form->getSections()) > 1) {
            if (!empty($this->fields['name'])) {
                $handlers[$key][] = new TranslationHandler(
                    item: $this,
                    key: self::TRANSLATION_KEY_NAME,
                    name: __('Section title'),
                    value: $this->fields['name'],
                    is_rich_text: false,
                    category: $category_name
                );
            }

            if (!empty($this->fields['description'])) {
                $handlers[$key][] = new TranslationHandler(
                    item: $this,
                    key: self::TRANSLATION_KEY_DESCRIPTION,
                    name: __('Section description'),
                    value: $this->fields['description'],
                    is_rich_text: true,
                    category: $category_name
                );
            }
        }

        $blocks_handlers = [];
        foreach ($this->getBlocks() as $block) {
            $blocks_to_process = is_array($block) ? $block : [$block];
            foreach ($blocks_to_process as $b) {
                $blocks_handlers[] = $b->listTranslationsHandlers();
            }
        }

        return array_merge($handlers, ...$blocks_handlers);
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return [new VisibilityConditionHandler()];
    }

    #[Override]
    public function getSupportedValueOperators(
        ?JsonFieldInterface $question_config
    ): array {
        return array_merge(
            ...array_map(
                fn(ConditionHandlerInterface $handler) => $handler->getSupportedValueOperators(),
                $this->getConditionHandlers($question_config)
            )
        );
    }

    /**
     * Get blocks of this section
     * Block can be a question or a comment
     * Each block implements BlockInterface and extends CommonDBChild
     *
     * @return array<int, (BlockInterface & CommonDBChild)|(BlockInterface & CommonDBChild)[]>
     */
    public function getBlocks(): array
    {
        $blocks = array_merge($this->getQuestions(), $this->getFormComments());
        $groupedBlocks = [];

        // Sort blocks by their vertical rank
        usort($blocks, fn($a, $b) => $a->fields['vertical_rank'] <=> $b->fields['vertical_rank']);

        // Group blocks by their vertical rank
        foreach ($blocks as $block) {
            $verticalRank = $block->fields['vertical_rank'];
            $horizontalRank = $block->fields['horizontal_rank'];

            if ($horizontalRank !== null) {
                if (!isset($groupedBlocks[$verticalRank])) {
                    $groupedBlocks[$verticalRank] = [];
                }
                $groupedBlocks[$verticalRank][] = $block;
            } else {
                $groupedBlocks[] = $block;
            }
        }

        // Sort blocks in a horizontal block by their horizontal rank
        foreach ($groupedBlocks as &$group) {
            if (!is_array($group)) {
                continue;
            }

            usort($group, fn($a, $b) => $a->fields['horizontal_rank'] <=> $b->fields['horizontal_rank']);
        }

        return $groupedBlocks;
    }

    /**
     * Get questions of this section
     *
     * @return Question[]
     */
    public function getQuestions(): array
    {
        // Lazy loading
        if ($this->questions === null) {
            $this->questions = [];

            // Read from database
            $questions_data = (new Question())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'vertical_rank ASC, horizontal_rank ASC',
            );
            foreach ($questions_data as $row) {
                $question = new Question();
                $question->getFromResultSet($row);
                $question->post_getFromDB();
                $question->setSection($this);

                if ($question->getQuestionType() === null) {
                    // The question might belong to a disabled plugin
                    continue;
                }

                $this->questions[$row['id']] = $question;
            }
        }

        return $this->questions;
    }

    /**
     * Get comments of this section
     *
     * @return Comment[]
     */
    public function getFormComments(): array
    {
        // Lazy loading
        if ($this->comments === null) {
            $this->comments = [];

            // Read from database
            $comments_data = (new Comment())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'vertical_rank ASC, horizontal_rank ASC',
            );
            foreach ($comments_data as $row) {
                $comment = new Comment();
                $comment->getFromResultSet($row);
                $comment->post_getFromDB();
                $comment->setSection($this);

                $this->comments[$row['id']] = $comment;
            }
        }

        return $this->comments;
    }

    /**
     * Get the parent form of this section
     *
     * @return Form
     */
    public function getForm(): Form
    {
        if ($this->form === null) {
            $form = $this->getItem();
            if (!($form instanceof Form)) {
                throw new RuntimeException("Can't load parent form");
            }

            $this->form = $form;
        }

        return $this->form;
    }

    public function setForm(Form $form): void
    {
        $this->form = $form;
    }

    #[Override]
    public function getCloneRelations(): array
    {
        return [
            Question::class,
            Comment::class,
            FormTranslation::class,
        ];
    }

    #[Override]
    public function prepareInputForClone($input)
    {
        $input = parent::prepareInputForClone($input);
        return FormCloneHelper::getInstance()->prepareSectionInputForClone($input);
    }

    /**
     * Clear lazy loaded data
     *
     * @return void
     */
    protected function clearLazyLoadedData(): void
    {
        $this->questions = null;
        $this->comments = null;
    }
}
