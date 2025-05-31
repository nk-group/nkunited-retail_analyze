<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * メーカー（製造業者）マスタモデル
 * 
 * テーブル: manufacturers
 * 主キー: manufacturer_code
 */
class ManufacturerModel extends Model
{
    protected $table = 'manufacturers';
    protected $primaryKey = 'manufacturer_code';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'manufacturer_code',
        'manufacturer_name',
        'created_at',
        'updated_at'
    ];

    // 日付フィールド
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // バリデーションルール
    protected $validationRules = [
        'manufacturer_code' => 'required|max_length[8]|is_unique[manufacturers.manufacturer_code,manufacturer_code,{manufacturer_code}]',
        'manufacturer_name' => 'required|max_length[200]'
    ];

    protected $validationMessages = [
        'manufacturer_code' => [
            'required' => 'メーカーコードは必須です。',
            'max_length' => 'メーカーコードは8文字以内で入力してください。',
            'is_unique' => 'このメーカーコードは既に登録されています。'
        ],
        'manufacturer_name' => [
            'required' => 'メーカー名は必須です。',
            'max_length' => 'メーカー名は200文字以内で入力してください。'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * メーカー検索（キーワード検索用）
     */
    public function searchByKeyword($keyword, $limit = 20)
    {
        return $this->like('manufacturer_code', $keyword)
                   ->orLike('manufacturer_name', $keyword)
                   ->orderBy('manufacturer_code')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * メーカーコード範囲取得
     */
    public function getByCodeRange($codeFrom = null, $codeTo = null)
    {
        $builder = $this->builder();
        
        if (!empty($codeFrom)) {
            $builder->where('manufacturer_code >=', $codeFrom);
        }
        
        if (!empty($codeTo)) {
            $builder->where('manufacturer_code <=', $codeTo);
        }
        
        return $builder->orderBy('manufacturer_code')->get()->getResultArray();
    }
}