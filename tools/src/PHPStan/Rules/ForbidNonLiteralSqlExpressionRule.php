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

namespace Glpi\Tools\PHPStan\Rules;

use Glpi\DBAL\QueryElementInterface;
use Glpi\DBAL\QueryExpression;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Forbids building a raw SQL fragment from anything but a literal string.
 *
 * `QueryExpression` is the only escape hatch that injects verbatim SQL into a query. It is
 * legitimate for a hardcoded fragment, but as soon as the fragment is assembled at runtime
 * the safety of the whole statement depends on the caller, and nothing can verify it.
 *
 * Identifiers belong in `QueryIdentifier`, values in `QueryValue` (or in the `values:`
 * argument, bound as statement parameters), and SQL fragments in `QueryFunction` /
 * `QuerySubQuery`.
 *
 * @implements Rule<New_>
 */
final class ForbidNonLiteralSqlExpressionRule implements Rule
{
    /**
     * Classes whose first constructor argument is raw SQL, mapped to that argument name.
     *
     * @var array<class-string, string>
     */
    private const RAW_SQL_CLASSES = [
        QueryExpression::class => 'expression',
    ];

    public function __construct(private bool $treatPhpDocTypesAsCertain) {}

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->class instanceof Name)) {
            // Dynamic instantiation, handled by ForbidDynamicInstantiationRule.
            return [];
        }

        $class_name = $scope->resolveName($node->class);
        $arg_name = self::RAW_SQL_CLASSES[$class_name] ?? null;
        if ($arg_name === null) {
            return [];
        }

        $arg = $this->getArgument($node->args, $arg_name);
        if ($arg === null) {
            // No argument, or arguments spread from an array: nothing to analyse.
            return [];
        }

        $type = $this->treatPhpDocTypesAsCertain
            ? $scope->getType($arg->value)
            : $scope->getNativeType($arg->value);

        if ($this->isTypeSafe($type)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Building a %s from a non-literal SQL string is forbidden.'
                    . ' Use QueryIdentifier for an identifier, QueryValue for a value,'
                    . ' QueryFunction or QuerySubQuery for a SQL fragment,'
                    . ' or pass the dynamic parts through the `values:` argument to have them bound'
                    . ' as statement parameters.',
                    $class_name
                )
            )
                ->identifier('glpi.forbidNonLiteralSqlExpression')
                ->line($arg->getStartLine())
                ->build(),
        ];
    }

    private function isTypeSafe(Type $type): bool
    {
        if ($type instanceof UnionType) {
            // A union type is safe only if all of the possible types are safe.
            foreach ($type->getTypes() as $sub_type) {
                if (!$this->isTypeSafe($sub_type)) {
                    return false;
                }
            }
            return true;
        }

        if ((new ObjectType(QueryElementInterface::class))->isSuperTypeOf($type)->yes()) {
            // A query element renders its own SQL and carries its own parameters.
            return true;
        }

        // A literal SQL string. Concatenation and interpolation of constants are folded into a
        // constant string by PHPStan, so `'FOO(' . self::SEP . ')'` is accepted too.
        return count($type->getConstantStrings()) === 1;
    }

    /**
     * @param array<Arg|Node\VariadicPlaceholder> $args
     */
    private function getArgument(array $args, string $name): ?Arg
    {
        foreach ($args as $arg) {
            if (!($arg instanceof Arg)) {
                // Argument unpacking (`...$args`): the position cannot be resolved statically.
                return null;
            }
            if ($arg->name === null || $arg->name->name === $name) {
                return $arg;
            }
        }

        return null;
    }
}
