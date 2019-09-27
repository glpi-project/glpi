<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/operatingsystem.class.php */

class OperatingSystem extends AbstractInventoryAsset {

   protected function assetProvider() :array {
      return [
         [
            'nodes'  => [
               'ARCH'            => 'x86_64',
               'BOOT_TIME'       => '2018-10-02 08:56:09',
               'DNS_DOMAIN'      => 'teclib.infra',
               'FQDN'            => 'glpixps.teclib.infra',
               'FULL_NAME'       => 'Fedora 28 (Workstation Edition)',
               'HOSTID'          => 'a8c07701',
               'KERNEL_NAME'     => 'linux',
               'KERNEL_VERSION'  => '4.18.9-200.fc28.x86_64',
               'NAME'            => 'Fedora',
               'VERSION'         => '28 (Workstation Edition)'
            ],
            'expected'  => '{"arch": "x86_64", "boot_time": "2018-10-02 08:56:09", "dns_domain": "teclib.infra", "fqdn": "glpixps.teclib.infra", "full_name": "Fedora 28 (Workstation Edition)", "hostid": "a8c07701", "kernel_name": "linux", "kernel_version": "4.18.9-200.fc28.x86_64", "name": "Fedora", "version": "28 (Workstation Edition)", "timezone": {"name": "CEST", "offset": "+0200"}, "operatingsystems_id": "Fedora 28 (Workstation Edition)", "operatingsystemversions_id": "28 (Workstation Edition)", "operatingsystemarchitectures_id": "x86_64", "operatingsystemkernels_id": "linux", "operatingsystemkernelversions_id": "4.18.9-200.fc28.x86_64"}'
         ], [
            'nodes'  => [
               'ARCH'           => '64-bit',
               'FULL_NAME'      => 'Microsoft Windows 7 Enterprise',
               'KERNEL_NAME'    => 'MSWin32',
               'KERNEL_VERSION' => '6.1.7600',
               'NAME'           => 'Windows',
               'SERVICE_PACK'   => ''
            ],
            'expected'  => [
               'operatingsystemarchitectures_id'   => '64-bit',
               'operatingsystemkernels_id'         => 'MSWin32',
               'operatingsystemkernelversions_id'  => '6.1.7600',
               'operatingsystems_id'               => 'Microsoft Windows 7 Enterprise',
            ]
         ]
      ] + $this->fusionProvider();
   }

   /**
    * @dataProvider assetProvider
    */
   public function testPrepare($nodes, $expected) {
      $xml = $this->buildXml($nodes);

      $this->login();
      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($xml);
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\OperatingSystem($computer, (array)$json->content->operatingsystem);
      $asset->setExtraData((array)$json->content);
      $conf = new \Glpi\Inventory\Conf();
      $this->boolean(
         $asset->checkConf($conf)
      )->isTrue();
      $result = $asset->prepare();
      if (!is_array($expected)) {
         $object = json_decode($expected);
      } else {
         $object = clone $json->content->operatingsystem;

         foreach ($expected as $name => $value) {
            $object->$name = $value;
         }
         $tz = new \stdClass();
         $tz->name = 'CEST';
         $tz->offset = '+0200';
         $object->timezone = $tz;
      }
      $this->object($result[0])->isEqualTo($object);
   }

   private function buildXml($nodes) {
      $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>";

      foreach ($nodes as $name => $value) {
         $xml .= "\t\t\t<$name>$value</$name>\n";
      }

      $xml .= "      <TIMEZONE>
        <NAME>CEST</NAME>
        <OFFSET>+0200</OFFSET>
      </TIMEZONE>";
      $xml .= "    </OPERATINGSYSTEM>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";
      return $xml;
   }

