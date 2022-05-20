# Test instructions

Most tests are about validating the queries againts the underlying database, therefore a test-database is required.

## Prepare test database
Follow the instructions in [database](../database/) to create an empty database with a read-only and a read-write user.

## Sendmail
Some functions must send a confirmation mail to complete the request. The test-system requires a working MTA, so that PHPs `mail()` function returns without an error. A convienient solution is to install exim and configure it to deliver all mail to a local file.
