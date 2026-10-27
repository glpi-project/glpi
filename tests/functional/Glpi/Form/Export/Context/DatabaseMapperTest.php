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

namespace tests\units\Glpi\Form\Export\Context;

use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class DatabaseMapperTest extends DbTestCase
{
    use FormTesterTrait;

    public static function formItemsProvider(): iterable
    {
        yield 'Question' => [Question::class];
        yield 'Section'  => [Section::class];
        yield 'Comment'  => [Comment::class];
    }

    #[DataProvider('formItemsProvider')]
    public function testMissingFormItemRequirementIsInvalid(string $itemtype): void
    {
        // Arrange: build a requirement on an item that does not exist
        $mapper = $this->getMapper();
        $requirement = new DataRequirementSpecification($itemtype, 'Unknown ' . uniqid());

        // Act: try to map the requirement
        $mapper->mapExistingItemsForRequirements([$requirement]);

        // Assert: the requirement is reported as invalid
        $this->assertFalse($mapper->validateRequirements([$requirement]));
        $this->assertEquals([$requirement], $mapper->getInvalidRequirements([$requirement]));
    }

    #[DataProvider('formItemsProvider')]
    public function testUniqueFormItemRequirementIsMappedByName(string $itemtype): void
    {
        // Arrange: create a form with an item that has a unique name
        $name = "Unique " . uniqid();
        $form = $this->createFormWithItem($itemtype, $name);
        $mapper = $this->getMapper();
        $requirement = new DataRequirementSpecification($itemtype, $name);

        // Act: try to map the requirement
        $mapper->mapExistingItemsForRequirements([$requirement]);

        // Assert: the requirement is mapped to the item
        $this->assertTrue($mapper->validateRequirements([$requirement]));
        $this->assertEquals(
            $this->getFormItemId($form, $itemtype, $name),
            $mapper->getItemId($itemtype, $name),
        );
    }

    public function testAmbiguousFormItemRequirementIsInvalid(): void
    {
        // Arrange: create two forms with a question of the same name
        $name = "Ambiguous " . uniqid();
        $this->createFormWithItem(Question::class, $name);
        $this->createFormWithItem(Question::class, $name);
        $mapper = $this->getMapper();
        $requirement = new DataRequirementSpecification(Question::class, $name);

        // Act: try to map the requirement
        $mapper->mapExistingItemsForRequirements([$requirement]);

        // Assert: the requirement can't be mapped automatically
        $this->assertFalse($mapper->validateRequirements([$requirement]));
    }

    public function testFormItemRequirementIgnoresFormsOutsideEntities(): void
    {
        // Arrange: create a form in an entity that is not visible by the mapper
        $name = "Other entity " . uniqid();
        $builder = (new FormBuilder())
            ->setEntitiesId(getItemByTypeName('Entity', '_test_child_2', true))
            ->setIsRecursive(0)
            ->addQuestion($name, QuestionTypeShortText::class)
        ;
        $this->createForm($builder);
        $mapper = new DatabaseMapper([getItemByTypeName('Entity', '_test_child_1', true)]);
        $requirement = new DataRequirementSpecification(Question::class, $name);

        // Act: try to map the requirement
        $mapper->mapExistingItemsForRequirements([$requirement]);

        // Assert: the question is not found
        $this->assertFalse($mapper->validateRequirements([$requirement]));
    }

    public function testFormItemRequirementIgnoresDeletedForms(): void
    {
        // Arrange: create a form then put it in the trash
        $name = "Deleted form " . uniqid();
        $form = $this->createFormWithItem(Question::class, $name);
        $this->deleteItem(Form::class, $form->getID());
        $mapper = $this->getMapper();
        $requirement = new DataRequirementSpecification(Question::class, $name);

        // Act: try to map the requirement
        $mapper->mapExistingItemsForRequirements([$requirement]);

        // Assert: the question is not found
        $this->assertFalse($mapper->validateRequirements([$requirement]));
    }

    private function getMapper(): DatabaseMapper
    {
        return new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
    }

    private function createFormWithItem(string $itemtype, string $name): Form
    {
        $builder = new FormBuilder();
        match ($itemtype) {
            Question::class => $builder
                ->addSection("Section " . uniqid())
                ->addQuestion($name, QuestionTypeShortText::class),
            Section::class => $builder->addSection($name),
            Comment::class => $builder
                ->addSection("Section " . uniqid())
                ->addComment($name),
        };

        return $this->createForm($builder);
    }

    private function getFormItemId(Form $form, string $itemtype, string $name): int
    {
        return match ($itemtype) {
            Question::class => $this->getQuestionId($form, $name),
            Section::class  => $this->getSectionId($form, $name),
            Comment::class  => $this->getCommentId($form, $name),
        };
    }
}
