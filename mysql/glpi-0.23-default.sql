Database glpidb running on localhost

# phpMyAdmin MySQL-Dump
# version 2.2.3
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: May 02, 2004 at 10:20 PM
# Server version: 3.23.49
# PHP Version: 4.1.2
# Database : `glpidb`
# --------------------------------------------------------

#
# Table structure for table `computers`
#

DROP TABLE IF EXISTS computers;
CREATE TABLE computers (
  ID int(11) NOT NULL auto_increment,
  name varchar(200) NOT NULL default '',
  type varchar(100) NOT NULL default '',
  flags_server tinyint(4) NOT NULL default '0',
  os varchar(100) NOT NULL default '',
  osver varchar(20) NOT NULL default '',
  processor varchar(30) NOT NULL default '',
  processor_speed varchar(30) NOT NULL default '',
  location varchar(200) NOT NULL default '',
  serial varchar(200) NOT NULL default '',
  otherserial varchar(200) NOT NULL default '',
  ramtype varchar(200) NOT NULL default '',
  ram varchar(6) NOT NULL default '',
  network varchar(200) NOT NULL default '',
  hdspace varchar(6) NOT NULL default '',
  contact varchar(90) NOT NULL default '',
  contact_num varchar(90) NOT NULL default '',
  comments text NOT NULL,
  date_mod datetime default NULL,
  gfxcard varchar(255) NOT NULL default '',
  moboard varchar(255) NOT NULL default '',
  sndcard varchar(255) NOT NULL default '',
  hdtype varchar(255) NOT NULL default '',
  achat_date date NOT NULL default '0000-00-00',
  date_fin_garantie date NOT NULL default '0000-00-00',
  maintenance int(2) default '0',
  PRIMARY KEY  (ID),
  KEY location (location),
  KEY flags (flags_server)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `connect_wire`
#

DROP TABLE IF EXISTS connect_wire;
CREATE TABLE connect_wire (
  ID int(11) NOT NULL auto_increment,
  end1 int(11) NOT NULL default '0',
  end2 int(11) NOT NULL default '0',
  type tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_gfxcard`
#

DROP TABLE IF EXISTS dropdown_gfxcard;
CREATE TABLE dropdown_gfxcard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_hdtype`
#

DROP TABLE IF EXISTS dropdown_hdtype;
CREATE TABLE dropdown_hdtype (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_iface`
#

DROP TABLE IF EXISTS dropdown_iface;
CREATE TABLE dropdown_iface (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_locations`
#

DROP TABLE IF EXISTS dropdown_locations;
CREATE TABLE dropdown_locations (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_moboard`
#

DROP TABLE IF EXISTS dropdown_moboard;
CREATE TABLE dropdown_moboard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_network`
#

DROP TABLE IF EXISTS dropdown_network;
CREATE TABLE dropdown_network (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_os`
#

DROP TABLE IF EXISTS dropdown_os;
CREATE TABLE dropdown_os (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_processor`
#

DROP TABLE IF EXISTS dropdown_processor;
CREATE TABLE dropdown_processor (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_ram`
#

DROP TABLE IF EXISTS dropdown_ram;
CREATE TABLE dropdown_ram (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `dropdown_sndcard`
#

DROP TABLE IF EXISTS dropdown_sndcard;
CREATE TABLE dropdown_sndcard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `event_log`
#

DROP TABLE IF EXISTS event_log;
CREATE TABLE event_log (
  ID int(11) NOT NULL auto_increment,
  item int(11) NOT NULL default '0',
  itemtype varchar(10) NOT NULL default '',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  service varchar(20) default NULL,
  level tinyint(4) NOT NULL default '0',
  message text NOT NULL,
  PRIMARY KEY  (ID),
  KEY comp (item),
  KEY date (date)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `followups`
#

DROP TABLE IF EXISTS followups;
CREATE TABLE followups (
  ID int(11) NOT NULL auto_increment,
  tracking int(11) default NULL,
  date datetime default NULL,
  author varchar(200) default NULL,
  contents text,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `inst_software`
#

DROP TABLE IF EXISTS inst_software;
CREATE TABLE inst_software (
  ID int(11) NOT NULL auto_increment,
  cID int(11) NOT NULL default '0',
  license int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY cID (cID),
  KEY sID (license)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `licenses`
#

DROP TABLE IF EXISTS licenses;
CREATE TABLE licenses (
  ID int(15) NOT NULL auto_increment,
  sID int(15) NOT NULL default '0',
  serial varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `monitors`
#

DROP TABLE IF EXISTS monitors;
CREATE TABLE monitors (
  ID int(10) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  type varchar(255) NOT NULL default '',
  location varchar(255) NOT NULL default '',
  contact varchar(255) NOT NULL default '',
  contact_num varchar(255) NOT NULL default '',
  comments text NOT NULL,
  serial varchar(255) NOT NULL default '',
  otherserial varchar(255) NOT NULL default '',
  size int(3) NOT NULL default '0',
  flags_micro tinyint(4) NOT NULL default '0',
  flags_speaker tinyint(4) NOT NULL default '0',
  flags_subd tinyint(4) NOT NULL default '0',
  flags_bnc tinyint(4) NOT NULL default '0',
  achat_date date NOT NULL default '0000-00-00',
  date_fin_garantie date NOT NULL default '0000-00-00',
  maintenance int(2) default '0',
  PRIMARY KEY  (ID),
  KEY ID (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `networking`
#

DROP TABLE IF EXISTS networking;
CREATE TABLE networking (
  ID int(11) NOT NULL auto_increment,
  name varchar(30) NOT NULL default '',
  type varchar(30) NOT NULL default '',
  ram varchar(10) NOT NULL default '',
  location varchar(40) NOT NULL default '',
  serial varchar(50) NOT NULL default '',
  otherserial varchar(50) NOT NULL default '',
  contact varchar(30) NOT NULL default '',
  contact_num varchar(30) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  comments text NOT NULL,
  achat_date date NOT NULL default '0000-00-00',
  date_fin_garantie date NOT NULL default '0000-00-00',
  maintenance int(2) default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `networking_ports`
#

DROP TABLE IF EXISTS networking_ports;
CREATE TABLE networking_ports (
  ID int(11) NOT NULL auto_increment,
  on_device int(11) NOT NULL default '0',
  device_type tinyint(4) NOT NULL default '0',
  iface char(40) NOT NULL default '',
  logical_number int(11) NOT NULL default '0',
  name char(30) NOT NULL default '',
  ifaddr char(30) NOT NULL default '',
  ifmac char(30) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `networking_wire`
#

DROP TABLE IF EXISTS networking_wire;
CREATE TABLE networking_wire (
  ID int(11) NOT NULL auto_increment,
  end1 int(11) NOT NULL default '0',
  end2 int(11) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `prefs`
#

DROP TABLE IF EXISTS prefs;
CREATE TABLE prefs (
  user varchar(80) NOT NULL default '',
  tracking_order enum('no','yes') NOT NULL default 'no',
  language varchar(255) NOT NULL default '',
  PRIMARY KEY  (user)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `printers`
#

DROP TABLE IF EXISTS printers;
CREATE TABLE printers (
  ID int(10) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  type varchar(255) NOT NULL default '',
  location varchar(255) NOT NULL default '',
  contact varchar(255) NOT NULL default '',
  contact_num varchar(255) NOT NULL default '',
  serial varchar(255) NOT NULL default '',
  otherserial varchar(255) NOT NULL default '',
  flags_serial tinyint(4) NOT NULL default '0',
  flags_par tinyint(4) NOT NULL default '0',
  comments text NOT NULL,
  achat_date date NOT NULL default '0000-00-00',
  date_fin_garantie date NOT NULL default '0000-00-00',
  maintenance int(2) default '0',
  ramSize varchar(6) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY id (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `software`
#

DROP TABLE IF EXISTS software;
CREATE TABLE software (
  ID int(11) NOT NULL auto_increment,
  name varchar(200) NOT NULL default '',
  platform varchar(200) NOT NULL default '',
  version varchar(20) NOT NULL default '',
  location varchar(200) NOT NULL default '',
  comments text NOT NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `templates`
#

DROP TABLE IF EXISTS templates;
CREATE TABLE templates (
  ID int(11) NOT NULL auto_increment,
  templname varchar(200) NOT NULL default '',
  name varchar(200) NOT NULL default '',
  type varchar(200) NOT NULL default '',
  os varchar(200) NOT NULL default '',
  osver varchar(20) NOT NULL default '',
  processor varchar(200) NOT NULL default '',
  processor_speed varchar(100) NOT NULL default '',
  location varchar(200) NOT NULL default '',
  serial varchar(200) NOT NULL default '',
  otherserial varchar(200) NOT NULL default '',
  ramtype varchar(200) NOT NULL default '',
  ram varchar(20) NOT NULL default '',
  network varchar(200) NOT NULL default '',
  hdspace varchar(10) NOT NULL default '',
  contact varchar(200) NOT NULL default '',
  contact_num varchar(200) NOT NULL default '',
  comments text NOT NULL,
  moboard varchar(255) NOT NULL default '',
  sndcard varchar(255) NOT NULL default '',
  gfxcard varchar(255) NOT NULL default '',
  hdtype varchar(255) NOT NULL default '',
  achat_date date NOT NULL default '0000-00-00',
  date_fin_garantie date NOT NULL default '0000-00-00',
  maintenance int(2) default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `tracking`
#

DROP TABLE IF EXISTS tracking;
CREATE TABLE tracking (
  ID int(11) NOT NULL auto_increment,
  date datetime default NULL,
  closedate datetime NOT NULL default '0000-00-00 00:00:00',
  status enum('new','old') default NULL,
  author varchar(200) default NULL,
  assign varchar(200) default NULL,
  computer int(11) default NULL,
  contents text,
  priority tinyint(4) NOT NULL default '1',
  is_group enum('no','yes') NOT NULL default 'no',
  uemail varchar(100) default NULL,
  emailupdates varchar(4) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `type_computers`
#

DROP TABLE IF EXISTS type_computers;
CREATE TABLE type_computers (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `type_monitors`
#

DROP TABLE IF EXISTS type_monitors;
CREATE TABLE type_monitors (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `type_networking`
#

DROP TABLE IF EXISTS type_networking;
CREATE TABLE type_networking (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `type_printers`
#

DROP TABLE IF EXISTS type_printers;
CREATE TABLE type_printers (
  name varchar(255) default NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `users`
#

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  name varchar(80) NOT NULL default '',
  password varchar(80) NOT NULL default '',
  email varchar(80) NOT NULL default '',
  location varchar(100) NOT NULL default '',
  phone varchar(100) NOT NULL default '',
  type enum('normal','admin','post-only') NOT NULL default 'normal',
  realname varchar(255) NOT NULL default '',
  can_assign_job enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (name),
  KEY type (type)
) TYPE=MyISAM;

    

