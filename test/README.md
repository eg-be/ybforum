# Test instructions

Most tests are about validating the queries againts the underlying database, therefore a test-database is required.

## Prepare test database
Follow the instructions in [database](../database/) to create an empty database with a read-only and a read-write user.

## Sendmail
Some functions must send a confirmation mail to complete the request. The test-system requires a working MTA, so that PHPs `mail()` function returns without an error. A cheap solution is to install exim, configure it to deliver mail locally only and and then just specify an alias to redirect all mail for `www-data` to some other user:

```
eg-be@dev:~$ cat /etc/aliases 
www-data: eg-be
```
Exim will refuse any mail targeting a remote-domain and respond with a failure notice, which will be forwarded to your local user-account.

A proper solution would be to configure exim to redirect all outgoing mail to a specific user or a local file. But I dont remember how to..

And really proper would be to use a mock-mailer during the tests, see [#36](../../../issues/36).

And there is also the configuration option `MAIL_DEBUG`:

```
    /**
     * @var boolean If set, the Mailer will not try to send a mail,
     * but just log to syslog and stderr what would be sent as a mail.
     */
    const MAIL_DEBUG = false;
```

This is all a little bit messy. Address with [#36](../../../issues/36)
