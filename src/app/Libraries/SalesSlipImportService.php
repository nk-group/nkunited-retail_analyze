<?php
namespace App\Libraries;

use App\Libraries\BaseImportService;
use App\Libraries\DataTransformer;

/**
 * 売上伝票データのExcel/CSVファイル取り込み処理を行うサービスクラスです。
 * BaseImportServiceを継承し、売上伝票固有のデータマッピングとDB保存ロジックを担当します。
 */
class SalesSlipImportService extends BaseImportService
{
    private const TARGET_TABLE = 'sales_slip';
    private const EXPECTED_CSV_COLUMNS = 31; // 既存コードと命名を統一

    /**
     * SalesSlipImportService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->serviceNameForLogging = 'SalesSlipImportService';
    }

    /**
     * 売上伝票のExcel/CSVファイルを処理し、データベースに取り込みます。
     *
     * @param string $filePath サーバーに保存されたファイルのフルパス
     * @return array 処理結果の連想配列
     */
    public function processFile(string $filePath): array
    {
        $this->logger->info("{$this->serviceNameForLogging}: Processing sales slip file: " . basename($filePath));
        
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

            // 売上伝票の期待ヘッダー
            $expectedMainHeaders = [
                '入力番号',     // Excel列1
                '行',         // Excel列2
                '伝票番号',     // Excel列3
                '店舗',       // Excel列4
                '店舗名',     // Excel列5 
                '売上区分',     // Excel列6
                '売上日付',     // Excel列7
                '売上時間',     // Excel列8
                '顧客',       // Excel列9
                '客層',       // Excel列10
                '担当者',     // Excel列11
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
                    $this->cleanupSpreadsheetObjects($spreadsheetForCleanup, $worksheet); 
                    $worksheet = null; 
                }
                return $this->generateResult(false, $finalMessage,0,0,0,0,[$finalMessage]);
            }
            
            $currentBatchDataForInsert = [];
            $currentBatchDataForUpdate = [];
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

                // 主キー項目の検証
                $inputNumberRaw = $rowData[0] ?? null;
                $lineNumberRaw  = $rowData[1] ?? null;
                $inputNumber = DataTransformer::excelToIntOrNull($inputNumberRaw);
                $lineNumber  = DataTransformer::excelToIntOrNull($lineNumberRaw);

                if ($inputNumber === null || $lineNumber === null) {
                    $errorMessages[] = "{$currentRowNumInFile}行目: PK必須項目（入力番号または行番号）が空か無効。入力番号: '{$inputNumberRaw}', 行番号: '{$lineNumberRaw}'";
                    $skippedCount++;
                    $overallSuccess = false;
                    $rowIterator->next();
                    continue;
                }

                // その他必須項目の検証
                $slipNumberRaw = $rowData[2] ?? null;
                $storeCodeRaw  = $rowData[3] ?? null;
                $salesDateRaw  = $rowData[6] ?? null;

                $slipNumber = DataTransformer::excelToIntOrNull($slipNumberRaw);
                $storeCode  = DataTransformer::excelToStringOrEmpty($storeCodeRaw);
                $salesDate  = DataTransformer::excelToDbDate($salesDateRaw);    
                
                $currentRequiredErrors = [];
                if ($slipNumber === null) $currentRequiredErrors[] = "伝票番号(列3)";
                if (empty($storeCode))   $currentRequiredErrors[] = "店舗コード(列4)";
                if (empty($salesDate))   $currentRequiredErrors[] = "売上日付(列7)";

                if (!empty($currentRequiredErrors)) {
                    $errorMessages[] = "{$currentRowNumInFile}行目 (PK: {$inputNumber}-{$lineNumber}): 必須項目 (" . implode(', ', $currentRequiredErrors) . ") 不足によりスキップ。";
                    $skippedCount++;
                    $overallSuccess = false;
                    $rowIterator->next();
                    continue;
                }

