<?php
/**
 *
 * 事件定义文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

return [
    'bind'      => [
    ],
    'listen'    => [
        'AppInit'      => [],
        'AppBegin'     => [],
        'AppEnd'       => [],
        'LogLevel'     => [],
        'LogWrite'     => [],
        'ResponseSend' => [],
        'ResponseEnd'  => [
            'Maintain' => ['app\event\Maintain']
        ],
    ],
    'subscribe' => [
    ],
];
