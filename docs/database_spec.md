# **小売業向け販売分析システム - テーブル定義書**

## データベース概要
- **データベース名**: NKUnited.Retail.Analyze
- **文字セット**: UTF-8 / Japanese_CI_AS
- **リカバリモデル**: SIMPLE

---

## 1. import_tasks（取込タスク管理テーブル）

### テーブル概要
アップロードされたファイルの処理状況や結果を管理するテーブル。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| ID | id | int IDENTITY(1,1) | NOT NULL | - | 主キー |
| ステータス | status | varchar(20) | NOT NULL | 'pending' | 処理状況 |
| 対象データ名 | target_data_name | varchar(100) | NOT NULL | - | 取込対象のデータ種別 |
| 元ファイル名 | original_file_name | nvarchar(255) | NOT NULL | - | アップロード時のファイル名 |
| 保存ファイルパス | stored_file_path | nvarchar(512) | NOT NULL | - | サーバー上の保存パス |
| アップロード日時 | uploaded_at | datetime2(7) | NOT NULL | getdate() | ファイルアップロード日時 |
| アップロード者 | uploaded_by | nvarchar(100) | NULL | - | アップロード実行者 |
| 処理開始日時 | processing_started_at | datetime2(7) | NULL | - | 処理開始日時 |
| 処理終了日時 | processing_finished_at | datetime2(7) | NULL | - | 処理完了日時 |
| 結果メッセージ | result_message | nvarchar(max) | NULL | - | 処理結果の詳細メッセージ |

### 制約
- **主キー**: id

---

## 2. manufacturers（メーカーマスタテーブル）

### テーブル概要
メーカーマスタを格納するテーブル。メーカーコードと名称を管理する。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| メーカーコード | manufacturer_code | nvarchar(8) | NOT NULL | - | 主キー |
| メーカー名称 | manufacturer_name | nvarchar(200) | NOT NULL | - | メーカー名 |
| レコード作成日時 | created_at | datetime2(3) | NULL | getdate() | 作成日時 |
| レコード更新日時 | updated_at | datetime2(3) | NULL | getdate() | 更新日時 |

### 制約
- **主キー**: manufacturer_code

---

## 3. products（商品マスタテーブル）

