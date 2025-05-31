<?php

namespace App\Libraries;

/**
 * データ取り込みサービスのインターフェース。
 * 各マスタや伝票などのデータ取り込み処理を行うサービスは、このインターフェースを実装する必要があります。
 */
interface ImportServiceInterface
{
    /**
     * 指定されたファイルを処理し、取り込み結果を返します。
     *
     * @param string $filePath サーバー上に保存された処理対象ファイルのフルパス
     * @return array 処理結果を示す連想配列。最低限、以下のキーを含むことを期待します:
     * - 'success' (bool): 処理全体が成功したかどうか
     * - 'message' (string): ユーザー向けのサマリーメッセージ
     * - 'errorMessages' (array): 詳細なエラーメッセージの配列 (もしあれば)
     * - 'importedCount' (int): 新規インポート（挿入）された件数
     * - 'updatedCount' (int): 更新された件数
     * - 'skippedCount' (int): スキップされた件数
     * - 'processedDataRows' (int): 実際に処理を試みたデータ行数（ヘッダー除く）
     */
    public function processFile(string $filePath): array;
}