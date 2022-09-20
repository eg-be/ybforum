INSERT INTO blacklist_table (idblacklist, email, email_regex, description)
VALUES(1, 'foo@bar.com', NULL, 'foo-bar');

INSERT INTO blacklist_table (idblacklist, email, email_regex, description)
VALUES(10, NULL, '/.+\.ru$/i', 'Mailadressen aus .ru sind blockiert.');
