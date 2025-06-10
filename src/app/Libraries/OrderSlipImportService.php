<?php
namespace App\Libraries;

use App\Libraries\BaseImportService;
use App\Libraries\DataTransformer;

/**
 * 発注伝票データのExcel/CSVファイル取り込み処理を行うサービスクラスです。
 * BaseImportServiceを継承し、発注伝票固有のデータマッピングとDB保存ロジックを担当します。
 */
class OrderSlipImportService extends BaseImportService
{
    private const TARGET_TABLE = 'order_slip';
    private const EXPECTED_CSV_COLUMNS = 34;
    
    private const HEADER_ROW_NO = 2;  // 発注伝票は2行目がヘッダになります

    /**
     * OrderSlipImportService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->serviceNameForLogging = 'OrderSlipImportService';

        // 発注伝票は2行目がヘッダになります
        $this->headerRowNumber = self::HEADER_ROW_NO;
    }

    /**
     * 発注伝票のExcel/CSVファイルを処理し、データベースに取り込みます。
     *
     * @param string $filePath サーバーに保存されたファイルのフルパス
     * @return array 処理結果の連想配列
     */
    public function processFile(string $filePath): array
    {
        $this->logger->info("{$this->serviceNameForLogging}: Processing order slip file: " . basename($filePath));

        $errorMessages = [];
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $processedDataRows = 0;
        
        $initializationError = '';
        $headerValidationError = '';
        $worksheet = null;
        $overallSuccess = true; 

        try {
            $worksheet = $this->loadAndGetWorksheet($filePath, null, $initializationError);

            if ($worksheet === null) {
                $finalMessage = $initializationError ?: "Excel/CSVファイルのロードに失敗しました。";
                $this->logger->error("[{$this->serviceNameForLogging}] " . $finalMessage);
                return $this->generateResult(false, $finalMessage, 0,0,0,0,[$finalMessage]);
            }

            $expectedMainHeaders = [
                '発注番号',     // Excel列1
                '行',         // Excel列2
                '仕入先',     // Excel列3
                '仕入先名',   // Excel列4
                '納品方法',   // Excel列5
                '店舗',       // Excel列6
                '店舗名',     // Excel列7
                '発注区分',   // Excel列8
                '発注日付',   // Excel列9
                '倉庫納期',   // Excel列10
                '店舗納期',   // Excel列11
            ]; 
            
            $rowIterator = $this->getValidatedRowIterator(
                $worksheet,
                $expectedMainHeaders, 
                $headerValidationError,
                $this->headerRowNumber
                
            );
            
            if ($rowIterator === null) {
                $finalMessage = $headerValidationError ?: "ファイルヘッダーの検証に失敗しました。";
                $this->logger->error("[{$this->serviceNameForLogging}] Header validation failed. " . $finalMessage . " Expected main headers (sample): " . implode(',', array_slice($expectedMainHeaders,0,5)) . "...");
                
                if ($worksheet !== null) {
                    $spreadsheetForCleanup = $worksheet->getParent() ?? null;
                    $nullReader = null; 
                    $this->cleanupSpreadsheetObjects($spreadsheetForCleanup, $worksheet, $nullReader); 
                    $worksheet = null; 
                }
                return $this->generateResult(false, $finalMessage,0,0,0,0,[$finalMessage]);
            }
            
            $currentBatchDataForInsert = [];
            $highestRowForLoop = $worksheet->getHighestDataRow();

            while ($rowIterator->valid()) {
                $excelRowObject = $rowIterator->current(); 
                $currentRowNumInFile = $excelRowObject->getRowIndex();

                $cellIterator = $excelRowObject->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = array_fill(0, self::EXPECTED_CSV_COLUMNS, null); 
                $colNum = 0;
                foreach ($cellIterator as $cell) { 
                    if ($colNum >= self::EXPECTED_CSV_COLUMNS) break;
                    $rowData[$colNum++] = $cell->getValue(); 
                }
                unset($cellIterator, $cell, $excelRowObject); 
                
                if ($this->isRowEmpty($rowData)) { 
                    $rowIterator->next();
                    continue;
                }
                $processedDataRows++;

                $orderNumberRaw = $rowData[0] ?? null;
                $lineNumberRaw  = $rowData[1] ?? null;

                $orderNumber = DataTransformer::excelToIntOrNull($orderNumberRaw);
                $lineNumber  = DataTransformer::excelToIntOrNull($lineNumberRaw);

                if ($orderNumber === null || $lineNumber === null) {
                    $errorMessages[] = "{$currentRowNumInFile}行目: PK必須項目（発注番号または行番号）が空か無効。発注番号: '{$orderNumberRaw}', 行番号: '{$lineNumberRaw}'";
                    $skippedCount++;
                    $overallSuccess = false;
                    $rowIterator->next();
                    continue;
                }

                $supplierCodeRaw  = $rowData[2] ?? null;
                $storeCodeRaw     = $rowData[5] ?? null;
                $orderDateRaw     = $rowData[8] ?? null;

                $supplierCode = DataTransformer::excelToStringOrEmpty($supplierCodeRaw);
                $storeCode    = DataTransformer::excelToStringOrEmpty($storeCodeRaw);
                $orderDate    = DataTransformer::excelToDbDate($orderDateRaw);    
                
                $currentRequiredErrors = [];
                if (empty($supplierCode)) $currentRequiredErrors[] = "仕入先コード(列3)";
                if (empty($storeCode))    $currentRequiredErrors[] = "店舗コード(列6)";
                if (empty($orderDate))    $currentRequiredErrors[] = "発注日付(列9)";

                if (!empty($currentRequiredErrors)) {
                    $errorMessages[] = "{$currentRowNumInFile}行目 (PK: {$orderNumber}-{$lineNumber}): 必須項目 (" . implode(', ', $currentRequiredErrors) . ") 不足によりスキップ。";
                    $skippedCount++;
                    $overallSuccess = false;
                    $rowIterator->next();
                    continue;
                }

                // 空白列の補完処理
                $colorName = !empty($rowData[23]) ? DataTransformer::excelToStringOrEmpty($rowData[23]) : 
                            DataTransformer::excelToStringOrEmpty($rowData[22] ?? null); // カラーコードで補完
                $sizeName = !empty($rowData[25]) ? DataTransformer::excelToStringOrEmpty($rowData[25]) : 
                           DataTransformer::excelToStringOrEmpty($rowData[24] ?? null); // サイズコードで補完

                $dataForDb = [
                    'order_number'              => $orderNumber,
                    'line_number'               => $lineNumber,
                    'supplier_code'             => $supplierCode,
                    'supplier_name'             => DataTransformer::excelToStringOrEmpty($rowData[3] ?? null),
                    'delivery_method'           => DataTransformer::excelToStringOrEmpty($rowData[4] ?? null),
                    'store_code'                => $storeCode,
                    'store_name'                => DataTransformer::excelToStringOrEmpty($rowData[6] ?? null),
                    'order_type'                => DataTransformer::excelToStringOrEmpty($rowData[7] ?? null),
                    'order_date'                => $orderDate,
                    'warehouse_delivery_date'   => DataTransformer::excelToDbDate($rowData[9] ?? null),
                    'store_delivery_date'       => DataTransformer::excelToDbDate($rowData[10] ?? null),
                    'customer_order_type'       => DataTransformer::excelToStringOrEmpty($rowData[11] ?? null),
                    'edi_type'                  => DataTransformer::excelToStringOrEmpty($rowData[12] ?? null),
                    'staff_code'                => DataTransformer::excelToStringOrEmpty($rowData[13] ?? null),
                    'staff_name'                => DataTransformer::excelToStringOrEmpty($rowData[14] ?? null),
                    'jan_code'                  => DataTransformer::excelToStringOrEmpty($rowData[15] ?? null),
                    'sku_code'                  => DataTransformer::excelToStringOrEmpty($rowData[16] ?? null),
                    'manufacturer_code'         => DataTransformer::excelToStringOrEmpty($rowData[17] ?? null),
                    'department_code'           => DataTransformer::excelToStringOrEmpty($rowData[18] ?? null),
                    'product_number'            => DataTransformer::excelToStringOrEmpty($rowData[19] ?? null),
                    'product_name'              => DataTransformer::excelToStringOrEmpty($rowData[20] ?? null),
                    'manufacturer_color_code'   => DataTransformer::excelToStringOrEmpty($rowData[21] ?? null),
                    'color_code'                => DataTransformer::excelToStringOrEmpty($rowData[22] ?? null),
                    'color_name'                => $colorName,
                    'size_code'                 => DataTransformer::excelToStringOrEmpty($rowData[24] ?? null),
                    'size_name'                 => $sizeName,
                    'cost_price'                => DataTransformer::excelToDecimalOrNull($rowData[26] ?? null, 2),
                    'cost_price_tax_included'   => DataTransformer::excelToDecimalOrNull($rowData[27] ?? null, 2),
                    'selling_price'             => DataTransformer::excelToDecimalOrNull($rowData[28] ?? null, 2),
                    'selling_price_tax_included' => DataTransformer::excelToDecimalOrNull($rowData[29] ?? null, 2),
                    'order_quantity'            => DataTransformer::excelToIntOrNull($rowData[30] ?? null),
                    'order_amount'              => DataTransformer::excelToDecimalOrNull($rowData[31] ?? null, 2),
                    'order_amount_tax_included' => DataTransformer::excelToDecimalOrNull($rowData[32] ?? null, 2),
                    'updated_at'                => date('Y-m-d H:i:s.v'), // 現在日時
                ];

                $existing = $this->db->table(self::TARGET_TABLE)
                                   ->where('order_number', $dataForDb['order_number'])
                                   ->where('line_number', $dataForDb['line_number'])
                                   ->get()->getRow();
                
                $this->db->transBegin();
                $operationSuccess = false;

                if ($existing) { 
                    if ($this->executeUpdate(self::TARGET_TABLE, $dataForDb, ['order_number' => $dataForDb['order_number'], 'line_number' => $dataForDb['line_number']])) {
                        if ($this->db->transStatus()) {
                            $this->db->transCommit();
                            $connIdInfo = ($this->db->connID) ? (is_object($this->db->connID) ? get_class($this->db->connID) : gettype($this->db->connID)." (ID:".strval($this->db->connID).")") : 'N/A';
                            $this->logger->info("[{$this->serviceNameForLogging}] DEBUG: Update Committed for PK {$dataForDb['order_number']}-{$dataForDb['line_number']}. DB Connection Info: " . $connIdInfo);
                            $updatedCount++;
                            $operationSuccess = true;
                        } else {
                            $this->db->transRollback();
                            $this->logger->error("[{$this->serviceNameForLogging}] DEBUG: Update ROLLBACK (transStatus false) for PK {$dataForDb['order_number']}-{$dataForDb['line_number']}.");
                            $errorMessages[] = "{$currentRowNumInFile}行目 (PK: {$dataForDb['order_number']}-{$dataForDb['line_number']}): DB更新後トランザクションコミット失敗。";
                        }
                    } else {
                        $this->db->transRollback();
                        $this->logger->error("[{$this->serviceNameForLogging}] DEBUG: Update ROLLBACK (executeUpdate false) for PK {$dataForDb['order_number']}-{$dataForDb['line_number']}.");
                        $errorMessages[] = "{$currentRowNumInFile}行目 (PK: {$dataForDb['order_number']}-{$dataForDb['line_number']}): DB更新実行失敗。";
                    }
                } else { 
                    $currentBatchDataForInsert[] = $dataForDb;
                    $operationSuccess = true; 
                    if ($this->db->transStatus()) { 
                        $this->db->transCommit();
                    } else {
                        $this->db->transRollback();
                        $this->logger->error("[{$this->serviceNameForLogging}] DEBUG: Transaction for insert preparation path had an issue for PK {$dataForDb['order_number']}-{$dataForDb['line_number']}.");
                        $operationSuccess = false; 
                    }
                }

                if (!$operationSuccess && $existing) { 
                    $skippedCount++;
                    $overallSuccess = false;
                }

                if (count($currentBatchDataForInsert) >= $this->importBatchSize) {
                    $this->db->transBegin(); 
                    $tempImported = 0; $tempSkipped = 0; $tempErrors = [];
                    $this->executeBatchInsert(self::TARGET_TABLE, $currentBatchDataForInsert, $tempImported, $tempSkipped, $tempErrors, "{$currentRowNumInFile}行目までの");
                    
                    if ($this->db->transStatus() === false || $tempSkipped > 0 || !empty($tempErrors) ) { 
                         $this->db->transRollback();
                         $connIdInfo = ($this->db->connID) ? (is_object($this->db->connID) ? get_class($this->db->connID) : gettype($this->db->connID)." (ID:".strval($this->db->connID).")") : 'N/A';
                         $this->logger->info("[{$this->serviceNameForLogging}] DEBUG: Batch Insert ROLLED BACK around row {$currentRowNumInFile}. TempSkipped: {$tempSkipped}, TempErrors: " . count($tempErrors) . ", TransStatus: " . ($this->db->transStatus()?'true':'false') . ", DB Connection Info: " . $connIdInfo);
                         $skippedCount += $tempSkipped > 0 ? $tempSkipped : (empty($currentBatchDataForInsert) ? 0 : count($currentBatchDataForInsert)); 
                         $errorMessages = array_merge($errorMessages, $tempErrors);
                         if(empty($tempErrors) && $tempSkipped == 0 && $this->db->transStatus() === false) $errorMessages[] = "{$currentRowNumInFile}行目までの挿入バッチでトランザクションエラー。";
                         $overallSuccess = false; 
                    } else {
                         $this->db->transCommit();
                         $connIdInfo = ($this->db->connID) ? (is_object($this->db->connID) ? get_class($this->db->connID) : gettype($this->db->connID)." (ID:".strval($this->db->connID).")") : 'N/A';
                         $this->logger->info("[{$this->serviceNameForLogging}] DEBUG: Batch Insert COMMITTED around row {$currentRowNumInFile}. TempImported: {$tempImported}. DB Connection Info: " . $connIdInfo);
                         $importedCount += $tempImported;
                    }
                }
                
                if ($processedDataRows % 200 === 0) { 
                    $this->logMemoryUsage($processedDataRows, $highestRowForLoop, $this->serviceNameForLogging);
                }
                $rowIterator->next();
            } 

            if (!empty($currentBatchDataForInsert)) {
                $this->db->transBegin();
                $tempImported = 0; $tempSkipped = 0; $tempErrors = [];
                $batchDataCountBeforeClear = count($currentBatchDataForInsert);
                $this->executeBatchInsert(self::TARGET_TABLE, $currentBatchDataForInsert, $tempImported, $tempSkipped, $tempErrors, "最終");
                if ($this->db->transStatus() === false || $tempSkipped > 0 || !empty($tempErrors) ) {
                    $this->db->transRollback();
                    $connIdInfo = ($this->db->connID) ? (is_object($this->db->connID) ? get_class($this->db->connID) : gettype($this->db->connID)." (ID:".strval($this->db->connID).")") : 'N/A';
                    $this->logger->info("[{$this->serviceNameForLogging}] DEBUG: Final Batch Insert ROLLED BACK. TempSkipped: {$tempSkipped}, TempErrors: " . count($tempErrors) . ", TransStatus: " . ($this->db->transStatus()?'true':'false') . ", DB Connection Info: " . $connIdInfo);
                    $skippedCount += $tempSkipped > 0 ? $tempSkipped : $batchDataCountBeforeClear;
                    $errorMessages = array_merge($errorMessages, $tempErrors);
                    if(empty($tempErrors) && $tempSkipped == 0 && $this->db->transStatus() === false) $errorMessages[] = "最終挿入バッチでトランザクションエラー。";
                    $overallSuccess = false;
                } else {
                     $this->db->transCommit();
                     $connIdInfo = ($this->db->connID) ? (is_object($this->db->connID) ? get_class($this->db->connID) : gettype($this->db->connID)." (ID:".strval($this->db->connID).")") : 'N/A';
                     $this->logger->info("[{$this->serviceNameForLogging}] DEBUG: Final Batch Insert COMMITTED. TempImported: {$tempImported}. DB Connection Info: " . $connIdInfo);
                     $importedCount += $tempImported;
                }
            }

            $detailedCountMessage = "全{$processedDataRows}データ行を処理。新規{$importedCount}件、更新{$updatedCount}件。{$skippedCount}件スキップ。";
            if ($processedDataRows === 0) {
                if ($highestRowForLoop <= $this->headerRowNumber) {
                    $finalMessage = "ファイルに処理対象データがありませんでした。";
                } else {
                    $finalMessage = "処理対象の有効なデータ行が見つかりませんでした。";
                }
                if (!empty($errorMessages) && $overallSuccess) { 
                    $overallSuccess = false; 
                }
                $finalMessage .= " ({$detailedCountMessage})";
            } elseif ($overallSuccess === false || $skippedCount > 0) { 
                $finalMessage = "処理完了（一部スキップまたはエラーあり）: {$detailedCountMessage}";
                $overallSuccess = false; 
            } else { 
                $finalMessage = "処理完了: {$detailedCountMessage}";
                $overallSuccess = true; 
            }
            
            $this->logger->info("[{$this->serviceNameForLogging}] Processing finished. Final Message: " . $finalMessage . " OverallSuccess: " . ($overallSuccess ? 'true':'false'));
            
            $summaryErrorMessages = $this->summarizeErrorMessages($errorMessages); 
            return $this->generateResult( 
                $overallSuccess,
                $finalMessage, $importedCount, $updatedCount, $skippedCount, $processedDataRows, $summaryErrorMessages
            );

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            if ($this->db->connID) { $this->db->transRollback(); }
            $this->logger->error("[{$this->serviceNameForLogging}] PHPSpreadsheetライブラリエラー: " . $e->getMessage(), ['exception' => $e]);
            return $this->generateResult(false, "Excel/CSV処理ライブラリでエラーが発生: " . $e->getMessage(), $importedCount, $updatedCount, $skippedCount, $processedDataRows, [$e->getMessage()]);
        } catch (\Throwable $e) {
            if ($this->db->connID) { $this->db->transRollback(); }
            $this->logger->critical("[{$this->serviceNameForLogging}] 予期せぬ一般エラー: " . $e->getMessage(), ['exception' => $e]);
            return $this->generateResult(false, "予期せぬ処理エラーが発生: " . $e->getMessage(), $importedCount, $updatedCount, $skippedCount, $processedDataRows, [$e->getMessage()]);
        } finally {
            if ($worksheet !== null) {
                 $spreadsheet = $worksheet->getParent() ?? null; 
                 $this->cleanupSpreadsheetObjects($spreadsheet, $worksheet); 
            }
        }
    }
}