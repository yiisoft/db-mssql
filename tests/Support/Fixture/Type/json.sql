IF OBJECT_ID('[json]', 'U') IS NOT NULL DROP TABLE [json];
IF OBJECT_ID('[json_default]', 'U') IS NOT NULL DROP TABLE [json_default];

CREATE TABLE [dbo].[json]
(
    [id] [int] IDENTITY NOT NULL,
    [Myjson] NVarChar(max),
    CONSTRAINT [PK_json_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
)

CREATE TABLE [dbo].[json_default]
(
    [id] [int] IDENTITY NOT NULL,
    [Myjson] NVarChar(max) DEFAULT ('{}'),
    CONSTRAINT [PK_json_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
)
