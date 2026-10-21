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

namespace Glpi\Application\View\Extension;

use Glpi\Security\ContentSecurityPolicy\NonceContext;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the Content-Security-Policy nonce of the current response, to be
 * used on every `<script>` tags: `<script nonce="{{ csp_nonce() }}">`.
 */
final class ContentSecurityPolicyExtension extends AbstractExtension
{
    public function __construct(private NonceContext $nonce_context) {}

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->getNonce(...)),
        ];
    }

    public function getNonce(): string
    {
        return $this->nonce_context->getNonce();
    }
}
