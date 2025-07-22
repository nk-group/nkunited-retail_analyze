CREATE TABLE [dbo].[manufacturers](
	[manufacturer_code] [nvarchar](8) NOT NULL,
	[manufacturer_name] [nvarchar](200) NOT NULL,
	[created_at] [datetime2](3) NULL,
	[updated_at] [datetime2](3) NULL,
 CONSTRAINT [PK_manufacturers] PRIMARY KEY CLUSTERED 
(
	[manufacturer_code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[manufacturers] ADD  CONSTRAINT [DF_manufacturers_created_at]  DEFAULT (getdate()) FOR [created_at]
GO

ALTER TABLE [dbo].[manufacturers] ADD  CONSTRAINT [DF_manufacturers_updated_at]  DEFAULT (getdate()) FOR [updated_at]
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'メーカーコード（主キー）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'manufacturers', @level2type=N'COLUMN',@level2name=N'manufacturer_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'メーカー名称' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'manufacturers', @level2type=N'COLUMN',@level2name=N'manufacturer_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'レコード作成日時' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'manufacturers', @level2type=N'COLUMN',@level2name=N'created_at'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'レコード更新日時' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'manufacturers', @level2type=N'COLUMN',@level2name=N'updated_at'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'メーカーマスタを格納するテーブル。メーカーコードと名称を管理する。' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'manufacturers'
GO

