IF OBJECT_ID('[datetime]', 'U') IS NOT NULL DROP TABLE [datetime];

-- Use DATETIME function to convert data type of default value
CREATE TABLE [dbo].[datetime] (
    [id] [int] IDENTITY NOT NULL,
    [Mydatetime1] [varchar](10) NOT NULL DEFAULT CAST(datediff(day, '2005-12-31', '2006-01-01') AS varchar(10)) + ' days',
    [Mydatetime2] [varchar](10) NOT NULL DEFAULT DATENAME(month, '2023-02-21'),
    [Mydatetime3] [int] NOT NULL DEFAULT DATEPART(month, '2023-02-21'),
    [Mydatetime4] [int] NOT NULL DEFAULT DAY('2023-02-21'),
    [Mydatetime5] [int] NOT NULL DEFAULT MONTH('2023-02-21'),
    [Mydatetime6] [int] NOT NULL DEFAULT YEAR('2023-02-21'),
    CONSTRAINT [PK_datetime_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
