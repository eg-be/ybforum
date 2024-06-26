# Visual Studio Code setup
Just some hints what is required to debug from Visual Studio Code.
Note: This notes were taken while configuring a Debian 12.4 with php 8.2 and Xdebug 3.2.0
First install Xdebug and the php extensions for vscode, test that debugging basically works.
Then go on with phpunit.
And finally setup the db to debug ybforum.

## php-curl
Note: php-curl is required for re-captcha to work. Install it using
```
sudo apt-get install php-curl
```

## Xdebug
### Required Debian packages
```
sudo apt-get install php-xdebug
```

 #### Enable Xdebug
Edit `/etc/php/8.2/cli/conf.d/20-debug.ini`:
```
zend_extension=xdebug.so
xdebug.mode=debug
# start Xdebug for every request, we're debugging only here
xdebug.start_with_request=yes
``` 

### Visual Studio Code Extensions:
- PHP Debug (Debug support for PHP with Xdebug): https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug 

#### Test PHP Debug
Create file `dummy.php` and debug it:
```
<?php
echo "hello world";
```
Set a breackpoint and switch to `Run and Debug` and Debug the file. Breakpoint should be hit -> ok.
Create a `launch.json` using the provided link, it will look similar to the following:
```
{
    // Use IntelliSense to learn about possible attributes.
    // Hover to view descriptions of existing attributes.
    // For more information, visit: https://go.microsoft.com/fwlink/?linkid=830387
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 0,
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes"
            ],
            "env": {
                "XDEBUG_MODE": "debug,develop",
                "XDEBUG_CONFIG": "client_port=${port}"
            }
        },
        {
            "name": "Launch Built-in web server",
            "type": "php",
            "request": "launch",
            "runtimeArgs": [
                "-dxdebug.mode=debug",
                "-dxdebug.start_with_request=yes",
                "-S",
                "localhost:0"
            ],
            "program": "",
            "cwd": "${workspaceRoot}",
            "port": 9003,
            "serverReadyAction": {
                "pattern": "Development Server \\(http://localhost:([0-9]+)\\) started",
                "uriFormat": "http://localhost:%s",
                "action": "openExternally"
            }
        }
    ]
}
```


## Phpunit
### Install phar
Download the corresponding PHP archive from https://phar.phpunit.de into the folder of the project, make it executable and test what version is reported:
```
 wget -O phpunit.phar https://phar.phpunit.de/phpunit-11.0.3.phar
 chmod +x phpunit.phar
 ./phpunit.phar --version
```

### Deprecated - Required Debian packages
Unfortunately, the packages shipped with Debian bookworm are version 9.x, what is a little bit too old. Better install it from the phar, as described above
```
sudo apt-get install phpunit
```

### Visual Studio Code Extensions
- PHP Unit Test Explorer (PHPUnit Test Explorer for VSCode): https://marketplace.visualstudio.com/items?itemName=recca0120.vscode-phpunit

#### Configure PHP Unit Test Explorer
Create file `.vscode/settings.json` and define the path to phpunit:

```
{
    // use the downloaded PHP Archive:
    "phpunit.phpunit": "phpunit.phar"
    // the following was for the debian binary:
    // "phpunit.phpunit": "/usr/bin/phpunit" 
}
```

#### Test if running a test works
Create file `SimpleTest.php` with a dummy-test:
```
<?php

use PHPUnit\Framework\TestCase;

final class SimpleTest extends TestCase
{
    public function testDummy()
    {
        $this->assertSame(18, 19);
    }
}
```
From the Test-Explorer, run the test (it should fail).

#### Test if debugging a test works
1. Set a breakpoint in `SimpleTest.php`.
2. From "Run and Debug", start the debugger ("Listen for Xdebug").
3. Run the test again (from the test-explorer). Breakpoint should be hit. -> OK

## MariaDB
### Required Debian packages
```
sudo apt-get install mariadb-server
sudo apt-get install php-pdo php-mysql
```
### Start service
```
sudo service mariadb start
```
### Test Connection
```
sudo mariadb
```
### Setup database for the tests
```
MariaDB [(none)]> CREATE DATABASE IF NOT EXISTS dbybforum CHARACTER SET utf8mb4;
Query OK, 1 row affected (0.000 sec)

MariaDB [(none)]> CREATE USER 'dbybforum_ro'@'localhost' IDENTIFIED BY 'ro-password';
Query OK, 0 rows affected (0.004 sec)

MariaDB [(none)]> GRANT SELECT,SHOW VIEW ON dbybforum.* TO 'dbybforum_ro'@'localhost';
Query OK, 0 rows affected (0.003 sec)

MariaDB [(none)]> CREATE USER 'dbybforum_rw'@'localhost' IDENTIFIED BY 'rw-password';
Query OK, 0 rows affected (0.003 sec)

MariaDB [(none)]> GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON dbybforum.* TO 'dbybforum_rw'@'localhost';
Query OK, 0 rows affected (0.001 sec)
```
### Run some tests from within TestExplorer
If running the tests work, everything is fine.

