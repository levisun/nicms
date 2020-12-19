<?php

/**
 *
 * 控制层
 * 访问记录API
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
use app\common\library\api\Async;
use app\common\library\Ipv4;
use app\common\model\Feedback as ModelFeedback;

class Record extends Async
{

    public function index()
    {
        if (!$this->validate->referer()) {
            return miss(404, false);
        }

        if ($content = $this->request->param('content')) {
            $ip = (new Ipv4)->get($this->request->ip());


            ModelFeedback::create([
                'title'   => $this->request->param('title', 'Record'),
                'content' => $this->request->param('content'),
                'lang'    => $this->lang->getLangSet(),
            ]);
        }
    }
}