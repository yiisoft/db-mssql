IF OBJECT_ID('[T_upsert]', 'U') IS NOT NULL DROP TABLE [T_upsert];
CREATE TABLE [T_upsert]
(
    [id] INT NOT NULL IDENTITY PRIMARY KEY,
    [ts] INT NULL,
    [email] VARCHAR(128) NOT NULL UNIQUE,
    [recovery_email] VARCHAR(128) NULL,
    [address] TEXT NULL,
    [status] TINYINT NOT NULL DEFAULT 0,
    [orders] INT NOT NULL DEFAULT 0,
    [profile_id] INT NULL,
    UNIQUE ([email], [recovery_email])
);
