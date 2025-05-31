<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database; // データベース設定を読み込むために必要

class DbTest extends BaseController
{
    public function index()
    {
        $db = null; // 初期化
        $dbConnected = false;
        $dbErrorMessage = '';
        $sqlServerVersion = '';

        try {
            // デフォルトのデータベースグループに接続
            // .env ファイルまたは app/Config/Database.php の設定が使用されます
            $db = Database::connect();

            // 接続を試行 (実際にクエリを実行することで接続を確認)
            $query = $db->query('SELECT @@VERSION AS version');
            if ($query) {
                $dbConnected = true;
                $result = $query->getRow();
                if ($result) {
                    $sqlServerVersion = $result->version;
                } else {
                    $dbErrorMessage = 'バージョン情報の取得に失敗しました。';
                }
            } else {
                // $db->query が false を返した場合、接続エラーの可能性が高い
                // CodeIgniter 4.x の $db->connect() はすぐには例外を投げないことがあるため、
                // 実際のクエリで確認します。
                // より詳細なエラーは $db->error() で取得できる場合があります。
                $error = $db->error();
                $dbErrorMessage = 'データベースクエリの実行に失敗しました。';
                if ($error && isset($error['message'])) {
                    $dbErrorMessage .= ' エラー: ' . $error['message'];
                }
            }
        } catch (\Throwable $e) {
            // Database::connect() や $db->query() が例外を投げた場合
            $dbConnected = false;
            $dbErrorMessage = 'データベース接続中に例外が発生しました: ' . $e->getMessage();
        }

        // 結果をビューに渡すか、直接出力する
        echo "<h1>SQL Server 接続テスト</h1>";
        if ($dbConnected) {
            echo "<p style='color:green;'>データベースに正常に接続できました！</p>";
            echo "<p><strong>SQL Server のバージョン情報:</strong></p>";
            echo "<pre>" . esc($sqlServerVersion) . "</pre>";
        } else {
            echo "<p style='color:red;'>データベース接続に失敗しました。</p>";
            if (!empty($dbErrorMessage)) {
                echo "<p><strong>エラーメッセージ:</strong> " . esc($dbErrorMessage) . "</p>";
            }
            echo "<p>以下の点を確認してください:</p>";
            echo "<ul>";
            echo "<li><code>.env</code> ファイルのデータベース設定 (ホスト名、データベース名、ユーザー名、パスワード、ポート、DBDriver='SQLSRV')</li>";
            echo "<li>PHP の <code>sqlsrv</code> および <code>pdo_sqlsrv</code> 拡張機能が有効になっているか (phpinfo で確認)</li>";
            echo "<li>SQL Server が起動しており、TCP/IP 接続が有効で、指定したポート (通常 1433) でリッスンしているか</li>";
            echo "<li>ファイアウォールで SQL Server のポートが許可されているか</li>";
            echo "<li>指定したユーザーにデータベースへの接続権限があるか</li>";
            echo "</ul>";
        }

        // 接続を閉じる (省略可能、PHP スクリプト終了時に自動的に閉じられることが多い)
        if ($db && $db->connID) {
            $db->close();
        }
    }
}