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

Restoring the dump may fail if it contains `DEFINER` clauses. Check for `DEFINER` statements:

```
grep DEFINER YYYYMMDD_dbybforum.dump.sql
```

If there is any output like ```CREATE DEFINER=`dbybforum_usr`@`%` PROCEDURE `insert_reply` <snip>``` the dump does contain such claueses. Simply remove them before restoring the dump using `sed`:

```
sed -i.bak s/DEFINER=\`dbybforum_usr\`@\`%\`//g YYYYMMDD_dbybforum.dump.sql
```

Checking again using `grep DEFINER YYYYMMDD_dbybforum.dump.sql` should report nothing now.
