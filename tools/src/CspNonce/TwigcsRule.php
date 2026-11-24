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

namespace Glpi\Tools\CspNonce;

use FriendsOfTwig\Twigcs\Rule\AbstractRule;
use FriendsOfTwig\Twigcs\Rule\RuleInterface;
use FriendsOfTwig\Twigcs\TwigPort\TokenStream;
use FriendsOfTwig\Twigcs\Validator\Violation;
use Override;

/**
 * Twigcs rule reporting the `<script>` tags of Twig templates that do not carry the CSP nonce.
 *
 * @see MissingNonceFinder
 */
final class TwigcsRule extends AbstractRule implements RuleInterface
{
    /**
     * @return Violation[]
     */
    #[Override]
    public function check(TokenStream $tokens)
    {
        // An HTML tag may be split across several tokens (text, Twig expressions...),
        // the whole source is easier to analyse.
        $source = $tokens->getSourceContext();

        return array_map(
            fn(array $script): Violation => $this->createViolation(
                $source->getPath(),
                $script['line'],
                $script['column'],
                $script['message']
            ),
            MissingNonceFinder::find($source->getCode())
        );
    }
}
