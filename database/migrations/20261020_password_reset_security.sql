-- Run once before deploying the password reset and password_hash code.
-- Back up the database first.
ALTER TABLE usuarios
	MODIFY COLUMN password VARCHAR(255) NOT NULL,
	MODIFY COLUMN recuperacion VARCHAR(64) NOT NULL DEFAULT '0',
	ADD COLUMN recuperacion_expira DATETIME NULL DEFAULT NULL;
