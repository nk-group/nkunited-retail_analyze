<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ManufacturerModel;
use App\Models\ProductModel;
use App\Libraries\SingleProductAnalysisService;
use App\Libraries\SingleProductAnalysisException;
use CodeIgniter\HTTP\ResponseInterface;

class SalesAnalysisController extends BaseController
{
    protected $manufacturerModel;
    protected $productModel;
    protected $analysisService;

    public function __construct()
    {
        $this->manufacturerModel = new ManufacturerModel();
        $this->productModel = new ProductModel();
        $this->analysisService = new SingleProductAnalysisService();
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
            log_message('info', '単品分析実行開始: ' . json_encode($conditions));
            
            // 原価計算方式の設定（将来的に画面から選択可能にする）
            $costMethod = $this->request->getPost('cost_method') ?? 'average';
            $this->analysisService->setCostMethod($costMethod);
            
            // 単品分析サービスの実行
            $analysisResult = $this->analysisService->executeAnalysis($conditions);
            
            // 成功時の処理
            log_message('info', '単品分析実行完了: 実行時間=' . $analysisResult['execution_time'] . '秒');
            
            // セッションに結果データを保存
            $session = session();
            $session->setFlashdata('analysis_result', $analysisResult);
            $session->setFlashdata('success', '単品分析が完了しました。');
            
            return redirect()->to(site_url('sales-analysis/single-product/result'));
            
        } catch (SingleProductAnalysisException $e) {
            // 分析固有のエラー
            log_message('error', '単品分析エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
                
        } catch (\Exception $e) {
            // 予期しないエラー
            log_message('error', '単品分析予期しないエラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中に予期しないエラーが発生しました。システム管理者にお問い合わせください。');
        }
    }

    /**
     * 単品分析 - 結果画面
     */
    public function singleProductResult()
    {
        $session = session();
        $analysisResult = $session->getFlashdata('analysis_result');
        
        if (!$analysisResult) {
            return redirect()->to(site_url('sales-analysis/single-product'))
                ->with('error', '集計結果が見つかりません。再度実行してください。');
        }
        
        // 結果データの整形
        $formattedResult = $this->formatAnalysisResult($analysisResult);
        
        $data = [
            'pageTitle' => '商品販売分析 - 単品分析 結果',
            'analysis_result' => $analysisResult,
            'formatted_result' => $formattedResult,
            'warnings' => $analysisResult['warnings'] ?? [],
            'execution_time' => $analysisResult['execution_time'] ?? 0
        ];

        return view('sales_analysis/single_product_result', $data);
    }

