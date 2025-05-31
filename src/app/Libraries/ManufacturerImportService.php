<?php
namespace App\Libraries;

use App\Libraries\BaseImportService;
use App\Libraries\DataTransformer;

/**
 * メーカーマスタのExcelファイル取り込み処理を行うライブラリクラスです。
 * BaseImportServiceを継承し、メーカーマスタ固有のデータマッピングとDB保存ロジックを担当します。
 */
class ManufacturerImportService extends BaseImportService
{
    private const TARGET_TABLE = 'manufacturers';
    private const MAX_EXCEL_COLUMNS = 11; // A〜K列
    
    /**
     * ManufacturerImportService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->serviceNameForLogging = 'ManufacturerImportService';
    }

    /**
     * メーカーマスタのExcelファイルを処理し、データベースに取り込みます。
     *
     * @param string $filePath サーバーに保存されたファイルのフルパス
     * @return array 処理結果の連想配列
     */
    public function processFile(string $filePath): array
    {
        $this->logger->info("{$this->serviceNameForLogging}: Processing manufacturer master file: " . basename($filePath));
        
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
            $worksheet = $this->loadAndGetWorksheet($filePath, $readFilter, $initializationError);
            if ($worksheet === null) {
                $finalMessage = $initializationError ?: "Excel/CSVファイルのロードに失敗しました。";
                $this->logger->error("[{$this->serviceNameForLogging}] " . $finalMessage);
                return $this->generateResult(false, $finalMessage, 0,0,0,0,[$finalMessage]);
            }

            // メーカーマスタの期待ヘッダー（2行目がヘッダー）
            $expectedMainHeaders = ['コード', '名称'];
            
            // ヘッダー行は2行目なので、headerRowToValidateを2に設定
            $rowIterator = $this->getValidatedRowIterator(
                $worksheet,
                $expectedMainHeaders,
                $headerValidationError,
                2 // 2行目がヘッダー
            );
            
            if ($rowIterator === null) {
                $finalMessage = $headerValidationError ?: "ファイルヘッダーの検証に失敗しました（主要ヘッダー不一致）。";
                $this->logger->error("[{$this->serviceNameForLogging}] Header validation failed. " . $finalMessage . " Expected main headers: " . implode(',', $expectedMainHeaders));
                if ($worksheet && $worksheet->getParent()) { 
                    $this->cleanupSpreadsheetObjects($worksheet->getParent(), $worksheet); 
                } elseif($worksheet) { 
                    $this->cleanupSpreadsheetObjects(null, $worksheet); 
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
                $rowData = array_fill(0, self::MAX_EXCEL_COLUMNS, null); 
                $colNum = 0;
                foreach ($cellIterator as $cell) { 
                    if ($colNum >= self::MAX_EXCEL_COLUMNS) break;
                    $rowData[$colNum++] = $cell->getValue(); 
                }
                unset($cellIterator, $cell, $excelRowObject); 
                
                if ($this->isRowEmpty($rowData)) {
                    $rowIterator->next();
                    continue;
                }
                $processedDataRows++;

                $manufacturerCodeRaw = $rowData[0] ?? null;
                $manufacturerNameRaw = $rowData[1] ?? null;

                $manufacturerCode = DataTransformer::excelToStringOrEmpty($manufacturerCodeRaw);
                $manufacturerName = DataTransformer::excelToStringOrEmpty($manufacturerNameRaw);

                $currentRequiredErrors = [];
                if (empty($manufacturerCode)) $currentRequiredErrors[] = "メーカーコード(列1)";
                if (empty($manufacturerName)) $currentRequiredErrors[] = "メーカー名称(列2)";
                
                if (!empty($currentRequiredErrors)) {
                    $errorMessages[] = "{$currentRowNumInFile}行目: 必須項目 (" . implode(', ', $currentRequiredErrors) . ") 不足によりスキップ。コード: '{$manufacturerCodeRaw}', 名称: '{$manufacturerNameRaw}'";
                    $skippedCount++;
                    $overallSuccess = false; 
                    $rowIterator->next();
                    continue;
                }

                $dataForDb = [
                    'manufacturer_code' => $manufacturerCode,
                    'manufacturer_name' => $manufacturerName,
                    'updated_at'        => date('Y-m-d H:i:s.v'),
                ];

                $existing = $this->db->table(self::TARGET_TABLE)
                                   ->where('manufacturer_code', $dataForDb['manufacturer_code'])
                                   ->get()->getRow();
                
                if ($existing) {
                    $currentBatchDataForUpdate[] = $dataForDb;
                } else {
                    $dataForDb['created_at'] = date('Y-m-d H:i:s.v');
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
                        $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, 'manufacturer_code');
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
                    $affectedRows = $this->db->table(self::TARGET_TABLE)->updateBatch($currentBatchDataForUpdate, 'manufacturer_code');
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