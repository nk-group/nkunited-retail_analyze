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
        [
            'username'    => 'united',
            'password'    => 'united@',
            'displayName' => 'NKユナイテッド',
        ],
        [
            'username'    => 'kida',
            'password'    => 'kida@',
            'displayName' => '木田 直樹',
        ],
        [
            'username'    => 's.miura',
            'password'    => 's.miura@',
            'displayName' => '三浦 里美',
        ],
        [
            'username'    => 't.miura',
            'password'    => 't.miura@',
            'displayName' => '三浦 孝明',
        ],
        // 必要に応じてユーザーを追加
    ];
}