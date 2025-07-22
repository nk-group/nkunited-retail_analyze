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

    // 除外するメーカーコードの範囲を定義
    private const EXCLUDE_RANGES = [
        ['start' => '0001000', 'end' => '0001999'],
        ['start' => '0100000', 'end' => '0199999'],
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
     * メーカー検索（キーワード検索用、除外範囲適用）
     * @param string|null $keyword
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchMakersWithExclusion(?string $keyword, int $limit = 10, int $offset = 0): array
    {
        $builder = $this->builder();

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('manufacturer_code', $keyword)
                ->orLike('manufacturer_name', $keyword)
                ->groupEnd();
        }

        // 固定の除外範囲を適用
        foreach (self::EXCLUDE_RANGES as $range) {
            $builder->groupStart()
                ->where('manufacturer_code <', $range['start'])
                ->orWhere('manufacturer_code >', $range['end'])
                ->groupEnd();
        }

        return $builder->orderBy('manufacturer_code')->limit($limit, $offset)->get()->getResultArray();
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

    /**
     * 除外範囲を適用した総件数を取得
     * @param string|null $keyword
     * @return int
     */
    public function countAllWithExclusion(?string $keyword): int
    {
        $builder = $this->builder();

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('manufacturer_code', $keyword)
                ->orLike('manufacturer_name', $keyword)
                ->groupEnd();
        }

        foreach (self::EXCLUDE_RANGES as $range) {
            $builder->groupStart()
                ->where('manufacturer_code <', $range['start'])
                ->orWhere('manufacturer_code >', $range['end'])
                ->groupEnd();
        }
        return $builder->countAllResults();
    }
}