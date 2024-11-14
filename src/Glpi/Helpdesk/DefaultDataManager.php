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

namespace Glpi\Helpdesk;

use AbstractRightsDropdown;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldStrategy;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Form\Section;
use Glpi\Form\Tag\AnswerTagProvider;
use ITILCategory;
use Location;
use Ticket;

final class DefaultDataManager
{
    private AnswerTagProvider $answer_tag_provider;

    public function __construct()
    {
        $this->answer_tag_provider = new AnswerTagProvider();
    }

    public function initializeDataIfNeeded(): void
    {
        if ($this->dataHasBeenInitialized()) {
            return;
        }

        $this->initializeData();
    }

    public function initializeData(): void
    {
        $this->createIncidentForm();
        $this->createRequestForm();
    }

    private function dataHasBeenInitialized(): bool
    {
        return countElementsInTable(Form::getTable()) > 0;
    }

    private function createIncidentForm(): void
    {
        // Create form
        $form = $this->createForm(
            name: __('Report an issue'),
            description: __("Ask for support from our helpdesk team."),
            illustration: 'report-issue.svg',
        );

        // Get first section
        $sections = $form->getSections();
        $section = array_pop($sections);

        // Add questions
        $this->addQuestion($section, $this->getUrgencyQuestionData());
        $this->addQuestion($section, $this->getCategoryQuestionData());
        $this->addQuestion($section, $this->getUserDevicesQuestionData());
        $this->addQuestion($section, $this->getWatchersQuestionData());
        $this->addQuestion($section, $this->getLocationQuestionData());
        $title_question = $this->addQuestion($section, $this->getTitleQuestionData());
        $description_question = $this->addQuestion($section, $this->getDescriptionQuestionData());

        // Find title and description question tags, needed to configure the created ticket
        $title_tag = $this->answer_tag_provider->getTagForQuestion(
            $title_question
        );
        $description_tag = $this->answer_tag_provider->getTagForQuestion(
            $description_question
        );

        // Prepare ticket destination config
        $config = [
            // Set title using an answer
            TitleField::getKey() => (new SimpleValueConfig(
                $title_tag->html,
            ))->jsonSerialize(),

            // Set manual description using an answer
            ContentField::getAutoConfigKey() => false,
            ContentField::getKey() => (new SimpleValueConfig(
                $description_tag->html,
            ))->jsonSerialize(),

            // Force incident type
            RequestTypeField::getKey() => (new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: Ticket::INCIDENT_TYPE,
            ))->jsonSerialize(),

            // Set last valid answer as observer
            ObserverField::getKey() => (new ITILActorFieldConfig(
                strategy: ITILActorFieldStrategy::LAST_VALID_ANSWER,
            ))->jsonSerialize(),
        ];

        // Add ticket destination
        $this->addTicketDestination($form, $config);

