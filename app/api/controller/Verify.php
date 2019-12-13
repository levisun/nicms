<?php

/**
 *
 * 控制层
 * 验证码API
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
use app\common\controller\Async;
use think\captcha\facade\Captcha;

class Verify extends Async
{

    /**
     * 图片验证码
     * @access public
     * @return
     */
    public function img()
    {
        if ($this->analysis()->isReferer(false)) {
            $config = mt_rand(0, 1) ? 'verify_zh' : 'verify_math';
            $captcha = Captcha::create($config);
            $captcha = 'data:image/png;base64,' . base64_encode($captcha->getContent());
            return Response::create($captcha)
                ->header([
                    'X-Powered-By'   => 'NIAPI',
                    'Content-Length' => strlen($captcha)
                ])
                ->contentType('image/png');
        }

        return miss(404);
    }

    /**
     * 短信验证码
     * @access public
     * @return
     */
    public function sms()
    {
        if ($this->analysis()->isReferer()) {
            $phone = $this->request->param('phone', false);
            if ($phone && preg_match('/^1[3-9][0-9]\d{8}$/', $phone)) {
                $key = md5('sms_' . $this->request->ip());

                if ($this->session->has($key) && $result = $this->session->get($key)) {
                    if ($result['time'] >= time()) {
                        $this->cache(false)->success('请勿重复请求');
                    }
                }

                $this->session->set($key, [
                    'verify' => mt_rand(100000, 999999),
                    'time'   => time() + 120,
                    'phone'  => $phone,
                ]);
                return $this->cache(false)->success('验证码发送成功');
            } else {
                return $this->error('手机号错误', 40009);
            }
        }

        return miss(404);
    }

    public function check()
    {
        // sms
        if ($this->analysis()->isReferer()) {
            $phone = $this->request->param('phone', false);
            $verify = $this->request->param('verify/d', false);
            if ($phone && preg_match('/^1[3-9][0-9]\d{8}$/', $phone) && $verify) {
                $key = md5('sms_' . $this->request->ip());

                if ($this->session->has($key) && $result = $this->session->get($key)) {
                    if ($result['time'] >= time() && $result['verify'] == $verify && $result['phone'] == $phone) {
                        $this->session->delete($key);
                        return $this->cache(false)->success('验证码验证成功');
                    }
                }
            }
        }

        return miss(404);
    }
}
