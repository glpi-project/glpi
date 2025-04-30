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

namespace Glpi\Form\Export\Serializer;

use Glpi\Form\Export\Specification\ExportContentSpecification;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Simple wrapper for the Serializer component.
 * This is where we configure options relative to symfony's serializer.
 *
 * Form serializing logic must be delegated to a concrete class that will extend
 * this one.
 */
abstract class AbstractFormSerializer
{
    private Serializer $serializer;

    public function __construct()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            // Need to handle arrays of objects
            new ArrayDenormalizer(),

            // The `propertyTypeExtractor` parameter is required to normalize
            // nested objects because we are not a full symfony application.
            // See: https://symfony.com/doc/current/components/serializer.html#recursive-denormalization-and-type-safety
            new PropertyNormalizer(propertyTypeExtractor: new PhpDocExtractor()),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    protected function serialize(ExportContentSpecification $specification): string
    {
        return $this->serializer->serialize($specification, 'json', [
            PropertyNormalizer::NORMALIZE_VISIBILITY => PropertyNormalizer::NORMALIZE_PUBLIC,
        ]);
    }

    protected function deserialize(string $json): ExportContentSpecification
    {
        return $this->serializer->deserialize(
            $json,
            ExportContentSpecification::class,
            'json'
        );
    }
}
