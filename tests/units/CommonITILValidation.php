<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use CommonITILValidation as CoreCommonITILValidation;
use GLPITestCase;

class CommonITILValidation extends GLPITestCase {

   protected function testComputeValidationProvider(): array {
      return [
         // 100% validation required
         [
            'accepted'           => 0,
            'refused'            => 0,
            'validation_percent' => 100,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 10,
            'refused'            => 0,
            'validation_percent' => 100,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 90,
            'refused'            => 0,
            'validation_percent' => 100,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 100,
            'refused'            => 0,
            'validation_percent' => 100,
            'result'             => CoreCommonITILValidation::ACCEPTED,
         ],
         [
            'accepted'           => 0,
            'refused'            => 10,
            'validation_percent' => 100,
            'result'             => CoreCommonITILValidation::REFUSED,
         ],
         // 50% validation required
         [
            'accepted'           => 0,
            'refused'            => 0,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 10,
            'refused'            => 0,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 50,
            'refused'            => 0,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::ACCEPTED,
         ],
         [
            'accepted'           => 0,
            'refused'            => 10,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 0,
            'refused'            => 50,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 0,
            'refused'            => 60,
            'validation_percent' => 50,
            'result'             => CoreCommonITILValidation::REFUSED,
         ],
         // 0% validation required
         [
            'accepted'           => 0,
            'refused'            => 0,
            'validation_percent' => 0,
            'result'             => CoreCommonITILValidation::WAITING,
         ],
         [
            'accepted'           => 10,
            'refused'            => 0,
            'validation_percent' => 0,
            'result'             => CoreCommonITILValidation::ACCEPTED,
         ],
         [
            'accepted'           => 0,
            'refused'            => 10,
            'validation_percent' => 0,
            'result'             => CoreCommonITILValidation::REFUSED,
         ],
      ];
   }

   /**
    * @dataprovider testComputeValidationProvider
    */
   public function testComputeValidation(
      int $accepted,
      int $refused,
      int $validation_percent,
      int $result
   ): void {
      $test_result = CoreCommonITILValidation::computeValidation(
         $accepted,
         $refused,
         $validation_percent
      );

      $this->integer($test_result)->isEqualTo($result);
   }
}
