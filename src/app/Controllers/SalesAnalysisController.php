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
        helper(['form', 'url']);
    }

    /**
     * 販売分析メニュー画面
     */
    public function index()
    {
        $data = [
            'pageTitle' => '商品販売分析 - メニュー'
        ];

        return view('sales_analysis/index', $data);
    }

    /**
     * 単品分析 - 集計指示画面
     */
    public function singleProduct()
    {
        $data = [
            'pageTitle' => '商品販売分析 - 単品分析 集計指示'
        ];

        return view('sales_analysis/single_product_form', $data);
    }

    /**
     * 単品分析 - 集計実行
     */
    public function executeSingleProduct()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', '不正なリクエストです。');
        }

        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'manufacturer_code' => [
                'label' => 'メーカーコード',
                'rules' => 'required|max_length[8]'
            ],
            'product_number' => [
                'label' => 'メーカー品番',
                'rules' => 'required|max_length[50]'
            ],
            'product_name' => [
                'label' => 'メーカー品番名',
                'rules' => 'required|max_length[200]'
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $conditions = [
            'manufacturer_code' => $this->request->getPost('manufacturer_code'),
            'product_number' => $this->request->getPost('product_number'),
            'product_name' => $this->request->getPost('product_name')
        ];

        try {
            // TODO: 単品分析サービスクラスを呼び出して集計処理を実行
            // $singleProductAnalysisService = new \App\Services\SingleProductAnalysisService();
            // $result = $singleProductAnalysisService->executeAnalysis($conditions);
            
            // 現在は仮実装
            session()->setFlashdata('success', '単品分析の集計処理を開始しました。');
            session()->setFlashdata('analysis_conditions', $conditions);
            
            return redirect()->to(site_url('sales-analysis/single-product/result'));
            
        } catch (\Exception $e) {
            log_message('error', '単品分析実行エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中にエラーが発生しました。');
        }
    }

    /**
     * 単品分析 - 結果画面
     */
    public function singleProductResult()
    {
        $conditions = session()->getFlashdata('analysis_conditions');
        
        $data = [
            'pageTitle' => '商品販売分析 - 単品分析 結果',
            'conditions' => $conditions
        ];

        return view('sales_analysis/single_product_result', $data);
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
        $limit = 10;
        
        try {
            $builder = $this->manufacturerModel;
            
            if (!empty($keyword)) {
                $builder = $builder->groupStart()
                    ->like('manufacturer_code', $keyword)
                    ->orLike('manufacturer_name', $keyword)
                    ->groupEnd();
            }
            
            $totalCount = $builder->countAllResults(false);
            
            $makers = $builder
                ->orderBy('manufacturer_code')
                ->limit($limit, ($page - 1) * $limit)
                ->findAll();

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
     * 品番検索API（Ajax用）
     */
    public function searchProducts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $manufacturerCode = $this->request->getGet('manufacturer_code');
        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = 10;
        
        try {
            // TODO: ProductModelを作成して実装
            // 仮データを返す
            $products = [
                [
                    'manufacturer_code' => $manufacturerCode,
                    'product_number' => 'S-001',
                    'product_name' => '半袖Tシャツ',
                    'season_code' => '2025SS',
                    'selling_price' => 1800,
                    'jan_count' => 3
                ],
                [
                    'manufacturer_code' => $manufacturerCode,
                    'product_number' => 'S-001',
                    'product_name' => 'カットソー',
                    'season_code' => '2025SS',
                    'selling_price' => 1800,
                    'jan_count' => 3
                ],
                [
                    'manufacturer_code' => $manufacturerCode,
                    'product_number' => 'S-001',
                    'product_name' => 'ポロシャツ',
                    'season_code' => '2025SS',
                    'selling_price' => 2200,
                    'jan_count' => 3
                ]
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'current_page' => 1,
                    'total_count' => 3,
                    'per_page' => $limit,
                    'total_pages' => 1,
                    'has_next_page' => false,
                    'has_prev_page' => false,
                    'from' => 1,
                    'to' => 3
                ],
                'keyword' => $keyword
            ]);

        } catch (\Exception $e) {
            log_message('error', '品番検索エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '検索処理中にエラーが発生しました。'
            ]);
        }
    }

    /**
     * 集計対象商品（JANコード）取得API
     */
    public function getTargetProducts()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $manufacturerCode = $this->request->getGet('manufacturer_code');
        $productNumber = $this->request->getGet('product_number');
        $productName = $this->request->getGet('product_name');
        
        try {
            // TODO: ProductModelを作成して実際のJANコードを取得
            // 仮データを返す
            $janCodes = [
                ['jan_code' => '4912300200055', 'size_name' => 'S'],
                ['jan_code' => '4912300200066', 'size_name' => 'M'],
                ['jan_code' => '4912300200077', 'size_name' => 'L']
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $janCodes
            ]);

        } catch (\Exception $e) {
            log_message('error', '集計対象商品取得エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '商品情報の取得に失敗しました。'
            ]);
        }
    }
}