#Script a executer pour passer de la base de donnée de la version 0.2
# a celle de la version 0.21 sans perdre de données.
 Alter table users drop can_assign_job;
 Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no';
 Update users set can_assign_job = 'yes' where type = 'admin';
