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

use DirectoryIterator;
use ReflectionClass;

/**
 * Helper class to load all available question types
 */
final class QuestionTypesManager
{
    /**
     * Singleton instance
     * @var QuestionTypesManager|null
     */
    protected static ?QuestionTypesManager $instance = null;

    /**
     * Available question types
     * @var QuestionTypeInterface[]
     */
    protected array $question_types = [];

    /**
     * Available question categories
     * @var QuestionTypeCategoryInterface[]
     */
    protected array $categories = [];

    /**
     * Private constructor to prevent instantiation (singleton)
     */
    private function __construct()
    {
        self::loadQuestionsTypes();
        self::loadCategories();
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
        return $this->categories;
    }

    /**
     * Get all available question categories
     *
     * @return QuestionTypeInterface[]
     */
    public function getQuestionTypes(): array
    {
        return $this->question_types;
    }

    /**
     * Get available types for a given parent category
     *
     * @param QuestionTypeCategory $category Parent category
     *
     * @return QuestionTypeInterface[]
     */
    public function getTypesForCategory(QuestionTypeCategoryInterface $category): array
    {
        $filtered_types = array_filter(
            $this->question_types,
            fn(QuestionTypeInterface $type) => $type->getCategory()->getKey() === $category->getKey()
        );

        uasort(
            $filtered_types,
            fn(QuestionTypeInterface $a, QuestionTypeInterface $b) => $a->getWeight() <=> $b->getWeight()
        );

        return $filtered_types;
    }

    /** @param class-string<QuestionTypeCategoryInterface>[] $categories */
    public function registerPluginCategories(array $categories): void
    {
        foreach ($categories as $category) {
            if ($this->isQuestionTypeCategoryValid($category)) {
                $this->categories[] = new $category();
            }
        }

        $this->sortCategoriesTypes();
    }

    /** @param class-string<QuestionTypeInterface>[] $types */
    public function registerPluginTypes(array $types): void
    {
        foreach ($types as $type) {
            if ($this->isQuestionTypeValid($type)) {
                $this->question_types[$type] = new $type();
            }
        }

        $this->sortQuestionTypes();
    }

    public function getTemplateSelectionForCategories(): string
    {
        $icons = array_combine(
            array_map(
                fn(QuestionTypeCategoryInterface $type) => $type->getKey(),
                (array) $this->getCategories()
            ),
            array_map(
                fn(QuestionTypeCategoryInterface $type) => $type->getIcon(),
                (array) $this->getCategories()
            )
        );
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
        $icons = array_combine(
            array_map(
                fn(QuestionTypeCategoryInterface $type) => $type->getKey(),
                (array) $this->getCategories()
            ),
            array_map(
                fn(QuestionTypeCategoryInterface $type) => $type->getIcon(),
                (array) $this->getCategories()
            )
        );
        $js_icons = json_encode($icons);

        return <<<JS
            function(item) {
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
        $icons = array_combine(
            array_map(
                fn(QuestionTypeInterface $type) => $type::class,
                $this->getQuestionTypes()
            ),
            array_map(
                fn(QuestionTypeInterface $type) => $type->getIcon(),
                $this->getQuestionTypes()
            )
        );
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
        $icons = array_combine(
            array_map(
                fn(QuestionTypeInterface $type) => $type::class,
                $this->getQuestionTypes()
            ),
            array_map(
                fn(QuestionTypeInterface $type) => $type->getIcon(),
                $this->getQuestionTypes()
            )
        );
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

    protected function isQuestionTypeValid(?string $classname): bool
    {
        return
            $classname !== null
            && class_exists($classname)
            && is_subclass_of($classname, QuestionTypeInterface::class)
            && (new ReflectionClass($classname))->isAbstract() === false
        ;
    }

    protected function isQuestionTypeCategoryValid(?string $classname): bool
    {
        return
            $classname !== null
            && class_exists($classname)
            && is_subclass_of($classname, QuestionTypeCategoryInterface::class)
            && (new ReflectionClass($classname))->isAbstract() === false
        ;
    }

    /**
     * Automatically build core questions type list.
     *
     * TODO: Would be better to do it with a DI auto-discovery feature, but
     * it is not possible yet.
     *
     * @return void
     */
    protected function loadQuestionsTypes(): void
    {
        // Get files in the current directory
        $directory_iterator = new DirectoryIterator(__DIR__);

        while ($directory_iterator->valid()) {
            // Compute class name with the expected namespace
            $classname = $directory_iterator->getExtension() === 'php'
                ? 'Glpi\\Form\\QuestionType\\' . $directory_iterator->getBasename('.php')
                : null;

            // Validate that the class is a valid question type
            if ($this->isQuestionTypeValid($classname)) {
                $this->question_types[$classname] = new $classname();
            }

            $directory_iterator->next();
        }

        $this->sortQuestionTypes();
    }

    protected function loadCategories(): void
    {
        $this->categories = QuestionTypeCategory::cases();
        $this->sortCategoriesTypes();
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
    }
}
