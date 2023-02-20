IF OBJECT_ID('[varbinary]', 'U') IS NOT NULL DROP TABLE [varbinary];
IF OBJECT_ID('[varbinary_default]', 'U') IS NOT NULL DROP TABLE [varbinary_default];

CREATE TABLE [dbo].[varbinary] (
    [id] [int] IDENTITY NOT NULL,
    [Myvarbinary1] [varbinary](10) NOT NULL,
    [Myvarbinary2] [varbinary](10),
    [Myvarbinary3] [varbinary](100) NOT NULL,
    [Myvarbinary4] [varbinary](100),
    CONSTRAINT [PK_varbinary_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[varbinary_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myvarbinary1] [varbinary](10) NOT NULL DEFAULT CONVERT(varbinary(10), 'varbinary'),
    [Myvarbinary2] [varbinary](100) NOT NULL DEFAULT CONVERT(varbinary(100), 'v'),
    CONSTRAINT [PK_varbinary_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
