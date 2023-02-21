IF OBJECT_ID('[ntext]', 'U') IS NOT NULL DROP TABLE [ntext];
IF OBJECT_ID('[ntext_default]', 'U') IS NOT NULL DROP TABLE [ntext_default];

CREATE TABLE [dbo].[ntext] (
    [id] [int] IDENTITY NOT NULL,
    [Myntext1] [ntext] NOT NULL,
    [Myntext2] [ntext],
    CONSTRAINT [PK_ntext_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[ntext_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myntext] [ntext] NOT NULL DEFAULT 'ntext',
    CONSTRAINT [PK_ntext_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
