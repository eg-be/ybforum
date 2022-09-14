/* just some very simple thread indexes */
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(1, 1, NULL, 101, 'Thread 1', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:31:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(2, 2, NULL, 102, 'Thread 2', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:32:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(3, 3, NULL, 103, 'Thread 3', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:33:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(4, 4, NULL, 101, 'Thread 4', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:34:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(5, 5, NULL, 102, 'Thread 5', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:35:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(6, 6, NULL, 103, 'Thread 6', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:36:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(7, 7, NULL, 101, 'Thread 7', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:37:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(8, 8, NULL, 102, 'Thread 8', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:38:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(9, 9, NULL, 103, 'Thread 9', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:39:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(10, 10, NULL, 101, 'Thread 10', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:40:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(11, 11, NULL, 102, 'Thread 11', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:41:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(12, 12, NULL, 103, 'Thread 12', 'The quick brown fox jumps over the lazy dog', 1, 0, '2020-03-30 14:42:00', '::1');

/* populate ONE thread with some post-tree. That is enough for most of the tests.
   tests requiring more data, shall provide their own data and load it */
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(20, 3, 3, 101, 'Thread 3 - A1', 'The quick brown fox jumps over the lazy dog', 2, 1, '2020-03-30 14:50:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(21, 3, 20, 102, 'Thread 3 - A1-1', 'The quick brown fox jumps over the lazy dog', 3, 2, '2020-03-30 14:51:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(22, 3, 3, 103, 'Thread 3 - A2', 'The quick brown fox jumps over the lazy dog', 7, 1, '2020-03-30 14:52:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(23, 3, 20, 102, 'Thread 3 - A1-2', 'The quick brown fox jumps over the lazy dog', 4, 2, '2020-03-30 14:53:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(24, 3, 22, 101, 'Thread 3 - A2-1', 'The quick brown fox jumps over the lazy dog', 8, 2, '2020-03-30 14:54:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(25, 3, 23, 101, 'Thread 3 - A1-2-1', 'The quick brown fox jumps over the lazy dog', 5, 3, '2020-03-30 14:55:00', '::1');
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address)
VALUES(26, 3, 20, 103, 'Thread 3 - A1-3', 'The quick brown fox jumps over the lazy dog', 6, 2, '2020-03-30 14:55:00', '::1');

/* populate ONE thread with a post with all fields set, except hidden */
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address, email, link_text, link_url, img_url, old_no)
VALUES(30, 5, 5, 101, 'Thread 5 - A1', 'The quick brown fox jumps over the lazy dog', 2, 1, '2022-06-22 16:13:25', '::1', 'mail@me.com', 'Visit me', 'https://foobar', 'https://giphy/bar.gif', 131313);

/* and add a hidden-post */
INSERT INTO post_table (idpost, idthread, parent_idpost, iduser, title, content, `rank`, indent, creation_ts, ip_address, hidden)
VALUES(40, 8, 8, 103, 'Thread 8 - A1', 'The quick brown fox jumps over the lazy dog', 2, 1, '2020-03-30 14:50:00', '::1', 1);