    /**
     * 分析結果を画面表示用に整形（拡張版）
     * 
     * 【修正内容】
     * - 定価の定義をselling_price→m_unit_priceに変更
     * - サマリー部に総仕入数を追加
     * - サマリー部の並び順変更
     * 
     * 【新機能追加】
     * - 残在庫数の表示
     * - 週別イベント情報の備考生成
     * - 伝票詳細情報の整形
     */
    private function formatAnalysisResult(array $analysisResult): array
    {
        $basicInfo = $analysisResult['basic_info'];
        $weeklyAnalysis = $analysisResult['weekly_analysis'];
        $currentStock = $analysisResult['current_stock'];
        $recommendation = $analysisResult['recommendation'];
        $purchaseInfo = $analysisResult['purchase_info'];
        $transferInfo = $analysisResult['transfer_info'];
        $slipDetails = $analysisResult['slip_details']; // 新規追加
        
        // ヘッダー情報の整形
        $headerInfo = [
            'manufacturer_name' => $basicInfo['manufacturer']['manufacturer_name'],
            'manufacturer_code' => $basicInfo['manufacturer']['manufacturer_code'],
            'product_number' => $basicInfo['product_info']['product_number'],
            'product_name' => $basicInfo['product_info']['product_name'],
            'season_code' => $basicInfo['product_info']['season_code'] ?? '-',
            'first_transfer_date' => $transferInfo['first_transfer_date'],
            'days_since_transfer' => $this->calculateDaysSince($transferInfo['first_transfer_date']),
            'deletion_scheduled_date' => $basicInfo['product_info']['deletion_scheduled_date'] ?? null,
            // 【修正】定価の定義をM単価に変更
            'm_unit_price' => $basicInfo['product_info']['avg_selling_price'] ?? 0, // M単価の平均値
            'avg_cost_price' => $purchaseInfo['avg_cost_price'],
            'is_fallback_date' => $transferInfo['is_fallback']
        ];
        
        // サマリー情報の整形
        // 【修正】サマリー部の並び順変更と総仕入数追加
        $lastWeek = !empty($weeklyAnalysis) ? end($weeklyAnalysis) : null;
        $summaryInfo = [
            // 1. 仕入原価合計
            'total_purchase_cost' => $purchaseInfo['total_purchase_cost'],
            // 2. 売上合計
            'total_sales_amount' => $lastWeek['cumulative_sales_amount'] ?? 0,
            // 3. 粗利合計
            'total_gross_profit' => $lastWeek['cumulative_gross_profit'] ?? 0,
            // 4. 原価回収率
            'recovery_rate' => $lastWeek['recovery_rate'] ?? 0,
            // 5. 総仕入数【新規追加】
            'total_purchase_qty' => $purchaseInfo['total_purchase_qty'],
            // 6. 総販売数
            'total_sales_qty' => $lastWeek['cumulative_sales_qty'] ?? 0,
            // 7. 残在庫数
            'current_stock_qty' => $currentStock['current_stock_qty'],
            // 8. 残在庫原価
            'current_stock_value' => $currentStock['current_stock_value'],
            // 9. 定価【修正】M単価に変更
            'm_unit_price' => $headerInfo['m_unit_price'],
            // 10. 集計対象商品（既存）
            'target_products_count' => count($basicInfo['jan_details'] ?? [])
        ];
        
        // 週別データの整形（拡張版）
        $formattedWeeklyData = [];
        foreach ($weeklyAnalysis as $week) {
            $formattedWeeklyData[] = [
                'week_number' => $week['week_number'],
                'period' => date('m/d', strtotime($week['week_start'])) . '-' . date('m/d', strtotime($week['week_end'])),
                'sales_qty' => $week['weekly_sales_qty'],
                'avg_price' => $week['avg_sales_price'],
                'sales_amount' => $week['weekly_sales_amount'],
                'gross_profit' => $week['weekly_gross_profit'],
                'cumulative_sales' => $week['cumulative_sales_qty'],
                'cumulative_profit' => $week['cumulative_gross_profit'],
                'recovery_rate' => $week['recovery_rate'],
                'remaining_stock' => $week['remaining_stock'], // 新規追加
                // 【修正】M単価ベースでの備考生成
                'remarks' => $this->generateWeekRemarksExtended($week, $headerInfo['m_unit_price']), // 拡張版
                'has_returns' => $week['has_returns'],
                'return_qty' => $week['return_qty'],
                'purchase_events' => $week['purchase_events'], // 新規追加
                'adjustment_events' => $week['adjustment_events'], // 新規追加
                'transfer_events' => $week['transfer_events'] // 新規追加
            ];
        }
        
        // 売価別販売状況の生成（簡易版）
        // 【修正】M単価ベースでの計算
        $priceBreakdown = $this->generatePriceBreakdown($weeklyAnalysis, $headerInfo['m_unit_price']);
        
        // 推奨アクションの整形
        $formattedRecommendation = [
            'status' => $recommendation['status'],
            'status_text' => $this->getStatusText($recommendation['status']),
            'status_class' => $this->getStatusClass($recommendation['status']),
            'message' => $recommendation['message'],
            'action' => $recommendation['action'],
            'days_to_disposal' => $recommendation['days_to_disposal'],
            'disposal_possible' => $recommendation['disposal_possible'],
            'recovery_achieved' => $recommendation['recovery_achieved']
        ];
        
        // 伝票詳細情報の整形（新規追加）
        $formattedSlipDetails = $this->formatSlipDetails($slipDetails);
        
        return [
            'header_info' => $headerInfo,
            'summary_info' => $summaryInfo,
            'weekly_data' => $formattedWeeklyData,
            'price_breakdown' => $priceBreakdown,
            'recommendation' => $formattedRecommendation,
            'slip_details' => $formattedSlipDetails // 新規追加
        ];
    }