                // 日時結合処理
                $updatedAt = DataTransformer::combineExcelDateAndTimeToDbDateTime(
                    $rowData[29] ?? null, // 更新日付 (AD列)
                    $rowData[30] ?? null, // 更新時間 (AE列)
                    true 
                );

                // データベース保存用配列の作成
                $dataForDb = [
                    'input_number'              => $inputNumber,
                    'line_number'               => $lineNumber,
                    'slip_number'               => $slipNumber,
                    'store_code'                => $storeCode,
                    'store_name'                => DataTransformer::excelToStringOrEmpty($rowData[4] ?? null),
                    'sales_type'                => DataTransformer::excelToStringOrEmpty($rowData[5] ?? null),
                    'sales_date'                => $salesDate,
                    'sales_time'                => DataTransformer::excelToStringOrEmpty($rowData[7] ?? null), // time型として処理
                    'customer_code'             => DataTransformer::excelToStringOrEmpty($rowData[8] ?? null),
                    'customer_category'         => DataTransformer::excelToStringOrEmpty($rowData[9] ?? null),
                    'staff_code'                => DataTransformer::excelToStringOrEmpty($rowData[10] ?? null),
                    'staff_name'                => DataTransformer::excelToStringOrEmpty($rowData[11] ?? null),
                    'jan_code'                  => DataTransformer::excelToStringOrEmpty($rowData[12] ?? null),
                    'sku_code'                  => DataTransformer::excelToStringOrEmpty($rowData[13] ?? null),
                    'manufacturer_code'         => DataTransformer::excelToStringOrEmpty($rowData[14] ?? null),
                    'department_code'           => DataTransformer::excelToStringOrEmpty($rowData[15] ?? null),
                    'product_number'            => DataTransformer::excelToStringOrEmpty($rowData[16] ?? null),
                    'product_name'              => DataTransformer::excelToStringOrEmpty($rowData[17] ?? null),
                    'manufacturer_color_code'   => DataTransformer::excelToStringOrEmpty($rowData[18] ?? null),
                    'color_code'                => DataTransformer::excelToStringOrEmpty($rowData[19] ?? null),
                    'color_name'                => DataTransformer::excelToStringOrEmpty($rowData[20] ?? null),
                    'size_code'                 => DataTransformer::excelToStringOrEmpty($rowData[21] ?? null),
                    'size_name'                 => DataTransformer::excelToStringOrEmpty($rowData[22] ?? null),
                    'cost_price'                => DataTransformer::excelToDecimalOrNull($rowData[23] ?? null, 2),
                    'selling_price'             => DataTransformer::excelToDecimalOrNull($rowData[24] ?? null, 2),
                    'sales_unit_price'          => DataTransformer::excelToDecimalOrNull($rowData[25] ?? null, 2),
                    'sales_quantity'            => DataTransformer::excelToIntOrNull($rowData[26] ?? null),
                    'sales_amount'              => DataTransformer::excelToDecimalOrNull($rowData[27] ?? null, 2),
                    'discount_amount'           => DataTransformer::excelToDecimalOrNull($rowData[28] ?? null, 2),
                    'updated_at'                => $updatedAt,
                ];

                // 既存データの確認
                $existing = $this->db->table(self::TARGET_TABLE)
                                   ->where('input_number', $dataForDb['input_number'])
                                   ->where('line_number', $dataForDb['line_number'])
                                   ->get()->getRow();
                
                if ($existing) {
                    $currentBatchDataForUpdate[] = $dataForDb;
                } else {
                    $currentBatchDataForInsert[] = $dataForDb;
                }

