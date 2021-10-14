<?php

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath(),
        ],
		'extend' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'extend/',
            // 磁盘路径对应的外部URL路径
            'url'        => '',
            // 可见性
            'visibility' => 'private',
        ],
		'config' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'config/',
            // 磁盘路径对应的外部URL路径
            'url'        => '',
            // 可见性
            'visibility' => 'private',
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/',
            // 磁盘路径对应的外部URL路径
            'url'        => '',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];