   protected function fusionProvider() :array {
      $data = [
      //          array(
      //              array(
      //                  'ARCH'           => '',
      //                  'FULL_NAME'      => '',
      //                  'KERNEL_NAME'    => '',
      //                  'KERNEL_VERSION' => '',
      //                  'NAME'           => '',
      //                  'SERVICE_PACK'   => ''
      //              ),
      //              array(
      //                  'arch'        => '',
      //                  'kernname'    => '',
      //                  'kernversion' => '',
      //                  'os'          => '',
      //                  'osversion'   => '',
      //                  'servicepack' => '',
      //                  'edition'     => ''
      //              ),
      //              array(
      //                  'arch'        => '',
      //                  'kernname'    => '',
      //                  'kernversion' => '',
      //                  'os'          => '',
      //                  'osversion'   => '',
      //                  'servicepack' => '',
      //                  'edition'     => ''
      //              )
      //          ),
          [
              [
                  'ARCH'           => '64-bit',
                  'FULL_NAME'      => 'Microsoft Windows 7 Enterprise',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.1.7600',
                  'NAME'           => 'Windows',
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7600',
                  'os'          => 'Windows',
                  'osversion'   => '7',
                  'edition'     => 'Enterprise'
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7600',
                  'os'          => 'Microsoft Windows 7 Enterprise',
                  'osversion'   => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'SUSE Linux Enterprise Server 12 (x86_64)',
                  'KERNEL_NAME'    => 'linux',
                  'KERNEL_VERSION' => '3.12.43-52.6-default',
                  'NAME'           => 'SuSE',
                  'SERVICE_PACK'   => '0',
                  'VERSION'        => '12'
              ],
              [
                  'arch'        => 'x86_64',
                  'kernname'    => 'linux',
                  'kernversion' => '3.12.43-52.6-default',
                  'os'          => 'SuSE',
                  'osversion'   => '12',
                  'servicepack' => '',
                  'edition'     => 'Enterprise Server'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.12.43-52.6-default',
                  'os'          => 'SUSE Linux Enterprise Server 12 (x86_64)',
                  'osversion'   => '12',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Debian GNU/Linux 7.8 (wheezy)',
                  'KERNEL_NAME'    => 'linux',
                  'KERNEL_VERSION' => '3.2.0-4-amd64',
                  'NAME'           => 'Debian',
                  'VERSION'        => '7.8'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.2.0-4-amd64',
                  'os'          => 'Debian',
                  'osversion'   => '7.8',
                  'servicepack' => '',
                  'edition'     => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.2.0-4-amd64',
                  'os'          => 'Debian GNU/Linux 7.8 (wheezy)',
                  'osversion'   => '7.8',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'ARCH'           => 'x86_64',
                  'FULL_NAME'      => 'Debian GNU/Linux 8.4 (jessie)',
                  'KERNEL_NAME'    => 'linux',
                  'KERNEL_VERSION' => '3.16.0-4-amd64',
                  'NAME'           => 'Debian',
                  'VERSION'        => '8.4'
              ],
              [
                  'arch'        => 'x86_64',
                  'kernname'    => 'linux',
                  'kernversion' => '3.16.0-4-amd64',
                  'os'          => 'Debian',
                  'osversion'   => '8.4',
                  'servicepack' => '',
                  'edition'     => ''
              ],
              [
                  'arch'        => 'x86_64',
                  'kernname'    => 'linux',
                  'kernversion' => '3.16.0-4-amd64',
                  'os'          => 'Debian GNU/Linux 8.4 (jessie)',
                  'osversion'   => '8.4',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          /*[
              [
                  'ARCH'           => '32-bit',
                  'FULL_NAME'      => 'Microsoft Windows Embedded Standard',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.1.7601',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 1'
              ],
              [
                  'arch'        => '32-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7601',
                  'os'          => 'Windows',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => 'Embedded Standard'
              ],
              [
                  'arch'        => '32-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7601',
                  'os'          => 'Microsoft Windows Embedded Standard',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => ''
              ]
          ]/*, //5
          [
              [
                  'FULL_NAME'      => 'MicrosoftÂ® Windows ServerÂ® 2008 Standard',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.0.6002',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 2'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.0.6002',
                  'os'          => 'Windows',
                  'osversion'   => '2008',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => 'ServerÂ® Standard'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.0.6002',
                  'os'          => 'MicrosoftÂ® Windows ServerÂ® 2008 Standard',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft(R) Windows(R) Server 2003, Standard Edition',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '5.2.3790',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 2'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.2.3790',
                  'os'          => 'Windows',
                  'osversion'   => '2003',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => 'Server Standard Edition'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.2.3790',
                  'os'          => 'Microsoft(R) Windows(R) Server 2003, Standard Edition',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => ''
              ]
          ],*/
          [
              [
                  'FULL_NAME'      => 'Microsoft Windows Server 2012 R2 Datacenter',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.3.9600',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Windows',
                  'osversion'   => '2012 R2',
                  'servicepack' => '',
                  'edition'     => 'Server Datacenter'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Microsoft Windows Server 2012 R2 Datacenter',
                  'osversion'   => '',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'ARCH'           => '64-bit',
                  'FULL_NAME'      => 'Microsoft Windows Server 2008 R2 Datacenter',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.1.7601',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 1'
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7601',
                  'os'          => 'Windows',
                  'osversion'   => '2008 R2',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => 'Server Datacenter'
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.1.7601',
                  'os'          => 'Microsoft Windows Server 2008 R2 Datacenter',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft® Windows Vista™ Professionnel',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.0.6001',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 1'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.0.6001',
                  'os'          => 'Windows',
                  'osversion'   => 'Vista',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => 'Professionnel'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.0.6001',
                  'os'          => 'Microsoft® Windows Vista™ Professionnel',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 1',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft(R) Windows(R) Server 2003, Standard Edition x64',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '5.2.3790',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 2'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.2.3790',
                  'os'          => 'Windows',
                  'osversion'   => '2003',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => 'Server Standard Edition x64'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.2.3790',
                  'os'          => 'Microsoft(R) Windows(R) Server 2003, Standard Edition x64',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 2',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft Windows XP Édition familiale',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '5.1.2600',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 3'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.1.2600',
                  'os'          => 'Windows',
                  'osversion'   => 'XP',
                  'servicepack' => 'Service Pack 3',
                  'edition'     => 'Édition familiale'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.1.2600',
                  'os'          => 'Microsoft Windows XP Édition familiale',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 3',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft Windows 2000 Professionnel',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '5.0.2195',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => 'Service Pack 4'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.0.2195',
                  'os'          => 'Windows',
                  'osversion'   => '2000',
                  'servicepack' => 'Service Pack 4',
                  'edition'     => 'Professionnel'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '5.0.2195',
                  'os'          => 'Microsoft Windows 2000 Professionnel',
                  'osversion'   => '',
                  'servicepack' => 'Service Pack 4',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Microsoft Hyper-V Server 2012 R2',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.3.9600',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Windows',
                  'osversion'   => '2012 R2',
                  'servicepack' => '',
                  'edition'     => 'Hyper-V Server'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Microsoft Hyper-V Server 2012 R2',
                  'osversion'   => '',
                  'servicepack' => '',
                  'edition'     => ''
              ]

          ],
          [
              [
                  'ARCH'           => 'x86_64',
                  'FULL_NAME'      => 'Fedora release 23 (Twenty Three)',
                  'KERNEL_NAME'    => 'linux',
                  'KERNEL_VERSION' => '4.4.6-301.fc23.x86_64',
                  'NAME'           => 'Fedora',
                  'VERSION'        => '23'
              ],
              [
                  'arch'        => 'x86_64',
                  'kernname'    => 'linux',
                  'kernversion' => '4.4.6-301.fc23.x86_64',
                  'os'          => 'Fedora',
                  'osversion'   => '23',
                  'servicepack' => '',
                  'edition'     => ''
              ],
              [
                  'arch'        => 'x86_64',
                  'kernname'    => 'linux',
                  'kernversion' => '4.4.6-301.fc23.x86_64',
                  'os'          => 'Fedora release 23 (Twenty Three)',
                  'osversion'   => '23',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'ThinPro 5.2.0',
                  'KERNEL_NAME'    => 'linux',
                  'KERNEL_VERSION' => '3.8.13-hp',
                  'NAME'           => 'ThinPro',
                  'VERSION'        => '5.2.0'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.8.13-hp',
                  'os'          => 'ThinPro',
                  'osversion'   => '5.2.0',
                  'servicepack' => '',
                  'edition'     => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.8.13-hp',
                  'os'          => 'ThinPro 5.2.0',
                  'osversion'   => '5.2.0',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'ARCH'           => '64-bit',
                  'FULL_NAME'      => 'Microsoft Windows 10 Professionnel',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '10.0.10586',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => ''
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '10.0.10586',
                  'os'          => 'Windows',
                  'osversion'   => '10',
                  'servicepack' => '',
                  'edition'     => 'Professionnel'
              ],
              [
                  'arch'        => '64-bit',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '10.0.10586',
                  'os'          => 'Microsoft Windows 10 Professionnel',
                  'osversion'   => '',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'   => 'Debian GNU/Linux 7.4 (wheezy)',
                  'VERSION'     => '3.2.0-2-amd64',
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'linux',
                  'kernversion' => '3.2.0-2-amd64',
                  'os'          => 'Debian',
                  'osversion'   => '7.4 (wheezy)',
                  'servicepack' => '',
                  'edition'     => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => '',
                  'kernversion' => '',
                  'os'          => 'Debian GNU/Linux 7.4 (wheezy)',
                  'osversion'   => '3.2.0-2-amd64',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Майкрософт Windows 8.1 Профессиональная',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '6.3.9600',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Windows',
                  'osversion'   => '8.1',
                  'servicepack' => '',
                  'edition'     => 'Профессиональная'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '6.3.9600',
                  'os'          => 'Майкрософт Windows 8.1 Профессиональная',
                  'osversion'   => '',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ],
          [
              [
                  'FULL_NAME'      => 'Майкрософт Windows 10 Pro',
                  'KERNEL_NAME'    => 'MSWin32',
                  'KERNEL_VERSION' => '10.0.10586',
                  'NAME'           => 'Windows',
                  'SERVICE_PACK'   => ''
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '10.0.10586',
                  'os'          => 'Windows',
                  'osversion'   => '10',
                  'servicepack' => '',
                  'edition'     => 'Pro'
              ],
              [
                  'arch'        => '',
                  'kernname'    => 'MSWin32',
                  'kernversion' => '10.0.10586',
                  'os'          => 'Майкрософт Windows 10 Pro',
                  'osversion'   => '',
                  'servicepack' => '',
                  'edition'     => ''
              ]
          ]
      ];

      $mapping = [
          'arch'        => 'operatingsystemarchitectures_id',
          'kernname'    => 'operatingsystemkernels_id',
          'kernversion' => 'operatingsystemkernelversions_id',
          'os'          => 'operatingsystems_id',
          'osversion'   => 'operatingsystemversions_id',
          'servicepack' => 'operatingsystemservicepacks_id'
      ];

      $result = [];
      foreach ($data as $row) {
         $standard = [];
         foreach ($row[2] as $name => $value) {
            if (isset($mapping[$name]) && (!empty($value) || $name == 'edition')) {
               $standard[$mapping[$name]] = $value;
            }
         }

         $result[] = [
            'nodes'  => $row[0],
            'expected' => $standard
         ];
      }
      return $result;
   }


   public function testHandle() {
      $computer = getItemByTypeName('Computer', '_test_pc01');

      //first, check there are no controller linked to this computer
      $ios = new \Item_OperatingSystem();
      $this->boolean($ios->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('An operating system is already linked to computer!');

      //convert data
      $expected = $this->assetProvider()[0];

      $converter = new \Glpi\Inventory\Converter;
      $data = $converter->convert($this->buildXml($expected['nodes']));
      $json = json_decode($data);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $asset = new \Glpi\Inventory\Asset\OperatingSystem($computer, (array)$json->content->operatingsystem);
      $asset->setExtraData((array)$json->content);
      $result = $asset->prepare();
      $this->object($result[0])->isEqualTo(json_decode($expected['expected']));

      //handle
      $asset->handleLinks();
      $asset->handle();
      $this->boolean($ios->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('Operating system has not been linked to computer :(');
   }
}
