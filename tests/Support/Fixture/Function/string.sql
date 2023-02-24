IF OBJECT_ID('[string]', 'U') IS NOT NULL DROP TABLE [string];

-- Use string functions for default values with uppercase and lowercase function names and parameters.
CREATE TABLE [dbo].[string] (
    [id] [int] IDENTITY NOT NULL,
    [Myascii] [int] NOT NULL DEFAULT ascii('a'),
    [Mychar] [char](1) NOT NULL DEFAULT CHAR(97),
    [Mycharindex] [int] NOT NULL DEFAULT charindex('B', 'aBc'),
    [Myconcat] [varchar](3) NOT NULL DEFAULT CONCAT('a', 'b', 'c'),
    [Myconcatws] [varchar](3) NOT NULL DEFAULT CONCAT_WS('a', 'b', 'C'),
    [Mycomplex] [varchar](10) NOT NULL DEFAULT SUBSTRING(STUFF(concat('a', 'b', 'c'), 3, 1, concat_ws('f', 'g', 'h')), 5, 1),
    [Mydatalength] [int] NOT NULL DEFAULT DATALENGTH('abc'),
    [Myleft] [varchar](1) NOT NULL DEFAULT LEFT('abc', 1),
    [Mylen] [int] NOT NULL DEFAULT LEN('abc'),
    [Mylower] [varchar](3) NOT NULL DEFAULT LOWER('ABC'),
    [Myltrim] [varchar](3) NOT NULL DEFAULT LTRIM(' abc'),
    [Mynchar] [nchar](1) NOT NULL DEFAULT NCHAR(50),
    [Mypatindex] [int] NOT NULL DEFAULT PATINDEX('a', 'abc'),
    [Myreplace] [varchar](3) NOT NULL DEFAULT REPLACE('abc', 'a', 'd'),
    [Myright] [varchar](1) NOT NULL DEFAULT RIGHT('abc', 1),
    [Myrtrim] [varchar](3) NOT NULL DEFAULT RTRIM('abc '),
    [Mystr] [varchar](5) NOT NULL DEFAULT STR(1.234, 5, 2),
    [Mystuff] [varchar](3) NOT NULL DEFAULT STUFF('abc', 1, 1, 'd'),
    [Mysubstring] [varchar](3) NOT NULL DEFAULT SUBSTRING('abc', 1, 1),
    [Myupper] [varchar](3) NOT NULL DEFAULT UPPER('abc'),
    CONSTRAINT [PK_string_pk] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);
