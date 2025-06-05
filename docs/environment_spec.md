# **小売業向け販売分析システム - 環境情報資料**  

## **システム概要**  
### **システム名称**  
**CodeIgniter 4 小売業向け販売分析システム**  

本システムは、基幹システムから出力された各種マスタおよび伝票のExcelファイルをSQL Serverにデータとして取り込み、販売分析に活用することを目的としています。  

ファイルサイズが大きいExcelデータの取り込みに対応するため、Web画面の専用インターフェースからアップロードを行い、バックグラウンド処理でデータ登録を実行します。これにより、業務負荷を軽減し、スムーズなデータ管理を実現します。  

販売分析では、伝票の情報をもとに販売動向を可視化し、原価の回収率や店舗への品出し後の週ごとの販売状況を分析します。  
正確な販売データを活用することで、売れ行きの遅い商品の価格調整を検討し、売れ筋商品の選定・適切な補充発注を行い、売上の向上を促進します。  

---

## **プロジェクトフォルダ**  
`retail_analyze`  

---

## **システム構成**  

### **フレームワーク・言語**  
| **技術** | **バージョン** |  
|----------|--------------|  
| **PHP** | 8.2.28 以上 |  
| **フレームワーク** | CodeIgniter 4.6.1 |  
| **アーキテクチャ** | MVC（Model-View-Controller） |  
| **DBアクセス拡張コンポーネント** | Microsoft Drivers 5.12 for PHP for SQL Server (`php_pdo_sqlsrv_82_nts_x64.dll`, `php_sqlsrv_82_nts_x64.dll`) |  

### **フロントエンド技術**  
- **CSS フレームワーク**: Bootstrap 5.3  
- **アイコンライブラリ**: Bootstrap Icons 1.11.3  
- **JavaScript ライブラリ**:  
  - Flatpickr 4.6.13（日本語ローカライゼーション対応の日付ピッカー）  
- **レスポンシブデザイン**: 対応済み  

### **データベース**  
- **推奨**: Microsoft SQL Server 2022  
- **文字セット**: UTF-8 / Japanese_CI_AS  
- **日時形式**: SQL Server DATETIME2(7) 対応  

### **サーバー要件**  
| **項目** | **内容** |  
|----------|--------|  
| **Webサーバー** | IIS |  
| **PHP拡張モジュール** | `mbstring`, `intl`, `json`, `sqlsrv`, `curl`, `fileinfo`, `zip` |  

---

## **PHP設定（php.ini）**  
```ini
memory_limit = 4096M
post_max_size = 300M
upload_max_filesize = 200M

extension_dir = "ext"
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=openssl
extension=zip
extension=php_sqlsrv_82_nts_x64
extension=php_pdo_sqlsrv_82_nts_x64

mbstring.language = Japanese
```

---

## **ディレクトリ構造（開発中）**  
```plaintext
retail_analyze/
├── src/
│   ├── app/
│   │   ├── Commands/           # CLIコマンド
│   │   ├── Config/             # 設定ファイル
│   │   ├── Controllers/        # コントローラ
│   │   ├── Filters/            # フィルター
│   │   ├── Helpers/            # ヘルパー
│   │   ├── Libraries/          # ライブラリ
│   │   ├── Models/             # モデル
│   │   └── Views/              # ビュー
│   ├── public/                 # Webルート
│   │   └── assets/             # assets
│   │         ├── css/           # CSS
│   │         ├── js/            # Java Script
│   │         ├── bootstrap/     # Bootstrap
│   │         └── flatpickr/     # Flatpickr
│   ├── writable/               # 書き込み可能領域
│   ├── vendor/                 # Composer依存関係
│   └── sqlserver/              # SQL Server関連
│        ├── ddl/                # DDL
│        └── script/             # Stored,Functionなど
```

---

