<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

class GlpiNetwork extends \GLPITestCase {

   protected function registrationKeyProvider() {
      $dataset = [];

      $registration_key = $this->buildKey(
         [
            'some' => 'data',
            'that' => 'key',
            'may'  => 'contains',
         ]
      );
      $signature_key = $this->getSignaturePublicKey();

      // Invalid key (random string).
      $dataset[] = [
         'registration_key' => 'abcdefg',
         'signature_key'    => $signature_key,
         'expected' => [
            'is_valid'           => false,
            'validation_message' => __('The registration key is invalid.'),
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      // Invalid key (missing signature).
      $keyData = json_decode(base64_decode($registration_key), true);
      unset($keyData['signature']);
      $dataset[] = [
         'registration_key' => base64_encode(json_encode($keyData)),
         'signature_key'    => $signature_key,
         'expected' => [
            'is_valid'           => false,
            'validation_message' => __('The registration key is invalid.'),
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      // Invalid signature (mismatch key data).
      $keyData = json_decode(base64_decode($registration_key), true);
      $keyData['extra'] = 'data';
      $dataset[] = [
         'registration_key' => base64_encode(json_encode($keyData)),
         'signature_key'    => $signature_key,
         'expected' => [
            'is_valid'           => false,
            'validation_message' => __('Registration key signature is not valid.'),
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      // Missing signature key (cannot be retrieved in offline mode).
      $dataset[] = [
         'registration_key' => $registration_key,
         'signature_key'    => '',
         'expected' => [
            'is_valid'           => false,
            'validation_message' => __('Unable to verify registration key signature.'),
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      // Valid registration and signature keys.
      $dataset[] = [
         'registration_key' => $registration_key,
         'signature_key'    => $signature_key,
         'expected' => [
            'is_valid'           => true,
            'validation_message' => null,
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      // Valid registration and signature keys.
      // Registration key contains extra data (check that data stored in registration key can evolve).
      $dataset[] = [
         'registration_key' => $this->buildKey(
            [
               'customer_reference' => 'TestCustomer01',
               'owner_name'         => 'John Doe',
               'subscription_title' => 'Registered',
               'some_data'          => 'that can be added',
               'to_enhance'         => 'informations',
            ]
         ),
         'signature_key'    => $signature_key,
         'expected' => [
            'is_valid'           => true,
            'validation_message' => null,
            'owner'              => null,
            'subscription'       => null,
         ],
      ];

      return $dataset;
   }


   /**
    * @dataProvider registrationKeyProvider
    */
   public function testGetRegistrationInformations(string $registration_key, string $signature_key, array $expected) {
      global $CFG_GLPI;

      $cfg_backup = $CFG_GLPI;
      $CFG_GLPI['glpinetwork_signature_key'] = $signature_key;
      $CFG_GLPI['glpinetwork_registration_key'] = $registration_key;
      $informations = \GLPINetwork::getRegistrationInformations(true);
      $CFG_GLPI = $cfg_backup;

      $this->array($informations)->isEqualTo($expected);
   }

   /**
    * Generate a registration key based on given informations.
    *
    * @param array $informations
    *
    * @return string
    */
   private function buildKey(array $informations) {
      $signature = null;
      openssl_sign(json_encode($informations), $signature, $this->getSignaturePrivateKey());

      $informations['signature'] = base64_encode($signature);
      return base64_encode(json_encode($informations));
   }

   /**
    * Get private key used to sign registration informations.
    *
    * @return string
    */
   private function getSignaturePrivateKey() {
      return file_get_contents(GLPI_ROOT . '/tests/resources/registration_private.pem');
   }

   /**
    * Get public key used to validate signature of registration informations.
    *
    * @return string
    */
   private function getSignaturePublicKey() {
      return file_get_contents(GLPI_ROOT . '/tests/resources/registration_public.pem');
   }
}