                // バッチサイズに達した場合の処理
                if (count($currentBatchDataForInsert) >= $this->importBatchSize) {
                    $this->db->transBegin();
                    $this->executeBatchInsert(self::TARGET_TABLE, $currentBatchDataForInsert, $importedCount, $skippedCount, $errorMessages, "{$currentRowNumInFile}行目までの");
                    if ($this->db->transStatus() === false) { 
                        $this->db->transRollback();
                        $this->logger->warning("[{$this->serviceNameForLogging}] Batch insert rolled back around row {$currentRowNumInFile}.");
                        $overallSuccess = false; 
                    } else {
                        $this->db->transCommit();
                    }
                }

                if (count($currentBatchDataForUpdate) >= $this->importBatchSize) {
                    $this->db->transBegin();
                    try {
                        $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, ['input_number', 'line_number']);
                        if ($affectedRows !== false && $this->db->transStatus()) { 
                            $this->db->transCommit();
                            $updatedCount += $affectedRows;
                        } else {
                            $this->db->transRollback();
                            $errorMessages[] = "{$currentRowNumInFile}行目までの更新バッチでエラー(DB状態:".$this->db->transStatus().",影響行:".($affectedRows===false ? 'N/A' : $affectedRows).")";
                            $skippedCount += count($currentBatchDataForUpdate); 
                            $this->logger->error("[{$this->serviceNameForLogging}] Update batch rolled back or failed for table '" . self::TARGET_TABLE . "'. DB Error: " . print_r($this->db->error(), true));
                            $overallSuccess = false;
                        }
                    } catch (\Throwable $eUpd) {
                        $this->db->transRollback(); 
                        $errorMessages[] = "{$currentRowNumInFile}行目までの更新バッチで例外発生: " . $eUpd->getMessage();
                        $skippedCount += count($currentBatchDataForUpdate);
                        $this->logger->error("[{$this->serviceNameForLogging}] Exception during update batch for '" . self::TARGET_TABLE . "': " . $eUpd->getMessage(), ['exception' => $eUpd]);
                        $overallSuccess = false;
                    }
                    $currentBatchDataForUpdate = [];
                }
                
                if ($processedDataRows % 200 === 0) { 
                    $this->logMemoryUsage($processedDataRows, $highestRowForLoop, $this->serviceNameForLogging);
                }
                $rowIterator->next();
            } 

            // 最終バッチの処理
            if (!empty($currentBatchDataForInsert)) {
                $this->db->transBegin();
                $this->executeBatchInsert(self::TARGET_TABLE, $currentBatchDataForInsert, $importedCount, $skippedCount, $errorMessages, "最終");
                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    $this->logger->warning("[{$this->serviceNameForLogging}] Final batch insert rolled back.");
                    $overallSuccess = false;
                } else {
                    $this->db->transCommit();
                }
            }

            if (!empty($currentBatchDataForUpdate)) {
                $this->db->transBegin();
                 try {
                    $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, ['input_number', 'line_number']);
                    if ($affectedRows !== false && $this->db->transStatus()) {
                        $this->db->transCommit();
                        $updatedCount += $affectedRows;
                    } else {
                        $this->db->transRollback();
                        $errorMessages[] = "最終更新バッチでエラー(DB状態:".$this->db->transStatus().",影響行:".($affectedRows===false ? 'N/A' : $affectedRows).")";
                        $skippedCount += count($currentBatchDataForUpdate);
                        $this->logger->error("[{$this->serviceNameForLogging}] Final update batch rolled back or failed for table '" . self::TARGET_TABLE . "'. DB Error: " . print_r($this->db->error(), true));
                        $overallSuccess = false;
                    }
                } catch (\Throwable $eUpd) {
                    $this->db->transRollback(); 
                    $errorMessages[] = "最終更新バッチで例外発生: " . $eUpd->getMessage();
                    $skippedCount += count($currentBatchDataForUpdate);
                    $this->logger->error("[{$this->serviceNameForLogging}] Exception during final update batch for '" . self::TARGET_TABLE . "': " . $eUpd->getMessage(), ['exception' => $eUpd]);
                    $overallSuccess = false;
                }
            }

            // 結果メッセージの生成
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