<?php
use think\facade\Env;
return [
    'default' => Env::get('filesystem.driver', 'local'),
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            'type'       => 'local',
            'root'       => app()->getRootPath() . 'public/storage',
            'url'        => '/storage',
            'visibility' => 'public',
        ],
        // 上传
        'uploads' => [
            'type'       => 'local',
            'root'       => app()->getRootPath() . 'public/storage/uploads/' . date('Ym'),
            'url'        => '/storage/uploads/' . date('Ym'),
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];
