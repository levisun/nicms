<?php

/**
 *
 * 控制层
 * 访问记录API
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

use think\Response;
use app\api\logic\Async;
use app\common\library\AccessLog;

class Record extends Async
{

    public function index()
    {
        if ($this->request->isGet() && $this->validate->referer()) {
            AccessLog::record();

            return Response::create()->allowCache(true)
                ->cacheControl('max-age=30,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + 30) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + 30) . ' GMT')
                ->contentType('application/javascript');
        }

        return miss(404);
    }
}
