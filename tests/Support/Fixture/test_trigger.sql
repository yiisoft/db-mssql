IF OBJECT_ID('[dbo].[test_trigger]', 'U') IS NOT NULL DROP TABLE [dbo].[test_trigger];
CREATE TABLE [dbo].[test_trigger] (
  [id] [int] IDENTITY NOT NULL,
  [stringcol] [varchar](32) DEFAULT NULL,
  PRIMARY KEY (id)
);
