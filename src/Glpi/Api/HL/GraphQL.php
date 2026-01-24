<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Api\HL;

use Glpi\Api\HL\GraphQL\DefaultResolvers;
use Glpi\Api\HL\GraphQL\SchemaGenerator;
use Glpi\Api\HL\GraphQL\Types;
use Glpi\Application\Environment;
use Glpi\Debug\Profiler;
use Glpi\Http\Request;
use GraphQL\Executor\Executor;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Throwable;

use function Safe\json_decode;

final class GraphQL
{
    /**
     * Maximum depth of fields in the query that will be recognized.
     */
    public const MAX_QUERY_FIELD_DEPTH = 15;
    private static $resolver_time = 0;

    public static function processRequest(Request $request): array
    {
        $api_version = $request->getHeaderLine('GLPI-API-Version') ?: Router::API_VERSION;
        $query = self::extractQueryFromBody($request);

        Profiler::getInstance()->start('GraphQL::processRequest', Profiler::CATEGORY_HLAPI);

        try {
            Profiler::getInstance()->start('GraphQL::executeQuery', Profiler::CATEGORY_HLAPI);
            Profiler::getInstance()->start('GraphQL::getSchema', Profiler::CATEGORY_HLAPI);
            $schema_generator = new SchemaGenerator($api_version);
            $schema = $schema_generator->getSchema();
            Profiler::getInstance()->stop('GraphQL::getSchema');
            //TODO Need to re-add pagination response headers
            $result = \GraphQL\GraphQL::executeQuery(
                schema: $schema,
                source: $query,
                fieldResolver: self::getFieldResolver($api_version),
                validationRules: self::getValidationRules(),
            )->setErrorsHandler(function (array $errors, callable $formatter) {
                return array_map($formatter, $errors);
            });

            Profiler::getInstance()->stop('GraphQL::executeQuery');
        } catch (Throwable $e) {
            global $PHPLOGGER;
            $PHPLOGGER->error(
                "Error processing GraphQL request: {$e->getMessage()}",
                ['exception' => $e]
            );

            return [];
        } finally {
            Profiler::getInstance()->stop('GraphQL::processRequest');
        }
        return $result->toArray();
    }

    private static function getFieldResolver(string $api_version): ?callable
    {
        $default_resolvers = new DefaultResolvers($api_version);
        return static function ($source, $args, $context, ResolveInfo $info) use ($default_resolvers) {
            $start_time = microtime(true);
            $field_type = $info->returnType;
            $is_scalar = !($field_type instanceof ObjectType || $field_type instanceof ListOfType);

            if ($is_scalar) {
                $resolved = $default_resolvers->resolveScalarField($source, $args, $context, $info);
            } elseif ($field_type instanceof ListOfType) {
                $resolved = $default_resolvers->resolveListField($source, $args, $context, $info);
            } else {
                $resolved = $default_resolvers->resolveObjectField($source, $args, $context, $info);
            }
            self::$resolver_time += microtime(true) - $start_time;
            return $resolved;
        };
    }

    private static function getValidationRules(): array
    {
        $rules = [
            new QueryComplexity(100),
            new QueryDepth(self::MAX_QUERY_FIELD_DEPTH),
        ];
        foreach (DocumentValidator::defaultRules() as $rule) {
            $rules[] = $rule;
        }
        return $rules;
    }

    private static function extractQueryFromBody(Request $request): string
    {
        $contentType = $request->getHeaderLine('Content-Type');

        return match ($contentType) {
            'application/graphql' => (string) $request->getBody(),
            'application/json' => json_decode((string) $request->getBody(), true)['query'] ?? '',
            default => (string) $request->getBody(),
        };
    }
}
