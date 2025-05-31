<?php
namespace App\Libraries;

use Config\App; // CodeIgniterの定数 WRITEPATH を使うため
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ファイルのアーカイブおよび古いアーカイブファイルのクリーンアップを行うためのヘルパークラスです。
 *
 * 主な機能:
 * - 指定されたファイルをタイムスタンプと識別子を含めた新しい名前で、
 * 　[ベースパス]/[ターゲット識別子]/[ステータス]/ のディレクトリ構造に移動します。
 * - 新しいファイルをアーカイブする際に、指定された保持期間より古いファイルを
 * 　同じステータスフォルダ内から削除します。
 */
class FileArchiveManager
{
    private string $archiveBasePath;
    private int $retentionDays = 5; // ファイルの保持期間（日数）

    /**
     * Constructor
     * @param string|null $archiveBasePath アーカイブのベースパス。指定がなければ writable/archives/ となります。
     * @param int $retentionDays ファイル保持日数
     */
    public function __construct(?string $archiveBasePath = null, int $retentionDays = 5)
    {
        $this->archiveBasePath = rtrim($archiveBasePath ?? WRITEPATH . 'archives', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->retentionDays = $retentionDays;
    }

    /**
     * 指定されたファイルをアーカイブし、古いファイルをクリーンアップします。
     *
     * @param string $sourceFilePath      アーカイブするファイルの現在のフルパス
     * @param string $targetIdentifier    マスタや伝票の種類を示す識別子 (例: "product_master")
     * @param string $originalFileName    元のファイル名 (拡張子含む)
     * @param string $status              処理結果のステータス (例: "perfect_success", "completed_with_skips")
     * @return bool                       アーカイブ成功時は true、失敗時は false
     */
    public function archive(string $sourceFilePath, string $targetIdentifier, string $originalFileName, string $status = 'completed'): bool
    {
        // 1. アーカイブ先のパスを決定
        $statusPath = $this->archiveBasePath . $targetIdentifier . DIRECTORY_SEPARATOR . $status . DIRECTORY_SEPARATOR;

        // 2. ディレクトリが存在しない場合は作成
        if (!is_dir($statusPath)) {
            if (!mkdir($statusPath, 0775, true)) {
                log_message('error', "[FileArchiveManager] Failed to create directory: " . $statusPath);
                return false;
            }
            log_message('info', "[FileArchiveManager] Created directory: " . $statusPath);
        }

        // 3. 新しいファイル名を生成 (例: product_master_20250528-163000_商品照会(8).xlsx)
        $timestamp = date('Ymd-His');
        $sanitizedOriginalFileName = preg_replace('/[^A-Za-z0-9_.\-()]/', '_', $originalFileName);
        $newFileName = $targetIdentifier . '_' . $timestamp . '_' . $sanitizedOriginalFileName;
        $destinationFilePath = $statusPath . $newFileName;

        // 4. ファイルを移動
        if (rename($sourceFilePath, $destinationFilePath)) {
            log_message('info', "[FileArchiveManager] Moved file '{$sourceFilePath}' to '{$destinationFilePath}'");

            // 5. このターゲット識別子とステータスに関連する古いファイルをクリーンアップ
            $this->cleanupOldFiles($statusPath, $targetIdentifier);
            return true;
        } else {
            log_message('error', "[FileArchiveManager] Failed to move file '{$sourceFilePath}' to '{$destinationFilePath}'");
            return false;
        }
    }

    /**
     * 指定されたディレクトリ内の古いファイルを削除します。
     *
     * @param string $directoryPath クリーンアップ対象のディレクトリパス
     * @param string $targetIdentifier ログ出力用のターゲット識別子
     */
    private function cleanupOldFiles(string $directoryPath, string $targetIdentifier): void
    {
        log_message('info', "[FileArchiveManager] Starting cleanup of old files for '{$targetIdentifier}' in '{$directoryPath}'");
        $cutoffTimestamp = time() - ($this->retentionDays * 24 * 60 * 60);

        if (!is_dir($directoryPath)) {
            log_message('debug', "[FileArchiveManager] Cleanup directory does not exist, skipping: " . $directoryPath);
            return;
        }

        try {
            $iterator = new \DirectoryIterator($directoryPath);
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    if ($fileInfo->getMTime() < $cutoffTimestamp) {
                        $filePathToDelete = $fileInfo->getRealPath();
                        if (unlink($filePathToDelete)) {
                            log_message('info', "[FileArchiveManager] Deleted old file: " . $filePathToDelete);
                        } else {
                            log_message('error', "[FileArchiveManager] Failed to delete old file: " . $filePathToDelete);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', "[FileArchiveManager] Error during cleanup in '{$directoryPath}': " . $e->getMessage());
        }
        log_message('info', "[FileArchiveManager] Finished cleanup for '{$targetIdentifier}' in '{$directoryPath}'");
    }
}