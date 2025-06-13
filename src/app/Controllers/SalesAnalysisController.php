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
    protected $db;

    public function __construct()
    {
        $this->manufacturerModel = new ManufacturerModel();
        $this->productModel = new ProductModel();
        $this->analysisService = new SingleProductAnalysisService();
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }

    /**
     * セッションチェック（API共通）
     */
    private function checkSession()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([
                'error' => 'セッションが切れています'
            ]);
        }
        return null;
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
     * 単品分析 - 集計実行（フォーム経由）
     * フォーム送信→JANコード取得→single-product/resultにリダイレクト
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

        try {
            $manufacturerCode = $this->request->getPost('manufacturer_code');
            $productNumber = $this->request->getPost('product_number');
            $productName = $this->request->getPost('product_name');
            
            // 指定条件からJANコード一覧を取得
            $janCodes = $this->productModel->getJanCodesByGroup(
                $manufacturerCode,
                $productNumber,
                $productName
            );
            
            if (empty($janCodes)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '指定された条件に該当する商品が見つかりません。');
            }
            
            $janCodeList = array_column($janCodes, 'jan_code');
            
            // single-product/resultにリダイレクト（原価計算方式も引き継ぎ）
            $costMethod = $this->request->getPost('cost_method') ?? 'average';
            $queryString = 'jan_codes=' . implode(',', $janCodeList) . '&cost_method=' . $costMethod;
            return redirect()->to(site_url('sales-analysis/single-product/result?' . $queryString));
            
        } catch (\Exception $e) {
            log_message('error', 'フォーム経由分析エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * クイック分析 - JANコード/SKUコード直接指定
     * 統一エンドポイント（フォーム経由・直接URL両対応）
     */
    public function singleProductResult()
    {
        try {
            // パラメータ取得
            $janCodes = $this->request->getGet('jan_codes');
            $skuCodes = $this->request->getGet('sku_codes');
            
            $targetJanCodes = [];
            
            // JANコード指定の場合
            if (!empty($janCodes)) {
                if (is_string($janCodes)) {
                    $targetJanCodes = array_filter(array_map('trim', explode(',', $janCodes)));
                } elseif (is_array($janCodes)) {
                    $targetJanCodes = array_filter($janCodes);
                }
            }
            // SKUコード指定の場合
            elseif (!empty($skuCodes)) {
                if (is_string($skuCodes)) {
                    $skuCodeList = array_filter(array_map('trim', explode(',', $skuCodes)));
                } elseif (is_array($skuCodes)) {
                    $skuCodeList = array_filter($skuCodes);
                }
                
                // SKUからJANコードに変換
                $targetJanCodes = $this->productModel->getJanCodesBySku($skuCodeList);
                
                if (empty($targetJanCodes)) {
                    return $this->showQuickAnalysisError(
                        'SKUコードエラー',
                        '指定されたSKUコードに対応するJANコードが見つかりません。',
                        ['invalid_sku_codes' => $skuCodeList]
                    );
                }
            }
            else {
                return $this->showQuickAnalysisError(
                    'パラメータエラー',
                    'jan_codes または sku_codes パラメータが必要です。',
                    ['example_url' => site_url('sales-analysis/single-product/result?jan_codes=1234567890123,9876543210987')]
                );
            }
            
            // JANコード検証
            if (empty($targetJanCodes)) {
                return $this->showQuickAnalysisError(
                    'JANコードエラー',
                    '有効なJANコードが指定されていません。'
                );
            }
            
            // 分析実行
            $costMethod = $this->request->getGet('cost_method') ?? 'average';
            $this->analysisService->setCostMethod($costMethod);
            
            $analysisResult = $this->analysisService->executeAnalysisByJanCodes($targetJanCodes);
            
            // 結果データの整形
            $formattedResult = $this->formatAnalysisResultForJanBase($analysisResult);
            
            $data = [
                'pageTitle' => '商品販売分析 - クイック分析結果',
                'analysis_result' => $analysisResult,
                'formatted_result' => $formattedResult,
                'warnings' => $analysisResult['warnings'] ?? [],
                'execution_time' => $analysisResult['execution_time'] ?? 0,
                'input_jan_codes' => $targetJanCodes,
            ];

            return view('sales_analysis/single_product_result', $data);
            
        } catch (SingleProductAnalysisException $e) {
            log_message('error', 'クイック分析エラー: ' . $e->getMessage());
            return $this->showQuickAnalysisError(
                '分析エラー',
                $e->getMessage(),
                ['target_jan_codes' => $targetJanCodes ?? []]
            );
            
        } catch (\Exception $e) {
            log_message('error', 'クイック分析予期しないエラー: ' . $e->getMessage());
            return $this->showQuickAnalysisError(
                'システムエラー',
                '予期しないエラーが発生しました。システム管理者にお問い合わせください。',
                ['error_detail' => $e->getMessage()]
            );
        }
    }

    /**
     * クイック分析エラー画面表示
     */
    protected function showQuickAnalysisError(string $title, string $message, array $additionalData = []): string
    {
        $data = [
            'pageTitle' => '商品販売分析 - エラー',
            'error_title' => $title,
            'error_message' => $message,
            'additional_data' => $additionalData
        ];

        return view('sales_analysis/single_product_error', $data);
    }

    /**
     * JANコードベース分析結果を画面表示用に整形
     */
    private function formatAnalysisResultForJanBase(array $analysisResult): array
    {
        $basicInfo = $analysisResult['basic_info'];
        $weeklyAnalysis = $analysisResult['weekly_analysis'];
        $currentStock = $analysisResult['current_stock'];
        $recommendation = $analysisResult['recommendation'];
        $purchaseInfo = $analysisResult['purchase_info'];
        $transferInfo = $analysisResult['transfer_info'];
        $slipDetails = $analysisResult['slip_details'];
        
        // 代表商品情報の取得
        $representative = $basicInfo['representative_product'];
        
        // ヘッダー情報の整形
        $headerInfo = [
            'manufacturer_name' => $representative['manufacturer_name'],
            'manufacturer_code' => $representative['manufacturer_code'],
            'product_number' => $representative['product_number'],
            'product_name' => $representative['product_name'],
            'season_code' => $representative['season_code'] ?? '-',
            'first_transfer_date' => $transferInfo['first_transfer_date'],
            'days_since_transfer' => $this->calculateDaysSince($transferInfo['first_transfer_date']),
            'deletion_scheduled_date' => $representative['deletion_scheduled_date'] ?? null,
            'm_unit_price' => (float)($representative['m_unit_price'] ?? 0),
            'avg_cost_price' => $purchaseInfo['avg_cost_price'],
            'is_fallback_date' => $transferInfo['is_fallback'],
            'total_manufacturers' => $basicInfo['total_manufacturer_count'],
            'total_product_groups' => $basicInfo['total_product_group_count'],
            'is_multi_group' => $basicInfo['total_product_group_count'] > 1
        ];
        
        // サマリー情報の整形
        $lastWeek = !empty($weeklyAnalysis) ? end($weeklyAnalysis) : null;
        $summaryInfo = [
            'total_purchase_cost' => $purchaseInfo['total_purchase_cost'],
            'total_sales_amount' => $lastWeek['cumulative_sales_amount'] ?? 0,
            'total_gross_profit' => $lastWeek['cumulative_gross_profit'] ?? 0,
            'recovery_rate' => $lastWeek['recovery_rate'] ?? 0,
            'total_purchase_qty' => $purchaseInfo['total_purchase_qty'],
            'total_sales_qty' => $lastWeek['cumulative_sales_qty'] ?? 0,
            'current_stock_qty' => $currentStock['current_stock_qty'],
            'current_stock_value' => $currentStock['current_stock_value'],
            'm_unit_price' => $headerInfo['m_unit_price'],
            'target_products_count' => $basicInfo['total_jan_count']
        ];
        
        // 週別データの整形
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
                'remaining_stock' => $week['remaining_stock'],
                'remarks' => $this->generateWeekRemarksExtended($week, $headerInfo['m_unit_price']),
                'has_returns' => $week['has_returns'],
                'return_qty' => $week['return_qty'],
                'purchase_events' => $week['purchase_events'],
                'adjustment_events' => $week['adjustment_events'],
                'transfer_events' => $week['transfer_events']
            ];
        }
        
        // 売価別販売状況の生成
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
        
        // 伝票詳細情報の整形
        $formattedSlipDetails = $this->formatSlipDetails($slipDetails);
        
        return [
            'header_info' => $headerInfo,
            'summary_info' => $summaryInfo,
            'weekly_data' => $formattedWeeklyData,
            'price_breakdown' => $priceBreakdown,
            'recommendation' => $formattedRecommendation,
            'slip_details' => $formattedSlipDetails,
            'manufacturer_groups' => $basicInfo['manufacturers'],
            'product_groups' => $basicInfo['product_groups']
        ];
    }

    /**
     * 週別備考の生成（拡張版）
     */
    private function generateWeekRemarksExtended(array $week, float $mUnitPrice): string
    {
        $remarks = [];
        
        // M単価ベースでの価格変動検出
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
        
        // イベント情報の追加
        $eventBadges = $this->generateEventBadges($week);
        if (!empty($eventBadges)) {
            $remarks = array_merge($remarks, $eventBadges);
        }
        
        return implode('、', $remarks) ?: '-';
    }

    /**
     * イベントバッジの生成
     */
    private function generateEventBadges(array $week): array
    {
        $badges = [];
        
        // 仕入イベント
        if (!empty($week['purchase_events'])) {
            $totalPurchase = array_sum(array_column($week['purchase_events'], 'quantity'));
            if ($totalPurchase > 0) {
                $badges[] = "📦 仕入+{$totalPurchase}";
            }
        }
        
        // 調整イベント
        if (!empty($week['adjustment_events'])) {
            $totalAdjustment = array_sum(array_column($week['adjustment_events'], 'quantity'));
            if ($totalAdjustment != 0) {
                $sign = $totalAdjustment > 0 ? '+' : '';
                $badges[] = "⚖️ 調整{$sign}{$totalAdjustment}";
            }
        }
        
        // 移動イベント
        if (!empty($week['transfer_events'])) {
            $badges[] = "🚚 移動";
        }
        
        // 発注イベント
        if (!empty($week['order_events'])) {
            $badges[] = "📠 発注";
        }
        
        return $badges;
    }
    
    /**
     * 伝票詳細情報の整形
     */
    private function formatSlipDetails(array $slipDetails): array
    {
        return [
            'purchase_slips' => $this->formatPurchaseSlips($slipDetails['purchase_slips']),
            'adjustment_slips' => $this->formatAdjustmentSlips($slipDetails['adjustment_slips']),
            'transfer_slips' => $this->formatTransferSlips($slipDetails['transfer_slips']),
            'order_slips' => $this->formatOrderSlips($slipDetails['order_slips'] ?? []),
            'summary' => [
                'purchase_count' => count($slipDetails['purchase_slips']),
                'adjustment_count' => count($slipDetails['adjustment_slips']),
                'transfer_count' => count($slipDetails['transfer_slips']),
                'order_count' => count($slipDetails['order_slips'] ?? [])
            ]
        ];
    }
    
    /**
     * 仕入伝票の整形
     */
    private function formatPurchaseSlips(array $purchaseSlips): array
    {
        $formatted = [];
        foreach ($purchaseSlips as $slip) {
            $formatted[] = [
                'date' => $slip['purchase_date'],
                'slip_number' => $slip['slip_number'],
                'order_number' => $slip['order_number'] ?? null,
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
     */
    private function formatAdjustmentSlips(array $adjustmentSlips): array
    {
        $formatted = [];
        foreach ($adjustmentSlips as $slip) {
            $formatted[] = [
                'date' => $slip['adjustment_date'],
                'slip_number' => $slip['slip_number'],
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
     */
    private function formatTransferSlips(array $transferSlips): array
    {
        $formatted = [];
        foreach ($transferSlips as $slip) {
            $formatted[] = [
                'date' => $slip['transfer_date'],
                'slip_number' => $slip['slip_number'],
                'type' => $slip['transfer_type'],
                'source_store' => $slip['source_store_name'],
                'destination_store' => $slip['destination_store_name'],
                'quantity' => $slip['total_quantity'],
                'remarks' => $this->getTransferRemarks($slip),
                'is_initial_delivery' => $slip['is_initial_delivery']
            ];
        }
        return $formatted;
    }
    
    /**
     * 発注伝票の整形
     */
    private function formatOrderSlips(array $orderSlips): array
    {
        $formatted = [];
        foreach ($orderSlips as $slip) {
            $formatted[] = [
                'date' => $slip['order_date'],
                'order_number' => $slip['order_number'],
                'store' => $slip['store_name'] ?: '-',
                'supplier' => $slip['supplier_name'] ?: '-',
                'delivery_method' => $slip['delivery_method'] ?: '-',
                'quantity' => $slip['total_quantity'],
                'unit_price' => $slip['avg_cost_price'],
                'amount' => $slip['total_amount'],
                'warehouse_delivery' => $slip['warehouse_delivery_date'] ?: '-',
                'store_delivery' => $slip['store_delivery_date'] ?: '-'
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
     * 売価別販売状況の生成
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
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $exact = $this->request->getGet('exact');
        $limit = 10;
        
        // 除外範囲パラメータ
        $excludeRangeStart = $this->request->getGet('exclude_range_start');
        $excludeRangeEnd = $this->request->getGet('exclude_range_end');
        
        try {
            $builder = $this->manufacturerModel;
            
            // 除外範囲の適用（0100000-0199999を除外）
            if ($excludeRangeStart && $excludeRangeEnd) {
                $builder = $builder->groupStart()
                        ->where('manufacturer_code <', $excludeRangeStart)
                        ->orWhere('manufacturer_code >', $excludeRangeEnd)
                        ->groupEnd();
            }
            
            if (!empty($keyword)) {
                if ($exact) {
                    $builder = $builder->where('manufacturer_code', $keyword);
                } else {
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
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $manufacturerCode = $this->request->getGet('manufacturer_code');
        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = 50;

        
        try {
            if (empty($manufacturerCode)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'メーカーコードが必要です。'
                ]);
            }

            $manufacturer = $this->manufacturerModel->find($manufacturerCode);
            if (!$manufacturer) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => '指定されたメーカーが見つかりません。'
                ]);
            }

            // ProductModelからページネーション対応でデータを取得
            // 仮定: getProductNumberGroups は ['data' => [], 'total' => 0] のような形式で返す
            // モデル側の改修または新しいメソッドの作成が必要な場合があります。
            $pagedResult = $this->productModel->getProductNumberGroups(
                $manufacturerCode,
                $keyword,
                $limit, // $limit は 50 のまま
                $page   // page パラメータを渡す
            );

            $products = $pagedResult['data'] ?? [];
            $totalCount = (int)($pagedResult['total'] ?? 0);
            


            // $products は $pagedResult['data'] を使用するため、上記の再呼び出しは不要
            // 前回の修正で削除済みのはずですが、念のためコメントアウト
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

            $totalPages = ($limit > 0 && $totalCount > 0) ? (int)ceil($totalCount / $limit) : 1;
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => $page,
                    'total_count' => $totalCount,
                    'per_page' => $limit,
                    'total_pages' => $totalPages,
                    'has_next_page' => $page < $totalPages,
                    'has_prev_page' => $page > 1,
                    'from' => $totalCount > 0 ? (($page - 1) * $limit) + 1 : 0,
                    'to' => $totalCount > 0 ? min($page * $limit, $totalCount) : 0,
                ],
                'keyword' => $keyword,
                'manufacturer' => $manufacturer // 元のコード通りメーカーオブジェクトを返す
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
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

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
                    'avg_selling_price' => count($formattedJanCodes) > 0 
                        ? array_sum(array_column($formattedJanCodes, 'selling_price')) / count($formattedJanCodes) 
                        : 0,
                    'price_range' => [
                        'min' => count($formattedJanCodes) > 0 ? min(array_column($formattedJanCodes, 'selling_price')) : 0,
                        'max' => count($formattedJanCodes) > 0 ? max(array_column($formattedJanCodes, 'selling_price')) : 0
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
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

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

    /**
     * コード分析 - 集計指示画面
     */
    public function codeAnalysis()
    {
        $data = [
            'pageTitle' => '商品販売分析 - コード分析 集計指示'
        ];

        return view('sales_analysis/code_analysis_form', $data);
    }

    /**
     * コード分析 - 集計実行（フォーム経由）
     */
    public function executeCodeAnalysis()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', '不正なリクエストです。');
        }

        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'code_type' => [
                'label' => 'コード種類',
                'rules' => 'required|in_list[jan_code,sku_code]'
            ],
            'product_codes' => [
                'label' => '商品コード',
                'rules' => 'required'
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            $codeType = $this->request->getPost('code_type');
            $productCodesJson = $this->request->getPost('product_codes');
            
            // JSON形式の商品コードリストをデコード
            $productCodes = json_decode($productCodesJson, true);
            if (!is_array($productCodes) || empty($productCodes)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '有効な商品コードが指定されていません。');
            }
            
            // コードリストから有効なコードのみ抽出
            $validCodes = array_filter(array_map('trim', $productCodes));
            if (empty($validCodes)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '有効な商品コードが指定されていません。');
            }
            
            // single-product/resultにリダイレクト
            $costMethod = $this->request->getPost('cost_method') ?? 'average';
            
            if ($codeType === 'jan_code') {
                $queryString = 'jan_codes=' . implode(',', $validCodes) . '&cost_method=' . $costMethod;
            } else {
                $queryString = 'sku_codes=' . implode(',', $validCodes) . '&cost_method=' . $costMethod;
            }
            
            return redirect()->to(site_url('sales-analysis/single-product/result?' . $queryString));
            
        } catch (\Exception $e) {
            log_message('error', 'コード分析エラー: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', '集計処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 全商品検索API（Ajax用）
     */
    public function searchAllProducts()
    {
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }
        
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $keyword = $this->request->getGet('keyword');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = 50; // 20から50に変更
        
        try {
            // 空のキーワードでも検索を許可（初期表示用）
            if (!empty($keyword) && strlen($keyword) < 2) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => '検索キーワードは2文字以上で入力してください。',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_count' => 0,
                        'per_page' => $limit,
                        'total_pages' => 0,
                        'has_next_page' => false,
                        'has_prev_page' => false,
                        'from' => 0,
                        'to' => 0
                    ],
                    'keyword' => $keyword
                ]);
            }

            // データベース接続確認
            if (!$this->db) {
                throw new \Exception('データベース接続が確立されていません');
            }

            $builder = $this->db->table('products p');
            $builder->select([
                'p.jan_code',
                'p.sku_code', 
                'p.product_number',
                'p.product_name',
                'p.color_code',
                'p.size_code',
                'p.selling_price',
                'p.m_unit_price',
                'p.cost_price',
                'p.last_purchase_cost',
                'p.manufacturer_code',
                'm.manufacturer_name',
                'p.deletion_type',
                'p.deletion_scheduled_date'
            ]);
            
            $builder->join('manufacturers m', 'p.manufacturer_code = m.manufacturer_code', 'left');
            
            // 検索条件（キーワードがある場合のみ）
            if (!empty($keyword)) {
                $builder->groupStart()
                    ->like('p.product_name', $keyword)
                    ->orLike('m.manufacturer_name', $keyword)
                    ->orLike('p.product_number', $keyword)
                    ->orLike('p.jan_code', $keyword);
                
                // SKUコードがNULLでない場合のみ検索条件に含める
                $builder->orWhere('p.sku_code IS NOT NULL')
                    ->like('p.sku_code', $keyword);
                
                $builder->groupEnd();
            }
            
            // 廃盤商品を除外
            $builder->groupStart()
                ->where('p.deletion_type IS NULL')
                ->orWhere('p.deletion_type', 0)
                ->orWhere('p.deletion_scheduled_date >', date('Y-m-d'))
                ->orWhere('p.deletion_scheduled_date IS NULL')
                ->groupEnd();
            
            // 総件数取得
            $countBuilder = clone $builder;
            $totalCount = $countBuilder->countAllResults();
            
            // キーワードなしで件数が多すぎる場合は制限
            if (empty($keyword) && $totalCount > 1000) {
                $totalCount = 1000;
            }
            
            $products = $builder
                ->orderBy('p.sku_code', 'DESC')  // SKUコード降順を追加（最優先）
                ->orderBy('p.manufacturer_code')
                ->orderBy('p.product_number')
                ->orderBy('p.jan_code')
                ->limit($limit, ($page - 1) * $limit)
                ->get()->getResultArray();

            // データ整形
            foreach ($products as &$product) {
                $product['selling_price'] = (float)($product['selling_price'] ?? 0);
                $product['m_unit_price'] = (float)($product['m_unit_price'] ?? 0);
                $product['cost_price'] = (float)($product['cost_price'] ?? 0);
                $product['last_purchase_cost'] = (float)($product['last_purchase_cost'] ?? 0);
                $product['size_name'] = $this->generateSizeName($product['size_code']);
                $product['color_name'] = $this->generateColorName($product['color_code']);
                
                // 有効原価の決定
                $product['effective_cost_price'] = $product['last_purchase_cost'] > 0 
                    ? $product['last_purchase_cost'] 
                    : $product['cost_price'];
            }

            $totalPages = $totalCount > 0 ? ceil($totalCount / $limit) : 1;
            $hasNextPage = $page < $totalPages;
            $hasPrevPage = $page > 1;

            return $this->response->setJSON([
                'success' => true,
                'data' => $products,
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
            log_message('error', '全商品検索エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => '検索処理中にエラーが発生しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 商品コード検証API（Ajax用）
     */
    public function validateProductCode()
    {
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }

        $code = $this->request->getGet('code');
        $codeType = $this->request->getGet('code_type');
        
        try {
            if (empty($code) || empty($codeType)) {
                return $this->response->setJSON([
                    'success' => false,
                    'valid' => false,
                    'message' => '必要なパラメータが不足しています。'
                ]);
            }

            // 形式チェック
            $isValidFormat = $this->isValidCodeFormat($code, $codeType);
            if (!$isValidFormat) {
                return $this->response->setJSON([
                    'success' => true,
                    'valid' => false,
                    'message' => 'コード形式が正しくありません。',
                    'product_info' => null
                ]);
            }

            // 商品情報取得
            $productInfo = $this->getProductInfoByCode($code, $codeType);
            
            if (!$productInfo) {
                return $this->response->setJSON([
                    'success' => true,
                    'valid' => false,
                    'message' => '該当する商品が見つかりません。',
                    'product_info' => null
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'valid' => true,
                'message' => '商品情報を取得しました。',
                'product_info' => $productInfo
            ]);

        } catch (\Exception $e) {
            log_message('error', '商品コード検証エラー: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'valid' => false,
                'message' => 'システムエラーが発生しました。',
                'product_info' => null
            ]);
        }
    }

    /**
     * コード形式の基本チェック
     */
    private function isValidCodeFormat($code, $codeType)
    {
        if ($codeType === 'jan_code') {
            // JANコード: 8桁または13桁の数字
            return preg_match('/^\d{8}$|^\d{13}$/', $code);
        } else {
            // SKUコード: 英数字、ハイフン、アンダースコア（1-50文字）
            return preg_match('/^[A-Za-z0-9\-_]{1,50}$/', $code);
        }
    }

    /**
     * 商品情報の取得
     */
    private function getProductInfoByCode($code, $codeType)
    {
        try {
            // データベース接続確認
            if (!$this->db) {
                log_message('error', 'データベース接続が確立されていません');
                return null;
            }

            $builder = $this->db->table('products p');
            $builder->select([
                'p.jan_code',
                'p.sku_code',
                'p.manufacturer_code',
                'p.product_number',
                'p.product_name',
                'p.color_code',
                'p.size_code',
                'p.selling_price',
                'p.m_unit_price',
                'p.cost_price',
                'p.last_purchase_cost',
                'p.deletion_type',
                'p.deletion_scheduled_date',
                'm.manufacturer_name'
            ]);
            
            $builder->join('manufacturers m', 'p.manufacturer_code = m.manufacturer_code', 'left');
            
            if ($codeType === 'jan_code') {
                $builder->where('p.jan_code', $code);
            } else {
                $builder->where('p.sku_code', $code);
            }
            
            // 廃盤商品を除外
            $builder->groupStart()
                ->where('p.deletion_type IS NULL')
                ->orWhere('p.deletion_type', 0)
                ->orWhere('p.deletion_scheduled_date >', date('Y-m-d'))
                ->orWhere('p.deletion_scheduled_date IS NULL')
                ->groupEnd();
            
            $product = $builder->get()->getRowArray();
            
            if (!$product) {
                return null;
            }

            // データ整形
            return [
                'jan_code' => $product['jan_code'],
                'sku_code' => $product['sku_code'] ?? '',
                'manufacturer_code' => $product['manufacturer_code'],
                'manufacturer_name' => $product['manufacturer_name'] ?? '-',
                'product_number' => $product['product_number'],
                'product_name' => $product['product_name'],
                'color_code' => $product['color_code'],
                'color_name' => $this->generateColorName($product['color_code']),
                'size_code' => $product['size_code'],
                'size_name' => $this->generateSizeName($product['size_code']),
                'selling_price' => (float)($product['selling_price'] ?? 0),
                'm_unit_price' => (float)($product['m_unit_price'] ?? 0),
                'cost_price' => (float)($product['cost_price'] ?? 0),
                'effective_cost_price' => (float)($product['last_purchase_cost'] ?? $product['cost_price'] ?? 0),
                'manufacturer' => $product['manufacturer_code'] . ':' . ($product['manufacturer_name'] ?? '-')
            ];

        } catch (\Exception $e) {
            log_message('error', '商品情報取得エラー: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * サイズ名称生成（簡易版）
     */
    private function generateSizeName($sizeCode)
    {
        if (empty($sizeCode)) {
            return 'F';
        }
        
        $sizeMap = [
            'XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL',
            'XXL' => 'XXL', '2L' => '2L', '3L' => '3L', 'FREE' => 'F'
        ];
        
        $upperSizeCode = strtoupper($sizeCode);
        return $sizeMap[$upperSizeCode] ?? $sizeCode;
    }

    /**
     * カラー名称生成（簡易版）
     */
    private function generateColorName($colorCode)
    {
        if (empty($colorCode)) {
            return '-';
        }
        
        $colorMap = [
            'BK' => '黒', 'WH' => '白', 'RD' => '赤', 'BL' => '青', 'GR' => '緑',
            'YE' => '黄', 'PK' => 'ピンク', 'GY' => 'グレー', 'NV' => 'ネイビー', 'BR' => 'ブラウン'
        ];
        
        $upperCode = strtoupper($colorCode);
        return $colorMap[$upperCode] ?? $colorCode;
    }

    /**
     * AI分析用データ生成（Ajax用）
     */
    public function generateAiDataAjax()
    {
        // セッションチェック
        if ($sessionError = $this->checkSession()) {
            return $sessionError;
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => '不正なリクエスト']);
        }
        
        try {
            $requestData = $this->request->getJSON(true);
            $janCodes = $requestData['jan_codes'] ?? [];
            
            if (empty($janCodes)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'JANコードが指定されていません'
                ]);
            }
            
            // 原価計算方式の設定
            $costMethod = $requestData['cost_method'] ?? 'average';
            $this->analysisService->setCostMethod($costMethod);
            
            // 分析を再実行
            $analysisResult = $this->analysisService->executeAnalysisByJanCodes($janCodes);
            $formattedResult = $this->formatAnalysisResultForJanBase($analysisResult);
            
            // AI用テキスト生成
            $aiText = $this->generateAiAnalysisText($analysisResult, $formattedResult);
            
            return $this->response->setJSON([
                'success' => true,
                'ai_text' => $aiText,
                'character_count' => strlen($aiText),
                'generation_time' => date('Y-m-d H:i:s')
            ]);
            
        } catch (SingleProductAnalysisException $e) {
            log_message('error', 'AI分析データ生成エラー（分析例外）: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => '分析データの生成に失敗しました: ' . $e->getMessage()
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'AI分析データ生成エラー（一般例外）: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'データ生成中にエラーが発生しました'
            ]);
        }
    }
    
    /**
     * AI分析用テキストデータ生成（アパレル・雑貨特化版）
     */
    private function generateAiAnalysisText($analysisResult, $formattedResult): string
    {
        $representative = $analysisResult['basic_info']['representative_product'];
        $lastWeek = !empty($analysisResult['weekly_analysis']) ? end($analysisResult['weekly_analysis']) : null;
        $purchaseInfo = $analysisResult['purchase_info'];
        $transferInfo = $analysisResult['transfer_info'];
        $currentStock = $analysisResult['current_stock'];
        $recommendation = $analysisResult['recommendation'];
        
        $text = "=== アパレル・雑貨商品 販売分析データ（AI分析用） ===\n\n";
        
        // 商品基本情報（業界特化）
        $text .= "【商品基本情報】\n";
        $text .= "商品名: " . $representative['product_name'] . "\n";
        $text .= "メーカー: " . $representative['manufacturer_name'] . "\n";
        $text .= "品番: " . $representative['product_number'] . "\n";
        $text .= "シーズン: " . ($representative['season_code'] ?? '不明') . "\n";
        $text .= "商品年: " . ($representative['product_year'] ?? '不明') . "\n";
        $text .= "展開SKU数: " . $analysisResult['basic_info']['total_jan_count'] . "個\n";
        $text .= "品出し日: " . $transferInfo['first_transfer_date'] . "\n";
        $text .= "経過日数: " . $formattedResult['header_info']['days_since_transfer'] . "日\n";
        
        if ($representative['deletion_scheduled_date']) {
            $daysToDisposal = (strtotime($representative['deletion_scheduled_date']) - time()) / 86400;
            $text .= "廃盤予定: あと" . round($daysToDisposal) . "日\n";
        }
        $text .= "\n";
        
        // 価格戦略情報
        $text .= "【価格戦略情報】\n";
        $text .= "M単価（定価）: ¥" . number_format($representative['m_unit_price']) . "\n";
        $text .= "仕入単価: ¥" . number_format($purchaseInfo['avg_cost_price']) . "\n";
        $text .= "粗利率: " . number_format((($representative['m_unit_price'] - $purchaseInfo['avg_cost_price']) / $representative['m_unit_price']) * 100, 1) . "%\n";
        $text .= "現在の平均売価: ¥" . number_format($lastWeek['avg_sales_price'] ?? 0) . "\n";
        
        if ($lastWeek && $representative['m_unit_price'] > 0) {
            $currentDiscountRate = (1 - $lastWeek['avg_sales_price'] / $representative['m_unit_price']) * 100;
            $text .= "現在の値引率: " . number_format(max(0, $currentDiscountRate), 1) . "%\n";
        }
        $text .= "\n";
        
        // 財務パフォーマンス
        $text .= "【財務パフォーマンス】\n";
        $text .= "総仕入金額: ¥" . number_format($purchaseInfo['total_purchase_cost']) . "\n";
        $text .= "累計売上金額: ¥" . number_format($lastWeek['cumulative_sales_amount'] ?? 0) . "\n";
        $text .= "累計粗利: ¥" . number_format($lastWeek['cumulative_gross_profit'] ?? 0) . "\n";
        $text .= "原価回収率: " . number_format($lastWeek['recovery_rate'] ?? 0, 1) . "%\n";
        $text .= "残在庫数: " . $currentStock['current_stock_qty'] . "個\n";
        $text .= "残在庫金額: ¥" . number_format($currentStock['current_stock_value']) . "\n";
        $text .= "\n";
        
        // 週別販売トレンド（アパレル特化）
        $text .= "【週別販売トレンド】\n";
        foreach ($analysisResult['weekly_analysis'] as $week) {
            $priceStatus = '';
            if ($week['avg_sales_price'] > 0 && $representative['m_unit_price'] > 0) {
                $discountRate = (1 - $week['avg_sales_price'] / $representative['m_unit_price']) * 100;
                if ($discountRate < 5) {
                    $priceStatus = '定価';
                } elseif ($discountRate < 30) {
                    $priceStatus = number_format($discountRate, 0) . '%値引';
                } else {
                    $priceStatus = number_format($discountRate, 0) . '%大幅値引';
                }
            }
            
            $text .= sprintf(
                "%d週目: 販売%d個、売価¥%s、回収率%.1f%%、在庫%d個、%s\n",
                $week['week_number'],
                $week['weekly_sales_qty'],
                number_format($week['avg_sales_price']),
                $week['recovery_rate'],
                $week['remaining_stock'],
                $priceStatus
            );
        }
        $text .= "\n";
        
        // SKU別パフォーマンス（サイズ・カラー展開）
        if (!empty($formattedResult['price_breakdown'])) {
            $text .= "【価格帯別販売実績】\n";
            foreach ($formattedResult['price_breakdown'] as $price) {
                $text .= sprintf(
                    "¥%s: %d個(%.1f%%)、値引率%.0f%%、%s\n",
                    number_format($price['price']),
                    $price['quantity'],
                    $price['ratio'],
                    $price['discount_rate'],
                    $price['period']
                );
            }
            $text .= "\n";
        }
        
        // 季節性・トレンド分析用情報
        $text .= "【季節性・市場環境】\n";
        $currentMonth = date('n');
        $seasonInfo = $this->getSeasonInfo($currentMonth, $representative['season_code'] ?? '');
        $text .= "現在時期: " . $seasonInfo['season'] . "\n";
        $text .= "商品適正時期: " . $seasonInfo['product_season'] . "\n";
        $text .= "時期適合度: " . $seasonInfo['match_level'] . "\n";
        $text .= "\n";
        
        // 現在のステータスと推奨アクション
        $text .= "【現在のステータス】\n";
        $text .= "推奨判定: " . $recommendation['message'] . "\n";
        $text .= "処分可能性: " . ($recommendation['disposal_possible'] ? '可能' : '要検討') . "\n";
        $text .= "推奨アクション: " . $recommendation['action'] . "\n";
        $text .= "\n";
        
        // AI分析要求（アパレル・雑貨特化）
        $text .= "【AI分析依頼内容】\n";
        $text .= "以下の観点でアパレル・雑貨商品として分析してください：\n\n";
        $text .= "1. 【販売トレンド分析】\n";
        $text .= "   - 立ち上がりから現在までの売れ行き評価\n";
        $text .= "   - 週別販売数の推移パターン分析\n";
        $text .= "   - 価格弾力性の評価（値引き効果）\n";
        $text .= "   - 季節性・時期要因の影響分析\n\n";
        $text .= "2. 【在庫管理提案】\n";
        $text .= "   - 現在の在庫消化ペース評価\n";
        $text .= "   - 残在庫リスクの評価\n";
        $text .= "   - サイズ・カラー別の売れ筋分析\n";
        $text .= "   - 在庫処分の緊急度判定\n\n";
        $text .= "3. 【アクション提案】\n";
        $text .= "   - 具体的な値下げ戦略（時期・率・期間）\n";
        $text .= "   - 販促施策の提案\n";
        $text .= "   - 処分方法の選択肢\n";
        $text .= "   - 類似商品の仕入れ判断への活用方法\n\n";
        $text .= "4. 【業界ベストプラクティス】\n";
        $text .= "   - アパレル・雑貨業界の一般的な処分タイミング\n";
        $text .= "   - 季節商品の効率的な販売戦略\n";
        $text .= "   - 同業他社との比較評価\n";
        $text .= "   - 次回仕入れへの改善提案\n\n";
        
        return $text;
    }

    /**
     * 季節情報取得
     */
    private function getSeasonInfo($currentMonth, $seasonCode): array
    {
        $seasonMap = [
            'SS' => ['name' => '春夏', 'months' => [3,4,5,6,7,8]],
            'AW' => ['name' => '秋冬', 'months' => [9,10,11,12,1,2]],
            'SP' => ['name' => '春', 'months' => [3,4,5]],
            'SU' => ['name' => '夏', 'months' => [6,7,8]],
            'AU' => ['name' => '秋', 'months' => [9,10,11]],
            'WI' => ['name' => '冬', 'months' => [12,1,2]]
        ];
        
        $currentSeason = ($currentMonth >= 3 && $currentMonth <= 8) ? '春夏時期' : '秋冬時期';
        $productSeason = $seasonMap[$seasonCode]['name'] ?? '通年';
        
        $isMatch = false;
        if (isset($seasonMap[$seasonCode])) {
            $isMatch = in_array($currentMonth, $seasonMap[$seasonCode]['months']);
        }
        
        return [
            'season' => $currentSeason,
            'product_season' => $productSeason,
            'match_level' => $isMatch ? '適正時期' : '時期外'
        ];
    }    
}