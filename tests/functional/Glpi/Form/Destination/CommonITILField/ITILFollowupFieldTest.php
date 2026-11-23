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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use CommonITILObject;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldStrategy;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\StatusField;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILFollowup;
use ITILFollowupTemplate;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;
use User;

final class ITILFollowupFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testFollowupForNoFollowup(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $no_followup = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $no_followup,
            expected_itilfollowups: []
        );
    }

    public function testFollowupForSpecificValues(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_values = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_VALUES,
            specific_itilfollowuptemplates_ids: [$templates[0]->getID(), $templates[1]->getID()]
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_values,
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
            ]
        );
    }

    public function testFormTagsAreResolvedInSpecificContent(): void
    {
        $form = $this->createForm(
            (new FormBuilder())->addQuestion("First name", QuestionTypeShortText::class)
        );
        $question = Question::getById($this->getQuestionId($form, "First name"));
        $question_tag = (new QuestionTagProvider())->getTagForQuestion($question);
        $answer_tag = (new AnswerTagProvider())->getTagForQuestion($question);
        $specific_content = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_content: "<p>ENTREE VALIDEE</p><p>{$question_tag->html}: {$answer_tag->html}</p>",
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_content,
            expected_itilfollowups: ['ENTREE VALIDEEFirst name: John'],
            answers: ['First name' => 'John'],
        );
    }

    public function testFollowupIsNotCreatedForEmptySpecificContent(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_content = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_content: '',
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_content,
            expected_itilfollowups: []
        );
    }

    public function testFollowupIsNotCreatedWhenSpecificContentIsEmptyOnceTagsAreResolved(): void
    {
        $form = $this->createForm(
            (new FormBuilder())->addQuestion("Comment", QuestionTypeShortText::class)
        );
        $question = Question::getById($this->getQuestionId($form, "Comment"));
        $answer_tag = (new AnswerTagProvider())->getTagForQuestion($question);
        $specific_content = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_content: $answer_tag->html,
        );

        // The optional question is not answered
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_content,
            expected_itilfollowups: []
        );
    }

    public function testFollowupIsNotCreatedForBlankSpecificContent(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_content = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_content: '<p>&nbsp;</p>',
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_content,
            expected_itilfollowups: []
        );
    }

    public function testSpecificContentFollowupDoesNotReopenTicket(): void
    {
        // The form filler is the requester, its followups usually reopen the ticket
        $this->login();
        $ticket = $this->sendFormAndAssertITILFollowup(
            form: $this->createAndGetFormWithMultipleDropdownItemQuestions(),
            config: new ITILFollowupFieldConfig(
                strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
                specific_content: '<p>ENTREE VALIDEE</p>',
            ),
            expected_itilfollowups: ['ENTREE VALIDEE'],
            other_fields_config: [
                StatusField::getKey() => new SimpleValueConfig((string) CommonITILObject::CLOSED),
            ],
        );

        $this->assertEquals(CommonITILObject::CLOSED, Ticket::getById($ticket->getID())->fields['status']);
    }

    public function testSpecificContentFollowupDoesNotTakeTicketIntoAccount(): void
    {
        // The form is filled on behalf of another user by someone that can
        // add followups, its followups usually take the ticket into account
        $this->login();
        $ticket = $this->sendFormAndAssertITILFollowup(
            form: $this->createAndGetFormWithMultipleDropdownItemQuestions(),
            config: new ITILFollowupFieldConfig(
                strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
                specific_content: '<p>ENTREE VALIDEE</p>',
            ),
            expected_itilfollowups: ['ENTREE VALIDEE'],
            other_fields_config: [
                RequesterField::getKey() => new RequesterFieldConfig(
                    strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                    specific_itilactors_ids: [
                        User::getForeignKeyField() . '-' . getItemByTypeName(User::class, 'normal', true),
                    ],
                ),
            ],
        );

        $this->assertNull(Ticket::getById($ticket->getID())->fields['takeintoaccountdate']);
    }

    public function testOnlySpecificContentIsUsedWhenSpecificContentStrategyIsSelected(): void
    {
        $template = $this->createITILFollowupTemplate('Followup template 1');
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $config = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_itilfollowuptemplates_ids: [$template->getID()],
            specific_content: '<p>ENTREE VALIDEE</p>',
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $config,
            expected_itilfollowups: ['ENTREE VALIDEE']
        );
    }

    public function testOnlyTemplatesAreUsedWhenSpecificValuesStrategyIsSelected(): void
    {
        $template = $this->createITILFollowupTemplate('Followup template 1');
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $config = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_VALUES,
            specific_itilfollowuptemplates_ids: [$template->getID()],
            specific_content: '<p>ENTREE VALIDEE</p>',
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $config,
            expected_itilfollowups: ['Followup template 1']
        );
    }

    public function testSpecificContentIsRenderedInRichTextEditorWithFormTags(): void
    {
        $this->login();
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $destination = current($form->getDestinations());
        $config = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_CONTENT,
            specific_content: '<p>ENTREE VALIDEE</p>',
        );

        $field = new ITILFollowupField();
        $html = $field->renderConfigForm(
            $form,
            $destination,
            $config,
            'config[' . ITILFollowupField::getKey() . ']',
            ['is_horizontal' => false, 'rand' => 42],
        );

        $textarea = (new Crawler($html))->filter(
            '[data-glpi-itildestination-field-config-display-condition="specific_content"] '
            . 'textarea[name="config[' . ITILFollowupField::getKey() . '][value]"]'
        );
        $this->assertCount(1, $textarea);
        $this->assertStringContainsString('ENTREE VALIDEE', $textarea->text());
        $this->assertStringContainsString('GLPI.RichText.FormTags', $html);
    }

    private function sendFormAndAssertITILFollowup(
        Form $form,
        ITILFollowupFieldConfig $config,
        array $expected_itilfollowups,
        array $answers = [],
        array $other_fields_config = [],
    ): Ticket {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => array_map(
                fn(JsonFieldInterface $field_config) => $field_config->jsonSerialize(),
                [ITILFollowupField::getKey() => $config] + $other_fields_config,
            )],
            ["config"],
        );

        $ticket = $this->sendFormAndGetCreatedTicket($form, $answers);

        // Check ITILFollowup
        $this->assertEquals(
            countElementsInTable(
                ITILFollowup::getTable(),
                [
                    'itemtype' => Ticket::getType(),
                    'items_id' => $ticket->getID(),
                ]
            ),
            count($expected_itilfollowups)
        );

        $itilfollowup  = new ITILFollowup();
        $itilfollowups = $itilfollowup->find([
            'itemtype' => Ticket::getType(),
            'items_id' => $ticket->getID(),
        ]);

        $this->assertEquals(
            array_values(
                array_map(
                    fn(array $itilfollowup) => strip_tags($itilfollowup['content']),
                    $itilfollowups
                )
            ),
            array_values($expected_itilfollowups)
        );

        return $ticket;
    }

    private function createAndGetFormWithMultipleDropdownItemQuestions(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }

    private function createITILFollowupTemplate(?string $content = null): ITILFollowupTemplate
    {
        return $this->createItem(ITILFollowupTemplate::class, [
            'name' => 'ITIL Followup Template',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'content' => $content ?? 'This is a followup template',
        ]);
    }
}
