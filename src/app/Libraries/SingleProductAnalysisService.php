<?php

namespace App\Libraries;

use App\Models\ProductModel;
use App\Models\ManufacturerModel;
use CodeIgniter\Database\ConnectionInterface;

/**
 * 単品販売分析サービス（JANコードベース統一版）
 */
class SingleProductAnalysisService
{
    protected $db;
    protected $productModel;
    protected $manufacturerModel;
    protected $costMethod = 'average';
    protected $maxWeeks = 50;
    protected $timeout = 30;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->productModel = new ProductModel();
        $this->manufacturerModel = new ManufacturerModel();
    }
    
    /**
     * JANコードベース単品分析を実行
     */
    public function executeAnalysisByJanCodes(array $janCodes): array
    {
        $startTime = microtime(true);
        
        try {
            log_message('info', 'JANコードベース単品分析開始: ' . json_encode($janCodes));
            
            // 1. JANコード検証・商品情報取得
            $basicInfo = $this->getBasicInfoByJanCodes($janCodes);
            
            // 2. 代表商品選定
            $representativeProduct = $this->selectRepresentativeProduct($basicInfo['products']);
            $basicInfo['representative_product'] = $representativeProduct;
            
            // 3. 品出し日取得
            $transferInfo = $this->getTransferInfo($basicInfo['valid_jan_codes']);
            
            // 4. 仕入情報取得
            $purchaseInfo = $this->getPurchaseInfo($basicInfo['valid_jan_codes'], $transferInfo['first_transfer_date']);
            
            // 5. 週別販売データ取得
            $weeklySales = $this->getWeeklySales($basicInfo['valid_jan_codes'], $transferInfo['first_transfer_date']);
            
            // 6. 週別データの統合・計算
            $weeklyAnalysis = $this->calculateWeeklyAnalysisExtended(
                $weeklySales, 
                $purchaseInfo, 
                $transferInfo['first_transfer_date'],
                $basicInfo['valid_jan_codes']
            );
            
            // 7. 現在庫計算
            $currentStock = $this->calculateCurrentStock($basicInfo['valid_jan_codes'], $purchaseInfo, $weeklyAnalysis);
            
            // 8. 処分判定・推奨アクション
            $recommendation = $this->generateRecommendation($weeklyAnalysis, $currentStock, $basicInfo);
            
            // 9. 警告情報の収集
            $warnings = $this->collectWarnings($transferInfo, $purchaseInfo, $weeklyAnalysis, $basicInfo);
            
            // 10. 伝票詳細情報の取得
            $slipDetails = $this->getSlipDetails($basicInfo['valid_jan_codes']);
            
            $executionTime = microtime(true) - $startTime;
            log_message('info', "JANコードベース単品分析完了: {$executionTime}秒");
            
            return [
                'basic_info' => $basicInfo,
                'transfer_info' => $transferInfo,
                'purchase_info' => $purchaseInfo,
                'weekly_analysis' => $weeklyAnalysis,
                'current_stock' => $currentStock,
                'recommendation' => $recommendation,
                'warnings' => $warnings,
                'slip_details' => $slipDetails,
                'execution_time' => $executionTime,
                'analysis_date' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'JANコードベース単品分析エラー: ' . $e->getMessage());
            throw new SingleProductAnalysisException(
                '分析処理中にエラーが発生しました: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * JANコードから基本情報取得
     */
    protected function getBasicInfoByJanCodes(array $janCodes): array
    {
        // JANコード検証
        $validation = $this->productModel->validateJanCodes($janCodes);
        if (!$validation['valid']) {
            throw new SingleProductAnalysisException($validation['message']);
        }
        
        // 商品情報取得
        $products = $this->productModel->getProductsByJanCodes($validation['valid_codes']);
        if (empty($products)) {
            throw new SingleProductAnalysisException('商品情報が取得できませんでした');
        }
        
        // メーカー情報の集計
        $manufacturers = [];
        $manufacturerGroups = [];
        
        foreach ($products as $product) {
            $manufacturerCode = $product['manufacturer_code'];
            
            if (!isset($manufacturers[$manufacturerCode])) {
                $manufacturers[$manufacturerCode] = [
                    'manufacturer_code' => $manufacturerCode,
                    'manufacturer_name' => $product['manufacturer_name'],
                    'product_count' => 0,
                    'jan_count' => 0
                ];
            }
            
            $manufacturers[$manufacturerCode]['jan_count']++;
            
            // 商品グループ（メーカー+品番+品名）の集計
            $groupKey = $manufacturerCode . '_' . $product['product_number'] . '_' . $product['product_name'];
            if (!isset($manufacturerGroups[$groupKey])) {
                $manufacturerGroups[$groupKey] = [
                    'manufacturer_code' => $manufacturerCode,
                    'manufacturer_name' => $product['manufacturer_name'],
                    'product_number' => $product['product_number'],
                    'product_name' => $product['product_name'],
                    'season_code' => $product['season_code'],
                    'jan_codes' => [],
                    'jan_count' => 0
                ];
                $manufacturers[$manufacturerCode]['product_count']++;
            }
            
            $manufacturerGroups[$groupKey]['jan_codes'][] = $product['jan_code'];
            $manufacturerGroups[$groupKey]['jan_count']++;
        }
        
        return [
            'input_jan_codes' => $janCodes,
            'valid_jan_codes' => $validation['valid_codes'],
            'invalid_jan_codes' => $validation['invalid_codes'],
            'products' => $products,
            'manufacturers' => array_values($manufacturers),
            'product_groups' => array_values($manufacturerGroups),
            'total_jan_count' => count($validation['valid_codes']),
            'total_manufacturer_count' => count($manufacturers),
            'total_product_group_count' => count($manufacturerGroups)
        ];
    }
    
    /**
     * 代表商品選定（品番最小優先）
     */
    protected function selectRepresentativeProduct(array $products): array
    {
        if (empty($products)) {
            throw new SingleProductAnalysisException('代表商品選定対象が見つかりません');
        }
        
        // 優先順位で選定：メーカーコード → 品番 → 品名 → JANコード
        usort($products, function($a, $b) {
            $cmp = strcmp($a['manufacturer_code'], $b['manufacturer_code']);
            if ($cmp !== 0) return $cmp;
            
            $cmp = strcmp($a['product_number'], $b['product_number']);
            if ($cmp !== 0) return $cmp;
            
            $cmp = strcmp($a['product_name'], $b['product_name']);
            if ($cmp !== 0) return $cmp;
            
            return strcmp($a['jan_code'], $b['jan_code']);
        });
        
        $representative = $products[0];
        
        log_message('info', "代表商品選定: {$representative['manufacturer_code']} - {$representative['product_number']} - {$representative['product_name']}");
        
        return $representative;
    }
    
    /**
     * 品出し日情報取得（DC→店舗の最初受入日）
     */
    protected function getTransferInfo(array $janCodes): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            MIN(transfer_date) as first_transfer_date,
            COUNT(DISTINCT transfer_date) as transfer_date_count,
            COUNT(*) as total_transfer_records
        FROM transfer_slip
        WHERE transfer_type = '受入'
          AND source_store_code = '0900'
          AND destination_store_code BETWEEN '0010' AND '0500'
          AND jan_code IN ({$janCodesPlaceholder})
        ";
        
        $result = $this->db->query($sql, $janCodes)->getRowArray();
        
        if (!$result || !$result['first_transfer_date']) {
            log_message('warning', '品出し日が見つかりません。商品登録日を使用します。');
            
            $fallbackSql = "
            SELECT MIN(initial_registration_date) as fallback_date
            FROM products 
            WHERE jan_code IN ({$janCodesPlaceholder})
            ";
            
            $fallbackResult = $this->db->query($fallbackSql, $janCodes)->getRowArray();
            
            return [
                'first_transfer_date' => $fallbackResult['fallback_date'] ?? date('Y-m-d'),
                'transfer_date_count' => 0,
                'total_transfer_records' => 0,
                'is_fallback' => true,
                'fallback_reason' => 'transfer_not_found'
            ];
        }
        
        return [
            'first_transfer_date' => $result['first_transfer_date'],
            'transfer_date_count' => (int)$result['transfer_date_count'],
            'total_transfer_records' => (int)$result['total_transfer_records'],
            'is_fallback' => false
        ];
    }
    
    /**
     * 仕入情報取得
     */
    protected function getPurchaseInfo(array $janCodes, string $baseDate): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        if ($this->costMethod === 'average') {
            $sql = "
            SELECT 
                SUM(purchase_quantity) as total_purchase_qty,
                CASE 
                    WHEN SUM(CASE WHEN purchase_quantity > 0 THEN purchase_quantity ELSE 0 END) > 0 
                    THEN SUM(CASE WHEN purchase_quantity > 0 THEN purchase_quantity * cost_price ELSE 0 END) / 
                         SUM(CASE WHEN purchase_quantity > 0 THEN purchase_quantity ELSE 0 END)
                    ELSE 0 
                END as avg_cost_price,
                SUM(purchase_quantity * cost_price) as total_purchase_cost,
                COUNT(*) as purchase_record_count,
                MIN(purchase_date) as first_purchase_date,
                MAX(purchase_date) as last_purchase_date
            FROM purchase_slip
            WHERE jan_code IN ({$janCodesPlaceholder})
            ";
            $params = $janCodes;
        } else {
            $sql = "
            WITH latest_purchase AS (
                SELECT 
                    cost_price as latest_cost_price,
                    ROW_NUMBER() OVER (ORDER BY purchase_date DESC, input_number DESC, line_number DESC) as rn
                FROM purchase_slip
                WHERE jan_code IN ({$janCodesPlaceholder})
                  AND purchase_quantity > 0
            )
            SELECT 
                SUM(ps.purchase_quantity) as total_purchase_qty,
                (SELECT latest_cost_price FROM latest_purchase WHERE rn = 1) as avg_cost_price,
                SUM(ps.purchase_quantity) * (SELECT latest_cost_price FROM latest_purchase WHERE rn = 1) as total_purchase_cost,
                COUNT(*) as purchase_record_count,
                MIN(ps.purchase_date) as first_purchase_date,
                MAX(ps.purchase_date) as last_purchase_date
            FROM purchase_slip ps
            WHERE ps.jan_code IN ({$janCodesPlaceholder})
            ";
            $params = array_merge($janCodes, $janCodes);
        }
        
        $result = $this->db->query($sql, $params)->getRowArray();
        
        if (!$result || $result['total_purchase_qty'] <= 0) {
            throw new SingleProductAnalysisException('仕入データが見つかりません');
        }
        
        $dateSql = "
        SELECT COUNT(DISTINCT purchase_date) as purchase_date_count
        FROM purchase_slip 
        WHERE jan_code IN ({$janCodesPlaceholder})
        ";
        
        $dateResult = $this->db->query($dateSql, $janCodes)->getRowArray();
        $result['purchase_date_count'] = $dateResult['purchase_date_count'] ?? 0;
        
        $prePurchaseSales = $this->checkPrePurchaseSales($janCodes, $result['first_purchase_date']);
        
        return [
            'total_purchase_qty' => (int)$result['total_purchase_qty'],
            'avg_cost_price' => (float)$result['avg_cost_price'],
            'total_purchase_cost' => (float)$result['total_purchase_cost'],
            'purchase_record_count' => (int)$result['purchase_record_count'],
            'purchase_date_count' => (int)$result['purchase_date_count'],
            'first_purchase_date' => $result['first_purchase_date'],
            'last_purchase_date' => $result['last_purchase_date'],
            'cost_method' => $this->costMethod,
            'pre_purchase_sales' => $prePurchaseSales
        ];
    }

    /**
     * 仕入前売上のチェック
     */
    protected function checkPrePurchaseSales(array $janCodes, string $firstPurchaseDate): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            COUNT(*) as record_count,
            SUM(sales_quantity) as total_quantity,
            SUM(sales_amount) as total_amount
        FROM sales_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND sales_date < ?
          AND sales_quantity > 0
        ";
        
        $params = array_merge($janCodes, [$firstPurchaseDate]);
        $result = $this->db->query($sql, $params)->getRowArray();
        
        return [
            'exists' => (int)$result['record_count'] > 0,
            'record_count' => (int)$result['record_count'],
            'total_quantity' => (int)($result['total_quantity'] ?? 0),
            'total_amount' => (float)($result['total_amount'] ?? 0)
        ];
    }
    
    /**
     * 週別販売データ取得
     */
    protected function getWeeklySales(array $janCodes, string $baseDate): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            sales_date,
            SUM(sales_quantity) as daily_sales_qty,
            SUM(sales_amount) as daily_sales_amount,
            AVG(CASE WHEN sales_quantity > 0 THEN sales_unit_price ELSE NULL END) as avg_sales_price,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN sales_quantity < 0 THEN 1 ELSE 0 END) as return_count,
            SUM(CASE WHEN sales_quantity < 0 THEN sales_quantity ELSE 0 END) as return_qty
        FROM sales_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND sales_date >= ?
        GROUP BY sales_date
        ORDER BY sales_date
        ";
        
        $params = array_merge($janCodes, [$baseDate]);
        $dailyResults = $this->db->query($sql, $params)->getResultArray();
        
        return $this->convertDailyToWeekly($dailyResults, $baseDate);
    }
    
    /**
     * 日別データを週別データに変換
     */
    protected function convertDailyToWeekly(array $dailyResults, string $baseDate): array
    {
        $weeklyData = [];
        $baseTimestamp = strtotime($baseDate);
        
        foreach ($dailyResults as $daily) {
            $saleTimestamp = strtotime($daily['sales_date']);
            $daysDiff = ($saleTimestamp - $baseTimestamp) / 86400;
            $weekNumber = floor($daysDiff / 7) + 1;
            
            if ($weekNumber > $this->maxWeeks || $weekNumber < 1) {
                continue;
            }
            
            if (!isset($weeklyData[$weekNumber])) {
                $weekStart = date('Y-m-d', $baseTimestamp + (($weekNumber - 1) * 7 * 86400));
                $weekEnd = date('Y-m-d', $baseTimestamp + (($weekNumber - 1) * 7 + 6) * 86400);
                
                $weeklyData[$weekNumber] = [
                    'week_number' => $weekNumber,
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                    'weekly_sales_qty' => 0,
                    'weekly_sales_amount' => 0,
                    'avg_sales_price' => 0,
                    'transaction_count' => 0,
                    'return_count' => 0,
                    'return_qty' => 0,
                    'price_total' => 0,
                    'price_qty' => 0
                ];
            }
            
            $weeklyData[$weekNumber]['weekly_sales_qty'] += (int)$daily['daily_sales_qty'];
            $weeklyData[$weekNumber]['weekly_sales_amount'] += (float)$daily['daily_sales_amount'];
            $weeklyData[$weekNumber]['transaction_count'] += (int)$daily['transaction_count'];
            $weeklyData[$weekNumber]['return_count'] += (int)$daily['return_count'];
            $weeklyData[$weekNumber]['return_qty'] += (int)$daily['return_qty'];
            
            if ($daily['avg_sales_price'] > 0 && $daily['daily_sales_qty'] > 0) {
                $weeklyData[$weekNumber]['price_total'] += $daily['avg_sales_price'] * $daily['daily_sales_qty'];
                $weeklyData[$weekNumber]['price_qty'] += $daily['daily_sales_qty'];
            }
        }
        
        foreach ($weeklyData as &$week) {
            if ($week['price_qty'] > 0) {
                $week['avg_sales_price'] = $week['price_total'] / $week['price_qty'];
            }
            unset($week['price_total'], $week['price_qty']);
        }
        
        ksort($weeklyData);
        return array_values($weeklyData);
    }

    /**
     * 週別分析データの計算（累計値、粗利、回収率）
     */
    protected function calculateWeeklyAnalysisExtended(
        array $weeklySales, 
        array $purchaseInfo, 
        string $baseDate,
        array $janCodes
    ): array {
        $analysis = [];
        $cumulativeSales = 0;
        $cumulativeAmount = 0;
        $cumulativeGrossProfit = 0;
        
        $weeklyEvents = $this->getWeeklyEvents($janCodes, $baseDate, count($weeklySales));
        
        foreach ($weeklySales as $index => $week) {
            $cumulativeSales += $week['weekly_sales_qty'];
            $cumulativeAmount += $week['weekly_sales_amount'];
            
            $weeklyGrossProfit = $week['weekly_sales_qty'] * 
                (($week['avg_sales_price'] ?? 0) - $purchaseInfo['avg_cost_price']);
            $cumulativeGrossProfit += $weeklyGrossProfit;
            
            $recoveryRate = $purchaseInfo['total_purchase_cost'] > 0 
                ? ($cumulativeAmount / $purchaseInfo['total_purchase_cost']) * 100 
                : 0;
            
            $daysSinceStart = (strtotime($week['week_end']) - strtotime($baseDate)) / 86400;
            
            $remainingStock = $this->calculateRemainingStockByWeek(
                $purchaseInfo['total_purchase_qty'],
                $cumulativeSales,
                $weeklyEvents,
                $week['week_number']
            );
            
            $weekEvents = $weeklyEvents[$week['week_number']] ?? [];
            
            $analysis[] = [
                'week_number' => (int)$week['week_number'],
                'week_start' => $week['week_start'],
                'week_end' => $week['week_end'],
                'days_since_start' => (int)$daysSinceStart,
                'weekly_sales_qty' => (int)$week['weekly_sales_qty'],
                'weekly_sales_amount' => (float)$week['weekly_sales_amount'],
                'avg_sales_price' => (float)($week['avg_sales_price'] ?? 0),
                'weekly_gross_profit' => (float)$weeklyGrossProfit,
                'cumulative_sales_qty' => (int)$cumulativeSales,
                'cumulative_sales_amount' => (float)$cumulativeAmount,
                'cumulative_gross_profit' => (float)$cumulativeGrossProfit,
                'recovery_rate' => (float)$recoveryRate,
                'transaction_count' => (int)$week['transaction_count'],
                'return_count' => (int)$week['return_count'],
                'return_qty' => (int)($week['return_qty'] ?? 0),
                'has_returns' => (int)$week['return_count'] > 0,
                'remaining_stock' => (int)$remainingStock,
                'purchase_events' => $weekEvents['purchase'] ?? [],
                'adjustment_events' => $weekEvents['adjustment'] ?? [],
                'transfer_events' => $weekEvents['transfer'] ?? []
            ];
        }
        
        return $analysis;
    }
    
    /**
     * 週別の残在庫計算
     */
    protected function calculateRemainingStockByWeek(
        int $totalPurchase,
        int $cumulativeSales,
        array $weeklyEvents,
        int $weekNumber
    ): int {
        $stock = $totalPurchase;
        
        for ($w = 1; $w <= $weekNumber; $w++) {
            if (isset($weeklyEvents[$w]['adjustment'])) {
                foreach ($weeklyEvents[$w]['adjustment'] as $adj) {
                    $stock += $adj['quantity'];
                }
            }
        }
        
        $stock -= $cumulativeSales;
        return max(0, $stock);
    }
    
    /**
     * 週別イベント情報の取得
     */
    protected function getWeeklyEvents(array $janCodes, string $baseDate, int $totalWeeks): array
    {
        $events = [];
        
        $purchaseEvents = $this->getWeeklyPurchaseEvents($janCodes, $baseDate, $totalWeeks);
        $adjustmentEvents = $this->getWeeklyAdjustmentEvents($janCodes, $baseDate, $totalWeeks);
        $transferEvents = $this->getWeeklyTransferEvents($janCodes, $baseDate, $totalWeeks);
        
        for ($week = 1; $week <= $totalWeeks; $week++) {
            $events[$week] = [
                'purchase' => $purchaseEvents[$week] ?? [],
                'adjustment' => $adjustmentEvents[$week] ?? [],
                'transfer' => $transferEvents[$week] ?? []
            ];
        }
        
        return $events;
    }
    
    /**
     * 週別仕入イベント取得
     */
    protected function getWeeklyPurchaseEvents(array $janCodes, string $baseDate, int $totalWeeks): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            purchase_date,
            SUM(purchase_quantity) as total_quantity,
            AVG(cost_price) as avg_cost_price
        FROM purchase_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND purchase_date >= ?
          AND purchase_quantity > 0
        GROUP BY purchase_date
        ORDER BY purchase_date
        ";
        
        $params = array_merge($janCodes, [$baseDate]);
        $results = $this->db->query($sql, $params)->getResultArray();
        
        return $this->mapEventsToWeeks($results, $baseDate, $totalWeeks, 'purchase_date');
    }
    
    /**
     * 週別調整イベント取得
     */
    protected function getWeeklyAdjustmentEvents(array $janCodes, string $baseDate, int $totalWeeks): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            adjustment_date,
            SUM(adjustment_quantity) as total_quantity,
            adjustment_reason_name
        FROM adjustment_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND adjustment_date >= ?
        GROUP BY adjustment_date, adjustment_reason_name
        ORDER BY adjustment_date
        ";
        
        $params = array_merge($janCodes, [$baseDate]);
        $results = $this->db->query($sql, $params)->getResultArray();
        
        return $this->mapEventsToWeeks($results, $baseDate, $totalWeeks, 'adjustment_date');
    }
    
    /**
     * 週別移動イベント取得
     */
    protected function getWeeklyTransferEvents(array $janCodes, string $baseDate, int $totalWeeks): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            transfer_date,
            COUNT(*) as event_count,
            transfer_type
        FROM transfer_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND transfer_date >= ?
        GROUP BY transfer_date, transfer_type
        ORDER BY transfer_date
        ";
        
        $params = array_merge($janCodes, [$baseDate]);
        $results = $this->db->query($sql, $params)->getResultArray();
        
        return $this->mapEventsToWeeks($results, $baseDate, $totalWeeks, 'transfer_date');
    }
    
    /**
     * イベントを週別にマッピング
     */
    protected function mapEventsToWeeks(array $events, string $baseDate, int $totalWeeks, string $dateField): array
    {
        $weeklyEvents = [];
        $baseTimestamp = strtotime($baseDate);
        
        foreach ($events as $event) {
            $eventTimestamp = strtotime($event[$dateField]);
            $daysDiff = ($eventTimestamp - $baseTimestamp) / 86400;
            $weekNumber = floor($daysDiff / 7) + 1;
            
            if ($weekNumber >= 1 && $weekNumber <= $totalWeeks) {
                if (!isset($weeklyEvents[$weekNumber])) {
                    $weeklyEvents[$weekNumber] = [];
                }
                
                $eventData = [
                    'date' => $event[$dateField],
                    'quantity' => isset($event['total_quantity']) ? (int)$event['total_quantity'] : 0,
                    'reason' => $event['adjustment_reason_name'] ?? null,
                    'type' => $event['transfer_type'] ?? null,
                    'avg_cost_price' => isset($event['avg_cost_price']) ? (float)$event['avg_cost_price'] : 0
                ];
                
                $weeklyEvents[$weekNumber][] = $eventData;
            }
        }
        
        return $weeklyEvents;
    }
    
    /**
     * 現在庫計算
     */
    protected function calculateCurrentStock(array $janCodes, array $purchaseInfo, array $weeklyAnalysis): array
    {
        $totalSales = 0;
        if (!empty($weeklyAnalysis)) {
            $lastWeek = end($weeklyAnalysis);
            $totalSales = $lastWeek['cumulative_sales_qty'];
        }
        
        $adjustmentQty = $this->getAdjustmentQuantity($janCodes);
        
        $currentStock = $purchaseInfo['total_purchase_qty'] - $totalSales - $adjustmentQty['total_adjustment'];
        $currentStockValue = $currentStock * $purchaseInfo['avg_cost_price'];
        
        return [
            'current_stock_qty' => (int)$currentStock,
            'current_stock_value' => (float)$currentStockValue,
            'total_adjustment_qty' => (int)$adjustmentQty['total_adjustment'],
            'adjustment_records' => (int)$adjustmentQty['record_count']
        ];
    }
    
    /**
     * 調整数量取得
     */
    protected function getAdjustmentQuantity(array $janCodes): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            SUM(adjustment_quantity) as total_adjustment,
            COUNT(*) as record_count
        FROM adjustment_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
        ";
        
        $result = $this->db->query($sql, $janCodes)->getRowArray();
        
        return [
            'total_adjustment' => (int)($result['total_adjustment'] ?? 0),
            'record_count' => (int)($result['record_count'] ?? 0)
        ];
    }

    /**
     * 推奨アクション生成
     */
    protected function generateRecommendation(array $weeklyAnalysis, array $currentStock, array $basicInfo): array
    {
        if (empty($weeklyAnalysis)) {
            return [
                'status' => 'no_data',
                'message' => '販売データが不足しています',
                'action' => '販売実績の蓄積をお待ちください',
                'disposal_possible' => false,
                'recovery_achieved' => false,
                'total_weeks' => 0,
                'days_to_disposal' => null,
                'current_stock_qty' => $currentStock['current_stock_qty'],
                'recovery_rate' => 0
            ];
        }
        
        $lastWeek = end($weeklyAnalysis);
        $totalWeeks = count($weeklyAnalysis);
        
        $disposalPossible = $lastWeek['cumulative_gross_profit'] > 0;
        $recoveryAchieved = $lastWeek['recovery_rate'] >= 100;
        
        // 廃盤予定日の取得（代表商品から）
        $daysToDisposal = null;
        $representative = $basicInfo['representative_product'];
        if ($representative['deletion_scheduled_date']) {
            $disposalDate = strtotime($representative['deletion_scheduled_date']);
            $daysToDisposal = ($disposalDate - time()) / 86400;
        }
        
        if ($disposalPossible && $recoveryAchieved) {
            $status = 'disposal_possible';
            $message = '処分実行可能';
            $action = $currentStock['current_stock_qty'] > 0 
                ? '残在庫の早期処分を推奨します（売上金額で原価回収済み）' 
                : '販売完了済み';
        } elseif ($recoveryAchieved) {
            $status = 'recovery_achieved';
            $message = '売上金額による原価回収達成';
            $action = '値引き販売を検討できます';
        } elseif ($totalWeeks >= 8) {
            $status = 'disposal_consideration';
            $message = '処分検討';
            $action = '大幅値引きまたは処分を検討してください';
        } elseif ($totalWeeks >= 4) {
            $status = 'discount_recommended';
            $message = '値引き推奨';
            $action = '段階的な値引きを検討してください';
        } else {
            $status = 'continue_selling';
            $message = '定価維持';
            $action = '継続販売を推奨します';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'action' => $action,
            'disposal_possible' => $disposalPossible,
            'recovery_achieved' => $recoveryAchieved,
            'total_weeks' => $totalWeeks,
            'days_to_disposal' => $daysToDisposal,
            'current_stock_qty' => $currentStock['current_stock_qty'],
            'recovery_rate' => $lastWeek['recovery_rate']
        ];
    }    

    /**
     * 警告情報の収集
     */
    protected function collectWarnings(array $transferInfo, array $purchaseInfo, array $weeklyAnalysis, array $basicInfo): array
    {
        $warnings = [];
        
        // 品出し日関連の警告
        if ($transferInfo['is_fallback']) {
            $warnings[] = [
                'type' => 'no_transfer_date',
                'level' => 'warning',
                'message' => '品出し日が特定できません。商品登録日を使用しています。',
                'icon' => 'bi-exclamation-triangle'
            ];
        }
        
        // 仕入前売上の警告
        if ($purchaseInfo['pre_purchase_sales']['exists']) {
            $warnings[] = [
                'type' => 'sales_before_purchase',
                'level' => 'info',
                'message' => "仕入前売上が検出されました（{$purchaseInfo['pre_purchase_sales']['total_quantity']}個）。前期在庫として処理しています。",
                'icon' => 'bi-info-circle'
            ];
        }
        
        // 返品関連の警告
        $totalReturns = array_sum(array_column($weeklyAnalysis, 'return_qty'));
        if ($totalReturns < 0) {
            $warnings[] = [
                'type' => 'return_detected',
                'level' => 'warning',
                'message' => "返品が発生しています（" . abs($totalReturns) . "個）。詳細は週別履歴をご確認ください。",
                'icon' => 'bi-arrow-counterclockwise'
            ];
        }
        
        // 複数メーカー混在の警告
        if ($basicInfo['total_manufacturer_count'] > 1) {
            $warnings[] = [
                'type' => 'multiple_manufacturers',
                'level' => 'info',
                'message' => "複数メーカーの商品が含まれています（{$basicInfo['total_manufacturer_count']}社）。メーカー別分析も検討してください。",
                'icon' => 'bi-building'
            ];
        }
        
        // 複数商品グループの警告
        if ($basicInfo['total_product_group_count'] > 1) {
            $warnings[] = [
                'type' => 'multiple_product_groups',
                'level' => 'info',
                'message' => "複数の商品グループが含まれています（{$basicInfo['total_product_group_count']}グループ）。商品別分析も検討してください。",
                'icon' => 'bi-box-seam'
            ];
        }
        
        // 無効JANコードの警告
        if (!empty($basicInfo['invalid_jan_codes'])) {
            $warnings[] = [
                'type' => 'invalid_jan_codes',
                'level' => 'warning',
                'message' => count($basicInfo['invalid_jan_codes']) . "個のJANコードが見つかりませんでした。",
                'icon' => 'bi-exclamation-triangle'
            ];
        }
        
        return $warnings;
    }
    
    /**
     * 伝票詳細情報の取得
     */
    protected function getSlipDetails(array $janCodes): array
    {
        return [
            'purchase_slips' => $this->getPurchaseSlipDetails($janCodes),
            'adjustment_slips' => $this->getAdjustmentSlipDetails($janCodes),
            'transfer_slips' => $this->getTransferSlipDetails($janCodes)
        ];
    }
    
    /**
     * 仕入伝票詳細取得
     */
    protected function getPurchaseSlipDetails(array $janCodes): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            purchase_date,
            slip_number,
            store_name,
            supplier_name,
            SUM(purchase_quantity) as total_quantity,
            AVG(cost_price) as avg_cost_price,
            SUM(purchase_amount) as total_amount,
            MAX(CASE WHEN purchase_quantity > 0 THEN '仕入' ELSE '返品' END) as slip_type
        FROM purchase_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
        GROUP BY purchase_date, input_number, slip_number, store_name, supplier_name
        ORDER BY purchase_date, input_number, slip_number
        ";
        
        $results = $this->db->query($sql, $janCodes)->getResultArray();
        
        foreach ($results as &$row) {
            $row['total_quantity'] = (int)$row['total_quantity'];
            $row['avg_cost_price'] = (float)$row['avg_cost_price'];
            $row['total_amount'] = (float)$row['total_amount'];
            $row['slip_number'] = (int)$row['slip_number'];
        }
        
        return $results;
    }
    
    /**
     * 調整伝票詳細取得
     */
    protected function getAdjustmentSlipDetails(array $janCodes): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            adjustment_date,
            slip_number,
            store_name,
            adjustment_type,
            SUM(adjustment_quantity) as total_quantity,
            adjustment_reason_name,
            staff_name
        FROM adjustment_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
        GROUP BY adjustment_date, input_number, slip_number, store_name, adjustment_type, adjustment_reason_name, staff_name
        ORDER BY adjustment_date, input_number, slip_number
        ";
        
        $results = $this->db->query($sql, $janCodes)->getResultArray();
        
        foreach ($results as &$row) {
            $row['total_quantity'] = (int)$row['total_quantity'];
            $row['slip_number'] = (int)$row['slip_number'];
        }
        
        return $results;
    }
    
    /**
     * 移動伝票詳細取得
     */
    protected function getTransferSlipDetails(array $janCodes): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        $sql = "
        SELECT 
            transfer_date,
            slip_number,
            transfer_type,
            source_store_code,
            source_store_name,
            destination_store_code,
            destination_store_name,
            SUM(transfer_quantity) as total_quantity
        FROM transfer_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
        GROUP BY transfer_date, input_number, slip_number, transfer_type, source_store_code, source_store_name, destination_store_code, destination_store_name
        ORDER BY transfer_date, input_number, slip_number
        ";
        
        $results = $this->db->query($sql, $janCodes)->getResultArray();
        
        foreach ($results as &$row) {
            $row['total_quantity'] = (int)$row['total_quantity'];
            $row['slip_number'] = (int)$row['slip_number'];
            $row['source_store_name'] = $row['source_store_name'] ?: '-';
            $row['destination_store_name'] = $row['destination_store_name'] ?: '-';
            
            $row['is_initial_delivery'] = ($row['transfer_type'] === '受入' 
                && $row['source_store_code'] === '0900' 
                && $row['destination_store_code'] >= '0010' 
                && $row['destination_store_code'] <= '0500');
        }
        
        return $results;
    }
    
    /**
     * 原価計算方式を設定
     */
    public function setCostMethod(string $method): self
    {
        if (!in_array($method, ['average', 'latest'])) {
            throw new \InvalidArgumentException('原価計算方式は average または latest を指定してください');
        }
        
        $this->costMethod = $method;
        return $this;
    }
    
    /**
     * 最大集計週数を設定
     */
    public function setMaxWeeks(int $weeks): self
    {
        if ($weeks < 1) {
            throw new \InvalidArgumentException('最大週数は1以上を指定してください');
        }
        
        $this->maxWeeks = $weeks;
        return $this;
    }
    
    /**
     * 処理タイムアウトを設定
     */
    public function setTimeout(int $seconds): self
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException('タイムアウトは1秒以上を指定してください');
        }
        
        $this->timeout = $seconds;
        return $this;
    }
}

/**
 * 単品分析専用例外クラス
 */
class SingleProductAnalysisException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        log_message('error', "SingleProductAnalysisException: {$message}");
    }
}