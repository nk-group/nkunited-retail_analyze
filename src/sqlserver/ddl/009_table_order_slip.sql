CREATE TABLE [dbo].[order_slip](
	[order_number] [int] NOT NULL,
	[line_number] [smallint] NOT NULL,
	[supplier_code] [nvarchar](50) NOT NULL,
	[supplier_name] [nvarchar](150) NULL,
	[delivery_method] [nvarchar](50) NULL,
	[store_code] [nvarchar](20) NOT NULL,
	[store_name] [nvarchar](100) NULL,
	[order_type] [nvarchar](50) NULL,
	[order_date] [date] NOT NULL,
	[warehouse_delivery_date] [date] NULL,
	[store_delivery_date] [date] NULL,
	[customer_order_type] [nvarchar](50) NULL,
	[edi_type] [nvarchar](50) NULL,
	[staff_code] [nvarchar](20) NULL,
	[staff_name] [nvarchar](100) NULL,
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
	[order_quantity] [int] NULL,
	[order_amount] [decimal](12, 2) NULL,
	[order_amount_tax_included] [decimal](12, 2) NULL,
	[updated_at] [datetime2](3) NULL,
 CONSTRAINT [PK_order_slip] PRIMARY KEY CLUSTERED 
(
	[order_number] ASC,
	[line_number] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注番号（主キーの一部）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'order_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注内の行番号（主キーの一部）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'line_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'仕入先コード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'supplier_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'仕入先名称' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'supplier_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'納品方法（PC、店舗直送等）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'delivery_method'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注店舗コード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'store_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注店舗名' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'store_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注区分（手入力、自動等）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'order_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注日' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'order_date'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'倉庫納期日' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'warehouse_delivery_date'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'店舗納期日' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'store_delivery_date'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'客注区分（通常、客注等）' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'customer_order_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'EDI連携区分' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'edi_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注担当者コード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'staff_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'発注担当者名' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'staff_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'商品JANコード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'jan_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'商品SKUコード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'sku_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'メーカー識別コード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'manufacturer_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'商品部門コード' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'department_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'メーカー品番' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'order_slip', @level2type=N'COLUMN',@level2name=N'product_number'
GO