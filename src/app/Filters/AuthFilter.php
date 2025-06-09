<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            // 現在のURLを元URL（intended URL）としてセッションに保存
            $currentUrl = current_url();
            $queryString = $request->getUri()->getQuery();
            if ($queryString) {
                $currentUrl .= '?' . $queryString;
            }
            
            $session->set('intended_url', $currentUrl);
            
            // ログインページにリダイレクト
            return redirect()->to(site_url('login'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 後処理は不要
    }
}