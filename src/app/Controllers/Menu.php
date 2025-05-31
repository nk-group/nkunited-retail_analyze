<?php

namespace App\Controllers;

// BaseController を継承
class Menu extends BaseController
{
    public function __construct()
    {
        // セッションは BaseController またはここでロード/初期化できます。
        // BaseController で $this->session = \Config\Services::session(); されているか、
        // グローバル関数 session() を直接使用します。
        // このコントローラはフィルターで保護されるので、ここでは直接的な認証チェックは省略。
    }

    public function index()
    {
        $data = [
            'pageTitle'   => 'メインメニュー - ' . (getenv('app.name') ?: 'アプリケーション'),
        ];
        return view('menu_view', $data);
    }
}