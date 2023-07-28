# Conversion
This folder contains the scripts that were used to transform the original database into the new format of ybforum. Those scripts are probably never ever used again.
Note: Documentation is poor and maybe things are missing. This process has been executed only once, a long time ago.

## Create required databases
Two databases are required. They must both run on the same server (`localhost`) and share a common username and password:
- `old1898`: Contains the original database, restored from a dump
- `ybforum`: Database in the new format of ybforum

Setup the new database as decsribed in [database](../database), but:
- only one user is required (but with full access)
- do not create the initial admin account

Important: Check that the default collation is set to `utf8mb4` (note: Probably only relevant for the new database??)

Restore the original database from a dump:
```
mysql -p -u ybforum old1898 < 1898admin.sql
```

## Run the Converter
Configure the database connection parameters for the old and the new database in [ImporterConfig.php](ImporterConfig.php)

From inside the directory `conversion`, start the process:

### Import users
```
 php -d zend.assertions=1 ImportUsers.php > importUsers.log
```

Note: The original database contains duplicates for the email addresses (multiple nicknames using the same email-address). Duplicates are inserted into the new database with an email address that has a suffix `_duplicate_X` (where `X` is an integer, starting with `0`). These duplicated accounts must be cleaned up manually: Decide which of the two accounts (or both) shall be deactivated (see below).

### Import threads
```
php -d zend.assertions=1 ImportThreads.php > importThreads.log
```

### Turn blocked users into dummies
Dummies do not have a email address nor a password. Turn users that have been blocked in the old database (contains `sperre` in the email address) into dummies:
```
UPDATE user_table SET email = NULL, old_passwd = NULL WHERE email LIKE '%sperre%'
```

### Copy unused accounts into the unused_user_table and delete from user_table
```
INSERT into unused_user_table 
SELECT iduser, nick, email, admin, active, registration_ts, registration_msg, old_passwd FROM ybforum.user_table where iduser not in (select iduser from post_table)
DELETE FROM ybforum.user_table where iduser not in (select iduser from post_table)
```
(Why did we copy them to the unused_user_table? Can we remove that table? Its nowhere used, see #46)

### Resolve duplicates
Find duplicates:
```
SELECT * FROM user_table WHERE email LIKE '%duplicate%'
```

Manually decide which account must be turned into a dummy.

### Update collation of column nick
The collation of the column `nick` must be set to `utf8mb4_german2_ci`:
```
ALTER TABLE `user_table` CHANGE `nick` `nick` VARCHAR(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci NOT NULL;
```

Updating the collation may fail if there are more duplicates that are found now (for example with `utf8mb4_german2_ci` the two nicknames `Globi` and `GLOBI` will be identified as equal, the same for nicknames with `ä` and `ae`, etc.).

Mark one of the problematic accounts with a `_` at the end:
```
UPDATE user_table SET nick = 'Problem_' WHERE nick = 'Problem'
```
and re-run the update of the collation until it works. Then turn all accounts with `_` at the end into dummies.


