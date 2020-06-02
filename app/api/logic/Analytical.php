<?php

/**
 *
 * 解析
 *
 * @package   NICMS
 * @category  app\api\logic
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\logic;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Request;
use app\common\library\Base64;
use app\common\library\DataFilter;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use app\common\model\ApiApp as ModelApiApp;


class Analytical
{
    /**
     * 开启版本控制
     * 默认关闭
     * @var bool
     */
    public $openVersion = false;

    /**
     * 版本号
     * 解析[accept]获得
     * @var array
     */
    public $version = [
        // 'paradigm' => '1',
        'major'    => '1',
        'minor'    => '0',
        'revision' => '',
    ];

    /**
     * 返回数据类型
     * 解析[accept]获得
     * @var string
     */
    public $format = 'json';

    /**
     * 应用名
     * @var string
     */
    public $appName = '';

    /**
     * APP密钥
     * @var string
     */
    public $appSecret;

    /**
     * 权限认证KEY
     * @var string
     */
    public $appAuthKey = 'user_auth_key';

    /**
     * 应用方法
     * @var array
     */
    public $appMethod = [
        'logic'  => null,
        'method' => null,
        'action' => null,
        'class'  => null
    ];

    /**
     * 用户ID
     * @var int
     */
    public $uid = 0;

    /**
     * 用户组ID
     * @var int
     */
    public $u_role = 0;

    /**
     * 用户类型(用户和管理员)
     * @var string
     */
    public $type = 'guest';

    /**
     * 验证APPID
     * @access public
     * @return void
     */
    public function appId(): void
    {
        $appId = Request::param('appid/d', 0, 'abs');
        if (!$appId || $appId < 1000001) {
            $response = Response::create(['code' => 21001, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        $appId -= 1000000;
        $result = ModelApiApp::field('name, secret, authkey')
            ->where([
                ['id', '=', $appId]
            ])
            ->cache('asyncappid' . $appId)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $this->appName = $result['name'];
            $this->appSecret = $result['secret'];
            $this->appAuthKey = $result['authkey'];
        } else {
            $response = Response::create(['code' => 21002, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
    }

    /**
     * 解析method参数
     * @access public
     * @return void
     */
    public function method(): void
    {
        // 校验方法名格式
        $method = Request::param('method');
        if (!$method || !preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $method)) {
            $response = Response::create(['code' => 25001, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        // 解析方法名
        list($logic, $action, $method) = explode('.', strtolower($method), 3);
        $class  = '\app\\' . $this->appName . '\logic\\';
        $class .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
        $class .= $logic . '\\' . ucfirst($action);

        // 校验方法是否存在
        if (!class_exists($class)) {
            $response = Response::create(['code' => 25002, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
        if (!method_exists($class, $method)) {
            $response = Response::create(['code' => 25003, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        // 记录方法
        $this->appMethod = [
            // 业务层
            'logic'  => $logic,
            // 方法类
            'action' => $action,
            // 执行方法
            'method' => $method,
            // 命名空间
            'class'  => $class,
        ];
    }

    /**
     * 解析accept信息
     * version与format解析
     * @access public
     * @return void
     */
    public function accept(): void
    {
        $accept = Request::header('accept');
        $pattern = '/^application\/vnd\.[A-Za-z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[a-zA-Z0-9]+\+[A-Za-z]{3,5}+$/u';
        if (!$accept || !preg_match($pattern, $accept)) {
            $response = Response::create(['code' => 20004, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }



        // 过滤多余信息
        // application/vnd.nicms.v1.0.1+json
        $accept = str_replace('application/vnd.', '', $accept);
        // 校验域名合法性
        list($domain, $accept) = explode('.', $accept, 2);
        list($root) = explode('.', Request::rootDomain(), 2);
        if (!hash_equals($domain, $root)) {
            $response = Response::create(['code' => 20005, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
        unset($domain, $root);



        // 取得版本与数据类型
        list($version, $this->format) = explode('+', $accept, 2);
        if (!$version || !preg_match('/^[a-zA-Z0-9.]+$/u', $version)) {
            $response = Response::create(['code' => 20006, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
        // 去掉"v"
        $version = substr($version, 1);
        list($major, $minor, $revision) = explode('.', $version, 3);
        $this->version = [
            'major'    => $major,
            'minor'    => $minor,
            'revision' => $revision,
        ];
        unset($version, $major, $minor, $revision);



        // 校验返回数据类型
        if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
            $response = Response::create(['code' => 20007, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
    }

    /**
     * 解析authorization信息
     * JWT校验
     * Session初始化
     * @access public
     * @return void
     */
    public function authorization(): void
    {
        $authorization = Request::header('authorization');
        $authorization = str_replace('&#43;', '+', $authorization);
        $authorization = str_replace('Bearer ', '', $authorization);
        if (!$authorization) {
            $response = Response::create(['code' => 20001, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        // 校验authorization合法性
        $token = (new Parser)->parse($authorization);
        // 密钥
        $key  = Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $data = new ValidationData;
        $data->setIssuer(Request::rootDomain());
        $data->setAudience(parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST));
        $data->setId($token->getClaim('jti'));
        $data->setCurrentTime(Request::time() + 60);

        if (false === $token->verify(new Sha256, $key) || false === $token->validate($data)) {
            $response = Response::create(['code' => 20002, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }



        // 校验session是否存在
        // Session初始化并规定sessionID
        $jti = Base64::decrypt($token->getClaim('jti'));
        $jti = DataFilter::filter($jti);
        if ($jti && is_file(root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . Config::get('session.prefix') . DIRECTORY_SEPARATOR . 'sess_' . $jti)) {
            $session = app('session');
            $session->setId($jti);
            $session->init();
            Request::withSession($session);
        } else {
            $response = Response::create(['code' => 20003, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
    }
}
