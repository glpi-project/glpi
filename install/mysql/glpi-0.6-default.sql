#GLPI Dump database on 2005-09-06 14:25

### Dump table glpi_cartridges

DROP TABLE IF EXISTS glpi_cartridges;
CREATE TABLE glpi_cartridges (
    ID int(11) NOT NULL auto_increment,
    FK_glpi_cartridges_type int(11) DEFAULT '0' NOT NULL,
    FK_glpi_printers int(11) DEFAULT '0' NOT NULL,
    date_in date,
    date_use date,
    date_out date,
    pages int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_cartridges_type (FK_glpi_cartridges_type),
   KEY FK_glpi_printers (FK_glpi_printers)
) TYPE=MyISAM;


### Dump table glpi_cartridges_assoc

DROP TABLE IF EXISTS glpi_cartridges_assoc;
CREATE TABLE glpi_cartridges_assoc (
    ID int(11) NOT NULL auto_increment,
    FK_glpi_cartridges_type int(11) DEFAULT '0' NOT NULL,
    FK_glpi_type_printer int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_glpi_type_printer (FK_glpi_type_printer, FK_glpi_cartridges_type),
   KEY FK_glpi_cartridges_type (FK_glpi_cartridges_type),
   KEY FK_glpi_type_printer_2 (FK_glpi_type_printer)
) TYPE=MyISAM;


### Dump table glpi_cartridges_type

DROP TABLE IF EXISTS glpi_cartridges_type;
CREATE TABLE glpi_cartridges_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    ref varchar(255) NOT NULL,
    location int(11) DEFAULT '0' NOT NULL,
    type tinyint(4) DEFAULT '0' NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    tech_num int(11) DEFAULT '0',
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    comments text NOT NULL,
    alarm tinyint(4) DEFAULT '10' NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY tech_num (tech_num),
   KEY deleted (deleted)
) TYPE=MyISAM;


### Dump table glpi_computer_device

DROP TABLE IF EXISTS glpi_computer_device;
CREATE TABLE glpi_computer_device (
    ID int(11) NOT NULL auto_increment,
    specificity varchar(250) NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    FK_device int(11) DEFAULT '0' NOT NULL,
    FK_computers int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY device_type (device_type),
   KEY device_type_2 (device_type, FK_device),
   KEY FK_computers (FK_computers)
) TYPE=MyISAM;

INSERT INTO glpi_computer_device VALUES ('1','','8','1','19');
INSERT INTO glpi_computer_device VALUES ('2','','8','1','21');
INSERT INTO glpi_computer_device VALUES ('3','','8','4','8');
INSERT INTO glpi_computer_device VALUES ('4','','8','4','10');
INSERT INTO glpi_computer_device VALUES ('5','','8','4','15');
INSERT INTO glpi_computer_device VALUES ('6','','8','4','18');
INSERT INTO glpi_computer_device VALUES ('7','','8','4','20');
INSERT INTO glpi_computer_device VALUES ('8','20','4','1','10');
INSERT INTO glpi_computer_device VALUES ('9','20','4','1','15');
INSERT INTO glpi_computer_device VALUES ('10','20','4','1','18');
INSERT INTO glpi_computer_device VALUES ('11','6','4','1','20');
INSERT INTO glpi_computer_device VALUES ('12','10','4','2','8');
INSERT INTO glpi_computer_device VALUES ('13','','4','2','19');
INSERT INTO glpi_computer_device VALUES ('14','','4','2','21');
INSERT INTO glpi_computer_device VALUES ('15','','5','1','8');
INSERT INTO glpi_computer_device VALUES ('16','','5','1','10');
INSERT INTO glpi_computer_device VALUES ('17','','5','1','18');
INSERT INTO glpi_computer_device VALUES ('18','','5','1','19');
INSERT INTO glpi_computer_device VALUES ('19','','5','1','21');
INSERT INTO glpi_computer_device VALUES ('20','','5','5','15');
INSERT INTO glpi_computer_device VALUES ('21','','5','5','20');
INSERT INTO glpi_computer_device VALUES ('22','','1','2','19');
INSERT INTO glpi_computer_device VALUES ('23','','1','2','21');
INSERT INTO glpi_computer_device VALUES ('24','','1','3','8');
INSERT INTO glpi_computer_device VALUES ('25','','1','3','10');
INSERT INTO glpi_computer_device VALUES ('26','','1','3','15');
INSERT INTO glpi_computer_device VALUES ('27','','1','3','18');
INSERT INTO glpi_computer_device VALUES ('28','','1','3','20');
INSERT INTO glpi_computer_device VALUES ('29','333','2','6','20');
INSERT INTO glpi_computer_device VALUES ('30','750','2','7','8');
INSERT INTO glpi_computer_device VALUES ('31','750','2','7','10');
INSERT INTO glpi_computer_device VALUES ('32','750','2','7','15');
INSERT INTO glpi_computer_device VALUES ('33','750','2','7','18');
INSERT INTO glpi_computer_device VALUES ('34','','2','11','19');
INSERT INTO glpi_computer_device VALUES ('35','','2','11','21');
INSERT INTO glpi_computer_device VALUES ('36','','3','1','19');
INSERT INTO glpi_computer_device VALUES ('37','','3','1','21');
INSERT INTO glpi_computer_device VALUES ('38','512','3','6','8');
INSERT INTO glpi_computer_device VALUES ('39','128','3','6','10');
INSERT INTO glpi_computer_device VALUES ('40','512','3','6','15');
INSERT INTO glpi_computer_device VALUES ('41','128','3','6','18');
INSERT INTO glpi_computer_device VALUES ('42','128','3','9','20');
INSERT INTO glpi_computer_device VALUES ('43','','9','1','19');
INSERT INTO glpi_computer_device VALUES ('44','','9','3','8');
INSERT INTO glpi_computer_device VALUES ('45','','9','3','10');
INSERT INTO glpi_computer_device VALUES ('46','','9','3','15');
INSERT INTO glpi_computer_device VALUES ('47','','9','3','18');
INSERT INTO glpi_computer_device VALUES ('48','','9','3','20');
INSERT INTO glpi_computer_device VALUES ('49','','9','3','21');

### Dump table glpi_computers

