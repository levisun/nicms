<?php
/**
 *
 * 控制层
 * admin
 *
 * @package   NICMS
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\controller;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Lang;
use app\library\Rbac;
use app\library\Template;

class admin extends Template
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTheme('admin/' . Env::get('app.admin_theme', 'default'));
        $tpl_path = Config::get('app.cdn_host') . '/template/admin/' . Env::get('app.admin_theme', 'default') . '/';
        $this->setReplace([
            'theme' => $tpl_path,
            'css'   => $tpl_path . 'css/',
            'img'   => $tpl_path . 'img/',
            'js'    => $tpl_path . 'js/',
        ]);

        // 开启session
        $session = Config::get('session');
        $session['auto_start'] = true;
        Config::set($session, 'session');
        session_start();
        session_write_close();
    }

    /**
     * 主页
     * @access public
     * @param  string $_logic
     * @param  string $_controller
     * @param  string $_action
     * @return void
     */
    public function index(string $logic = 'account', string $controller = 'user', string $action = 'login')
    {
        // $api_url = 'https://dysmsapi.aliyuncs.com/?';

        // $data = array(
        //     'Signature'        => '',
        //     'AccessKeyId'      => 'LTAIXSdAPLSmsECt',
        //     'Action'           => 'SendSms',
        //     'Format'           => 'json',
        //     'SignatureMethod'  => 'HMAC-SHA1',
        //     'SignatureNonce'   => time() . rand(111111111, 999999999),
        //     'SignatureVersion' => '1.0',
        //     'Timestamp'        => gmdate('Y-m-d') . 'T' . gmdate('H:i:m') . 'Z',
        //     'Version'          => '2017-05-25',

        //     'SignName'         => '学霸无忧',
        //     'TemplateCode'     => 'SMS_163530537',
        //     'PhoneNumbers'     => '18629503709',
        //     'TemplateParam'    => '{"code":"' . 123456 . '"}',
        // );
        // ksort($data);
        // $str_sign = '';
        // foreach ($data as $key => $value) {
        //     if ($key != 'Signature') {
        //         $str_sign .= urlencode($key) . '=' . urlencode($value) . '&';
        //     }
        // }
        // $str_sign = rtrim($str_sign, '&');
        // echo $str_sign;
        // $signature = 'GET&' . urlencode('/') . '&' . urlencode($str_sign);
        // $signature = base64_encode(hash_hmac('sha1', $signature, 'ftagFtNscXpWJ26EpvDauyRMspNjQb&', true));
        // echo $api_url. 'Signature=' . urlencode($signature) . '&' . $str_sign;




        $this->__authenticate($logic, $controller, $action);

        $tpl  = $logic . DIRECTORY_SEPARATOR . $controller;
        $tpl .= $action ? DIRECTORY_SEPARATOR . $action : '';

        $this->fetch($tpl);
    }

    /**
     * 验证权限
     * @access private
     * @param  string $_logic
     * @param  string $_controller
     * @param  string $_action
     * @return void
     */
    private function __authenticate(string $_logic, string $_controller, string $_action): void
    {
        if (in_array($_logic, ['account']) && session('?admin_auth_key')) {
            $result = url('settings/info/index');
        } elseif (session('?admin_auth_key')) {
            $result =
            (new Rbac)->authenticate(
                session('admin_auth_key'),
                'admin',
                $_logic,
                $_controller,
                $_action,
                [
                    'not_auth_logic' => [
                        'account'
                    ]
                ]
            );
            $result = $result ? : url('settings/info/index');
        } elseif (!in_array($_logic, ['account'])) {
            $result = url('account/user/login');
        }

        if (isset($result) && is_string($result)) {
            $response = Response::create($result, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
