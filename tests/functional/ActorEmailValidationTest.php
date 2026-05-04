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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Safe\preg_match;

class ActorEmailValidationTest extends DbTestCase
{
    /**
     * @return iterable<array{string, int}>
     */
    public static function emailValidationProvider()
    {
        yield ['josé@example.com', 1];
        yield ['françois@example.fr', 1];
        yield ['müller@example.de', 1];
        yield ['andré.garcía@example.es', 1];
        yield ['jürgen_øvergård@example.no', 1];
        yield ['andré+tag@example.com', 1];
        yield ['søren@example.dk', 1];
        yield ['björk@example.is', 1];
        yield ['déjà-vu@example.com', 1];
        yield ['naïve@example.com', 1];
        yield ['user_123@mötörhead.com', 1];
        yield ['test.user+tag@café.fr', 1];
        yield ['FRANÇOIS@EXAMPLE.FR', 1];
        yield ['José.García@Example.COM', 1];
        yield ['test_user-123@münchen.de', 1];
        yield ["o'brien@example.com", 1];
        yield ['nëñö_123@example.com', 1];
        yield ['test.user%tag@example.com', 1];
        yield ['user+test@localhost.domain.com', 1];
        yield ['test@example.com', 1];
        yield ['user.name@example.com', 1];
        yield ['user+tag@example.com', 1];
        yield ['user_name@example.com', 1];
        yield ['user-name@example.com', 1];
        yield ['user%name@example.com', 1];
        yield ['test.user+tag@sub.example.com', 1];
        yield ['a@b.co', 1];
        yield ['123@example.com', 1];
        yield ['user123@example.com', 1];
        yield ['user@sub1.sub2.example.com', 1];
        yield ['user@example123.com', 1];
        yield ['user@ex-ample.com', 1];
        yield ['user@josé.example.com', 1];
        yield ['user@münchen.de', 1];
        yield ['user@café.fr', 1];
        yield ['user@ñoño.es', 1];
        yield ['user@例え.jp', 1];
        yield ['test@José.com', 1];
        yield [str_repeat('a', 63) . '@example.' . str_repeat('a', 63), 1];
        yield ['', 0];
        yield ['notanemail', 0];
        yield ['@example.com', 0];
        yield ['user@', 0];
        yield ['user@@example.com', 0];
        yield ['user@example', 0];
        yield ['user @example.com', 0];
        yield ['user@exam ple.com', 0];
        yield ['user@example.c', 0];
        yield ['user name@example.com', 0];
        yield ['user@example .com', 0];
        yield ['user@example.' . str_repeat('a', 64), 0];
    }

    #[DataProvider('emailValidationProvider')]
    public function testActorEmailValidation(string $email, int $expected): void
    {
        // Tests the regex used in templates/components/itilobject/actors/field.html.twig
        // for validating email addresses in the Select2 tag creation
        $is_valid = preg_match(\Toolbox::ACTOR_EMAIL_VALIDATION_REGEX, $email);

        $this->assertSame(
            $expected,
            $is_valid,
            sprintf('Email "%s" should be %s', $email, $expected ? 'valid' : 'invalid')
        );
    }
}
