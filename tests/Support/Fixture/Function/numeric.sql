IF OBJECT_ID('[numeric]', 'U') IS NOT NULL DROP TABLE [numeric];

-- Use NUMERIC function to convert data type of default value

CREATE TABLE [dbo].[numeric] (
    [id] [int] IDENTITY NOT NULL,
    [Myabs] [numeric](3, 1) NOT NULL DEFAULT ABS(-1),
    [Myacos] [numeric](8, 5) NOT NULL DEFAULT ACOS(-1.0),
    [Myasin] [numeric](7, 5) NOT NULL DEFAULT ASIN(0.1472738),
    [Myatan] [numeric](11, 7) NOT NULL DEFAULT ATAN((197.1099392)),
    [Myceiling] [money] NOT NULL DEFAULT CEILING($-123.45),
    [Mycos] [numeric](9, 6) NOT NULL DEFAULT COS(14.78),
    [Mycot] [numeric](9, 6) NOT NULL DEFAULT COT(124.1332),
    [Mydegrees] [numeric](18, 7) NOT NULL DEFAULT DEGREES((PI()/2)),
    [Myexp] [numeric](11, 5) NOT NULL DEFAULT EXP(10.0),
    [Myfloor] [int] NOT NULL DEFAULT FLOOR(-123.45),
    [Mylog] [numeric](6, 5) NOT NULL DEFAULT LOG(10.0),
    [Mylog10] [numeric](6, 5) NOT NULL DEFAULT LOG10(145.175643),
    [Mypi] [numeric](6, 5) NOT NULL DEFAULT PI(),
    [Mypower] [numeric](6, 3) NOT NULL DEFAULT POWER(2, 2.5),
    [Myradians] [numeric](7, 5) NOT NULL DEFAULT RADIANS(180.0),
    [Myrand] [numeric](7, 5) NOT NULL DEFAULT RAND(),
    [Myround] [numeric](8, 4) NOT NULL DEFAULT ROUND(123.9994, 3),
    [Mysign] [float] NOT NULL DEFAULT SIGN(-125),
    [Mysin] [numeric](8, 6) NOT NULL DEFAULT SIN(45.175643),
    [Mysqrt] [float] NOT NULL DEFAULT SQRT(10.0),
    CONSTRAINT [PK_numeric_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
