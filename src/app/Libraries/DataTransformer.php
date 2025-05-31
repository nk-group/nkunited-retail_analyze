<?php
namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate; // Excel日付処理用

/**
 * データ変換ユーティリティクラス。
 * Excelなどから取得した値を、データベース保存などに適した型や形式に変換するメソッドを提供します。
 */
class DataTransformer
{
    /**
     * Excelの値 (数値シリアル値または日付形式文字列) を
     * データベース保存用の 'Y-m-d' 形式の文字列に変換します。
     *
     * @param mixed $excelValue Excelから読み取った値、またはnull
     * @return string|null 変換後の日付文字列 (YYYY-MM-DD形式)、変換不可または入力が空の場合はnull
     */
    public static function excelToDbDate($excelValue): ?string
    {
        if ($excelValue === null || (is_string($excelValue) && trim($excelValue) === '')) {
            return null;
        }

        try {
            if (is_numeric($excelValue)) {
                // Excelのシリアル値の場合
                $dt = PhpSpreadsheetDate::excelToDateTimeObject($excelValue);
            } else {
                // 文字列の日付の場合 (一般的な区切り文字を考慮)
                $normalizedDate = str_replace(['/', '.'], '-', trim((string)$excelValue));
                $dt = new \DateTime($normalizedDate);
            }
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            // log_message('debug', "DataTransformer: Failed to parse date '{$excelValue}'. Error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Excelの値 (数値シリアル値または日時形式文字列) をデータベース保存用の
     * 'Y-m-d H:i:s.v' 形式 (SQL Server DATETIME2(3)想定) の文字列に変換します。
     *
     * @param mixed $excelValue Excelから読み取った値、またはnull
     * @param bool $includeMilliseconds ミリ秒を含むかどうか (デフォルト: true)
     * @return string|null 変換後の日時文字列、変換不可または入力が空の場合はnull
     */
    public static function excelToDbDateTime($excelValue, bool $includeMilliseconds = true): ?string
    {
        if ($excelValue === null || (is_string($excelValue) && trim($excelValue) === '')) {
            return null;
        }

        try {
            if (is_numeric($excelValue)) {
                $dt = PhpSpreadsheetDate::excelToDateTimeObject($excelValue);
            } else {
                $normalizedDateTime = str_replace(['/', '.'], '-', trim((string)$excelValue));
                $dt = new \DateTime($normalizedDateTime);
            }
            
            if ($includeMilliseconds) {
                return $dt->format('Y-m-d H:i:s.v'); // '.v' はミリ秒
            } else {
                return $dt->format('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            // log_message('debug', "DataTransformer: Failed to parse datetime '{$excelValue}'. Error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Excelの日付部分の値と時刻部分の値を結合し、DB保存用の 'Y-m-d H:i:s.v' 形式に変換します。
     * 日付が空または不正な場合は null を返します。時刻が空の場合は 00:00:00.000 として扱います。
     *
     * @param mixed $excelDateValue Excelから読み取った日付部分の値 (数値または文字列)
     * @param mixed $excelTimeValue Excelから読み取った時刻部分の値 (数値または文字列)
     * @param bool $includeMilliseconds ミリ秒を含むかどうか (デフォルト: true)
     * @return string|null 変換後の日時文字列、変換不可や日付が空の場合はnull
     */
    public static function combineExcelDateAndTimeToDbDateTime($excelDateValue, $excelTimeValue, bool $includeMilliseconds = true): ?string
    {
        if (($excelDateValue === null || (is_string($excelDateValue) && trim($excelDateValue) === '')) && $excelDateValue !== 0 && $excelDateValue !== '0') {
            return null; // 日付が完全に空ならnull
        }

        $datePart = null;
        $timeString = '00:00:00';
        $millisecondsString = '000';

        // 日付部分の処理
        if (is_numeric($excelDateValue)) {
            try {
                $dtObj = PhpSpreadsheetDate::excelToDateTimeObject($excelDateValue);
                $datePart = $dtObj->format('Y-m-d');
                 // もし日付シリアル値に時刻部分も含まれている場合、時刻もここから取得する方が正確な場合がある
                 // if (strpos((string)$excelDateValue, '.') !== false) { // 小数点があれば時刻も含む可能性
                 //    $timeString = $dtObj->format('H:i:s');
                 //    $millisecondsString = $dtObj->format('v');
                 // }
            } catch (\Exception $e) { /* 変換失敗時は無視 */ }
        } elseif (is_string($excelDateValue) && trim($excelDateValue) !== '') {
            try {
                $dtObj = new \DateTime(str_replace(['/', '.'], '-', trim($excelDateValue)));
                $datePart = $dtObj->format('Y-m-d');
            } catch (\Exception $e) { /* 変換失敗時は無視 */ }
        }
        
        if ($datePart === null) return null; // 日付が正しくパースできなければnull

        // 時刻部分の処理 (excelTimeValueが提供されていればそれを優先)
        if (!($excelTimeValue === null || (is_string($excelTimeValue) && trim($excelTimeValue) === ''))) {
            if (is_numeric($excelTimeValue)) { 
                try {
                    // 時刻シリアル値の場合、日付部分は無視して時刻のみ取得
                    $dtObj = PhpSpreadsheetDate::excelToDateTimeObject($excelTimeValue);
                    $timeString = $dtObj->format('H:i:s');
                    if ($includeMilliseconds) {
                        $millisecondsString = $dtObj->format('v');
                    }
                } catch (\Exception $e) { /* デフォルト時刻のまま */ }
            } elseif (is_string($excelTimeValue) && trim($excelTimeValue) !== '') {
                try {
                    $dtObj = new \DateTime('1970-01-01 ' . trim($excelTimeValue)); // 日付部分はダミーで時刻をパース
                    $timeString = $dtObj->format('H:i:s');
                    if ($includeMilliseconds) {
                        $millisecondsString = $dtObj->format('v');
                    }
                } catch (\Exception $e) { /* デフォルト時刻のまま */ }
            }
        }
        // else: $excelTimeValueが空なら、初期値の $timeString = '00:00:00', $millisecondsString = '000' が使われる

        if ($includeMilliseconds) {
            return $datePart . ' ' . $timeString . '.' . $millisecondsString;
        } else {
            return $datePart . ' ' . $timeString;
        }
    }

    /**
     * 値を DECIMAL 型に適した文字列 (指定スケール) または null に変換します。
     * 空、または数値として解釈できない場合は null を返します。
     *
     * @param mixed $value 変換する値
     * @param int $scale 小数点以下の桁数 (デフォルト: 2)
     * @return string|null 変換後の数値文字列、または変換不可の場合はnull
     */
    public static function excelToDecimalOrNull($value, int $scale = 2): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        // 全角数字やカンマを半角・除去する場合
        if (is_string($value)) {
            $value = mb_convert_kana(trim($value), 'n'); // 半角カナも数字に
            $value = str_replace(',', '', $value);
        }

        if (!is_numeric($value)) {
            // log_message('debug', "DataTransformer: Value '{$value}' could not be converted to numeric for decimal, returning null.");
            return null; 
        }
        return number_format((float)$value, $scale, '.', ''); // 小数点以下を指定桁数にし、桁区切りなし
    }

    /**
     * 値を整数 (int) に変換します。
     * 空、または整数として解釈できない場合は null を返します。
     *
     * @param mixed $value 変換する値
     * @return int|null 変換後の整数、または変換不可の場合はnull
     */
    public static function excelToIntOrNull($value): ?int
    {
        // ユーザー提供のロジックをベースに、可読性を少し調整
        if ($value === null) return null;
        $trimmedValue = trim((string)$value);
        if ($trimmedValue === '') return null;

        // 全角数字を半角に
        $trimmedValue = mb_convert_kana($trimmedValue, 'n');
        
        // 整数として妥当か (先頭に +/- があってもよく、数字のみで構成されるか、または小数点以下がない数値)
        if (filter_var($trimmedValue, FILTER_VALIDATE_INT) !== false) {
            return (int)$trimmedValue;
        }
        // filter_varでダメでも、"1.00"のような形式を整数とみなしたい場合
        if (is_numeric($trimmedValue) && (float)$trimmedValue == (int)(float)$trimmedValue) {
             return (int)(float)$trimmedValue;
        }
        
        // log_message('debug', "DataTransformer: Value '{$value}' (trimmed: '{$trimmedValue}') could not be reliably converted to int, returning null.");
        return null;
    }
    
    /**
     * 値をトリムし、文字列として返します。VARCHAR/NVARCHAR 型カラム用です。
     * 元の値が null や空文字列の場合、結果は空文字列 ('') になります。
     *
     * @param mixed $value 変換する値
     * @return string トリムされた文字列 (空文字列の場合も含む)
     */
    public static function excelToStringOrEmpty($value): string
    {
        return trim((string)($value ?? ''));
    }



    /**
     * Excelの時刻値をデータベース保存用の TIME型文字列 (HH:MM:SS) に変換します。
     *
     * @param mixed $excelValue Excel    /**
     * Excelの時刻値をデータベース保存用の TIME型文字列 (HH:MM:SS) に変換します。
     *
     * @param mixed $excelValue Excelから読み取った時刻値 (数値または時刻文字列)
     * @return string|null 変換後の時刻文字列 (HH:MM:SS形式)、変換不可または入力が空の場合はnull
     */
    public static function excelToDbTime($excelValue): ?string
    {
        if ($excelValue === null || (is_string($excelValue) && trim($excelValue) === '')) {
            return null;
        }
        
        try {
            if (is_numeric($excelValue)) {
                // Excelの時刻シリアル値の場合 (0.0〜1.0の範囲)
                if ($excelValue >= 0 && $excelValue < 1) {
                    $seconds = $excelValue * 24 * 60 * 60;
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = floor($seconds % 60);
                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                } else {
                    // 1以上の場合は日付も含む可能性があるので、時刻部分のみ抽出
                    $dt = PhpSpreadsheetDate::excelToDateTimeObject($excelValue);
                    return $dt->format('H:i:s');
                }
            } else {
                // 文字列の時刻の場合 (例: "10:30:00", "10:30")
                $timeString = trim((string)$excelValue);
                
                // 既に HH:MM:SS 形式の場合
                if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $timeString)) {
                    // 秒が無い場合は :00 を追加
                    if (substr_count($timeString, ':') === 1) {
                        $timeString .= ':00';
                    }
                    
                    // 時刻の妥当性をチェック
                    $timeParts = explode(':', $timeString);
                    if (count($timeParts) === 3 && 
                        $timeParts[0] >= 0 && $timeParts[0] <= 23 &&
                        $timeParts[1] >= 0 && $timeParts[1] <= 59 &&
                        $timeParts[2] >= 0 && $timeParts[2] <= 59) {
                        return sprintf('%02d:%02d:%02d', $timeParts[0], $timeParts[1], $timeParts[2]);
                    }
                }
                
                // その他の形式の場合はDateTimeでパース試行
                $dt = new \DateTime('1970-01-01 ' . $timeString);
                return $dt->format('H:i:s');
            }
        } catch (\Exception $e) {
            // log_message('debug', "DataTransformer: Failed to parse time '{$excelValue}'. Error: {$e->getMessage()}");
            return null;
        }
    }


}