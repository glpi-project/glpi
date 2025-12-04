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

namespace Glpi\Helpdesk;

use Entity;
use Glpi\DBAL\QueryParam;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\ObserverFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldStrategy;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
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
use Glpi\Helpdesk\Tile\FormTile;
use Glpi\Helpdesk\Tile\GlpiPageTile;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\ItemTranslation\ItemTranslation;
use ITILCategory;
use Location;
use RuntimeException;
use Session;
use Ticket;

use function Safe\json_encode;

final class DefaultDataManager
{
    private AnswerTagProvider $answer_tag_provider;
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->answer_tag_provider = new AnswerTagProvider();
        $this->tiles_manager = TilesManager::getInstance();
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
        $incident_form = $this->createIncidentForm();
        $request_form = $this->createRequestForm();
        $this->addFormsTranslations([$incident_form, $request_form]);

        $root_entity = Entity::getById(0);

        $this->tiles_manager->addTile($root_entity, GlpiPageTile::class, [
            'title'        => __("Browse help articles"),
            'description'  => __("See all available help articles and our FAQ."),
            'illustration' => "browse-kb",
            'page'         => GlpiPageTile::PAGE_FAQ,
        ]);

        $this->tiles_manager->addTile($root_entity, FormTile::class, [
            'forms_forms_id' => $incident_form->getID(),
        ]);

        $this->tiles_manager->addTile($root_entity, FormTile::class, [
            'forms_forms_id' => $request_form->getID(),
        ]);

        $this->tiles_manager->addTile($root_entity, GlpiPageTile::class, [
            'title'        => __("Create a ticket"),
            'description'  => __("Go to our service catalog and pick a form to create a new ticket."),
            'illustration' => "request-support",
            'page'         => GlpiPageTile::PAGE_SERVICE_CATALOG,
        ]);

        $this->tiles_manager->addTile($root_entity, GlpiPageTile::class, [
            'title'        => __("See your tickets"),
            'description'  => __("View all the tickets that you have created."),
            'illustration' => "tickets",
            'page'         => GlpiPageTile::PAGE_ALL_TICKETS,
        ]);

        $this->tiles_manager->addTile($root_entity, GlpiPageTile::class, [
            'title'        => __("Make a reservation"),
            'description'  => __("Pick an available asset and reserve it for a given date."),
            'illustration' => "reservation",
            'page'         => GlpiPageTile::PAGE_RESERVATION,
        ]);

