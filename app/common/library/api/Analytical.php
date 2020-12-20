<?php

/**
 *
 * 解析
 *
 * @package   NICMS
 * @category  app\common\library\api
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\api;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;

use app\common\library\Base64;
use app\common\library\Filter;
use app\common\model\ApiApp as ModelApiApp;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

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
     * APP密钥
     * @var string
     */
    public $appAuthKey = 'user_auth_key';

    /**
     * session id
     * @var string
     */
    public $sessionId;

    /**
     * 解析method参数
     * @access public
     * @return void
     */
    public function method(): void
    {
        // 校验方法名格式
        $method = Request::param('method');
        if (!$method || !!!preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $method)) {


            $this->abort('The method parameter is empty or formatted incorrectly.', 25001);
        }

        // 解析方法名
        list($logic, $action, $method) = explode('.', strtolower($method), 3);
        $class  = '\app\\' . $this->appName . '\logic\\';
        $class .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
        $class .= $logic . '\\' . ucfirst($action);

        // 校验方法是否存在
        if (!class_exists($class)) {
            $this->abort('Method parameter error, this method could not be found.', 25002);
        }
        if (!method_exists($class, $method)) {
            $this->abort('Method parameter error, this method could not be found.', 25003);
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
     * 加载语言包
     * @access public
     * @return void
     */
    public function loadLang(): void
    {
        Lang::load([
            // 公众语言包
            root_path('app/common/lang') . Lang::getLangSet() . '.php',
            // API方法所属应用的语言包
            root_path('app/' . $this->appName . '/lang') . Lang::getLangSet() . '.php',
        ]);
    }

    /**
     * 验证APPID
     * @access public
     * @return void
     */
    public function appId(): void
    {
        $app_id = Request::param('appid/d', 0, 'abs');
        if (!$app_id || $app_id < 1000001) {
            $this->abort('The appid parameter is wrong.', 21001);
        }

        $app_id -= 1000000;
        $result = ModelApiApp::field('name, secret, authkey')
            ->where('id', '=', $app_id)
            ->where('status', '=', 1)
            ->cache('async app id' . $app_id)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $this->appName = $result['name'];
            $this->appSecret = sha1($result['secret'] . Base64::asyncSecret());
            $this->appAuthKey = $result['authkey'];
        } else {
            $this->abort('The appid parameter is wrong.', 21002);
        }
    }

    /**
     * 解析accept信息
     * version与format解析
     * @access public
     * @return void
     */
    public function accept(): void
    {
        $accept = (string) Request::header('accept', '');
        $pattern = '/^application\/vnd\.[a-zA-Z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[a-zA-Z0-9]+\+[a-zA-Z]{3,5}+$/u';
        if (!$accept || !!!preg_match($pattern, $accept)) {
            $this->abort('The accept parameter is wrong.', 20004);
        }



        // 过滤多余信息
        // application/vnd.nicms.v1.0.1+json
        $accept = str_replace('application/vnd.', '', $accept);
        // 校验域名合法性
        list($domain, $accept) = explode('.', $accept, 2);
        list($root) = explode('.', Request::rootDomain(), 2);
        if (!hash_equals($domain, $root)) {
            $this->abort('Accept parameter domain name error.', 20005);
        }
        unset($domain, $root);



        // 取得版本与数据类型
        list($version, $this->format) = explode('+', $accept, 2);
        if (!$version || !!!preg_match('/^[a-zA-Z0-9.]+$/u', $version)) {
            $this->abort('The accept parameter version is incorrect.', 20006);
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
            $this->abort('The accept parameter returns an error type.', 20007);
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
        $authorization = (string) Request::header('authorization', '');
        $authorization = str_replace('&#43;', '+', $authorization);
        $authorization = str_replace('Bearer ', '', $authorization);
        if (!$authorization || !!!preg_match('/^[\w\-]+\.[\w\-]+\.[\w\-]+$/u', $authorization)) {
            $this->abort('The authentication information is incorrect.', 20001);
        }

        // 校验authorization合法性
        $token = (new Parser)->parse($authorization);
        // 密钥
        // $key  = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        // $key = sha1(Base64::encrypt($key));

        $data = new ValidationData;
        $data->setIssuer(Request::rootDomain());
        $data->setAudience(parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST));
        $data->setId($token->getClaim('jti'));
        $data->setCurrentTime(Request::time() + 2880);

        if (false === $token->verify(new Sha256, Base64::asyncSecret()) || false === $token->validate($data)) {
            $this->abort('The authentication information is incorrect.', 20002);
        }



        // 校验session是否存在
        // Session初始化并规定sessionID
        $jti = Base64::decrypt($token->getClaim('jti'));
        $jti = Filter::safe($jti);
        if ($jti && is_file(runtime_path('session/' . Config::get('session.prefix')) . 'sess_' . $jti)) {
            $this->sessionId = $jti;
        } else {
            $this->abort('The authentication information is incorrect.', 20003);
        }
    }

    private function abort(string $_msg, int $_code)
    {
        $result = [
            'code'    => $_code,
            'message' => $_msg,
        ];
        $response = Response::create($result, 'json');
        ob_start('ob_gzhandler');
        throw new HttpResponseException($response);
    }
}
