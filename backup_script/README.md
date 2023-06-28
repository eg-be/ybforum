# Backup
This folder contains some helpers to create a backup of the database and restore it again.

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
