IF OBJECT_ID('[dbo].[order]', 'U') IS NOT NULL DROP TABLE [dbo].[order];
CREATE TABLE [dbo].[order] (
    [id] [int] IDENTITY NOT NULL,
    [customer_id] [int] NOT NULL,
    [created_at] [int] NOT NULL,
    [total] [decimal](10,0) NOT NULL,
    CONSTRAINT [PK_order] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (1, 1325282384, 110.0);
INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (2, 1325334482, 33.0);
INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (2, 1325502201, 40.0);
