## テーブル定義書

### 1. 取込タスク管理テーブル (`import_tasks`)

**テーブル名:** `import_tasks`

**テーブル説明:** アップロードされたファイル取り込み処理のタスク状況を管理するテーブルです。

| No. | 論理名                     | 物理名                      | データ型        | 桁数/精度      | NULL許容 | PK | デフォルト値    | 備考                                                    |
|-----|------------------------------|-----------------------------|-----------------|----------------|----------|----|---------------|---------------------------------------------------------|
| 1   | ID                         | `id`                        | `INT`           |                | `N`      | `Y`  | IDENTITY(1,1) | 主キー、自動採番                                        |
| 2   | ステータス                   | `status`                    | `VARCHAR(20)`   | `20`           | `N`      |    | `'pending'`   | `pending`, `processing`, `success`, `failed`            |
| 3   | 取込対象データ名             | `target_data_name`          | `VARCHAR(100)`  | `100`          | `N`      |    |               | `'product_master'`, `'department_master'` など          |
| 4   | オリジナルファイル名         | `original_file_name`        | `NVARCHAR(255)` | `255`          | `N`      |    |               | アップロードされた元のファイル名                              |
| 5   | 保存ファイルパス             | `stored_file_path`          | `NVARCHAR(512)` | `512`          | `N`      |    |               | サーバー上の実際のファイル保存パス                          |
| 6   | アップロード日時             | `uploaded_at`               | `DATETIME2(3)`  |                | `N`      |    | `GETDATE()`   | ファイルがアップロードされた日時                              |
| 7   | アップロードユーザー         | `uploaded_by`               | `NVARCHAR(100)` | `100`          | `Y`      |    |               | アップロードしたユーザーの識別子 (例: ユーザー名)             |
| 8   | 処理開始日時               | `processing_started_at`     | `DATETIME2(3)`  |                | `Y`      |    |               | バックグラウンド処理が開始された日時                          |
| 9   | 処理終了日時               | `processing_finished_at`    | `DATETIME2(3)`  |                | `Y`      |    |               | バックグラウンド処理が完了 (成功/失敗) した日時              |
| 10  | 処理結果メッセージ           | `result_message`            | `NVARCHAR(MAX)` | `MAX`          | `Y`      |    |               | 処理結果のサマリーやエラー詳細                              |

---

### 2. 商品マスタテーブル (`Products`)

**テーブル名:** `Products`

**テーブル説明:** 商品の基本情報を管理するマスタテーブルです。

