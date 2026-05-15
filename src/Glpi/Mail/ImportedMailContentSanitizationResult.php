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

namespace Glpi\Mail;

final class ImportedMailContentSanitizationResult
{
    /**
     * @param string[] $applied_steps
     */
    public function __construct(
        private readonly string $content,
        private readonly bool $changed,
        private readonly array $applied_steps,
        private readonly ?string $source_encoding
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function hasChanged(): bool
    {
        return $this->changed;
    }

    /**
     * @return string[]
     */
    public function getAppliedSteps(): array
    {
        return $this->applied_steps;
    }

    public function getSourceEncoding(): ?string
    {
        return $this->source_encoding;
    }
}
