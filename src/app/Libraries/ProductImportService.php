<?php
namespace App\Libraries;

use App\Filters\ProductMasterReadFilter;
use App\Libraries\BaseImportService;
use App\Libraries\DataTransformer;

/**
 * 商品マスタのExcel/CSVファイル取り込み処理を行うライブラリクラスです。
 * BaseImportServiceを継承し、商品マスタ固有のデータマッピングとDB保存ロジックを担当します。
 */
class ProductImportService extends BaseImportService
{
    private const TARGET_TABLE = 'products';
    private const MAX_EXCEL_COLUMNS = 58; 

    /**
     * ProductImportService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->serviceNameForLogging = 'ProductImportService';
    }

    /**
     * 商品マスタのExcel/CSVファイルを処理し、データベースに取り込みます。
     *
     * @param string $filePath サーバーに保存されたファイルのフルパス
     * @return array 処理結果の連想配列
     */
    public function processFile(string $filePath): array
    {
        $this->logger->info("{$this->serviceNameForLogging}: Processing product master file: " . basename($filePath));

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
            $readFilter = null;
            if (class_exists('\App\Filters\ProductMasterReadFilter')) {
                 $readFilter = new ProductMasterReadFilter();
            }
            $worksheet = $this->loadAndGetWorksheet($filePath, $readFilter, $initializationError);

            if ($worksheet === null) {
                $finalMessage = $initializationError ?: "Excel/CSVファイルのロードに失敗しました。";
                $this->logger->error("[{$this->serviceNameForLogging}] " . $finalMessage);
                return $this->generateResult(false, $finalMessage, 0,0,0,0,[$finalMessage]);
            }

            $expectedMainHeaders = ['ＪＡＮ', 'SKU', 'ﾒｰｶｰ', '品番', '略名'];
            
            $rowIterator = $this->getValidatedRowIterator(
                $worksheet,
                $expectedMainHeaders,
                $headerValidationError,
                $this->headerRowNumber 
            );
            
            if ($rowIterator === null) {
                $finalMessage = $headerValidationError ?: "ファイルヘッダーの検証に失敗しました（主要ヘッダー不一致）。";
                $this->logger->error("[{$this->serviceNameForLogging}] Header validation failed. " . $finalMessage . " Expected main headers: " . implode(',', $expectedMainHeaders));
                if ($worksheet && $worksheet->getParent()) { $this->cleanupSpreadsheetObjects($worksheet->getParent(), $worksheet); }
                elseif($worksheet) { $this->cleanupSpreadsheetObjects(null, $worksheet); }
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
                $rowData = array_fill(0, self::MAX_EXCEL_COLUMNS, null); 
                $colNum = 0;
                foreach ($cellIterator as $cell) { 
                    if ($colNum >= self::MAX_EXCEL_COLUMNS) break;
                    $rowData[$colNum++] = $cell->getValue(); 
                }
                unset($cellIterator, $cell, $excelRowObject); 
                
                if ($this->isRowEmpty($rowData)) { // BaseImportServiceのメソッドを使用
                    $rowIterator->next();
                    continue;
                }
                $processedDataRows++;

                $janCodeRaw     = $rowData[0] ?? null;
                $productNameRaw = $rowData[5] ?? null;

                $janCode     = DataTransformer::excelToStringOrEmpty($janCodeRaw);
                $productName = DataTransformer::excelToStringOrEmpty($productNameRaw);

                $currentRequiredErrors = [];
                if (empty($janCode))     $currentRequiredErrors[] = "ＪＡＮ(列1)";
                // 品名のnullチェックは無し
                //if (empty($productName)) $currentRequiredErrors[] = "品名(列6)";
                
                if (!empty($currentRequiredErrors)) {
                    $errorMessages[] = "{$currentRowNumInFile}行目: 必須項目 (" . implode(', ', $currentRequiredErrors) . ") 不足によりスキップ。JAN: '{$janCodeRaw}'";
                    $skippedCount++;
                    $overallSuccess = false; 
                    $rowIterator->next();
                    continue;
                }
                
                $lastModifiedDateTime = DataTransformer::combineExcelDateAndTimeToDbDateTime(
                    $rowData[55] ?? null, 
                    $rowData[56] ?? null, 
                    true                  
                );
                
                $lastPurchaseDate = DataTransformer::excelToDbDateTime(
                    $rowData[25] ?? null, 
                    true                  
                );

                $dataForDb = [
                    'jan_code'                      => $janCode,
                    'sku_code'                      => DataTransformer::excelToStringOrEmpty($rowData[1] ?? null),
                    'manufacturer_code'             => DataTransformer::excelToStringOrEmpty($rowData[2] ?? null),
                    'product_number'                => DataTransformer::excelToStringOrEmpty($rowData[3] ?? null),
                    'short_name'                    => DataTransformer::excelToStringOrEmpty($rowData[4] ?? null),
                    'product_name'                  => $productName,
                    'department_code'               => DataTransformer::excelToStringOrEmpty($rowData[11] ?? null),
                    'manufacturer_color_code'       => DataTransformer::excelToStringOrEmpty($rowData[12] ?? null),
                    'color_code'                    => DataTransformer::excelToStringOrEmpty($rowData[13] ?? null),
                    'size_code'                     => DataTransformer::excelToStringOrEmpty($rowData[14] ?? null),
                    'product_year'                  => DataTransformer::excelToStringOrEmpty($rowData[15] ?? null),
                    'season_code'                   => DataTransformer::excelToStringOrEmpty($rowData[16] ?? null),
                    'supplier_code'                 => DataTransformer::excelToStringOrEmpty($rowData[17] ?? null),
                    'selling_price'                 => DataTransformer::excelToDecimalOrNull($rowData[18] ?? null, 2),
                    'selling_price_tax_included'    => DataTransformer::excelToDecimalOrNull($rowData[19] ?? null, 2),
                    'cost_price'                    => DataTransformer::excelToDecimalOrNull($rowData[20] ?? null, 2),
                    'cost_price_tax_included'       => DataTransformer::excelToDecimalOrNull($rowData[21] ?? null, 2),
                    'm_unit_price'                  => DataTransformer::excelToDecimalOrNull($rowData[22] ?? null, 2),
                    'm_unit_price_tax_included'     => DataTransformer::excelToDecimalOrNull($rowData[23] ?? null, 2),
                    'last_purchase_cost'            => DataTransformer::excelToDecimalOrNull($rowData[24] ?? null, 2),
                    'last_purchase_date'            => $lastPurchaseDate,
                    'standard_purchase_cost'        => DataTransformer::excelToDecimalOrNull($rowData[26] ?? null, 2),
                    'attribute_1'                   => DataTransformer::excelToStringOrEmpty($rowData[30] ?? null),
                    'attribute_2'                   => DataTransformer::excelToStringOrEmpty($rowData[31] ?? null),
                    'attribute_3'                   => DataTransformer::excelToStringOrEmpty($rowData[32] ?? null),
                    'attribute_4'                   => DataTransformer::excelToStringOrEmpty($rowData[33] ?? null),
                    'attribute_5'                   => DataTransformer::excelToStringOrEmpty($rowData[34] ?? null),
                    'purchase_type_id'              => DataTransformer::excelToIntOrNull($rowData[35] ?? null),
                    'product_classification_id'     => DataTransformer::excelToIntOrNull($rowData[36] ?? null),
                    'inventory_management_flag'     => DataTransformer::excelToIntOrNull($rowData[38] ?? null),
                    'initial_registration_date'     => DataTransformer::excelToDbDate($rowData[54] ?? null),
                    'last_modified_datetime'        => $lastModifiedDateTime,
                ];

                $existing = $this->db->table(self::TARGET_TABLE)
                                   ->where('jan_code', $dataForDb['jan_code'])
                                   ->get()->getRow();
                
                if ($existing) {
                    $currentBatchDataForUpdate[] = $dataForDb;
                } else {
                    $currentBatchDataForInsert[] = $dataForDb;
                }

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
                        $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, 'jan_code');
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
                    $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, 'jan_code');
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

            $detailedCountMessage = "全{$processedDataRows}データ行を処理。新規{$importedCount}件、更新{$updatedCount}件。{$skippedCount}件スキップ。";

            if ($processedDataRows === 0) {
                if ($highestRowForLoop <= $this->headerRowNumber) {
                    $finalMessage = "ファイルにデータ行がありませんでした。";
                } else {
                    $finalMessage = "処理対象の有効なデータ行が見つかりませんでした。";
                }
                if (!empty($errorMessages)) { 
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
            if ($this->db->connID && $this->db->getTransDepth() > 0) { $this->db->transRollback(); }
            $this->logger->error("[{$this->serviceNameForLogging}] PHPSpreadsheetライブラリエラー: " . $e->getMessage(), ['exception' => $e]);
            return $this->generateResult(false, "Excel/CSV処理ライブラリでエラーが発生: " . $e->getMessage(), $importedCount, $updatedCount, $skippedCount, $processedDataRows, [$e->getMessage()]);
        } catch (\Throwable $e) {
            if ($this->db->connID && $this->db->getTransDepth() > 0) { $this->db->transRollback(); }
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