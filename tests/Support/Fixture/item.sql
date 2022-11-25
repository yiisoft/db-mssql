IF OBJECT_ID('[dbo].[item]', 'U') IS NOT NULL DROP TABLE [dbo].[item];
CREATE TABLE [dbo].[item] (
    [id] [int] IDENTITY NOT NULL,
    [name] [varchar](128) NOT NULL,
    [category_id] [int] NOT NULL,
    CONSTRAINT [PK_item] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[item] ([name], [category_id]) VALUES ('Agile Web Application Development with Yii1.1 and PHP5', 1);
INSERT INTO [dbo].[item] ([name], [category_id]) VALUES ('Yii 1.1 Application Development Cookbook', 1);
INSERT INTO [dbo].[item] ([name], [category_id]) VALUES ('Ice Age', 2);
INSERT INTO [dbo].[item] ([name], [category_id]) VALUES ('Toy Story', 2);
INSERT INTO [dbo].[item] ([name], [category_id]) VALUES ('Cars', 2);
