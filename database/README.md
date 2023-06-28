# Database
All data is stored in a mysql database. This page describes the required step to setup a new, empty database and import the required initial data.

## Prerequisites
- A working installation of [MySQL](https://www.mysql.com).

## Create empty database with two users
- An empty database
- A user with read-only access to the database
- A user with read-write access to the database

Depending on your MySQL-version, this will be similar to:
```
# connect to MySQL:
eg-be@dev:~$ mysql -u root -p

# Create the database:
mysql> CREATE DATABASE IF NOT EXISTS dbybforum CHARACTER SET utf8mb4;
Query OK, 1 row affected, 1 warning (0.00 sec)

# Create the read-only user:
mysql> CREATE USER 'dbybforum_ro'@'localhost' IDENTIFIED BY 'ro-password';
Query OK, 0 rows affected (0.01 sec)
mysql> GRANT SELECT,SHOW VIEW ON dbybforum.* TO 'dbybforum_ro'@'localhost';
Query OK, 0 rows affected (0.00 sec)

# Create the read-write user:
mysql> CREATE USER 'dbybforum_rw'@'localhost' IDENTIFIED BY 'rw-password';
Query OK, 0 rows affected (0.01 sec)
mysql> GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON dbybforum.* TO 'dbybforum_rw'@'localhost';
Query OK, 0 rows affected (0.01 sec)

# exit mysql
mysql> exit
Bye

# check that connection is working:
eg-be@dev:~$ mysql -h localhost -u dbybforum_ro -pro-password dbybforum

mysql> SHOW GRANTS FOR dbybforum_ro@localhost;
+------------------------------------------------------------------------+
| Grants for dbybforum_ro@localhost                                      |
+------------------------------------------------------------------------+
| GRANT USAGE ON *.* TO `dbybforum_ro`@`localhost`                       |
| GRANT SELECT, SHOW VIEW ON `dbybforum`.* TO `dbybforum_ro`@`localhost` |
+------------------------------------------------------------------------+

eg-be@dev:~$ mysql -h localhost -u dbybforum_rw -prw-password dbybforum

mysql> SHOW GRANTS FOR dbybforum_rw@localhost;
+---------------------------------------------------------------------+
| Grants for dbybforum_rw@localhost                                   |
+---------------------------------------------------------------------+
| GRANT USAGE ON *.* TO `dbybforum_rw`@`localhost`                    |
| GRANT ALL PRIVILEGES ON `dbybforum`.* TO `dbybforum_rw`@`localhost` |
+---------------------------------------------------------------------+
```

## Create database structure and import initial data
Create the database-structure and import the initial data:
```
eg-be@dev:~/ybforum/database$ mysql -h localhost -u dbybforum_rw -prw-password dbybforum < dbybforum-no-data.dump.sql 
eg-be@dev:~/ybforum/database$ mysql -h localhost -u dbybforum_rw -prw-password dbybforum < log_type_table_data.dump.sql 
```

Create a very first admin user:
The password must be hashed, just use a php interactive shell to create a hash from your password:
```
eg-be@dev:~$ php -a
Interactive shell
php > echo password_hash("my-pass", PASSWORD_DEFAULT);
$2y$10$g.h9s8ncbW6qhK4Xqb49OOXhUQqX/IPhPmwuG.PvvHc6QCflevQcS
```
And create the corresponding user-entry with that password-hash:
```
eg-be@dev:~$ mysql -h localhost -u dbybforum_rw -prw-password dbybforum

mysql> INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) VALUES('admin', '$2y$10$g.h9s8ncbW6qhK4Xqb49OOXhUQqX/IPhPmwuG.PvvHc6QCflevQcS', 'eg-be@dev', 1, 1, 'initial admin-user', CURRENT_TIMESTAMP());
Query OK, 1 row affected (0.00 sec)

mysql> SELECT * FROM user_table;
+--------+-------+--------------------------------------------------------------+-----------+-------+--------+---------------------+--------------------+---------------------+------------+
| iduser | nick  | password                                                     | email     | admin | active | registration_ts     | registration_msg   | confirmation_ts     | old_passwd |
+--------+-------+--------------------------------------------------------------+-----------+-------+--------+---------------------+--------------------+---------------------+------------+
|   2874 | admin | $2y$10$g.h9s8ncbW6qhK4Xqb49OOXhUQqX/IPhPmwuG.PvvHc6QCflevQcS | eg-be@dev |     1 |      1 | 2022-05-11 17:24:02 | initial admin-user | 2022-05-11 17:24:02 | NULL       |
+--------+-------+--------------------------------------------------------------+-----------+-------+--------+---------------------+--------------------+---------------------+------------+
1 row in set (0.00 sec)
```

## FULLTEXT INDEX
The table `post_table` has a FULLTEXT index to speed up searching. The following SQL can be used to drop and recreate that index:

Drop: 
```
ALTER TABLE post_table DROP INDEX fulltext_title_content;
```

Create: 
```
ALTER TABLE post_table ADD FULLTEXT INDEX fulltext_title_content (title, content);
``` 

## Collation
If, for whatever reason, the collations get mixed up, execute the following to update the collation of a column:

```
ALTER TABLE `post_table` CHANGE `title` `title` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_german2_ci NOT NULL;
```
