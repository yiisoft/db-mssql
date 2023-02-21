IF OBJECT_ID('[cast]', 'U') IS NOT NULL DROP TABLE [cast];
IF OBJECT_ID('[convert]', 'U') IS NOT NULL DROP TABLE [convert];
IF OBJECT_ID('[trycast]', 'U') IS NOT NULL DROP TABLE [trycast];
IF OBJECT_ID('[tryconvert]', 'U') IS NOT NULL DROP TABLE [tryconvert];

-- Use CAST function to convert data type of default value
CREATE TABLE [dbo].[cast] (
    [id] [int] IDENTITY NOT NULL,
    [Mycast1] [int] NOT NULL DEFAULT CAST('1' AS int),
    [Mycast2] [int] NOT NULL DEFAULT CAST(14.85 AS int),
    [Mycast3] [float] NOT NULL DEFAULT CAST('14.85' AS float),
    [Mycast4] [varchar](4) NOT NULL DEFAULT CAST(15.6 AS varchar(4)),
    [Mycast5] [datetime] NOT NULL DEFAULT CAST('2023-02-21' AS DATETIME),
    [Mycast6] [binary](10) NOT NULL DEFAULT CAST('testme' AS binary),
    CONSTRAINT [PK_cast_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

-- Use CONVERT function to convert data type of default value
CREATE TABLE [dbo].[convert] (
    [id] [int] IDENTITY NOT NULL,
    [Myconvert1] [int] NOT NULL DEFAULT CONVERT(int, '1'),
    [Myconvert2] [int] NOT NULL DEFAULT CONVERT(int, 14.85),
    [Myconvert3] [float] NOT NULL DEFAULT CONVERT(float, '14.85'),
    [Myconvert4] [varchar](4) NOT NULL DEFAULT CONVERT(varchar(4), 15.6),
    [Myconvert5] [datetime] NOT NULL DEFAULT CONVERT(datetime, '2023-02-21'),
    [Myconvert6] [binary](10) NOT NULL DEFAULT CONVERT(binary, 'testme'),
    CONSTRAINT [PK_convert_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

-- Use TRY_CAST function to convert data type of default value
CREATE TABLE [dbo].[trycast] (
    [id] [int] IDENTITY NOT NULL,
    [Mytrycast1] [int] NOT NULL DEFAULT TRY_CAST('1' AS int),
    [Mytrycast2] [int] NOT NULL DEFAULT TRY_CAST(14.85 AS int),
    [Mytrycast3] [float] NOT NULL DEFAULT TRY_CAST('14.85' AS float),
    [Mytrycast4] [varchar](4) NOT NULL DEFAULT TRY_CAST(15.6 AS varchar(4)),
    [Mytrycast5] [datetime] NOT NULL DEFAULT TRY_CAST('2023-02-21' AS DATETIME),
    [Mytrycast6] [binary](10) NOT NULL DEFAULT TRY_CAST('testme' AS binary),
    CONSTRAINT [PK_trycast_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

-- Use TRY_CONVERT function to convert data type of default value
CREATE TABLE [dbo].[tryconvert] (
    [id] [int] IDENTITY NOT NULL,
    [Mytryconvert1] [int] NOT NULL DEFAULT TRY_CONVERT(int, '1'),
    [Mytryconvert2] [int] NOT NULL DEFAULT TRY_CONVERT(int, 14.85),
    [Mytryconvert3] [float] NOT NULL DEFAULT TRY_CONVERT(float, '14.85'),
    [Mytryconvert4] [varchar](4) NOT NULL DEFAULT TRY_CONVERT(varchar(4), 15.6),
    [Mytryconvert5] [datetime] NOT NULL DEFAULT TRY_CONVERT(datetime, '2023-02-21'),
    [Mytryconvert6] [binary](10) NOT NULL DEFAULT TRY_CONVERT(binary, 'testme'),
    CONSTRAINT [PK_tryconvert_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
