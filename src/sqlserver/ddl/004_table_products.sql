CREATE TABLE [dbo].[products](
	[jan_code] [nvarchar](50) NOT NULL,
	[sku_code] [nvarchar](50) NULL,
	[manufacturer_code] [nvarchar](8) NULL,
	[product_number] [nvarchar](50) NULL,
	[short_name] [nvarchar](50) NULL,
	[product_name] [nvarchar](200) NOT NULL,
	[department_code] [nvarchar](50) NULL,
	[manufacturer_color_code] [nvarchar](20) NULL,
	[color_code] [nvarchar](50) NULL,
	[size_code] [nvarchar](50) NULL,
	[product_year] [nvarchar](50) NULL,
	[season_code] [nvarchar](50) NULL,
	[supplier_code] [nvarchar](50) NULL,
	[selling_price] [decimal](12, 2) NULL,
	[selling_price_tax_included] [decimal](12, 2) NULL,
	[cost_price] [decimal](12, 2) NULL,
	[cost_price_tax_included] [decimal](12, 2) NULL,
	[m_unit_price] [decimal](12, 2) NULL,
	[m_unit_price_tax_included] [decimal](12, 2) NULL,
	[last_purchase_cost] [decimal](12, 2) NULL,
	[last_purchase_date] [datetime2](3) NULL,
	[standard_purchase_cost] [decimal](12, 2) NULL,
	[attribute_1] [nvarchar](50) NULL,
	[attribute_2] [nvarchar](50) NULL,
	[attribute_3] [nvarchar](50) NULL,
	[attribute_4] [nvarchar](50) NULL,
	[attribute_5] [nvarchar](50) NULL,
	[purchase_type_id] [int] NULL,
	[product_classification_id] [int] NULL,
	[inventory_management_flag] [int] NULL,
	[deletion_scheduled_date] [date] NULL,
	[deletion_type] [int] NULL,
	[initial_registration_date] [date] NULL DEFAULT (CAST(GETDATE() AS DATE)),
	[last_modified_datetime] [datetime2](3) NULL DEFAULT (GETDATE()),	
 CONSTRAINT [PK_Products_JanCode] PRIMARY KEY CLUSTERED 
(
	[jan_code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

-- テーブルコメント
EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', 
    @value = N'商品マスタテーブル。商品の基本情報と削除管理情報を格納する。', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'products';

-- 主要列のコメント
EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'商品のJANコード（主キー）', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'products', 
    @level2type = N'COLUMN', @level2name = N'jan_code';


-- インデックス作成
CREATE INDEX IX_products_manufacturer_code ON [dbo].[products] ([manufacturer_code]);
CREATE INDEX IX_products_product_number ON [dbo].[products] ([product_number]);
CREATE INDEX IX_products_supplier_code ON [dbo].[products] ([supplier_code]);
CREATE INDEX IX_products_department_code ON [dbo].[products] ([department_code]);
CREATE INDEX IX_products_deletion_type ON [dbo].[products] ([deletion_type]);
CREATE INDEX IX_products_deletion_scheduled_date ON [dbo].[products] ([deletion_scheduled_date]);
