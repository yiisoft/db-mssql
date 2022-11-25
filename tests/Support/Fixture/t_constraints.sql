IF OBJECT_ID('[T_constraints_4]', 'U') IS NOT NULL DROP TABLE [T_constraints_4];
IF OBJECT_ID('[T_constraints_3]', 'U') IS NOT NULL DROP TABLE [T_constraints_3];
IF OBJECT_ID('[T_constraints_2]', 'U') IS NOT NULL DROP TABLE [T_constraints_2];
IF OBJECT_ID('[T_constraints_1]', 'U') IS NOT NULL DROP TABLE [T_constraints_1];
CREATE TABLE [T_constraints_1]
(
    [C_id] INT NOT NULL IDENTITY PRIMARY KEY,
    [C_not_null] INT NOT NULL,
    [C_check] VARCHAR(255) NULL CHECK ([C_check] <> ''),
    [C_unique] INT NOT NULL,
    [C_default] INT NOT NULL DEFAULT 0,
    CONSTRAINT [CN_unique] UNIQUE ([C_unique])
);
CREATE TABLE [T_constraints_2]
(
    [C_id_1] INT NOT NULL,
    [C_id_2] INT NOT NULL,
    [C_index_1] INT NULL,
    [C_index_2_1] INT NULL,
    [C_index_2_2] INT NULL,
    CONSTRAINT [CN_constraints_2_multi] UNIQUE ([C_index_2_1], [C_index_2_2]),
    CONSTRAINT [CN_pk] PRIMARY KEY ([C_id_1], [C_id_2])
);
CREATE INDEX [CN_constraints_2_single] ON [T_constraints_2] ([C_index_1]);
CREATE TABLE [T_constraints_3]
(
    [C_id] INT NOT NULL,
    [C_fk_id_1] INT NOT NULL,
    [C_fk_id_2] INT NOT NULL,
    CONSTRAINT [CN_constraints_3] FOREIGN KEY ([C_fk_id_1], [C_fk_id_2]) REFERENCES [T_constraints_2] ([C_id_1], [C_id_2]) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE [T_constraints_4]
(
    [C_id] INT NOT NULL IDENTITY  PRIMARY KEY,
    [C_col_1] INT NULL,
    [C_col_2] INT NOT NULL,
    CONSTRAINT [CN_constraints_4] UNIQUE ([C_col_1], [C_col_2])
);