        // TODO: the default tiles should also be translated
    }

    private function dataHasBeenInitialized(): bool
    {
        return countElementsInTable(Form::getTable()) > 0;
    }

    private function applyTranslation(
        TranslationHandler $to_translate,
        string $lang
    ): ?string {
        global $TRANSLATE;

        if (
            $to_translate->getItem() instanceof Question
            && $to_translate->getValue() == _n('Category', 'Categories', 1)
        ) {
            // Specific translation for the category question
            $translated_value = $TRANSLATE->translatePlural(
                'Category',
                'Categories',
                1,
                'glpi',
                $lang
            );
        } elseif (
            $to_translate->getItem() instanceof Question
            && $to_translate->getValue() == _n('Location', 'Locations', 1)
        ) {
            // Specific translation for the location question
            $translated_value = $TRANSLATE->translatePlural(
                'Location',
                'Locations',
                1,
                'glpi',
                $lang
            );
        } elseif (
            $to_translate->getItem() instanceof Question
            && $to_translate->getValue() == _n(
                'Observer',
                'Observers',
                Session::getPluralNumber()
            )
        ) {
            // Specific translation for the observers question
            $translated_value = $TRANSLATE->translatePlural(
                'Observer',
                'Observers',
                Session::getPluralNumber(),
                'glpi',
                $lang
            );
        } else {
            // Standard translation
            $translated_value = $TRANSLATE->translate(
                $to_translate->getValue(),
                'glpi',
                $lang,
            );
        }

        // The translate method of laminas says it return a string in its
        // PHPDoc but this is a LIE, do not believe it.
        // I found that it also return `null` or an array of translations.
        if ($translated_value === null) {
            return null;
        } elseif (is_array($translated_value)) {
            // It seems we get an array with multiple values depending
            // of the number of item.
            // We'll take the first item here as forms translation are
            // always for singular items.
            return $translated_value[0];
        } else {
            return $translated_value;
        }
    }

    /** @param Form[] $forms */
    private function addFormsTranslations(array $forms): void
    {
        global $CFG_GLPI, $DB, $TRANSLATE;

        // List all values that need to be translated
        /** @var TranslationHandler[] $values_to_translate */
        $values_to_translate = [];
        foreach ($forms as $form) {
            $form->getFromDB($form->getID()); // Reset lazy loaded content
            $forms_items_to_translate = $form->listTranslationsHandlers();
            foreach ($forms_items_to_translate as $item_to_translate) {
                array_push($values_to_translate, ...$item_to_translate);
            }
        }

        // Keep track of the current language and load all core locales
        $current_language = Session::getLanguage();
        Session::loadAllCoreLocales();

        $add_translation_query = $DB->buildInsert(ItemTranslation::getTable(), [
            'itemtype'     => new QueryParam(),
            'items_id'     => new QueryParam(),
            'key'          => new QueryParam(),
            'language'     => new QueryParam(),
            'translations' => new QueryParam(),
            'hash'         => new QueryParam(),
        ]);
        $add_translation_stmt = $DB->prepare($add_translation_query);

        foreach (array_keys($CFG_GLPI['languages']) as $lang) {
            foreach ($values_to_translate as $value_to_translate) {
                // Translate value
                $translated_value = $this->applyTranslation(
                    $value_to_translate,
                    $lang,
                );

                if ($translated_value === null) {
                    continue;
                }

                // Make sure a real translation exist for this value
                // TODO: in this case, is there a point to saving the data,
                // can't we just skip the value with a `continue;` statement?
                // I didn't risk it when rewritting this code but maybe we can
                // investigate this later.
                if ($translated_value === $value_to_translate->getValue()) {
                    $translations = '{}';
                } else {
                    $translations = json_encode(['one' => $translated_value]);
                }

                // Store to database
                $add_translation_stmt->execute([
                    $value_to_translate->getItem()::class,
                    $value_to_translate->getItem()->getID(),
                    $value_to_translate->getKey(),
                    $lang,
                    $translations,
                    md5($value_to_translate->getValue()),
                ]);
            }

            // Unload languages when we are done with them to avoid wasting
            // memory.
            if ($lang !== $current_language) {
                // This is a custom method we added on an anonymous class so
                // phpstan doesn't recognize it.
                // We could create an interface and make the anonymous class
                // implement it but it would "officialize" this method, which
                // I am not sure is something we want since this is already a
                // hacky solution.
                // To be improved when we use something else than laminas for
                // translations.
                // @phpstan-ignore method.notFound
                $TRANSLATE->removeCoreTranslationsForLanguage($lang);
            }
        }
    }

    private function createIncidentForm(): Form
    {
        // Create form
        $form = $this->createForm(
            name: __('Report an issue'),
            description: __("Ask for support from our helpdesk team."),
            illustration: 'report-issue',
        );

        // Get first section
        $sections = $form->getSections();
        $section = array_pop($sections);

        // Add questions
        $this->addQuestion($section, $this->getUrgencyQuestionData());
        $this->addQuestion($section, $this->getCategoryQuestionData());
        $this->addQuestion($section, $this->getUserDevicesQuestionData());
        $this->addQuestion($section, $this->getObserversQuestionData());
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
            ObserverField::getKey() => (new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::LAST_VALID_ANSWER],
            ))->jsonSerialize(),
        ];

        // Add ticket destination
        $this->setDefaultDestinationConfig($form, $config);

        return $form;
    }

    private function createRequestForm(): Form
    {
        $form = $this->createForm(
            name: __('Request a service'),
            description: __("Ask for a service to be provided by our team."),
            illustration: 'request-service',
        );

        // Get first section
        $sections = $form->getSections();
        $section = array_pop($sections);

        // Add questions
        $this->addQuestion($section, $this->getUrgencyQuestionData());
        $this->addQuestion($section, $this->getCategoryQuestionData());
        $this->addQuestion($section, $this->getUserDevicesQuestionData());
        $this->addQuestion($section, $this->getObserversQuestionData());
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
            ObserverField::getKey() => (new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::LAST_VALID_ANSWER],
            ))->jsonSerialize(),
        ];

        // Add ticket destination
        $this->setDefaultDestinationConfig($form, $config);

        return $form;
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

        $form = Form::getById($form_id);
        if (!$form_id || !$form instanceof Form) {
            throw new RuntimeException("Failed to create form");
        }

        return $form;
    }

    private function addQuestion(
        Section $section,
        array $question_data,
    ): Question {
        global $CFG_GLPI, $DB;

        // Refresh data
        $section->getFromDB($section->getID());

        // Set common values
        $question_data['vertical_rank'] = count($section->getQuestions());
        $question_data[Section::getForeignKeyField()] = $section->getID();

        // Create question
        $question = new Question();
        if (!$question->add($question_data)) {
            throw new RuntimeException(
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
            'default_value' => null,
            'extra_data' => json_encode([
                'itemtype'             => ITILCategory::class,
                'categories_filter'    => ['request', 'incident', 'change', 'problem'],
                'root_items_id'        => 0,
                'subtree_depth'        => 0,
                'selectable_tree_root' => false,
            ]),
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
            'default_value' => null,
            'extra_data' => json_encode([
                'itemtype'             => Location::class,
                'categories_filter'    => [],
                'root_items_id'        => 0,
                'subtree_depth'        => 0,
                'selectable_tree_root' => false,
            ]),
        ];
    }

    private function getUrgencyQuestionData(): array
    {
        return [
            'type' => QuestionTypeUrgency::class,
            'name' => __("Urgency"),
        ];
    }

    private function getObserversQuestionData(): array
    {
        return [
            'type' => QuestionTypeObserver::class,
            'name' => _n('Observer', 'Observers', Session::getPluralNumber()),
            'extra_data' => json_encode(['is_multiple_actors' => true]),
        ];
    }

    private function setDefaultDestinationConfig(Form $form, array $config): void
    {
        $destination = current($form->getDestinations());
        $success = $destination->update([
            'id' => $destination->getID(),
            'config'   => $config,
        ]);

        if (!$success) {
            throw new RuntimeException("Failed configure destination");
        }
    }
}
