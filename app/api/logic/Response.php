<?php

/**
 *
 * 异步请求实现
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

use think\Response as ThinkResponse;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\facade\Session;

class Response
{
    /**
     * 调试开关
     * @var bool
     */
    public $debug = false;

    /**
     * 浏览器数据缓存开关
     * @var bool
     */
    public $cache = false;

    /**
     * 浏览器数据缓存时间
     * @var int
     */
    public $cacheExpire = 1440;

    /**
     * 返回表单令牌
     * @var bool
     */
    public $fromToken = false;

    /**
     * 调试信息
     * @var array
     */
    public $debugLog = [];


    /**
     * 操作成功
     * @access public
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为10000
     * @return void
     */
    public function success(string $_msg, array $_data = [], int $_code = 10000): ThinkResponse
    {
        return $this->result($_msg, $_data, $_code);
    }

    /**
     * 操作失败
     * 10000 成功
     * 200xx 权限|授权|参数等错误
     * 3000x 请求类型等错误
     * 40001 缺少参数
     * 40002 错误请求
     * @access public
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    public function error(string $_msg, int $_code = 40001): ThinkResponse
    {
        return $this->result($_msg, [], $_code);
    }

    /**
     * 抛出异常
     * @access public
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    public function abort(string $_msg, int $_code = 40001): void
    {
        $response = $this->result($_msg, [], $_code);
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
    protected function result(string $_msg, array $_data = [], int $_code = 10000): ThinkResponse
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => $_msg,
            'runtime' => number_format(microtime(true) - app()->getBeginTime(), 3) . ', ' .
                number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3) . ', ' .
                (true === $this->cache ? $this->cacheExpire : 'off'),

            // 返回地址
            'return_url' => Session::has('return_url')
                ? Session::pull('return_url')
                : '',

            // 新表单令牌
            'token' => $this->fromToken
                ? Request::buildToken('__token__', 'md5')
                : '',

            // 调试数据
            'debug' => true === $this->debug ? [
                'query'   => app('db')->getQueryTimes(),
                'cache'   => app('cache')->getReadTimes(),
                'log'     => $this->debugLog,
            ] : '',
        ];
        $result = array_filter($result);

        $response = ThinkResponse::create($result, $this->format)->allowCache(false);
        $response->header(array_merge(
            $response->getHeader(),
            ['X-Powered-By' => 'NI API']
        ));
        if (Request::isGet() && true === $this->cache && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->cacheExpire . ',must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', Request::time() + $this->cacheExpire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', Request::time() + $this->cacheExpire) . ' GMT')
                ->eTag(md5(Request::ip()));
        }

        $this->log->save();
        $this->session->save();

        return $response;
    }
}
