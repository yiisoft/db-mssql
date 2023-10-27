IF OBJECT_ID('[bit]', 'U') IS NOT NULL DROP TABLE [bit];
IF OBJECT_ID('[bit_default]', 'U') IS NOT NULL DROP TABLE [bit_default];

CREATE TABLE [dbo].[bit] (
    [id] [int] IDENTITY NOT NULL,
    [Mybit1] [bit],
    [Mybit2] [bit] NOT NULL,
    [Mybit3] [bit] NOT NULL,
    CONSTRAINT [PK_bit_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[bit_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mybit1] [bit] NOT NULL DEFAULT 0,
    [Mybit2] [bit] NOT NULL DEFAULT 1,
    CONSTRAINT [PK_bit_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
