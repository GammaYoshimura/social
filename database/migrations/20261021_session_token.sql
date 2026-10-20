-- Run once before deploying the secure session token changes.
-- Back up the database first.
ALTER TABLE usuarios
	MODIFY COLUMN idsesion VARCHAR(64) NOT NULL DEFAULT '';