| No. | 論理名                     | 物理名                          | データ型        | 桁数/精度      | NULL許容 | PK | デフォルト値    | 備考                                         |
|-----|------------------------------|---------------------------------|-----------------|----------------|----------|----|---------------|----------------------------------------------|
| 1   | JANコード                  | `jan_code`                      | `VARCHAR(20)`   | `20`           | `N`      | `Y`  |               | 主キー                                       |
| 2   | SKUコード                  | `sku_code`                      | `VARCHAR(10)`   | `10`           | `Y`      |    |               |                                              |
| 3   | メーカーコード               | `manufacturer_code`             | `NVARCHAR(8)`   | `8`            | `Y`      |    |               | 半角カタカナ等も考慮                               |
| 4   | 品番                       | `product_number`                | `VARCHAR(20)`   | `20`           | `Y`      |    |               |                                              |
| 5   | 略名                       | `short_name`                    | `NVARCHAR(50)`  | `50`           | `Y`      |    |               | 商品の略称                                   |
| 6   | 品名                       | `product_name`                  | `NVARCHAR(200)` | `200`          | `N`      |    |               | 正式な商品名                                 |
| 7   | 部門コード                 | `department_code`               | `VARCHAR(10)`   | `10`           | `Y`      |    |               |                                              |
| 8   | メーカーカラーコード         | `manufacturer_color_code`       | `NVARCHAR(20)`  | `20`           | `Y`      |    |               | 半角カタカナ等も考慮                               |
| 9   | カラーコード               | `color_code`                    | `VARCHAR(4)`    | `4`            | `Y`      |    |               |                                              |
| 10  | サイズコード               | `size_code`                     | `VARCHAR(4)`    | `4`            | `Y`      |    |               |                                              |
| 11  | 年度                       | `product_year`                  | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 例: "2025"                                   |
| 12  | 季節コード                 | `season_code`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 例: "SS", "AW"                               |
| 13  | 仕入先コード               | `supplier_code`                 | `VARCHAR(20)`   | `20`           | `Y`      |    |               |                                              |
| 14  | 売価                       | `selling_price`                 | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               | 税抜の販売価格                               |
| 15  | 税込売価                   | `selling_price_tax_included`    | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               | 税込の販売価格                               |
| 16  | 原価                       | `cost_price`                    | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               | 税抜の原価                                   |
| 17  | 税込原価                   | `cost_price_tax_included`       | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               | 税込の原価                                   |
| 18  | M単価                      | `m_unit_price`                  | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               | 特別な単価など（意味合いに応じて命名変更も検討）   |
| 19  | 税込M単価                  | `m_unit_price_tax_included`     | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               |                                              |
| 20  | 最終仕入原価               | `last_purchase_cost`            | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               |                                              |
| 21  | 最終仕入日                 | `last_purchase_date`            | `DATETIME2(3)`  |                | `Y`      |    |               |                                              |
| 22  | 標準仕入原価               | `standard_purchase_cost`        | `DECIMAL(12,2)` | `12,2`         | `Y`      |    |               |                                              |
| 23  | 属性1                      | `attribute_1`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 汎用的な属性フィールド1                          |
| 24  | 属性2                      | `attribute_2`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 汎用的な属性フィールド2                          |
| 25  | 属性3                      | `attribute_3`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 汎用的な属性フィールド3                          |
| 26  | 属性4                      | `attribute_4`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 汎用的な属性フィールド4                          |
| 27  | 属性5                      | `attribute_5`                   | `VARCHAR(4)`    | `4`            | `Y`      |    |               | 汎用的な属性フィールド5                          |
| 28  | 仕入形態ID                 | `purchase_type_id`              | `INT`           |                | `Y`      |    |               | 別テーブルのIDを参照するか、区分値                   |
| 29  | 商品区分ID                 | `product_classification_id`     | `INT`           |                | `Y`      |    |               | 別テーブルのIDを参照するか、区分値                   |
| 30  | 在庫管理フラグ/区分        | `inventory_management_flag`     | `INT`           |                | `Y`      |    |               | 在庫管理の対象か、方法を示すフラグや区分値           |
| 31  | 初回登録日                 | `initial_registration_date`     | `DATE`          |                | `Y`      |    | `GETDATE()`   |                                              |
| 32  | 最終変更日時               | `last_modified_datetime`        | `DATETIME2(3)`  |                | `Y`      |    | `GETDATE()`   | レコードの最終変更日時                             |

---

**共通の注記:**

* **NULL許容:** 上記は一般的な設定です。業務要件に応じて必須項目 (`NOT NULL`) を見直してください。
* **データ型 `VARCHAR` vs `NVARCHAR`:**
    * `VARCHAR`: 英数字のみ、または特定の文字コードセット（日本語環境では通常Shift_JISやEUCなど）の文字を格納します。
    * `NVARCHAR`: Unicode文字（世界のほぼ全ての言語の文字）を格納します。日本語の文字列を確実に扱うためには `NVARCHAR` が推奨されます。コード系のフィールドでも、将来的に多言語対応の可能性がある場合や、半角カタカナ・特殊記号などを扱う場合は `NVARCHAR` の方が安全です。
* **デフォルト値:** `GETDATE()` はSQL Serverで現在の日時を取得する関数です。
* **インデックス:** 主キー以外の列で検索条件として頻繁に使用されるものには、パフォーマンス向上のために非クラスター化インデックスを作成することを検討してください。
