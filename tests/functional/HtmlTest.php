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

namespace tests\units;

use Glpi\Toolbox\FrontEnd;
use GlpiPlugin\Tester\MyPsr4Class;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

/* Test for inc/html.class.php */

class HtmlTest extends \DbTestCase
{
    public function testConvDate()
    {
        $this->assertNull(\Html::convDate(null));
        $this->assertNull(\Html::convDate('NULL'));
        $this->assertNull(\Html::convDate(''));
        $this->assertNull(\Html::convDate('0000-00-00'));
        $this->assertNull(\Html::convDate('0000-00-00 00:00:00'));

        $mydate = date('Y-m-d H:i:s');

        $expected = date('Y-m-d');
        unset($_SESSION['glpidate_format']);
        $this->assertSame($expected, \Html::convDate($mydate));
        $_SESSION['glpidate_format'] = 0;
        $this->assertSame($expected, \Html::convDate($mydate));

        $this->assertSame($expected, \Html::convDate(date('Y-m-d')));

        $expected = date('d-m-Y');
        $this->assertSame($expected, \Html::convDate($mydate, 1));

        $expected = date('m-d-Y');
        $this->assertSame($expected, \Html::convDate($mydate, 2));

        // Check type casting when session property is not an int
        $_SESSION['glpidate_format'] = '1';
        $this->assertSame(date('d-m-Y'), \Html::convDate($mydate, 1));
        $_SESSION['glpidate_format'] = '2';
        $this->assertSame(date('m-d-Y'), \Html::convDate($mydate, 2));

        $expected_error = 'Failed to parse time string (not a date) at position 0 (n): The timezone could not be found in the database';
        $this->assertSame('not a date', \Html::convDate('not a date', 2));
        $this->hasPhpLogRecordThatContains($expected_error, LogLevel::ERROR);
    }

    public function testConvDateTime()
    {
        $this->assertNull(\Html::convDateTime(null));
        $this->assertNull(\Html::convDateTime('NULL'));

        $timestamp = time();

        $mydate = date('Y-m-d H:i:s', $timestamp);

        $expected = date('Y-m-d H:i', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate));

