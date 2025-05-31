<?php
namespace App\Models;

use CodeIgniter\Model;

class ImportTaskModel extends Model
{
    protected $table            = 'import_tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object'; // または 'array'
    protected $useSoftDeletes   = false; // ソフトデリートは使用しない想定

    // 基本的に読み取りが主なので、厳密な allowedFields は不要かもしれないが、
    // 万が一このモデル経由で更新する場合のために主要なものをリストアップ (CLIコマンドやコントローラで直接更新が主)
    protected $allowedFields    = [
        'status', 
        'target_data_name', 
        'original_file_name', 
        'stored_file_path', 
        'uploaded_at', 
        'uploaded_by', 
        'processing_started_at', 
        'processing_finished_at', 
        'result_message'
    ];

    // Dates
    protected $useTimestamps = false; // created_at, updated_at カラムは import_tasks テーブルに直接はない想定
                                     // uploaded_at, processing_started_at, processing_finished_at は手動で日時をセット

    // Validation (このモデル経由でデータを保存する場合のルール。今回は主に読み取り)
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = true; // 今回は読み取りが主なのでスキップ

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
     * 日付範囲フィルターを適用したクエリビルダを返します。
     * TaskViewControllerから呼び出されることを想定。
     *
     * @param string|null $startDate YYYY-MM-DD形式の開始日
     * @param string|null $endDate   YYYY-MM-DD形式の終了日
     * @return $this
     */
    public function applyDateFilter(?string $startDate, ?string $endDate)
    {
        $builder = $this->builder(); //常に新しいビルダインスタンスで開始

        if ($startDate && $endDate) {
            // uploaded_at は DATETIME2(7) なので、 endDate はその日の終わりまで含むように調整
            // SQL Server の DATETIME2(7) は 'YYYY-MM-DD HH:MM:SS.sssssss'
            $builder->where('uploaded_at >=', $startDate . ' 00:00:00.0000000');
            $builder->where('uploaded_at <=', $endDate . ' 23:59:59.9999999');
        } elseif ($startDate) { // 開始日のみ指定の場合 (その日一日)
            $builder->where('uploaded_at >=', $startDate . ' 00:00:00.0000000');
            $builder->where('uploaded_at <=', $startDate . ' 23:59:59.9999999');
        }
        // $this を返すことでメソッドチェーンが可能になるが、
        // paginate() の直前に orderBy() などが呼ばれるため、
        // TaskViewController側で $taskModel->applyDateFilter()->orderBy()->paginate() とする。
        // そのため、このメソッドは builder() インスタンスではなく $this を返す。
        return $this;
    }

    // 必要であれば、より複雑な検索条件に対応するメソッドをここに追加できます。
    // 例: public function searchTasks($conditions, $orderBy, $orderDir) { ... }
}