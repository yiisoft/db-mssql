IF OBJECT_ID('[smallint]', 'U') IS NOT NULL DROP TABLE [smallint];
IF OBJECT_ID('[smallint_default]', 'U') IS NOT NULL DROP TABLE [smallint_default];

CREATE TABLE [dbo].[smallint] (
    [id] [int] IDENTITY NOT NULL,
    [Mysmallint1] [smallint] NOT NULL,
    [Mysmallint2] [smallint],
    CONSTRAINT [PK_smallint_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[smallint_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mysmallint] [smallint] NOT NULL DEFAULT 32767,
    CONSTRAINT [PK_smallint_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
