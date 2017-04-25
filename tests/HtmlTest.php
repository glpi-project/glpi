<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

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

/* Test for inc/html.class.php */

class HtmlTest extends PHPUnit\Framework\TestCase {

   /**
    * @covers Html::convDate
    */
    public function testConvDate() {
      $this->assertNull(Html::convDate(null));
      $this->assertNull(Html::convDate('NULL'));
      $this->assertNull(Html::convDate(''));

      $mydate = date('Y-m-d H:i:s');

      $expected = date('Y-m-d');
      unset($_SESSION['glpidate_format']);
      $this->assertEquals($expected, Html::convDate($mydate));
      $_SESSION['glpidate_format'] = 0;
      $this->assertEquals($expected, Html::convDate($mydate));

      $this->assertEquals($expected, Html::convDate(date('Y-m-d')));

      $expected = date('d-m-Y');
      $this->assertEquals($expected, Html::convDate($mydate, 1));

      $expected = date('m-d-Y');
      $this->assertEquals($expected, Html::convDate($mydate, 2));
    }

   /**
    * @covers Html::convDateTime
    */
    public function testConvDateTime() {
      $this->assertNull(Html::convDateTime(null));
      $this->assertNull(Html::convDateTime('NULL'));

      $mydate = date('Y-m-d H:i:s');

      $expected = date('Y-m-d H:i');
      $this->assertEquals($expected, Html::convDateTime($mydate));

      $expected = date('d-m-Y H:i');
      $this->assertEquals($expected, Html::convDateTime($mydate, 1));

      $expected = date('m-d-Y H:i');
      $this->assertEquals($expected, Html::convDateTime($mydate, 2));
   }

   /**
    * @covers Html::cleanInputText
    */
   public function testCleanInputText() {
      $origin = 'This is a \'string\' with some "replacements" needed, but not « others »!';
      $expected = 'This is a &apos;string&apos; with some &quot;replacements&quot; needed, but not « others »!';
      $this->assertEquals($expected, Html::cleanInputText($origin));
   }

   /**
    * @covers Html::cleanParametersURL
    */
   public function cleanParametersURL() {
      $url = 'http://host/glpi/path/to/file.php?var1=2&var2=3';
      $expected = 'http://host/glpi/path/to/file.php';
      $this->assertEquals($expected, Html::cleanParametersURL($url));
   }

   /**
    * @covers Html::nl2br_deep
    */
   public function testNl2br_deep() {
      $origin = "A string\nwith breakline.";
      $expected = "A string<br />\nwith breakline.";
      $this->assertEquals($expected, Html::nl2br_deep($origin));

      $origin = [
         "Another string\nwith breakline.",
         "And another\none"
      ];
      $expected = [
         "Another string<br />\nwith breakline.",
         "And another<br />\none"
      ];
      $this->assertEquals($expected, Html::nl2br_deep($origin));
   }

   /**
    * @covers Html::resume_text
    */
   public function testResume_text() {
      $origin = 'This is a very long string which will be truncated by a dedicated method. ' .
         'If the string is not truncated, well... We\'re wrong and got a very serious issue in our codebase!' .
         'And if the string has been correctly truncated, well... All is ok then, let\'s show if all the other tests are OK :)';
      $expected = 'This is a very long string which will be truncated by a dedicated method. ' .
         'If the string is not truncated, well... We\'re wrong and got a very serious issue in our codebase!' .
         'And if the string has been correctly truncated, well... All is ok then, let\'s show i&nbsp;(...)';
      $this->assertEquals($expected, Html::resume_text($origin));

      $origin = 'A string that is longer than 10 characters.';
      $expected = 'A string t&nbsp;(...)';
      $this->assertEquals($expected, Html::resume_text($origin, 10));
   }

