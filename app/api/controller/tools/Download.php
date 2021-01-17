<?php

/**
 *
 * 控制层
 * 下载API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\tools\File;

class Download extends BaseApi
{

    public function index()
    {
        if (!$this->validate->referer() || !$file = $this->request->param('file')) {
            return miss(404, false);
        }

        if ($file = File::pathDecode($file, true)) {
            // $ext = pathinfo($file, PATHINFO_EXTENSION);

            return Response::create($file, 'file')
                ->name(sha1(pathinfo($file, PATHINFO_FILENAME) . date('Ymd')))
                ->isContent(false)
                ->expire(28800);
        }
    }
}