    /**
     * 週別備考の生成（拡張版）
     * 
     * 【修正】M単価ベースでの値引き率計算に変更
     * 
     * 【新機能追加】
     * - 仕入イベントの表示（数量付き）
     * - 調整イベントの表示（数量付き）
     * - 移動イベントの表示（数量なし）
     * - 絵文字を活用した視覚的表示
     */
    private function generateWeekRemarksExtended(array $week, float $mUnitPrice): string
    {
        $remarks = [];
        
        // 【修正】M単価ベースでの価格変動検出（絵文字付き）
        if ($week['avg_sales_price'] < $mUnitPrice * 0.95) {
            $discountRate = round((1 - $week['avg_sales_price'] / $mUnitPrice) * 100);
            if ($discountRate >= 50) {
                $remarks[] = "🔥 {$discountRate}%値引";
            } else {
                $remarks[] = "💰 {$discountRate}%値引";
            }
        } elseif ($week['avg_sales_price'] >= $mUnitPrice * 0.95) {
            $remarks[] = '🏪 定価販売';
        }
        
        // 回収率の節目
        if ($week['recovery_rate'] >= 100) {
            $remarks[] = '✅ 原価回収達成';
        }
        
        // 返品発生
        if ($week['has_returns']) {
            $remarks[] = '↩️ 返品発生';
        }
        
        // 売れ行き状況
        if ($week['weekly_sales_qty'] <= 0) {
            $remarks[] = '📉 販売停滞';
        }
        
        // 在庫状況
        if ($week['remaining_stock'] <= 5 && $week['remaining_stock'] > 0) {
            $remarks[] = '⚠️ 在庫僅少';
        } elseif ($week['remaining_stock'] <= 0) {
            $remarks[] = '✅ 完売';
        }
        
        // イベント情報の追加（新規機能）
        $eventBadges = $this->generateEventBadges($week);
        if (!empty($eventBadges)) {
            $remarks = array_merge($remarks, $eventBadges);
        }
        
        return implode('、', $remarks) ?: '-';
    }

    /**
     * イベントバッジの生成（新規追加）
     * 
     * 仕入・調整・移動の各イベントをバッジ形式で生成
     * 
     * @param array $week 週別データ
     * @return array イベントバッジ配列
     */
    private function generateEventBadges(array $week): array
    {
        $badges = [];
        
        // 仕入イベント（数量付き）
        if (!empty($week['purchase_events'])) {
            $totalPurchase = array_sum(array_column($week['purchase_events'], 'quantity'));
            if ($totalPurchase > 0) {
                $badges[] = "📦 仕入+{$totalPurchase}";
            }
        }
        
        // 調整イベント（数量付き）
        if (!empty($week['adjustment_events'])) {
            $totalAdjustment = array_sum(array_column($week['adjustment_events'], 'quantity'));
            if ($totalAdjustment != 0) {
                $sign = $totalAdjustment > 0 ? '+' : '';
                $badges[] = "⚖️ 調整{$sign}{$totalAdjustment}";
            }
        }
        
        // 移動イベント（数量なし）
        if (!empty($week['transfer_events'])) {
            $badges[] = "🚚 移動";
        }
        
        return $badges;
    }
    
