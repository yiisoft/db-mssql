IF OBJECT_ID('[date]', 'U') IS NOT NULL DROP TABLE [date];
IF OBJECT_ID('[date_default]', 'U') IS NOT NULL DROP TABLE [date_default];
IF OBJECT_ID('[date_default_expressions]', 'U') IS NOT NULL DROP TABLE [date_default_expressions];

CREATE TABLE [dbo].[date] (
    [id] [int] IDENTITY NOT NULL,
    [Mydate1] [date] NOT NULL,
    [Mydate2] [date],
    [Mydatetime1] [datetime] NOT NULL,
    [Mydatetime2] [datetime2],
    [Mydatetimeoffset1] [datetimeoffset] NOT NULL,
    [Mydatetimeoffset2] [datetimeoffset],
    [Mytime1] [time] NOT NULL,
    [Mytime2] [time],
    CONSTRAINT [PK_date_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[date_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mydate] [date] NOT NULL DEFAULT '2007-05-08 12:35:29. 1234567 +12:15',
    [Mydatetime] [datetime] NOT NULL DEFAULT '2007-05-08 12:35:29.123',
    [Mydatetime2] [datetime2] NOT NULL DEFAULT '2007-05-08 12:35:29. 1234567 +12:15',
    [Mydatetimeoffset] [datetimeoffset] NOT NULL DEFAULT '2007-05-08 12:35:29.1234567 +12:15',
    [Mytime] [time] NOT NULL DEFAULT '2007-05-08 12:35:29. 1234567 +12:15',
    CONSTRAINT [PK_date_default_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[date_default_expressions] (
    [id] [int] IDENTITY NOT NULL,
    [Mydate1] [date] NOT NULL DEFAULT GETUTCDATE(),
    [Mydate2] [date] NOT NULL DEFAULT GETDATE(),
    [Mydatetime1] [datetime] NOT NULL DEFAULT SYSDATETIME(),
    [Mydatetime2] [datetime] NOT NULL DEFAULT SYSDATETIMEOFFSET(),
    [Mydatetimeoffset] [datetimeoffset] NOT NULL DEFAULT SYSUTCDATETIME(),
    [Mytime] [time] NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT [PK_date_default_expressions_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
