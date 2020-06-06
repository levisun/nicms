<?php

/**
 *
 * 控制层
 * 图片验证码API
 *
 * @package   NICMS
 * @category  app\api\controller\verify
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\verify;

use think\Response;
use app\common\library\api\Async;
use think\captcha\facade\Captcha;

class Img extends Async
{

    /**
     * 图片验证码
     * @access public
     * @return
     */
    public function index()
    {
        if ($this->request->isGet() && $this->validate->referer()) {
            $this->ApiInit();
            $captcha = Captcha::create();
            $this->session->save();
            $captcha = 'data:image/png;base64,' . base64_encode($captcha->getContent());
            return Response::create($captcha)
                ->header([
                    'Content-Type'   => 'image/png',
                    'Content-Length' => strlen($captcha),
                    'X-Powered-By'   => 'NI API',
                ]);
        }

        return miss(404);
    }
}
