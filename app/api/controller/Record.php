<?php

/**
 *
 * 控制层
 * Api
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

use app\common\controller\Async;
use app\common\library\AccessLog;

class Record extends Async
{

    public function index()
    {
        (new AccessLog)->record();

        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    }
}
