IF OBJECT_ID('[numeric]', 'U') IS NOT NULL DROP TABLE [numeric];
IF OBJECT_ID('[numeric_default]', 'U') IS NOT NULL DROP TABLE [numeric_default];

CREATE TABLE [dbo].[numeric] (
    [id] [int] IDENTITY NOT NULL,
    [Mynumeric1] [numeric](38,0) NOT NULL,
    [Mynumeric2] [numeric](38,0),
    CONSTRAINT [PK_numeric_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[numeric_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mynumeric] [numeric](38,0) NOT NULL DEFAULT 1e+38,
    CONSTRAINT [PK_numeric_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
