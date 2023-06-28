# Backup
This folder contains some helpers to create a backup of the database and restore it again. Most of the hints and tips are specific to the hoster I've been using and wont be usable for anyone else.

## Create dump
Note: The user needs to have sufficient privileges to dump stored procedures (EXECUTE), else the stored procedures will not be included in the dump.

> mysqldump --routines --host=<server> --port=<port> -u <user> -p <password> > YYYYMMDD_dbybforum.dump.sql
> 