    /**
     * 伝票詳細情報の整形（新規追加）
     * 
     * 【修正】各伝票に伝票番号を追加表示
     * 
     * @param array $slipDetails 伝票詳細データ
     * @return array 整形済み伝票詳細
     */
    private function formatSlipDetails(array $slipDetails): array
    {
        return [
            'purchase_slips' => $this->formatPurchaseSlips($slipDetails['purchase_slips']),
            'adjustment_slips' => $this->formatAdjustmentSlips($slipDetails['adjustment_slips']),
            'transfer_slips' => $this->formatTransferSlips($slipDetails['transfer_slips']),
            'summary' => [
                'purchase_count' => count($slipDetails['purchase_slips']),
                'adjustment_count' => count($slipDetails['adjustment_slips']),
                'transfer_count' => count($slipDetails['transfer_slips'])
            ]
        ];
    }
    
    /**
     * 仕入伝票の整形
     * 
     * 【修正】伝票番号を追加
     */
    private function formatPurchaseSlips(array $purchaseSlips): array
    {
        $formatted = [];
        foreach ($purchaseSlips as $slip) {
            $formatted[] = [
                'date' => $slip['purchase_date'],
                'slip_number' => $slip['slip_number'], // 【追加】伝票番号
                'store' => $slip['store_name'] ?: '本部DC',
                'supplier' => $slip['supplier_name'] ?: '-',
                'quantity' => $slip['total_quantity'],
                'unit_price' => $slip['avg_cost_price'],
                'amount' => $slip['total_amount'],
                'type' => $slip['slip_type'],
                'remarks' => $this->getPurchaseRemarks($slip)
            ];
        }
        return $formatted;
    }

    /**
     * 調整伝票の整形
     * 
     * 【修正】伝票番号を追加
     */
    private function formatAdjustmentSlips(array $adjustmentSlips): array
    {
        $formatted = [];
        foreach ($adjustmentSlips as $slip) {
            $formatted[] = [
                'date' => $slip['adjustment_date'],
                'slip_number' => $slip['slip_number'], // 【追加】伝票番号
                'store' => $slip['store_name'] ?: '-',
                'type' => $slip['adjustment_type'] ?: '-',
                'quantity' => $slip['total_quantity'],
                'reason' => $slip['adjustment_reason_name'] ?: '-',
                'staff' => $slip['staff_name'] ?: '-'
            ];
        }
        return $formatted;
    }
    
    /**
     * 移動伝票の整形
     * 
     * 【修正】伝票番号を追加、品出し判定による色分け情報追加
     */
    private function formatTransferSlips(array $transferSlips): array
    {
        $formatted = [];
        foreach ($transferSlips as $slip) {
            $formatted[] = [
                'date' => $slip['transfer_date'],
                'slip_number' => $slip['slip_number'], // 【追加】伝票番号
                'type' => $slip['transfer_type'],
                'source_store' => $slip['source_store_name'],
                'destination_store' => $slip['destination_store_name'],
                'quantity' => $slip['total_quantity'],
                'remarks' => $this->getTransferRemarks($slip),
                'is_initial_delivery' => $slip['is_initial_delivery'] // 【追加】品出し判定フラグ
            ];
        }
        return $formatted;
    }
    
    /**
     * 仕入備考生成
     */
    private function getPurchaseRemarks(array $slip): string
    {
        if ($slip['total_quantity'] > 500) {
            return '大量仕入';
        } elseif (strpos($slip['supplier_name'], '緊急') !== false) {
            return '緊急仕入';
        } elseif ($slip['slip_type'] === '返品') {
            return '仕入返品';
        } else {
            return $slip['slip_type'] === '仕入' ? '通常仕入' : '追加仕入';
        }
    }

    /**
     * 移動備考生成
     */
    private function getTransferRemarks(array $slip): string
    {
        if ($slip['source_store_name'] === '本部DC' || strpos($slip['source_store_name'], 'DC') !== false) {
            return '店舗配送';
        } else {
            return '店舗間移動';
        }
    }

    /**
     * 経過日数計算
     */
    private function calculateDaysSince(string $date): int
    {
        return (int)((time() - strtotime($date)) / 86400);
    }

