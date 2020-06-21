<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\library\api
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\api;

use think\Response;
use app\common\library\api\BaseLogic;
use app\common\library\api\Analytical;
use app\common\library\api\Validate;

class Async extends BaseLogic
{
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
    protected $uid = 0;

    /**
     * 用户组ID
     * @var int
     */
    protected $urole = 0;

    /**
     * 用户类型(用户和管理员)
     * @var string
     */
    protected $type = 'guest';

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        $this->analytical = new Analytical($this->app);
        $this->validate = new Validate($this->app);
    }

    /**
     * 运行
     * @access protected
     * @return $this
     */
    protected function exec(): array
    {
        $this->ApiInit();
        $this->analytical->method();
        $this->validate->RBAC($this->analytical->appName, $this->analytical->appMethod, $this->uid);

        // 执行METHOD获得返回数据
        $result = call_user_func([
            $this->app->make($this->analytical->appMethod['class']),
            $this->analytical->appMethod['method']
        ]);

        // 校验返回数据
        if (!is_array($result) && array_key_exists('msg', $result)) {
            $this->abort('返回数据格式错误', 28001);
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
            $this->apiCache = $_expire;
        }

        // 指定缓存时间(int类型)
        elseif (is_numeric($_expire)) {
            $_expire = (int) $_expire;
            $this->apiExpire = $_expire ? (int) $_expire : $this->apiExpire;
            $this->apiCache = true;
        }

        return $this;
    }

    /**
     * API初始化
     * @access protected
     * @return void
     */
    protected function ApiInit(): void
    {
        $this->analytical->openVersion = false;
        $this->analytical->authorization();
        $this->analytical->accept();
        $this->analytical->appId();
        $this->analytical->loadLang();

        // 设置会话信息(用户ID,用户组)
        $this->session->setId($this->analytical->sessionId);
        $this->session->init();
        $this->request->withSession($this->session);
        if ($this->session->has($this->analytical->appAuthKey)) {
            $this->uid = (int) $this->session->get($this->analytical->appAuthKey);
            $this->urole = (int) $this->session->get($this->analytical->appAuthKey . '_role');
            $this->type = $this->analytical->appAuthKey == 'user_auth_key' ? 'user' : 'admin';
        }

        $this->validate->sign($this->analytical->appSecret);
        $this->validate->referer();
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
            'token' => $this->request->isPost()
                ? $this->request->buildToken('__token__', 'md5')
                : '',
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->analytical->format);
        $header = $response->getHeader();
        $header['X-Powered-By'] = 'NI API';

        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $timestamp = $this->request->time() + 3600 * 6;
            $response->allowCache($this->apiCache);
            $header['Cache-Control'] = 'max-age=' . $this->apiExpire . ',must-revalidate';
            $header['Last-Modified'] = gmdate('D, d M Y H:i:s', $timestamp + $this->apiExpire) . ' GMT';
            $header['Expires']       = gmdate('D, d M Y H:i:s', $timestamp + $this->apiExpire) . ' GMT';
            $header['ETag']          = md5($this->request->ip());
        }

        $response->header($header);

        $this->log->save();
        $this->session->save();

        return $response;
    }
}