   /**
    * @covers Html::resume_name
    */
   public function testResume_name() {
      $origin = 'This is a very long string which will be truncated by a dedicated method. ' .
         'If the string is not truncated, well... We\'re wrong and got a very serious issue in our codebase!' .
         'And if the string has been correctly truncated, well... All is ok then, let\'s show if all the other tests are OK :)';
      $expected = 'This is a very long string which will be truncated by a dedicated method. ' .
         'If the string is not truncated, well... We\'re wrong and got a very serious issue in our codebase!' .
         'And if the string has been correctly truncated, well... All is ok then, let\'s show i...';
      $this->assertEquals($expected, Html::resume_name($origin));

      $origin = 'A string that is longer than 10 characters.';
      $expected = 'A string t...';
      $this->assertEquals($expected, Html::resume_name($origin, 10));
   }

   /**
    * @covers Html::cleanPostForTextArea
    */
   public function testCleanPostForTextArea() {
      $origin = "A text that \\\"would\\\" be entered in a \\'textarea\\'\\nWith breakline\\r\\nand breaklines.";
      $expected = "A text that \"would\" be entered in a 'textarea'\nWith breakline\nand breaklines.";
      $this->assertEquals($expected, Html::cleanPostForTextArea($origin));

      $aorigin = [
        $origin,
        "Another\\none!"
      ];
      $aexpected = [
         $expected,
         "Another\none!"
      ];
      $this->assertEquals($aexpected, Html::cleanPostForTextArea($aorigin));
   }

   /**
    * @covers Html::formatNumber
    */
   public function testFormatNumber() {
      $_SESSION['glpinumber_format'] = 0;
      $origin = '';
      $expected = 0;
      $this->assertEquals($expected, Html::formatNumber($origin));

      $origin = '1207.3';

      $expected = '1&nbsp;207.30';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $expected = '1207.30';
      $this->assertEquals($expected, Html::formatNumber($origin, true));

      $origin = 124556.693;
      $expected = '124&nbsp;556.69';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $origin = 120.123456789;

      $expected = '120.12';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $expected = '120.12346';
      $this->assertEquals($expected, Html::formatNumber($origin, false, 5));

      $expected = '120';
      $this->assertEquals($expected, Html::formatNumber($origin, false, 0));

      $origin = 120.999;
      $expected = '121';
      $this->assertEquals($expected, Html::formatNumber($origin));
      $this->assertEquals($expected, Html::formatNumber($origin, false, 0));

      $this->assertEquals('-', Html::formatNumber('-'));

      $_SESSION['glpinumber_format'] = 2;

      $origin = '1207.3';
      $expected = '1&nbsp;207,30';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $_SESSION['glpinumber_format'] = 3;

      $origin = '1207.3';
      $expected = '1207.30';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $_SESSION['glpinumber_format'] = 4;

      $origin = '1207.3';
      $expected = '1207,30';
      $this->assertEquals($expected, Html::formatNumber($origin));

      $_SESSION['glpinumber_format'] = 1337;
      $origin = '1207.3';

      $expected = '1,207.30';
      $this->assertEquals($expected, Html::formatNumber($origin));
   }

   /**
    * @covers Html::timestampToString
    */
   public function testTimestampToString() {
      $expected = '0 seconds';
      $this->assertEquals($expected, Html::timestampToString(null));
      $this->assertEquals($expected, Html::timestampToString(''));
      $this->assertEquals($expected, Html::timestampToString(0));

      $tstamp = 57226;
      $expected = '15 hours 53 minutes 46 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp));

      $tstamp = -57226;
      $expected = '- 15 hours 53 minutes 46 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp));

      $tstamp = 1337;
      $expected = '22 minutes 17 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp));

      $expected = '22 minutes';
      $this->assertEquals($expected, Html::timestampToString($tstamp, false));

      $tstamp = 54;
      $expected = '54 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp));
      $this->assertEquals($expected, Html::timestampToString($tstamp, false));

      $tstamp = 157226;
      $expected = '1 days 19 hours 40 minutes 26 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp));

      $expected = '1 days 19 hours 40 minutes';
      $this->assertEquals($expected, Html::timestampToString($tstamp, false));

      $expected = '43 hours 40 minutes 26 seconds';
      $this->assertEquals($expected, Html::timestampToString($tstamp, true, false));

      $expected = '43 hours 40 minutes';
      $this->assertEquals($expected, Html::timestampToString($tstamp, false, false));
   }

