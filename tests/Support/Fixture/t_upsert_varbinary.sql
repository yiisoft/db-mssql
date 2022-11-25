IF OBJECT_ID('[T_upsert_varbinary]', 'U') IS NOT NULL DROP TABLE [T_upsert_varbinary];
CREATE TABLE [T_upsert_varbinary]
(
    [id] INT NOT NULL,
    [blob_col] [varbinary](MAX),
    UNIQUE ([id])
);
