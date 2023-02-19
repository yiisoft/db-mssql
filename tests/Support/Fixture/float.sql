IF OBJECT_ID('[float]', 'U') IS NOT NULL DROP TABLE [float];
IF OBJECT_ID('[float_default]', 'U') IS NOT NULL DROP TABLE [float_default];

CREATE TABLE [dbo].[float] (
    [id] [int] IDENTITY NOT NULL,
    [Myfloat1] [float] NOT NULL,
    [Myfloat2] [float],
    CONSTRAINT [PK_float_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[float_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myfloat] [float] NOT NULL DEFAULT 2.23E-308,
    CONSTRAINT [PK_float_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