DROP TABLE IF EXISTS glpi_computers;
CREATE TABLE glpi_computers (
    ID int(11) NOT NULL auto_increment,
    name varchar(200) NOT NULL,
    serial varchar(200) NOT NULL,
    otherserial varchar(200) NOT NULL,
    contact varchar(90) NOT NULL,
    contact_num varchar(90) NOT NULL,
    tech_num int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    date_mod datetime,
    os int(11),
    location int(11),
    domain int(11) DEFAULT '0' NOT NULL,
    network int(11) DEFAULT '0' NOT NULL,
    model int(11),
    type int(11),
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(200),
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (ID),
   KEY location (location),
   KEY os (os),
   KEY type (model),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY date_mod (date_mod),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_computers VALUES ('8','Dell Inspiron 450','4586-sd6-fds','','Roger Rabbit','5462','0','','2003-09-18 00:15:44','5','1','0','0','1',NULL,'0',NULL,'0','N');
INSERT INTO glpi_computers VALUES ('10','Dell Inspiron 450','4598-jhd-545','','Peter Pan','8565','0','','2003-09-18 00:07:58','13','1','0','0','1',NULL,'0',NULL,'0','N');
INSERT INTO glpi_computers VALUES ('15','Dell Inspiron 450','4561-hsub-dfsnj','','Poppins Marry','6545','0','','2003-09-18 00:09:47','1','1','0','0','1',NULL,'0',NULL,'0','N');
INSERT INTO glpi_computers VALUES ('18','IBM 945gx','9854-5f-4s4f','','Jeannot Lapin','5465','0','','2003-09-18 00:05:07','2','1','0','0','1',NULL,'0',NULL,'0','N');
INSERT INTO glpi_computers VALUES ('19','','','','','','0','Empty Template',NULL,'2','0','0','0','1',NULL,'1','Blank Template','0','N');
INSERT INTO glpi_computers VALUES ('20','','','','','','0','Standard iMac',NULL,'9','0','0','0','3',NULL,'1','iMac','0','N');
INSERT INTO glpi_computers VALUES ('21','','','','','','0','',NULL,'12','1','0','0','1',NULL,'1','test','0','N');

### Dump table glpi_config

DROP TABLE IF EXISTS glpi_config;
CREATE TABLE glpi_config (
    ID int(11) NOT NULL auto_increment,
    ldap_port varchar(10) DEFAULT '389' NOT NULL,
    num_of_events varchar(200) NOT NULL,
    jobs_at_login varchar(200) NOT NULL,
    sendexpire varchar(200) NOT NULL,
    cut varchar(200) NOT NULL,
    expire_events varchar(200) NOT NULL,
    list_limit varchar(200) NOT NULL,
    version varchar(200) NOT NULL,
    logotxt varchar(200) NOT NULL,
    root_doc varchar(200) NOT NULL,
    event_loglevel varchar(200) NOT NULL,
    mailing varchar(200) NOT NULL,
    imap_auth_server varchar(200) NOT NULL,
    imap_host varchar(200) NOT NULL,
    ldap_host varchar(200) NOT NULL,
    ldap_basedn varchar(200) NOT NULL,
    ldap_rootdn varchar(200) NOT NULL,
    ldap_pass varchar(200) NOT NULL,
    admin_email varchar(200) NOT NULL,
    mailing_resa_all_admin varchar(200) DEFAULT '0' NOT NULL,
    mailing_resa_user varchar(200) DEFAULT '1' NOT NULL,
    mailing_resa_admin varchar(200) DEFAULT '1' NOT NULL,
    mailing_signature varchar(200) NOT NULL,
    mailing_new_admin varchar(200) NOT NULL,
    mailing_followup_admin varchar(200) NOT NULL,
    mailing_finish_admin varchar(200) NOT NULL,
    mailing_new_all_admin varchar(200) NOT NULL,
    mailing_followup_all_admin varchar(200) NOT NULL,
    mailing_finish_all_admin varchar(200) NOT NULL,
    mailing_new_all_normal varchar(200) NOT NULL,
    mailing_followup_all_normal varchar(200) NOT NULL,
    mailing_finish_all_normal varchar(200) NOT NULL,
    mailing_new_attrib varchar(200) NOT NULL,
    mailing_followup_attrib varchar(200) NOT NULL,
    mailing_finish_attrib varchar(200) NOT NULL,
    mailing_new_user varchar(200) NOT NULL,
    mailing_followup_user varchar(200) NOT NULL,
    mailing_finish_user varchar(200) NOT NULL,
    ldap_field_name varchar(200) NOT NULL,
    ldap_field_email varchar(200) NOT NULL,
    ldap_field_location varchar(200) NOT NULL,
    ldap_field_realname varchar(200) NOT NULL,
    ldap_field_phone varchar(200) NOT NULL,
    ldap_condition varchar(255) NOT NULL,
    permit_helpdesk varchar(200) NOT NULL,
    default_language varchar(255) DEFAULT 'french' NOT NULL,
    priority_1 varchar(200) DEFAULT '#fff2f2' NOT NULL,
    priority_2 varchar(200) DEFAULT '#ffe0e0' NOT NULL,
    priority_3 varchar(200) DEFAULT '#ffcece' NOT NULL,
    priority_4 varchar(200) DEFAULT '#ffbfbf' NOT NULL,
    priority_5 varchar(200) DEFAULT '#ffadad' NOT NULL,
    date_fiscale date DEFAULT '2005-12-31' NOT NULL,
    cartridges_alarm int(11) DEFAULT '10' NOT NULL,
    cas_host varchar(255) NOT NULL,
    cas_port varchar(255) NOT NULL,
    cas_uri varchar(255) NOT NULL,
    planning_begin time DEFAULT '08:00:00' NOT NULL,
    planning_end time DEFAULT '20:00:00' NOT NULL,
    auto_assign enum('0','1') DEFAULT '0' NOT NULL,
    utf8_conv int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_config VALUES ('1','389','10','1','1','80','30','15',' 0.6','GLPI powered by indepnet','/glpi','5','0','','','','','','','admsys@xxxxx.fr','0','1','1','SIGNATURE','1','1','1','1','0','0','0','0','0','0','0','0','1','1','1','uid','mail','physicaldeliveryofficename','cn','telephonenumber','','','french','#fff2f2','#ffe0e0','#ffcece','#ffbfbf','#ffadad','2005-12-31','10','','','','08:00:00','20:00:00','0','0');

### Dump table glpi_connect_wire

DROP TABLE IF EXISTS glpi_connect_wire;
CREATE TABLE glpi_connect_wire (
    ID int(11) NOT NULL auto_increment,
    end1 int(11) DEFAULT '0' NOT NULL,
    end2 int(11) DEFAULT '0' NOT NULL,
    type tinyint(4) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE end1_1 (end1, end2, type),
   KEY end1 (end1),
   KEY end2 (end2),
   KEY type (type)
) TYPE=MyISAM;


### Dump table glpi_consumables

DROP TABLE IF EXISTS glpi_consumables;
CREATE TABLE glpi_consumables (
    ID int(11) NOT NULL auto_increment,
    FK_glpi_consumables_type int(11),
    date_in date,
    date_out date,
   PRIMARY KEY (ID),
   KEY FK_glpi_cartridges_type (FK_glpi_consumables_type),
   KEY date_in (date_in),
   KEY date_out (date_out)
) TYPE=MyISAM;


### Dump table glpi_consumables_type

DROP TABLE IF EXISTS glpi_consumables_type;
CREATE TABLE glpi_consumables_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    ref varchar(255) NOT NULL,
    location int(11) DEFAULT '0' NOT NULL,
    type tinyint(4) DEFAULT '0' NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    tech_num int(11) DEFAULT '0',
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    comments text NOT NULL,
    alarm tinyint(4) DEFAULT '10' NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY tech_num (tech_num),
   KEY deleted (deleted)
) TYPE=MyISAM;


### Dump table glpi_contact_enterprise

DROP TABLE IF EXISTS glpi_contact_enterprise;
CREATE TABLE glpi_contact_enterprise (
    ID int(11) NOT NULL auto_increment,
    FK_enterprise int(11) DEFAULT '0' NOT NULL,
    FK_contact int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_enterprise (FK_enterprise, FK_contact),
   KEY FK_enterprise_2 (FK_enterprise),
   KEY FK_contact (FK_contact)
) TYPE=MyISAM;


### Dump table glpi_contacts

DROP TABLE IF EXISTS glpi_contacts;
CREATE TABLE glpi_contacts (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    phone varchar(30) NOT NULL,
    phone2 varchar(30) NOT NULL,
    fax varchar(30) NOT NULL,
    email varchar(255) NOT NULL,
    type tinyint(4) DEFAULT '1' NOT NULL,
    comments text NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
   PRIMARY KEY (ID),
   KEY deleted (deleted),
   KEY type (type)
) TYPE=MyISAM;


### Dump table glpi_contract_device

DROP TABLE IF EXISTS glpi_contract_device;
CREATE TABLE glpi_contract_device (
    ID int(11) NOT NULL auto_increment,
    FK_contract int(11) DEFAULT '0' NOT NULL,
    FK_device int(11) DEFAULT '0' NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_contract (FK_contract, FK_device, device_type),
   KEY FK_contract_2 (FK_contract),
   KEY FK_device (FK_device, device_type)
) TYPE=MyISAM;


### Dump table glpi_contract_enterprise

DROP TABLE IF EXISTS glpi_contract_enterprise;
CREATE TABLE glpi_contract_enterprise (
    ID int(11) NOT NULL auto_increment,
    FK_enterprise int(11) DEFAULT '0' NOT NULL,
    FK_contract int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_enterprise (FK_enterprise, FK_contract),
   KEY FK_enterprise_2 (FK_enterprise),
   KEY FK_contract (FK_contract)
) TYPE=MyISAM;


### Dump table glpi_contracts

DROP TABLE IF EXISTS glpi_contracts;
CREATE TABLE glpi_contracts (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    num varchar(255) NOT NULL,
    cost float DEFAULT '0' NOT NULL,
    contract_type int(11) DEFAULT '0' NOT NULL,
    begin_date date,
    duration tinyint(4) DEFAULT '0' NOT NULL,
    notice tinyint(4) DEFAULT '0' NOT NULL,
    periodicity tinyint(4) DEFAULT '0' NOT NULL,
    facturation tinyint(4) DEFAULT '0' NOT NULL,
    bill_type int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    compta_num varchar(255) NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    week_begin_hour time DEFAULT '00:00:00' NOT NULL,
    week_end_hour time DEFAULT '00:00:00' NOT NULL,
    saturday_begin_hour time DEFAULT '00:00:00' NOT NULL,
    saturday_end_hour time DEFAULT '00:00:00' NOT NULL,
    saturday enum('Y','N') DEFAULT 'N' NOT NULL,
    monday_begin_hour time DEFAULT '00:00:00' NOT NULL,
    monday_end_hour time DEFAULT '00:00:00' NOT NULL,
    monday enum('Y','N') DEFAULT 'N' NOT NULL,
    device_countmax int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY contract_type (contract_type),
   KEY begin_date (begin_date),
   KEY bill_type (bill_type)
) TYPE=MyISAM;


### Dump table glpi_device_case

DROP TABLE IF EXISTS glpi_device_case;
CREATE TABLE glpi_device_case (
    ID int(11) NOT NULL auto_increment,
    designation varchar(255) NOT NULL,
    format enum('Grand','Moyen','Micro') DEFAULT 'Moyen' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;


### Dump table glpi_device_control

DROP TABLE IF EXISTS glpi_device_control;
CREATE TABLE glpi_device_control (
    ID int(11) NOT NULL auto_increment,
    designation varchar(255) NOT NULL,
    interface enum('IDE','SATA','SCSI','USB') DEFAULT 'IDE' NOT NULL,
    raid enum('Y','N') DEFAULT 'Y' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;


### Dump table glpi_device_drive

DROP TABLE IF EXISTS glpi_device_drive;
CREATE TABLE glpi_device_drive (
    ID int(11) NOT NULL auto_increment,
    designation varchar(255) NOT NULL,
    is_writer enum('Y','N') DEFAULT 'Y' NOT NULL,
    speed varchar(30) NOT NULL,
    interface enum('IDE','SATA','SCSI') DEFAULT 'IDE' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;


### Dump table glpi_device_gfxcard

DROP TABLE IF EXISTS glpi_device_gfxcard;
CREATE TABLE glpi_device_gfxcard (
    ID int(11) NOT NULL auto_increment,
    designation varchar(120) NOT NULL,
    ram varchar(10) NOT NULL,
    interface enum('AGP','PCI','PCI-X','Other') DEFAULT 'AGP' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_gfxcard VALUES ('1','ATI Rage Pro 3D AGP','','AGP','','0','');
INSERT INTO glpi_device_gfxcard VALUES ('2','Matrox Millennium G400DH','','AGP','','0','');
INSERT INTO glpi_device_gfxcard VALUES ('3','S3 Trio 64V+','','AGP','','0','');
INSERT INTO glpi_device_gfxcard VALUES ('4','integrated','','AGP','','0','');

### Dump table glpi_device_hdd

DROP TABLE IF EXISTS glpi_device_hdd;
CREATE TABLE glpi_device_hdd (
    ID int(11) NOT NULL auto_increment,
    designation varchar(100) NOT NULL,
    rpm varchar(20) NOT NULL,
    interface int(11) DEFAULT '0' NOT NULL,
    cache varchar(20) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_hdd VALUES ('1','IBM DTTA 35101','','1','','','0','');
INSERT INTO glpi_device_hdd VALUES ('2','IBM DCAS 34330','','1','','','0','');

### Dump table glpi_device_iface

DROP TABLE IF EXISTS glpi_device_iface;
CREATE TABLE glpi_device_iface (
    ID int(11) NOT NULL auto_increment,
    designation varchar(120) NOT NULL,
    bandwidth varchar(20) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_iface VALUES ('1','3Com (100Mbps)','','','0','');
INSERT INTO glpi_device_iface VALUES ('2','3Com (10Mbps)','','','0','');
INSERT INTO glpi_device_iface VALUES ('3','Intel (100Mbps)','','','0','');
INSERT INTO glpi_device_iface VALUES ('4','Intel (10Mbps)','','','0','');
INSERT INTO glpi_device_iface VALUES ('5','Generic 100Mbps Card','','','0','');
INSERT INTO glpi_device_iface VALUES ('6','Generic 10Mbps Card','','','0','');
INSERT INTO glpi_device_iface VALUES ('7','None','','','0','');
INSERT INTO glpi_device_iface VALUES ('8','AMD 10Mbps','','','0','');
INSERT INTO glpi_device_iface VALUES ('9','Realtek 10Mbps','','','0','');
INSERT INTO glpi_device_iface VALUES ('10','Realtek 100Mbps','','','0','');
INSERT INTO glpi_device_iface VALUES ('11','integrated','','','0','');

### Dump table glpi_device_moboard

DROP TABLE IF EXISTS glpi_device_moboard;
CREATE TABLE glpi_device_moboard (
    ID int(11) NOT NULL auto_increment,
    designation varchar(100) NOT NULL,
    chipset varchar(120) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_moboard VALUES ('1','Asus T2P4S','','','0','');
INSERT INTO glpi_device_moboard VALUES ('2','Asus P2BX','','','0','');
INSERT INTO glpi_device_moboard VALUES ('3','unknown','','','0','');

### Dump table glpi_device_pci

DROP TABLE IF EXISTS glpi_device_pci;
CREATE TABLE glpi_device_pci (
    ID int(11) NOT NULL auto_increment,
    designation varchar(255) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;


### Dump table glpi_device_power

DROP TABLE IF EXISTS glpi_device_power;
CREATE TABLE glpi_device_power (
    ID int(11) NOT NULL auto_increment,
    designation varchar(255) NOT NULL,
    power varchar(20) NOT NULL,
    atx enum('Y','N') DEFAULT 'Y' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;


### Dump table glpi_device_processor

DROP TABLE IF EXISTS glpi_device_processor;
CREATE TABLE glpi_device_processor (
    ID int(11) NOT NULL auto_increment,
    designation varchar(120) NOT NULL,
    frequence int(11) DEFAULT '0' NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_processor VALUES ('1','Intel Pentium','0','','0','');
INSERT INTO glpi_device_processor VALUES ('2','Intel Pentium II','0','','0','');
INSERT INTO glpi_device_processor VALUES ('3','AMD K6-1','0','','0','');
INSERT INTO glpi_device_processor VALUES ('4','AMD K6-2','0','','0','');
INSERT INTO glpi_device_processor VALUES ('5','AMD K6-3','0','','0','');
INSERT INTO glpi_device_processor VALUES ('6','PowerPC G3','0','','0','');
INSERT INTO glpi_device_processor VALUES ('7','Intel Pentium III','0','','0','');
INSERT INTO glpi_device_processor VALUES ('8','AMD Athlon','0','','0','');
INSERT INTO glpi_device_processor VALUES ('9','68k (Motorola)','0','','0','');
INSERT INTO glpi_device_processor VALUES ('10','486 SX','0','','0','');
INSERT INTO glpi_device_processor VALUES ('11','486 DX','0','','0','');
INSERT INTO glpi_device_processor VALUES ('12','486 DX2/4','0','','0','');
INSERT INTO glpi_device_processor VALUES ('13','Intel Itanium','0','','0','');
INSERT INTO glpi_device_processor VALUES ('14','PowerPC G4','0','','0','');
INSERT INTO glpi_device_processor VALUES ('15','RS3000','0','','0','');
INSERT INTO glpi_device_processor VALUES ('16','RS10k','0','','0','');
INSERT INTO glpi_device_processor VALUES ('17','Alpha EV6.7','0','','0','');
INSERT INTO glpi_device_processor VALUES ('18','PowerPC 603ev','0','','0','');
INSERT INTO glpi_device_processor VALUES ('19','PowerPC 603','0','','0','');
INSERT INTO glpi_device_processor VALUES ('20','PowerPC 601','0','','0','');
INSERT INTO glpi_device_processor VALUES ('21','68040','0','','0','');
INSERT INTO glpi_device_processor VALUES ('22','68040','0','','0','');
INSERT INTO glpi_device_processor VALUES ('23','ULTRASparc II','0','','0','');
INSERT INTO glpi_device_processor VALUES ('24','Intel Pentium IV','0','','0','');
INSERT INTO glpi_device_processor VALUES ('25','AMD Athlon','0','','0','');
INSERT INTO glpi_device_processor VALUES ('26','AMD Duron','0','','0','');

### Dump table glpi_device_ram

DROP TABLE IF EXISTS glpi_device_ram;
CREATE TABLE glpi_device_ram (
    ID int(11) NOT NULL auto_increment,
    designation varchar(100) NOT NULL,
    frequence varchar(8) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
    type int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_ram VALUES ('1','36pin SIMMS','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('2','72pin SIMMS (Fast Page)','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('3','72pin SIMMS (EDO)','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('4','Unbuffered DIMMs','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('5','DIMMs w/EEPROM','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('6','SDRAM DIMMs (<10ns)','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('7','ECC DIMMs','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('8','Other','','','0','','1');
INSERT INTO glpi_device_ram VALUES ('9','iMac DIMMS','','','0','','1');

### Dump table glpi_device_sndcard

DROP TABLE IF EXISTS glpi_device_sndcard;
CREATE TABLE glpi_device_sndcard (
    ID int(11) NOT NULL auto_increment,
    designation varchar(120) NOT NULL,
    type varchar(100) NOT NULL,
    comment text NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    specif_default varchar(250) NOT NULL,
   PRIMARY KEY (ID),
   KEY FK_glpi_enterprise (FK_glpi_enterprise)
) TYPE=MyISAM;

INSERT INTO glpi_device_sndcard VALUES ('1','Soundblaster 128 PCI','','','0','');
INSERT INTO glpi_device_sndcard VALUES ('2','Soundblaster 16 PnP','','','0','');
INSERT INTO glpi_device_sndcard VALUES ('3','integrated','','','0','');

### Dump table glpi_doc_device

DROP TABLE IF EXISTS glpi_doc_device;
CREATE TABLE glpi_doc_device (
    ID int(11) NOT NULL auto_increment,
    FK_doc int(11) DEFAULT '0' NOT NULL,
    FK_device int(11) DEFAULT '0' NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_doc (FK_doc, FK_device, device_type),
   KEY FK_doc_2 (FK_doc),
   KEY FK_device (FK_device, device_type),
   KEY is_template (is_template)
) TYPE=MyISAM;


### Dump table glpi_docs

DROP TABLE IF EXISTS glpi_docs;
CREATE TABLE glpi_docs (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    filename varchar(255) NOT NULL,
    rubrique int(11) DEFAULT '0' NOT NULL,
    mime varchar(30) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    comment text NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    link varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY rubrique (rubrique),
   KEY deleted (deleted),
   KEY date_mod (date_mod)
) TYPE=MyISAM;


### Dump table glpi_dropdown_cartridge_type

DROP TABLE IF EXISTS glpi_dropdown_cartridge_type;
CREATE TABLE glpi_dropdown_cartridge_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_cartridge_type VALUES ('1','Jet-Encre');
INSERT INTO glpi_dropdown_cartridge_type VALUES ('2','Toner');
INSERT INTO glpi_dropdown_cartridge_type VALUES ('3','Ruban');

### Dump table glpi_dropdown_consumable_type

DROP TABLE IF EXISTS glpi_dropdown_consumable_type;
CREATE TABLE glpi_dropdown_consumable_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_contact_type

DROP TABLE IF EXISTS glpi_dropdown_contact_type;
CREATE TABLE glpi_dropdown_contact_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_contact_type VALUES ('1','Technicien');
INSERT INTO glpi_dropdown_contact_type VALUES ('2','Commercial');

### Dump table glpi_dropdown_contract_type

DROP TABLE IF EXISTS glpi_dropdown_contract_type;
CREATE TABLE glpi_dropdown_contract_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_contract_type VALUES ('1','Pr&#234;t');
INSERT INTO glpi_dropdown_contract_type VALUES ('2','Location');
INSERT INTO glpi_dropdown_contract_type VALUES ('3','Leasing');
INSERT INTO glpi_dropdown_contract_type VALUES ('4','Assurances');
INSERT INTO glpi_dropdown_contract_type VALUES ('5','Maintenance Hardware');
INSERT INTO glpi_dropdown_contract_type VALUES ('6','Maintenance Software');
INSERT INTO glpi_dropdown_contract_type VALUES ('7','Prestation');

### Dump table glpi_dropdown_domain

DROP TABLE IF EXISTS glpi_dropdown_domain;
CREATE TABLE glpi_dropdown_domain (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_enttype

DROP TABLE IF EXISTS glpi_dropdown_enttype;
CREATE TABLE glpi_dropdown_enttype (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_firmware

DROP TABLE IF EXISTS glpi_dropdown_firmware;
CREATE TABLE glpi_dropdown_firmware (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_hdd_type

DROP TABLE IF EXISTS glpi_dropdown_hdd_type;
CREATE TABLE glpi_dropdown_hdd_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_hdd_type VALUES ('1','IDE');
INSERT INTO glpi_dropdown_hdd_type VALUES ('2','SATA');
INSERT INTO glpi_dropdown_hdd_type VALUES ('3','SCSI');

### Dump table glpi_dropdown_iface

DROP TABLE IF EXISTS glpi_dropdown_iface;
CREATE TABLE glpi_dropdown_iface (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_iface VALUES ('1','10Mbps Ethernet (UTP)');
INSERT INTO glpi_dropdown_iface VALUES ('2','100Mbps Ethernet (UTP)');
INSERT INTO glpi_dropdown_iface VALUES ('3','100Base FL');
INSERT INTO glpi_dropdown_iface VALUES ('4','100Mbps FDDI');
INSERT INTO glpi_dropdown_iface VALUES ('5','Frame Relay');
INSERT INTO glpi_dropdown_iface VALUES ('6','ISDN');
INSERT INTO glpi_dropdown_iface VALUES ('7','T1/E1 +');
INSERT INTO glpi_dropdown_iface VALUES ('8','Serial Link');

### Dump table glpi_dropdown_kbcategories

DROP TABLE IF EXISTS glpi_dropdown_kbcategories;
CREATE TABLE glpi_dropdown_kbcategories (
    ID int(11) NOT NULL auto_increment,
    parentID int(11) DEFAULT '0' NOT NULL,
    name varchar(255) NOT NULL,
    completename text NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE parentID_2 (parentID, name),
   KEY parentID (parentID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_locations

DROP TABLE IF EXISTS glpi_dropdown_locations;
CREATE TABLE glpi_dropdown_locations (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    parentID int(11) DEFAULT '0' NOT NULL,
    completename text NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE name (name, parentID),
   KEY parentID (parentID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_locations VALUES ('1','1 ier etage','0','1 ier etage');
INSERT INTO glpi_dropdown_locations VALUES ('2','2nd etage','0','2nd etage');

### Dump table glpi_dropdown_model

DROP TABLE IF EXISTS glpi_dropdown_model;
CREATE TABLE glpi_dropdown_model (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_model VALUES ('1','Generic x86 PC');
INSERT INTO glpi_dropdown_model VALUES ('2','PowerMac G4');
INSERT INTO glpi_dropdown_model VALUES ('3','iMac 2nd Generation');
INSERT INTO glpi_dropdown_model VALUES ('4','PowerMac G3');

### Dump table glpi_dropdown_netpoint

DROP TABLE IF EXISTS glpi_dropdown_netpoint;
CREATE TABLE glpi_dropdown_netpoint (
    ID int(11) NOT NULL auto_increment,
    location int(11) DEFAULT '0' NOT NULL,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY location (location)
) TYPE=MyISAM;


### Dump table glpi_dropdown_network

DROP TABLE IF EXISTS glpi_dropdown_network;
CREATE TABLE glpi_dropdown_network (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_os

DROP TABLE IF EXISTS glpi_dropdown_os;
CREATE TABLE glpi_dropdown_os (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_os VALUES ('1','Windows 2000 - SP2');
INSERT INTO glpi_dropdown_os VALUES ('2','Linux (Redhat 6.2)');
INSERT INTO glpi_dropdown_os VALUES ('3','Linux (Slackware 7)');
INSERT INTO glpi_dropdown_os VALUES ('4','Solaris');
INSERT INTO glpi_dropdown_os VALUES ('5','Windows NT 4.0');
INSERT INTO glpi_dropdown_os VALUES ('6','Windows 95a');
INSERT INTO glpi_dropdown_os VALUES ('7','Other');
INSERT INTO glpi_dropdown_os VALUES ('8','Windows 98');
INSERT INTO glpi_dropdown_os VALUES ('9','MacOS - 9');
INSERT INTO glpi_dropdown_os VALUES ('10','Windows 95 OSR2');
INSERT INTO glpi_dropdown_os VALUES ('11','Windows 98 SR2');
INSERT INTO glpi_dropdown_os VALUES ('12','Debian woody 3.0');
INSERT INTO glpi_dropdown_os VALUES ('13','Windows NT 4.0 - SP3');

### Dump table glpi_dropdown_ram_type

DROP TABLE IF EXISTS glpi_dropdown_ram_type;
CREATE TABLE glpi_dropdown_ram_type (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_ram_type VALUES ('1','EDO');
INSERT INTO glpi_dropdown_ram_type VALUES ('2','DDR');
INSERT INTO glpi_dropdown_ram_type VALUES ('3','SDRAM');
INSERT INTO glpi_dropdown_ram_type VALUES ('4','SDRAM-2');

### Dump table glpi_dropdown_rubdocs

DROP TABLE IF EXISTS glpi_dropdown_rubdocs;
CREATE TABLE glpi_dropdown_rubdocs (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_state

DROP TABLE IF EXISTS glpi_dropdown_state;
CREATE TABLE glpi_dropdown_state (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_dropdown_state VALUES ('1','RÃ©paration');

### Dump table glpi_dropdown_tracking_category

DROP TABLE IF EXISTS glpi_dropdown_tracking_category;
CREATE TABLE glpi_dropdown_tracking_category (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_dropdown_vlan

DROP TABLE IF EXISTS glpi_dropdown_vlan;
CREATE TABLE glpi_dropdown_vlan (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_enterprises

DROP TABLE IF EXISTS glpi_enterprises;
CREATE TABLE glpi_enterprises (
    ID int(11) NOT NULL auto_increment,
    name varchar(50) NOT NULL,
    type int(11) DEFAULT '0' NOT NULL,
    address text NOT NULL,
    website varchar(100) NOT NULL,
    phonenumber varchar(20) NOT NULL,
    comments text NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    fax varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY deleted (deleted),
   KEY type (type)
) TYPE=MyISAM;


### Dump table glpi_event_log

DROP TABLE IF EXISTS glpi_event_log;
CREATE TABLE glpi_event_log (
    ID int(11) NOT NULL auto_increment,
    item int(11) DEFAULT '0' NOT NULL,
    itemtype varchar(20) NOT NULL,
    date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    service varchar(20),
    level tinyint(4) DEFAULT '0' NOT NULL,
    message text NOT NULL,
   PRIMARY KEY (ID),
   KEY comp (item),
   KEY date (date),
   KEY itemtype (itemtype)
) TYPE=MyISAM;

INSERT INTO glpi_event_log VALUES ('380','-1','system','2005-09-06 14:25:36','login','3','glpi logged in from 127.0.0.1.');

### Dump table glpi_followups

DROP TABLE IF EXISTS glpi_followups;
CREATE TABLE glpi_followups (
    ID int(11) NOT NULL auto_increment,
    tracking int(11),
    date datetime,
    author int(11) DEFAULT '0' NOT NULL,
    contents text,
   PRIMARY KEY (ID),
   KEY tracking (tracking),
   KEY author (author),
   KEY date (date)
) TYPE=MyISAM;

INSERT INTO glpi_followups VALUES ('1','1','2003-09-18 00:53:35','4','J\'ai été voir, je pense que la carte mere a grillé.');
INSERT INTO glpi_followups VALUES ('2','1','2003-09-18 00:54:06','4','L\'alimentation est foutue, je vais tester la carte mere pour voir si elle est recuperable');

### Dump table glpi_infocoms

DROP TABLE IF EXISTS glpi_infocoms;
CREATE TABLE glpi_infocoms (
    ID int(11) NOT NULL auto_increment,
    FK_device int(11) DEFAULT '0' NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    buy_date date DEFAULT '0000-00-00' NOT NULL,
    use_date date DEFAULT '0000-00-00' NOT NULL,
    warranty_duration tinyint(4) DEFAULT '0' NOT NULL,
    warranty_info varchar(255) NOT NULL,
    FK_enterprise int(11),
    num_commande varchar(50) NOT NULL,
    bon_livraison varchar(50) NOT NULL,
    num_immo varchar(50) NOT NULL,
    value float DEFAULT '0' NOT NULL,
    warranty_value float,
    amort_time tinyint(4) DEFAULT '0' NOT NULL,
    amort_type varchar(20) NOT NULL,
    amort_coeff float DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    facture varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_device (FK_device, device_type),
   KEY FK_enterprise (FK_enterprise),
   KEY buy_date (buy_date)
) TYPE=MyISAM;

INSERT INTO glpi_infocoms VALUES ('1','18','1','2001-09-24','0000-00-00','12','',NULL,'','','','0',NULL,'0','','0','','');

### Dump table glpi_inst_software

DROP TABLE IF EXISTS glpi_inst_software;
CREATE TABLE glpi_inst_software (
    ID int(11) NOT NULL auto_increment,
    cID int(11) DEFAULT '0' NOT NULL,
    license int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY cID (cID),
   KEY sID (license)
) TYPE=MyISAM;

INSERT INTO glpi_inst_software VALUES ('2','10','7');
INSERT INTO glpi_inst_software VALUES ('1','8','8');
INSERT INTO glpi_inst_software VALUES ('3','8','6');
INSERT INTO glpi_inst_software VALUES ('4','8','9');

### Dump table glpi_kbitems

DROP TABLE IF EXISTS glpi_kbitems;
CREATE TABLE glpi_kbitems (
    ID int(11) NOT NULL auto_increment,
    categoryID int(11) DEFAULT '0' NOT NULL,
    question text NOT NULL,
    answer text NOT NULL,
    faq enum('yes','no') DEFAULT 'no' NOT NULL,
   PRIMARY KEY (ID),
   KEY categoryID (categoryID)
) TYPE=MyISAM;


### Dump table glpi_licenses

DROP TABLE IF EXISTS glpi_licenses;
CREATE TABLE glpi_licenses (
    ID int(15) NOT NULL auto_increment,
    sID int(15) DEFAULT '0' NOT NULL,
    serial varchar(255) NOT NULL,
    expire date,
    oem enum('N','Y') DEFAULT 'N' NOT NULL,
    oem_computer int(11) DEFAULT '0' NOT NULL,
    buy enum('Y','N') DEFAULT 'Y' NOT NULL,
   PRIMARY KEY (ID),
   KEY sID (sID)
) TYPE=MyISAM;

INSERT INTO glpi_licenses VALUES ('7','4','12-aa-asd-12-aa',NULL,'N','0','Y');
INSERT INTO glpi_licenses VALUES ('6','4','asd-asdf-asdf-12',NULL,'N','0','Y');
INSERT INTO glpi_licenses VALUES ('4','4','au-23-as-23-cd',NULL,'N','0','Y');
INSERT INTO glpi_licenses VALUES ('5','4','qw-as-23-0k-23-dg',NULL,'N','0','Y');
INSERT INTO glpi_licenses VALUES ('8','3','free',NULL,'N','0','Y');
INSERT INTO glpi_licenses VALUES ('9','5','free',NULL,'N','0','Y');

### Dump table glpi_links

DROP TABLE IF EXISTS glpi_links;
CREATE TABLE glpi_links (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_links_device

DROP TABLE IF EXISTS glpi_links_device;
CREATE TABLE glpi_links_device (
    ID int(11) NOT NULL auto_increment,
    FK_links int(11) DEFAULT '0' NOT NULL,
    device_type int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE device_type_2 (device_type, FK_links),
   KEY device_type (device_type),
   KEY FK_links (FK_links)
) TYPE=MyISAM;


### Dump table glpi_monitors

DROP TABLE IF EXISTS glpi_monitors;
CREATE TABLE glpi_monitors (
    ID int(10) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    tech_num int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    size int(3) DEFAULT '0' NOT NULL,
    flags_micro tinyint(4) DEFAULT '0' NOT NULL,
    flags_speaker tinyint(4) DEFAULT '0' NOT NULL,
    flags_subd tinyint(4) DEFAULT '0' NOT NULL,
    flags_bnc tinyint(4) DEFAULT '0' NOT NULL,
    location int(11),
    type int(11),
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    is_global enum('0','1') DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(255),
   PRIMARY KEY (ID),
   KEY ID (ID),
   KEY type (type),
   KEY location (location),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_monitors VALUES ('3','nokia 20\'','2003-09-18 00:14:14','','','0','Ecran infographiste','','','20','1','1','1','0','1','1','0','0','N','0',NULL);
INSERT INTO glpi_monitors VALUES ('2','Sony 19\'','2003-09-18 00:14:50','','','0','ecran documentation','','','19','0','0','1','1','1','3','0','0','N','0',NULL);
INSERT INTO glpi_monitors VALUES ('4','','0000-00-00 00:00:00','','','0','','','','0','0','0','0','0',NULL,NULL,'0','0','N','1','Blank Template');

### Dump table glpi_networking

DROP TABLE IF EXISTS glpi_networking;
CREATE TABLE glpi_networking (
    ID int(11) NOT NULL auto_increment,
    name varchar(30) NOT NULL,
    ram varchar(10) NOT NULL,
    serial varchar(50) NOT NULL,
    otherserial varchar(50) NOT NULL,
    contact varchar(30) NOT NULL,
    contact_num varchar(30) NOT NULL,
    tech_num int(11) DEFAULT '0' NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    comments text NOT NULL,
    location int(11),
    domain int(11) DEFAULT '0' NOT NULL,
    network int(11) DEFAULT '0' NOT NULL,
    type int(11),
    firmware int(11),
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(255),
    ifmac varchar(30) NOT NULL,
    ifaddr varchar(30) NOT NULL,
   PRIMARY KEY (ID),
   KEY location (location),
   KEY type (type),
   KEY firmware (firmware),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_networking VALUES ('9','Dlink 450','','4586-puis-kioe','','','','0','0000-00-00 00:00:00','','1','0','0','1',NULL,'0','N','0',NULL,'','');
INSERT INTO glpi_networking VALUES ('10','','','','','','','0','0000-00-00 00:00:00','',NULL,'0','0',NULL,NULL,'0','N','1','Blank Template','','');

### Dump table glpi_networking_ports

DROP TABLE IF EXISTS glpi_networking_ports;
CREATE TABLE glpi_networking_ports (
    ID int(11) NOT NULL auto_increment,
    on_device int(11) DEFAULT '0' NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    logical_number int(11) DEFAULT '0' NOT NULL,
    name char(30) NOT NULL,
    ifaddr char(30) NOT NULL,
    ifmac char(30) NOT NULL,
    iface int(11),
    netpoint int(11),
   PRIMARY KEY (ID),
   KEY on_device (on_device, device_type),
   KEY netpoint (netpoint)
) TYPE=MyISAM;

INSERT INTO glpi_networking_ports VALUES ('1','8','1','1','3Com','10.10.0.26','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('2','10','1','1','3com','10.10.0.27','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('3','15','1','1','Generic','10.10.0.28','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('4','18','1','1','3Com','10.10.0.29','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('5','9','2','1','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('6','9','2','2','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('7','9','2','3','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('8','10','2','0','','','','5',NULL);

### Dump table glpi_networking_vlan

DROP TABLE IF EXISTS glpi_networking_vlan;
CREATE TABLE glpi_networking_vlan (
    ID int(11) NOT NULL auto_increment,
    FK_port int(11) DEFAULT '0' NOT NULL,
    FK_vlan int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE FK_port_2 (FK_port, FK_vlan),
   KEY FK_port (FK_port),
   KEY FK_vlan (FK_vlan)
) TYPE=MyISAM;


### Dump table glpi_networking_wire

DROP TABLE IF EXISTS glpi_networking_wire;
CREATE TABLE glpi_networking_wire (
    ID int(11) NOT NULL auto_increment,
    end1 int(11) DEFAULT '0' NOT NULL,
    end2 int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE end1_1 (end1, end2),
   KEY end1 (end1),
   KEY end2 (end2)
) TYPE=MyISAM;

INSERT INTO glpi_networking_wire VALUES ('1','5','1');
INSERT INTO glpi_networking_wire VALUES ('2','6','2');
INSERT INTO glpi_networking_wire VALUES ('3','7','3');

### Dump table glpi_peripherals

DROP TABLE IF EXISTS glpi_peripherals;
CREATE TABLE glpi_peripherals (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    tech_num int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    location int(11) DEFAULT '0' NOT NULL,
    type int(11) DEFAULT '0' NOT NULL,
    brand varchar(255) NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    is_global enum('0','1') DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(255),
   PRIMARY KEY (ID),
   KEY type (type),
   KEY location (location),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_peripherals VALUES ('1','','0000-00-00 00:00:00','','','0','','','','0','0','','0','0','N','1','Blank Template');

### Dump table glpi_printers

DROP TABLE IF EXISTS glpi_printers;
CREATE TABLE glpi_printers (
    ID int(10) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    tech_num int(11) DEFAULT '0' NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    flags_serial tinyint(4) DEFAULT '0' NOT NULL,
    flags_par tinyint(4) DEFAULT '0' NOT NULL,
    flags_usb tinyint(4) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    ramSize varchar(6) NOT NULL,
    location int(11),
    domain int(11) DEFAULT '0' NOT NULL,
    network int(11) DEFAULT '0' NOT NULL,
    type int(11),
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(255),
    initial_pages varchar(30) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY id (ID),
   KEY location (location),
   KEY type (type),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_printers VALUES ('1','HP laser','2003-09-18 00:12:43','','','0','hp-jsgsj-658','','0','1','0','Imprimante bureau du directeur','','1','0','0','1','0','N','0',NULL,'0');
INSERT INTO glpi_printers VALUES ('2','HP deskjet','2003-09-18 00:13:11','','','0','45dskjs-ds','','0','1','0','Imprimante documentation','','2','0','0','3','0','N','0',NULL,'0');
INSERT INTO glpi_printers VALUES ('3','','0000-00-00 00:00:00','','','0','','','0','0','0','','',NULL,'0','0',NULL,'0','N','1','Blank Template','0');

### Dump table glpi_reservation_item

DROP TABLE IF EXISTS glpi_reservation_item;
CREATE TABLE glpi_reservation_item (
    ID int(11) NOT NULL auto_increment,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    id_device int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
   PRIMARY KEY (ID),
   KEY device_type (device_type)
) TYPE=MyISAM;

INSERT INTO glpi_reservation_item VALUES ('1','1','10','Pile Poil');

### Dump table glpi_reservation_resa

DROP TABLE IF EXISTS glpi_reservation_resa;
CREATE TABLE glpi_reservation_resa (
    ID bigint(20) NOT NULL auto_increment,
    id_item int(11) DEFAULT '0' NOT NULL,
    begin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    id_user int(11) DEFAULT '0' NOT NULL,
    comment varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY id_item (id_item),
   KEY id_user (id_user),
   KEY begin (begin),
   KEY end (end)
) TYPE=MyISAM;


### Dump table glpi_software

DROP TABLE IF EXISTS glpi_software;
CREATE TABLE glpi_software (
    ID int(11) NOT NULL auto_increment,
    name varchar(200) NOT NULL,
    version varchar(20) NOT NULL,
    comments text,
    location int(11),
    tech_num int(11) DEFAULT '0' NOT NULL,
    platform int(11),
    is_update enum('N','Y') DEFAULT 'N' NOT NULL,
    update_software int(11) DEFAULT '-1' NOT NULL,
    FK_glpi_enterprise int(11) DEFAULT '0' NOT NULL,
    deleted enum('Y','N') DEFAULT 'N' NOT NULL,
    is_template enum('0','1') DEFAULT '0' NOT NULL,
    tplname varchar(255),
    date_mod datetime,
   PRIMARY KEY (ID),
   KEY platform (platform),
   KEY location (location),
   KEY FK_glpi_enterprise (FK_glpi_enterprise),
   KEY deleted (deleted),
   KEY is_template (is_template),
   KEY date_mod (date_mod),
   KEY tech_num (tech_num)
) TYPE=MyISAM;

INSERT INTO glpi_software VALUES ('3','Acrobat PDF Viewer','4',NULL,NULL,'0','5','N','-1','0','N','0',NULL,NULL);
INSERT INTO glpi_software VALUES ('4','MS Windows NT','4.0',NULL,NULL,'0','5','N','-1','0','N','0',NULL,NULL);
INSERT INTO glpi_software VALUES ('5','Latex','6.2','Latex','1','0','2','N','-1','0','N','0',NULL,NULL);
INSERT INTO glpi_software VALUES ('6','','',NULL,NULL,'0',NULL,'N','-1','0','N','1','Blank Template',NULL);

### Dump table glpi_state_item

DROP TABLE IF EXISTS glpi_state_item;
CREATE TABLE glpi_state_item (
    ID int(11) NOT NULL auto_increment,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    id_device int(11) DEFAULT '0' NOT NULL,
    state int(11) DEFAULT '1',
    is_template enum('0','1') DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY device_type (device_type),
   KEY device_type_2 (device_type, id_device)
) TYPE=MyISAM;


### Dump table glpi_tracking

DROP TABLE IF EXISTS glpi_tracking;
CREATE TABLE glpi_tracking (
    ID int(11) NOT NULL auto_increment,
    date datetime,
    closedate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    status enum('new','old'),
    author int(11) DEFAULT '0' NOT NULL,
    assign int(11) DEFAULT '0' NOT NULL,
    assign_type tinyint(4) DEFAULT '0' NOT NULL,
    device_type int(11) DEFAULT '1' NOT NULL,
    computer int(11),
    contents text,
    priority tinyint(4) DEFAULT '1' NOT NULL,
    is_group enum('no','yes') DEFAULT 'no' NOT NULL,
    uemail varchar(100),
    emailupdates varchar(4),
    realtime float DEFAULT '0' NOT NULL,
    category int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY computer (computer),
   KEY author (author),
   KEY assign (assign),
   KEY date (date),
   KEY closedate (closedate),
   KEY status (status),
   KEY category (category)
) TYPE=MyISAM;

INSERT INTO glpi_tracking VALUES ('1','2003-09-18 00:46:40','2003-09-18 00:54:43','old','1','4','15','1','8','Mon ordinateur ne s\'allume plus, et il ya des bruits byzarres','3','no','','','0','0');
INSERT INTO glpi_tracking VALUES ('2','2003-09-18 00:48:19','0000-00-00 00:00:00','new','1','4','15','1','10','Un message en anglais s\'affiche, je n\'y comprend rien, je n\'ose plus toucher à rien de peur de tout casser.
Venez vite !!!!','4','no','','','0','0');
INSERT INTO glpi_tracking VALUES ('3','2003-09-18 00:49:29','0000-00-00 00:00:00','new','1','4','15','1','18','Ma souris se bloque sans arret, elle defile mal sur l\'ecran et elle glisse tres mal sur le tapis de souris.','3','no','','','0','0');

### Dump table glpi_tracking_planning

DROP TABLE IF EXISTS glpi_tracking_planning;
CREATE TABLE glpi_tracking_planning (
    ID bigint(20) NOT NULL auto_increment,
    id_tracking int(11) DEFAULT '0' NOT NULL,
    id_assign int(11) DEFAULT '0' NOT NULL,
    begin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
   PRIMARY KEY (ID),
   KEY id_tracking (id_tracking),
   KEY begin (begin),
   KEY end (end)
) TYPE=MyISAM;


### Dump table glpi_type_computers

DROP TABLE IF EXISTS glpi_type_computers;
CREATE TABLE glpi_type_computers (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_type_computers VALUES ('1','Generic x86 PC');
INSERT INTO glpi_type_computers VALUES ('2','PowerMac G4');
INSERT INTO glpi_type_computers VALUES ('3','iMac 2nd Generation');
INSERT INTO glpi_type_computers VALUES ('4','PowerMac G3');
INSERT INTO glpi_type_computers VALUES ('5','Serveur');

### Dump table glpi_type_docs

DROP TABLE IF EXISTS glpi_type_docs;
CREATE TABLE glpi_type_docs (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    ext varchar(10) NOT NULL,
    icon varchar(255) NOT NULL,
    mime varchar(100) NOT NULL,
    upload enum('Y','N') DEFAULT 'Y' NOT NULL,
    date_mod datetime,
   PRIMARY KEY (ID),
   UNIQUE extension (ext),
   KEY upload (upload)
) TYPE=MyISAM;

INSERT INTO glpi_type_docs VALUES ('1','JPEG','jpg','jpg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('2','PNG','png','png-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('3','GIF','gif','gif-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('4','BMP','bmp','bmp-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('5','Photoshop','psd','psd-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('6','TIFF','tif','tif-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('7','AIFF','aiff','aiff-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('8','Windows Media','asf','asf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('9','Windows Media','avi','avi-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('44','C source','c','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('27','RealAudio','rm','rm-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('16','Midi','mid','mid-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('17','QuickTime','mov','mov-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('18','MP3','mp3','mp3-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('19','MPEG','mpg','mpg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('20','Ogg Vorbis','ogg','ogg-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('24','QuickTime','qt','qt-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('10','BZip','bz2','bz2-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('25','RealAudio','ra','ra-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('26','RealAudio','ram','ram-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('11','Word','doc','doc-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('12','DjVu','djvu','','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('42','MNG','mng','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('13','PostScript','eps','ps-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('14','GZ','gz','gz-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('37','WAV','wav','wav-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('15','HTML','html','html-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('34','Flash','swf','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('21','PDF','pdf','pdf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('22','PowerPoint','ppt','ppt-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('23','PostScript','ps','ps-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('40','Windows Media','wmv','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('28','RTF','rtf','rtf-dist.png','','Y','2004-12-13 19:47:21');
INSERT INTO glpi_type_docs VALUES ('29','StarOffice','sdd','sdd-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('30','StarOffice','sdw','sdw-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('31','Stuffit','sit','sit-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('43','Adobe Illustrator','ai','ai-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('32','OpenOffice Impress','sxi','sxi-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('33','OpenOffice','sxw','sxw-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('46','DVI','dvi','dvi-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('35','TGZ','tgz','tgz-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('36','texte','txt','txt-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('49','RedHat/Mandrake/SuSE','rpm','rpm-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('38','Excel','xls','xls-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('39','XML','xml','xml-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('41','Zip','zip','zip-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('45','Debian','deb','deb-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('47','C header','h','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('48','Pascal','pas','','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('50','OpenOffice Calc','sxc','sxc-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('51','LaTeX','tex','tex-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('52','GIMP multi-layer','xcf','xcf-dist.png','','Y','2004-12-13 19:47:22');
INSERT INTO glpi_type_docs VALUES ('53','JPEG','jpeg','jpg-dist.png','','Y','2005-03-07 22:23:17');

### Dump table glpi_type_monitors

DROP TABLE IF EXISTS glpi_type_monitors;
CREATE TABLE glpi_type_monitors (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_type_monitors VALUES ('1','Nokia 445Xav');
INSERT INTO glpi_type_monitors VALUES ('2','Sony 200GDM');
INSERT INTO glpi_type_monitors VALUES ('3','Sony 200sf');
INSERT INTO glpi_type_monitors VALUES ('4','integrated');

### Dump table glpi_type_networking

DROP TABLE IF EXISTS glpi_type_networking;
CREATE TABLE glpi_type_networking (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_type_networking VALUES ('1','Dlink Switch');

### Dump table glpi_type_peripherals

DROP TABLE IF EXISTS glpi_type_peripherals;
CREATE TABLE glpi_type_peripherals (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;


### Dump table glpi_type_printers

DROP TABLE IF EXISTS glpi_type_printers;
CREATE TABLE glpi_type_printers (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
) TYPE=MyISAM;

INSERT INTO glpi_type_printers VALUES ('1','HP Laserjet 4050N');
INSERT INTO glpi_type_printers VALUES ('2','HP Laserjet 4+');
INSERT INTO glpi_type_printers VALUES ('3','HP Deskjet 850c');

### Dump table glpi_users

DROP TABLE IF EXISTS glpi_users;
CREATE TABLE glpi_users (
    ID int(11) NOT NULL auto_increment,
    name varchar(80) NOT NULL,
    password varchar(80) NOT NULL,
    password_md5 varchar(80) NOT NULL,
    email varchar(80) NOT NULL,
    phone varchar(100),
    type enum('normal','admin','post-only','super-admin') DEFAULT 'normal' NOT NULL,
    realname varchar(255) NOT NULL,
    can_assign_job enum('yes','no') DEFAULT 'no' NOT NULL,
    location int(11),
    tracking_order enum('yes','no') DEFAULT 'no' NOT NULL,
    language varchar(255) DEFAULT 'english' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE name (name),
   KEY type (type),
   KEY name_2 (name)
) TYPE=MyISAM;

INSERT INTO glpi_users VALUES ('1','Helpdesk','14e43c2d31dcbdd1','','',NULL,'post-only','Helpdesk Injector','no',NULL,'no','french');
INSERT INTO glpi_users VALUES ('2','glpi','*64B4BB8F2A8C2F41C639DBC894D2759330199470','41ece51526515624ff89973668497d00','','','super-admin','','yes','2','yes','french');
INSERT INTO glpi_users VALUES ('3','post-only','*5683D7F638D6598D057638B1957F194E4CA974FB','3177926a7314de24680a9938aaa97703','','','post-only','','no','1','','english');
INSERT INTO glpi_users VALUES ('4','tech','*B09F1B2C210DEEA69C662977CC69C6C461965B09','d9f9133fb120cd6096870bc2b496805b','','','super-admin','','yes','2','yes','french');
INSERT INTO glpi_users VALUES ('5','normal','*F3F91B23FC1DB728B49B1F22DEE3D7A839E10F0E','fea087517c26fadd409bd4b9dc642555','','','normal','','no','1','','english');
