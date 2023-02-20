IF OBJECT_ID('[smallmoney]', 'U') IS NOT NULL DROP TABLE [smallmoney];
IF OBJECT_ID('[smallmoney_default]', 'U') IS NOT NULL DROP TABLE [smallmoney_default];

CREATE TABLE [dbo].[smallmoney] (
    [id] [int] IDENTITY NOT NULL,
    [Mysmallmoney1] [smallmoney] NOT NULL,
    [Mysmallmoney2] [smallmoney],
    CONSTRAINT [PK_smallmoney_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[smallmoney_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mysmallmoney] [smallmoney] NOT NULL DEFAULT 214748.3647,
    CONSTRAINT [PK_smallmoney_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
