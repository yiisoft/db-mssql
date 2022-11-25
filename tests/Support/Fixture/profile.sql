IF OBJECT_ID('[dbo].[profile]', 'U') IS NOT NULL DROP TABLE [dbo].[profile];
CREATE TABLE [dbo].[profile] (
    [id] [int] IDENTITY NOT NULL,
    [description] [varchar](128) NOT NULL,
    CONSTRAINT [PK_profile] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[profile] ([description]) VALUES ('profile customer 1');
INSERT INTO [dbo].[profile] ([description]) VALUES ('profile customer 3');
