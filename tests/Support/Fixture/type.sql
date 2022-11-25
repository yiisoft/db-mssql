IF OBJECT_ID('[dbo].[type]', 'U') IS NOT NULL DROP TABLE [dbo].[type];
CREATE TABLE [dbo].[type] (
    [int_col] [int] NOT NULL,
    [int_col2] [int] DEFAULT '1',
    [tinyint_col] [tinyint] DEFAULT '1',
    [smallint_col] [smallint] DEFAULT '1',
    [char_col] [char](100) NOT NULL,
    [char_col2] [varchar](100) DEFAULT 'something',
    [char_col3] [text],
    [float_col] [decimal](4,3) NOT NULL,
    [float_col2] [float] DEFAULT '1.23',
    [blob_col] [varbinary](MAX),
    [numeric_col] [decimal](5,2) DEFAULT '33.22',
    [time] [datetime] NOT NULL DEFAULT '2002-01-01 00:00:00',
    [bool_col] [tinyint] NOT NULL,
    [bool_col2] [tinyint] DEFAULT '1'
);
