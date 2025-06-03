<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Tools\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class ForbidDynamicInstantiationRule implements Rule
{
    private bool $treat_php_doc_types_as_certain;

    public function __construct()
    {
        // @FIXME Fetch it from the config parameters.
        //        This is not possible right now but will be possible if this rules is moved
        //        in a PHPStan extension.
        $this->treat_php_doc_types_as_certain = false;
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isSafe($node, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Instantiating an object from an unrestricted dynamic string is forbidden. To safely instantiate a `CommonDBTM` object, please use the `getItemForItemtype()` function, otherwise, you have to limit the possible values.'
            )
            ->identifier('glpi.forbidDynamicInstantiation')
            ->build(),
        ];
    }

    private function isSafe(Node $node, Scope $scope): bool
    {
        if ($node->class instanceof Name) {
            // Either a class identifier (e.g. `new User()`),
            // or a PHP keyword (e.g. `new self()` or `new static()`).
            return true;
        }

        if ($node->class instanceof Node\Stmt\Class_) {
            // Anonymous class instantiation (e.g. `$var = new class () extends CommonDBTM {}`).
            return true;
        }

        $type = $this->treat_php_doc_types_as_certain ? $scope->getType($node->class) : $scope->getNativeType($node->class);

        if ($this->isTypeSafe($type)) {
            return true;
        }

        return false;
    }

    private function isTypeSafe(Type $type): bool
    {
        if ($type instanceof UnionType) {
            // A union type variable is safe only if all of the possible types are safe.
            foreach ($type->getTypes() as $sub_type) {
                if (!$this->isTypeSafe($sub_type)) {
                    return false;
                }
            }
            return true;
        }

        if ($type instanceof IntersectionType) {
            // A intersection type variable is safe as long as one of the type is safe.
            foreach ($type->getTypes() as $sub_type) {
                if ($this->isTypeSafe($sub_type)) {
                    return true;
                }
            }
            return false;
        }

        if ($type instanceof ObjectType) {
            // Either a instanciation from another object instance (e.g. `$a = new Computer(); $b = new $a();`),
            // or from a variable with an object type assigned by the PHPDoc (e.g. `/* @var $class Computer */ $c = new $class();`).
            // Creating an instance from an already instantiated object is considered safe.
            return true;
        }

        if ($type instanceof GenericClassStringType) {
            // A variable with a `class-string<Type>` type assigned by the PHPDoc.
            // We consider that the related code produces all the necessary
            // checks to ensure that the variable is safe before assigning this type.
            return true;
        }

        if (
            $type instanceof ConstantStringType
            && ReflectionProviderStaticAccessor::getInstance()->hasClass($type->getValue())
        ) {
            // Instantiation from a string variable with constant value that matches a known class
            // (e.g. `$class = 'Computer'; $c = new $class();`).
            // This is considered safe as the class name has been intentionally hardcoded.
            return true;
        }

        if ($type instanceof NullType) {
            // Instantiation will a `null` hardcoded class name (e.g. `$a = $condition ? Computer::class : null; $b = new $a();`),
            // or from a variable with a nullable type assigned by the PHPDoc (e.g. `/* @var $class class-string<CommonDBTM>|null */ $c = new $class();`).
            // This is safe from this rule point of view as it will not permit to instantiate an unexpected object.
            //
            // An error will be triggered by base PHPStan rules with a most relevant message.
            return true;
        }

        return false;
    }
}
