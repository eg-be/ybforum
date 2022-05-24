-- pass is always <username>-pass, like for user 'admin' the password is 'admin-pass', etc.
INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('admin', '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', 'eg-be@dev', 1, 1, 'initial admin-user', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('user1', '$2y$10$xn7P.wZZiMm2buOgEEPtCuugPD7SBGkRqzuoBHFskabrk4jcLPvE2', 'user1@dev', 0, 1, 'initial user1', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('user2', '$2y$10$Ky/MNkG.KcqWa.mUkhVmVekjNbzhwIFy8N8vG7a5KgX2U1pKJFPL.', 'user2@dev', 0, 1, 'initial user2', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('user3', '$2y$10$bDdkceU8zopT4E9RifoZyOn435QvCabBJsHEOpdOAsnUXHC0VxrJK', 'user3@dev', 0, 1, 'initial user3', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('foo bar', '$2y$10$Q3BsPdLJ6v74fKBJZh0DEO43mLfvMsuxpOsJ70OYHtuDyVnASub6W', 'foo-bar@dev', 0, 1, 'initial foo bar', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('inactive', '$2y$10$Q3BsPdLJ6v74fKBJZh0DEO43mLfvMsuxpOsJ70OYHtuDyVnASub6W', 'inactive@dev', 0, 0, 'initial inactive', CURRENT_TIMESTAMP());

INSERT INTO user_table (nick, password, email, admin, active, registration_msg, confirmation_ts) 
VALUES('dummy', '$2y$10$eE06foi/V8STnyJ3MpPBGOTO4Y.Sn63xPDQPDhe1ch3m9kQyUYhBe', 'dummy@dev', 0, 0, 'initial dummy', CURRENT_TIMESTAMP());
