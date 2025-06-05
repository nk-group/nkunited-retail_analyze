<?php

namespace App\Libraries;

use App\Models\ProductModel;
use App\Models\ManufacturerModel;
use CodeIgniter\Database\ConnectionInterface;

/**
 * 単品販売分析サービス
 * 
 * 指定された商品グループの販売分析を実行し、週別販売推移、原価回収率、
 * 在庫処分判定などを提供する。品出し日を基準とした週別集計により、
 * 商品の販売状況を詳細に分析し、値引きや処分のタイミングを判定する。
 * 
 * @package App\Libraries
 * @author  NK Inter y.ueda
 * @version 1.0
 */
class SingleProductAnalysisService
{
    /**
     * データベース接続インスタンス
     * 
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;
    
    /**
     * 商品モデルインスタンス
     * 
     * @var ProductModel
     */
    protected $productModel;
    
    /**
     * メーカーモデルインスタンス
     * 
     * @var ManufacturerModel
     */
    protected $manufacturerModel;
    
    /**
     * 原価計算方式
     * 
     * @var string 'average'（平均原価法）または 'latest'（最終仕入原価法）
     */
    protected $costMethod = 'average';
    
    /**
     * 最大集計週数制限
     * 
     * @var int パフォーマンス保護のための週数上限
     */
    protected $maxWeeks = 50;
    
    /**
     * 処理タイムアウト（秒）
     * 
     * @var int 長時間処理の防止
     */
    protected $timeout = 30;
    