        // Allow all users
        $this->allowAllUsers($form);
    }

    private function createRequestForm(): void
    {
        $form = $this->createForm(
            name: __('Request a service'),
            description: __("Ask for a service to be provided by our team."),
            illustration: 'request-service.svg',
        );

        // Get first section
        $sections = $form->getSections();
        $section = array_pop($sections);

        // Add questions
        $this->addQuestion($section, $this->getUrgencyQuestionData());
        $this->addQuestion($section, $this->getCategoryQuestionData());
        $this->addQuestion($section, $this->getUserDevicesQuestionData());
        $this->addQuestion($section, $this->getWatchersQuestionData());
        $this->addQuestion($section, $this->getLocationQuestionData());
        $title_question = $this->addQuestion($section, $this->getTitleQuestionData());
        $description_question = $this->addQuestion($section, $this->getDescriptionQuestionData());

        // Find title and description question tags, needed to configure the created ticket
        $title_tag = $this->answer_tag_provider->getTagForQuestion(
            $title_question
        );
        $description_tag = $this->answer_tag_provider->getTagForQuestion(
            $description_question
        );

        // Prepare ticket destination config
        $config = [
            // Set title using an answer
            TitleField::getKey() => (new SimpleValueConfig(
                $title_tag->html,
            ))->jsonSerialize(),

            // Set manual description using an answer
            ContentField::getAutoConfigKey() => false,
            ContentField::getKey() => (new SimpleValueConfig(
                $description_tag->html,
            ))->jsonSerialize(),

            // Force incident type
            RequestTypeField::getKey() => (new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                specific_request_type: Ticket::DEMAND_TYPE,
            ))->jsonSerialize(),

            // Set last valid answer as observer
            ObserverField::getKey() => (new ITILActorFieldConfig(
                strategy: ITILActorFieldStrategy::LAST_VALID_ANSWER,
            ))->jsonSerialize(),
        ];

        // Add ticket destination
        $this->addTicketDestination($form, $config);

        // Allow all users
        $this->allowAllUsers($form);
    }

    private function createForm(
        string $name,
        string $description,
        string $illustration,
    ): Form {
        $form = new Form();
        $form_id = $form->add([
            // Specific properties
            'name'         => $name,
            'description'  => $description,
            'illustration' => $illustration,
            // Common properties
            'is_active'    => true,
            'is_recursive' => true,
            'entities_id'  => 0,
        ]);

        if (!$form_id) {
            throw new \RuntimeException("Failed to create form");
        }

        $form = Form::getById($form_id);
        return $form;
    }

    private function addQuestion(
        Section $section,
        array $question_data,
    ): Question {
        // Refresh data
        $section->getFromDB($section->getID());

        // Set common values
        $question_data['rank'] = count($section->getQuestions());
        $question_data[Section::getForeignKeyField()] = $section->getID();

        // Create question
        $question = new Question();
        if (!$question->add($question_data)) {
            throw new \RuntimeException(
                "Failed to create question: " . json_encode($question_data)
            );
        }

        return $question;
    }

    private function getTitleQuestionData(): array
    {
        return [
            'type' => QuestionTypeShortText::class,
            'name' => __("Title"),
        ];
    }

    private function getDescriptionQuestionData(): array
    {
        return [
            'type' => QuestionTypeLongText::class,
            'name' => __("Description"),
            'is_mandatory' => true,
        ];
    }

    private function getCategoryQuestionData(): array
    {
        return [
            'type' => QuestionTypeItemDropdown::class,
            'name' => _n('Category', 'Categories', 1),
            'default_value' => 0,
            'extra_data' => json_encode(['itemtype' => ITILCategory::class]),
        ];
    }

    private function getUserDevicesQuestionData(): array
    {
        return [
            'type' => QuestionTypeUserDevice::class,
            'name' => __("User devices"),
            'default_value' => 0,
            'extra_data' => json_encode(['is_multiple_devices' => false]),
        ];
    }

    private function getLocationQuestionData(): array
    {
        return [
            'type' => QuestionTypeItemDropdown::class,
            'name' => _n('Location', 'Locations', 1),
            'default_value' => 0,
            'extra_data' => json_encode(['itemtype' => Location::class]),
        ];
    }

    private function getUrgencyQuestionData(): array
    {
        return [
            'type' => QuestionTypeUrgency::class,
            'name' => __("Urgency"),
        ];
    }

    private function getWatchersQuestionData(): array
    {
        return [
            'type' => QuestionTypeObserver::class,
            'name' => __("Watchers"),
            'extra_data' => json_encode(['is_multiple_actors' => true]),
        ];
    }

    private function addTicketDestination(Form $form, array $config): void
    {
        $destination = new FormDestination();
        $id = $destination->add([
            Form::getForeignKeyField() => $form->getID(),
            'itemtype' => FormDestinationTicket::class,
            'name'     => _n('Ticket', 'Tickets', 1),
            'config'   => $config,
        ]);

        if (!$id) {
            throw new \RuntimeException("Failed to create destination");
        }
    }

    private function allowAllUsers(Form $form): void
    {
        $form_access_control = new FormAccessControl();
        $id = $form_access_control->add([
            Form::getForeignKeyField() => $form->getID(),
            'strategy' => AllowList::class,
            '_config'   => new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            'is_active' => 1,
        ]);

        if (!$id) {
            throw new \RuntimeException("Failed to create access policy");
        }
    }
}
