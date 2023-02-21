IF OBJECT_ID('[binary]', 'U') IS NOT NULL DROP TABLE [binary];
IF OBJECT_ID('[binary_default]', 'U') IS NOT NULL DROP TABLE [binary_default];

CREATE TABLE [dbo].[binary] (
    [id] [int] IDENTITY NOT NULL,
    [Mybinary1] [binary](10) NOT NULL,
    [Mybinary2] [binary](10),
    [Mybinary3] [binary](1) NOT NULL,
    [Mybinary4] [binary](1),
    CONSTRAINT [PK_binary_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[binary_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mybinary1] [binary](10) NOT NULL DEFAULT CONVERT(binary(10), 'binary'),
    [Mybinary2] [binary](1) NOT NULL DEFAULT CONVERT(binary(1), 'b'),
    CONSTRAINT [PK_binary_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

