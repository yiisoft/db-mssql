IF OBJECT_ID('[geometry]', 'U') IS NOT NULL DROP TABLE [geometry];
IF OBJECT_ID('[geometry_default]', 'U') IS NOT NULL DROP TABLE [geometry_default];

CREATE TABLE [dbo].[geometry] (
    [id] [int] IDENTITY NOT NULL,
    [Mygeometry1] [geometry] NOT NULL,
    [Mygeometry2] AS Mygeometry1.STAsText(),
    CONSTRAINT [PK_geometry_pk] PRIMARY KEY CLUSTERED
    (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[geometry_default] (
    [id] [int] IDENTITY NOT NULL,
    [Mygeometry1] [geometry] NOT NULL DEFAULT geometry::STGeomFromText('POINT(0 0)', 0),
    [Mygeometry2] AS Mygeometry1.STAsText(),
    CONSTRAINT [PK_geometry_default_pk] PRIMARY KEY CLUSTERED
    (
        [id] ASC
    ) ON [PRIMARY]
);
