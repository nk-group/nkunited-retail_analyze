CREATE TABLE [dbo].[purchase_slip](
	[input_number] [int] NOT NULL,
	[line_number] [smallint] NOT NULL,
	[slip_number] [int] NOT NULL,
	[store_code] [nvarchar](20) NOT NULL,
	[store_name] [nvarchar](100) NULL,
	[supplier_code] [nvarchar](50) NOT NULL,
	[supplier_name] [nvarchar](150) NULL,
	[purchase_type] [nvarchar](50) NULL,
	[payable_type] [nvarchar](50) NULL,
	[purchase_expense_type] [nvarchar](50) NULL,
	[purchase_date] [date] NOT NULL,
	[payable_date] [date] NULL,
	[staff_code] [nvarchar](20) NULL,
	[staff_name] [nvarchar](100) NULL,
	[order_number] [int] NULL,
	[order_line] [smallint] NULL,
	[jan_code] [nvarchar](50) NULL,
	[sku_code] [nvarchar](50) NULL,
	[manufacturer_code] [nvarchar](8) NULL,
	[department_code] [nvarchar](50) NULL,
	[product_number] [nvarchar](50) NULL,
	[product_name] [nvarchar](200) NULL,
	[manufacturer_color_code] [nvarchar](20) NULL,
	[color_code] [nvarchar](50) NULL,
	[color_name] [nvarchar](100) NULL,
	[size_code] [nvarchar](50) NULL,
	[size_name] [nvarchar](100) NULL,
	[cost_price] [decimal](12, 2) NULL,
	[cost_price_tax_included] [decimal](12, 2) NULL,
	[selling_price] [decimal](12, 2) NULL,
	[selling_price_tax_included] [decimal](12, 2) NULL,
	[purchase_quantity] [int] NULL,
	[purchase_amount] [decimal](12, 2) NULL,
	[purchase_amount_tax_included] [decimal](12, 2) NULL,
	[updated_at] [datetime2](3) NULL,
 CONSTRAINT [PK_purchase_slip] PRIMARY KEY CLUSTERED 
(
	[input_number] ASC,
	[line_number] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'システム内部での入力番号（主キーの一部）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip', @level2type=N'COLUMN',@level2name=N'input_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'伝票内の行番号（主キーの一部）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip', @level2type=N'COLUMN',@level2name=N'line_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'仕入伝票の番号' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip', @level2type=N'COLUMN',@level2name=N'slip_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'仕入数量。負数の場合は返品を表す' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip', @level2type=N'COLUMN',@level2name=N'purchase_quantity'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'元の更新日付と更新時間を統合した日時情報' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip', @level2type=N'COLUMN',@level2name=N'updated_at'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'仕入伝票データを格納するテーブル。各商品の仕入情報を行単位で管理する。' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'purchase_slip'
GO

