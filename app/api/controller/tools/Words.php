<?php

/**
 *
 * API接口层
 * 分词
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use app\common\library\api\Async;
use app\common\library\tools\Participle;

class Words extends Async
{

    public function index()
    {
        if (!$this->validate->referer() || !$txt = $this->request->param('txt')) {
            return miss(404, false);
        }

        if (mb_strlen($txt, 'UTF-8') <= 500) {
            if (!$this->cache->has($txt) || !$result = $this->cache->get($txt)) {
                $participle = new Participle($txt);
                $this->cache->set($txt, $participle->result);
            }

            return $result
                ? $this->cache(true)->success('Words success', $result)
                : $this->error('Words error');
        }
    }
}
