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

namespace Glpi\Api\HL\Doc;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    private string $description;
    /** @var string[] */
    private array $methods;
    /** @var Parameter[] $parameters */
    private array $parameters;
    /** @var Response[] $responses */
    private array $responses;

    /**
     * @param string $description
     * @param string[] $methods
     * @param array<Parameter|array{name: string, description?: string, location?: string, schema?: array{type?: string, format?: string}, example?: string, required?: bool}> $parameters
     * @param array<Response|array{methods?: array<string>, description?: string, headers?: array, schema?: array{type?: string, format?: string}, examples?: array}> $responses
     */
    public function __construct(string $description = '', array $methods = [], array $parameters = [], array $responses = [])
    {
        $this->description = $description;
        $this->methods = $methods;
        // Convert generic array to array of Parameter objects (Cannot nest class instances in attributes as of PHP 8.0)
        $this->parameters = array_map(static function ($parameter) {
            $schema = $parameter['schema'] ?? [];
            if (is_string($schema)) {
                // Reference to a known schema
                $schema = new SchemaReference($schema);
            } else if (is_array($schema)) {
                $schema = Schema::fromArray($schema);
            }
            return new Parameter(
                name: $parameter['name'],
                description: $parameter['description'] ?? '',
                location: $parameter['location'] ?? 'query',
                schema: $schema,
                example: $parameter['example'] ?? null,
                required: filter_var(isset($parameter['required']) ? $parameter['required'] : false, FILTER_VALIDATE_BOOLEAN),
            );
        }, $parameters);

        $this->responses = [];
        foreach ($responses as $status_code => $response) {
            if ((int) $status_code < 100) {
                $status_code = 200;
            }
            $schema = $response['schema'] ?? [];
            if (is_string($schema)) {
                // Reference to a known schema
                $schema = new SchemaReference($schema);
            } else if (is_array($schema)) {
                $schema = Schema::fromArray($schema);
            }
            $this->responses[] = new Response(
                description: $response['description'] ?? '',
                headers: $response['headers'] ?? [],
                schema: $schema,
                examples: $response['examples'] ?? [],
                media_type: $response['media_type'] ?? 'application/json',
                status_code: (int) $status_code,
            );
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Array of methods that this documentation applies to.
     * If empty, it will be applied to every method defined in the Route itself.
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return Response[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }
}
