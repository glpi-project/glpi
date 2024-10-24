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

namespace Glpi\Form\QuestionType;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Export\Context\ConfigWithForeignKeysInterface;
use Glpi\Form\Export\Context\ForeignKey\ForeignKeyHandler;
use Glpi\Form\Export\Specification\ContentSpecificationInterface;
use Glpi\Form\Export\Specification\QuestionContentSpecification;
use Override;

final class QuestionTypeItemDefaultValueConfig implements JsonFieldInterface, ConfigWithForeignKeysInterface
{
    // Unique reference to hardcoded name used for serialization
    public const KEY_ITEMS_ID = "items_id";

    /**
     * @param null|int|string $items_id Must accept a string because the foreign key handler
     *                                  replaces the ID with the item name during serialization.
     */
    public function __construct(
        private null|int|string $items_id = null
    ) {
    }

    #[Override]
    public static function listForeignKeysHandlers(ContentSpecificationInterface $content_spec): array
    {
        if (!($content_spec instanceof QuestionContentSpecification)) {
            throw new \InvalidArgumentException(
                "Content specification must be an instance of " . QuestionContentSpecification::class
            );
        }

        $extra_data_config = (new QuestionTypeItemExtraDataConfig())->jsonDeserialize(
            json_decode($content_spec->extra_data, true)
        );

        $default_value_config = (new self())->jsonDeserialize(
            json_decode($content_spec->default_value, true)
        );

        if (
            $extra_data_config->getItemtype() !== null
            && !empty($default_value_config->items_id)
        ) {
            return [
                new ForeignKeyHandler(
                    key: self::KEY_ITEMS_ID,
                    itemtype: $extra_data_config->getItemtype(),
                ),
            ];
        }

        return [];
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            items_id: $data[self::KEY_ITEMS_ID] ?? null,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::KEY_ITEMS_ID => $this->items_id,
        ];
    }

    public function getItemsId(): ?int
    {
        return $this->items_id;
    }
}
