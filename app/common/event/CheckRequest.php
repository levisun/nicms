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

class CheckRequest
{

    public function handle()
    {
        // $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        // is_dir($path) or mkdir($path, 0755, true);
        app('log')->record('[锁定] 锁定', 'alert');

        // $lock  = $path . md5(app('request')->ip() . date('Ymd')) . '.lock';
        // if (is_file($lock)) {
        //     app('log')->record('[锁定]', 'alert')->save();
        //     http_response_code(502);
        //     echo file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '502.html');
        //     exit();
        // }
    }
}
