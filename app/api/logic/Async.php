<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\async
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\logic;

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use app\api\logic\Analytical;
use app\api\logic\Validate;

abstract class Async
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
     * 返回表单令牌
     * @var bool
     */
    protected $apiRetFromToken = false;

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
        $this->request->filter('\app\common\library\DataFilter::filter');
        // 请勿更改参数(超时,执行内存)
        @set_time_limit(5);
        @ini_set('max_execution_time', '5');
        @ini_set('memory_limit', '8M');
    }

    /**
     * 运行
     * @access protected
     * @return $this
     */
    protected function exec()
    {
        $analytical = new Analytical;
        $analytical->authorization();
        $analytical->accept();
        $analytical->appId();


        $validate = new Validate;
        $validate->sign($analytical->appSecret);
        $validate->RBAC($analytical->appName, $analytical->appMethod, $analytical->uid);


        $analytical->openVersion = false;
        $analytical->method();


        // 加载语言包
        $this->loadLang($analytical->appName);

        // 执行METHOD获得返回数据
        $result = call_user_func([
            $this->app->make($analytical->appMethod['class']),
            $analytical->appMethod['method']
        ]);

        // 校验返回数据
        if (!is_array($result) && array_key_exists('msg', $result)) {
            $this->abort('返回数据格式错误', 28001);
        }

        // 调试模式
        // 返回数据没有指定默认关闭
        $this->debug(isset($result['debug']) ? $result['debug'] : false);

        // 缓存(缓存时间) true or int 单位秒
        // 返回数据没有指定默认开启
        $this->cache(isset($result['cache']) ? $result['cache'] : true);

        $result['data'] = isset($result['data']) ? $result['data'] : [];
        $result['code'] = isset($result['code']) ? $result['code'] : 10000;

        return $result;
    }

    /**
     * 开启或关闭调试
     * @access protected
     * @param  bool $_debug 开启或关闭调试模式
     * @return $this
     */
    protected function debug(bool $_debug)
    {
        $this->apiDebug = $_debug;
        return $this;
    }

    /**
     * 开启或关闭缓存
     * 当调试模式开启时,缓存一律关闭
     * @access protected
     * @param  int|false $_cache
     * @return $this
     */
    protected function cache($_cache)
    {
        // 调试模式开启时关闭缓存
        if (true === $this->apiDebug) {
            $this->apiCache = false;
        }
        // 指定缓存时间(int类型)
        elseif (is_numeric($_cache)) {
            $this->apiExpire = $_cache ? (int) $_cache : $this->apiExpire;
            $this->apiCache = true;
        }
        // 开启或关闭缓存
        else {
            $this->apiCache = $_cache;
        }
        return $this;
    }

    /**
     * 加载语言包
     * @access protected
     * @return void
     */
    protected function loadLang(string $_app_name): void
    {
        // 公众语言包
        $common_lang  = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $common_lang .= $this->lang->getLangSet() . '.php';

        // API方法所属应用的语言包
        $lang  = $this->app->getBasePath() . $_app_name . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $lang .= $this->lang->getLangSet() . '.php';

        $this->lang->load([$common_lang, $lang]);
    }

    /**
     * 操作成功
     * @access protected
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为10000
     * @return void
     */
    protected function success(string $_msg, array $_data = [], int $_code = 10000): Response
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
        return $this->response($_msg, [], $_code);
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
        $response = $this->response($_msg, [], $_code);
        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   返回码
     * @return Response
     */
    protected function response(string $_msg, array $_data = [], int $_code = 10000): Response
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
            'token' => $this->apiRetFromToken
                ? $this->request->buildToken('__token__', 'md5')
                : '',

            // 调试数据
            'debug' => true === $this->apiDebug ? [
                'query'   => $this->app->db->getQueryTimes(),
                'cache'   => $this->app->cache->getReadTimes(),
            ] : '',
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->format)->allowCache(false);
        $response->header(array_merge(
            $response->getHeader(),
            ['X-Powered-By' => 'NI API']
        ));
        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + $this->apiExpire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + $this->apiExpire) . ' GMT')
                ->eTag(md5($this->request->ip()));
        }

        $this->log->save();
        $this->session->save();

        return $response;
    }
}
