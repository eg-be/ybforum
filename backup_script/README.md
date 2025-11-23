# Backup
This folder contains some helpers and mostrly notes to create a backup of the database and restore it again.

## Create dump
Note: The user needs to have sufficient privileges to dump stored procedures (EXECUTE), else the stored procedures will not be included in the dump.

```
mysqldump --routines --host=<server> --port=<port> -u <user> -p<password> dbybforum > YYYYMMDD_dbybforum.dump.sql
gzip YYYYMMDD_dbybforum.dump.sql
```

## Restore dump
Again, the user needs to have sufficient privileges.

Basically, the following does the job (but read below about `DEFINER` clauses):
```
gunzip YYYYMMDD_dbybforum.dump.sql.gz
mysql --host=<server> --port=<port> -u <user> -p<password> dbybforum < YYYYMMDD_dbybforum.dump.sql
```

### DEFINER clause
Restoring the dump may fail if it contains `DEFINER` clauses. Check for `DEFINER` clauses:

```
grep DEFINER YYYYMMDD_dbybforum.dump.sql
```

If there is any output like ```CREATE DEFINER=`dbybforum_usr`@`%` PROCEDURE `insert_reply` <snip>``` the dump does contain such claueses. Simply remove them before restoring the dump using `sed`:

```
sed -i.bak s/DEFINER=\`dbybforum_usr\`@\`%\`//g YYYYMMDD_dbybforum.dump.sql
```

Checking again should report nothing now.

### FULLTEXT KEY
Restoring a dump that contains a `FULLTEXT INDEX`is extremly slow. Its much faster to delete the `FULLTEXT KEY`in the dump before restoring it, and then recreating the `FULLTEXT INDEX`on the database once the dump has been imported.

Check for a `FULLTEXT KEY`:

```
cat YYYYMMDD_dbybforum.sql.dump | grep FULLTEXT
```

If there is any output like ```FULLTEXT KEY `fulltext_title_content` (`title`,`content`),``` the dump does contain such keys. Simply remove them using `sed`:

```
sed -i.bak '/FULLTEXT/d' dbybforum_2018-03-10.sql.dump
```

Checking again should report nothing now.

To recreate the `FULLTEXT INDEX` on the `post_table` execute the following SQL:

```
ALTER TABLE post_table ADD FULLTEXT INDEX fulltext_title_content (title, content);
```

# Export database structure and minimal required data
To export only the database structure and the required entries from `log_type_table` execute the following:

```
mysqldump --no-data --routines --host=<server> --port=<port> -u <user> -p<password> dbybforum > dbybforum-no-data.dump.sql
mysqldump --no-create-info --host=<server> --port=<port> -u <user> -p<password> dbybforum log_type_table > log_type_table_data.dump.sql
```

# Restore to a new db
To restore a backup to a newly created database (for testing purposes or whatever), the following recipe can be used:
```
# create a new empty database and grant permissions to already existing users:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ sudo mariadb
MariaDB [(none)]> CREATE DATABASE IF NOT EXISTS dbybforum2 CHARACTER SET utf8mb4;
MariaDB [(none)]> GRANT SELECT,SHOW VIEW ON dbybforum2.* TO 'dbybforum_ro'@'localhost';
MariaDB [(none)]> GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON dbybforum2.* TO 'dbybforum_rw'@'localhost';

# unizp an existing dump:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ gunzip dbybforum_2025-11-23.sql.dump.gz

# Remove DEFINER and FULLTEXT clauses from dump:
# to check if there are actually some clauses to remove:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ grep DEFINER dbybforum_2025-11-23.sql.dump
eg@TITANUS-3113:~/dev/ybforum-restore-db$ cat dbybforum_2025-11-23.sql.dump | grep FULLTEXT
# remove them using sed:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ sed -i.bak s/DEFINER=\`dbybforum_usr\`@\`%\`//g dbybforum_2025-11-23.sql.dump
eg@TITANUS-3113:~/dev/ybforum-restore-db$ sed -i.bak '/FULLTEXT/d' dbybforum_2025-11-23.sql.dump
# check that they are no longer there:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ grep DEFINER dbybforum_2025-11-23.sql.dump
eg@TITANUS-3113:~/dev/ybforum-restore-db$ cat dbybforum_2025-11-23.sql.dump | grep FULLTEXT

# restore the dump:
eg@TITANUS-3113:~/dev/ybforum-restore-db$ mysql --host=localhost --port=3306 -u dbybforum_rw -prw-password dbybforum2 <dbybforum_2025-11-23.sql.dump

```
