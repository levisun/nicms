<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\library\api
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\Response;
use think\exception\HttpResponseException;

use app\common\library\Base64;
use app\common\library\Filter;
use app\common\library\Rbac;
use app\common\model\ApiApp as ModelApiApp;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

abstract class BaseApi
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Cache实例
     * @var \think\Cache
     */
    protected $cache;

    /**
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

    /**
     * log实例
     * @var \think\Log
     */
    protected $log;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * session实例
     * @var \think\Session
     */
    protected $session;


    /**
     * 不用验证
     * @var array
     */
    protected $validateNotAuth = [
        'not_auth_action' => [
            'auth',
            'profile',
            'notice'
        ]
    ];

    /**
     * 开启版本控制
     * 默认关闭
     * @var bool
     */
    protected $openVersion = false;

    /**
     * 版本号
     * 解析[accept]获得
     * @var array
     */
    protected $version = [
        // 'paradigm' => '1',
        'major'    => 1,
        'minor'    => 0,
        'revision' => 0,
    ];

    /**
     * 返回数据类型
     * 解析[accept]获得
     * @var string
     */
    protected $format = 'json';

    /**
     * 应用方法
     * @var array
     */
    protected $appMethod = [
        'logic'  => null,
        'method' => null,
        'action' => null,
        'class'  => null
    ];

    /**
     * 应用名
     * @var string
     */
    protected $appName = '';

    /**
     * APP密钥
     * @var string
     */
    protected $appSecret;

    /**
     * APP密钥
     * @var string
     */
    protected $appAuthKey = 'user_auth_key';

    /**
     * session id
     * @var string
     */
    protected $sessionId;

    /**
     * 浏览器数据缓存开关
     * @var bool
     */
    protected $apiCache = false;

    /**
     * 浏览器数据缓存时间
     * @var int
     */
    protected $apiExpire = 1440;

    /**
     * 用户ID
     * @var int
     */
    protected $userId = 0;

    /**
     * 用户组ID
     * @var int
     */
    protected $userRoleId = 0;

    /**
     * 用户类型(用户或管理员)
     * @var string
     */
    protected $userType = 'guest';

    /**
     * 构造方法
     * @access public
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = &$_app;
        $this->cache   = &$this->app->cache;
        $this->config  = &$this->app->config;
        $this->lang    = &$this->app->lang;
        $this->log     = &$this->app->log;
        $this->request = &$this->app->request;
        $this->session = &$this->app->session;

        // 请勿开启调试模式
        $this->app->debug(false);
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\Filter::strict');

        // 请勿更改参数(超时,执行内存)
        @ignore_user_abort(false);
        @set_time_limit(60);
        @ini_set('max_execution_time', '60');
        @ini_set('memory_limit', '16M');

        $this->initialize();
    }

    public function __destruct()
    {
        ignore_user_abort(false);
    }

    public function __get(string $_name)
    {
        return $this->$_name;
    }

    public function __set(string $_name, string $_value)
    {
        $this->$_name = $_value;
    }

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {
    }

    /**
     * 运行
     * @access protected
     * @return $this
     */
    protected function run(): array
    {
        $this->ApiInit();
        $this->AnalyticalMethod();
        $this->ValidateRBAC();

        // 执行METHOD获得返回数据
        $result = call_user_func([
            $this->app->make($this->appMethod['class']),
            $this->appMethod['method']
        ]);

        // 校验返回数据
        if (!is_array($result) || !array_key_exists('msg', $result)) {
            $this->abort('The data was returned in the wrong format.', 28001);
        }

        // 缓存(缓存时间) true or int 单位秒
        // 返回数据没有指定默认开启
        $this->cache(isset($result['cache']) ? $result['cache'] : true);

        $result['data'] = isset($result['data']) ? $result['data'] : [];
        $result['code'] = isset($result['code']) ? $result['code'] : 10000;

        return $result;
    }

    /**
     * 开启或关闭缓存
     * 当调试模式开启时,缓存一律关闭
     * @access protected
     * @param  int|false $_cache
     * @return $this
     */
    protected function cache($_expire)
    {
        // 开启或关闭缓存
        if (is_bool($_expire)) {
            $this->apiCache = !!$_expire;
        }

        // 指定缓存时间(int类型)
        elseif (is_numeric($_expire)) {
            $_expire = (int) $_expire;
            $this->apiExpire = $_expire ?: $this->apiExpire;
            $this->apiCache = true;
        }

        return $this;
    }

    /**
     * API初始化
     * 请勿修改执行顺序
     * @access protected
     * @return void
     */
    protected function ApiInit(): void
    {
        $this->openVersion = false;

        $this->ValidateTimestamp();
        $this->ValidateReferer();

        $this->AnalyticalAuthorization();
        $this->AnalyticalAccept();
        $this->AnalyticalAppId();
        $this->AnalyticalLoadLang();

        $this->ValidateSign();

        // 设置会话信息(用户ID,用户组)
        $this->session->setId($this->sessionId);
        $this->session->init();
        $this->request->withSession($this->session);
        if ($this->session->has($this->appAuthKey)) {
            $this->userId = (int) $this->session->get($this->appAuthKey);
            $this->userRoleId = (int) $this->session->get($this->appAuthKey . '_role');
            $this->userType = $this->appAuthKey == 'user_auth_key' ? 'user' : 'admin';
        }

        $this->ValidateFromToken();
    }



    /**
     * 解析method参数
     * @access public
     * @return void
     */
    public function AnalyticalMethod(): void
    {
        // 校验方法名格式
        $method = $this->request->param('method');
        if (!$method || !!!preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $method)) {
            trace($method, 'warning');
            $this->abort('The method parameter is empty or formatted incorrectly.', 25001);
        }

        // 解析方法名
        list($logic, $action, $method) = explode('.', strtolower($method), 3);
        $class  = '\app\\' . $this->appName . '\logic\\';
        $class .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
        $class .= $logic . '\\' . ucfirst($action);

        // 校验方法是否存在
        if (!class_exists($class)) {
            trace($class, 'warning');
            $this->abort('Method parameter error, this method could not be found.', 25002);
        }
        if (!method_exists($class, $method)) {
            trace($class . '::' . $method, 'warning');
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
    public function AnalyticalLoadLang(): void
    {
        $this->lang->load([
            // 公众语言包
            root_path('app/common/lang') . $this->lang->getLangSet() . '.php',
            // API方法所属应用的语言包
            root_path('app/' . $this->appName . '/lang') . $this->lang->getLangSet() . '.php',
        ]);
    }

    /**
     * 验证APPID
     * @access public
     * @return void
     */
    public function AnalyticalAppId(): void
    {
        $app_id = $this->request->param('appid/d', 0, 'abs');
        if (!$app_id || $app_id < 1000001) {
            trace($app_id, 'warning');
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
            trace($app_id, 'warning');
            $this->abort('The appid parameter is wrong.', 21002);
        }
    }

    /**
     * 解析accept信息
     * version与format解析
     * @access public
     * @return void
     */
    public function AnalyticalAccept(): void
    {
        // application/vnd.xxx.v1.0.1+json
        $accept = (string) $this->request->header('accept', '');
        $pattern = '/^application\/vnd\.[a-zA-Z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[a-zA-Z0-9]+\+[a-zA-Z]{3,5}+$/u';
        if (!$accept || !!!preg_match($pattern, $accept)) {
            trace($accept, 'warning');
            $this->abort('The accept parameter is wrong.', 20004);
        }



        // 过滤多余信息
        $accept = str_replace('application/vnd.', '', $accept);

        // 校验应用合法性
        list($app_name, $accept) = explode('.', $accept, 2);
        if (!hash_equals($app_name, $this->config->get('app.app_name'))) {
            trace($app_name, 'warning');
            $this->abort('Accept parameter app name error.', 20005);
        }
        unset($app_name);

        // 取得版本与数据类型
        list($version, $this->format) = explode('+', $accept, 2);
        if (!$version || !!!preg_match('/^v[\d.]+$/u', $version)) {
            trace($version, 'warning');
            $this->abort('The accept parameter version is incorrect.', 20006);
        }
        // 去掉"v"
        $version = substr($version, 1);
        list($major, $minor, $revision) = explode('.', $version, 3);
        $this->version = [
            'major'    => (int) $major,
            'minor'    => (int) $minor,
            'revision' => (int) $revision,
        ];
        unset($version, $major, $minor, $revision);

        // 校验返回数据类型
        if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
            trace($this->format, 'warning');
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
    public function AnalyticalAuthorization(): void
    {
        $authorization = (string) $this->request->header('authorization', '');
        $authorization = str_replace('&#43;', '+', $authorization);
        $authorization = str_replace('Bearer ', '', $authorization);
        if (!$authorization || !!!preg_match('/^[\w\-]+\.[\w\-]+\.[\w\-]+$/u', $authorization)) {
            trace($authorization, 'warning');
            $this->abort('The authentication information is incorrect.', 20001);
        }

        // 校验authorization合法性
        $token = (new Parser)->parse($authorization);
        $data = new ValidationData;
        $data->setIssuer($this->request->rootDomain());
        $data->setAudience(parse_url($this->request->server('HTTP_REFERER'), PHP_URL_HOST));
        $data->setId($token->getClaim('jti'));
        $data->setCurrentTime($this->request->time() + 1440);

        if (false === $token->verify(new Sha256, Base64::asyncSecret()) || false === $token->validate($data)) {
            trace($authorization, 'warning');
            $this->abort('The authentication information is incorrect.', 20002);
        }

        // 校验session是否存在
        // Session初始化并规定sessionID
        $jti = Base64::decrypt($token->getClaim('jti'));
        $jti = Filter::strict($jti);
        if ($jti && is_file(runtime_path('session/' . $this->config->get('session.prefix')) . 'sess_' . $jti)) {
            $this->sessionId = $jti;
        } else {
            trace($jti, 'warning');
            $this->abort('The authentication information is incorrect.', 20003);
        }
    }



    /**
     * 验证签名类型与签名合法性
     * @access protected
     * @return void
     */
    protected function ValidateSign(): void
    {
        // 校验签名类型
        $sign_type = $this->request->param('sign_type', 'md5');
        if (!function_exists($sign_type)) {
            $this->abort('The signature type is wrong.', 22001);
        }

        // 校验签名合法性
        $sign = $this->request->param('sign');
        if (!$sign || !!!preg_match('/^[A-Za-z0-9]+$/u', $sign)) {
            $this->abort('The signature is wrong.', 22002);
        }

        // 获得原始数据
        $params = $this->request->param('', '', 'trim');
        $params = array_merge($params, $_FILES);
        ksort($params);

        $str = '';
        foreach ($params as $key => $value) {
            if ('sign' == $key) {
                continue;
            } elseif (is_array($value)) {
                continue;
            } elseif (is_numeric($value) || $value) {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str  = rtrim($str, '&');
        $str .= $this->appSecret;

        if (!hash_equals(call_user_func($sign_type, $str), $sign)) {
            $this->abort('The signature is wrong.', 22003);
        }
    }

    /**
     * 验证请求时间
     * @access protected
     * @return void
     */
    protected function ValidateTimestamp(): bool
    {
        $timestamp = $this->request->param('timestamp/d', $this->request->time(), 'abs');
        if ($timestamp <= strtotime('-1 minutes') && $timestamp >= strtotime('+30 seconds')) {
            $this->abort('The request timed out.', 23001);
        }

        return true;
    }

    /**
     * 验证表单令牌
     * @access protected
     * @return void
     */
    protected function ValidateFromToken(): bool
    {
        if ($this->request->isPost() && false === $this->request->checkToken()) {
            $this->abort('The request form token is wrong.', 24002);
        }

        return true;
    }

    /**
     * 验证请求来源
     * @access protected
     * @return bool
     */
    protected function ValidateReferer(): bool
    {
        $referer = $this->request->server('HTTP_REFERER');
        if (!$referer || false === stripos($referer, $this->request->rootDomain())) {
            $this->abort('The request was incorrect.', 24001);
        }

        return true;
    }

    /**
     * 验证权限
     * @access protected
     * @return void
     */
    protected function ValidateRBAC(): void
    {
        // 需要鉴权应用
        if (in_array($this->appName, ['admin', 'user'])) {
            // 不需要鉴权方法(登录 登出 找回密码)
            if (!in_array($this->appMethod['method'], ['login', 'logout', 'forget'])) {
                // 验证权限
                $result = (new Rbac)->authenticate(
                    $this->userId,
                    $this->appName,
                    $this->appMethod['logic'],
                    $this->appMethod['action'],
                    $this->appMethod['method'],
                    $this->validateNotAuth
                );
                if (false === $result) {
                    $this->abort('No action permissions.', 26001);
                }
            }
        }
    }

    /**
     * 操作成功
     * @access protected
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为10000
     * @return void
     */
    protected function success(string $_msg, $_data = '', int $_code = 10000): Response
    {
        return $this->response($_msg, $_data, $_code);
    }

    /**
     * 操作失败
     * 10000 成功
     * 200xx 权限|授权|参数等错误
     * 3000x 请求类型等错误
     * 40001 缺少参数
     * 40002 错误请求
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function error(string $_msg, int $_code = 40001): Response
    {
        return $this->cache(false)->response($_msg, [], $_code);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   返回码
     * @return Response
     */
    protected function response(string $_msg, $_data = '', int $_code = 10000): Response
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => $_msg,
            'runtime' => date('Y-m-d H:i:s') . ', ' .
                number_format(microtime(true) - $this->app->getBeginTime(), 3) . ', ' .
                number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3) . ', ' .
                $this->app->db->getQueryTimes() . ', ' .
                // count(get_included_files()) . ', ' .
                (true === $this->apiCache ? $this->apiExpire : 'off'),

            // 返回地址
            'return_url' => $this->session->has('return_url')
                ? $this->session->pull('return_url')
                : '',

            // 新表单令牌
            'from_token' => $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '',
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->format)->allowCache(false);

        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
                ->expires(gmdate('D, d M Y H:i:s', time() + $this->apiExpire) . ' GMT');
        }

        ob_start('ob_gzhandler');

        return $response;
    }

    /**
     * 抛出异常
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function abort(string $_msg, int $_code = 40001): void
    {
        $result = [
            'code'    => $_code,
            'message' => $_msg,

            // 返回地址
            'return_url' => $_code > 21000 && $this->session->has('return_url')
                ? $this->session->pull('return_url')
                : '',

            // 新表单令牌
            'from_token' => $_code > 21000 && $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '',
        ];

        $result = array_filter($result);

        $response = Response::create($result, 'json')->allowCache(false);

        ob_start('ob_gzhandler');

        throw new HttpResponseException($response);
    }
}
