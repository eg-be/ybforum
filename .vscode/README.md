# Visual Studio Code setup
Just some hints what is required to debug from Visual Studio Code.
Note: This notes were taken while configuring a Debian 12.4 with php 8.2 and Xdebug 3.2.0
First install Xdebug and the php extensions for vscode, test that debugging basically works.
Then go on with phpunit.
And finally setup the db to debug ybforum.

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

### Visual Studio Code packages
- PHP Debug

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
### Required Debian packages
```
sudo apt-get install phpunit
```

### Visual Studio Code packages
- PHP Unit Test Explorer

#### Configure PHP Unit Test Explorer
Create file `.vscode/settings.json` and define the path to phpunit:

```
{
    "phpunit.phpunit": "/usr/bin/phpunit"
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
