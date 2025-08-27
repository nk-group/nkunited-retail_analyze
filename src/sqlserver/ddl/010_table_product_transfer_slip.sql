CREATE TABLE [dbo].[product_transfer_slip](
	[input_number] [int] NOT NULL,
	[line_number] [smallint] NOT NULL,
	[transfer_slip_number] [int] NOT NULL,
	[store_code] [nvarchar](20) NOT NULL,
	[store_name] [nvarchar](100) NULL,
	[transfer_type] [nvarchar](50) NOT NULL,
	[transfer_date] [date] NOT NULL,
	[transfer_reason_code] [nvarchar](20) NULL,
	[transfer_reason_name] [nvarchar](100) NULL,
	[staff_code] [nvarchar](20) NULL,
	[staff_name] [nvarchar](100) NULL,
	[transfer_pair_id] [int] NULL,
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
	[selling_price] [decimal](12, 2) NULL,
	[transfer_quantity] [int] NULL,
	[cost_amount] [decimal](12, 2) NULL,
	[selling_amount] [decimal](12, 2) NULL,
	[updated_at] [datetime2](3) NULL,
 CONSTRAINT [PK_product_transfer_slip] PRIMARY KEY CLUSTERED 
(
	[input_number] ASC,
	[line_number] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

-- テーブルコメント
EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', 
    @value = N'商品振替伝票データを格納するテーブル。生産伝票に近い性質を持ち、振替元の在庫を減らして振替先の商品の在庫を増やす処理を行単位で管理する。', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip';

-- カラムコメント
EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'システム内部での入力番号（主キーの一部）', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'input_number';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'伝票内の行番号（主キーの一部）', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'line_number';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替伝票の識別番号', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'transfer_slip_number';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替を実行する店舗のコード', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'store_code';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替の種別（OUT:振替元、IN:振替先）', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'transfer_type';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替実行日', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'transfer_date';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替元・振替先の関連を示すペア識別子', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'transfer_pair_id';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'商品のJANコード', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'jan_code';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'振替数量。OUT時は負数（在庫減少）、IN時は正数（在庫増加）', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'transfer_quantity';

EXEC sys.sp_addextendedproperty 
    @name = N'MS_Description', @value = N'元の更新日付と更新時間を統合した日時情報', 
    @level0type = N'SCHEMA', @level0name = N'dbo', 
    @level1type = N'TABLE', @level1name = N'product_transfer_slip', 
    @level2type = N'COLUMN', @level2name = N'updated_at';

-- インデックス作成
CREATE INDEX IX_product_transfer_slip_transfer_slip_number ON [dbo].[product_transfer_slip] ([transfer_slip_number]);
CREATE INDEX IX_product_transfer_slip_store_code ON [dbo].[product_transfer_slip] ([store_code]);
CREATE INDEX IX_product_transfer_slip_transfer_date ON [dbo].[product_transfer_slip] ([transfer_date]);
CREATE INDEX IX_product_transfer_slip_transfer_pair_id ON [dbo].[product_transfer_slip] ([transfer_pair_id]);
CREATE INDEX IX_product_transfer_slip_jan_code ON [dbo].[product_transfer_slip] ([jan_code]);

-- 振替区分のチェック制約（OUT または IN のみ許可）
ALTER TABLE [dbo].[product_transfer_slip] 
ADD CONSTRAINT CK_product_transfer_slip_transfer_type 
CHECK ([transfer_type] IN ('OUT', 'IN'));