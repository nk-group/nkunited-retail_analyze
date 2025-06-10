<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SimpleAuth extends BaseConfig
{
    public array $users = [
        [
            'username'    => 'admin',
            'password'    => 'password123',
            'displayName' => '管理者',
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
            'username'    => 'bizrobo',
            'password'    => 'bizRobo@8989',
            'displayName' => 'BizRobo!',
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
            'password'    => 't@Miura',
            'displayName' => '三浦 孝明',
        ],
        [
            'username'    => 'y.iwakawa',
            'password'    => 'y@Iwakawa',
            'displayName' => '岩川 洋二郎',
        ],
        [
            'username'    => 's.tanno',
            'password'    => 's@Tanno',
            'displayName' => '丹野 紗衣',
        ],
        [
            'username'    => 'h.satou',
            'password'    => 'h@Satou',
            'displayName' => '佐藤 大貴',
        ],
        [
            'username'    => 'm.watanabe',
            'password'    => 'm@Watanabe',
            'displayName' => '渡邊 万貴',
        ],
        [
            'username'    => 'n.nishiura',
            'password'    => 'n@Nishiura',
            'displayName' => '西浦 菜々美',
        ],
        [
            'username'    => 'y.oikawa',
            'password'    => 'y@Oikawa',
            'displayName' => '及川 ゆみ',
        ],
        [
            'username'    => 'a.ninomiya',
            'password'    => 'a@Ninomiya',
            'displayName' => '二宮 文音',
        ],
        [
            'username'    => 's.tsuchimoto',
            'password'    => 's@Tsuchimoto',
            'displayName' => '土本 咲',
        ],
        
        // 必要に応じてユーザーを追加
    ];
}