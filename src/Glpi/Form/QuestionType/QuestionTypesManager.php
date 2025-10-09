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

namespace Glpi\Form\QuestionType;

use BackedEnum;
use DirectoryIterator;
use ReflectionClass;

use function Safe\json_encode;

/**
 * Helper class to load all available question types and categories.
 */
final class QuestionTypesManager
{
    /**
     * Singleton instance
     * @var QuestionTypesManager|null
     */
    private static ?QuestionTypesManager $instance = null;

    /**
     * Available question types
     * @var QuestionTypeInterface[]
     */
    private array $question_types = [];

    /**
     * Store classes used by $question_types.
     * This avoid having to map $question_types each time we want to validate
     * that a given question type is valid.
     * Types are stored in keys to allow using isset instead of in_array for
     * faster lookup.
     * See self::isValidQuestionType().
     * @var array<class-string<QuestionTypeInterface>, true>
     */
    private array $raw_question_types_map = [];

    /**
     * Available question categories
     * @var QuestionTypeCategoryInterface[]
     */
    private array $categories = [];

    private bool $categories_are_sorted = false;

    private bool $question_types_are_sorted = false;

    /**
     * Private constructor to prevent instantiation (singleton)
     */
    private function __construct()
    {
        self::loadCoreQuestionsTypes();
        self::loadCoreCategories();
    }

    /**
     * Get the singleton instance
     *
     * @return QuestionTypesManager
     */
    public static function getInstance(): QuestionTypesManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the default question type class
     *
     * @return string
     */
    public function getDefaultTypeClass(): string
    {
        return QuestionTypeShortText::class;
    }

    /**
     * Get all available question categories
     *
     * @return QuestionTypeCategoryInterface[]
     */
    public function getCategories(): array
    {
        if ($this->categories_are_sorted === false) {
            $this->sortCategoriesTypes();
        }
        return $this->categories;
    }

    /**
     * Get all available question categories
     *
     * @return QuestionTypeInterface[]
     */
    public function getQuestionTypes(): array
    {
        if ($this->question_types_are_sorted === false) {
            $this->sortQuestionTypes();
        }
        return $this->question_types;
    }

    /**
     * Get available categories in the format expected by dropdowns (key => label)
     *
     * @return array<string, string>
     */
    public function getCategoriesDropdownValues(): array
    {
        $values = [];
        foreach ($this->getCategories() as $category) {
            $values[$this->getCategoryKey($category)] = $category->getLabel();
        }
        return $values;
    }

    /**
     * Get available types for a given parent category in the format expected
     * by dropdowns (class => label)
     *
     * @return array<string, string>
     */
    public function getQuestionTypesDropdownValuesForCategory(
        QuestionTypeCategoryInterface $category
    ): array {
        $filtered_types = [];
        foreach ($this->getQuestionTypes() as $type) {
            $question_type_key = $this->getCategoryKey($type->getCategory());
            $expected_key = $this->getCategoryKey($category);
            if ($question_type_key === $expected_key) {
                $filtered_types[$type::class] = $type->getName();
            }
        }

        return $filtered_types;
    }

    public function registerPluginCategory(
        QuestionTypeCategoryInterface $category
    ): void {
        $this->categories[] = $category;
        $this->categories_are_sorted = false;
    }

    public function registerPluginQuestionType(
        QuestionTypeInterface $question_type
    ): void {
        $this->addQuestionType($question_type);
        $this->question_types_are_sorted = false;
    }

    public function getTemplateSelectionForCategories(): string
    {
        $icons = [];
        foreach ($this->getCategories() as $category) {
            $icons[$this->getCategoryKey($category)] = $category->getIcon();
        }
        $js_icons = json_encode($icons);

        return <<<JS
            function(item) {
                const icons = {$js_icons};
                return $('<span class="d-flex flex-row-reverse align-items-center gap-2">'
                    + '<i class="' + _.escape(icons[item.id]) + '"></i>'
                    + _.escape(item.text)
                    + '</span>');
            }
JS;
    }

