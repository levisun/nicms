<?php

/**
 *
 * 控制层
 * 短信验证码API
 *
 * @package   NICMS
 * @category  app\api\controller\verify
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\verify;

use app\common\controller\BaseApi;

class Sms extends BaseApi
{

    /**
     * 短信验证码
     * @access public
     * @return
     */
    public function index()
    {
        $this->ApiInit();

        $phone = $this->request->param('phone', false);
        if ($phone && preg_match('/^1[3-9]\d{9}$/', $phone)) {
            $key = sha1('sms_' . $phone);

            if ($this->session->has($key) && $result = $this->session->get($key)) {
                if ($result['time'] >= time()) {
                    return $this->cache(false)->error('请勿重复请求', 40009);
                }
            }

            $captcha = mt_rand(100000, 999999);

            # TODO

            $this->session->set($key, [
                'captcha' => $captcha,
                'time'    => time() + 300,
                'phone'   => $phone,
            ]);
            return $this->cache(false)->success('验证码发送成功');
        } else {
            return $this->error('手机号错误', 40009);
        }
    }

    public function check()
    {
        $this->ApiInit();

        $phone = $this->request->param('phone', false);
        $verify = $this->request->param('verify/d', false);
        if ($phone && preg_match('/^1[3-9][0-9]\d{8}$/', $phone) && $verify) {
            $key = sha1('sms_' . $phone);

            if ($this->session->has($key) && $result = $this->session->get($key)) {
                if ($result['time'] >= time() && $result['verify'] == $verify && $result['phone'] == $phone) {
                    $this->session->delete($key);
                    return $this->cache(false)->success('验证成功');
                } else {
                    return $this->error('手机号或验证码错误', 40009);
                }
            }
        } else {
            return $this->error('手机号或验证码错误', 40009);
        }

        return miss(404, false);
    }
}
