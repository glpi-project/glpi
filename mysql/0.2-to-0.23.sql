
#partie pour la 0.22

alter table printers add ramSize varchar(6) NOT NULL default '';
 
# partie pour la .023

ALTER TABLE computers MODIFY name VARCHAR(200) NOT NULL default '';
ALTER TABLE computers MODIFY type VARCHAR(100) NOT NULL default '';
ALTER TABLE computers MODIFY os VARCHAR(100) NOT NULL default '';
ALTER TABLE computers MODIFY osver VARCHAR(20) NOT NULL default '';
ALTER TABLE computers MODIFY processor VARCHAR(30) NOT NULL default '';
ALTER TABLE computers MODIFY processor_speed VARCHAR(30) NOT NULL default '';
ALTER TABLE computers MODIFY hdspace VARCHAR(6) NOT NULL default '';
ALTER TABLE computers MODIFY contact VARCHAR(90) NOT NULL default '';
ALTER TABLE computers MODIFY contact_num VARCHAR(90) NOT NULL default '';
ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00';
ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00';


ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00';
ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00';

ALTER TABLE networking MODIFY ram varchar(10) NOT NULL default '';
ALTER TABLE networking MODIFY serial varchar(50) NOT NULL default '';
ALTER TABLE networking MODIFY otherserial varchar(50) NOT NULL default '';
ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00';
ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00';


ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00';
ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00';

ALTER TABLE software MODIFY name varchar(200) NOT NULL default '';
ALTER TABLE software MODIFY platform varchar(200) NOT NULL default '';
ALTER TABLE software MODIFY version varchar(20) NOT NULL default '';
ALTER TABLE software MODIFY location varchar(200) NOT NULL default '';
ALTER TABLE software MODIFY comments text NOT NULL;


ALTER TABLE templates MODIFY templname varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY name varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY os varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY osver varchar(20) NOT NULL default '';
ALTER TABLE templates MODIFY processor varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY processor_speed varchar(100) NOT NULL default '';
ALTER TABLE templates MODIFY location varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY serial varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY otherserial varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY ramtype varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY ram varchar(20) NOT NULL default '';
ALTER TABLE templates MODIFY network varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY hdspace varchar(10) NOT NULL default '';
ALTER TABLE templates MODIFY contact varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY contact_num varchar(200) NOT NULL default '';
ALTER TABLE templates MODIFY comments text NOT NULL;
ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00';
ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00';

ALTER TABLE users MODIFY password varchar(80) NOT NULL default '';
ALTER TABLE users MODIFY email varchar(80) NOT NULL default '';
ALTER TABLE users MODIFY location varchar(100) NOT NULL default '';
ALTER TABLE users MODIFY phone varchar(100) NOT NULL default '';