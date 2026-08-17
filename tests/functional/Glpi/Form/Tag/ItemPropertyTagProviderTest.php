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

namespace tests\units\Glpi\Form\Tag;

use Computer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\ItemPropertyTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Search\SearchOption;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use User;
use UserEmail;

use function Safe\json_encode;

final class ItemPropertyTagProviderTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTagsForEmptyForm(): void
    {
        $form = $this->createForm(new FormBuilder());
        $this->assertEquals([], (new ItemPropertyTagProvider())->getTags($form));
    }

    public function testGetTagsSkipsQuestionsThatAreNotItemQuestions(): void
    {
        $form = $this->createForm(
            (new FormBuilder())->addQuestion('Comments', QuestionTypeShortText::class)
        );

        $this->assertEquals([], (new ItemPropertyTagProvider())->getTags($form));
    }

    public static function getTagsForItemQuestionReturnsScalarFieldsProvider(): array
    {
        return [
            [
                'question_type' => QuestionTypeItem::class,
                'extra_data' => json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                ),
                'item_type' => Computer::class,
            ],
            [
                'question_type' => QuestionTypeItemDropdown::class,
                'extra_data' => json_encode(
                    (new QuestionTypeItemDropdownExtraDataConfig(itemtype: Location::class))->jsonSerialize()
                ),
                'item_type' => Location::class,
            ],
            [
                'question_type' => QuestionTypeItem::class,
                'extra_data' => json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: User::class))->jsonSerialize()
                ),
                'item_type' => User::class,
            ],
        ];
    }

    #[DataProvider('getTagsForItemQuestionReturnsScalarFieldsProvider')]
    public function testGetTagsForItemQuestionReturnsScalarFields(string $question_type, string $extra_data, string $item_type): void
    {
        $form = $this->createForm(
            (new FormBuilder())->addQuestion(
                name: 'Q1',
                type: $question_type,
                extra_data: $extra_data,
            )
        );
        $question_id = $this->getQuestionId($form, 'Q1');

        $item_property_tag_provider = new ItemPropertyTagProvider();
        $tags = $item_property_tag_provider->getTags($form);
        $this->assertNotEmpty($tags);

        $allowed_type = $item_property_tag_provider->getAllowedDataTypes();

        // Every exposed property must belong to the main itemtype table,
        // not to a joined table (ex: manufacturer name, location name, ...),
        // and must be an allowed data type (ex: no password, no image, ...).
        $options = SearchOption::getOptionsForItemtype($item_type);
        foreach ($tags as $tag) {
            $this->assertInstanceOf(Tag::class, $tag);

            [$tag_question_id, $option_id] = array_map('intval', explode(':', $this->getTagValue($tag), 2));
            $this->assertSame($question_id, $tag_question_id);

            // Special case for the "email addresses" pseudo-property for User itemtype
            if ($option_id === ItemPropertyTagProvider::USER_EMAILS_PSEUDO_ID) {
                $this->assertSame(User::class, $item_type);
                continue;
            } else {
                $this->assertArrayHasKey($option_id, $options);
                $this->assertSame($item_type::getTable(), $options[$option_id]['table']);
                $this->assertContains($options[$option_id]['datatype'] ?? 'string', $allowed_type);
            }
        }

        // Test a well known scalar property.
        $name_option_id = $this->getSearchOptionId($item_type, 'name');
        $this->assertNotNull($this->findTagByValue($tags, "$question_id:$name_option_id"));

        // Check email tag for User itemtype
        if (is_a($item_type, User::class, true)) {
            $emails_tag = $this->findTagByValue($tags, $question_id . ':' . ItemPropertyTagProvider::USER_EMAILS_PSEUDO_ID);

            $this->assertNotNull($emails_tag);
            $this->assertEquals(
                sprintf('Answer: %s › %s', 'Q1', 'Email addresses'),
                $emails_tag->label,
            );
        }
    }

    public function testGetTagFromRawValue(): void
    {
        $item_property_tag_provider = new ItemPropertyTagProvider();
        $tag = $item_property_tag_provider->getTagFromRawValue('999999:1');
        $this->assertNull($tag);

        $tag = $item_property_tag_provider->getTagFromRawValue('not-a-value');
        $this->assertNull($tag);

        $form = $this->createForm(
            (new FormBuilder())->addQuestion('Comments', QuestionTypeShortText::class)
        );
        $question_id = $this->getQuestionId($form, 'Comments');
        $tag = $item_property_tag_provider->getTagFromRawValue("$question_id:1");
        $this->assertNull($tag);

        [, $question_id] = $this->createFormWithQuestion(Computer::class, 'Asset');
        $tag = $item_property_tag_provider->getTagFromRawValue("$question_id:999999");
        $this->assertNull($tag);

        $serial_option_id = $this->getSearchOptionId(Computer::class, 'serial');
        $tag = $item_property_tag_provider->getTagFromRawValue("$question_id:$serial_option_id");

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals("$question_id:$serial_option_id", $this->getTagValue($tag));
    }

    public function testGetTagContentForValueWithSimpleQuestion(): void
    {
        $item_property_tag_provider = new ItemPropertyTagProvider();

        // Test with empty answer -> empty
        [$form, $question_id] = $this->createFormWithQuestion(Computer::class, 'Asset');
        $serial_option_id = $this->getSearchOptionId(Computer::class, 'serial');
        $this->assertEquals(
            '',
            $item_property_tag_provider->getTagContentForValue(
                "$question_id:$serial_option_id",
                $this->getEmptyAnswerSet()
            )
        );

        // Test with unvalid tag value -> empty
        $this->assertEquals(
            '',
            $item_property_tag_provider->getTagContentForValue(
                'not-a-value',
                $this->getEmptyAnswerSet()
            )
        );

        // Test with valid tag value and answer
        $computer = $this->createItem(Computer::class, [
            'name'        => 'My computer',
            'serial'      => 'SN-1234',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $answers = AnswersHandler::getInstance()->saveAnswers($form, [
            $question_id => [
                'itemtype'  => Computer::class,
                'items_ids' => [$computer->getID()],
            ],
        ], 0);

        $serial_option_id = $this->getSearchOptionId(Computer::class, 'serial');
        $this->assertEquals(
            'SN-1234',
            $item_property_tag_provider->getTagContentForValue(
                "$question_id:$serial_option_id",
                $answers
            )
        );
    }

    public function testGetTagContentForValueWithMultipleItemsAnswer(): void
    {
        $item_property_tag_provider = new ItemPropertyTagProvider();

        // Test with a multiple items answer
        $form = $this->createForm(
            (new FormBuilder())->addQuestion(
                name: 'Assets',
                type: QuestionTypeItem::class,
                extra_data: json_encode(
                    (new QuestionTypeItemExtraDataConfig(
                        itemtype: Computer::class,
                        is_multiple_items: true,
                    ))->jsonSerialize()
                ),
            )
        );
        $question_id = $this->getQuestionId($form, 'Assets');

        $first_computer = $this->createItem(Computer::class, [
            'name'        => 'First computer',
            'serial'      => 'SN-FIRST',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $second_computer = $this->createItem(Computer::class, [
            'name'        => 'Second computer',
            'serial'      => 'SN-SECOND',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $answers = AnswersHandler::getInstance()->saveAnswers($form, [
            $question_id => [
                'itemtype'  => Computer::class,
                'items_ids' => [$first_computer->getID(), $second_computer->getID()],
            ],
        ], 0);

        $serial_option_id = $this->getSearchOptionId(Computer::class, 'serial');
        $this->assertEquals(
            'SN-FIRST, SN-SECOND',
            $item_property_tag_provider->getTagContentForValue(
                "$question_id:$serial_option_id",
                $answers
            )
        );
    }

    public function testGetTagContentForValueWithSpecialProperties(): void
    {
        $item_property_tag_provider = new ItemPropertyTagProvider();

        // Test with the special "email addresses" pseudo-property
        [$form, $question_id] = $this->createFormWithQuestion(User::class, 'Requester asset');

        $user = $this->createItem(User::class, ['name' => 'tag_provider_test_user']);
        $this->createItem(UserEmail::class, [
            'users_id' => $user->getID(),
            'email'    => 'primary@example.org',
        ]);
        $this->createItem(UserEmail::class, [
            'users_id' => $user->getID(),
            'email'    => 'secondary@example.org',
        ]);

        $answers = AnswersHandler::getInstance()->saveAnswers($form, [
            $question_id => [
                'itemtype'  => User::class,
                'items_ids' => [$user->getID()],
            ],
        ], 0);

        $this->assertEquals(
            'primary@example.org, secondary@example.org',
            $item_property_tag_provider->getTagContentForValue(
                "$question_id:" . ItemPropertyTagProvider::USER_EMAILS_PSEUDO_ID,
                $answers
            )
        );

        // Test with blacklisted properties
        $allowed_types = $item_property_tag_provider->getAllowedDataTypes();
        $black_listed_search_options = array_filter(
            SearchOption::getOptionsForItemtype(User::class),
            fn($option) => isset($option['datatype']) && !in_array($option['datatype'], $allowed_types, true)
        );

        foreach ($black_listed_search_options as $id => $option) {
            $this->assertEquals(
                '',
                $item_property_tag_provider->getTagContentForValue(
                    "$question_id:$id",
                    $answers
                )
            );
        }
    }

    public function testExtractItemIdFromValue(): void
    {
        $this->assertEquals('42', (new ItemPropertyTagProvider())->extractItemIdFromValue('42:7'));
    }

    public function testRebuildValueWithMappedId(): void
    {
        $this->assertEquals(
            '99:7',
            (new ItemPropertyTagProvider())->rebuildValueWithMappedId('42:7', '99'),
        );
    }

    /** @return array{0: Form, 1: int} */
    private function createFormWithQuestion(string $answer_itemtype, string $question_name): array
    {
        $form = $this->createForm(
            (new FormBuilder())->addQuestion(
                name: $question_name,
                type: QuestionTypeItem::class,
                extra_data: json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: $answer_itemtype))->jsonSerialize()
                ),
            )
        );

        return [$form, $this->getQuestionId($form, $question_name)];
    }

    private function getSearchOptionId(string $itemtype, string $field): int
    {
        foreach (SearchOption::getOptionsForItemtype($itemtype) as $id => $option) {
            if (
                is_int($id)
                && ($option['field'] ?? null) === $field
                && ($option['table'] ?? null) === $itemtype::getTable()
            ) {
                return $id;
            }
        }

        $this->fail("No search option found for field '$field' on '$itemtype'");
    }

    private function findTagByValue(array $tags, string $value): ?Tag
    {
        foreach ($tags as $tag) {
            if ($this->getTagValue($tag) === $value) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * `Tag::$value` is not exposed as a public property, it is only used to
     * build the tag's HTML representation. Extract it back from there.
     */
    private function getTagValue(Tag $tag): string
    {
        preg_match('/data-form-tag-value="([^"]+)"/', $tag->html, $matches);
        return $matches[1] ?? '';
    }

    private function getEmptyAnswerSet(): AnswersSet
    {
        $answers = new AnswersSet();
        $answers->fields['answers'] = json_encode([]);
        return $answers;
    }
}
