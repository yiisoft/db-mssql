IF OBJECT_ID('[char]', 'U') IS NOT NULL DROP TABLE [char];
IF OBJECT_ID('[char_default]', 'U') IS NOT NULL DROP TABLE [char_default];

CREATE TABLE [dbo].[char] (
    [id] [int] IDENTITY NOT NULL,
    [Mychar1] [char](10) NOT NULL,
    [Mychar2] [char](10),
    [Mychar3] [char](1) NOT NULL,
    [Mychar4] [char](1),
    CONSTRAINT [PK_char_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[char_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mychar1] [char](10) NOT NULL DEFAULT 'char',
    [Mychar2] [char](1) NOT NULL DEFAULT 'c',
    CONSTRAINT [PK_char_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