### テーブル概要
商品の基本情報と削除管理情報を格納するテーブル。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| JANコード | jan_code | nvarchar(50) | NOT NULL | - | 主キー |
| SKUコード | sku_code | nvarchar(50) | NULL | - | 在庫管理単位コード |
| メーカーコード | manufacturer_code | nvarchar(8) | NULL | - | メーカー識別コード |
| 品番 | product_number | nvarchar(50) | NULL | - | メーカー品番 |
| 略称 | short_name | nvarchar(50) | NULL | - | 商品略称 |
| 商品名 | product_name | nvarchar(200) | NOT NULL | - | 正式商品名 |
| 部門コード | department_code | nvarchar(50) | NULL | - | 商品分類部門 |
| メーカーカラーコード | manufacturer_color_code | nvarchar(20) | NULL | - | メーカー独自の色コード |
| カラーコード | color_code | nvarchar(50) | NULL | - | 標準カラーコード |
| サイズコード | size_code | nvarchar(50) | NULL | - | サイズ識別コード |
| 商品年度 | product_year | nvarchar(50) | NULL | - | 商品発売年度 |
| シーズンコード | season_code | nvarchar(50) | NULL | - | 販売シーズン |
| 仕入先コード | supplier_code | nvarchar(50) | NULL | - | 仕入先識別コード |
| 売価（税抜） | selling_price | decimal(12,2) | NULL | - | 定価（税抜） |
| 売価（税込） | selling_price_tax_included | decimal(12,2) | NULL | - | 定価（税込） |
| 原価（税抜） | cost_price | decimal(12,2) | NULL | - | 仕入原価（税抜） |
| 原価（税込） | cost_price_tax_included | decimal(12,2) | NULL | - | 仕入原価（税込） |
| M単価（税抜） | m_unit_price | decimal(12,2) | NULL | - | メーカー単価（税抜） |
| M単価（税込） | m_unit_price_tax_included | decimal(12,2) | NULL | - | メーカー単価（税込） |
| 最終仕入原価 | last_purchase_cost | decimal(12,2) | NULL | - | 最新の仕入単価 |
| 最終仕入日 | last_purchase_date | datetime2(3) | NULL | - | 最新の仕入日 |
| 標準仕入原価 | standard_purchase_cost | decimal(12,2) | NULL | - | 標準仕入単価 |
| 属性1 | attribute_1 | nvarchar(50) | NULL | - | 追加属性1 |
| 属性2 | attribute_2 | nvarchar(50) | NULL | - | 追加属性2 |
| 属性3 | attribute_3 | nvarchar(50) | NULL | - | 追加属性3 |
| 属性4 | attribute_4 | nvarchar(50) | NULL | - | 追加属性4 |
| 属性5 | attribute_5 | nvarchar(50) | NULL | - | 追加属性5 |
| 仕入区分ID | purchase_type_id | int | NULL | - | 仕入方法区分 |
| 商品分類ID | product_classification_id | int | NULL | - | 商品分類識別子 |
| 在庫管理フラグ | inventory_management_flag | int | NULL | - | 在庫管理対象フラグ |
| 廃盤予定日 | deletion_scheduled_date | date | NULL | - | 販売終了予定日 |
| 削除区分 | deletion_type | int | NULL | - | 削除処理区分 |
| 初期登録日 | initial_registration_date | date | NULL | CAST(GETDATE() AS DATE) | 初回登録日 |
| 最終更新日時 | last_modified_datetime | datetime2(3) | NULL | GETDATE() | 最終更新日時 |

### 制約
- **主キー**: jan_code

### インデックス
- IX_products_manufacturer_code
- IX_products_product_number
- IX_products_supplier_code
- IX_products_department_code
- IX_products_deletion_type
- IX_products_deletion_scheduled_date

---

## 4. purchase_slip（仕入伝票テーブル）

### テーブル概要
仕入伝票データを格納するテーブル。各商品の仕入情報を行単位で管理する。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| 入力番号 | input_number | int | NOT NULL | - | 主キー（複合）|
| 行番号 | line_number | smallint | NOT NULL | - | 主キー（複合）|
| 仕入伝票番号 | slip_number | int | NOT NULL | - | 伝票識別番号 |
| 店舗コード | store_code | nvarchar(20) | NOT NULL | - | 仕入店舗コード |
| 店舗名 | store_name | nvarchar(100) | NULL | - | 店舗名称 |
| 仕入先コード | supplier_code | nvarchar(50) | NOT NULL | - | 仕入先識別コード |
| 仕入先名 | supplier_name | nvarchar(150) | NULL | - | 仕入先名称 |
| 仕入区分 | purchase_type | nvarchar(50) | NULL | - | 仕入の種類 |
| 支払区分 | payable_type | nvarchar(50) | NULL | - | 支払方法区分 |
| 仕入費用区分 | purchase_expense_type | nvarchar(50) | NULL | - | 費用処理区分 |
| 仕入日 | purchase_date | date | NOT NULL | - | 仕入実行日 |
| 支払予定日 | payable_date | date | NULL | - | 支払期日 |
| 担当者コード | staff_code | nvarchar(20) | NULL | - | 処理担当者コード |
| 担当者名 | staff_name | nvarchar(100) | NULL | - | 処理担当者名 |
| 発注番号 | order_number | int | NULL | - | 関連発注番号 |
| 発注行番号 | order_line | smallint | NULL | - | 発注行識別番号 |
| JANコード | jan_code | nvarchar(50) | NULL | - | 商品JANコード |
| SKUコード | sku_code | nvarchar(50) | NULL | - | 商品SKUコード |
| メーカーコード | manufacturer_code | nvarchar(8) | NULL | - | メーカー識別コード |
| 部門コード | department_code | nvarchar(50) | NULL | - | 商品部門コード |
| 品番 | product_number | nvarchar(50) | NULL | - | メーカー品番 |
| 商品名 | product_name | nvarchar(200) | NULL | - | 商品名称 |
| メーカーカラーコード | manufacturer_color_code | nvarchar(20) | NULL | - | メーカー色コード |
| カラーコード | color_code | nvarchar(50) | NULL | - | 標準色コード |
| カラー名 | color_name | nvarchar(100) | NULL | - | 色名称 |
| サイズコード | size_code | nvarchar(50) | NULL | - | サイズ識別コード |
| サイズ名 | size_name | nvarchar(100) | NULL | - | サイズ名称 |
| 仕入単価（税抜） | cost_price | decimal(12,2) | NULL | - | 仕入単価（税抜） |
| 仕入単価（税込） | cost_price_tax_included | decimal(12,2) | NULL | - | 仕入単価（税込） |
| 売価（税抜） | selling_price | decimal(12,2) | NULL | - | 定価（税抜） |
| 売価（税込） | selling_price_tax_included | decimal(12,2) | NULL | - | 定価（税込） |
| 仕入数量 | purchase_quantity | int | NULL | - | 仕入数量（負数は返品） |
| 仕入金額（税抜） | purchase_amount | decimal(12,2) | NULL | - | 仕入金額（税抜） |
| 仕入金額（税込） | purchase_amount_tax_included | decimal(12,2) | NULL | - | 仕入金額（税込） |
| 更新日時 | updated_at | datetime2(3) | NULL | - | 統合更新日時 |

