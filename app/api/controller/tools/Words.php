<?php

/**
 *
 * API接口层
 * 分词
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use app\common\library\api\Async;

class Words extends Async
{

    public function index()
    {
        if ($text = $this->request->param('text', false)) {
            if (mb_strlen($text, 'UTF-8')) {
                $sort = $this->request->param('sort', 'DESC');
                $length = $this->request->param('length/d', false, 'abs');
                $words = words($text, $sort, $length);

                return $this->cache(true)->success('spider success', $words);
            }
        }


        return miss(404);
    }
}
