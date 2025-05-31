<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ManufacturerModel;
use CodeIgniter\HTTP\ResponseInterface;

class SalesAnalysisController extends BaseController
{
    protected $manufacturerModel;

    public function __construct()
    {
        $this->manufacturerModel = new ManufacturerModel();
        // 必要なHelper をロード
        helper(['form', 'url']);
    }

    /**
     * 販売分析メイン画面
     */
    public function index()
    {
        $data = [
            'pageTitle' => '販売分析 - 集計指示'
        ];

        return view('sales_analysis/index', $data);
    }

    /**
     * 集計実行
     */
    public function execute()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', '不正なリクエストです。');
        }

        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'date_from' => [
                'label' => '集計開始日',
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'date_to' => [
                'label' => '集計終了日', 
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'maker_code_from' => [
                'label' => 'メーカーコード（開始）',
                'rules' => 'permit_empty|max_length[20]'
            ],
            'maker_code_to' => [
                'label' => 'メーカーコード（終了）',
                'rules' => 'permit_empty|max_length[20]'
            ],
            'maker_item_code_from' => [
                'label' => 'メーカー品番（開始）',
                'rules' => 'permit_empty|max_length[50]'
            ],
            'maker_item_code_to' => [
                'label' => 'メーカー品番（終了）',
                'rules' => 'permit_empty|max_length[50]'
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $conditions = [
            'date_from' => $this->request->getPost('date_from'),
            'date_to' => $this->request->getPost('date_to'),
            'maker_code_from' => $this->request->getPost('maker_code_from'),
            'maker_code_to' => $this->request->getPost('maker_code_to'),
            'maker_item_code_from' => $this->request->getPost('maker_item_code_from'),
            'maker_item_code_to' => $this->request->getPost('maker_item_code_to')
        ];

        // 日付範囲の妥当性チェック
        if (strtotime($conditions['date_from']) > strtotime($conditions['date_to'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', '集計開始日は集計終了日より前の日付を指定してください。');
        }

        // メーカーコード範囲の妥当性チェック
        if (!empty($conditions['maker_code_from']) && !empty($conditions['maker_code_to'])) {
            if ($conditions['maker_code_from'] > $conditions['maker_code_to']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'メーカーコードの開始値は終了値以下で指定してください。');
            }
        }

        // メーカー品番範囲の妥当性チェック
        if (!empty($conditions['maker_item_code_from']) && !empty($conditions['maker_item_code_to'])) {
            if ($conditions['maker_item_code_from'] > $conditions['maker_item_code_to']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'メーカー品番の開始値は終了値以下で指定してください。');
            }
        }

        try {
            // TODO: 販売分析サービスクラスを呼び出して集計処理を実行
            // $salesAnalysisService = new \App\Services\SalesAnalysisService();
            // $result = $salesAnalysisService->executeAnalysis($conditions);
            
            // 現在は仮実装
            session()->setFlashdata('success', '販売分析の集計処理を開始しました。');
            session()->setFlashdata('analysis_conditions', $conditions);
            
            return redirect()->to('/sales-analysis/result');
            
        } catch (\Exception $e) {
            log_message('error', '販売分析実行エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中にエラーが発生しました。');
        }
    }

    /**
     * メーカー検索API（Ajax用）
     */
    public function searchMakers()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = 10; // 表示件数
        
        try {
            $builder = $this->manufacturerModel;
            
            // キーワード検索の条件設定
            if (!empty($keyword)) {
                $builder = $builder->groupStart()
                    ->like('manufacturer_code', $keyword)
                    ->orLike('manufacturer_name', $keyword)
                    ->groupEnd();
            }
            
            // 検索条件に該当する総件数を取得
            $totalCount = $builder->countAllResults(false);
            
            // データ取得（ページング対応）
            $makers = $builder
                ->orderBy('manufacturer_code')
                ->limit($limit, ($page - 1) * $limit)
                ->findAll();

            // ページング情報の計算
            $totalPages = ceil($totalCount / $limit);
            $hasNextPage = $page < $totalPages;
            $hasPrevPage = $page > 1;

            return $this->response->setJSON([
                'success' => true,
                'data' => $makers,
                'pagination' => [
                    'current_page' => $page,
                    'total_count' => $totalCount,
                    'per_page' => $limit,
                    'total_pages' => $totalPages,
                    'has_next_page' => $hasNextPage,
                    'has_prev_page' => $hasPrevPage,
                    'from' => ($page - 1) * $limit + 1,
                    'to' => min($page * $limit, $totalCount)
                ],
                'keyword' => $keyword
            ]);

        } catch (\Exception $e) {
            log_message('error', 'メーカー検索エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '検索処理中にエラーが発生しました。'
            ]);
        }
    }

    /**
     * 結果画面（仮実装）
     */
    public function result()
    {
        $conditions = session()->getFlashdata('analysis_conditions');
        
        $data = [
            'pageTitle' => '販売分析 - 結果',
            'conditions' => $conditions
        ];

        return view('sales_analysis/result', $data);
    }
}