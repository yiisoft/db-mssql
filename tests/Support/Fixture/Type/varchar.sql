IF OBJECT_ID('[varchar]', 'U') IS NOT NULL DROP TABLE [varchar];
IF OBJECT_ID('[varchar_default]', 'U') IS NOT NULL DROP TABLE [varchar_default];

CREATE TABLE [dbo].[varchar] (
    [id] [int] IDENTITY NOT NULL,
    [Myvarchar1] [varchar](10) NOT NULL,
    [Myvarchar2] [varchar](10),
    [Myvarchar3] [varchar](100) NOT NULL,
    [Myvarchar4] [varchar](100),
    CONSTRAINT [PK_varchar_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[varchar_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myvarchar1] [varchar](10) NOT NULL DEFAULT 'varchar',
    [Myvarchar2] [varchar](100) NOT NULL DEFAULT 'v',
    CONSTRAINT [PK_varchar_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