### 制約
- **主キー**: (input_number, line_number)

---

## 5. sales_slip（売上伝票テーブル）

### テーブル概要
売上伝票データを格納するテーブル。各商品の売上情報を行単位で管理する。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| 入力番号 | input_number | int | NOT NULL | - | 主キー（複合）|
| 行番号 | line_number | smallint | NOT NULL | - | 主キー（複合）|
| 売上伝票番号 | slip_number | int | NOT NULL | - | 伝票識別番号 |
| 店舗コード | store_code | nvarchar(20) | NOT NULL | - | 売上店舗コード |
| 店舗名 | store_name | nvarchar(100) | NULL | - | 店舗名称 |
| 売上区分 | sales_type | nvarchar(50) | NULL | - | 売上の種類 |
| 売上日 | sales_date | date | NOT NULL | - | 売上実行日 |
| 売上時刻 | sales_time | time(7) | NULL | - | 売上時刻 |
| 顧客コード | customer_code | nvarchar(50) | NULL | - | 顧客識別コード |
| 顧客区分 | customer_category | nvarchar(10) | NULL | - | 顧客分類 |
| 担当者コード | staff_code | nvarchar(20) | NULL | - | 販売担当者コード |
| 担当者名 | staff_name | nvarchar(100) | NULL | - | 販売担当者名 |
| JANコード | jan_code | nvarchar(50) | NULL | - | 商品JANコード |
| SKUコード | sku_code | nvarchar(50) | NULL | - | 商品SKUコード |
| メーカーコード | manufacturer_code | nvarchar(8) | NULL | - | メーカー識別コード |
| 部門コード | department_code | nvarchar(50) | NULL | - | 商品部門コード |
| 品番 | product_number | nvarchar(50) | NULL | - | メーカー品番 |
| 商品名 | product_name | nvarchar(200) | NULL | - | 商品名称 |
| メーカーカラーコード | manufacturer_color_code | nvarchar(20) | NULL | - | メーカー色コード |
| カラーコード | color_code | nvarchar(50) | NULL | - | 標準色コード |
| カラー名 | color_name | nvarchar(100) | NULL | - | 色名称 |
| サイズコード | size_code | nvarchar(50) | NULL | - | サイズ識別コード |
| サイズ名 | size_name | nvarchar(100) | NULL | - | サイズ名称 |
| 原価 | cost_price | decimal(12,2) | NULL | - | 商品原価 |
| 定価 | selling_price | decimal(12,2) | NULL | - | 商品定価 |
| 売上単価 | sales_unit_price | decimal(12,2) | NULL | - | 実際の販売単価 |
| 売上数量 | sales_quantity | int | NULL | - | 販売数量（負数は返品） |
| 売上金額 | sales_amount | decimal(12,2) | NULL | - | 売上金額 |
| 値引金額 | discount_amount | decimal(12,2) | NULL | - | 値引き額 |
| 更新日時 | updated_at | datetime2(3) | NULL | - | 統合更新日時 |