    /**
     * 週別備考の生成
     */
    private function generateWeekRemarks(array $week, float $sellingPrice): string
    {
        $remarks = [];
        
        // 価格変動の検出
        if ($week['avg_sales_price'] < $sellingPrice * 0.95) {
            $discountRate = round((1 - $week['avg_sales_price'] / $sellingPrice) * 100);
            $remarks[] = "{$discountRate}%値引";
        } elseif ($week['avg_sales_price'] >= $sellingPrice * 0.95) {
            $remarks[] = '定価販売';
        }
        
        // 回収率の節目
        if ($week['recovery_rate'] >= 100) {
            $remarks[] = '原価回収達成';
        }
        
        // 返品発生
        if ($week['has_returns']) {
            $remarks[] = '返品発生';
        }
        
        // 売れ行き状況
        if ($week['weekly_sales_qty'] <= 0) {
            $remarks[] = '販売停滞';
        }
        
        return implode('、', $remarks) ?: '-';
    }

    /**
     * 売価別販売状況の生成（簡易版）
     * 
     * 【修正】M単価ベースでの値引き率計算
     */
    private function generatePriceBreakdown(array $weeklyAnalysis, float $mUnitPrice): array
    {
        $priceGroups = [];
        $totalSales = 0;
        $totalAmount = 0;
        
        foreach ($weeklyAnalysis as $week) {
            $totalSales += $week['weekly_sales_qty'];
            $totalAmount += $week['weekly_sales_amount'];
            
            $priceKey = number_format($week['avg_sales_price'], 0);
            
            if (!isset($priceGroups[$priceKey])) {
                $priceGroups[$priceKey] = [
                    'price' => $week['avg_sales_price'],
                    'quantity' => 0,
                    'amount' => 0,
                    'weeks' => []
                ];
            }
            
            $priceGroups[$priceKey]['quantity'] += $week['weekly_sales_qty'];
            $priceGroups[$priceKey]['amount'] += $week['weekly_sales_amount'];
            $priceGroups[$priceKey]['weeks'][] = $week['week_number'];
        }
        
        // 構成比とその他の計算
        $formattedPriceBreakdown = [];
        foreach ($priceGroups as $group) {
            $ratio = $totalSales > 0 ? ($group['quantity'] / $totalSales) * 100 : 0;
            // 【修正】M単価ベースでの値引き率計算
            $discountRate = $mUnitPrice > 0 ? (1 - $group['price'] / $mUnitPrice) * 100 : 0;
            
            $formattedPriceBreakdown[] = [
                'price' => $group['price'],
                'quantity' => $group['quantity'],
                'amount' => $group['amount'],
                'ratio' => $ratio,
                'discount_rate' => max(0, $discountRate),
                'period' => $this->formatWeeksPeriod($group['weeks'])
            ];
        }
        
        // 価格順でソート（高価格から）
        usort($formattedPriceBreakdown, function($a, $b) {
            return $b['price'] <=> $a['price'];
        });
        
        return $formattedPriceBreakdown;
    }

    /**
     * 週数を期間表現に変換
     */
    private function formatWeeksPeriod(array $weeks): string
    {
        if (empty($weeks)) return '-';
        
        sort($weeks);
        $min = min($weeks);
        $max = max($weeks);
        
        if ($min === $max) {
            return "{$min}週目";
        } else {
            return "{$min}-{$max}週目";
        }
    }

    /**
     * ステータステキストの取得
     */
    private function getStatusText(string $status): string
    {
        $statusMap = [
            'disposal_possible' => '在庫処分実行可能',
            'recovery_achieved' => '原価回収達成',
            'disposal_consideration' => '処分検討',
            'discount_recommended' => '値引き推奨',
            'continue_selling' => '定価維持',
            'no_data' => 'データ不足'
        ];
        
        return $statusMap[$status] ?? '状態不明';
    }

    /**
     * ステータスCSSクラスの取得
     */
    private function getStatusClass(string $status): string
    {
        $classMap = [
            'disposal_possible' => 'success',
            'recovery_achieved' => 'info', 
            'disposal_consideration' => 'warning',
            'discount_recommended' => 'warning',
            'continue_selling' => 'primary',
            'no_data' => 'secondary'
        ];
        
        return $classMap[$status] ?? 'secondary';
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