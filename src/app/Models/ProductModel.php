<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'jan_code';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'jan_code', 'sku_code', 'manufacturer_code', 'product_number', 
        'short_name', 'product_name', 'department_code', 'manufacturer_color_code',
        'color_code', 'size_code', 'product_year', 'season_code', 'supplier_code',
        'selling_price', 'selling_price_tax_included', 'cost_price', 'cost_price_tax_included',
        'm_unit_price', 'm_unit_price_tax_included', 'last_purchase_cost', 'last_purchase_date',
        'standard_purchase_cost', 'attribute_1', 'attribute_2', 'attribute_3', 'attribute_4', 'attribute_5',
        'purchase_type_id', 'product_classification_id', 'inventory_management_flag',
        'deletion_scheduled_date', 'deletion_type', 'initial_registration_date', 'last_modified_datetime'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'initial_registration_date';
    protected $updatedField  = 'last_modified_datetime';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * JANコード配列から商品情報を取得
     * 
     * @param array $janCodes JANコード配列
     * @return array 商品情報配列（メーカー別にグループ化）
     */
    public function getProductsByJanCodes(array $janCodes): array
    {
        if (empty($janCodes)) {
            return [];
        }

        // 重複除去と正規化
        $janCodes = array_unique(array_filter($janCodes));
        
        if (empty($janCodes)) {
            return [];
        }

        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            p.*,
            m.manufacturer_name
        FROM products p
        LEFT JOIN manufacturers m ON p.manufacturer_code = m.manufacturer_code
        WHERE p.jan_code IN ({$janCodesPlaceholder})
        ORDER BY p.manufacturer_code, p.product_number, p.product_name, p.jan_code
        ";
        
        $results = $this->db->query($sql, $janCodes)->getResultArray();
        
        // 各商品に追加情報を付与
        foreach ($results as &$product) {
            $product['size_name'] = $this->generateSizeName($product['size_code']);
            $product['color_name'] = $this->generateColorName($product['color_code'], $product['manufacturer_color_code']);
            $product['display_name'] = $this->generateDisplayName($product);
            $product['is_discontinued'] = $this->isDiscontinuedItem($product);
            $product['effective_cost_price'] = $this->getEffectiveCostPrice($product);
        }
        
        log_message('info', 'ProductModel::getProductsByJanCodes 取得件数: ' . count($results));
        
        return $results;
    }

    /**
     * SKUコード配列からJANコードを取得
     * 
     * @param array $skuCodes SKUコード配列
     * @return array JANコード配列
     */
    public function getJanCodesBySku(array $skuCodes): array
    {
        if (empty($skuCodes)) {
            return [];
        }

        // 重複除去と正規化
        $skuCodes = array_unique(array_filter($skuCodes));
        
        if (empty($skuCodes)) {
            return [];
        }

        $skuCodesPlaceholder = str_repeat('?,', count($skuCodes) - 1) . '?';
        
        $sql = "
        SELECT jan_code, sku_code
        FROM products
        WHERE sku_code IN ({$skuCodesPlaceholder})
          AND sku_code IS NOT NULL
          AND sku_code != ''
        ORDER BY sku_code
        ";
        
        $results = $this->db->query($sql, $skuCodes)->getResultArray();
        
        log_message('info', 'ProductModel::getJanCodesBySku SKU入力: ' . count($skuCodes) . ', JAN取得: ' . count($results));
        
        return array_column($results, 'jan_code');
    }

    /**
     * JANコードの存在チェック
     * 
     * @param array $janCodes JANコード配列
     * @return array バリデーション結果
     */
    public function validateJanCodes(array $janCodes): array
    {
        if (empty($janCodes)) {
            return [
                'valid' => false,
                'message' => 'JANコードが指定されていません',
                'valid_codes' => [],
                'invalid_codes' => [],
                'found_count' => 0
            ];
        }

        // 重複除去と正規化
        $janCodes = array_unique(array_filter($janCodes));
        
        if (empty($janCodes)) {
            return [
                'valid' => false,
                'message' => '有効なJANコードが指定されていません',
                'valid_codes' => [],
                'invalid_codes' => [],
                'found_count' => 0
            ];
        }

        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT jan_code
        FROM products
        WHERE jan_code IN ({$janCodesPlaceholder})
        ";
        
        $results = $this->db->query($sql, $janCodes)->getResultArray();
        $foundJanCodes = array_column($results, 'jan_code');
        $invalidJanCodes = array_diff($janCodes, $foundJanCodes);
        
        $isValid = count($foundJanCodes) > 0;
        $message = '';
        
        if (!$isValid) {
            $message = '指定されたJANコードが見つかりません';
        } elseif (!empty($invalidJanCodes)) {
            $message = count($invalidJanCodes) . '個のJANコードが見つかりませんでした';
        } else {
            $message = 'すべてのJANコードが確認されました';
        }
        
        return [
            'valid' => $isValid,
            'message' => $message,
            'valid_codes' => $foundJanCodes,
            'invalid_codes' => $invalidJanCodes,
            'found_count' => count($foundJanCodes)
        ];
    }

    /**
     * SKUコードの存在チェック
     * 
     * @param array $skuCodes SKUコード配列
     * @return array バリデーション結果
     */
    public function validateSkuCodes(array $skuCodes): array
    {
        if (empty($skuCodes)) {
            return [
                'valid' => false,
                'message' => 'SKUコードが指定されていません',
                'valid_codes' => [],
                'invalid_codes' => [],
                'found_count' => 0
            ];
        }

        // 重複除去と正規化
        $skuCodes = array_unique(array_filter($skuCodes));
        
        if (empty($skuCodes)) {
            return [
                'valid' => false,
                'message' => '有効なSKUコードが指定されていません',
                'valid_codes' => [],
                'invalid_codes' => [],
                'found_count' => 0
            ];
        }

        $skuCodesPlaceholder = str_repeat('?,', count($skuCodes) - 1) . '?';
        
        $sql = "
        SELECT sku_code
        FROM products
        WHERE sku_code IN ({$skuCodesPlaceholder})
          AND sku_code IS NOT NULL
          AND sku_code != ''
        ";
        
        $results = $this->db->query($sql, $skuCodes)->getResultArray();
        $foundSkuCodes = array_column($results, 'sku_code');
        $invalidSkuCodes = array_diff($skuCodes, $foundSkuCodes);
        
        $isValid = count($foundSkuCodes) > 0;
        $message = '';
        
        if (!$isValid) {
            $message = '指定されたSKUコードが見つかりません';
        } elseif (!empty($invalidSkuCodes)) {
            $message = count($invalidSkuCodes) . '個のSKUコードが見つかりませんでした';
        } else {
            $message = 'すべてのSKUコードが確認されました';
        }
        
        return [
            'valid' => $isValid,
            'message' => $message,
            'valid_codes' => $foundSkuCodes,
            'invalid_codes' => $invalidSkuCodes,
            'found_count' => count($foundSkuCodes)
        ];
    }

    /**
     * 指定されたメーカーの品番リストを取得
     * メーカーコード + 品番でグループ化し、品番名ごとに集計
     * 
     * @param string $manufacturerCode メーカーコード
     * @param string $keyword 検索キーワード（品番または品番名）
     * @param int $limit 取得件数制限
     * @return array
     */
    public function getProductNumberGroups($manufacturerCode, $keyword = '', $limit = 50)
    {
        $builder = $this->db->table($this->table);
        
        $builder->select([
            'manufacturer_code',
            'product_number',
            'product_name',
            'season_code',
            'selling_price',
            'COUNT(jan_code) as jan_count',
            'MIN(selling_price) as min_price',
            'MAX(selling_price) as max_price',
            'MIN(deletion_scheduled_date) as earliest_deletion_date',
            'MAX(deletion_scheduled_date) as latest_deletion_date'
        ]);
        
        $builder->where('manufacturer_code', $manufacturerCode);
        
        // 削除予定でない、または削除予定日が未来の商品のみ
        $builder->groupStart()
            ->where('deletion_type IS NULL')
            ->orWhere('deletion_type', 0)
            ->orWhere('deletion_scheduled_date >', date('Y-m-d'))
            ->groupEnd();
        
        // キーワード検索
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('product_number', $keyword)
                ->orLike('product_name', $keyword)
                ->groupEnd();
        }
        
        $builder->groupBy([
            'manufacturer_code',
            'product_number', 
            'product_name',
            'season_code',
            'selling_price'
        ]);
        
        $builder->orderBy('product_number');
        $builder->orderBy('product_name');
        
        if ($limit > 0) {
            $builder->limit($limit);
        }
        
        $result = $builder->get()->getResultArray();
        
        log_message('info', 'ProductModel::getProductNumberGroups SQL: ' . $this->db->getLastQuery());
        log_message('info', 'ProductModel::getProductNumberGroups 結果件数: ' . count($result));
        
        return $result;
    }

    /**
     * 商品基本情報を取得（分析用拡張版）
     * 
     * @param string $manufacturerCode
     * @param string $productNumber
     * @param string $productName
     * @return array|null
     */
    public function getProductBasicInfo($manufacturerCode, $productNumber, $productName)
    {
        $builder = $this->db->table($this->table);
        
        $builder->select([
            'manufacturer_code',
            'product_number',
            'product_name',
            'season_code',
            'selling_price',
            'deletion_scheduled_date',
            'deletion_type',
            'product_year',
            'department_code',
            'COUNT(jan_code) as total_jan_count',
            'AVG(selling_price) as avg_selling_price',
            'MIN(selling_price) as min_selling_price',
            'MAX(selling_price) as max_selling_price',
            'AVG(cost_price) as avg_cost_price',
            'MIN(initial_registration_date) as earliest_registration_date'
        ]);
        
        $builder->where('manufacturer_code', $manufacturerCode);
        $builder->where('product_number', $productNumber);
        $builder->where('product_name', $productName);
        
        $builder->groupBy([
            'manufacturer_code',
            'product_number',
            'product_name',
            'season_code',
            'selling_price',
            'deletion_scheduled_date',
            'deletion_type',
            'product_year',
            'department_code'
        ]);
        
        $result = $builder->get()->getRowArray();
        
        if ($result) {
            $result['product_group_key'] = $this->generateProductGroupKey(
                $manufacturerCode, 
                $productNumber, 
                $productName
            );
            
            // 廃盤状態の判定
            $result['is_discontinued'] = $this->isDiscontinued($result);
            
            // 経過日数計算
            if ($result['earliest_registration_date']) {
                $result['days_since_registration'] = floor(
                    (time() - strtotime($result['earliest_registration_date'])) / 86400
                );
            }
        }
        
        log_message('info', 'ProductModel::getProductBasicInfo SQL: ' . $this->db->getLastQuery());
        
        return $result;
    }

    /**
     * 指定された商品グループのJANコード一覧を取得（分析用拡張版）
     * 
     * @param string $manufacturerCode メーカーコード
     * @param string $productNumber 品番
     * @param string $productName 品番名
     * @param bool $includeDiscontinued 廃盤商品も含めるか
     * @return array
     */
    public function getJanCodesByGroup($manufacturerCode, $productNumber, $productName, $includeDiscontinued = true)
    {
        $builder = $this->db->table($this->table);
        
        $builder->select([
            'jan_code',
            'sku_code',
            'short_name',
            'size_code',
            'color_code',
            'manufacturer_color_code',
            'selling_price',
            'cost_price',
            'last_purchase_cost',
            'last_purchase_date',
            'deletion_scheduled_date',
            'deletion_type',
            'initial_registration_date',
            'inventory_management_flag'
        ]);
        
        $builder->where('manufacturer_code', $manufacturerCode);
        $builder->where('product_number', $productNumber);
        $builder->where('product_name', $productName);
        
        // 廃盤商品の除外設定
        if (!$includeDiscontinued) {
            $builder->groupStart()
                ->where('deletion_type IS NULL')
                ->orWhere('deletion_type', 0)
                ->orWhere('deletion_scheduled_date >', date('Y-m-d'))
                ->groupEnd();
        }
        
        $builder->orderBy('size_code');
        $builder->orderBy('color_code');
        $builder->orderBy('jan_code');
        
        $result = $builder->get()->getResultArray();
        
        // 各アイテムに追加情報を付与
        foreach ($result as &$item) {
            // サイズ・カラー名称生成
            $item['size_name'] = $this->generateSizeName($item['size_code']);
            $item['color_name'] = $this->generateColorName($item['color_code'], $item['manufacturer_color_code']);
            
            // 廃盤状態の判定
            $item['is_discontinued'] = $this->isDiscontinuedItem($item);
            
            // 表示用の商品名生成
            $item['display_name'] = $this->generateDisplayName($item);
            
            // 価格情報の整理
            $item['effective_cost_price'] = $this->getEffectiveCostPrice($item);
        }
        
        log_message('info', 'ProductModel::getJanCodesByGroup SQL: ' . $this->db->getLastQuery());
        log_message('info', 'ProductModel::getJanCodesByGroup 結果件数: ' . count($result));
        
        return $result;
    }

    /**
     * 商品グループキーを生成
     * 
     * @param string $manufacturerCode
     * @param string $productNumber
     * @param string $productName
     * @return string
     */
    public function generateProductGroupKey($manufacturerCode, $productNumber, $productName)
    {
        return $manufacturerCode . '_' . $productNumber . '_' . md5($productName);
    }

    /**
     * メーカーコードと品番から商品の存在確認
     * 
     * @param string $manufacturerCode
     * @param string $productNumber
     * @return bool
     */
    public function existsProductNumber($manufacturerCode, $productNumber)
    {
        $builder = $this->db->table($this->table);
        
        $count = $builder->where('manufacturer_code', $manufacturerCode)
                        ->where('product_number', $productNumber)
                        ->countAllResults();
        
        return $count > 0;
    }

    /**
     * 商品検索（複合条件）- 分析用拡張版
     * 
     * @param array $conditions 検索条件
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchProducts($conditions = [], $limit = 100, $offset = 0)
    {
        $builder = $this->db->table($this->table);
        
        // 基本的な条件適用
        if (!empty($conditions['manufacturer_code'])) {
            $builder->where('manufacturer_code', $conditions['manufacturer_code']);
        }
        
        if (!empty($conditions['product_number'])) {
            $builder->like('product_number', $conditions['product_number']);
        }
        
        if (!empty($conditions['product_name'])) {
            $builder->like('product_name', $conditions['product_name']);
        }
        
        if (!empty($conditions['season_code'])) {
            $builder->where('season_code', $conditions['season_code']);
        }
        
        if (!empty($conditions['department_code'])) {
            $builder->where('department_code', $conditions['department_code']);
        }
        
        // JANコードでの検索
        if (!empty($conditions['jan_code'])) {
            $builder->where('jan_code', $conditions['jan_code']);
        }
        
        // 価格範囲での検索
        if (!empty($conditions['price_from'])) {
            $builder->where('selling_price >=', $conditions['price_from']);
        }
        
        if (!empty($conditions['price_to'])) {
            $builder->where('selling_price <=', $conditions['price_to']);
        }
        
        // 削除予定商品の除外
        if (isset($conditions['exclude_deleted']) && $conditions['exclude_deleted']) {
            $builder->groupStart()
                ->where('deletion_type IS NULL')
                ->orWhere('deletion_type', 0)
                ->orWhere('deletion_scheduled_date >', date('Y-m-d'))
                ->groupEnd();
        }
        
        // 在庫管理対象のみ
        if (isset($conditions['inventory_managed_only']) && $conditions['inventory_managed_only']) {
            $builder->where('inventory_management_flag', 1);
        }
        
        $builder->orderBy('manufacturer_code');
        $builder->orderBy('product_number');
        $builder->orderBy('product_name');
        $builder->orderBy('jan_code');
        
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }
        
        $result = $builder->get()->getResultArray();
        
        // 各レコードに追加情報を付与
        foreach ($result as &$item) {
            $item['size_name'] = $this->generateSizeName($item['size_code']);
            $item['color_name'] = $this->generateColorName($item['color_code'], $item['manufacturer_color_code']);
            $item['display_name'] = $this->generateDisplayName($item);
            $item['is_discontinued'] = $this->isDiscontinuedItem($item);
        }
        
        return $result;
    }

    /**
     * 商品の販売実績サマリー取得（分析用）
     * 
     * @param string $manufacturerCode
     * @param string $productNumber  
     * @param string $productName
     * @param string $dateFrom 集計開始日
     * @param string $dateTo 集計終了日
     * @return array
     */
    public function getProductSalesSummary($manufacturerCode, $productNumber, $productName, $dateFrom = null, $dateTo = null)
    {
        // JANコード一覧取得
        $janCodes = $this->getJanCodesByGroup($manufacturerCode, $productNumber, $productName);
        $janCodeList = array_column($janCodes, 'jan_code');
        
        if (empty($janCodeList)) {
            return null;
        }
        
        $janCodesPlaceholder = str_repeat('?,', count($janCodeList) - 1) . '?';
        $params = $janCodeList;
        
        $dateCondition = '';
        if ($dateFrom) {
            $dateCondition .= ' AND sales_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $dateCondition .= ' AND sales_date <= ?';
            $params[] = $dateTo;
        }
        
        $sql = "
        SELECT 
            COUNT(DISTINCT sales_date) as sales_days,
            SUM(sales_quantity) as total_sales_qty,
            SUM(sales_amount) as total_sales_amount,
            AVG(sales_unit_price) as avg_unit_price,
            MIN(sales_date) as first_sales_date,
            MAX(sales_date) as last_sales_date,
            COUNT(*) as transaction_count,
            COUNT(CASE WHEN sales_quantity < 0 THEN 1 END) as return_count
        FROM sales_slip s
        WHERE s.jan_code IN ({$janCodesPlaceholder})
          {$dateCondition}
        ";
        
        $result = $this->db->query($sql, $params)->getRowArray();
        
        if ($result && $result['total_sales_qty'] > 0) {
            $result['sales_period_days'] = $result['first_sales_date'] && $result['last_sales_date']
                ? (strtotime($result['last_sales_date']) - strtotime($result['first_sales_date'])) / 86400 + 1
                : 0;
                
            $result['daily_avg_sales'] = $result['sales_days'] > 0 
                ? $result['total_sales_qty'] / $result['sales_days'] 
                : 0;
        }
        
        return $result;
    }

    /**
     * 商品の仕入実績サマリー取得（分析用）
     * 
     * @param string $manufacturerCode
     * @param string $productNumber
     * @param string $productName  
     * @return array
     */
    public function getProductPurchaseSummary($manufacturerCode, $productNumber, $productName)
    {
        // JANコード一覧取得
        $janCodes = $this->getJanCodesByGroup($manufacturerCode, $productNumber, $productName);
        $janCodeList = array_column($janCodes, 'jan_code');
        
        if (empty($janCodeList)) {
            return null;
        }
        
        $janCodesPlaceholder = str_repeat('?,', count($janCodeList) - 1) . '?';
        
        $sql = "
        SELECT 
            COUNT(DISTINCT purchase_date) as purchase_days,
            SUM(purchase_quantity) as total_purchase_qty,
            SUM(purchase_amount) as total_purchase_amount,
            AVG(cost_price) as avg_cost_price,
            MIN(purchase_date) as first_purchase_date,
            MAX(purchase_date) as last_purchase_date,
            COUNT(*) as transaction_count
        FROM purchase_slip ps
        WHERE ps.jan_code IN ({$janCodesPlaceholder})
          AND ps.purchase_quantity > 0
        ";
        
        $result = $this->db->query($sql, $janCodeList)->getRowArray();
        
        if ($result && $result['total_purchase_qty'] > 0) {
            $result['purchase_period_days'] = $result['first_purchase_date'] && $result['last_purchase_date']
                ? (strtotime($result['last_purchase_date']) - strtotime($result['first_purchase_date'])) / 86400 + 1
                : 0;
        }
        
        return $result;
    }

    /**
     * 廃盤状態の判定（商品グループ）
     */
    private function isDiscontinued(array $productInfo): bool
    {
        if ($productInfo['deletion_type'] && $productInfo['deletion_type'] > 0) {
            return true;
        }
        
        if ($productInfo['deletion_scheduled_date'] && 
            strtotime($productInfo['deletion_scheduled_date']) <= time()) {
            return true;
        }
        
        return false;
    }

    /**
     * 個別商品の廃盤状態判定
     */
    private function isDiscontinuedItem(array $item): bool
    {
        if ($item['deletion_type'] && $item['deletion_type'] > 0) {
            return true;
        }
        
        if ($item['deletion_scheduled_date'] && 
            strtotime($item['deletion_scheduled_date']) <= time()) {
            return true;
        }
        
        return false;
    }

    /**
     * 表示用商品名生成
     */
    private function generateDisplayName(array $item): string
    {
        $parts = [];
        
        if (!empty($item['short_name'])) {
            $parts[] = $item['short_name'];
        }
        
        if (!empty($item['size_name']) && $item['size_name'] !== 'F') {
            $parts[] = $item['size_name'];
        }
        
        if (!empty($item['color_name']) && $item['color_name'] !== '-') {
            $parts[] = $item['color_name'];
        }
        
        return implode(' ', $parts) ?: $item['jan_code'];
    }

    /**
     * 有効原価の取得（最終仕入原価優先）
     */
    private function getEffectiveCostPrice(array $item): float
    {
        if (!empty($item['last_purchase_cost']) && $item['last_purchase_cost'] > 0) {
            return (float)$item['last_purchase_cost'];
        }
        
        if (!empty($item['cost_price']) && $item['cost_price'] > 0) {
            return (float)$item['cost_price'];
        }
        
        return 0.0;
    }

    /**
     * サイズ名称生成（簡易版）
     * 実際の運用では、サイズマスタテーブルから取得することを推奨
     * 
     * @param string $sizeCode
     * @return string
     */
    private function generateSizeName($sizeCode)
    {
        if (empty($sizeCode)) {
            return 'F'; // フリーサイズ
        }
        
        // 一般的なサイズコードの変換
        $sizeMap = [
            'XS' => 'XS',
            'S' => 'S',
            'M' => 'M', 
            'L' => 'L',
            'XL' => 'XL',
            'XXL' => 'XXL',
            '2L' => '2L',
            '3L' => '3L',
            '4L' => '4L',
            'FREE' => 'F'
        ];
        
        $upperSizeCode = strtoupper($sizeCode);
        return $sizeMap[$upperSizeCode] ?? $sizeCode;
    }

    /**
     * カラー名称生成（簡易版）
     * 実際の運用では、カラーマスタテーブルから取得することを推奨
     * 
     * @param string $colorCode
     * @param string $manufacturerColorCode
     * @return string
     */
    private function generateColorName($colorCode, $manufacturerColorCode)
    {
        // メーカーカラーコードを優先
        $code = !empty($manufacturerColorCode) ? $manufacturerColorCode : $colorCode;
        
        if (empty($code)) {
            return '-';
        }
        
        // 簡易的なカラー名変換
        $colorMap = [
            'BK' => '黒',
            'WH' => '白', 
            'RD' => '赤',
            'BL' => '青',
            'GR' => '緑',
            'YE' => '黄',
            'PK' => 'ピンク',
            'GY' => 'グレー',
            'NV' => 'ネイビー',
            'BR' => 'ブラウン'
        ];
        
        $upperCode = strtoupper($code);
        return $colorMap[$upperCode] ?? $code;
    }
}