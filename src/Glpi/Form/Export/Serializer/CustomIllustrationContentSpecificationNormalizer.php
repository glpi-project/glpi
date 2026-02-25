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

namespace Glpi\Form\Export\Serializer;

use Glpi\Form\Export\Specification\CustomIllustrationContentSpecification;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomIllustrationContentSpecificationNormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): CustomIllustrationContentSpecification
    {
        if (!\is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'Expected array data for CustomIllustrationContentSpecification.',
                $data,
                ['array'],
                $context['deserialization_path'] ?? null,
            );
        }

        $specification = new CustomIllustrationContentSpecification();
        $specification->key = $data['key'];
        $specification->data = $data['data'];
        $specification->checksum = $data['checksum'];

        return $specification;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === CustomIllustrationContentSpecification::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CustomIllustrationContentSpecification::class => true,
        ];
    }
}
