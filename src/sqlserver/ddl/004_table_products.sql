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
	[initial_registration_date] [date] NULL,
	[last_modified_datetime] [datetime2](3) NULL,
 CONSTRAINT [PK_Products_JanCode] PRIMARY KEY CLUSTERED 
(
	[jan_code] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[products] ADD  CONSTRAINT [DF__Products__initia__3B75D760]  DEFAULT (getdate()) FOR [initial_registration_date]
GO

ALTER TABLE [dbo].[products] ADD  CONSTRAINT [DF__Products__last_m__3C69FB99]  DEFAULT (getdate()) FOR [last_modified_datetime]
GO

