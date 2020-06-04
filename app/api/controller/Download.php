<?php

/**
 *
 * 控制层
 * 下载API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\Async;
use app\common\library\Download as DownloadFile;

class Download extends Async
{

    public function index()
    {
        if ($this->validate->referer()) {
            if ($file = $this->request->param('file', false)) {
                return DownloadFile::file($file);
            }
        }

        return miss(404);
    }
}
