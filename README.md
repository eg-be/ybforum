# YB Forum 1898
[![PHPUnit Tests](https://github.com/eg-be/ybforum/actions/workflows/github-actions-phpunit-tests.yml/badge.svg)](https://github.com/eg-be/ybforum/actions/workflows/github-actions-phpunit-tests.yml)

A forum with a user-interface stuck in the 90s, but with an implementation from the 21 century.

This page provides some information on how to setup your own ybforum.

## Requirements
- PHP 8.3
- MariaDB or MySQL

## Install
1. Follow the instructions in [database](database) to setup the required database.
2. Copy the content of [src](src) to the httpdoc-folder of your webserver.
3. Adjust the database-connection parameters in file [src/model/DbConfig.php](src/model/DbConfig.php).
4. Adjust the settings in file [src/YbForumConfig.php](src/YbForumConfig.php). Most defaults are okay, but update the values for:
   - `BASE_URL`
   - `MAIL_FROM`
   - `MAIL_ALL_BCC`
5. Adjust the values for the google captcha-verify in file [src/helpers/CaptchaV3Config.php](src/helpers/CaptchaV3Config.php), if you want to enable captcha.
   - `CAPTCHA_VERIFY`
   - `CAPTCHA_SITE_KEY`
   - `CAPTCHA_SECRET`

Thats it, now point your browser to the URL serving the content of httpdoc. You should see the index-page with zero posts for now:

![Empty index](index.png)

You are ready to post your first entry now.

## Development setup
The following steps describe the minimal setup for running the tests. See [Folder .vscode](.vscode) for some notes about howto setup a dev-environment with vscode.

### Required php-extensions
The following php-extensions are required and must be installed:
- pdo_mysql

### Composer
Ensure [composer](https://getcomposer.org) is installed and install the required dependencies:
```
composer update
```
Note: The only required dependency is [phpunit](https://phpunit.de), which is required during development only.

### Test-Database
Follow the instructions in [database](database) to setup the database required for the tests and adjust the database-connection parameters in file [src/model/DbConfig.php](src/model/DbConfig.php).

### Run the tests
```
eg@TITANUS-3113:~/dev/ybforum$ ./vendor/phpunit/phpunit/phpunit
```
See [Folder test](test) for some more notes about howto run the tests.

### Test coverage
Run with `--coverage-html` to report the test coverage:
```
eg@TITANUS-3113:~/dev/ybforum$ export XDEBUG_MODE=coverage
eg@TITANUS-3113:~/dev/ybforum$ ./vendor/phpunit/phpunit/phpunit --coverage-html reports
```

## Backup and restoring the databse
See [Folder backup_script](backup_script) for some notes about backuping and restoring the database.

## Various notes
### sendmail
Confirmation mails are sent using phps built-in function [mail](https://www.php.net/manual/de/function.mail.php). Make sure that the `sendmail_path` in `php.ini` is configured with a MTA satisfying the [requirements](https://www.php.net/manual/en/mail.requirements.php).
