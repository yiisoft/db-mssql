IF OBJECT_ID('[rowversion]', 'U') IS NOT NULL DROP TABLE [rowversion];

CREATE TABLE [dbo].[rowversion] (
    [id] [int] IDENTITY NOT NULL,
    [Myrowversion] [rowversion] NOT NULL,
    CONSTRAINT [PK_rowversion_pk] PRIMARY KEY CLUSTERED
    (
        [id] ASC
    ) ON [PRIMARY]

);
