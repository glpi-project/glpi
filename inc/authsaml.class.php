<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

/**
 *  Class used to manage Auth LDAP config
**/
class AuthSAML extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';


   static function getTypeName($nb=0) {
      return __('SAML authentication');
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('AuthMapping', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }



   /**
    * Print the auth ldap form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the form
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {

      if (!Config::canUpdate()) {
         return false;
      }
      $spotted = false;
      if (empty($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (Toolbox::canUseOpenssl()) {
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Active'). "</td>";
         echo "<td>";
         Dropdown::showYesNo('is_active', $this->fields['is_active']);
         echo "</td>";
         echo "<td>" . __('Comment'). "</td>";
         echo "<td>";
         echo "<input type='text' name='comment' size='60' value=\"".$this->fields["comment"]."\">";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4'><br/></td>";
         echo "</tr>";

         echo "<tr><th colspan='4'>" . __('Service Provider') . "</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('entityID'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_entityid' size='60' value=\"".$this->fields["sp_entityid"]."\">";
         echo "</td>";
         echo "<td>" . __('Certificate'). "</td>";
         echo "<td>";
         echo "<textarea cols='40' rows='4' name='sp_x509cert'>".$this->fields["sp_x509cert"]."</textarea>";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Name identifier'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_nameidformat' size='60' value=\"".$this->fields["sp_nameidformat"]."\">";
         echo "</td>";
         echo "<td>" . __('Private key of certificate'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_privateKey' size='60' value=\"".$this->fields["sp_privateKey"]."\">";
         echo "</td>";
         echo "</tr>";

         echo "<tr>";
         echo "<th colspan='2'>" . __('Assertion customer service (response)') . "</th>";
         echo "<th colspan='2'>" . __('Single logout service (logout)') . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('URL Location'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_assertionconsumerservice_url' size='60' value=\"".$this->fields["sp_assertionconsumerservice_url"]."\">";
         echo "</td>";
         echo "<td>" . __('URL Location'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_singlelogoutservice_url' size='60' value=\"".$this->fields["sp_singlelogoutservice_url"]."\">";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Protocol binding'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_assertionconsumerservice_binding' size='60' value=\"".$this->fields["sp_assertionconsumerservice_binding"]."\">";
         echo "</td>";
         echo "<td>" . __('Protocol binding'). "</td>";
         echo "<td>";
         echo "<input type='text' name='sp_singlelogoutservice_binding' size='60' value=\"".$this->fields["sp_singlelogoutservice_binding"]."\">";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4'><br/></td>";
         echo "</tr>";

         echo "<tr><th colspan='4'>" . __('Identity Provider') . "</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('entityID'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_entityid' size='60' value=\"".$this->fields["idp_entityid"]."\">";
         echo "</td>";
         echo "<td>" . __('Certificate'). "</td>";
         echo "<td>";
         echo "<textarea cols='40' rows='4' name='idp_x509cert'>".$this->fields["idp_x509cert"]."</textarea>";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Fingerprint of certificate'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_certfingerprint' size='60' value=\"".$this->fields["idp_certfingerprint"]."\">";
         echo "</td>";
         echo "</td>";
         echo "<td>" . __('Fingerprint algorithm'). "</td>";
         echo "<td>";
         $elements = array('sha1', 'sha256', 'sha384', 'sha512');
         Dropdown::showFromArray(
                 'idp_certfingerprintalgorithm',
                 $elements,
                 array('value' => $this->fields["idp_certfingerprintalgorithm"]));
         echo "</td>";
         echo "</tr>";

         echo "<tr>";
         echo "<th colspan='2'>" . __('SSO endpoint info (for login)') . "</th>";
         echo "<th colspan='2'>" . __('SSO endpoint info (for logout)') . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('URL Location'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_singlesignonservice_url' size='60' value=\"".$this->fields["idp_singlesignonservice_url"]."\">";
         echo "</td>";
         echo "<td>" . __('URL Location'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_singlelogoutservice_url' size='60' value=\"".$this->fields["idp_singlelogoutservice_url"]."\">";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('Protocol binding'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_singlesignonservice_binding' size='60' value=\"".$this->fields["idp_singlesignonservice_binding"]."\">";
         echo "</td>";
         echo "<td>" . __('Protocol binding'). "</td>";
         echo "<td>";
         echo "<input type='text' name='idp_singlelogoutservice_binding' size='60' value=\"".$this->fields["idp_singlelogoutservice_binding"]."\">";
         echo "</td>";
         echo "</tr>";

         $this->showFormButtons($options);

      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . self::getTypeName(1) . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>". __("The Openssl extension of your PHP parser isn't installed")."</p>";
         echo "<p>".__('Impossible to use Openssl as external source of connection')."</p>".
              "</td></tr></table></div>";
      }

      echo "<div align='center'>";
      echo "<a href='".$this->getFormURL()."?metadata' target='_blank' class='vsubmit'>".__('Generate SAML metadata (XML file)')."<a>";
      echo "</div><br/><br/>";
   }



   function generate_settings() {
      $this->getFromDB(1);

      $sp_x509cert = $this->fields['sp_x509cert'];
      $sp_x509cert = str_replace('-----BEGIN CERTIFICATE-----', '', $sp_x509cert);
      $sp_x509cert = str_replace('-----END CERTIFICATE-----', '', $sp_x509cert);
      $sp_x509cert = str_replace(array("\r\n", "\r", "\n"), "", $sp_x509cert);
      $sp_x509cert = trim($sp_x509cert);

$privkey = "-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDGt1QMjQXAwdpUh/oDqINnEnlWxja8mlq+mRLUy4sl+8NC3s8Q
IL0uUlL07sIoC8hx2CoSCq2+iRTfM18FDIX/DW3wav0hdeyUObQeeqDml8GGtWIj
KZ9jzPY/HxjQ1Smg/Ig1SXQfUWBoWUtgY4ReK7j7BEwxaXam180Z2OyovwIDAQAB
AoGBAJWaEVm9lZYvmypGkI/OftbLTrRoV28YGbciUM4JSwHWj4M5cNPogeRsr+2c
DmnNrSMgJVQd2/30/9SlvSR08C7mW86qtYthdGHBvTv9tKh/VFv5HpT4WXi0fdf9
k+UUkA5LSPQiupShq6cWL1BeBBJCeUQfhisB8VlJitHQ+Dl5AkEA9ZWnIF9Dm3GO
p2vPNTzOm2Xej9eMPlqcWAJQatk+0dp15rMhSThbrq01A6ZiU4b1LRWNLEv3qV6L
YQ94FFteNQJBAM8k0WK2Hh06VEwcqiOixVY0V14+n3RhUOxy0SXOdKNPJ0poNXlj
ec+ZwcXMjsqXFZ8dGTa07mAGiEY+Dt/qmaMCQCIIXSK1UO8nq3c0D4D3LkKGuXMB
kePKNsRTfcVw2a3HMnOH+2LkNoBcbG5XDmU43J0k0W2EEYwdF/+ZXzCjAMkCQQC+
f96siE4TlSQXRzlVQol3KPW8b50XW7Qfb8xWl5L+6Xl53XJoK4rH2GCzNtePCvlQ
dmNMw4KB/x/ucX4egVM/AkAvS6RbBeBaovzXaLVGnnVMXITqNhBygca9l/h+UEPY
Hr1Hq+mzI6tJqfMQRoYVZFdRNloJS3xhClqfnk+P1xR+
-----END RSA PRIVATE KEY-----";

$privkey = str_replace('-----BEGIN CERTIFICATE-----', '', $privkey);
$privkey = str_replace('-----END CERTIFICATE-----', '', $privkey);
$privkey = str_replace(array("\r\n", "\r", "\n"), "", $privkey);
$privkey = trim($privkey);

      $idp_x509cert = $this->fields['idp_x509cert'];
      $idp_x509cert = str_replace('-----BEGIN CERTIFICATE-----', '', $idp_x509cert);
      $idp_x509cert = str_replace('-----END CERTIFICATE-----', '', $idp_x509cert);
      $idp_x509cert = str_replace(array("\r\n", "\r", "\n"), "", $idp_x509cert);
      $idp_x509cert = trim($idp_x509cert);

      $settings = array (
         'strict' => false,
         'debug'  => true,
         'sp'     => array(
            'entityId' => $this->fields['sp_entityid'],
            'assertionConsumerService' => array(
               'url'     => $this->fields['sp_assertionconsumerservice_url'],
               'binding' => $this->fields['sp_assertionconsumerservice_binding'],
            ),
            'singleLogoutService' => array(
               'url'     => $this->fields['sp_singlelogoutservice_url'],
               'binding' => $this->fields['sp_singlelogoutservice_binding'],
            ),
            'NameIDFormat' => $this->fields['sp_nameidformat'],
            'x509cert'     => $sp_x509cert,
            'privateKey'   => $privkey,
         ),
         'idp'    => array(
            'entityId' => $this->fields['idp_entityid'],
            'singleSignOnService' => array(
               'url'     => $this->fields['idp_singlesignonservice_url'],
               'binding' => $this->fields['idp_singlesignonservice_binding'],
            ),
            'singleLogoutService' => array(
               'url'     => $this->fields['idp_singlelogoutservice_url'],
               'binding' => $this->fields['idp_singlelogoutservice_binding'],
            ),
            'x509cert' => $idp_x509cert,
            'certFingerprint' => $this->fields['idp_certfingerprint'],
            'certFingerprintAlgorithm' => 'sha1',
         ),
         'security' => array('requestedAuthnContext' => False)
      );
      return $settings;
   }



   function generate_metadata() {
      $this->getFromDB(1);

      require_once(GLPI_ROOT.'/lib/php-saml/_toolkit_loader.php');

      try {
         $auth = new OneLogin_Saml2_Auth($this->generate_settings());
         $settings = $auth->getSettings();
         $metadata = $settings->getSPMetadata();
         $errors = $settings->validateMetadata($metadata);
         if (empty($errors)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=metadata.xml');
            header('Content-Transfer-Encoding: binary');
            header('Connection: Keep-Alive');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            echo $metadata;
         } else {
             throw new OneLogin_Saml2_Error(
                 'Invalid SP metadata: '.implode(', ', $errors),
                 OneLogin_Saml2_Error::METADATA_SP_INVALID
             );
         }
      } catch (Exception $e) {
         echo $e->getMessage();
      }
   }
}
?>
