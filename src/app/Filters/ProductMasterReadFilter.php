<?php

namespace App\Filters;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * PHPSpreadsheet リードフィルター: 商品マスタ用
 *
 * 商品マスタのExcelファイルを読み込む際に、データベースのProductsテーブルに
 * マッピングする列のみをメモリにロードするように制限します。
 * これにより、特に列数が多いファイルの場合にメモリ消費量を削減します。
 */
class ProductMasterReadFilter implements IReadFilter
{
    /**
     * @var array データベースに取り込む必要があるExcelの列のアルファベット名。
     * Excelの列とDBカラムのマッピングに基づいて定義します。
     * (Excel列1 => 'A', Excel列2 => 'B', ..., Excel列26 => 'Z', Excel列27 => 'AA', ...)
     */
    private array $columnsToReadLetters = [
        'A',  // ＪＡＮ (Excel列1)
        'B',  // SKU (Excel列2)
        'C',  // ﾒｰｶｰ (Excel列3)
        'D',  // 品番 (Excel列4)
        'E',  // 略名 (Excel列5)
        'F',  // 品名 (Excel列6)
        // G列からK列はProductsテーブルへの直接のマッピング対象外のため読み飛ばします
        'L',  // 部門 (Excel列12)
        'M',  // MKｶﾗｰ (Excel列13)
        'N',  // カラー (Excel列14)
        'O',  // サイズ (Excel列15)
        'P',  // 年度 (Excel列16)
        'Q',  // 季節 (Excel列17)
        'R',  // 仕入先 (Excel列18)
        'S',  // 売価 (Excel列19)
        'T',  // 税込売価 (Excel列20)
        'U',  // 原価 (Excel列21)
        'V',  // 税込原価 (Excel列22)
        'W',  // Ｍ単価 (Excel列23)
        'X',  // 税込Ｍ単価 (Excel列24)
        'Y',  // 最終仕入原価 (Excel列25)
        'Z',  // 最終仕入日 (Excel列26)
        'AA', // 標準仕入原価 (Excel列27)
        // AB, AC, AD (発注ロット数, 最低発注数, 担当者) はProductsテーブルへの直接のマッピング対象外
        'AE', // 属性1 (Excel列31)
        'AF', // 属性2 (Excel列32)
        'AG', // 属性3 (Excel列33)
        'AH', // 属性4 (Excel列34)
        'AI', // 属性5 (Excel列35)
        'AJ', // 仕入形態 (Excel列36)
        'AK', // 商品区分 (Excel列37)
        // AL (自動発注) はProductsテーブルへの直接のマッピング対象外
        'AM', // 在庫管理 (Excel列39)
        // AN (タグ有無) から BB (販売着地日) まではProductsテーブルへの直接のマッピング対象外
        // Excelの列番号55,56,57 はそれぞれ BC, BD, BE になります
        'BA', // 削除予定日 (Excel列53)
        'BB', // 削除区分 (Excel列54)
        'BC', // 初回登録日 (Excel列55)
        'BD', // 変更日付 (Excel列56)
        'BE', // 変更時間 (Excel列57)
        // BF (備考) はProductsテーブルへの直接のマッピング対象外
    ];

    /**
     * 指定されたセルを読み込むべきかどうかをPHPSpreadsheetに伝えます。
     *
     * @param string $columnAddress セルの列アドレス (例: 'A', 'B', 'AA')
     * @param int    $row           セルの行番号 (1から始まる)
     * @param string $worksheetName ワークシート名 (このフィルターでは未使用)
     * @return bool セルを読み込む場合は true、そうでない場合は false を返します。
     */
    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        // 1行目 (ヘッダー行) は、列名検証のために常に全ての列を読み込みます。
        // (ヘッダー行自体の列数が多すぎると、ここでもメモリを消費する可能性はゼロではありませんが、
        // 通常はデータ行に比べて影響は軽微です。)
        if ($row === 1) {
            return true;
        }

        // データ行 (2行目以降) については、$columnsToReadLetters 配列に含まれる列のセルのみを読み込みます。
        return in_array($columnAddress, $this->columnsToReadLetters);
    }
}