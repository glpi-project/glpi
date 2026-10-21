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

use Glpi\Application\View\NodeVisitor\CspNonceNodeVisitor;
use Glpi\Security\ContentSecurityPolicy\NonceContext;
use Override;
use Twig\Extension\AbstractExtension;

/**
 * Kept separate from other extensions: Twig's compiled template cache key
 * includes the registered extension class names, so adding this extension
 * forces templates compiled without the nonce visitor to be recompiled.
 */
final class ContentSecurityPolicyExtension extends AbstractExtension
{
    public function __construct(private NonceContext $nonce_context) {}

    public function getNonce(): string
    {
        return $this->nonce_context->getNonce();
    }

    #[Override]
    public function getNodeVisitors(): array
    {
        return [
            new CspNonceNodeVisitor(),
        ];
    }
}
