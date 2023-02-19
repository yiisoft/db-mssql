IF OBJECT_ID('[decimal]', 'U') IS NOT NULL DROP TABLE [decimal];
IF OBJECT_ID('[decimal_default]', 'U') IS NOT NULL DROP TABLE [decimal_default];

CREATE TABLE [dbo].[decimal] (
    [id] [int] IDENTITY NOT NULL,
    [Mydecimal1] [decimal](38,0) NOT NULL,
    [Mydecimal2] [decimal](38,0),
    CONSTRAINT [PK_decimal_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[decimal_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mydecimal] [decimal](38,0) NOT NULL DEFAULT 1e+38,
    CONSTRAINT [PK_decimal_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
