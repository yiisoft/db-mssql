IF OBJECT_ID('[int]', 'U') IS NOT NULL DROP TABLE [int];
IF OBJECT_ID('[int_default]', 'U') IS NOT NULL DROP TABLE [int_default];

CREATE TABLE [dbo].[int] (
    [id] [int] IDENTITY NOT NULL,
    [Myint1] [int] NOT NULL,
    [Myint2] [int],
    CONSTRAINT [PK_int_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[int_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myint] [int] NOT NULL DEFAULT 2147483647,
    CONSTRAINT [PK_int_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
