<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Context\ForeignKey\ForeignKeyHandler;
use Glpi\Form\Export\Specification\ContentSpecificationInterface;
use Glpi\Form\Export\Specification\DestinationContentSpecification;
use Override;

final class TemplateFieldConfig implements
    JsonFieldInterface,
    ConfigWithForeignKeysInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const TEMPLATE_ID = 'template_id';

    public function __construct(
        private TemplateFieldStrategy $strategy,
        private ?int $specific_template_id = null,
    ) {
    }

    #[Override]
    public static function listForeignKeysHandlers(ContentSpecificationInterface $content_spec): array
    {
        if (!($content_spec instanceof DestinationContentSpecification)) {
            throw new \InvalidArgumentException(
                "Content specification must be an instance of " . DestinationContentSpecification::class
            );
        }

        $destination_item = new $content_spec->itemtype();
        $destination_target = new ($destination_item->getTargetItemtype())();
        return [
            new ForeignKeyHandler(self::TEMPLATE_ID, $destination_target->getTemplateClass())
        ];
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = TemplateFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = TemplateFieldStrategy::DEFAULT_TEMPLATE;
        }

        return new self(
            strategy: $strategy,
            specific_template_id: $data[self::TEMPLATE_ID] ?? null
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::TEMPLATE_ID => $this->specific_template_id,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGY;
    }

    /**
     * @return array<TemplateFieldStrategy>
     */
    public function getStrategies(): array
    {
        return [$this->strategy];
    }

    public function getSpecificTemplateID(): ?int
    {
        return $this->specific_template_id;
    }
}
