<?php

namespace App\Libraries;

use App\Models\ProductModel;
use App\Models\ManufacturerModel;
use CodeIgniter\Database\ConnectionInterface;

/**
 * 単品販売分析サービス
 * 
 * 指定された商品グループの販売分析を実行し、
 * 週別販売推移、原価回収率、在庫処分判定などを提供する
 */
class SingleProductAnalysisService
{
    protected $db;
    protected $productModel;
    protected $manufacturerModel;
    
    // 設定値
    protected $costMethod = 'average';  // 'average' | 'latest'
    protected $maxWeeks = 50;           // 最大集計週数
    protected $timeout = 30;            // 処理タイムアウト（秒）
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->productModel = new ProductModel();
        $this->manufacturerModel = new ManufacturerModel();
    }
    
    /**
     * 単品分析を実行
     * 
     * @param array $conditions 分析条件
     * @return array 分析結果
     * @throws SingleProductAnalysisException
     */
    public function executeAnalysis(array $conditions): array
    {
        $startTime = microtime(true);
        
        try {
            log_message('info', '単品分析開始: ' . json_encode($conditions));
            
            // 1. 基本情報取得・検証
            $basicInfo = $this->getBasicInfo($conditions);
            
            // 2. 品出し日取得
            $transferInfo = $this->getTransferInfo($basicInfo['jan_codes']);
            
            // 3. 仕入情報取得
            $purchaseInfo = $this->getPurchaseInfo($basicInfo['jan_codes'], $transferInfo['first_transfer_date']);
            
            // 4. 週別販売データ取得
            $weeklySales = $this->getWeeklySales($basicInfo['jan_codes'], $transferInfo['first_transfer_date']);
            
            // 5. 週別データの統合・計算
            $weeklyAnalysis = $this->calculateWeeklyAnalysis($weeklySales, $purchaseInfo, $transferInfo['first_transfer_date']);
            
            // 6. 現在庫計算
            $currentStock = $this->calculateCurrentStock($basicInfo['jan_codes'], $purchaseInfo, $weeklyAnalysis);
            
            // 7. 処分判定・推奨アクション
            $recommendation = $this->generateRecommendation($weeklyAnalysis, $currentStock, $basicInfo);
            
            // 8. 警告情報の収集
            $warnings = $this->collectWarnings($transferInfo, $purchaseInfo, $weeklyAnalysis);
            
            $executionTime = microtime(true) - $startTime;
            log_message('info', "単品分析完了: {$executionTime}秒");
            
            return [
                'basic_info' => $basicInfo,
                'transfer_info' => $transferInfo,
                'purchase_info' => $purchaseInfo,
                'weekly_analysis' => $weeklyAnalysis,
                'current_stock' => $currentStock,
                'recommendation' => $recommendation,
                'warnings' => $warnings,
                'execution_time' => $executionTime,
                'analysis_date' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            log_message('error', '単品分析エラー: ' . $e->getMessage());
            throw new SingleProductAnalysisException(
                '分析処理中にエラーが発生しました: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * 基本情報取得（商品情報、JANコード一覧、メーカー情報）
     */
    protected function getBasicInfo(array $conditions): array
    {
        // メーカー情報取得
        $manufacturer = $this->manufacturerModel->find($conditions['manufacturer_code']);
        if (!$manufacturer) {
            throw new SingleProductAnalysisException('指定されたメーカーが見つかりません');
        }
        
        // 商品基本情報取得
        $productInfo = $this->productModel->getProductBasicInfo(
            $conditions['manufacturer_code'],
            $conditions['product_number'],
            $conditions['product_name']
        );
        
        if (!$productInfo) {
            throw new SingleProductAnalysisException('指定された商品が見つかりません');
        }
        
        // JANコード一覧取得
        $janCodes = $this->productModel->getJanCodesByGroup(
            $conditions['manufacturer_code'],
            $conditions['product_number'],
            $conditions['product_name']
        );
        
        if (empty($janCodes)) {
            throw new SingleProductAnalysisException('商品のJANコードが見つかりません');
        }
        
        return [
            'conditions' => $conditions,
            'manufacturer' => $manufacturer,
            'product_info' => $productInfo,
            'jan_codes' => array_column($janCodes, 'jan_code'),
            'jan_details' => $janCodes,
            'total_sku_count' => count($janCodes)
        ];
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
        
        log_message('info', 'getTransferInfo SQL: ' . $sql);
        log_message('info', 'getTransferInfo パラメータ: ' . json_encode($janCodes));
        
        $result = $this->db->query($sql, $janCodes)->getRowArray();
        
        if (!$result || !$result['first_transfer_date']) {
            // 品出し日が見つからない場合の代替処理
            log_message('warning', '品出し日が見つかりません。商品登録日を使用します。');
            
            // 商品登録日を代替として使用
            $fallbackSql = "
            SELECT MIN(initial_registration_date) as fallback_date
            FROM products 
            WHERE jan_code IN ({$janCodesPlaceholder})
            ";
            
            log_message('info', 'getTransferInfo Fallback SQL: ' . $fallbackSql);
            
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
     * 仕入情報取得（総仕入数量、平均原価、総仕入金額）
     */
    protected function getPurchaseInfo(array $janCodes, string $baseDate): array
    {
        $janCodesPlaceholder = str_repeat('?,', count($janCodes) - 1) . '?';
        
        if ($this->costMethod === 'average') {
            // 平均原価法
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
                (SELECT COUNT(DISTINCT purchase_date) FROM purchase_slip p2 WHERE p2.jan_code IN ({$janCodesPlaceholder})) as purchase_date_count,
                MIN(purchase_date) as first_purchase_date,
                MAX(purchase_date) as last_purchase_date
            FROM purchase_slip
            WHERE jan_code IN ({$janCodesPlaceholder})
            ";
            $params = array_merge($janCodes, $janCodes);
        } else {
            // 最終仕入原価法
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
                (SELECT COUNT(DISTINCT purchase_date) FROM purchase_slip p2 WHERE p2.jan_code IN ({$janCodesPlaceholder})) as purchase_date_count,
                MIN(ps.purchase_date) as first_purchase_date,
                MAX(ps.purchase_date) as last_purchase_date
            FROM purchase_slip ps
            WHERE ps.jan_code IN ({$janCodesPlaceholder})
            ";
            $params = array_merge($janCodes, $janCodes, $janCodes);
        }
        
        log_message('info', 'getPurchaseInfo SQL: ' . $sql);
        log_message('info', 'getPurchaseInfo パラメータ: ' . json_encode($params));
        
        $result = $this->db->query($sql, $params)->getRowArray();
        
        if (!$result || $result['total_purchase_qty'] <= 0) {
            throw new SingleProductAnalysisException('仕入データが見つかりません');
        }
        
        // 仕入前売上のチェック
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
            FLOOR(DATEDIFF(day, ?, sales_date) / 7) + 1 as week_number,
            DATEADD(day, (FLOOR(DATEDIFF(day, ?, sales_date) / 7) * 7), ?) as week_start,
            DATEADD(day, (FLOOR(DATEDIFF(day, ?, sales_date) / 7) * 7 + 6), ?) as week_end,
            SUM(sales_quantity) as weekly_sales_qty,
            SUM(sales_amount) as weekly_sales_amount,
            AVG(CASE WHEN sales_quantity > 0 THEN sales_unit_price ELSE NULL END) as avg_sales_price,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN sales_quantity < 0 THEN 1 ELSE 0 END) as return_count,
            SUM(CASE WHEN sales_quantity < 0 THEN sales_quantity ELSE 0 END) as return_qty
        FROM sales_slip
        WHERE jan_code IN ({$janCodesPlaceholder})
          AND sales_date >= ?
        GROUP BY FLOOR(DATEDIFF(day, ?, sales_date) / 7) + 1,
                 DATEADD(day, (FLOOR(DATEDIFF(day, ?, sales_date) / 7) * 7), ?),
                 DATEADD(day, (FLOOR(DATEDIFF(day, ?, sales_date) / 7) * 7 + 6), ?)
        HAVING FLOOR(DATEDIFF(day, ?, sales_date) / 7) + 1 <= ?
        ORDER BY FLOOR(DATEDIFF(day, ?, sales_date) / 7) + 1
        ";
        
        $params = array_merge(
            [$baseDate, $baseDate, $baseDate, $baseDate, $baseDate], // SELECT部用
            $janCodes,
            [$baseDate, $baseDate, $baseDate, $baseDate, $baseDate, $baseDate, $this->maxWeeks, $baseDate] // GROUP BY, HAVING, ORDER BY用
        );
        
        $result = $this->db->query($sql, $params)->getResultArray();
        
        // 週数チェック
        if (count($result) > $this->maxWeeks) {
            log_message('warning', "週数が上限を超過: " . count($result) . "週");
        }
        
        return $result;
    }
    
    /**
     * 週別分析データの計算（累計値、粗利、回収率）
     */
    protected function calculateWeeklyAnalysis(array $weeklySales, array $purchaseInfo, string $baseDate): array
    {
        $analysis = [];
        $cumulativeSales = 0;
        $cumulativeAmount = 0;
        $cumulativeGrossProfit = 0;
        
        foreach ($weeklySales as $week) {
            // 累計計算
            $cumulativeSales += $week['weekly_sales_qty'];
            $cumulativeAmount += $week['weekly_sales_amount'];
            
            // 週別粗利計算
            $weeklyGrossProfit = $week['weekly_sales_qty'] * 
                (($week['avg_sales_price'] ?? 0) - $purchaseInfo['avg_cost_price']);
            $cumulativeGrossProfit += $weeklyGrossProfit;
            
            // 累計回収率計算
            $recoveryRate = $purchaseInfo['total_purchase_cost'] > 0 
                ? ($cumulativeGrossProfit / $purchaseInfo['total_purchase_cost']) * 100 
                : 0;
            
            // 経過日数計算
            $daysSinceStart = (strtotime($week['week_end']) - strtotime($baseDate)) / 86400;
            
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
                'has_returns' => (int)$week['return_count'] > 0
            ];
        }
        
        return $analysis;
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
        
        // 調整数量の取得
        $adjustmentQty = $this->getAdjustmentQuantity($janCodes);
        
        // 現在庫 = 仕入数 - 売上数 - 調整数
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
                'action' => '販売実績の蓄積をお待ちください'
            ];
        }
        
        $lastWeek = end($weeklyAnalysis);
        $totalWeeks = count($weeklyAnalysis);
        
        // 処分判定
        $disposalPossible = $lastWeek['cumulative_gross_profit'] > 0;
        $recoveryAchieved = $lastWeek['recovery_rate'] >= 100;
        
        // 廃盤までの日数
        $daysToDisposal = null;
        if ($basicInfo['product_info']['deletion_scheduled_date']) {
            $disposalDate = strtotime($basicInfo['product_info']['deletion_scheduled_date']);
            $daysToDisposal = ($disposalDate - time()) / 86400;
        }
        
        // 推奨アクション決定
        if ($disposalPossible && $recoveryAchieved) {
            $status = 'disposal_possible';
            $message = '処分実行可能';
            $action = $currentStock['current_stock_qty'] > 0 
                ? '残在庫の早期処分を推奨します' 
                : '販売完了済み';
        } elseif ($recoveryAchieved) {
            $status = 'recovery_achieved';
            $message = '原価回収達成';
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
    protected function collectWarnings(array $transferInfo, array $purchaseInfo, array $weeklyAnalysis): array
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
        
        // 返品の警告
        $totalReturns = array_sum(array_column($weeklyAnalysis, 'return_qty'));
        if ($totalReturns < 0) {
            $warnings[] = [
                'type' => 'return_detected',
                'level' => 'warning',
                'message' => "返品が発生しています（" . abs($totalReturns) . "個）。詳細は週別履歴をご確認ください。",
                'icon' => 'bi-arrow-counterclockwise'
            ];
        }
        
        return $warnings;
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