    /**
     * コンストラクタ
     * 
     * データベース接続とモデルインスタンスを初期化する。
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->productModel = new ProductModel();
        $this->manufacturerModel = new ManufacturerModel();
    }
    
    /**
     * 単品分析を実行
     * 
     * 指定された分析条件に基づいて商品の販売分析を実行する。
     * 処理の流れ：基本情報取得→品出し日特定→仕入情報集計→週別販売集計
     * →累計計算→現在庫算出→推奨アクション判定→警告情報収集
     * 
     * @param array $conditions 分析条件配列
     *                         - manufacturer_code: メーカーコード
     *                         - product_number: 品番
     *                         - product_name: 品番名
     * @return array 分析結果配列
     *               - basic_info: 基本情報（商品・メーカー・JANコード）
     *               - transfer_info: 品出し日情報
     *               - purchase_info: 仕入情報
     *               - weekly_analysis: 週別分析データ
     *               - current_stock: 現在庫情報
     *               - recommendation: 推奨アクション
     *               - warnings: 警告情報
     *               - slip_details: 伝票詳細情報
     *               - execution_time: 実行時間
     *               - analysis_date: 分析実行日時
     * @throws SingleProductAnalysisException 分析処理中にエラーが発生した場合
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
            $weeklyAnalysis = $this->calculateWeeklyAnalysisExtended(
                $weeklySales, 
                $purchaseInfo, 
                $transferInfo['first_transfer_date'],
                $basicInfo['jan_codes']
            );
            
            // 6. 現在庫計算
            $currentStock = $this->calculateCurrentStock($basicInfo['jan_codes'], $purchaseInfo, $weeklyAnalysis);
            
            // 7. 処分判定・推奨アクション
            $recommendation = $this->generateRecommendation($weeklyAnalysis, $currentStock, $basicInfo);
            
            // 8. 警告情報の収集
            $warnings = $this->collectWarnings($transferInfo, $purchaseInfo, $weeklyAnalysis);
            
            // 9. 伝票詳細情報の取得
            $slipDetails = $this->getSlipDetails($basicInfo['jan_codes']);
            
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
                'slip_details' => $slipDetails,
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
     * 
     * 分析対象商品の基本情報を取得し、存在確認を行う。
     * メーカー情報、商品基本情報、JANコード一覧を取得して
     * 後続処理で使用する基本データを準備する。
     * 
     * @param array $conditions 分析条件配列
     * @return array 基本情報配列
     *               - conditions: 入力条件
     *               - manufacturer: メーカー情報
     *               - product_info: 商品基本情報
     *               - jan_codes: JANコード配列
     *               - jan_details: JANコード詳細情報
     *               - total_sku_count: SKU総数
     * @throws SingleProductAnalysisException メーカーまたは商品が見つからない場合
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
     * 
     * 配送センター（DC）から店舗への最初の受入日を品出し日として特定する。
     * 品出し日が見つからない場合は商品登録日を代替として使用し、
     * 代替使用フラグを設定して後続処理で警告表示を行う。
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 品出し日情報配列
     *               - first_transfer_date: 品出し日
     *               - transfer_date_count: 移動日数
     *               - total_transfer_records: 移動レコード総数
     *               - is_fallback: 代替日使用フラグ
     *               - fallback_reason: 代替理由（代替使用時のみ）
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
            log_message('warning', '品出し日が見つかりません。商品登録日を使用します。');
            
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
     * 
     * 指定されたJANコード群の仕入情報を集計し、原価計算方式に応じて
     * 平均原価または最終仕入原価を用いて総仕入原価を算出する。
     * SQL Serverの制約を回避するため、purchase_date_countは別クエリで取得。
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日（品出し日）
     * @return array 仕入情報配列
     *               - total_purchase_qty: 総仕入数量
     *               - avg_cost_price: 平均原価
     *               - total_purchase_cost: 総仕入金額
     *               - purchase_record_count: 仕入レコード数
     *               - purchase_date_count: 仕入日数
     *               - first_purchase_date: 最初仕入日
     *               - last_purchase_date: 最終仕入日
     *               - cost_method: 原価計算方式
     *               - pre_purchase_sales: 仕入前売上情報
     * @throws SingleProductAnalysisException 仕入データが見つからない場合
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
        
        log_message('info', 'getPurchaseInfo SQL: ' . $sql);
        log_message('info', 'getPurchaseInfo パラメータ: ' . json_encode($params));
        
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
     * 
     * 最初仕入日より前に発生した売上を検出する。
     * 前期在庫からの売上や期またぎ処理の確認に使用。
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $firstPurchaseDate 最初仕入日
     * @return array 仕入前売上情報配列
     *               - exists: 仕入前売上存在フラグ
     *               - record_count: レコード数
     *               - total_quantity: 総販売数量
     *               - total_amount: 総売上金額
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
     * 
     * 指定されたJANコード群の販売データを基準日から週別に集計する。
     * SQL ServerのDATEADD関数の複雑さを回避するため、日別データを取得してから
     * PHPで週別集計に変換する方式を採用。
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日（品出し日）
     * @return array 週別販売データ配列（週番号、期間、販売数量、売上金額、平均売価等）
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
        
        log_message('info', 'getWeeklySales SQL: ' . $sql);
        log_message('info', 'getWeeklySales パラメータ数: ' . count($params));
        
        $dailyResults = $this->db->query($sql, $params)->getResultArray();
        
        return $this->convertDailyToWeekly($dailyResults, $baseDate);
    }
    
    /**
     * 日別データを週別データに変換
     * 
     * 日別の販売データを基準日からの経過日数に基づいて週別に集計する。
     * 週は基準日を起点とした7日間単位で計算し、週番号は1から開始。
     * 各週の平均売価は販売数量による加重平均で算出。
     * 
     * @param array $dailyResults 日別販売データ配列
     * @param string $baseDate 基準日（週番号計算の起点）
     * @return array 週別集計データ配列
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
        
        if (count($weeklyData) > $this->maxWeeks) {
            log_message('warning', "週数が上限を超過: " . count($weeklyData) . "週");
        }
        
        ksort($weeklyData);
        
        return array_values($weeklyData);
    }

    /**
     * 週別分析データの計算（累計値、粗利、回収率）
     * 
     * 週別販売データから累計値、週別粗利、累計粗利、原価回収率を算出する。
     * 各週の粗利は (平均売価 - 平均原価) × 販売数量 で計算。
     * 回収率は累計売上金額 ÷ 総仕入金額 × 100 で算出（売上金額ベース）。
     * 
     * @param array $weeklySales 週別販売データ配列
     * @param array $purchaseInfo 仕入情報配列
     * @param string $baseDate 基準日（品出し日）
     * @param array $janCodes 対象JANコード配列
     * @return array 週別分析データ配列
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
     * 
     * 指定週時点での残在庫数を計算する。
     * 計算式：総仕入数量 + 調整数量累計 - 累計販売数量
     * 
     * @param int $totalPurchase 総仕入数量
     * @param int $cumulativeSales 累計販売数量（指定週まで）
     * @param array $weeklyEvents 週別イベント情報
     * @param int $weekNumber 計算対象週番号
     * @return int 残在庫数
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
     * 
     * 仕入・調整・移動の各イベントを週別に分類して取得する。
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日
     * @param int $totalWeeks 総週数
     * @return array 週別イベント情報配列
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日
     * @param int $totalWeeks 総週数
     * @return array 週別仕入イベント配列
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日
     * @param int $totalWeeks 総週数
     * @return array 週別調整イベント配列
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @param string $baseDate 基準日
     * @param int $totalWeeks 総週数
     * @return array 週別移動イベント配列
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
     * 
     * @param array $events イベントデータ配列
     * @param string $baseDate 基準日
     * @param int $totalWeeks 総週数
     * @param string $dateField 日付フィールド名
     * @return array 週別イベント配列
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
     * 伝票詳細情報の取得
     * 
     * 分析対象商品の仕入・調整・移動伝票の詳細情報を取得する。
     * 結果画面での詳細表示に使用。
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 伝票詳細情報配列
     *               - purchase_slips: 仕入伝票詳細
     *               - adjustment_slips: 調整伝票詳細
     *               - transfer_slips: 移動伝票詳細
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 仕入伝票詳細配列
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 調整伝票詳細配列
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
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 移動伝票詳細配列
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
     * 現在庫計算
     * 
     * 在庫移動式による現在庫数量と在庫金額を算出する。
     * 計算式：現在庫 = 総仕入数量 - 累計販売数量 - 調整数量
     * 在庫金額は現在庫数量 × 平均原価で算出。
     * 
     * @param array $janCodes 対象JANコード配列
     * @param array $purchaseInfo 仕入情報配列
     * @param array $weeklyAnalysis 週別分析データ配列
     * @return array 現在庫情報配列
     *               - current_stock_qty: 現在庫数量
     *               - current_stock_value: 現在庫金額
     *               - total_adjustment_qty: 調整数量合計
     *               - adjustment_records: 調整レコード数
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
     * 
     * 在庫調整伝票から対象商品の調整数量合計を取得する。
     * 正数は在庫増加、負数は在庫減少を表す。
     * 
     * @param array $janCodes 対象JANコード配列
     * @return array 調整数量情報配列
     *               - total_adjustment: 調整数量合計
     *               - record_count: 調整レコード数
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
     * 
     * 週別分析結果と現在庫状況に基づいて処分判定と推奨アクションを決定する。
     * 判定基準（売上金額ベース回収率）：
     * - 処分可能：粗利黒字 かつ 売上金額回収率100%以上
     * - 原価回収達成：売上金額回収率100%以上
     * - 処分検討：8週経過
     * - 値引き推奨：4週経過
     * - 定価維持：上記以外
     * 
     * @param array $weeklyAnalysis 週別分析データ配列
     * @param array $currentStock 現在庫情報配列
     * @param array $basicInfo 基本情報配列
     * @return array 推奨アクション配列
     *               - status: ステータスコード
     *               - message: 判定メッセージ
     *               - action: 推奨アクション
     *               - disposal_possible: 処分可能フラグ
     *               - recovery_achieved: 回収達成フラグ
     *               - total_weeks: 総週数
     *               - days_to_disposal: 廃盤までの日数
     *               - current_stock_qty: 現在庫数量
     *               - recovery_rate: 回収率（売上金額ベース）
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
        
        $daysToDisposal = null;
        if ($basicInfo['product_info']['deletion_scheduled_date']) {
            $disposalDate = strtotime($basicInfo['product_info']['deletion_scheduled_date']);
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
     * 
     * 分析過程で検出された注意事項や異常値を警告として収集する。
     * 警告種類：
     * - 品出し日未特定：移動データ不足による代替日使用
     * - 仕入前売上：前期在庫からの売上検出
     * - 返品発生：マイナス売上の検出
     * 
     * @param array $transferInfo 品出し日情報配列
     * @param array $purchaseInfo 仕入情報配列
     * @param array $weeklyAnalysis 週別分析データ配列
     * @return array 警告情報配列
     *               各要素：
     *               - type: 警告タイプ
     *               - level: 警告レベル（info/warning/error）
     *               - message: 警告メッセージ
     *               - icon: 表示用アイコンクラス
     */
    protected function collectWarnings(array $transferInfo, array $purchaseInfo, array $weeklyAnalysis): array
    {
        $warnings = [];
        
        if ($transferInfo['is_fallback']) {
            $warnings[] = [
                'type' => 'no_transfer_date',
                'level' => 'warning',
                'message' => '品出し日が特定できません。商品登録日を使用しています。',
                'icon' => 'bi-exclamation-triangle'
            ];
        }
        
        if ($purchaseInfo['pre_purchase_sales']['exists']) {
            $warnings[] = [
                'type' => 'sales_before_purchase',
                'level' => 'info',
                'message' => "仕入前売上が検出されました（{$purchaseInfo['pre_purchase_sales']['total_quantity']}個）。前期在庫として処理しています。",
                'icon' => 'bi-info-circle'
            ];
        }
        
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
     * 
     * 仕入原価の計算方式を変更する。
     * - average: 平均原価法（デフォルト）
     * - latest: 最終仕入原価法
     * 
     * @param string $method 原価計算方式
     * @return self チェーンメソッド用
     * @throws \InvalidArgumentException 不正な計算方式が指定された場合
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
     * 
     * パフォーマンス保護のための週数上限を変更する。
     * 
     * @param int $weeks 最大週数（1以上）
     * @return self チェーンメソッド用
     * @throws \InvalidArgumentException 不正な週数が指定された場合
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
     * 
     * 長時間処理防止のためのタイムアウト値を変更する。
     * 
     * @param int $seconds タイムアウト秒数（1以上）
     * @return self チェーンメソッド用
     * @throws \InvalidArgumentException 不正な秒数が指定された場合
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
 * 
 * 単品販売分析処理で発生する業務例外を管理する。
 * 発生時に自動的にログ出力を行い、エラー追跡を支援する。
 * 
 * @package App\Libraries
 */
class SingleProductAnalysisException extends \Exception
{
    /**
     * コンストラクタ
     * 
     * 例外生成時に自動的にエラーログを出力する。
     * 
     * @param string $message エラーメッセージ
     * @param int $code エラーコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        log_message('error', "SingleProductAnalysisException: {$message}");
    }
}