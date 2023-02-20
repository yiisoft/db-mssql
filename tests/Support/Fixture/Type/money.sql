
IF OBJECT_ID('[money]', 'U') IS NOT NULL DROP TABLE [money];
IF OBJECT_ID('[money_default]', 'U') IS NOT NULL DROP TABLE [money_default];

CREATE TABLE [dbo].[money] (
    [id] [int] IDENTITY NOT NULL,
    [Mymoney1] [money] NOT NULL,
    [Mymoney2] [money],
    CONSTRAINT [PK_money_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[money_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mymoney] [money] NOT NULL DEFAULT 922337203685477.5807,
    CONSTRAINT [PK_money_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
