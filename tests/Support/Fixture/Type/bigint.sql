IF OBJECT_ID('[bigint]', 'U') IS NOT NULL DROP TABLE [bigint];
IF OBJECT_ID('[bigint_default]', 'U') IS NOT NULL DROP TABLE [bigint_default];

CREATE TABLE [dbo].[bigint] (
    [id] [int] IDENTITY NOT NULL,
    [Mybigint1] [bigint] NOT NULL,
    [Mybigint2] [bigint],
    CONSTRAINT [PK_bigint_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[bigint_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mybigint] [bigint] NOT NULL DEFAULT 9223372036854775807,
    CONSTRAINT [PK_bigint_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
