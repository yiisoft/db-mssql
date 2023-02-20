IF OBJECT_ID('[image]', 'U') IS NOT NULL DROP TABLE [image];
IF OBJECT_ID('[image_default]', 'U') IS NOT NULL DROP TABLE [image_default];

CREATE TABLE [dbo].[image] (
    [id] [int] IDENTITY NOT NULL,
    [Myimage1] [image] NOT NULL,
    [Myimage2] [image],
    CONSTRAINT [PK_image_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[image_default] (
    [id] [int] IDENTITY NOT NULL,
    [Myimage] [image] NOT NULL DEFAULT 'image',
    CONSTRAINT [PK_image_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
