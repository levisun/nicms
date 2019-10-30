<?php

/**
 *
 * 检查请求
 * 频繁或非法请求将被锁定
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use think\App;

class CheckRequest
{

    public function handle(App $_app)
    {
        $path = $_app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);
        $_app->log->record('[锁定]', 'alert')->save();

        $lock  = $path . md5($_app->request->ip() . date('Ymd')) . '.lock';
        if (is_file($lock)) {
            $_app->log->record('[锁定]', 'alert')->save();
            http_response_code(502);
            echo '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>502</title><section><h2>502</h2><h3>Oops! Something went wrong.</h3></section>';
            exit();
        }
    }
}
