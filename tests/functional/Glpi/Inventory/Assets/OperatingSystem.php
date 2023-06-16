<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory\Asset;

use Rule;
use RuleDictionnaryOperatingSystem;
use RuleDictionnaryOperatingSystemEdition;
use RuleDictionnaryOperatingSystemVersion;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/operatingsystem.class.php */

class OperatingSystem extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
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
                    'SERVICE_PACK'   => '',
                    'INSTALL_DATE'   => '2022-01-01 10:35:07'
                ],
                'expected'  => [
                    'operatingsystemarchitectures_id'   => '64-bit',
                    'operatingsystemkernels_id'         => 'MSWin32',
                    'operatingsystemkernelversions_id'  => '6.1.7600',
                    'operatingsystems_id'               => 'Microsoft Windows 7 Enterprise',
                    'install_date'                      => '2022-01-01',
                ]
            ]
        ] + $this->fusionProvider();
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($nodes, $expected)
    {
        $xml = $this->buildXml($nodes);

        $this->login();
        $converter = new \Glpi\Inventory\Converter();
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

    protected function assetCleanOsProvider(): array
    {
        $os_input = [
            "fedora_01" => [
                "full_name" => "Fedora 28 (Workstation Edition)",
                "name" => "Fedora",
                "version" => "28",
                "edition" => "Workstation Edition"
            ],
            "fedora_02" => [
                "full_name" => "Fedora release 25 (Twenty Five)",
                "name" => "Fedora",
                "version" => "25",
                "edition" => "Twenty Five"
            ],
            "ubuntu_01" => [
                "full_name" => "Ubuntu 16.04.5 LTS",
                "name" => "Ubuntu",
                "version" => "16.04.5",
                "edition" => "LTS"
            ],
            "ubuntu_02" => [
                "full_name" => "Ubuntu 16.10",
                "name" => "Ubuntu",
                "version" => "16.10",
                "edition" => ""
            ],
            "ubuntu_03" => [
                "full_name" => "Ubuntu 16.10 LTS",
                "name" => "Ubuntu",
                "version" => "16.10",
                "edition" => "LTS"
            ],
            "redhat_01" => [
                "full_name" => "Red Hat Enterprise Linux Server release 7.9 (Maipo)",
                "name" => "Red Hat",
                "version" => "7.9",
                "edition" => "Maipo"
            ],
            "redhat_02" => [
                "full_name" => "Red Hat Enterprise Linux 8.4 (Ootpa)",
                "name" => "Red Hat",
                "version" => "8.4",
                "edition" => "Ootpa"
            ],
            "redhat_03" => [
                "full_name" => "Red Hat Enterprise Linux Server",
                "name" => "Red Hat",
                "version" => "",
                "edition" => ""
            ],
            "oracle_01" => [
                "full_name" => "Oracle Linux Server release 7.3",
                "name" => "Oracle",
                "version" => "7.3",
                "edition" => ""
            ],
            "oracle_01" => [
                "full_name" => "Oracle Linux Server release 7.3",
                "name" => "Oracle",
                "version" => "7.3",
                "edition" => ""
            ],
            "debian_01" => [
                "full_name" => "Debian GNU/Linux 9.4 (stretch)",
                "name" => "Debian",
                "version" => "9.4",
                "edition" => "stretch"
            ],
            "debian_02" => [
                "full_name" => "Debian GNU/Linux 8.5",
                "name" => "Debian",
                "version" => "8.5",
                "edition" => ""
            ],
            "debian_03" => [
                "full_name" => "Debian GNU/Linux 7.11 (wheezy)",
                "name" => "Debian",
                "version" => "7.11",
                "edition" => "wheezy"
            ],
            "centos_01" => [
                "full_name" => "CentOS release 6.6 (Final)",
                "name" => "CentOS",
                "version" => "6.6",
                "edition" => "Final"
            ],
            "centos_02" => [
                "full_name" => "CentOS release 6.10 (Final)",
                "name" => "CentOS",
                "version" => "6.10",
                "edition" => "Final"
            ],
            "centos_03" => [
                "full_name" => "CentOS Linux release 7.7.1908 (Core)",
                "name" => "CentOS",
                "version" => "7.7.1908",
                "edition" => "Core"
            ],
            "centos_04" => [
                "full_name" => "CentOS Linux 8",
                "name" => "CentOS",
                "version" => "8",
                "edition" => ""
            ],
            "centos_05" => [
                "full_name" => "CentOS Linux 8 (Core)",
                "name" => "CentOS",
                "version" => "8",
                "edition" => "Core"
            ],
            "alma_01" => [
                "full_name" => "AlmaLinux 9.0 (Emerald Puma)",
                "name" => "AlmaLinux",
                "version" => "9.0",
                "edition" => "Emerald Puma"
            ],
            "alma_02" => [
                "full_name" => "AlmaLinux 8.5 (Arctic Sphynx)",
                "name" => "AlmaLinux",
                "version" => "8.5",
                "edition" => "Arctic Sphynx"
            ],
            "windows_01" => [
                "full_name" => "Microsoft Windows XP Professionnel",
                "name" => "Windows",
                "version" => "XP",
                "edition" => "Professionnel"
            ],
            "windows_02" => [
                "full_name" => "Microsoft® Windows Vista™ Professionnel",
                "name" => "Windows",
                "version" => "Vista",
                "edition" => "Professionnel"
            ],
            "windows_03" => [
                "full_name" => "Microsoft Windows 2000 Professionnel",
                "name" => "Windows",
                "version" => "2000",
                "edition" => "Professionnel"
            ],
            "windows_04" => [
                "full_name" => "Microsoft Windows 11 Professionnel",
                "name" => "Windows",
                "version" => "11",
                "edition" => "Professionnel"
            ],
            "windows_05" => [
                "full_name" => "Microsoft Windows 10 Entreprise",
                "name" => "Windows",
                "version" => "10",
                "edition" => "Entreprise"
            ],
            "windows_server_01" => [
                "full_name" => "Microsoft Windows Server 2012 R2 Datacenter",
                "name" => "Windows Server",
                "version" => "2012 R2",
                "edition" => "Datacenter"
            ],
            "windows_server_02" => [
                "full_name" => "Microsoft(R) Windows(R) Server 2003, Standard Edition x64",
                "name" => "Windows Server",
                "version" => "2003",
                "edition" => "Standard"
            ],
            "windows_server_03" => [
                "full_name" => "Microsoft Windows Server 2016 Standard",
                "name" => "Windows Server",
                "version" => "2016",
                "edition" => "Standard"
            ],
            "windows_server_04" => [
                "full_name" => "Microsoft Hyper-V Server 2012 R2",
                "name" => "Hyper-V Server",
                "version" => "2012 R2",
                "edition" => ""
            ],
            "windows_server_05" => [
                "full_name" => "Microsoft(R) Windows(R) Server 2003, Standard Edition",
                "name" => "Windows Server",
                "version" => "2003",
                "edition" => "Standard"
            ],
            "windows_server_06" => [
                "full_name" => "Microsoft® Windows Server® 2008 Standard",
                "name" => "Windows Server",
                "version" => "2008",
                "edition" => "Standard"
            ]
        ];

        $data = [];
        foreach ($os_input as $value) {
            $data[] = [
                'nodes'  => [
                    'ARCH'            => 'x86_64',
                    'BOOT_TIME'       => '2018-10-02 08:56:09',
                    'DNS_DOMAIN'      => 'teclib.infra',
                    'FQDN'            => 'glpixps.teclib.infra',
                    'FULL_NAME'       => $value['full_name'],
                    'HOSTID'          => 'a8c07701',
                    'KERNEL_NAME'     => 'linux',
                    'KERNEL_VERSION'  => '4.18.9-200.fc28.x86_64',
                    'NAME'            => $value['name'],
                    'VERSION'         => $value['version'],
                ],
                'expected'  => '{
                    "arch": "x86_64",
                    "boot_time": "2018-10-02 08:56:09",
                    "dns_domain": "teclib.infra",
                    "fqdn": "glpixps.teclib.infra",
                    "full_name": "' . $value['full_name'] . '",
                    "hostid": "a8c07701",
                    "kernel_name": "linux",
                    "kernel_version": "4.18.9-200.fc28.x86_64",
                    "name": "' . $value['name'] . '",
                    "version": "' . $value['version'] . '",
                    "timezone": {
                        "name": "CEST",
                        "offset": "+0200"
                    },
                    "operatingsystems_id": "' . $value['name'] . '",
                    "operatingsystemversions_id": "' . $value['version'] . '",
                    "operatingsystemarchitectures_id": "x86_64",
                    "operatingsystemkernels_id": "linux",
                    "operatingsystemkernelversions_id": "4.18.9-200.fc28.x86_64",
                    "operatingsystemeditions_id": "' . $value['edition'] . '"
                    }'
            ];
        }
        return $data  + $this->fusionProvider();
    }

    /**
     * @dataProvider assetCleanOsProvider
     */
    public function testPrepareWithCleanOS($nodes, $expected)
    {
        global $DB;
        //enable rule to clean OS
        $DB->update(
            Rule::getTable(),
            [
                'is_active' => 1,
            ],
            [
                "sub_type" =>
                [
                    RuleDictionnaryOperatingSystem::class,
                    RuleDictionnaryOperatingSystemEdition::class,
                    RuleDictionnaryOperatingSystemVersion::class,
                ]
            ]
        );

        $xml = $this->buildXml($nodes);

        $this->login();
        $converter = new \Glpi\Inventory\Converter();
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

        //replace missing properties by empty string
        //see : assetCleanOsProvider -> redhat_03 example
        if (!property_exists($result[0], 'version')) {
            $result[0]->version = '';
        }
        if (!property_exists($result[0], 'operatingsystemversions_id')) {
            $result[0]->operatingsystemversions_id = '';
        }
        if (!property_exists($result[0], 'operatingsystemeditions_id')) {
            $result[0]->operatingsystemeditions_id = '';
        }

        $this->object($result[0])->isEqualTo($object);
    }

    private function buildXml($nodes)
    {
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

    protected function fusionProvider(): array
    {
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

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no controller linked to this computer
        $ios = new \Item_OperatingSystem();
        $this->boolean($ios->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('An operating system is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
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

    public function testInventoryUpdate()
    {
        $computer = new \Computer();
        $os = new \OperatingSystem();
        $cos = new \Item_OperatingSystem();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2018-10-02 08:56:09</BOOT_TIME>
      <FQDN>test-pc002</FQDN>
      <FULL_NAME>Fedora 28 (Workstation Edition)</FULL_NAME>
      <HOSTID>a8c07701</HOSTID>
      <INSTALL_DATE>2022-01-01 10:35:07</INSTALL_DATE>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>4.18.9-200.fc28.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <TIMEZONE>
        <NAME>CEST</NAME>
        <OFFSET>+0200</OFFSET>
      </TIMEZONE>
      <VERSION>28 (Workstation Edition)</VERSION>
    </OPERATINGSYSTEM>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

       //create manually a computer, with an operating system
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

        $os_id = $os->add([
            'name' => 'Fedora 28 (Workstation Edition)'
        ]);
        $this->integer($os_id)->isGreaterThan(0);

        $cos_id = $cos->add([
            'itemtype' => 'Computer',
            'items_id' => $computers_id,
            'operatingsystems_id' => $os_id
        ]);
        $this->integer($cos_id)->isGreaterThan(0);

        $this->doInventory($xml_source, true);

        $list = $os->find();
        $this->integer(count($list))->isIdenticalTo(1);

       //check that OS is linked to computer, and is now dynamic
        $list = $cos->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($list))->isIdenticalTo(1);
        $theos = current($list);
        $this->integer($theos['operatingsystems_id'])->isIdenticalTo($os_id);
        $this->integer($theos['is_dynamic'])->isIdenticalTo(1);
        $this->string($theos['install_date'])->isIdenticalTo("2022-01-01");


       //Redo inventory, but with updated operating system
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2020-12-21 07:58:42</BOOT_TIME>
      <DNS_DOMAIN></DNS_DOMAIN>
      <FQDN>test-pc002</FQDN>
      <FULL_NAME>Fedora 32 (Workstation Edition)</FULL_NAME>
      <HOSTID>a8c06c01</HOSTID>
      <INSTALL_DATE>2022-10-14 10:35:07</INSTALL_DATE>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>5.9.13-100.fc32.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <TIMEZONE>
        <NAME>CET</NAME>
        <OFFSET>+0100</OFFSET>
      </TIMEZONE>
      <VERSION>32 (Workstation Edition)</VERSION>
    </OPERATINGSYSTEM>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

       //We now have 2 operating systems
        $list = $os->find();
        $this->integer(count($list))->isIdenticalTo(2);

        //but still only one linked to computer
        $list = $cos->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($list))->isIdenticalTo(1);
        $theos = current($list);
        $this->integer($theos['operatingsystems_id'])->isNotIdenticalTo($os_id, 'Operating system link has not been updated');
        $this->integer($theos['is_dynamic'])->isIdenticalTo(1);
        $this->string($theos['install_date'])->isIdenticalTo("2022-10-14");
    }

    public function testReplayRuleOnOS()
    {
        $os = new \OperatingSystem();
        $cos = new \Item_OperatingSystem();
        global $DB;

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2018-10-02 08:56:09</BOOT_TIME>
      <FQDN>test-pc002</FQDN>
      <FULL_NAME>Fedora 28 (Workstation Edition)</FULL_NAME>
      <HOSTID>a8c07701</HOSTID>
      <INSTALL_DATE>2022-01-01 10:35:07</INSTALL_DATE>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>4.18.9-200.fc28.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <TIMEZONE>
        <NAME>CEST</NAME>
        <OFFSET>+0200</OFFSET>
      </TIMEZONE>
      <VERSION>28 (Workstation Edition)</VERSION>
    </OPERATINGSYSTEM>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('test-pc002')
            ->string['itemtype']->isIdenticalTo('Computer');


        //check created computer
        $computers_id = $agent['items_id'];
        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();

        //check found OS
        $list = $os->find();
        $this->integer(count($list))->isIdenticalTo(1);

        //check found item_OS
        $list = $cos->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($list))->isIdenticalTo(1);
        $theos1 = current($list);

        $os->getFromDBByCrit([ 'name' => 'Fedora 28 (Workstation Edition)']);
        $this->integer($os->fields['id'])->isGreaterThan(0);

        //check item_OS data
        $this->integer($theos1['operatingsystems_id'])->isIdenticalTo($os->fields['id']);
        $this->integer($theos1['is_dynamic'])->isIdenticalTo(1);
        $this->string($theos1['install_date'])->isIdenticalTo("2022-01-01");

        //enable rule to clean OS
        $this->boolean(
            $DB->update(
                Rule::getTable(),
                [
                    'is_active' => 1,
                ],
                [
                    "sub_type" =>
                    [
                        RuleDictionnaryOperatingSystem::class,
                    ]
                ]
            )
        )->isTrue();

        //replay rule
        $this->login('glpi', 'glpi');

        $os_dictionnary = new \RuleDictionnaryOperatingSystemCollection();
        $this->output(
            function () use ($os_dictionnary) {
                $os_dictionnary->replayRulesOnExistingDB(0, 0, [], []);
            }
        )->contains("Replay rules on existing database started on");


        $list = $cos->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($list))->isIdenticalTo(1);
        $theos2 = current($list);
        //same Item_OperatingSystem before and after
        $this->integer($theos2['id'])->isIdenticalTo($theos1['id']);

        //load new OS name cleaned by dictionnary
        $os->getFromDBByCrit([ 'name' => 'Fedora']);
        $this->integer($os->fields['id'])->isGreaterThan(0);

        //check item_OS data
        $this->integer($theos2['operatingsystems_id'])->isIdenticalTo($os->fields['id']);
        $this->integer($theos2['is_dynamic'])->isIdenticalTo(1);
        $this->string($theos2['install_date'])->isIdenticalTo("2022-01-01");

        //no lock
        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLockedValues($computer->getType(), $computers_id))->isEmpty();
    }
}
