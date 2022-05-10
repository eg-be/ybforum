# Database
All data is stored in a mysql database. This page describes the required step to setup a new, empty database and import the required intial data.

## Prerequisites
- A working installation of [MySQL](https://www.mysql.com).
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


```


## Setup database
