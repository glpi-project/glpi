<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI tools
 *
 * @copyright 2017-2023 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/glpi-project/tools
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI tools.
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

namespace GlpiProject\Tools\PHPStan\Rules;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Global_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;

class GlobalVarTypeRule implements Rule
{
    private FileTypeMapper $fileTypeMapper;

    public function __construct(
        FileTypeMapper $fileTypeMapper
    ) {
        $this->fileTypeMapper = $fileTypeMapper;
    }

    public function getNodeType(): string
    {
        return Stmt::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node instanceof Global_)) {
            return [];
        }

        $variablesTypes = [];
        foreach ($node->vars as $var) {
            if (!$var instanceof Variable) {
                continue;
            }
            if (!is_string($var->name)) {
                continue;
            }

            $variablesTypes[$var->name] = null;
        }

        $function = $scope->getFunction();
        foreach ($node->getComments() as $comment) {
            if (!$comment instanceof Doc) {
                continue;
            }
            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $scope->isInClass() ? $scope->getClassReflection()->getName() : null,
                $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
                $function !== null ? $function->getName() : null,
                $comment->getText(),
            );
            foreach ($resolvedPhpDoc->getVarTags() as $key => $varTag) {
                if (array_key_exists($key, $variablesTypes)) {
                    $variablesTypes[$key] = $varTag->getType()->toPhpDocNode();
                }
            }
        }

        $errors = [];

        foreach ($variablesTypes as $variableName => $variableType) {
            if ($variableType === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Missing PHPDoc tag @var for global variable $%s',
                        $variableName
                    )
                )->identifier('varTag.noType')->build();
            }
        }

        return $errors;
    }
}
