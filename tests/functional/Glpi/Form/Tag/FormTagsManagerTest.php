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

use CommonDBTM;
use Computer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\CommentDescriptionTagProvider;
use Glpi\Form\Tag\CommentTitleTagProvider;
use Glpi\Form\Tag\FormTagProvider;
use Glpi\Form\Tag\FormTagsManager;
use Glpi\Form\Tag\FullFormTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\SectionTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\UppercaseFormTitleTagProvider;
use PHPUnit\Framework\Attributes\DataProvider;

final class FormTagsManagerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTags(): void
    {
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();

        // All possible tags that may be returned for all cases.
        $tags = [
            new Tag(
                label: 'Form name: First and last name form',
                value: $form->getId(),
                provider: new FormTagProvider(),
            ),
            new Tag(
                label: 'Section: Personal information',
                value: $this->getSectionId($form, 'Personal information'),
                provider: new SectionTagProvider(),
            ),
            new Tag(
                label: 'Comment title: Comment title',
                value: $this->getCommentId($form, 'Comment title'),
                provider: new CommentTitleTagProvider(),
            ),
            new Tag(
                label: 'Comment description: Comment description',
                value: $this->getCommentId($form, 'Comment title'),
                provider: new CommentDescriptionTagProvider(),
            ),
            new Tag(
                label: 'Question: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: new QuestionTagProvider(),
            ),
            new Tag(
                label: 'Question: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: new QuestionTagProvider(),
            ),
            new Tag(
                label: 'Answer: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: new AnswerTagProvider(),
            ),
            new Tag(
                label: 'Answer: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: new AnswerTagProvider(),
            ),
            new Tag(
                label: 'Full form (all questions and answers)',
                value: $form->getId(),
                provider: new FullFormTagProvider(),
            ),
            new Tag(
                label: 'Tester: uppercase form title',
                value: $form->getId(),
                provider: new UppercaseFormTitleTagProvider('pink'),
            ),
        ];

        // Without filter
        $this->checkGetTags($form, "", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Section: Personal information'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
            $this->getTagByName($tags, 'Comment title: Comment title'),
            $this->getTagByName($tags, 'Comment description: Comment description'),
            $this->getTagByName($tags, 'Full form (all questions and answers)'),
            $this->getTagByName($tags, 'Tester: uppercase form title'),
        ]);

        // With "name" filter
        $this->checkGetTags($form, "name", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "Question" filter
        $this->checkGetTags($form, "Question", [
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Full form (all questions and answers)'),
        ]);

        // With "Answer" filter
        $this->checkGetTags($form, "Answer", [
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
            $this->getTagByName($tags, 'Full form (all questions and answers)'),
        ]);

        // With "First" filter
        $this->checkGetTags($form, "First", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Answer: First name'),
        ]);

        // With "Last" filter
        $this->checkGetTags($form, "Last", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "last" filter
        $this->checkGetTags($form, "last", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "Form name" filter
        $this->checkGetTags($form, "Form name", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
        ]);

        // With "Section" filter
        $this->checkGetTags($form, "Section", [
            $this->getTagByName($tags, 'Section: Personal information'),
        ]);

        // With "Comment" filter
        $this->checkGetTags($form, "Comment", [
            $this->getTagByName($tags, 'Comment title: Comment title'),
            $this->getTagByName($tags, 'Comment description: Comment description'),
        ]);

        // With "Comment title" filter
        $this->checkGetTags($form, "Comment title", [
            $this->getTagByName($tags, 'Comment title: Comment title'),
        ]);

        // With "Comment description" filter
        $this->checkGetTags($form, "Comment description", [
            $this->getTagByName($tags, 'Comment description: Comment description'),
        ]);
    }

    private function checkGetTags(
        Form $form,
        string $filter,
        array $expected_tags
    ): void {
        $tag_manager = new FormTagsManager();
        $tags = $tag_manager->getTags($form, $filter);
        $this->assertEquals($expected_tags, $tags);
    }

    public function testInsertTagsContent()
    {
        $tag_manager = new FormTagsManager();
        $answers_handler = AnswersHandler::getInstance();

        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);

        $tags = $tag_manager->getTags($form);
        $form_name_tag = $this->getTagByName(
            $tags,
            'Form name: First and last name form'
        );
        $section_tag = $this->getTagByName(
            $tags,
            'Section: Personal information'
        );
        $first_name_question_tag = $this->getTagByName(
            $tags,
            'Question: First name'
        );
        $first_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: First name'
        );
        $last_name_question_tag = $this->getTagByName(
            $tags,
            'Question: Last name'
        );
        $last_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: Last name'
        );
        $comment_title_tag = $this->getTagByName(
            $tags,
            'Comment title: Comment title'
        );
        $comment_description_tag = $this->getTagByName(
            $tags,
            'Comment description: Comment description'
        );

        $content_with_tag
            = "$form_name_tag->html, "
            . "$section_tag->html, "
            . "$first_name_question_tag->html: $first_name_answer_tag->html, "
            . "$last_name_question_tag->html: $last_name_answer_tag->html, "
            . "$comment_title_tag->html, "
            . "$comment_description_tag->html"
        ;
        $computed_content = $tag_manager->insertTagsContent(
            $content_with_tag,
            $answers
        );

        $this->assertEquals('First and last name form, Personal information, First name: John, Last name: Smith, Comment title, Comment description', $computed_content);
    }

    public function testGetTagProviders(): void
    {
        $tag_manager = new FormTagsManager();
        $providers = $tag_manager->getTagProviders();
        $this->assertNotEmpty($providers);
    }

    public function testGetTagProvidersIncludesPluginProviders(): void
    {
        $tag_manager = new FormTagsManager();
        $providers = array_map(
            fn($provider) => $provider::class,
            $tag_manager->getTagProviders(),
        );

        // Providers registered by the tester plugin must be available, even
        // when the manager is not accessed through its singleton instance.
        $this->assertContains(UppercaseFormTitleTagProvider::class, $providers);
    }

    public function testPluginTagsContentCanBeInserted(): void
    {
        // Arrange: create a form, answer it and get the tag of the tester plugin
        $tag_manager = new FormTagsManager();
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $answers = AnswersHandler::getInstance()->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);
        $tag = $this->getTagByName(
            $tag_manager->getTags($form),
            'Tester: uppercase form title',
        );

        // Act: compute the tag content
        $computed_content = $tag_manager->insertTagsContent(
            "Title: $tag->html",
            $answers,
        );

        // Assert: the tag was resolved by the registered plugin provider
        $this->assertEquals('Title: FIRST AND LAST NAME FORM', $computed_content);
    }

    public function testPluginTagsCanBeRefreshed(): void
    {
        // Arrange: create a form and get the tag of the tester plugin
        $tag_manager = new FormTagsManager();
        $form = $this->createItem(Form::class, ['name' => "My form name"]);
        $tag = $this->getTagByName(
            $tag_manager->getTags($form),
            'Tester: uppercase form title',
        );

        // Act: refresh the tag
        $new_html = $tag_manager->refreshTagsContent($tag->html);

        // Assert: the tag was rebuilt by the registered plugin provider
        $this->assertEquals($tag->html, $new_html);
    }

    public function testPluginTagsIdsAreReplaced(): void
    {
        // Arrange: create a form and get the tag of the tester plugin
        $tag_manager = new FormTagsManager();
        $form = $this->createItem(Form::class, ['name' => "My form name"]);
        $tag = $this->getTagByName(
            $tag_manager->getTags($form),
            'Tester: uppercase form title',
        );
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(Form::class, $form->getId(), $form->getId() + 1000);

        // Act: replace the ids referenced by the tags
        $new_html = $tag_manager->replaceIdsInTags($tag->html, $mapper);

        // Assert: the form id referenced by the plugin tag has been updated
        $this->assertStringContainsString(
            sprintf('data-form-tag-value="%d"', $form->getId() + 1000),
            $new_html,
        );
        $this->assertStringContainsString(
            sprintf(
                'data-form-tag-provider="%s"',
                htmlescape(UppercaseFormTitleTagProvider::class)
            ),
            $new_html,
        );
    }

    public static function invalidProviderProvider(): iterable
    {
        yield 'Unknown class' => ['Glpi\\Form\\Tag\\UnknownTagProvider'];
        yield 'Class that is not a tag provider' => [Computer::class];
    }

    #[DataProvider('invalidProviderProvider')]
    public function testTagsWithInvalidProviderAreRemoved(string $provider): void
    {
        // Arrange: build a tag that reference an invalid provider
        $tag_manager = new FormTagsManager();
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $answers = AnswersHandler::getInstance()->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
        ], 0 /* Invalid user id but we dont care for this here */);
        $content = sprintf(
            'Before <span data-form-tag="true" data-form-tag-value="%d" data-form-tag-provider="%s">#Invalid</span> after',
            $form->getId(),
            htmlescape($provider),
        );

        // Act: compute and refresh the tags
        $inserted_content = $tag_manager->insertTagsContent($content, $answers);
        $refreshed_content = $tag_manager->refreshTagsContent($content);

        // Assert: the invalid tag was removed
        $this->assertEquals('Before  after', $inserted_content);
        $this->assertEquals('Before  after', $refreshed_content);
    }

    public function testTagsCanBeRefreshed(): void
    {
        // Arrange: create a form
        $tag_provider = new FormTagProvider();
        $form = $this->createItem(Form::class, ['name' => "My form name"]);
        $tags = $tag_provider->getTags($form);
        $tag = $tags[0];
        $this->assertStringContainsString("My form name", $tag->html, );
        $this->assertStringNotContainsString("My new form name", $tag->html);

        // Act: update the form, then refresh the tag
        $this->updateItem(Form::class, $form->getId(), [
            'name' => "My new form name",
        ]);
        $tag_manager = new FormTagsManager();
        $new_html = $tag_manager->refreshTagsContent($tag->html);

        // Assert: the tag label was updated
        $this->assertStringNotContainsString("My form name", $new_html);
        $this->assertStringContainsString("My new form name", $new_html);
    }

    public static function externalTagsProvider(): iterable
    {
        yield 'Question' => [QuestionTagProvider::class, Question::class];
        yield 'Section'  => [SectionTagProvider::class, Section::class];
        yield 'Comment'  => [CommentTitleTagProvider::class, Comment::class];
        yield 'Form'     => [FormTagProvider::class, Form::class];
    }

    #[DataProvider('externalTagsProvider')]
    public function testExternalTagIsConvertedToRequirementOnExport(
        string $provider_class,
        string $itemtype,
    ): void {
        // Arrange: get a tag of a form that is not exported
        $tag_manager = new FormTagsManager();
        $form = $this->createFormWithItems();
        $exported_form = $this->createForm(new FormBuilder("Exported form"));
        $tag = (new $provider_class())->getTags($form)[0];
        $item = $this->getFormItem($form, $itemtype);

        // Act: prepare the tag for an export that does not include its form
        $export_data = $tag_manager->prepareTagsForExport(
            "Before $tag->html after",
            [$exported_form->getID()],
        );

        // Assert: the tag now references the item name and is a requirement
        $this->assertStringContainsString(
            sprintf('data-form-tag-value="%s"', htmlescape($item->fields['name'])),
            $export_data->getData(),
        );
        $this->assertStringContainsString(
            'data-form-tag-requirement="true"',
            $export_data->getData(),
        );
        $this->assertEquals(
            [DataRequirementSpecification::fromItem($item)],
            $export_data->getRequirements(),
        );
    }

    public function testExternalTagWithSpecialCharsRoundTrips(): void
    {
        // Arrange: export the tag of a question whose name contains special chars
        $tag_manager = new FormTagsManager();
        $name = 'Date "début" & <fin> l\'été ' . uniqid();
        $form = $this->createForm(
            (new FormBuilder())->addQuestion($name, QuestionTypeShortText::class)
        );
        $tag = (new QuestionTagProvider())->getTags($form)[0];
        $exported = $tag_manager->prepareTagsForExport($tag->html, [0])->getData();
        $mapper = $this->getDatabaseMapper();
        $mapper->addMappedItem(Question::class, $name, 1234);

        // Act: replace the ids in the exported tag
        $imported = $tag_manager->replaceIdsInTags($exported, $mapper);

        // Assert: the raw name was used to find the mapped id
        $this->assertStringContainsString('data-form-tag-value="1234"', $imported);
    }

    public function testIdsAreReplacedWhateverTheAttributesOrder(): void
    {
        // Arrange: a tag whose attributes are not in the usual order
        $tag_manager = new FormTagsManager();
        $html = '<span class="x" data-form-tag-provider="Glpi\Form\Tag\QuestionTagProvider" data-form-tag-value="12" data-form-tag="true">#Q</span>';
        $mapper = $this->getDatabaseMapper();
        $mapper->addMappedItem(Question::class, 12, 34);

        // Act: replace the ids in the tag
        $result = $tag_manager->replaceIdsInTags($html, $mapper);

        // Assert: the id was replaced
        $this->assertEquals(
            '<span class="x" data-form-tag-provider="Glpi\Form\Tag\QuestionTagProvider" data-form-tag-value="34" data-form-tag="true">#Q</span>',
            $result,
        );
    }

    private function createFormWithItems(): Form
    {
        $uid = uniqid();
        $builder = new FormBuilder("Form $uid");
        $builder->addSection("Section $uid");
        $builder->addQuestion("Question $uid", QuestionTypeShortText::class);
        $builder->addComment("Comment $uid", "Comment description $uid");
        return $this->createForm($builder);
    }

    private function getFormItem(Form $form, string $itemtype): CommonDBTM
    {
        $items = match ($itemtype) {
            Form::class     => [$form],
            Section::class  => $form->getSections(),
            Question::class => $form->getQuestions(),
            Comment::class  => $form->getFormComments(),
        };
        return current($items);
    }

    private function getDatabaseMapper(): DatabaseMapper
    {
        return new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
    }

    private function createAndGetFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->setName("First and last name form");
        $builder->addSection("Personal information");
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        $builder->addComment("Comment title", "Comment description");
        return $this->createForm($builder);
    }
}