### 制約
- **主キー**: (input_number, line_number)

---

## 6. transfer_slip（移動伝票テーブル）

### テーブル概要
移動伝票データを格納するテーブル。各商品の店舗間移動情報を行単位で管理する。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| 入力番号 | input_number | int | NOT NULL | - | 主キー（複合）|
| 行番号 | line_number | smallint | NOT NULL | - | 主キー（複合）|
| 移動伝票番号 | slip_number | int | NOT NULL | - | 伝票識別番号 |
| 振出店舗コード | source_store_code | nvarchar(20) | NOT NULL | - | 移動元店舗コード |
| 振出店舗名 | source_store_name | nvarchar(100) | NULL | - | 移動元店舗名 |
| 受入店舗コード | destination_store_code | nvarchar(20) | NOT NULL | - | 移動先店舗コード |
| 受入店舗名 | destination_store_name | nvarchar(100) | NULL | - | 移動先店舗名 |
| 移動区分 | transfer_type | nvarchar(50) | NULL | - | 移動の種類 |
| 移動日 | transfer_date | date | NOT NULL | - | 移動実行日 |
| 担当者コード | staff_code | nvarchar(20) | NULL | - | 処理担当者コード |
| 担当者名 | staff_name | nvarchar(100) | NULL | - | 処理担当者名 |
| JANコード | jan_code | nvarchar(50) | NULL | - | 商品JANコード |
| SKUコード | sku_code | nvarchar(50) | NULL | - | 商品SKUコード |
| メーカーコード | manufacturer_code | nvarchar(8) | NULL | - | メーカー識別コード |
| 部門コード | department_code | nvarchar(50) | NULL | - | 商品部門コード |
| 品番 | product_number | nvarchar(50) | NULL | - | メーカー品番 |
| 商品名 | product_name | nvarchar(200) | NULL | - | 商品名称 |
| メーカーカラーコード | manufacturer_color_code | nvarchar(20) | NULL | - | メーカー色コード |
| カラーコード | color_code | nvarchar(50) | NULL | - | 標準色コード |
| カラー名 | color_name | nvarchar(100) | NULL | - | 色名称 |
| サイズコード | size_code | nvarchar(50) | NULL | - | サイズ識別コード |
| サイズ名 | size_name | nvarchar(100) | NULL | - | サイズ名称 |
| 原価 | cost_price | decimal(12,2) | NULL | - | 商品原価 |
| 売価 | selling_price | decimal(12,2) | NULL | - | 商品売価 |
| 移動数量 | transfer_quantity | int | NULL | - | 移動数量（負数は返品・戻り） |
| 原価金額 | cost_amount | decimal(12,2) | NULL | - | 原価ベース金額 |
| 売価金額 | selling_amount | decimal(12,2) | NULL | - | 売価ベース金額 |
| 更新日時 | updated_at | datetime2(3) | NULL | - | 統合更新日時 |

### 制約
- **主キー**: (input_number, line_number)

---

## 7. adjustment_slip（調整伝票テーブル）

### テーブル概要
調整伝票データを格納するテーブル。各商品の在庫調整情報を行単位で管理する。

### カラム定義

