<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ManufacturerModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class SalesAnalysisController extends BaseController
{
    protected $manufacturerModel;
    protected $productModel;

    public function __construct()
    {
        $this->manufacturerModel = new ManufacturerModel();
        $this->productModel = new ProductModel();
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
            // 事前データ検証
            $manufacturer = $this->manufacturerModel->find($conditions['manufacturer_code']);
            if (!$manufacturer) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '指定されたメーカーが見つかりません。');
            }

            $productInfo = $this->productModel->getProductBasicInfo(
                $conditions['manufacturer_code'],
                $conditions['product_number'],
                $conditions['product_name']
            );

            if (!$productInfo) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '指定された商品が見つかりません。');
            }

            // TODO: 実際の単品分析サービスクラスを呼び出して集計処理を実行
            // $singleProductAnalysisService = new \App\Services\SingleProductAnalysisService();
            // $result = $singleProductAnalysisService->executeAnalysis($conditions);
            
            // 現在は仮実装
            session()->setFlashdata('success', '単品分析の集計処理を開始しました。');
            session()->setFlashdata('analysis_conditions', $conditions);
            session()->setFlashdata('manufacturer_info', $manufacturer);
            session()->setFlashdata('product_info', $productInfo);
            
            return redirect()->to(site_url('sales-analysis/single-product/result'));
            
        } catch (\Exception $e) {
            log_message('error', '単品分析実行エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 単品分析 - 結果画面
     */
    public function singleProductResult()
    {
        $conditions = session()->getFlashdata('analysis_conditions');
        $manufacturerInfo = session()->getFlashdata('manufacturer_info');
        $productInfo = session()->getFlashdata('product_info');
        
        if (!$conditions) {
            return redirect()->to(site_url('sales-analysis/single-product'))
                ->with('error', '集計結果が見つかりません。再度実行してください。');
        }
        
        $data = [
            'pageTitle' => '商品販売分析 - 単品分析 結果',
            'conditions' => $conditions,
            'manufacturer_info' => $manufacturerInfo,
            'product_info' => $productInfo
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
        $exact = $this->request->getGet('exact'); // 完全一致フラグ
        $limit = 10;
        
        try {
            $builder = $this->manufacturerModel;
            
            if (!empty($keyword)) {
                if ($exact) {
                    // 完全一致検索（メーカーコード入力時）
                    $builder = $builder->where('manufacturer_code', $keyword);
                } else {
                    // 部分一致検索（モーダル検索時）
                    $builder = $builder->groupStart()
                        ->like('manufacturer_code', $keyword)
                        ->orLike('manufacturer_name', $keyword)
                        ->groupEnd();
                }
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
                    'from' => $totalCount > 0 ? ($page - 1) * $limit + 1 : 0,
                    'to' => min($page * $limit, $totalCount)
                ],
                'keyword' => $keyword
            ]);

        } catch (\Exception $e) {
            log_message('error', 'メーカー検索エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '検索処理中にエラーが発生しました: ' . $e->getMessage()
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
        $limit = 50; // 品番検索は多めに表示
        
        try {
            if (empty($manufacturerCode)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'メーカーコードが必要です。'
                ]);
            }

            // メーカーの存在確認
            $manufacturer = $this->manufacturerModel->find($manufacturerCode);
            if (!$manufacturer) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => '指定されたメーカーが見つかりません。'
                ]);
            }

            // 品番グループを取得
            $products = $this->productModel->getProductNumberGroups(
                $manufacturerCode, 
                $keyword, 
                $limit
            );

            // データの整形
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedProducts[] = [
                    'manufacturer_code' => $product['manufacturer_code'],
                    'product_number' => $product['product_number'],
                    'product_name' => $product['product_name'],
                    'season_code' => $product['season_code'] ?? '-',
                    'selling_price' => (float) ($product['selling_price'] ?? 0),
                    'jan_count' => (int) ($product['jan_count'] ?? 0),
                    'min_price' => (float) ($product['min_price'] ?? 0),
                    'max_price' => (float) ($product['max_price'] ?? 0),
                    'earliest_deletion_date' => $product['earliest_deletion_date'],
                    'latest_deletion_date' => $product['latest_deletion_date']
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => 1,
                    'total_count' => count($formattedProducts),
                    'per_page' => $limit,
                    'total_pages' => 1,
                    'has_next_page' => false,
                    'has_prev_page' => false,
                    'from' => count($formattedProducts) > 0 ? 1 : 0,
                    'to' => count($formattedProducts)
                ],
                'keyword' => $keyword,
                'manufacturer' => $manufacturer
            ]);

        } catch (\Exception $e) {
            log_message('error', '品番検索エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '検索処理中にエラーが発生しました: ' . $e->getMessage()
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
            if (empty($manufacturerCode) || empty($productNumber) || empty($productName)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => '必要なパラメータが不足しています。'
                ]);
            }

            // 基本情報の確認
            $productInfo = $this->productModel->getProductBasicInfo(
                $manufacturerCode,
                $productNumber,
                $productName
            );

            if (!$productInfo) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error' => '指定された商品が見つかりません。'
                ]);
            }

            // JANコード一覧を取得
            $janCodes = $this->productModel->getJanCodesByGroup(
                $manufacturerCode,
                $productNumber,
                $productName
            );

            if (empty($janCodes)) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error' => 'この商品グループにはJANコードが登録されていません。'
                ]);
            }

            // データの整形
            $formattedJanCodes = [];
            foreach ($janCodes as $jan) {
                $formattedJanCodes[] = [
                    'jan_code' => $jan['jan_code'],
                    'sku_code' => $jan['sku_code'] ?? '',
                    'size_code' => $jan['size_code'] ?? '',
                    'size_name' => $jan['size_name'] ?? 'F',
                    'color_code' => $jan['color_code'] ?? '',
                    'color_name' => $jan['color_name'] ?? '-',
                    'manufacturer_color_code' => $jan['manufacturer_color_code'] ?? '',
                    'selling_price' => (float) ($jan['selling_price'] ?? 0),
                    'cost_price' => (float) ($jan['cost_price'] ?? 0),
                    'deletion_scheduled_date' => $jan['deletion_scheduled_date']
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedJanCodes,
                'product_info' => $productInfo,
                'summary' => [
                    'total_jan_count' => count($formattedJanCodes),
                    'avg_selling_price' => array_sum(array_column($formattedJanCodes, 'selling_price')) / count($formattedJanCodes),
                    'price_range' => [
                        'min' => min(array_column($formattedJanCodes, 'selling_price')),
                        'max' => max(array_column($formattedJanCodes, 'selling_price'))
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', '集計対象商品取得エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '商品情報の取得に失敗しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 品番存在確認API（リアルタイムバリデーション用）
     */
    public function validateProductNumber()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $manufacturerCode = $this->request->getGet('manufacturer_code');
        $productNumber = $this->request->getGet('product_number');
        
        try {
            if (empty($manufacturerCode) || empty($productNumber)) {
                return $this->response->setJSON([
                    'success' => false,
                    'exists' => false,
                    'message' => '必要なパラメータが不足しています。'
                ]);
            }

            $exists = $this->productModel->existsProductNumber($manufacturerCode, $productNumber);
            
            return $this->response->setJSON([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? '品番が確認されました。' : '該当する品番が見つかりません。'
            ]);

        } catch (\Exception $e) {
            log_message('error', '品番存在確認エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'exists' => false,
                'message' => 'システムエラーが発生しました。'
            ]);
        }
    }
}