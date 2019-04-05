Thre is no PostgreSQL schema available yet.
In order to test the feature, you'll need to
create a postgres database and convert your
MySQL installation using a tool like PgLoader.

create user with password:
postgres$ createuser -P glpi

create database:
postgres$ createdb -O glpi glpi_db

Populate postgre from mysql:
user$ pgloader mysql://glpi:secret@localhost/glpi_db pgsql://glpi:secret@localhost/glpi_db
