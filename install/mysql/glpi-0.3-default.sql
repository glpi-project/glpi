# phpMyAdmin SQL Dump
# version 2.5.6
# http://www.phpmyadmin.net
#
# Host: localhost
# Generation Time: Jun 03, 2004 at 11:46 AM
# Server version: 4.0.18
# PHP Version: 4.3.4
# 
# Database : `glpidb`
# 

# --------------------------------------------------------

#
# Table structure for table `computers`
#

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
) TYPE=MyISAM AUTO_INCREMENT=19 ;

#
# Dumping data for table `computers`
#

INSERT INTO `computers` (`ID`, `name`, `type`, `flags_server`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `date_mod`, `gfxcard`, `moboard`, `sndcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (8, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows NT 4.0', '', 'Intel Pentium III', '750', '1 ier etage', '4586-sd6-fds', '', 'SDRAM DIMMs (<10ns)', '512', '3Com (100Mbps)', '10', 'Roger Rabbit', '5462', '', '2003-09-18 00:15:44', 'integrated', 'unknown', 'integrated', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `computers` (`ID`, `name`, `type`, `flags_server`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `date_mod`, `gfxcard`, `moboard`, `sndcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (10, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows NT 4.0', 'SP3', 'Intel Pentium III', '750', '1 ier etage', '4598-jhd-545', '', 'SDRAM DIMMs (<10ns)', '128', '3Com (100Mbps)', '20', 'Peter Pan', '8565', '', '2003-09-18 00:07:58', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO `computers` (`ID`, `name`, `type`, `flags_server`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `date_mod`, `gfxcard`, `moboard`, `sndcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (15, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows 2000', 'SP2', 'Intel Pentium III', '750', '1 ier etage', '4561-hsub-dfsnj', '', 'SDRAM DIMMs (<10ns)', '512', 'Generic 100Mbps Card', '20', 'Poppins Marry', '6545', '', '2003-09-18 00:09:47', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO `computers` (`ID`, `name`, `type`, `flags_server`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `date_mod`, `gfxcard`, `moboard`, `sndcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (18, 'IBM 945gx', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', 'Intel Pentium III', '750', '1 ier etage', '9854-5f-4s4f', '', 'SDRAM DIMMs (<10ns)', '128', '3Com (100Mbps)', '20', 'Jeannot Lapin', '5465', '', '2003-09-18 00:05:07', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '2001-09-24', '2002-09-27', 0);

# --------------------------------------------------------

#
# Table structure for table `connect_wire`
#

DROP TABLE IF EXISTS `connect_wire`;
CREATE TABLE `connect_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Dumping data for table `connect_wire`
#


# --------------------------------------------------------

#
# Table structure for table `dropdown_gfxcard`
#

DROP TABLE IF EXISTS `dropdown_gfxcard`;
CREATE TABLE `dropdown_gfxcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_gfxcard`
#

INSERT INTO `dropdown_gfxcard` (`name`) VALUES ('ATI Rage Pro 3D AGP');
INSERT INTO `dropdown_gfxcard` (`name`) VALUES ('Matrox Millennium G400DH');
INSERT INTO `dropdown_gfxcard` (`name`) VALUES ('S3 Trio 64V+');
INSERT INTO `dropdown_gfxcard` (`name`) VALUES ('integrated');

# --------------------------------------------------------

#
# Table structure for table `dropdown_hdtype`
#

DROP TABLE IF EXISTS `dropdown_hdtype`;
CREATE TABLE `dropdown_hdtype` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_hdtype`
#

INSERT INTO `dropdown_hdtype` (`name`) VALUES ('IBM DTTA 35101');
INSERT INTO `dropdown_hdtype` (`name`) VALUES ('IBM DCAS 34330');

# --------------------------------------------------------

#
# Table structure for table `dropdown_iface`
#

DROP TABLE IF EXISTS `dropdown_iface`;
CREATE TABLE `dropdown_iface` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_iface`
#

INSERT INTO `dropdown_iface` (`name`) VALUES ('10Mbps Ethernet (UTP)');
INSERT INTO `dropdown_iface` (`name`) VALUES ('100Mbps Ethernet (UTP)');
INSERT INTO `dropdown_iface` (`name`) VALUES ('100Base FL');
INSERT INTO `dropdown_iface` (`name`) VALUES ('100Mbps FDDI');
INSERT INTO `dropdown_iface` (`name`) VALUES ('Frame Relay');
INSERT INTO `dropdown_iface` (`name`) VALUES ('ISDN');
INSERT INTO `dropdown_iface` (`name`) VALUES ('T1/E1 +');
INSERT INTO `dropdown_iface` (`name`) VALUES ('Serial Link');

# --------------------------------------------------------

#
# Table structure for table `dropdown_locations`
#

DROP TABLE IF EXISTS `dropdown_locations`;
CREATE TABLE `dropdown_locations` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_locations`
#

INSERT INTO `dropdown_locations` (`name`) VALUES ('1 ier etage');
INSERT INTO `dropdown_locations` (`name`) VALUES ('2nd etage');

# --------------------------------------------------------

#
# Table structure for table `dropdown_moboard`
#

DROP TABLE IF EXISTS `dropdown_moboard`;
CREATE TABLE `dropdown_moboard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_moboard`
#

INSERT INTO `dropdown_moboard` (`name`) VALUES ('Asus T2P4S');
INSERT INTO `dropdown_moboard` (`name`) VALUES ('Asus P2BX');
INSERT INTO `dropdown_moboard` (`name`) VALUES ('unknown');

# --------------------------------------------------------

#
# Table structure for table `dropdown_network`
#

DROP TABLE IF EXISTS `dropdown_network`;
CREATE TABLE `dropdown_network` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_network`
#

INSERT INTO `dropdown_network` (`name`) VALUES ('3Com (100Mbps)');
INSERT INTO `dropdown_network` (`name`) VALUES ('3Com (10Mbps)');
INSERT INTO `dropdown_network` (`name`) VALUES ('Intel (100Mbps)');
INSERT INTO `dropdown_network` (`name`) VALUES ('Intel (10Mbps)');
INSERT INTO `dropdown_network` (`name`) VALUES ('Generic 100Mbps Card');
INSERT INTO `dropdown_network` (`name`) VALUES ('Generic 10Mbps Card');
INSERT INTO `dropdown_network` (`name`) VALUES ('None');
INSERT INTO `dropdown_network` (`name`) VALUES ('AMD 10Mbps');
INSERT INTO `dropdown_network` (`name`) VALUES ('Realtek 10Mbps');
INSERT INTO `dropdown_network` (`name`) VALUES ('Realtek 100Mbps');
INSERT INTO `dropdown_network` (`name`) VALUES ('integrated');

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

INSERT INTO `dropdown_os` (`name`) VALUES ('Windows 2000');
INSERT INTO `dropdown_os` (`name`) VALUES ('Linux (Redhat 6.2)');
INSERT INTO `dropdown_os` (`name`) VALUES ('Linux (Slackware 7)');
INSERT INTO `dropdown_os` (`name`) VALUES ('Solaris');
INSERT INTO `dropdown_os` (`name`) VALUES ('Windows NT 4.0');
INSERT INTO `dropdown_os` (`name`) VALUES ('Windows 95a');
INSERT INTO `dropdown_os` (`name`) VALUES ('Other');
INSERT INTO `dropdown_os` (`name`) VALUES ('Windows 98');
INSERT INTO `dropdown_os` (`name`) VALUES ('MacOS');
INSERT INTO `dropdown_os` (`name`) VALUES ('Windows 95 OSR2');
INSERT INTO `dropdown_os` (`name`) VALUES ('Windows 98 SR2');
INSERT INTO `dropdown_os` (`name`) VALUES ('Debian woody 3.0');

# --------------------------------------------------------

#
# Table structure for table `dropdown_processor`
#

DROP TABLE IF EXISTS `dropdown_processor`;
CREATE TABLE `dropdown_processor` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_processor`
#

INSERT INTO `dropdown_processor` (`name`) VALUES ('Intel Pentium');
INSERT INTO `dropdown_processor` (`name`) VALUES ('Intel Pentium II');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD K6-1');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD K6-2');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD K6-3');
INSERT INTO `dropdown_processor` (`name`) VALUES ('PowerPC G3');
INSERT INTO `dropdown_processor` (`name`) VALUES ('Intel Pentium III');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD Athlon');
INSERT INTO `dropdown_processor` (`name`) VALUES ('68k (Motorola)');
INSERT INTO `dropdown_processor` (`name`) VALUES ('486 SX');
INSERT INTO `dropdown_processor` (`name`) VALUES ('486 DX');
INSERT INTO `dropdown_processor` (`name`) VALUES ('486 DX2/4');
INSERT INTO `dropdown_processor` (`name`) VALUES ('Intel Itanium');
INSERT INTO `dropdown_processor` (`name`) VALUES ('PowerPC G4');
INSERT INTO `dropdown_processor` (`name`) VALUES ('RS3000');
INSERT INTO `dropdown_processor` (`name`) VALUES ('RS10k');
INSERT INTO `dropdown_processor` (`name`) VALUES ('Alpha EV6.7');
INSERT INTO `dropdown_processor` (`name`) VALUES ('PowerPC 603ev');
INSERT INTO `dropdown_processor` (`name`) VALUES ('PowerPC 603');
INSERT INTO `dropdown_processor` (`name`) VALUES ('PowerPC 601');
INSERT INTO `dropdown_processor` (`name`) VALUES ('68040');
INSERT INTO `dropdown_processor` (`name`) VALUES ('68040');
INSERT INTO `dropdown_processor` (`name`) VALUES ('ULTRASparc II');
INSERT INTO `dropdown_processor` (`name`) VALUES ('Intel Pentium IV');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD Athlon');
INSERT INTO `dropdown_processor` (`name`) VALUES ('AMD Duron');

# --------------------------------------------------------

#
# Table structure for table `dropdown_ram`
#

DROP TABLE IF EXISTS `dropdown_ram`;
CREATE TABLE `dropdown_ram` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_ram`
#

INSERT INTO `dropdown_ram` (`name`) VALUES ('36pin SIMMS');
INSERT INTO `dropdown_ram` (`name`) VALUES ('72pin SIMMS (Fast Page)');
INSERT INTO `dropdown_ram` (`name`) VALUES ('72pin SIMMS (EDO)');
INSERT INTO `dropdown_ram` (`name`) VALUES ('Unbuffered DIMMs');
INSERT INTO `dropdown_ram` (`name`) VALUES ('DIMMs w/EEPROM');
INSERT INTO `dropdown_ram` (`name`) VALUES ('SDRAM DIMMs (<10ns)');
INSERT INTO `dropdown_ram` (`name`) VALUES ('ECC DIMMs');
INSERT INTO `dropdown_ram` (`name`) VALUES ('Other');
INSERT INTO `dropdown_ram` (`name`) VALUES ('iMac DIMMS');

# --------------------------------------------------------

#
# Table structure for table `dropdown_sndcard`
#

DROP TABLE IF EXISTS `dropdown_sndcard`;
CREATE TABLE `dropdown_sndcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Dumping data for table `dropdown_sndcard`
#

INSERT INTO `dropdown_sndcard` (`name`) VALUES ('Soundblaster 128 PCI');
INSERT INTO `dropdown_sndcard` (`name`) VALUES ('Soundblaster 16 PnP');
INSERT INTO `dropdown_sndcard` (`name`) VALUES ('integrated');

# --------------------------------------------------------

#
# Table structure for table `event_log`
#

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
) TYPE=MyISAM AUTO_INCREMENT=363 ;

#
# Dumping data for table `event_log`
#


# --------------------------------------------------------

#
# Table structure for table `followups`
#

DROP TABLE IF EXISTS `followups`;
CREATE TABLE `followups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL,
  `date` datetime default NULL,
  `author` varchar(200) default NULL,
  `contents` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Dumping data for table `followups`
#

INSERT INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (1, 1, '2003-09-18 00:53:35', 'tech', 'J\\\'ai été voir, je pense que la carte mere a grillé.');
INSERT INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (2, 1, '2003-09-18 00:54:06', 'tech', 'L\\\'alimentation est foutue, je vais tester la carte mere pour voir si elle est recuperable');
INSERT INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (3, 1, '2003-09-18 00:54:40', 'tech', 'Probleme reglé j\\\'ai seulement changé l\\\'alimentation.\r\nLe reste fonctionne tres bien.');
INSERT INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (4, 3, '2003-09-18 00:55:08', 'tech', 'Je pense que l\\\'on peux changer la souris.');
INSERT INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (5, 2, '2003-09-18 00:55:52', 'tech', 'Je suis passé, il faudra faire une restauration de windows NT4.');

# --------------------------------------------------------

#
# Table structure for table `inst_software`
#

DROP TABLE IF EXISTS `inst_software`;
CREATE TABLE `inst_software` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0',
  `license` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`license`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Dumping data for table `inst_software`
#

INSERT INTO `inst_software` (`ID`, `cID`, `license`) VALUES (2, 10, 7);
INSERT INTO `inst_software` (`ID`, `cID`, `license`) VALUES (1, 8, 8);
INSERT INTO `inst_software` (`ID`, `cID`, `license`) VALUES (3, 8, 6);
INSERT INTO `inst_software` (`ID`, `cID`, `license`) VALUES (4, 8, 9);

# --------------------------------------------------------

#
# Table structure for table `licenses`
#

DROP TABLE IF EXISTS `licenses`;
CREATE TABLE `licenses` (
  `ID` int(15) NOT NULL auto_increment,
  `sID` int(15) NOT NULL default '0',
  `serial` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;

#
# Dumping data for table `licenses`
#

INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (7, 4, '12-aa-asd-12-aa');
INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (6, 4, 'asd-asdf-asdf-12');
INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (4, 4, 'au-23-as-23-cd');
INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (5, 4, 'qw-as-23-0k-23-dg');
INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (8, 3, 'free');
INSERT INTO `licenses` (`ID`, `sID`, `serial`) VALUES (9, 5, 'free');

# --------------------------------------------------------

#
# Table structure for table `monitors`
#

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

#
# Dumping data for table `monitors`
#

INSERT INTO `monitors` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `comments`, `serial`, `otherserial`, `size`, `flags_micro`, `flags_speaker`, `flags_subd`, `flags_bnc`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', 'Nokia 445Xav', '1 ier etage', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0);
INSERT INTO `monitors` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `comments`, `serial`, `otherserial`, `size`, `flags_micro`, `flags_speaker`, `flags_subd`, `flags_bnc`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (2, 'Sony 19\'', '2003-09-18 00:14:50', 'Sony 200sf', '1 ier etage', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Table structure for table `networking`
#

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

#
# Dumping data for table `networking`
#

INSERT INTO `networking` (`ID`, `name`, `type`, `ram`, `location`, `serial`, `otherserial`, `contact`, `contact_num`, `date_mod`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (9, 'Dlink 450', 'Dlink Switch', '', '1 ier etage', '4586-puis-kioe', '', '', '', '0000-00-00 00:00:00', '', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Table structure for table `networking_ports`
#

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

#
# Dumping data for table `networking_ports`
#

INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (1, 8, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.26', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (2, 10, 1, '100Mbps Ethernet (UTP)', 1, '3com', '10.10.0.27', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (3, 15, 1, '100Mbps Ethernet (UTP)', 1, 'Generic', '10.10.0.28', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (4, 18, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.29', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (5, 9, 2, '100Mbps Ethernet (UTP)', 1, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (6, 9, 2, '100Mbps Ethernet (UTP)', 2, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (7, 9, 2, '100Mbps Ethernet (UTP)', 3, 'Dlink port', '10.10.0.1', '');
INSERT INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (8, 10, 2, 'Frame Relay', 0, '', '', '');

# --------------------------------------------------------

#
# Table structure for table `networking_wire`
#

DROP TABLE IF EXISTS `networking_wire`;
CREATE TABLE `networking_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Dumping data for table `networking_wire`
#

INSERT INTO `networking_wire` (`ID`, `end1`, `end2`) VALUES (1, 5, 1);
INSERT INTO `networking_wire` (`ID`, `end1`, `end2`) VALUES (2, 6, 2);
INSERT INTO `networking_wire` (`ID`, `end1`, `end2`) VALUES (3, 7, 3);

# --------------------------------------------------------

#
# Table structure for table `prefs`
#

DROP TABLE IF EXISTS `prefs`;
CREATE TABLE `prefs` (
  `user` varchar(80) NOT NULL default '',
  `tracking_order` enum('no','yes') NOT NULL default 'no',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user`)
) TYPE=MyISAM;

#
# Dumping data for table `prefs`
#

INSERT INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('glpi', 'yes', 'french');
INSERT INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('Helpdesk', 'no', 'french');
INSERT INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('normal', 'yes', 'english');
INSERT INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('tech', 'yes', 'french');
INSERT INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('post-only', 'yes', 'english');

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

#
# Dumping data for table `printers`
#

INSERT INTO `printers` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `serial`, `otherserial`, `flags_serial`, `flags_par`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`, `ramSize`) VALUES (1, 'HP laser', '2003-09-18 00:12:43', 'HP Laserjet 4050N', '1 ier etage', '', '', 'hp-jsgsj-658', '', 0, 1, 'Imprimante bureau du directeur', '0000-00-00', '0000-00-00', 0, '');
INSERT INTO `printers` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `serial`, `otherserial`, `flags_serial`, `flags_par`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`, `ramSize`) VALUES (2, 'HP deskjet', '2003-09-18 00:13:11', 'HP Deskjet 850c', '2nd etage', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0, '');

# --------------------------------------------------------

#
# Table structure for table `software`
#

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

#
# Dumping data for table `software`
#

INSERT INTO `software` (`ID`, `name`, `platform`, `version`, `location`, `comments`) VALUES (3, 'Acrobat PDF Viewer', 'Windows NT 4.0', '4', 'Admin', NULL);
INSERT INTO `software` (`ID`, `name`, `platform`, `version`, `location`, `comments`) VALUES (4, 'MS Windows NT', 'Windows NT 4.0', '4.0', 'Admin', NULL);
INSERT INTO `software` (`ID`, `name`, `platform`, `version`, `location`, `comments`) VALUES (5, 'Latex', 'Linux (Redhat 6.2)', '6.2', '1 ier etage', 'Latex');

# --------------------------------------------------------

#
# Table structure for table `templates`
#

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

#
# Dumping data for table `templates`
#

INSERT INTO `templates` (`ID`, `templname`, `name`, `type`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `moboard`, `sndcard`, `gfxcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (1, 'Blank Template', '', 'Generic x86 PC', 'Linux (Redhat 6.2)', '', '486 DX', '', 'Admin', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', 'Asus P2BX', 'Soundblaster 128 PCI', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `templates` (`ID`, `templname`, `name`, `type`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `moboard`, `sndcard`, `gfxcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (3, 'iMac', '', 'iMac 2nd Generation', 'MacOS', '9', 'PowerPC G3', '333', 'Admin', '', '', 'iMac DIMMS', '128', 'Generic 100Mbps Card', '6', '', '', 'Standard iMac', 'unknown', 'integrated', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO `templates` (`ID`, `templname`, `name`, `type`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `moboard`, `sndcard`, `gfxcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (7, 'test', '', 'Generic x86 PC', 'Debian woody 3.0', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', '', 'Asus P2BX', 'integrated', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Table structure for table `tracking`
#

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
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Dumping data for table `tracking`
#

INSERT INTO `tracking` (`ID`, `date`, `closedate`, `status`, `author`, `assign`, `computer`, `contents`, `priority`, `is_group`, `uemail`, `emailupdates`) VALUES (1, '2003-09-18 00:46:40', '2003-09-18 00:54:43', 'old', 'Helpdesk', 'tech', 8, 'Mon ordinateur ne s\\\'allume plus, et il ya des bruits byzarres', 3, 'no', '', '');
INSERT INTO `tracking` (`ID`, `date`, `closedate`, `status`, `author`, `assign`, `computer`, `contents`, `priority`, `is_group`, `uemail`, `emailupdates`) VALUES (2, '2003-09-18 00:48:19', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 10, 'Un message en anglais s\\\'affiche, je n\\\'y comprend rien, je n\\\'ose plus toucher à rien de peur de tout casser.\r\nVenez vite !!!!', 4, 'no', '', '');
INSERT INTO `tracking` (`ID`, `date`, `closedate`, `status`, `author`, `assign`, `computer`, `contents`, `priority`, `is_group`, `uemail`, `emailupdates`) VALUES (3, '2003-09-18 00:49:29', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 18, 'Ma souris se bloque sans arret, elle defile mal sur l\\\'ecran et elle glisse tres mal sur le tapis de souris.', 3, 'no', '', '');

# --------------------------------------------------------

#
# Table structure for table `type_computers`
#

DROP TABLE IF EXISTS `type_computers`;
CREATE TABLE `type_computers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `type_computers`
#

INSERT INTO `type_computers` (`name`) VALUES ('Generic x86 PC');
INSERT INTO `type_computers` (`name`) VALUES ('PowerMac G4');
INSERT INTO `type_computers` (`name`) VALUES ('iMac 2nd Generation');
INSERT INTO `type_computers` (`name`) VALUES ('PowerMac G3');

# --------------------------------------------------------

#
# Table structure for table `type_monitors`
#

DROP TABLE IF EXISTS `type_monitors`;
CREATE TABLE `type_monitors` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `type_monitors`
#

INSERT INTO `type_monitors` (`name`) VALUES ('Nokia 445Xav');
INSERT INTO `type_monitors` (`name`) VALUES ('Sony 200GDM');
INSERT INTO `type_monitors` (`name`) VALUES ('Sony 200sf');
INSERT INTO `type_monitors` (`name`) VALUES ('integrated');

# --------------------------------------------------------

#
# Table structure for table `type_networking`
#

DROP TABLE IF EXISTS `type_networking`;
CREATE TABLE `type_networking` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `type_networking`
#

INSERT INTO `type_networking` (`name`) VALUES ('Dlink Switch');

# --------------------------------------------------------

#
# Table structure for table `type_printers`
#

DROP TABLE IF EXISTS `type_printers`;
CREATE TABLE `type_printers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `type_printers`
#

INSERT INTO `type_printers` (`name`) VALUES ('HP Laserjet 4050N');
INSERT INTO `type_printers` (`name`) VALUES ('HP Laserjet 4+');
INSERT INTO `type_printers` (`name`) VALUES ('HP Deskjet 850c');

# --------------------------------------------------------

#
# Table structure for table `users`
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `name` varchar(80) NOT NULL default '',
  `password` varchar(80) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `location` varchar(100) NOT NULL default '',
  `phone` varchar(100) default NULL,
  `type` enum('normal','admin','post-only') NOT NULL default 'normal',
  `realname` varchar(255) NOT NULL default '',
  `can_assign_job` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`name`),
  KEY `type` (`type`)
) TYPE=MyISAM;

#
# Dumping data for table `users`
#

INSERT INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('Helpdesk', '14e43c2d31dcbdd1', '', 'Admin', NULL, 'post-only', 'Helpdesk Injector', 'no');
INSERT INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('glpi', '5b9b1ee2216a5ffe', '', '2nd etage', '', 'admin', 'glpi', 'yes');
INSERT INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('post-only', '3eb831c67be6aeda', '', '1 ier etage', NULL, 'post-only', 'post-only', 'no');
INSERT INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('tech', '37bd7c4221e8a247', '', '2nd etage', NULL, 'admin', 'technicien', 'yes');
INSERT INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('normal', '109e7883561b4202', '', '1 ier etage', NULL, 'normal', 'utilisateur normal', 'no');
    
