IF OBJECT_ID('[text]', 'U') IS NOT NULL DROP TABLE [text];
IF OBJECT_ID('[text_default]', 'U') IS NOT NULL DROP TABLE [text_default];

CREATE TABLE [dbo].[text] (
    [id] [int] IDENTITY NOT NULL,
    [Mytext1] [text] NOT NULL,
    [Mytext2] [text],
    CONSTRAINT [PK_text_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[text_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mytext] [text] NOT NULL DEFAULT 'text',
    CONSTRAINT [PK_text_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
