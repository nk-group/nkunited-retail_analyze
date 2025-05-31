<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SimpleAuth extends BaseConfig
{
    public array $users = [
        [
            'username'    => 'admin',
            'password'    => 'password123', // 本番環境では必ずハッシュ化してください
            'displayName' => '管理者',
        ],
        [
            'username'    => 'user1',
            'password'    => 'userpass',
            'displayName' => '一般ユーザー1',
        ],
        [
            'username'    => 'ueda',
            'password'    => 'Password_',
            'displayName' => '上田　祐一',
        ],
        // 必要に応じてユーザーを追加
    ];
}