   /**
    * @covers Html::weblink_extract
    */
   public function testWeblink_extract() {
      $origin = '<a href="http://glpi-project.org" class="example">THE GLPi Project!</a>';
      $expected = 'http://glpi-project.org';
      $this->assertEquals($expected, Html::weblink_extract($origin));

      $origin = '<a href="http://glpi-project.org/?one=two">THE GLPi Project!</a>';
      $expected = 'http://glpi-project.org/?one=two';
      $this->assertEquals($expected, Html::weblink_extract($origin));

      //These ones does not work, but probably should...
      $origin = '<a class="example" href="http://glpi-project.org">THE GLPi Project!</a>';
      $expected = $origin;
      $this->assertEquals($origin, Html::weblink_extract($origin));

      $origin = '<a href="http://glpi-project.org" class="example">THE <span>GLPi</span> Project!</a>';
      $expected = $origin;
      $this->assertEquals($expected, Html::weblink_extract($origin));
   }

   /**
    * @covers Html::getMenuInfos
    */
   public function testGetMenuInfos() {
      $menu = Html::getMenuInfos();
      $this->assertEquals(8, count($menu));

      $expected = [
         'assets',
         'helpdesk',
         'management',
         'tools',
         'plugins',
         'admin',
         'config',
         'preference'
      ];
      $this->assertEquals($expected, array_keys($menu));

      $expected = [
         'Computer',
         'Monitor',
         'Software',
         'NetworkEquipment',
         'Peripheral',
         'Printer',
         'CartridgeItem',
         'ConsumableItem',
         'Phone'
      ];
      $this->assertEquals('Assets', $menu['assets']['title']);
      $this->assertEquals($expected, $menu['assets']['types']);

      $expected = [
         'Ticket',
         'Problem',
         'Change',
         'Planning',
         'Stat',
         'TicketRecurrent'
      ];
      $this->assertEquals('Assistance', $menu['helpdesk']['title']);
      $this->assertEquals($expected, $menu['helpdesk']['types']);

      $expected = [
         'SoftwareLicense',
         'Budget',
         'Supplier',
         'Contact',
         'Contract',
         'Document'
      ];
      $this->assertEquals('Management', $menu['management']['title']);
      $this->assertEquals($expected, $menu['management']['types']);

      $expected = [
         'Project',
         'Reminder',
         'RSSFeed',
         'KnowbaseItem',
         'ReservationItem',
         'Report',
         'MigrationCleaner'
      ];
      $this->assertEquals('Tools', $menu['tools']['title']);
      $this->assertEquals($expected, $menu['tools']['types']);

      $expected = [];
      $this->assertEquals('Plugins', $menu['plugins']['title']);
      $this->assertEquals($expected, $menu['plugins']['types']);

      $expected = [
         'User',
         'Group',
         'Entity',
         'Rule',
         'Profile',
         'QueuedMail',
         'Backup',
         'Event'
      ];
      $this->assertEquals('Administration', $menu['admin']['title']);
      $this->assertEquals($expected, $menu['admin']['types']);

      $expected = [
         'CommonDropdown',
         'CommonDevice',
         'Notification',
         'SLA',
         'Config',
         'Control',
         'Crontask',
         'Auth',
         'MailCollector',
         'Link',
         'Plugin'
      ];
      $this->assertEquals('Setup', $menu['config']['title']);
      $this->assertEquals($expected, $menu['config']['types']);

      $this->assertEquals('My settings', $menu['preference']['title']);
      $this->assertNull($menu['preference']['types']);
      $this->assertEquals('/front/preference.php', $menu['preference']['default']);

   }

   /**
    * @covers Html::getCopyrightMessage()
    */
   public function testGetCopyrightMessage() {
      $message = Html::getCopyrightMessage();
      $this->assertContains(GLPI_VERSION, $message, 'Invalid GLPI version!');
      $this->assertContains(GLPI_YEAR, $message, 'Invalid copyright date!');
   }
}
