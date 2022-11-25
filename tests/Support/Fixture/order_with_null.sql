IF OBJECT_ID('[dbo].[order_with_null_fk]', 'U') IS NOT NULL DROP TABLE [dbo].[order_with_null_fk];
CREATE TABLE [dbo].[order_with_null_fk] (
    [id] [int] IDENTITY NOT NULL,
    [customer_id] [int] ,
    [created_at] [int] NOT NULL,
    [total] [decimal](10,0) NOT NULL,
    CONSTRAINT [PK_order_with_null_fk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[order_with_null_fk] ([customer_id], [created_at], [total]) VALUES (1, 1325282384, 110.0);
INSERT INTO [dbo].[order_with_null_fk] ([customer_id], [created_at], [total]) VALUES (2, 1325334482, 33.0);
INSERT INTO [dbo].[order_with_null_fk] ([customer_id], [created_at], [total]) VALUES (2, 1325502201, 40.0);
