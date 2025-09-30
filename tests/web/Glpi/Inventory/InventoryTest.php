<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory;

use GuzzleHttp;

class InventoryTest extends \GLPITestCase
{
    private $http_client;
    private $base_uri;

    public function setUp(): void
    {
        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim(GLPI_URI, '/') . '/';

        parent::setUp();
    }

    public function testInventoryRequest()
    {
        $res = $this->http_client->request(
            'POST',
            $this->base_uri . 'Inventory',
            [
                'headers' => [
                    'Content-Type' => 'application/xml',
                ],
                'body'   => '<?xml version="1.0" encoding="UTF-8" ?>'
                  . "<REQUEST>
                  <CONTENT>
                     <BIOS>
                        <ASSETTAG />  <BDATE>06/02/2016</BDATE>
                        <BMANUFACTURER>Dell Inc.</BMANUFACTURER>
                        <BVERSION>1.4.3</BVERSION>
                        <MMANUFACTURER>Dell Inc.</MMANUFACTURER>
                        <MMODEL>07TYC2</MMODEL>
                        <MSN>/640HP72/CN129636460078/</MSN>
                        <SKUNUMBER>0704</SKUNUMBER>
                        <SMANUFACTURER>Dell Inc.</SMANUFACTURER>
                        <SMODEL>XPS 13 9350</SMODEL>
                        <SSN>640HP72</SSN>
                     </BIOS>
                     <HARDWARE>
                        <CHASSIS_TYPE>Laptop</CHASSIS_TYPE>
                        <CHECKSUM>131071</CHECKSUM>
                        <DATELASTLOGGEDUSER>Wed Oct 3 06:56</DATELASTLOGGEDUSER>
                        <DEFAULTGATEWAY>192.168.1.1</DEFAULTGATEWAY>
                        <DNS>192.168.1.1/172.28.200.20</DNS>
                        <ETIME>3</ETIME>
                        <IPADDR>192.168.1.119/192.168.122.1/192.168.11.47</IPADDR>
                        <LASTLOGGEDUSER>trasher</LASTLOGGEDUSER>
                        <MEMORY>7822</MEMORY>
                        <NAME>glpixps</NAME>
                        <OSCOMMENTS>#1 SMP Thu Sep 20 02:43:23 UTC 2018</OSCOMMENTS>
                        <OSNAME>Fedora 28 (Workstation Edition)</OSNAME>
                        <OSVERSION>4.18.9-200.fc28.x86_64</OSVERSION>
                        <PROCESSORN>1</PROCESSORN>
                        <PROCESSORS>2300</PROCESSORS>
                        <PROCESSORT>Intel(R) Core(TM) i5-6200U CPU @ 2.30GHz</PROCESSORT>
                        <SWAP>7951</SWAP>
                        <USERID>trasher</USERID>
                        <UUID>4c4c4544-0034-3010-8048-b6c04f503732</UUID>
                        <VMSYSTEM>Physical</VMSYSTEM>
                        <WORKGROUP>teclib.infra</WORKGROUP>
                     </HARDWARE>
                     <VERSIONCLIENT>FusionInventory-Agent_v2.5.1-1.fc30</VERSIONCLIENT>
                     <VERSIONPROVIDER>
                        <COMMENTS>Platform  : linux buildvm-armv7-18.arm.fedoraproject.org 4.18.19-100.fc27.armv7hllpae 1 smp wed nov 14 21:55:54 utc 2018 armv7l armv7l armv7l gnulinux </COMMENTS>
                        <COMMENTS>Build date: Mon Jul  8 12:36:27 2019 GMT</COMMENTS>
                        <NAME>FusionInventory</NAME>
                        <PERL_ARGS>--debug --debug --logger=stderr --no-category=software,process,local_user,local_group,controller,environment</PERL_ARGS>
                        <PERL_CONFIG>gccversion: 9.2.1 20190827 (Red Hat 9.2.1-1)</PERL_CONFIG>
                        <PERL_CONFIG>defines: use64bitall use64bitint usedl usedtrace useithreads uselanginfo uselargefiles usemallocwrap usemultiplicity usemymalloc=n usenm=false useopcode useperlio useposix useshrplib usesitecustomize usethreads usevendorprefix usevfork=false</PERL_CONFIG>
                        <PERL_EXE>/usr/bin/perl</PERL_EXE>
                        <PERL_INC>/usr/share/fusioninventory/lib:/usr/local/lib64/perl5:/usr/local/share/perl5:/usr/lib64/perl5/vendor_perl:/usr/share/perl5/vendor_perl:/usr/lib64/perl5:/usr/share/perl5</PERL_INC>
                        <PERL_MODULE>LWP @ 6.39</PERL_MODULE>
                        <PERL_MODULE>LWP::Protocol @ 6.39</PERL_MODULE>
                        <PERL_MODULE>IO::Socket @ 1.39</PERL_MODULE>
                        <PERL_MODULE>IO::Socket::SSL @ 2.066</PERL_MODULE>
                        <PERL_MODULE>IO::Socket::INET @ 1.39</PERL_MODULE>
                        <PERL_MODULE>Net::SSLeay @ 1.85</PERL_MODULE>
                        <PERL_MODULE>Net::SSLeay uses OpenSSL 1.1.1d FIPS  10 Sep 2019</PERL_MODULE>
                        <PERL_MODULE>Net::HTTPS @ 6.19</PERL_MODULE>
                        <PERL_MODULE>HTTP::Status @ 6.18</PERL_MODULE>
                        <PERL_MODULE>HTTP::Response @ 6.18</PERL_MODULE>
                        <PERL_VERSION>v5.28.2</PERL_VERSION>
                        <PROGRAM>/usr/bin/fusioninventory-agent</PROGRAM>
                        <VERSION>2.5.1-1.fc30</VERSION>
                     </VERSIONPROVIDER>
                  </CONTENT>
                  <DEVICEID>computer-2018-07-09-09-07-13</DEVICEID>
                  <QUERY>INVENTORY</QUERY>
                  </REQUEST>",
            ]
        );
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<REPLY><RESPONSE>SEND</RESPONSE></REPLY>",
            (string) $res->getBody()
        );
        $this->assertSame(
            'application/xml',
            $res->getHeader('content-type')[0]
        );

        //check agent in database
        $agent = new \Agent();
        $this->assertTrue($agent->getFromDBByCrit(['deviceid' => 'computer-2018-07-09-09-07-13']));

        $expected = [
            'deviceid'        => 'computer-2018-07-09-09-07-13',
            'version'         => '2.5.1-1.fc30',
            'agenttypes_id'   => 1,
            'locked'          => 0,
            'itemtype'        => 'Computer',
            'items_id'        => 0,
        ];

        foreach ($expected as $key => $value) {
            if ($key === 'items_id') {
                //FIXME: retrieve created items_id
                $this->assertGreaterThan(0, (int) $agent->fields[$key]);
            } else {
                $this->assertEquals($value, $agent->fields[$key], "$key differs");
            }
        }
    }
}
