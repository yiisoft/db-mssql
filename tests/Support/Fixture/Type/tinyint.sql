IF OBJECT_ID('[tinyint]', 'U') IS NOT NULL DROP TABLE [tinyint];
IF OBJECT_ID('[tinyint_default]', 'U') IS NOT NULL DROP TABLE [tinyint_default];

CREATE TABLE [dbo].[tinyint] (
    [id] [int] IDENTITY NOT NULL,
    [Mytinyint1] [tinyint] NOT NULL,
    [Mytinyint2] [tinyint],
    CONSTRAINT [PK_tinyint_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[tinyint_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mytinyint] [tinyint] NOT NULL DEFAULT 255,
    CONSTRAINT [PK_tinyint_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
