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

use app\common\controller\AsyncController;

class Sms extends AsyncController
{

    public function index()
    {
        $phone = $this->request->param('phone', false);
        if ($phone && preg_match('/^1[3-9][0-9]\d{8}$/', $phone)) {
            $this->validate();

            $key = $this->session->has('client_token')
                ? $this->session->get('client_token')
                : $this->request->ip();
            $key = md5('sms_' . $key);

            if ($this->session->has($key) && $result = $this->session->get($key)) {
                if ($result['time'] >= time()) {
                    $this->openCache(false)->success('请勿重复请求');
                }
            }

            $this->session->set($key, [
                'verify' => mt_rand(100000, 999999),
                'time'   => time() + 120,
                'phone'  => $phone,
            ]);
            $this->openCache(false)->success('验证码发送成功');
        } else {
            $this->error('错误请求', 40009);
        }
    }

    public function check()
    {
        $phone = $this->request->param('phone', false);
        $verify = $this->request->param('verify/d', false);
        if ($phone && preg_match('/^1[3-9][0-9]\d{8}$/', $phone) && $verify) {
            $this->validate();

            $key = $this->session->has('client_token')
                ? $this->session->get('client_token')
                : $this->request->ip();
            $key = md5('sms_' . $key);

            if ($this->session->has($key) && $result = $this->session->get($key)) {
                if ($result['time'] >= time() && $result['verify'] == $verify && $result['phone'] == $phone) {
                    $this->session->delete($key);
                    $this->openCache(false)->success('验证码验证成功');
                }
            }

            $this->openCache(false)->success('验证码错误');
        } else {
            $this->error('错误请求', 40009);
        }
    }
}
