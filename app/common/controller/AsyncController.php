<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use app\common\library\Rbac;
use app\common\library\Base64;
use app\common\model\ApiApp as ModelApiApp;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

abstract class AsyncController
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
     * response实例
     * @var \think\Response
     */
    protected $response;

    /**
     * session实例
     * @var \think\Session
     */
    protected $session;


    /**
     * HEADER 指定接收类型
     * 包含[域名 版本 返回类型]
     * application/vnd.nicms.v1.0.1+json
     * @var string
     */
    protected $accept;

    /**
     * 开启版本控制
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
        'major'    => '1',
        'minor'    => '0',
        'revision' => '',
    ];

    /**
     * 返回数据类型
     * 解析[accept]获得
     * @var string
     */
    protected $format = 'json';

    /**
     * HEADER 授权信息
     * 包含[token sessid]
     * @var string
     */
    protected $authorization;

    /**
     * 签名类型
     * @var string
     */
    protected $signType;

    /**
     * 签名
     * @var string
     */
    protected $sign;

    /**
     * APPID
     * @var int
     */
    protected $appId;

    /**
     * APP密钥
     * @var string
     */
    protected $appSecret;

    /**
     * 应用名
     * @var string
     */
    protected $appName = '';

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
     * 调试信息
     * @var array
     */
    protected $debugLog = [];

    /**
     * 调试开关
     * @var bool
     */
    protected $apiDebug = false;

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
     * 请求时间戳
     * @var int
     */
    protected $timestamp;

    /**
     * API方法
     * @var string
     */
    protected $method;

    /**
     * 权限认证KEY
     * @var string
     */
    protected $appAuthKey = 'user_auth_key';

    /**
     * uid
     * @var int
     */
    protected $uid = 0;
    protected $urole = 0;

    /**
     * 不用验证
     * @var array
     */
    protected $notAuth = [
        'not_auth_action' => [
            'auth',
            'profile',
            'notice'
        ]
    ];

    /**
     * 构造方法
     * @access public
     * @return void
     */
    public function __construct(App $_app)
    {
        $max_input_vars = (int) ini_get('max_input_vars');
        if (count($_POST) + count($_FILES) + count($_GET) >= $max_input_vars - 5) {
            $this->error('非法参数', 40002);
        }
        unset($max_input_vars);

        $this->app      = $_app;
        $this->cache    = $this->app->cache;
        $this->config   = $this->app->config;
        $this->lang     = $this->app->lang;
        $this->log      = $this->app->log;
        $this->request  = $this->app->request;
        // $this->response = $this->app->response;
        $this->session  = $this->app->session;

        // API中请勿开启调试模式
        $this->app->debug(false);
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\DataFilter::default');

        // 设置最小
        @ini_set('memory_limit', '8M');
        set_time_limit(10);
    }

    /**
     * 运行
     * @access protected
     * @return array
     */
    protected function run(): array
    {
        // 加载语言包
        $common_lang  = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $common_lang .= $this->lang->getLangSet() . '.php';
        $lang  = $this->app->getBasePath() . $this->appName . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $lang .= $this->lang->getLangSet() . '.php';
        $this->lang->load([$common_lang, $lang]);

        // 执行类方法
        $result = call_user_func([
            $this->app->make($this->appMethod['class']),
            $this->appMethod['method']
        ]);

        if (!is_array($result) && array_key_exists('msg', $result)) {
            $this->error('返回数据格式错误', 40001);
        }

        // 调试模式设置 返回数据没有指定默认关闭
        $this->debug(isset($result['debug']) ? $result['debug'] : false);
        // 缓存设置 返回数据没有指定默认开启
        $this->cache(isset($result['cache']) ? $result['cache'] : true);

        $result['data'] = isset($result['data']) ? $result['data'] : [];
        $result['code'] = isset($result['code']) ? $result['code'] : 10000;

        return $result;
    }

    /**
     * 开启调试
     * @access protected
     * @return $this
     */
    protected function debug(bool $_debug)
    {
        $this->apiDebug = $_debug;
        return $this;
    }

    /**
     * 设置缓存
     * @access protected
     * @return $this
     */
    protected function cache(bool $_cache)
    {
        $this->apiCache = (true === $this->apiDebug) ? false : $_cache;
        return $this;
    }

    /**
     * 验证
     * 40011 错误请求
     * @access protected
     * @return $this
     */
    protected function validate()
    {
        // 解析header数据
        $this->analysisHeader();
        // 校验APPID
        $this->checkAppId();
        // 校验签名
        $this->checkSign();
        // 校验时间戳
        $this->checkTimestamp();
        // 校验表单令牌
        $this->checkFromToken();
        // 解析method参数
        $this->analysisMethod();
        // 权限认证
        $this->checkAuth();

        // 检查客户端token
        // token由\app\common\controller\BaseController::class签发
        if (!$this->session->has('client_token')) {
            $this->error('非法参数', 30002);
        }

        return $this;
    }

    /**
     * 权限认证
     * @access protected
     * @return void
     */
    protected function checkAuth(): void
    {
        // 需要鉴权应用
        if (!in_array($this->appName, ['admin', 'my'])) {
            return;
        }

        // 不需要鉴权方法
        // 登录 登出 找回密码
        if (in_array($this->appMethod['action'], ['login', 'logout', 'forget'])) {
            return;
        }

        // 设置会话信息(用户ID,用户组)
        if ($this->session->has($this->appAuthKey) && $this->session->has($this->appAuthKey . '_role')) {
            $this->uid = (int) $this->session->get($this->appAuthKey);
            $this->urole = (int) $this->session->get($this->appAuthKey . '_role');
        }

        // 验证权限
        $result = (new Rbac)->authenticate(
            $this->uid,
            $this->appName,
            $this->appMethod['logic'],
            $this->appMethod['action'],
            $this->appMethod['method'],
            $this->notAuth
        );
        if (false === $result) {
            $this->error('非法参数', 30001);
        }
    }

    /**
     * 解析method参数
     * @access protected
     * @return void
     */
    protected function analysisMethod(): void
    {
        // 校验API方法
        $this->method = $this->request->param('method');
        if (!$this->method || !preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $this->method)) {
            $this->error('非法参数{20014}', 20014);
        }

        list($logic, $action, $method) = explode('.', $this->method, 3);

        $class  = '\app\\' . $this->appName . '\logic\\';
        $class .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
        $class .= $logic . '\\' . ucfirst($action);

        // 校验类是否存在
        if (!class_exists($class)) {
            $this->debugLog['method not found'] = $class;
            $this->log->record('[Async] method not found ' . $class, 'error');
            $this->error('非法参数{20015}', 20015);
        }
        // 校验类方法是否存在
        if (!method_exists($class, $method)) {
            $this->debugLog['action not found'] = $class . '->' . $method . '();';
            $this->log->record('[Async] action not found ' . $class . '->' . $method . '();', 'error');
            $this->error('非法参数{20016}', 20016);
        }

        $this->appMethod = [
            'logic'  => $logic,
            'action' => $action,
            'method' => $method,
            'class'  => $class,
        ];
    }

    /**
     * 校验表单令牌
     * @access protected
     * @return void
     */
    protected function checkFromToken(): void
    {
        // POST请求 表单令牌校验
        if ($this->request->isPost() && false === $this->request->checkToken()) {
            $this->error('非法参数{20013}', 20013);
        }
    }

    /**
     * 校验请求时间
     * @access protected
     * @return void
     */
    protected function checkTimestamp(): void
    {
        $this->timestamp = $this->request->param('timestamp/d', $this->request->time());
        if (!$this->timestamp || date('ymd', $this->timestamp) !== date('ymd')) {
            $this->error('非法参数{20012}', 20012);
        }
    }

    /**
     * 校验签名类型与签名合法性
     * @access protected
     * @return void
     */
    protected function checkSign(): void
    {
        // 校验签名类型
        $this->signType = $this->request->param('sign_type', 'md5');
        if (!$this->signType || !function_exists($this->signType)) {
            $this->debugLog['sign_type'] = $this->signType;
            $this->log->record('[Async] params-sign_type error', 'error');
            $this->error('非法参数{20009}', 20009);
        }

        // 校验签名合法性
        $this->sign = $this->request->param('sign');
        if (!$this->sign || !preg_match('/^[A-Za-z0-9]+$/u', $this->sign)) {
            $this->debugLog['sign'] = $this->sign;
            $this->log->record('[Async] params-sign error', 'error');
            $this->error('非法参数{20010}', 20010);
        }



        $params = $this->request->param('', '', 'trim');
        ksort($params);

        $str = '';
        $c_f = ['appid', 'sign_type', 'timestamp', 'method'];
        foreach ($params as $key => $value) {
            if (is_string($value) && !is_null($value) && in_array($key, $c_f)) {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str = rtrim($str, '&');
        $str .= $this->appSecret;



        if (!hash_equals(call_user_func($this->signType, $str), $this->sign)) {
            $this->debugLog['sign_str'] = $str;
            $this->debugLog['sign'] = call_user_func($this->signType, $str);
            $this->log->record('[Async] params-sign error', 'error');
            $this->error('非法参数{20011}', 20011);
        }
    }

    /**
     * 校验APPID
     * @access protected
     * @return void
     */
    protected function checkAppId(): void
    {
        $this->appId = $this->request->param('appid/f', 1000001);
        if (!$this->appId || $this->appId < 1000001) {
            $this->log->record('[Async] auth-appid not', 'error');
            $this->error('非法参数{20007}', 20007);
        }



        $this->appId -= 1000000;
        $result = (new ModelApiApp)
            ->field('name, secret, authkey')
            ->where([
                ['id', '=', $this->appId]
            ])
            ->cache('APPID' . $this->appId)
            ->find();

        if ($result) {
            $result = $result->toArray();
            $this->appName = $result['name'];
            $this->appSecret = $result['secret'];
            $this->appAuthKey = $result['authkey'];
        } else {
            $this->log->record('[Async] auth-appid error', 'error');
            $this->error('非法参数{20008}', 20008);
        }
    }

    /**
     * 解析header信息
     * JWT校验
     * Session初始化
     * version与format解析
     * @access private
     * @return void
     */
    private function analysisHeader(): void
    {
        $this->authorization = $this->request->header('authorization');
        $this->authorization = str_replace('&#43;', '+', $this->authorization);
        $this->authorization = str_replace('Bearer ', '', $this->authorization);
        if (!$this->authorization) {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->error('非法参数{20001-1}', 20001);
        }

        $token = (new Parser)->parse($this->authorization);

        $key  = $this->request->ip();
        $key .= $key . $this->request->rootDomain();
        $key .= $key . $this->request->server('HTTP_USER_AGENT');
        $key = md5(Base64::encrypt($key));

        $data = new ValidationData;
        $data->setIssuer($this->request->rootDomain());
        $data->setAudience(parse_url($this->request->server('HTTP_REFERER'), PHP_URL_HOST));
        $data->setId($token->getClaim('jti'));
        $data->setCurrentTime(time() + 60);

        if (false === $token->verify(new Sha256, $key) || false === $token->validate($data)) {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->error('非法参数{20001-2}', 20001);
        }



        // Session初始化
        // 规定sessionID
        $jti = Base64::decrypt($token->getClaim('jti'));
        if ($jti && is_file($this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'sess_' . $jti)) {
            $this->session->setId($jti);
            $this->session->init();
            $this->request->withSession($this->session);
        } else {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->error('非法参数{20002}', 20002);
        }



        // 解析版本号与返回数据类型
        $this->accept = $this->request->header('accept');
        $pattern = '/^application\/vnd\.[A-Za-z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[a-zA-Z0-9]+\+[A-Za-z]{3,5}+$/u';
        if (!$this->accept || !preg_match($pattern, $this->accept)) {
            $this->debugLog['accept'] = $this->accept;
            $this->log->record('[Async] header-accept error', 'error');
            $this->error('非法参数{20003}', 20003);
        }



        // 过滤多余信息
        // application/vnd.nicms.v1.0.1+json
        $accept = str_replace('application/vnd.', '', $this->accept);
        // 校验域名合法性
        list($domain, $accept) = explode('.', $accept, 2);
        list($root) = explode('.', $this->request->rootDomain(), 2);
        if (!hash_equals($domain, $root)) {
            $this->log->record('[Async] header-accept domain error', 'error');
            $this->error('非法参数{20004}', 20004);
        }
        unset($doamin, $root);



        // 取得版本与数据类型
        list($version, $this->format) = explode('+', $accept, 2);
        if (!$version || !preg_match('/^[a-zA-Z0-9.]+$/u', $version)) {
            $this->debugLog['version'] = $version;
            $this->log->record('[Async] header-accept version error', 'error');
            $this->error('非法参数{20005}', 20005);
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
            $this->debugLog['format'] = $this->format;
            $this->log->record('[Async] header-accept format error', 'error');
            $this->error('非法参数{20006}', 20006);
        }
    }

    /**
     * 操作成功返回的数据
     * @access protected
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为SUCCESS
     * @return void
     */
    protected function success(string $_msg, array $_data = [], int $_code = 10000): void
    {
        $this->result($_msg, $_data, $_code);
    }

    /**
     * 操作失败返回的数据
     * 10000 成功
     * 200xx 权限|授权|参数等错误
     * 3000x 请求类型等错误
     * 40001 缺少参数
     * 40002 非法参数
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function error(string $_msg, int $_code = 40001): void
    {
        $this->result($_msg, [], $_code);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   返回码
     * @return Response
     */
    protected function result(string $_msg, array $_data = [], int $_code = 10000)
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => true === $this->apiDebug ? $_msg : (10000 === $_code ? 'success' : $_msg),
            // 表单令牌
            'token'   => $this->request->isPost() && $_code === 10000
                ? $this->request->buildToken('__token__', 'md5')
                : '',
            // 调试数据
            'debug'   => true === $this->apiDebug ? [
                'time'    => number_format(microtime(true) - $this->app->getBeginTime(), 3),
                'memory'  => number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3),
                'query'   => $this->app->db->getQueryTimes(),
                'cache'   => $this->app->cache->getReadTimes(),
                'file'    => count(get_included_files()),
                'log'     => $this->debugLog,
                'method'  => $this->method,
                'version' => implode('.', $this->version),
            ] : [],
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->format)->allowCache(false);
        $response->header(array_merge(['X-Powered-By' => 'NIAPI'], $response->getHeader()));
        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', time() + $this->apiExpire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
        }

        $this->log->save();
        $_code === 10000 and $this->session->save();

        throw new HttpResponseException($response);
    }
}
