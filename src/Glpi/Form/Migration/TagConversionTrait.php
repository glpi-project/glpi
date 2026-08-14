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

namespace Glpi\Form\Migration;

use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\ItemPropertyTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Search\SearchOption;

use function Safe\preg_match_all;

/**
 * Provides common functionality for converting legacy tags to new format
 */
trait TagConversionTrait
{
    /**
     * Maps Formcreator property names to database fields
     */
    private const FORMCREATOR_FIELD_MAP = [
        '*' => [
            'name'        => 'name',
            'comment'     => 'comment',
        ],
        \User::class => [
            'login'       => 'name',
            'firstname'   => 'firstname',
            'realname'    => 'realname',
            'phone'       => 'phone',
            'phone2'      => 'phone2',
            'mobile'      => 'mobile',
            'email'       => 'emails',
            'emails'      => 'emails',
        ],
        \Computer::class => [
            'serial'      => 'serial',
            'otherserial' => 'otherserial',
            'contact'     => 'contact',
            'contact_num' => 'contact_num',
        ],
    ];

    /**
     * Convert legacy tags in the format ##question_ID## or ##answer_ID## to new tag format
     *
     * @param string $content Content containing legacy tags
     * @param FormMigration $migration Migration object for ID mapping
     * @return string Content with converted tags
     */
    protected function convertLegacyTags(string $content, FormMigration $migration): string
    {
        // Skip processing if content is just the full form placeholder
        if (strip_tags($content) === '##FULLFORM##') {
            return $content;
        }

        preg_match_all('/##(question_\d+|answer_\d+(?:\.\w+)?)##/', $content, $tags);
        foreach ($tags[1] as $tag) {

            if (str_contains($tag, '.')) {
                [$answer_part, $property_name] = explode('.', $tag, 2);
                $item_id = (int) explode('_', $answer_part)[1];

                $target = $migration->getMappedItemTarget('PluginFormcreatorQuestion', $item_id);
                if (empty($target)) {
                    continue;
                }
                $question_id = $target['items_id'];
                $question = Question::getById($question_id);
                if (!$question) {
                    continue;
                }

                // Resolve the FormCreator property name to a search option ID
                $search_option_id = self::resolveFormcreatorPropertyToSearchOptionId($question, $property_name);
                if ($search_option_id === null) {
                    continue;
                }

                $provider = new ItemPropertyTagProvider();
                $new_tag = $provider->getTagFromRawValue($question_id . ':' . $search_option_id);
                if ($new_tag === null) {
                    continue;
                }

                $content = str_replace("##$tag##", $new_tag->html, $content);
                continue;
            }

            $type = explode('_', $tag)[0];
            $item_id = (int) explode('_', $tag)[1];

            // Get mapped question ID
            $target = $migration->getMappedItemTarget(
                'PluginFormcreatorQuestion',
                $item_id
            );

            if (empty($target)) {
                // Log this issue or handle the missing mapping
                continue;
            }

            $question_id = $target['items_id'];
            $question = Question::getById($question_id);

            if (!$question) {
                // Log this issue or handle the missing question
                continue;
            }

            $new_tag = match ($type) {
                'question' => (new QuestionTagProvider())->getTagForQuestion($question),
                'answer' => (new AnswerTagProvider())->getTagForQuestion($question),
                default => null,
            };

            if ($new_tag) {
                $content = str_replace("##$tag##", $new_tag->html, $content);
            }
        }

        return $content;
    }

    /**
     * Resolves a Formcreator property name to the corresponding search option ID for a given question.
     *
     * @param Question $question The question object
     * @param string $property_name The property name to resolve
     * @return int|null The corresponding search option ID, or null if not found
     */
    private static function resolveFormcreatorPropertyToSearchOptionId(
        Question $question,
        string $property_name
    ): ?int {
        if (!is_a($question->fields['type'], QuestionTypeItem::class, true)) {
            return null;
        }

        $itemtype = (new ($question->fields['type'])())->getDefaultValueItemtype($question);
        if ($itemtype === null || !is_a($itemtype, \CommonDBTM::class, true)) {
            return null;
        }

        $key = strtolower($property_name);
        $field_name = self::FORMCREATOR_FIELD_MAP[$itemtype][$key]
            ?? self::FORMCREATOR_FIELD_MAP['*'][$key]
            ?? null;

        if ($field_name === null) {
            // Log a warning about the unknown property
            trigger_error(
                sprintf('Formcreator migration: unknown property "%s" for %s', $property_name, $itemtype),
                E_USER_WARNING
            );
            return null;
        }

        // Handle special case for user emails
        if ($field_name === 'emails') {
            return ItemPropertyTagProvider::USER_EMAILS_PSEUDO_ID;
        }

        // Find the search option ID corresponding to the field name
        $options = SearchOption::getOptionsForItemtype($itemtype);
        foreach ($options as $id => $option) {
            if (is_int($id) && ($option['field'] ?? null) === $field_name) {
                return $id;
            }
        }

        return null;
    }

}
