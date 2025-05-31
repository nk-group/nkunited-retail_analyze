<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\SimpleAuth; // 作成した認証設定ファイルを読み込み

class Login extends Controller
{
    protected $helpers = ['form', 'url'];
    protected $session;

    public function __construct()
    {
        $this->session = \Config\Services::session();
    }



    public function index()
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to(site_url('menu'));
        }

        $data = [
            'pageTitle'  => 'ログイン - ' . (getenv('app.name') ?: 'アプリケーション'),
        ];
        return view('login_form', $data);
    }


    /**
     * ログイン試行処理 (複数ユーザー対応)
     */
    public function attempt()
    {
        $authConfig = new SimpleAuth();
        $request = $this->request;

        $submittedUsername = $request->getPost('username');
        $submittedPassword = $request->getPost('password');
        $loggedIn = false;
        $userSessionData = []; // セッションに保存するユーザー情報

        // SimpleAuth設定ファイル内の$users配列をループして検証
        foreach ($authConfig->users as $userAccount) {
            if ($submittedUsername === $userAccount['username'] && $submittedPassword === $userAccount['password']) {
                // ユーザー名とパスワードが一致
                // 本番環境では password_verify() を使用してください
                $userSessionData = [
                    'username'    => $userAccount['username'],
                    'displayName' => $userAccount['displayName'],
                    'isLoggedIn'  => true,
                ];
                $this->session->set($userSessionData);
                $loggedIn = true;
                break; // ログイン成功したらループを抜ける
            }
        }

        if ($loggedIn) {
            return redirect()->to(site_url('menu')); // メニューページへリダイレクト
        } else {
            // ログイン失敗
            $this->session->setFlashdata('error', 'ユーザー名またはパスワードが無効です。');
            return redirect()->to(site_url('login'));
        }
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to(site_url('login'));
    }
}