## Apache Setup
To manually test things from a Browser, while the debugger will break into vscode.

### Required Debian packages
```
sudo apt-get install apache2 libapache2-mod-php

```
### Test Apache is working
```
sudo service apache2 start
```
Browse to http://localhost where the apache2 default page must appear.

### Configure Apache
Edit `/etc/apache2/sites-available/000-default.conf` and add a section like the following:
```
        Alias /ybforum "/home/eg/dev/ybforum/src"
        <Directory /home/eg/dev/ybforum/src>
                Options FollowSymLinks
                Options Indexes
                AllowOverride None
                Require all granted
        </Directory>
```
The whole path needs `+x` permission, else apache fails with an error similar to `Permission denied: [client ::1:42068] AH00035: access to /dev/ denied (filesystem path '/home/eg/dev') because search permissions are missing on a component of the path`. Therefore give your home-directory `+x`: `chmod +x /home/eg`

Restart apache: `sudo service apache2 restart` and browse to http://localhost/ybforum -> you should see the forum with the data from the unit-tests (note: if everything is empty, run the unit-tests).

### Test a breakpoint
If vscode, start debugging with `Listen for Xdebug`. Set a breakpoint somewhere, for example in the constructor of class `ForumDb`. Browse to http://localhost/ybforum and the breakpoint must be hit.

Note: Make sure that mariadb is running, or you will get an error like `[php:notice] [pid 5204] [client ::1:34030] /home/eg/dev/ybforum/src/model/ForumDb.php(68): SQLSTATE[HY000] [2002] No such file or directory` in the apache2 error-log.

### Configure error-logging
Edit `/etc/php/8.2/apache2/php.ini` and set `error_reporting = E_ALL` and `display_errors = On`

## Mail setup
If you want to mailing by sending real mails, just setup an alternative for `sendmail` that will deliver mails to a local user:

### Required Debian packages
```
sudo apt-get install postfix
sudo apt-get install mailutils
```
choose `internet site` when configuring postfix, altough other settings will work as well
   
### Test sending a mail works 
test that sending a mail to a local user works using the `mail` command and check that this message is delivered:
```
eg@TITANUS-3113:~$ mail eg@TITANUS-3113
Cc:
Subject: test
hello

eg@TITANUS-3113:~$ mail
"/var/mail/eg": 1 message 1 new
>N   1 TITANUS-3113       Sun Jan 28 20:24  13/407   test
? 1
Return-Path: <eg@TITANUS-3113>
X-Original-To: eg@TITANUS-3113
Delivered-To: eg@TITANUS-3113
Received: by TITANUS-3113 (Postfix, from userid 1000)
        id 4C7619C8D; Sun, 28 Jan 2024 20:24:46 +0100 (CET)
To: <eg@TITANUS-3113>
Subject: test
User-Agent: mail (GNU Mailutils 3.15)
Date: Sun, 28 Jan 2024 20:24:46 +0100
Message-Id: <20240128192446.4C7619C8D@TITANUS-3113>
From: TITANUS-3113 <eg@TITANUS-3113>

hello
? q
Saved 1 message in /home/eg/mbox
Held 0 messages in /var/mail/eg
eg@TITANUS-3113:~$
```

### Configure regex to redirect mails for `test`
Configure postfix to deliver mails for the admin-addresses and the mail-monitor address to a local user: Add a corresponding entry in `/etc/postfix/main.cf`:

```
# The file can hold a regex for virtual aliases. put the following
# in the file:
# /^test/ eg@TITANUS-3113
# to get all mails starting with 'test' being sent to ourself
virtual_alias_maps = regexp:/etc/postfix/virtual_alias
```

And add the following entry in `/etc/postfix/virtual_alias`:
```
/^test/ eg@TITANUS-3113
/eg-be@dev/ eg@TITANUS-3113
/mail-monitor@1898.ch/ eg@TITANUS-3113
```
