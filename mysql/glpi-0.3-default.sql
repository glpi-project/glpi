DROP TABLE IF EXISTS `computers`;
CREATE TABLE `computers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `flags_server` tinyint(4) NOT NULL default '0',
  `os` varchar(100) NOT NULL default '',
  `osver` varchar(20) NOT NULL default '',
  `processor` varchar(30) NOT NULL default '',
  `processor_speed` varchar(30) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ramtype` varchar(200) NOT NULL default '',
  `ram` varchar(6) NOT NULL default '',
  `network` varchar(200) NOT NULL default '',
  `hdspace` varchar(6) NOT NULL default '',
  `contact` varchar(90) NOT NULL default '',
  `contact_num` varchar(90) NOT NULL default '',
  `comments` text NOT NULL,
  `date_mod` datetime default NULL,
  `gfxcard` varchar(255) NOT NULL default '',
  `moboard` varchar(255) NOT NULL default '',
  `sndcard` varchar(255) NOT NULL default '',
  `hdtype` varchar(255) NOT NULL default '',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `flags` (`flags_server`)
) TYPE=MyISAM AUTO_INCREMENT=23 ;


INSERT INTO `computers` VALUES (21, 'bgfdbfdbdfgb', 'Generic x86 PC', 0, 'Debian woody 3.0', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', '', '2004-06-15 20:41:09', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'integrated', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);


DROP TABLE IF EXISTS `connect_wire`;
CREATE TABLE `connect_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `dropdown_gfxcard`;
CREATE TABLE `dropdown_gfxcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;


INSERT INTO `dropdown_gfxcard` VALUES ('ATI Rage Pro 3D AGP');
INSERT INTO `dropdown_gfxcard` VALUES ('Matrox Millenium G400DH');
INSERT INTO `dropdown_gfxcard` VALUES ('S3 Trio 64V+');
INSERT INTO `dropdown_gfxcard` VALUES ('integrated');


DROP TABLE IF EXISTS `dropdown_hdtype`;
CREATE TABLE `dropdown_hdtype` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;


INSERT INTO `dropdown_hdtype` VALUES ('IBM DTTA 35101');
INSERT INTO `dropdown_hdtype` VALUES ('IBM DCAS 34330');


DROP TABLE IF EXISTS `dropdown_iface`;
CREATE TABLE `dropdown_iface` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;


INSERT INTO `dropdown_iface` VALUES ('10Mbps Ethernet (UTP)');
INSERT INTO `dropdown_iface` VALUES ('100Mbps Ethernet (UTP)');
INSERT INTO `dropdown_iface` VALUES ('100Base FL');
INSERT INTO `dropdown_iface` VALUES ('100Mbps FDDI');
INSERT INTO `dropdown_iface` VALUES ('Frame Relay');
INSERT INTO `dropdown_iface` VALUES ('ISDN');
INSERT INTO `dropdown_iface` VALUES ('T1/E1 +');
INSERT INTO `dropdown_iface` VALUES ('Serial Link');


DROP TABLE IF EXISTS `dropdown_locations`;
CREATE TABLE `dropdown_locations` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;


INSERT INTO `dropdown_locations` VALUES ('1 ier etage');
INSERT INTO `dropdown_locations` VALUES ('2nd etage');


DROP TABLE IF EXISTS `dropdown_moboard`;
CREATE TABLE `dropdown_moboard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;


INSERT INTO `dropdown_moboard` VALUES ('Asus T2P4S');
INSERT INTO `dropdown_moboard` VALUES ('Asus P2BX');
INSERT INTO `dropdown_moboard` VALUES ('unknown');

DROP TABLE IF EXISTS `dropdown_network`;
CREATE TABLE `dropdown_network` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_network`
#

INSERT INTO `dropdown_network` VALUES ('3Com (100Mbps)');
INSERT INTO `dropdown_network` VALUES ('3Com (10Mbps)');
INSERT INTO `dropdown_network` VALUES ('Intel (100Mbps)');
INSERT INTO `dropdown_network` VALUES ('Intel (10Mbps)');
INSERT INTO `dropdown_network` VALUES ('Generic 100Mbps Card');
INSERT INTO `dropdown_network` VALUES ('Generic 10Mbps Card');
INSERT INTO `dropdown_network` VALUES ('None');
INSERT INTO `dropdown_network` VALUES ('AMD 10Mbps');
INSERT INTO `dropdown_network` VALUES ('Realtek 10Mbps');
INSERT INTO `dropdown_network` VALUES ('Realtek 100Mbps');
INSERT INTO `dropdown_network` VALUES ('integrated');

# --------------------------------------------------------

#
# Table structure for table `dropdown_os`
#

DROP TABLE IF EXISTS `dropdown_os`;
CREATE TABLE `dropdown_os` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_os`
#

INSERT INTO `dropdown_os` VALUES ('Windows 2000');
INSERT INTO `dropdown_os` VALUES ('Linux (Redhat 6.2)');
INSERT INTO `dropdown_os` VALUES ('Linux (Slackware 7)');
INSERT INTO `dropdown_os` VALUES ('Solaris');
INSERT INTO `dropdown_os` VALUES ('Windows NT 4.0');
INSERT INTO `dropdown_os` VALUES ('Windows 95a');
INSERT INTO `dropdown_os` VALUES ('Other');
INSERT INTO `dropdown_os` VALUES ('Windows 98');
INSERT INTO `dropdown_os` VALUES ('MacOS');
INSERT INTO `dropdown_os` VALUES ('Windows 95 OSR2');
INSERT INTO `dropdown_os` VALUES ('Windows 98 SR2');
INSERT INTO `dropdown_os` VALUES ('Debian woody 3.0');

DROP TABLE IF EXISTS `dropdown_processor`;
CREATE TABLE `dropdown_processor` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;



INSERT INTO `dropdown_processor` VALUES ('Intel Pentium');
INSERT INTO `dropdown_processor` VALUES ('Intel Pentium II');
INSERT INTO `dropdown_processor` VALUES ('AMD K6-1');
INSERT INTO `dropdown_processor` VALUES ('AMD K6-2');
INSERT INTO `dropdown_processor` VALUES ('AMD K6-3');
INSERT INTO `dropdown_processor` VALUES ('PowerPC G3');
INSERT INTO `dropdown_processor` VALUES ('Intel Pentium III');
INSERT INTO `dropdown_processor` VALUES ('AMD Athlon');
INSERT INTO `dropdown_processor` VALUES ('68k (Motorola)');
INSERT INTO `dropdown_processor` VALUES ('486 SX');
INSERT INTO `dropdown_processor` VALUES ('486 DX');
INSERT INTO `dropdown_processor` VALUES ('486 DX2/4');
INSERT INTO `dropdown_processor` VALUES ('Intel Itanium');
INSERT INTO `dropdown_processor` VALUES ('PowerPC G4');
INSERT INTO `dropdown_processor` VALUES ('RS3000');
INSERT INTO `dropdown_processor` VALUES ('RS10k');
INSERT INTO `dropdown_processor` VALUES ('Alpha EV6.7');
INSERT INTO `dropdown_processor` VALUES ('PowerPC 603ev');
INSERT INTO `dropdown_processor` VALUES ('PowerPC 603');
INSERT INTO `dropdown_processor` VALUES ('PowerPC 601');
INSERT INTO `dropdown_processor` VALUES ('68040');
INSERT INTO `dropdown_processor` VALUES ('68040');
INSERT INTO `dropdown_processor` VALUES ('ULTRASparc II');
INSERT INTO `dropdown_processor` VALUES ('Intel Pentium IV');
INSERT INTO `dropdown_processor` VALUES ('AMD Athlon');
INSERT INTO `dropdown_processor` VALUES ('AMD Duron');



DROP TABLE IF EXISTS `dropdown_ram`;
CREATE TABLE `dropdown_ram` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;



INSERT INTO `dropdown_ram` VALUES ('36pin SIMMS');
INSERT INTO `dropdown_ram` VALUES ('72pin SIMMS (Fast Page)');
INSERT INTO `dropdown_ram` VALUES ('72pin SIMMS (EDO)');
INSERT INTO `dropdown_ram` VALUES ('Unbuffered DIMMs');
INSERT INTO `dropdown_ram` VALUES ('DIMMs w/EEPROM');
INSERT INTO `dropdown_ram` VALUES ('SDRAM DIMMs (<10ns)');
INSERT INTO `dropdown_ram` VALUES ('ECC DIMMs');
INSERT INTO `dropdown_ram` VALUES ('Other');
INSERT INTO `dropdown_ram` VALUES ('iMac DIMMS');



DROP TABLE IF EXISTS `dropdown_sndcard`;
CREATE TABLE `dropdown_sndcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;



INSERT INTO `dropdown_sndcard` VALUES ('Soundblaster 128 PCI');
INSERT INTO `dropdown_sndcard` VALUES ('Soundblaster 16 PnP');
INSERT INTO `dropdown_sndcard` VALUES ('integrated');



DROP TABLE IF EXISTS `event_log`;
CREATE TABLE `event_log` (
  `ID` int(11) NOT NULL auto_increment,
  `item` int(11) NOT NULL default '0',
  `itemtype` varchar(10) NOT NULL default '',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `service` varchar(20) default NULL,
  `level` tinyint(4) NOT NULL default '0',
  `message` text NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY `comp` (`item`),
  KEY `date` (`date`)
) TYPE=MyISAM AUTO_INCREMENT=451 ;



INSERT INTO `event_log` VALUES (363, -1, 'system', '2004-06-03 14:12:01', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (364, 8, 'computers', '2004-06-03 14:16:17', 'tracking', 4, 'glpi added new job.');
INSERT INTO `event_log` VALUES (365, -1, 'system', '2004-06-03 14:16:21', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (366, -1, 'system', '2004-06-03 14:16:32', 'login', 1, 'failed login: post-only');
INSERT INTO `event_log` VALUES (367, -1, 'system', '2004-06-03 14:16:42', 'login', 3, 'post-only logged in.');
INSERT INTO `event_log` VALUES (368, 15, 'computers', '2004-06-03 14:39:41', 'tracking', 4, 'post-only added new job.');
INSERT INTO `event_log` VALUES (369, -1, 'system', '2004-06-03 14:40:06', 'login', 3, 'post-only logged in.');
INSERT INTO `event_log` VALUES (370, -1, 'system', '2004-06-03 15:07:42', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (371, 15, 'computers', '2004-06-03 15:07:53', 'tracking', 4, 'glpi added followup to job 5.');
INSERT INTO `event_log` VALUES (372, -1, 'system', '2004-06-03 15:08:02', 'login', 3, 'post-only logged in.');
INSERT INTO `event_log` VALUES (373, -1, 'system', '2004-06-03 15:10:33', 'login', 3, 'post-only logged in.');
INSERT INTO `event_log` VALUES (374, -1, 'system', '2004-06-03 15:11:29', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (375, -1, 'system', '2004-06-03 15:11:40', 'login', 3, 'post-only logged in.');
INSERT INTO `event_log` VALUES (376, -1, 'system', '2004-06-03 15:11:57', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (377, -1, 'system', '2004-06-03 15:17:11', 'login', 3, 'tech logged in.');
INSERT INTO `event_log` VALUES (378, -1, 'system', '2004-06-03 15:45:08', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (379, -1, 'system', '2004-06-11 16:50:02', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (380, -1, 'system', '2004-06-11 17:17:22', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (381, -1, 'system', '2004-06-11 20:04:42', 'login', 1, 'failed login: ');
INSERT INTO `event_log` VALUES (382, -1, 'system', '2004-06-14 14:59:50', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (383, -1, 'system', '2004-06-14 17:05:48', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (384, -1, 'system', '2004-06-14 17:06:03', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (385, -1, 'system', '2004-06-14 17:07:01', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (386, -1, 'system', '2004-06-14 17:08:18', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (387, -1, 'system', '2004-06-14 17:12:10', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (388, -1, 'system', '2004-06-14 17:12:24', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (389, -1, 'system', '2004-06-14 17:12:52', 'login', 1, 'failed login: glpi_ldap');
INSERT INTO `event_log` VALUES (390, -1, 'system', '2004-06-14 17:19:13', 'login', 1, 'failed login: admin');
INSERT INTO `event_log` VALUES (391, -1, 'system', '2004-06-14 17:19:20', 'login', 1, 'failed login: admin');
INSERT INTO `event_log` VALUES (392, -1, 'system', '2004-06-14 17:23:33', 'login', 1, 'failed login: admin');
INSERT INTO `event_log` VALUES (393, -1, 'system', '2004-06-14 17:24:05', 'login', 1, 'failed login: admin');
INSERT INTO `event_log` VALUES (394, -1, 'system', '2004-06-14 17:24:19', 'login', 1, 'failed login: admin');
INSERT INTO `event_log` VALUES (395, -1, 'system', '2004-06-14 17:26:15', 'login', 3, 'admin logged in.');
INSERT INTO `event_log` VALUES (396, -1, 'system', '2004-06-14 17:42:41', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (397, -1, 'system', '2004-06-14 17:46:04', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (398, -1, 'system', '2004-06-14 17:46:58', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (399, -1, 'system', '2004-06-14 17:47:30', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (400, -1, 'system', '2004-06-14 18:19:58', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (401, -1, 'system', '2004-06-14 18:20:41', 'login', 1, 'failed login: bazou');
INSERT INTO `event_log` VALUES (402, -1, 'system', '2004-06-14 18:20:51', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (403, -1, 'system', '2004-06-14 19:20:40', 'login', 1, 'failed login: louison');
INSERT INTO `event_log` VALUES (404, -1, 'system', '2004-06-14 19:20:58', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (405, -1, 'system', '2004-06-14 20:23:25', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (406, -1, 'system', '2004-06-14 20:23:33', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (407, -1, 'system', '2004-06-15 11:02:18', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (408, -1, 'system', '2004-06-15 11:11:04', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (409, -1, 'system', '2004-06-15 11:11:17', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (410, -1, 'system', '2004-06-15 11:13:14', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (411, -1, 'system', '2004-06-15 11:13:27', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (412, -1, 'system', '2004-06-15 11:13:36', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (413, 0, 'users', '2004-06-15 11:13:44', 'setup', 4, 'glpi deleted user glpi.');
INSERT INTO `event_log` VALUES (414, -1, 'system', '2004-06-15 11:13:58', 'login', 1, 'failed login: glpi');
INSERT INTO `event_log` VALUES (415, -1, 'system', '2004-06-15 11:14:07', 'login', 3, 'louison logged in.');
INSERT INTO `event_log` VALUES (416, -1, 'system', '2004-06-15 11:15:32', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (417, 0, 'users', '2004-06-15 11:15:39', 'setup', 4, 'glpi deleted user normal.');
INSERT INTO `event_log` VALUES (418, -1, 'system', '2004-06-15 16:40:37', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (419, -1, 'system', '2004-06-15 16:41:29', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (420, -1, 'system', '2004-06-15 16:44:40', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (421, 15, 'computers', '2004-06-15 16:59:50', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `event_log` VALUES (422, 15, 'computers', '2004-06-15 16:59:53', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (423, 15, 'computers', '2004-06-15 17:02:26', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `event_log` VALUES (424, 10, 'computers', '2004-06-15 17:02:34', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (425, 1, 'printers', '2004-06-15 17:28:26', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (426, 2, 'printers', '2004-06-15 17:28:36', 'inventory', 4, 'glpi updated item.');
INSERT INTO `event_log` VALUES (427, 2, 'printers', '2004-06-15 17:28:42', 'inventory', 4, 'glpi updated item.');
INSERT INTO `event_log` VALUES (428, 9, 'networking', '2004-06-15 17:28:50', 'inventory', 4, 'glpi updated item.');
INSERT INTO `event_log` VALUES (429, -1, 'system', '2004-06-15 17:49:46', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (430, -1, 'system', '2004-06-15 17:50:17', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (431, 18, 'computers', '2004-06-15 17:50:27', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (432, 0, 'computers', '2004-06-15 19:11:55', 'inventory', 4, 'glpi added gkjghjvbjkbkj.');
INSERT INTO `event_log` VALUES (433, 19, 'computers', '2004-06-15 19:12:03', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (434, -1, 'system', '2004-06-15 19:50:01', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (435, -1, 'system', '2004-06-15 19:50:04', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (436, 8, 'computers', '2004-06-15 19:50:12', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (437, -1, 'system', '2004-06-15 20:40:05', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (438, 0, 'computers', '2004-06-15 20:40:15', 'inventory', 4, 'glpi added fsvdsgvfdsgvfdg.');
INSERT INTO `event_log` VALUES (439, 20, 'computers', '2004-06-15 20:40:21', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (440, -1, 'system', '2004-06-15 20:40:57', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (441, 0, 'computers', '2004-06-15 20:41:09', 'inventory', 4, 'glpi added bgfdbfdbdfgb.');
INSERT INTO `event_log` VALUES (442, 0, 'computers', '2004-06-15 20:47:02', 'inventory', 4, 'glpi added fvdsvdvsfd.');
INSERT INTO `event_log` VALUES (443, 22, 'computers', '2004-06-15 20:47:11', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `event_log` VALUES (444, 22, 'computers', '2004-06-15 20:47:13', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `event_log` VALUES (445, 22, 'computers', '2004-06-15 20:47:16', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `event_log` VALUES (446, -1, 'system', '2004-06-16 18:15:28', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (447, -1, 'system', '2004-06-16 18:15:29', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (448, -1, 'system', '2004-06-16 20:27:49', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (449, -1, 'system', '2004-06-17 17:50:24', 'login', 3, 'glpi logged in.');
INSERT INTO `event_log` VALUES (450, -1, 'system', '2004-06-17 17:55:11', 'login', 3, 'glpi logged in.');



DROP TABLE IF EXISTS `followups`;
CREATE TABLE `followups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL,
  `date` datetime default NULL,
  `author` varchar(200) default NULL,
  `contents` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=7 ;



DROP TABLE IF EXISTS `glpi_config`;
CREATE TABLE `glpi_config` (
  `config_id` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_attrib_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`config_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;



INSERT INTO `glpi_config` VALUES (1, '10', '10', '1', '80', '30', '15', ' 0.3', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', 'ldap://localhost/', 'dc=melnibone', '', '', 'admsys@sic.sp2mi.xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber');


DROP TABLE IF EXISTS `inst_software`;
CREATE TABLE `inst_software` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0',
  `license` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`license`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;



DROP TABLE IF EXISTS `licenses`;
CREATE TABLE `licenses` (
  `ID` int(15) NOT NULL auto_increment,
  `sID` int(15) NOT NULL default '0',
  `serial` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;


INSERT INTO `licenses` VALUES (7, 4, '12-aa-asd-12-aa');
INSERT INTO `licenses` VALUES (6, 4, 'asd-asdf-asdf-12');
INSERT INTO `licenses` VALUES (4, 4, 'au-23-as-23-cd');
INSERT INTO `licenses` VALUES (5, 4, 'qw-as-23-0k-23-dg');
INSERT INTO `licenses` VALUES (8, 3, 'free');
INSERT INTO `licenses` VALUES (9, 5, 'free');



DROP TABLE IF EXISTS `monitors`;
CREATE TABLE `monitors` (
  `ID` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `type` varchar(255) NOT NULL default '',
  `location` varchar(255) NOT NULL default '',
  `contact` varchar(255) NOT NULL default '',
  `contact_num` varchar(255) NOT NULL default '',
  `comments` text NOT NULL,
  `serial` varchar(255) NOT NULL default '',
  `otherserial` varchar(255) NOT NULL default '',
  `size` int(3) NOT NULL default '0',
  `flags_micro` tinyint(4) NOT NULL default '0',
  `flags_speaker` tinyint(4) NOT NULL default '0',
  `flags_subd` tinyint(4) NOT NULL default '0',
  `flags_bnc` tinyint(4) NOT NULL default '0',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`),
  KEY `ID` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;



INSERT INTO `monitors` VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', 'Nokia 445Xav', '1 ier etage', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0);
INSERT INTO `monitors` VALUES (2, 'Sony 19\'', '2003-09-18 00:14:50', 'Sony 200sf', '1 ier etage', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0);



DROP TABLE IF EXISTS `networking`;
CREATE TABLE `networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default '',
  `type` varchar(30) NOT NULL default '',
  `ram` varchar(10) NOT NULL default '',
  `location` varchar(40) NOT NULL default '',
  `serial` varchar(50) NOT NULL default '',
  `otherserial` varchar(50) NOT NULL default '',
  `contact` varchar(30) NOT NULL default '',
  `contact_num` varchar(30) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;



INSERT INTO `networking` VALUES (9, 'Dlink 450', 'Dlink Switch', '', '1 ier etage', '4586-puis-kioe', '', '', '', '2004-06-15 17:28:50', 'p', '0000-00-00', '0000-00-00', 0);



DROP TABLE IF EXISTS `networking_ports`;
CREATE TABLE `networking_ports` (
  `ID` int(11) NOT NULL auto_increment,
  `on_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `iface` char(40) NOT NULL default '',
  `logical_number` int(11) NOT NULL default '0',
  `name` char(30) NOT NULL default '',
  `ifaddr` char(30) NOT NULL default '',
  `ifmac` char(30) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=9 ;



INSERT INTO `networking_ports` VALUES (1, 8, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.26', '');
INSERT INTO `networking_ports` VALUES (2, 10, 1, '100Mbps Ethernet (UTP)', 1, '3com', '10.10.0.27', '');
INSERT INTO `networking_ports` VALUES (3, 15, 1, '100Mbps Ethernet (UTP)', 1, 'Generic', '10.10.0.28', '');
INSERT INTO `networking_ports` VALUES (4, 18, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.29', '');
INSERT INTO `networking_ports` VALUES (5, 9, 2, '100Mbps Ethernet (UTP)', 1, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` VALUES (6, 9, 2, '100Mbps Ethernet (UTP)', 2, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` VALUES (7, 9, 2, '100Mbps Ethernet (UTP)', 3, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` VALUES (8, 10, 2, 'Frame Relay', 0, '', '', '');



DROP TABLE IF EXISTS `networking_wire`;
CREATE TABLE `networking_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;



INSERT INTO `networking_wire` VALUES (1, 5, 1);
INSERT INTO `networking_wire` VALUES (2, 6, 2);
INSERT INTO `networking_wire` VALUES (3, 7, 3);



DROP TABLE IF EXISTS `prefs`;
CREATE TABLE `prefs` (
  `user` varchar(80) NOT NULL default '',
  `tracking_order` enum('no','yes') NOT NULL default 'no',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user`)
) TYPE=MyISAM;



INSERT INTO `prefs` VALUES ('glpi', 'yes', 'french');
INSERT INTO `prefs` VALUES ('Helpdesk', 'no', 'french');
INSERT INTO `prefs` VALUES ('tech', 'yes', 'french');
INSERT INTO `prefs` VALUES ('post-only', '', 'french');
INSERT INTO `prefs` VALUES ('admin', 'yes', 'french');
INSERT INTO `prefs` VALUES ('louison', 'yes', 'french');

# --------------------------------------------------------

#
# Table structure for table `printers`
#

DROP TABLE IF EXISTS `printers`;
CREATE TABLE `printers` (
  `ID` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `type` varchar(255) NOT NULL default '',
  `location` varchar(255) NOT NULL default '',
  `contact` varchar(255) NOT NULL default '',
  `contact_num` varchar(255) NOT NULL default '',
  `serial` varchar(255) NOT NULL default '',
  `otherserial` varchar(255) NOT NULL default '',
  `flags_serial` tinyint(4) NOT NULL default '0',
  `flags_par` tinyint(4) NOT NULL default '0',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  `ramSize` varchar(6) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `id` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;



INSERT INTO `printers` VALUES (2, 'HP deskjet', '2004-06-15 17:28:42', 'HP Deskjet 850c', '2nd etage', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0, '');



DROP TABLE IF EXISTS `software`;
CREATE TABLE `software` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `platform` varchar(200) NOT NULL default '',
  `version` varchar(20) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `comments` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;



INSERT INTO `software` VALUES (3, 'Acrobat PDF Viewer', 'Windows NT 4.0', '4', 'Admin', NULL);
INSERT INTO `software` VALUES (4, 'MS Windows NT', 'Windows NT 4.0', '4.0', 'Admin', NULL);
INSERT INTO `software` VALUES (5, 'Latex', 'Linux (Redhat 6.2)', '6.2', '1 ier etage', 'Latex');



DROP TABLE IF EXISTS `templates`;
CREATE TABLE `templates` (
  `ID` int(11) NOT NULL auto_increment,
  `templname` varchar(200) NOT NULL default '',
  `name` varchar(200) NOT NULL default '',
  `type` varchar(200) default NULL,
  `os` varchar(200) NOT NULL default '',
  `osver` varchar(20) NOT NULL default '',
  `processor` varchar(200) NOT NULL default '',
  `processor_speed` varchar(100) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ramtype` varchar(200) NOT NULL default '',
  `ram` varchar(20) NOT NULL default '',
  `network` varchar(200) NOT NULL default '',
  `hdspace` varchar(10) NOT NULL default '',
  `contact` varchar(200) NOT NULL default '',
  `contact_num` varchar(200) NOT NULL default '',
  `comments` text NOT NULL,
  `moboard` varchar(255) NOT NULL default '',
  `sndcard` varchar(255) NOT NULL default '',
  `gfxcard` varchar(255) NOT NULL default '',
  `hdtype` varchar(255) NOT NULL default '',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=8 ;



INSERT INTO `templates` VALUES (1, 'Blank Template', '', 'Generic x86 PC', 'Linux (Redhat 6.2)', '', '486 DX', '', 'Admin', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', 'Asus P2BX', 'Soundblaster 128 PCI', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `templates` VALUES (3, 'iMac', '', 'iMac 2nd Generation', 'MacOS', '9', 'PowerPC G3', '333', 'Admin', '', '', 'iMac DIMMS', '128', 'Generic 100Mbps Card', '6', '', '', 'Standard iMac', 'unknown', 'integrated', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO `templates` VALUES (7, 'test', '', 'Generic x86 PC', 'Debian woody 3.0', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', '', 'Asus P2BX', 'integrated', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);



DROP TABLE IF EXISTS `tracking`;
CREATE TABLE `tracking` (
  `ID` int(11) NOT NULL auto_increment,
  `date` datetime default NULL,
  `closedate` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` enum('new','old') default NULL,
  `author` varchar(200) default NULL,
  `assign` varchar(200) default NULL,
  `computer` int(11) default NULL,
  `contents` text,
  `priority` tinyint(4) NOT NULL default '1',
  `is_group` enum('no','yes') NOT NULL default 'no',
  `uemail` varchar(100) default NULL,
  `emailupdates` varchar(4) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;





DROP TABLE IF EXISTS `type_computers`;
CREATE TABLE `type_computers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;



INSERT INTO `type_computers` VALUES ('Generic x86 PC');
INSERT INTO `type_computers` VALUES ('PowerMac G4');
INSERT INTO `type_computers` VALUES ('iMac 2nd Generation');
INSERT INTO `type_computers` VALUES ('PowerMac G3');



DROP TABLE IF EXISTS `type_monitors`;
CREATE TABLE `type_monitors` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;



INSERT INTO `type_monitors` VALUES ('Nokia 445Xav');
INSERT INTO `type_monitors` VALUES ('Sony 200GDM');
INSERT INTO `type_monitors` VALUES ('Sony 200sf');
INSERT INTO `type_monitors` VALUES ('integrated');



DROP TABLE IF EXISTS `type_networking`;
CREATE TABLE `type_networking` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;



INSERT INTO `type_networking` VALUES ('Dlink Switch');



DROP TABLE IF EXISTS `type_printers`;
CREATE TABLE `type_printers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;


INSERT INTO `type_printers` VALUES ('HP Laserjet 4050N');
INSERT INTO `type_printers` VALUES ('HP Laserjet 4+');
INSERT INTO `type_printers` VALUES ('HP Deskjet 850c');



DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `name` varchar(80) NOT NULL default '',
  `password` varchar(80) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `location` varchar(100) NOT NULL default '',
  `phone` varchar(100) default NULL,
  `type` enum('normal','admin','post-only') NOT NULL default 'post-only',
  `realname` varchar(255) NOT NULL default '',
  `can_assign_job` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`name`),
  KEY `type` (`type`)
) TYPE=MyISAM;



INSERT INTO `users` VALUES ('Helpdesk', '14e43c2d31dcbdd1', '', 'Admin', NULL, 'post-only', 'Helpdesk Injector', 'no');
INSERT INTO `users` VALUES ('post-only', '3eb831c67be6aeda', '', '1 ier etage', NULL, 'post-only', 'post-only', 'no');
INSERT INTO `users` VALUES ('tech', '37bd7c4221e8a247', '', '2nd etage', NULL, 'admin', 'technicien', 'yes');
INSERT INTO `users` VALUES ('glpi', '5b9b1ee2216a5ffe', '', '', NULL, 'admin', '', 'yes');
INSERT INTO `users` VALUES ('louison', '', '', '', '', 'post-only', 'louison', 'no');