IF OBJECT_ID('[real]', 'U') IS NOT NULL DROP TABLE [real];
IF OBJECT_ID('[real_default]', 'U') IS NOT NULL DROP TABLE [real_default];

CREATE TABLE [dbo].[real] (
    [id] [int] IDENTITY NOT NULL,
    [Myreal1] [real] NOT NULL,
    [Myreal2] [real],
    CONSTRAINT [PK_real_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[real_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myreal] [real] NOT NULL DEFAULT 3.40E+38,
    CONSTRAINT [PK_real_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
