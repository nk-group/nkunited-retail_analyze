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
        
        // ログ出力（デバッグ用）
        log_message('info', 'ProductModel::getProductNumberGroups SQL: ' . $this->db->getLastQuery());
        log_message('info', 'ProductModel::getProductNumberGroups 結果件数: ' . count($result));
        
        return $result;
    }

    /**
     * 指定された商品グループのJANコード一覧を取得
     * 
     * @param string $manufacturerCode メーカーコード
     * @param string $productNumber 品番
     * @param string $productName 品番名
     * @return array
     */
    public function getJanCodesByGroup($manufacturerCode, $productNumber, $productName)
    {
        $builder = $this->db->table($this->table);
        
        $builder->select([
            'jan_code',
            'sku_code',
            'size_code',
            'color_code',
            'manufacturer_color_code',
            'selling_price',
            'cost_price',
            'deletion_scheduled_date'
        ]);
        
        $builder->where('manufacturer_code', $manufacturerCode);
        $builder->where('product_number', $productNumber);
        $builder->where('product_name', $productName);
        
        // 削除予定でない、または削除予定日が未来の商品のみ
        $builder->groupStart()
            ->where('deletion_type IS NULL')
            ->orWhere('deletion_type', 0)
            ->orWhere('deletion_scheduled_date >', date('Y-m-d'))
            ->groupEnd();
        
        $builder->orderBy('size_code');
        $builder->orderBy('color_code');
        
        $result = $builder->get()->getResultArray();
        
        // サイズ名称を生成（簡易版）
        foreach ($result as &$item) {
            $item['size_name'] = $this->generateSizeName($item['size_code']);
            $item['color_name'] = $this->generateColorName($item['color_code'], $item['manufacturer_color_code']);
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
     * 商品基本情報を取得
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
            'COUNT(jan_code) as total_jan_count',
            'AVG(selling_price) as avg_selling_price',
            'MIN(selling_price) as min_selling_price',
            'MAX(selling_price) as max_selling_price'
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
            'deletion_scheduled_date'
        ]);
        
        $result = $builder->get()->getRowArray();
        
        if ($result) {
            $result['product_group_key'] = $this->generateProductGroupKey(
                $manufacturerCode, 
                $productNumber, 
                $productName
            );
        }
        
        return $result;
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

    /**
     * 商品検索（複合条件）
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
        
        // 削除予定商品の除外
        if (isset($conditions['exclude_deleted']) && $conditions['exclude_deleted']) {
            $builder->groupStart()
                ->where('deletion_type IS NULL')
                ->orWhere('deletion_type', 0)
                ->orWhere('deletion_scheduled_date >', date('Y-m-d'))
                ->groupEnd();
        }
        
        $builder->orderBy('manufacturer_code');
        $builder->orderBy('product_number');
        $builder->orderBy('product_name');
        
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }
        
        return $builder->get()->getResultArray();
    }
}