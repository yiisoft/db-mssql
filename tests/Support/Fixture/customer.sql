IF OBJECT_ID('[dbo].[customer]', 'U') IS NOT NULL DROP TABLE [dbo].[customer];
CREATE TABLE [dbo].[customer] (
    [id] [int] IDENTITY NOT NULL,
    [email] [varchar](128) NOT NULL,
    [name] [varchar](128),
    [address] [text],
    [status] [int] DEFAULT 0,
    [profile_id] [int],
    CONSTRAINT [PK_customer] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user1@example.com', 'user1', 'address1', 1, 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status]) VALUES ('user2@example.com', 'user2', 'address2', 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user3@example.com', 'user3', 'address3', 2, 2);
