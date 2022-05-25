-- pass is always <username>-pass, like for user 'admin' the password is 'admin-pass', etc.
INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, registration_ts, confirmation_ts) 
VALUES(1, 'admin', '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', 'eg-be@dev', 1, 1, 'initial admin-user', '2020-03-30 14:30:05', '2020-03-30 14:30:15');

-- note the old-user has just a plain md5 password-hash
INSERT INTO user_table (iduser, nick, old_passwd, email, admin, active, registration_msg, registration_ts) 
VALUES(10, 'old-user', '895e1aace5e13c683491bb26dd7453bf', 'old-user@dev', 0, 0, 'needs migration', '2017-12-31 15:21:27');

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES(101, 'user1', '$2y$10$xn7P.wZZiMm2buOgEEPtCuugPD7SBGkRqzuoBHFskabrk4jcLPvE2', 'user1@dev', 0, 1, 'initial user1', CURRENT_TIMESTAMP());

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES(102, 'user2', '$2y$10$Ky/MNkG.KcqWa.mUkhVmVekjNbzhwIFy8N8vG7a5KgX2U1pKJFPL.', 'user2@dev', 0, 1, 'initial user2', CURRENT_TIMESTAMP());

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES(103, 'user3', '$2y$10$bDdkceU8zopT4E9RifoZyOn435QvCabBJsHEOpdOAsnUXHC0VxrJK', 'user3@dev', 0, 1, 'initial user3', CURRENT_TIMESTAMP());

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, registration_ts, confirmation_ts) 
VALUES(50, 'foo bar', '$2y$10$Q3BsPdLJ6v74fKBJZh0DEO43mLfvMsuxpOsJ70OYHtuDyVnASub6W', 'foo-bar@dev', 0, 1, 'initial foo bar', '2021-03-30 14:30:05', '2021-03-30 14:30:15');

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES(51, 'inactive', '$2y$10$Q3BsPdLJ6v74fKBJZh0DEO43mLfvMsuxpOsJ70OYHtuDyVnASub6W', 'inactive@dev', 0, 0, 'initial inactive', CURRENT_TIMESTAMP());

INSERT INTO user_table (iduser, nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES(66, 'dummy', NULL, NULL, 0, 0, 'initial dummy', CURRENT_TIMESTAMP());