    public function getTemplateResultForCategories(): string
    {
        $icons = [];
        foreach ($this->getCategories() as $category) {
            $icons[$this->getCategoryKey($category)] = $category->getIcon();
        }
        $js_icons = json_encode($icons);

        return <<<JS
            function(item) {
                if (item == false) {
                    return "";
                }
                const icons = {$js_icons};
                return $('<span class="d-flex align-items-center gap-2">'
                    + '<i class="' + _.escape(icons[item.id]) + '"></i>'
                    + _.escape(item.text)
                    + '</span>');
            }
JS;
    }

    public function getTemplateSelectionForQuestionTypes(): string
    {
        $icons = [];
        foreach ($this->getQuestionTypes() as $question_type) {
            $icons[$question_type::class] = $question_type->getIcon();
        }
        $js_icons = json_encode($icons);

        return <<<JS
            function(item) {
                const icons = {$js_icons};
                return $('<span class="d-flex flex-row-reverse align-items-center gap-2">'
                    + '<i class="' + _.escape(icons[item.id]) + '"></i>'
                    + _.escape(item.text)
                    + '</span>');
            }
JS;
    }

    public function getTemplateResultForQuestionTypes(): string
    {
        $icons = [];
        foreach ($this->getQuestionTypes() as $question_type) {
            $icons[$question_type::class] = $question_type->getIcon();
        }
        $js_icons = json_encode($icons);

        return <<<JS
            function(item) {
                const icons = {$js_icons};
                return $('<span class="d-flex align-items-center gap-1">'
                    + '<i class="' + _.escape(icons[item.id]) + '"></i>'
                    + _.escape(item.text)
                    + '</span>');
            }
JS;
    }

    public function getCategoryKey(QuestionTypeCategoryInterface $category): string
    {
        if ($category instanceof BackedEnum) {
            return $category::class . "->" . $category->value;
        }

        return $category::class;
    }

    public function isValidQuestionType(string $type): bool
    {
        return isset($this->raw_question_types_map[$type]);
    }

    private function addQuestionType(QuestionTypeInterface $type): void
    {
        $this->question_types[] = $type;
        $this->raw_question_types_map[$type::class] = true;
    }

    protected function isClassAValidQuestionType(?string $classname): bool
    {
        return
            $classname !== null
            && class_exists($classname)
            && is_subclass_of($classname, QuestionTypeInterface::class)
            && (new ReflectionClass($classname))->isAbstract() === false
        ;
    }

    protected function loadCoreQuestionsTypes(): void
    {
        // Get files in the current directory
        $directory_iterator = new DirectoryIterator(__DIR__);

        while ($directory_iterator->valid()) {
            // Compute class name with the expected namespace
            $classname = $directory_iterator->getExtension() === 'php'
                ? 'Glpi\\Form\\QuestionType\\' . $directory_iterator->getBasename('.php')
                : null;

            // Validate that the class is a valid question type
            if ($this->isClassAValidQuestionType($classname)) {
                // @phpstan-ignore glpi.forbidDynamicInstantiation (Type is checked by `self::isClassAValidQuestionType()`)
                $this->addQuestionType(new $classname());
            }

            $directory_iterator->next();
        }
    }

    protected function loadCoreCategories(): void
    {
        $this->categories = QuestionTypeCategory::cases();
    }

    protected function sortQuestionTypes()
    {
        // Sort question types by weight
        uasort(
            $this->question_types,
            fn(
                QuestionTypeInterface $a,
                QuestionTypeInterface $b,
            ): int => $a->getWeight() <=> $b->getWeight()
        );
        $this->question_types_are_sorted = true;
    }

    protected function sortCategoriesTypes()
    {
        // Sort question types by weight
        uasort(
            $this->categories,
            fn(
                QuestionTypeCategoryInterface $a,
                QuestionTypeCategoryInterface $b,
            ): int => $a->getWeight() <=> $b->getWeight()
        );
        $this->categories_are_sorted = true;
    }
}