        $expected = date('Y-m-d H:i:s', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate, null, true));

        $expected = date('d-m-Y H:i', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate, 1));

        $expected = date('d-m-Y H:i:s', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate, 1, true));

        $expected = date('m-d-Y H:i', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate, 2));

        $expected = date('m-d-Y H:i:s', $timestamp);
        $this->assertSame($expected, \Html::convDateTime($mydate, 2, true));
    }

    public function testCleanInputText()
    {
        $origin = 'This is a \'string\' with some "replacements" needed, but not « others »!';
        $expected = 'This is a &#039;string&#039; with some &quot;replacements&quot; needed, but not « others »!';
        $this->assertSame($expected, @\Html::cleanInputText($origin));
    }

    public function cleanParametersURL()
    {
        $url = 'http://host/glpi/path/to/file.php?var1=2&var2=3';
        $expected = 'http://host/glpi/path/to/file.php';
        $this->assertSame($expected, \Html::cleanParametersURL($url));
    }

    public function testResume_text()
    {
        $origin = 'This is a very long string which will be truncated by a dedicated method. '
         . 'If the string is not truncated, well... We\'re wrong and got a very serious issue in our codebase!'
         . 'And if the string has been correctly truncated, well... All is ok then, let\'s show if all the other tests are OK :)';
        $expected = 'This is a very long string which will be truncated by a dedicated method. '
         . 'If the string is not truncated, well... We&#039;re wrong and got a very serious issue in our codebase!'
         . 'And if the string has been correctly truncated, well... All is ok then, let&#039;s show i&nbsp;(...)';
        $this->assertSame($expected, \Html::resume_text($origin));

        $origin = 'A string that is longer than 10 characters.';
        $expected = 'A string t&nbsp;(...)';
        $this->assertSame($expected, \Html::resume_text($origin, 10));

        $origin = 'A string that contains HTML special chars like >, < and &, and some text that should be truncated.';
        $expected = 'A string that contains HTML special chars like &gt;, &lt; and &amp;, a&nbsp;(...)';
        $this->assertSame($expected, \Html::resume_text($origin, 60));

        $origin = 'A string that contains HTML special chars like >, < and &, and some text that should NOT be truncated.';
        $expected = 'A string that contains HTML special chars like &gt;, &lt; and &amp;, and some text that should NOT be truncated.';
        $this->assertSame($expected, \Html::resume_text($origin, 1500));
    }

    public function testFormatNumber()
    {
        $_SESSION['glpinumber_format'] = 0;
        $origin = '';
        $expected = '0.00';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $origin = '1207.3';

        $expected = '1 207.30';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $expected = '1207.30';
        $this->assertSame($expected, \Html::formatNumber($origin, true));

        $origin = 124556.693;
        $expected = '124 556.69';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $origin = 120.123456789;

        $expected = '120.12';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $expected = '120.12346';
        $this->assertSame($expected, \Html::formatNumber($origin, false, 5));

        $expected = '120';
        $this->assertSame($expected, \Html::formatNumber($origin, false, 0));

        $origin = 120.999;
        $expected = '121.00';
        $this->assertSame($expected, \Html::formatNumber($origin));
        $expected = '121';
        $this->assertSame($expected, \Html::formatNumber($origin, false, 0));

        $this->assertSame('-', \Html::formatNumber('-'));

        $_SESSION['glpinumber_format'] = 2;

        $origin = '1207.3';
        $expected = '1 207,30';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $_SESSION['glpinumber_format'] = 3;

        $origin = '1207.3';
        $expected = '1207.30';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $_SESSION['glpinumber_format'] = 4;

        $origin = '1207.3';
        $expected = '1207,30';
        $this->assertSame($expected, \Html::formatNumber($origin));

        $_SESSION['glpinumber_format'] = 1337;
        $origin = '1207.3';

        $expected = '1,207.30';
        $this->assertSame($expected, \Html::formatNumber($origin));
    }

    public function testTimestampToString()
    {
        $expected = '0 seconds';
        $this->assertSame($expected, \Html::timestampToString(null));
        $this->assertSame($expected, \Html::timestampToString(''));
        $this->assertSame($expected, \Html::timestampToString(0));

        $tstamp = 57226;
        $expected = '15 hours 53 minutes 46 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp));

        $tstamp = -57226;
        $expected = '- 15 hours 53 minutes 46 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp));

        $tstamp = 1337;
        $expected = '22 minutes 17 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp));

        $expected = '22 minutes';
        $this->assertSame($expected, \Html::timestampToString($tstamp, false));

        $tstamp = 54;
        $expected = '54 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp));
        $this->assertSame($expected, \Html::timestampToString($tstamp, false));

        $tstamp = 157226;
        $expected = '1 days 19 hours 40 minutes 26 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp));

        $expected = '1 days 19 hours 40 minutes';
        $this->assertSame($expected, \Html::timestampToString($tstamp, false));

        $expected = '43 hours 40 minutes 26 seconds';
        $this->assertSame($expected, \Html::timestampToString($tstamp, true, false));

        $expected = '43 hours 40 minutes';
        $this->assertSame($expected, \Html::timestampToString($tstamp, false, false));
    }

    public function testGetMenuInfos()
    {
        $menu = \Html::getMenuInfos();
        $this->assertSame(8, count($menu));

        $expected = [
            'assets',
            'helpdesk',
            'management',
            'tools',
            'plugins',
            'admin',
            'config',
            'preference',
        ];
        $this->assertSame($expected, array_keys($menu));

        $expected = [
            'Computer',
            'Monitor',
            'Software',
            'NetworkEquipment',
            'Peripheral',
            'Printer',
            'CartridgeItem',
            'ConsumableItem',
            'Phone',
            'Rack',
            'Enclosure',
            'PDU',
            'PassiveDCEquipment',
            'Unmanaged',
            'Cable',
            'Glpi\CustomAsset\Test01Asset',
            'Glpi\CustomAsset\Test02Asset',
            'Item_DeviceSimcard',
        ];
        $this->assertSame('Assets', $menu['assets']['title']);
        $this->assertSame($expected, $menu['assets']['types']);

        $expected = [
            'Ticket',
            'Glpi\Form\ServiceCatalog\ServiceCatalog',
            'Problem',
            'Change',
            'Planning',
            'Stat',
            'TicketRecurrent',
            'RecurrentChange',
        ];
        $this->assertSame('Assistance', $menu['helpdesk']['title']);
        $this->assertSame($expected, $menu['helpdesk']['types']);

        $expected = [
            'SoftwareLicense',
            'Budget',
            'Supplier',
            'Contact',
            'Contract',
            'Document',
            'Line',
            'Certificate',
            'Datacenter',
            'Cluster',
            'Domain',
            'Appliance',
            'Database',
        ];
        $this->assertSame('Management', $menu['management']['title']);
        $this->assertSame($expected, $menu['management']['types']);

        $expected = [
            'Project',
            'Reminder',
            'RSSFeed',
            'KnowbaseItem',
            'ReservationItem',
            'Report',
            'SavedSearch',
            'Impact',
        ];
        $this->assertSame('Tools', $menu['tools']['title']);
        $this->assertSame($expected, $menu['tools']['types']);

        $expected = [];
        $this->assertSame('Plugins', $menu['plugins']['title']);
        $this->assertSame($expected, $menu['plugins']['types']);

        $expected = [
            'User',
            'Group',
            'Entity',
            'Rule',
            'Profile',
            'QueuedNotification',
            'Glpi\System\Log\LogViewer',
            'Glpi\Inventory\Inventory',
            'Glpi\Form\Form',
        ];
        $this->assertSame('Administration', $menu['admin']['title']);
        $this->assertSame($expected, $menu['admin']['types']);

        $expected = [
            'Glpi\Asset\AssetDefinition',
            'CommonDropdown',
            'CommonDevice',
            'Notification',
            'Webhook',
            'SLM',
            'Config',
            'FieldUnicity',
            'CronTask',
            'Auth',
            'OAuthClient',
            'MailCollector',
            'Link',
            'Plugin',
        ];
        $this->assertSame('Setup', $menu['config']['title']);
        $this->assertSame($expected, $menu['config']['types']);

        $this->assertSame('My settings', $menu['preference']['title']);
        $this->assertArrayNotHasKey('types', $menu['preference']);
        $this->assertSame('/front/preference.php', $menu['preference']['default']);
    }

    public function testGetCopyrightMessage()
    {
        $message = \Html::getCopyrightMessage();
        $this->assertStringContainsString(GLPI_VERSION, $message);
        $this->assertStringContainsString(GLPI_YEAR, $message);

        $message = \Html::getCopyrightMessage(false);
        $this->assertStringNotContainsString(GLPI_VERSION, $message);
        $this->assertStringContainsString(GLPI_YEAR, $message);
    }

    public function testCss()
    {
        global $CFG_GLPI;

        //fake files
        $fake_files = [
            'file.css',
            'file.min.css',
            'other.css',
            'other-min.css',
        ];
        $dir = str_replace(realpath(GLPI_ROOT), '', realpath(GLPI_TMP_DIR));
        $version_key = FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $base_expected = '<link rel="stylesheet" type="text/css" href="'
         . $CFG_GLPI['root_doc'] . $dir . '/%url?v=' . $version_key . '" %attrs>';
        $base_attrs = 'media="all"';

        //create test files
        foreach ($fake_files as $fake_file) {
            $this->assertTrue(touch(GLPI_TMP_DIR . '/' . $fake_file));
        }

        //expect minified file
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.min.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css'));

        //explicitely require not minified file
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css', [], false));

        //activate debug mode: expect not minified file
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css'));
        $_SESSION['glpi_use_mode'] = \Session::NORMAL_MODE;

        //expect original file
        $expected = str_replace(
            ['%url', '%attrs'],
            ['nofile.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/nofile.css'));

        //expect original file
        $expected = str_replace(
            ['%url', '%attrs'],
            ['other.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/other.css'));

        //expect original file
        $expected = str_replace(
            ['%url', '%attrs'],
            ['other-min.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/other-min.css'));

        //expect minified file, print media
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.min.css', 'media="print"'],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css', ['media' => 'print']));

        //expect minified file, screen media
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.min.css', $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css', ['media' => '']));

        //expect minified file and specific version
        $fake_version = '0.0.1';
        $expected = str_replace(
            ['%url', '%attrs', $version_key],
            ['file.min.css', $base_attrs, FrontEnd::getVersionCacheKey($fake_version)],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css', ['version' => $fake_version]));

        //expect minified file with added attributes
        $expected = str_replace(
            ['%url', '%attrs'],
            ['file.min.css', 'attribute="one" ' . $base_attrs],
            $base_expected
        );
        $this->assertSame($expected, \Html::css($dir . '/file.css', ['attribute' => 'one']));

        //remove test files
        foreach ($fake_files as $fake_file) {
            unlink(GLPI_TMP_DIR . '/' . $fake_file);
        }
    }

    public function testScript()
    {
        global $CFG_GLPI;

        //fake files
        $fake_files = [
            'file.js',
            'file.min.js',
            'other.js',
            'other-min.js',
        ];
        $dir = str_replace(realpath(GLPI_ROOT), '', realpath(GLPI_TMP_DIR));
        $version_key = FrontEnd::getVersionCacheKey(GLPI_VERSION);
        $base_expected = '<script type="text/javascript" src="'
         . $CFG_GLPI['root_doc'] . $dir . '/%url?v=' . $version_key . '"></script>';

        //create test files
        foreach ($fake_files as $fake_file) {
            touch(GLPI_TMP_DIR . '/' . $fake_file);
        }

        //expect minified file
        $expected = str_replace(
            '%url',
            'file.min.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/file.js'));

        //explicitely require not minified file
        $expected = str_replace(
            '%url',
            'file.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/file.js', [], false));

        //activate debug mode: expect not minified file
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        $expected = str_replace(
            '%url',
            'file.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/file.js'));
        $_SESSION['glpi_use_mode'] = \Session::NORMAL_MODE;

        //expect original file
        $expected = str_replace(
            '%url',
            'nofile.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/nofile.js'));

        //expect original file
        $expected = str_replace(
            '%url',
            'other.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/other.js'));

        //expect original file
        $expected = str_replace(
            '%url',
            'other-min.js',
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/other-min.js'));

        //expect minified file and specific version
        $fake_version = '0.0.1';
        $expected = str_replace(
            ['%url', $version_key],
            ['file.min.js', FrontEnd::getVersionCacheKey($fake_version)],
            $base_expected
        );
        $this->assertSame($expected, \Html::script($dir . '/file.js', ['version' => $fake_version]));

        //remove test files
        foreach ($fake_files as $fake_file) {
            unlink(GLPI_TMP_DIR . '/' . $fake_file);
        }
    }

    public function testManageRefreshPage()
    {
        //no session refresh, no args => no timer
        if (isset($_SESSION['glpirefresh_views'])) {
            unset($_SESSION['glpirefresh_views']);
        }

        $base_script = \Html::scriptBlock("window.setInterval(function() {
               ##CALLBACK##
            }, ##TIMER##);");

        $expected = '';
        $message = \Html::manageRefreshPage();
        $this->assertSame($expected, $message);

        //Set session refresh to one minute
        $_SESSION['glpirefresh_views'] = 1;
        $expected = str_replace("##CALLBACK##", "window.location.reload()", $base_script);
        $expected = str_replace("##TIMER##", 1 * MINUTE_TIMESTAMP * 1000, $expected);
        $message = \Html::manageRefreshPage();
        $this->assertSame($expected, $message);

        $expected = str_replace("##CALLBACK##", '$(\'#mydiv\').remove();', $base_script);
        $expected = str_replace("##TIMER##", 1 * MINUTE_TIMESTAMP * 1000, $expected);
        $message = \Html::manageRefreshPage(false, '$(\'#mydiv\').remove();');
        $this->assertSame($expected, $message);

        $expected = str_replace("##CALLBACK##", "window.location.reload()", $base_script);
        $expected = str_replace("##TIMER##", 3 * MINUTE_TIMESTAMP * 1000, $expected);
        $message = \Html::manageRefreshPage(3);
        $this->assertSame($expected, $message);

        $expected = str_replace("##CALLBACK##", '$(\'#mydiv\').remove();', $base_script);
        $expected = str_replace("##TIMER##", 3 * MINUTE_TIMESTAMP * 1000, $expected);
        $message = \Html::manageRefreshPage(3, '$(\'#mydiv\').remove();');
        $this->assertSame($expected, $message);
    }

    public function testGenerateMenuSession()
    {
        //login to get session
        $auth = new \Auth();
        $this->assertTrue($auth->login(TU_USER, TU_PASS, true));

        $menu = \Html::generateMenuSession(true);

        $this->assertArrayHasKey('glpimenu', $_SESSION);

        $this->assertSame($menu, $_SESSION['glpimenu']);

        foreach ($menu as $menu_entry) {
            $this->assertArrayHasKey('title', $menu_entry);

            if (isset($menu_entry['content'])) {
                $this->assertArrayHasKey('types', $menu_entry);

                foreach ($menu_entry['content'] as $submenu_label => $submenu) {
                    if ($submenu_label === 'is_multi_entries') {
                        continue;
                    }

                    $this->assertArrayHasKey('title', $submenu);
                    $this->assertArrayHasKey('page', $submenu);
                }
            }
        }
    }

    public function testFuzzySearch()
    {
        //login to get session
        $auth = new \Auth();
        $this->assertTrue($auth->login(TU_USER, TU_PASS, true));

        // init menu
        \Html::generateMenuSession(true);

        // test retrieving entries
        $entries = \Html::getMenuFuzzySearchList();
        $this->assertGreaterThan(5, count($entries));

        foreach ($entries as $entry) {
            $this->assertArrayHasKey('title', $entry);
            $this->assertArrayHasKey('url', $entry);
        }
    }

    public function testEntitiesDeep()
    {
        $value = 'Should be \' "escaped" éè!';
        $expected = 'Should be &#039; &quot;escaped&quot; &eacute;&egrave;!';
        $result = @\Html::entities_deep($value);
        $this->assertSame($expected, $result);

        $result = @\Html::entities_deep([$value, $value, $value]);
        $this->assertSame([$expected, $expected, $expected], $result);
    }

    public function testCleanParametersURL()
    {
        $url = 'http://perdu.com';
        $this->assertSame($url, \Html::cleanParametersURL($url));

        $purl = $url . '?with=some&args=none';
        $this->assertSame($url, \Html::cleanParametersURL($purl));
    }

    public function testDisplayMessageAfterRedirect()
    {
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [
            ERROR    => ['Something went really wrong :('],
            WARNING  => ['Oooops, I did it again!'],
        ];

        ob_start();
        \Html::displayMessageAfterRedirect();
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression(
            '/class="[^"]*bg-danger[^"]*".*Error.*Something went really wrong :\(/s',
            $output
        );

        $this->assertMatchesRegularExpression(
            '/class="[^"]*bg-warning[^"]*".*Warning.*Oooops, I did it again!/s',
            $output
        );

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT']);
    }

    public function testDisplayBackLink()
    {
        global $CFG_GLPI;

        ob_start();
        \Html::displayBackLink();
        $output = ob_get_clean();
        $this->assertSame('<a href="http://localhost">Back</a>', $output);

        $_SERVER['HTTP_REFERER'] = 'http://localhost/originalpage.html';
        ob_start();
        \Html::displayBackLink();
        $output = ob_get_clean();
        $this->assertSame('<a href="http://localhost/originalpage.html">Back</a>', $output);
        $_SERVER['HTTP_REFERER'] = ''; // reset referer to prevent having this var in test loop mode
    }

    public function testAddConfirmationOnAction()
    {
        $string = 'Are U\' OK?';
        $expected = 'onclick="if (window.confirm(&quot;Are U&amp;#039; OK?&quot;)){ ;return true;} else { return false;}"';
        $this->assertSame($expected, \Html::addConfirmationOnAction($string));

        $strings = ['Are you', 'OK?'];
        $expected = 'onclick="if (window.confirm(&quot;Are you\nOK?&quot;)){ ;return true;} else { return false;}"';
        $this->assertSame($expected, \Html::addConfirmationOnAction($strings));

        $actions = '$("#mydiv").focus();';
        $expected = 'onclick="if (window.confirm(&quot;Are U&amp;#039; OK?&quot;)){ $(&quot;#mydiv&quot;).focus();return true;} else { return false;}"';
        $this->assertSame($expected, \Html::addConfirmationOnAction($string, $actions));
    }

    public function testJsFunctions()
    {
        $this->assertSame("$('#myid')", @\Html::jsGetElementbyID('myid'));
        $this->assertSame("$('#myid').trigger('setValue', 'myval');", @\Html::jsSetDropdownValue('myid', 'myval'));
        $this->assertSame("$('#myid').val()", @\Html::jsGetDropdownValue('myid'));
    }

    public function testCleanId()
    {
        $id = 'myid';
        $this->assertSame($id, \Html::cleanId($id));

        $id = 'array[]';
        $expected = 'array__';
        $this->assertSame($expected, \Html::cleanId($id));
    }

    public function testImage()
    {
        $path = '/path/to/image.png';
        $expected = '<img src="/path/to/image.png" title="" alt="" />';
        $this->assertSame($expected, \Html::image($path));

        $options = [
            'title'  => 'My title',
            'alt'    => 'no img text',
        ];
        $expected = '<img src="/path/to/image.png" title="My title" alt="no img text" />';
        $this->assertSame($expected, \Html::image($path, $options));

        $options = ['url' => 'mypage.php'];
        $expected = '<a href="mypage.php"><img src="/path/to/image.png" title="" alt="" class="pointer" /></a>';
        $this->assertSame($expected, \Html::image($path, $options));

        $options = ['url' => 'mypage.php', 'class' => 'specific-class'];
        $expected = '<a href="mypage.php"><img src="/path/to/image.png" class="specific-class" title="" alt="" /></a>';
        $this->assertSame($expected, \Html::image($path, $options));
    }

    public function testLink()
    {
        $text = 'My link';
        $url = 'mylink.php';

        $expected = '<a href="mylink.php" >My link</a>';
        $this->assertSame($expected, \Html::link($text, $url));

        $options = [
            'confirm'   => 'U sure?',
        ];
        $expected = '<a href="mylink.php" onclick="if (window.confirm(&quot;U sure?&quot;)){ ;return true;} else { return false;}">My link</a>';
        $this->assertSame($expected, \Html::link($text, $url, $options));

        $options['confirmaction'] = 'window.close();';
        $expected = '<a href="mylink.php" onclick="if (window.confirm(&quot;U sure?&quot;)){ window.close();return true;} else { return false;}">My link</a>';
        $this->assertSame($expected, \Html::link($text, $url, $options));
    }

    public function testHidden()
    {
        $name = 'hiddenfield';
        $expected = '<input type="hidden" name="hiddenfield"  />';
        $this->assertSame($expected, \Html::hidden($name));

        $options = ['value'  => 'myval'];
        $expected = '<input type="hidden" name="hiddenfield" value="myval" />';
        $this->assertSame($expected, \Html::hidden($name, $options));

        $options = [
            'value'  => [
                'a value',
                'another one',
            ],
        ];
        $expected = "<input type=\"hidden\" name=\"hiddenfield[0]\" value=\"a value\" />\n<input type=\"hidden\" name=\"hiddenfield[1]\" value=\"another one\" />\n";
        $this->assertSame($expected, \Html::hidden($name, $options));

        $options = [
            'value'  => [
                'one' => 'a value',
                'two' => 'another one',
            ],
        ];
        $expected = "<input type=\"hidden\" name=\"hiddenfield[one]\" value=\"a value\" />\n<input type=\"hidden\" name=\"hiddenfield[two]\" value=\"another one\" />\n";
        $this->assertSame($expected, \Html::hidden($name, $options));
    }

    public function testInput()
    {
        $name = 'in_put';
        $expected = '<input type="text" name="in_put" class="form-control" />';
        $this->assertSame($expected, \Html::input($name));

        $options = [
            'value'     => 'myval',
            'class'     => 'a_class',
            'data-id'   => 12,
        ];
        $expected = '<input type="text" name="in_put" value="myval" class="a_class" data-id="12" />';
        $this->assertSame($expected, \Html::input($name, $options));

        $options = [
            'type'      => 'number',
            'min'       => '10',
            'value'     => 'myval',
        ];
        $expected = '<input type="number" name="in_put" min="10" value="myval" class="form-control" />';
        $this->assertSame($expected, \Html::input($name, $options));
    }

    public static function providerGetRefererUrl(): iterable
    {
        // Basic cases
        yield 'http://example.org/' => [
            'referer'  => 'http://example.org/',
            'expected' => 'http://example.org/',
        ];
        yield 'http://localhost/glpi/front/change.form.php?id=1' => [
            'referer'  => 'http://localhost/glpi/front/change.form.php?id=1',
            'expected' => 'http://localhost/glpi/front/change.form.php?id=1',
        ];

        // Invalid referer
        yield '/invalid/referer' => [
            'referer'  => '/invalid/referer',
            'expected' => null,
        ];
        yield '' => [
            'referer'  => '',
            'expected' => null,
        ];
    }

    #[DataProvider('providerGetRefererUrl')]
    public function testGetRefererUrl(string $referer, ?string $expected): void
    {
        $_SERVER['HTTP_REFERER'] = $referer;
        $this->assertSame($expected, \Html::getRefererUrl());
    }

    public static function providerGetBackUrl(): iterable
    {
        // Basic cases
        yield 'http://localhost/glpi/front/change.form.php?id=1' => [
            'referer'  => 'http://localhost/glpi/front/change.form.php?id=1',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi/front/change.form.php?id=1',
        ];
        yield 'http://localhost/glpi/test.php?param1=1&param2=2&param3=3' => [
            'referer'  => 'http://localhost/glpi/test.php?param1=1&param2=2&param3=3',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi/test.php?param1=1&param2=2&param3=3',
        ];

        // `forcetab` param stripping
        yield 'http://localhost/glpi/front/change.form.php?id=1&forcetab=Change$2' => [
            'referer'  => 'http://localhost/glpi/front/change.form.php?id=1&forcetab=Change$2',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi/front/change.form.php?id=1',
        ];
        yield 'http://localhost/glpi/test.php?param1=1&param2=2&forcetab=test&param3=3' => [
            'referer'  => 'http://localhost/glpi/test.php?param1=1&param2=2&forcetab=test&param3=3',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi/test.php?param1=1&param2=2&param3=3',
        ];

        // Prevent switch between http and https schemes
        yield 'http://localhost/glpi/front/computer.form.php?id=1' => [
            'referer'  => 'http://localhost/glpi/front/computer.form.php?id=1',
            'base_url' => 'https://localhost/glpi',
            'expected' => 'http://localhost/glpi/front/computer.form.php?id=1',
        ];
        yield 'https://localhost/glpi/front/computer.form.php?id=1' => [
            'referer'  => 'https://localhost/glpi/front/computer.form.php?id=1',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'https://localhost/glpi/front/computer.form.php?id=1',
        ];

        // Invalid referer
        yield '/invalid/referer' => [
            'referer'  => '/invalid/referer',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi',
        ];
        yield '' => [
            'referer'  => '',
            'base_url' => 'http://localhost/glpi',
            'expected' => 'http://localhost/glpi',
        ];
    }

    #[DataProvider('providerGetBackUrl')]
    public function testGetBackUrl(string $referer, string $base_url, string $expected): void
    {
        global $CFG_GLPI;

        $_SERVER['HTTP_REFERER'] = $referer;
        $CFG_GLPI['url_base'] = $base_url;

        $this->assertSame($expected, \Html::getBackUrl());
    }

    public function testGetScssFileHash()
    {

        $structure = [
            'css' => [
                'all.scss' => <<<SCSS
body {
   font-size: 12px;
}
@import 'imports/borders';     /* import without extension */
@import 'imports/colors.scss'; /* import with extension */
SCSS,

                'another.scss' => <<<SCSS
form input {
   background: grey;
}
SCSS,

                'imports' => [
                    'borders.scss' => <<<SCSS
.big-border {
   border: 5px dashed black;
}
SCSS,
                    'colors.scss' => <<<SCSS
.red {
   color:red;
}
SCSS,
                ],
            ],
        ];
        vfsStream::setup('glpi', null, $structure);

        $files_md5 = [
            'all.scss'             => md5_file(vfsStream::url('glpi/css/all.scss')),
            'another.scss'         => md5_file(vfsStream::url('glpi/css/another.scss')),
            'imports/borders.scss' => md5_file(vfsStream::url('glpi/css/imports/borders.scss')),
            'imports/colors.scss'  => md5_file(vfsStream::url('glpi/css/imports/colors.scss')),
        ];

        // Composite scss file hash corresponds to self md5 suffixed by all imported scss md5
        $this->assertEquals(
            $files_md5['all.scss'] . $files_md5['imports/borders.scss'] . $files_md5['imports/colors.scss'],
            \Html::getScssFileHash(vfsStream::url('glpi/css/all.scss'))
        );

        // Simple scss file hash corresponds to self md5
        $this->assertEquals(
            $files_md5['another.scss'],
            \Html::getScssFileHash(vfsStream::url('glpi/css/another.scss'))
        );
    }

    public function testCompileScssForPlugin()
    {
        // FIXME remove the `@` operator once `scssphp/scssphp` and `league/uri` PHP 8.5 deprecation fixes will be released
        $compiled_scss = @\Html::compileScss(['file' => '/plugins/tester/css/styles.scss']);

        // Strip comments to ease comparison.
        $compiled_scss = \preg_replace('~/\*.*\*/~s', '', $compiled_scss);

        $this->assertEquals(
            <<<CSS
            
            nav > ul {
              margin: 0;
              padding: 0;
              list-style: none;
              color: blue;
            }
            CSS,
            $compiled_scss
        );
    }


    public static function getGenericDateTimeSearchItemsProvider(): array
    {
        return [
            [
                'options' => [
                    'with_time'          => true,
                    'with_future'        => false,
                    'with_days'          => false,
                    'with_specific_date' => false,
                ],
                'check_values' => [
                    'NOW'       => "Now",
                    '-4HOUR'    => "- 4 hours",
                    '-14MINUTE' => "- 14 minutes",
                ],
                'unwanted' => ['0', '4DAY', 'LASTMONDAY'],
            ],
            [
                'options' => [
                    'with_time'          => true,
                    'with_future'        => true,
                    'with_days'          => false,
                    'with_specific_date' => false,
                ],
                'check_values' => [
                    'NOW'       => "Now",
                    '-4HOUR'    => "- 4 hours",
                    '-14MINUTE' => "- 14 minutes",
                    '5DAY'      => "+ 5 days",
                    '11HOUR'    => "+ 11 hours",
                ],
                'unwanted' => ['0', 'LASTMONDAY'],
            ],
            [
                'options' => [
                    'with_time'          => false,
                    'with_future'        => true,
                    'with_days'          => false,
                    'with_specific_date' => false,
                ],
                'check_values' => [
                    'NOW'       => "Today",
                    '4DAY'      => "+ 4 days",
                    '-3DAY'      => "- 3 days",
                ],
                'unwanted' => ['0', 'LASTMONDAY', '-3MINUTE'],
            ],
            [
                'options' => [
                    'with_time'          => true,
                    'with_future'        => false,
                    'with_days'          => true,
                    'with_specific_date' => false,
                ],
                'check_values' => [
                    'NOW'        => "Now",
                    'TODAY'      => "Today",
                    '-4HOUR'     => "- 4 hours",
                    '-14MINUTE'  => "- 14 minutes",
                    'LASTMONDAY' => "last Monday",
                    'BEGINMONTH' => "Beginning of the month",
                    'BEGINYEAR'  => "Beginning of the year",
                ],
                'unwanted' => ['0', '+2DAY',],
            ],
            [
                'options' => [
                    'with_time'          => false,
                    'with_future'        => false,
                    'with_days'          => false,
                    'with_specific_date' => true,
                ],
                'check_values' => [
                    '0' => "Specify a date",
                ],
                'unwanted' => ['+2DAY', 'LASTMONDAY', '-3MINUTE'],
            ],
        ];
    }

    #[DataProvider('getGenericDateTimeSearchItemsProvider')]
    public function testGetGenericDateTimeSearchItems(
        array $options,
        array $check_values,
        array $unwanted
    ) {
        $values = \Html::getGenericDateTimeSearchItems($options);

        foreach ($check_values as $key => $value) {
            $this->assertArrayHasKey($key, $values);
            $this->assertEquals($value, $values[$key]);
        }

        foreach ($unwanted as $key) {
            $this->assertArrayNotHasKey($key, $values);
        }
    }

    public static function inputNameProvider(): iterable
    {
        yield [
            'name'      => 'itemtype',
            'expected'  => 'itemtype',
        ];

        yield [
            'name'      => 'link_abc1[itemtype]',
            'expected'  => 'link_abc1[itemtype]',
        ];

        yield [
            'name'      => 'foo\'"$**_23',
            'expected'  => 'foo__23',
        ];

        // Make sure the format used in form destination config is not broken
        yield [
            'name'     => 'config[glpi-form-destination-commonitilfield-olattrfield][slm_id]',
            'expected' => 'config[glpi-form-destination-commonitilfield-olattrfield][slm_id]',
        ];
    }

    #[DataProvider('inputNameProvider')]
    public function testSanitizeInputName(string $name, string $expected): void
    {
        $this->assertEquals($expected, \Html::sanitizeInputName($name));
    }

    public static function domIdProvider(): iterable
    {
        yield [
            'name'      => 'itemtype',
            'expected'  => 'itemtype',
        ];

        yield [
            'name'      => 'foo\'"$**_23-1',
            'expected'  => 'foo__23-1',
        ];
    }

    #[DataProvider('domIdProvider')]
    public function testSanitizeDomId(string $name, string $expected): void
    {
        $this->assertEquals($expected, \Html::sanitizeDomId($name));
    }

    public static function getMenuSectorForItemtypeProvider(): iterable
    {
        yield [
            'itemtype' => 'Computer',
            'expected' => 'assets',
        ];

        yield [
            'itemtype' => 'Ticket',
            'expected' => 'helpdesk',
        ];

        yield [
            'itemtype' => 'SoftwareLicense',
            'expected' => 'management',
        ];

        yield [
            'itemtype' => 'Project',
            'expected' => 'tools',
        ];

        yield [
            'itemtype' => 'User',
            'expected' => 'admin',
        ];

        yield [
            'itemtype' => 'Config',
            'expected' => 'config',
        ];

        yield [
            'itemtype' => MyPsr4Class::class,
            'expected' => 'management',
        ];
    }

    #[DataProvider('getMenuSectorForItemtypeProvider')]
    public function testGetMenuSectorForItemtype($itemtype, $expected): void
    {
        $this->assertEquals($expected, \Html::getMenuSectorForItemtype($itemtype));
    }

    public static function timestampToRelativeStrProvider(): iterable
    {
        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => null,
            'expected'  => 'Never',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 00:00:00.000',
            'expected'  => 'Now',
        ];

        yield [
            'current' => '2025-01-01 12:00:00.000',
            'timestamp' => '2025-01-01 11:59:59.000',
            'expected'  => 'Just now',
        ];

        yield [
            'current' => '2025-01-01 12:00:00.000',
            'timestamp' => '2025-01-01 11:59:01.000',
            'expected'  => 'Just now',
        ];

        yield [
            'current' => '2025-01-01 12:00:00.000',
            'timestamp' => '2025-01-01 11:59:00.000',
            'expected'  => '1 minutes ago',
        ];

        yield [
            'current' => '2025-01-01 12:00:00.000',
            'timestamp' => '2025-01-01 11:00:01.000',
            'expected'  => '59 minutes ago',
        ];

        yield [
            'current' => '2025-01-01 23:59:59.000',
            'timestamp' => '2025-01-01 22:59:59.000',
            'expected'  => '1 hours ago',
        ];

        yield [
            'current' => '2025-01-01 23:59:59.000',
            'timestamp' => '2025-01-01 00:00:00.000',
            'expected'  => '23 hours ago',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-31 00:00:00.000',
            'expected'  => 'Yesterday',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-30 00:00:00.000',
            'expected'  => '2 days ago',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-19 00:00:00.000',
            'expected'  => '13 days ago',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-18 00:00:00.000',
            'expected'  => '2 weeks ago',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-02 00:00:00.000',
            'expected'  => '4 weeks ago',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-12-01 00:00:00.000',
            'expected'  => 'Last month',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-11-03 00:00:00.000',
            'expected'  => 'Last month',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2024-11-02 00:00:00.000',
            'expected'  => 'November 2024',
        ];

        yield [
            'current' => '2026-01-01 00:00:00.000',
            'timestamp' => '2024-12-31 00:00:00.000',
            'expected'  => 'December 2024',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 00:00:01.000',
            'expected'  => 'In a minute',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 00:01:59.000',
            'expected'  => 'In a minute',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 00:02:00.000',
            'expected'  => 'In 2 minutes',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 00:59:59.000',
            'expected'  => 'In 59 minutes',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 01:00:00.000',
            'expected'  => 'In an hour',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 01:59:59.000',
            'expected'  => 'In an hour',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 02:00:00.000',
            'expected'  => 'In 2 hours',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-01 23:59:59.000',
            'expected'  => 'In 23 hours',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-02 00:00:00.000',
            'expected'  => 'Tomorrow',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-02 23:59:59.000',
            'expected'  => 'Tomorrow',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-03 00:00:00.000',
            'expected'  => 'In 2 days',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-14 23:59:59.000',
            'expected'  => 'In 13 days',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-15 00:00:00.000',
            'expected'  => 'In 2 weeks',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-01-31 23:59:59.000',
            'expected'  => 'In 4 weeks',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-02-01 00:00:00.000',
            'expected'  => 'Next month',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-03-01 23:59:59.000',
            'expected'  => 'Next month',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-03-02 00:00:00.000',
            'expected'  => 'March 2025',
        ];

        yield [
            'current' => '2025-01-01 00:00:00.000',
            'timestamp' => '2025-12-31 00:00:00.000',
            'expected'  => 'December 2025',
        ];
    }

    #[DataProvider('timestampToRelativeStrProvider')]
    public function testTimestampToRelativeStr(string $current, ?string $timestamp, string $expected): void
    {
        $this->login();

        $_SESSION['glpi_currenttime'] = $current;
        $_SESSION['glpilanguage'] = 'en_GB';

        $this->assertSame($expected, \Html::timestampToRelativeStr($timestamp));
    }
}
