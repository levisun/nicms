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

use app\common\library\api\Analytical;
use app\common\library\api\Validate;
use app\common\library\Base64;

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
     * 解析器
     * @var object
     */
    protected $analytical;

    /**
     * 验证器
     * @var object
     */
    protected $validate;

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
    protected $user_id = 0;

    /**
     * 用户组ID
     * @var int
     */
    protected $user_role_id = 0;

    /**
     * 用户类型(用户或管理员)
     * @var string
     */
    protected $user_type = 'guest';

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
        $this->request->filter('\app\common\library\Filter::safe');

        // 请勿更改参数(超时,执行内存)
        @ignore_user_abort(false);
        @set_time_limit(60);
        @ini_set('max_execution_time', '60');
        @ini_set('memory_limit', '16M');

        $this->analytical = new Analytical($this->app);
        $this->validate = new Validate($this->app);

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
        $this->analytical->method();
        $this->validate->RBAC($this->analytical->appName, $this->analytical->appMethod, $this->user_id);

        // 执行METHOD获得返回数据
        $result = call_user_func([
            $this->app->make($this->analytical->appMethod['class']),
            $this->analytical->appMethod['method']
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
        $this->analytical->openVersion = false;

        $this->validate->timestamp();
        $this->validate->referer();

        $this->analytical->authorization();
        $this->analytical->accept();
        $this->analytical->appId();
        $this->analytical->loadLang();

        $this->validate->sign($this->analytical->appSecret);

        // 设置会话信息(用户ID,用户组)
        $this->session->setId($this->analytical->sessionId);
        $this->session->init();
        $this->request->withSession($this->session);
        if ($this->session->has($this->analytical->appAuthKey)) {
            $this->user_id = (int) $this->session->get($this->analytical->appAuthKey);
            $this->user_role_id = (int) $this->session->get($this->analytical->appAuthKey . '_role');
            $this->user_type = $this->analytical->appAuthKey == 'user_auth_key' ? 'user' : 'admin';
        }

        $this->validate->fromToken();
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
            'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 3) . ', ' .
                number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3) . ', ' .
                (true === $this->apiCache ? $this->apiExpire : 'off'),

            // 返回地址
            'return_url' => $this->session->has('return_url')
                ? $this->session->pull('return_url')
                : '',

            // 新表单令牌
            'csrf_token' => $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '',

            // 用户令牌
            'user_token' => $this->user_id
                ? sha1(Base64::encrypt($this->user_id . $this->user_role_id . $this->user_type))
                : sha1($this->request->ip()),
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->analytical->format)->allowCache(false);

        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $timestamp = time() + $this->apiExpire;
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->lastModified(gmdate('D, d M Y H:i:s', $timestamp) . ' GMT')
                ->expires(gmdate('D, d M Y H:i:s', $timestamp) . ' GMT');
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
            'csrf_token' => $_code > 21000 && $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '',

            // 用户令牌
            'user_token' => $this->user_id
                ? sha1(Base64::encrypt($this->user_id . $this->user_role_id . $this->user_type))
                : sha1($this->request->ip()),
        ];

        $result = array_filter($result);

        $response = Response::create($result, 'json');
        ob_start('ob_gzhandler');
        throw new HttpResponseException($response);
    }
}