| 論理名 | 物理名 | データ型 | NULL | デフォルト値 | 備考 |
|--------|--------|----------|------|-------------|------|
| 入力番号 | input_number | int | NOT NULL | - | 主キー（複合）|
| 行番号 | line_number | smallint | NOT NULL | - | 主キー（複合）|
| 調整伝票番号 | slip_number | int | NOT NULL | - | 伝票識別番号 |
| 店舗コード | store_code | nvarchar(20) | NOT NULL | - | 調整実行店舗コード |
| 店舗名 | store_name | nvarchar(100) | NULL | - | 調整実行店舗名 |
| 調整区分 | adjustment_type | nvarchar(50) | NULL | - | 調整の種類（在庫増減等） |
| 調整日 | adjustment_date | date | NOT NULL | - | 調整実行日 |
| 調整理由コード | adjustment_reason_code | nvarchar(20) | NULL | - | 調整理由の識別コード |
| 調整理由名 | adjustment_reason_name | nvarchar(100) | NULL | - | 調整理由の名称 |
| 担当者コード | staff_code | nvarchar(20) | NULL | - | 処理担当者コード |
| 担当者名 | staff_name | nvarchar(100) | NULL | - | 処理担当者名 |
| JANコード | jan_code | nvarchar(50) | NULL | - | 商品JANコード |
| SKUコード | sku_code | nvarchar(50) | NULL | - | 商品SKUコード |
| メーカーコード | manufacturer_code | nvarchar(8) | NULL | - | メーカー識別コード |
| 部門コード | department_code | nvarchar(50) | NULL | - | 商品部門コード |
| 品番 | product_number | nvarchar(50) | NULL | - | メーカー品番 |
| 商品名 | product_name | nvarchar(200) | NULL | - | 商品名称 |
| メーカーカラーコード | manufacturer_color_code | nvarchar(20) | NULL | - | メーカー色コード |
| カラーコード | color_code | nvarchar(50) | NULL | - | 標準色コード |
| カラー名 | color_name | nvarchar(100) | NULL | - | 色名称 |
| サイズコード | size_code | nvarchar(50) | NULL | - | サイズ識別コード |
| サイズ名 | size_name | nvarchar(100) | NULL | - | サイズ名称 |
| 原価 | cost_price | decimal(12,2) | NULL | - | 商品原価 |
| 売価 | selling_price | decimal(12,2) | NULL | - | 商品売価 |
| 調整数量 | adjustment_quantity | int | NULL | - | 調整数量（正数：増加、負数：減少） |
| 原価金額 | cost_amount | decimal(12,2) | NULL | - | 原価ベース金額 |
| 売価金額 | selling_amount | decimal(12,2) | NULL | - | 売価ベース金額 |
| 更新日時 | updated_at | datetime2(3) | NULL | - | 統合更新日時 |

### 制約
- **主キー**: (input_number, line_number)

---

## テーブル関連図

```
manufacturers (メーカーマスタ)
│
├── products (商品マスタ) ※manufacturer_codeで関連
│   │
│   ├── purchase_slip (仕入伝票) ※jan_codeで関連
│   │
│   ├── sales_slip (売上伝票) ※jan_codeで関連
│   │
│   ├── transfer_slip (移動伝票) ※jan_codeで関連
│   │
│   └── adjustment_slip (調整伝票) ※jan_codeで関連
│
└── import_tasks (取込タスク管理) ※処理対象データとして関連
```

## データ運用に関する注意事項

1. **数量項目の負数処理**
   - `purchase_quantity`: 負数は返品を表す
   - `sales_quantity`: 負数は返品を表す
   - `transfer_quantity`: 負数は返品・戻りを表す
   - `adjustment_quantity`: 正数で増加、負数で減少を表す

2. **日時項目の統合**
   - 各伝票テーブルの`updated_at`は、元の更新日付と更新時間を統合した日時情報

3. **削除管理**
   - `products`テーブルでは`deletion_type`と`deletion_scheduled_date`で削除管理を行う

4. **主キー構成**
   - 伝票系テーブルは`input_number`と`line_number`の複合主キー

5. **文字エンコーディング**
   - nvarchar型を使用してUnicode対応