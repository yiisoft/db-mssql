IF OBJECT_ID('[dbo].[animal]', 'U') IS NOT NULL DROP TABLE [dbo].[animal];
CREATE TABLE [dbo].[animal] (
    [id] [int] IDENTITY NOT NULL,
    [type] [varchar](255) NOT NULL,
    CONSTRAINT [PK_animal] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
INSERT INTO [dbo].[animal] (type) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO [dbo].[animal] (type) VALUES ('yiiunit\data\ar\Dog');
IF OBJECT_ID('[dbo].[animal_view]', 'V') IS NOT NULL DROP VIEW [dbo].[animal_view];
CREATE VIEW [dbo].[animal_view] AS SELECT * FROM [dbo].[animal];
