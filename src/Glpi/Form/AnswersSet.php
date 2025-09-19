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
use CommonDBTM;
use Glpi\Form\Destination\AnswersSet_FormDestinationItem;
use InvalidArgumentException;
use Override;
use ReflectionClass;
use User;

use function Safe\json_decode;

/**
 * Answers set for a given helpdesk form
 */
final class AnswersSet extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    public array $files = [];

    public DelegationData $delegation;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return __('Answers');
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-circle-check";
    }

    #[Override]
    public function maybeRecursive(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        $answers = $this->getAnswers();
        $answers_array = [];
        foreach ($answers as $answer) {
            $answers_array[$answer->getQuestionId()] = $answer->getRawAnswer();
        }

        return $answers_array;
    }

    /** @return Answer[] */
    public function getAnswers(): array
    {
        $answers = [];
        $raw_answers = json_decode($this->fields['answers'], true);
        foreach ($raw_answers as $raw_answer) {
            try {
                $answers[] = Answer::fromDecodedJsonData($raw_answer);
            } catch (InvalidArgumentException $e) {
                // Skip invalid data
                continue;
            }
        }

        return $answers;
    }

    public function getAnswerByQuestionId(int $id): ?Answer
    {
        $answers = $this->getAnswers();
        $filtered_answers = array_filter(
            $answers,
            fn(Answer $answer) => $answer->getQuestionId() == $id
        );

        if (count($filtered_answers) == 1) {
            return current($filtered_answers);
        } else {
            return null;
        }
    }

    public function hasAnswerForQuestionId(int $id): bool
    {
        $answer = $this->getAnswerByQuestionId($id);
        return $answer !== null;
    }

    /** @return Answer[] */
    public function getAnswersByType(string $type): array
    {
        $answers = $this->getAnswers();
        return array_filter(
            $answers,
            fn(Answer $answer) => $answer->getRawType() == $type
        );
    }

    /** @return Answer[] */
    public function getAnswersByTypes(array $types): array
    {
        $answers = $this->getAnswers();
        return array_filter(
            $answers,
            fn(Answer $answer) => in_array($answer->getRawType(), $types)
        );
    }

    #[Override]
    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'       => '3',
            'table'    => User::getTable(),
            'field'    => 'name',
            'name'     => User::getTypeName(1),
            'datatype' => 'dropdown',
        ];

        $search_options[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => '5',
            'table'         => Form::getTable(),
            'field'         => 'name',
            'name'          => Form::getTypeName(),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
        ];

        return $search_options;
    }

    #[Override]
    public static function canView(): bool
    {
        // Answers set can't be viewed from the UI
        return false;
    }

    #[Override]
    public static function canUpdate(): bool
    {
        // Answers set can't be updated from the UI
        return false;
    }

    #[Override]
    public static function canCreate(): bool
    {
        // Answers set can't be created from the UI
        return false;
    }

    #[Override]
    public static function canDelete(): bool
    {
        // Answers set can't be deleted from the UI
        return false;
    }

    /**
     * Get items linked to this form answers set
     *
     * @return CommonDBTM[]
     */
    public function getCreatedItems(): array
    {
        $items = [];

        // Find all links
        $links = (new AnswersSet_FormDestinationItem())->find([
            self::getForeignKeyField() => $this->getID(),
        ]);

        // Instanciate matching items
        foreach ($links as $link) {
            // Validate itemtype
            if (
                !is_a($link['itemtype'], CommonDBTM::class, true)
                || (new ReflectionClass($link['itemtype']))->isAbstract()
            ) {
                continue;
            }

            // Try to load item
            $item = new $link['itemtype']();
            if (!$item->getFromDB($link['items_id'])) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Get links to created items that are visible for the current user.
     *
     * @return string[]
     */
    public function getLinksToCreatedItems(): array
    {
        $links = [];
        foreach ($this->getCreatedItems() as $item) {
            if ($item->canViewItem()) {
                $links[] = $item->getLink();
            }
        }

        // If no items were created, display one link to the answers themselves
        // TODO: delete this later as we will force at least one ticket to
        // be always created.
        if ($links === []) {
            $links[] = $this->getLink();
        }

        return $links;
    }

    public function getSubmittedFiles(): array
    {
        return $this->files;
    }

    public function setSubmittedFiles(array $files): void
    {
        $this->files = $files;
    }

    /**
     * Get delegation data
     *
     * @return DelegationData
     */
    public function getDelegation(): DelegationData
    {
        return $this->delegation;
    }

    /**
     * Set delegation data
     *
     * @param DelegationData $delegation
     */
    public function setDelegation(DelegationData $delegation): void
    {
        $this->delegation = $delegation;
    }

    /**
     * Count answers for a given form
     *
     * @param Form $form
     *
     * @return int
     */
    protected function countAnswers(Form $form): int
    {
        return countElementsInTable(self::getTable(), [
            Form::getForeignKeyField() => $form->getID(),
        ]);
    }
}
