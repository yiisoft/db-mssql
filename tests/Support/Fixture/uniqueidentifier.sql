IF OBJECT_ID('[uniqueidentifier]', 'U') IS NOT NULL DROP TABLE [uniqueidentifier];
IF OBJECT_ID('[uniqueidentifier_default]', 'U') IS NOT NULL DROP TABLE [uniqueidentifier_default];

CREATE TABLE [dbo].[uniqueidentifier] (
    [id] [int] IDENTITY NOT NULL,
    [Myuniqueidentifier1] [uniqueidentifier] NOT NULL,
    [Myuniqueidentifier2] [uniqueidentifier],
    CONSTRAINT [PK_uniqueidentifier_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);


CREATE TABLE [dbo].[uniqueidentifier_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myuniqueidentifier] [uniqueidentifier] NOT NULL DEFAULT '12345678-1234-1234-1234-123456789012',
    CONSTRAINT [PK_uniqueidentifier_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

