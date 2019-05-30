<?php
/**
 *
 * API接口层
 * 基础方法
 *     $this->authenticate(__METHOD__, ?操作日志) 权限验证
 *     $this->upload() 上传方法
 *     $this->validate(验证器, ?数据) 验证方法
 *
 * @package   NICMS
 * @category  app\service
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service;

use think\App;
use app\library\Rbac;
use app\library\Upload;
use app\model\Action as ModelAction;
use app\model\ActionLog as ModelActionLog;

class BaseService
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

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
     * 权限认证KEY
     * @var string
     */
    protected $auth_key;

    /**
     * uid
     * @var int
     */
    protected $uid;

    /**
     * 不用验证
     * @var array
     */
    protected $not_auth = [
        'not_auth_action' => [
            'login',
            'logout',
            'forget',
            'auth',
            'profile',
            'notice'
        ]
    ];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app      = $_app;
        $this->config   = $this->app->config;
        $this->lang     = $this->app->lang;
        $this->log      = $this->app->log;
        $this->request  = $this->app->request;
        $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('defalut_filter');

        $this->uid = session($this->auth_key);

        $this->initialize();
    }

    /**
     * 初始化
     * @access protected
     * @param
     * @return void
     */
    protected function initialize()
    { }

    /**
     * 权限验证
     * @access protected
     * @param  string  $_method
     * @param  string  $_write_log
     * @return bool|array
     */
    protected function authenticate(string $_method, string $_write_log = '')
    {
        $_method = str_replace('app\service\\', '', strtolower($_method));
        list($_method, $action) = explode('::', $_method);
        list($app, $service, $logic) = explode('\\', $_method);

        $result = (new Rbac)->authenticate($this->uid, $app, $service, $logic, $action, $this->not_auth);

        // 验证成功,记录操作日志
        if ($result && $_write_log) {
            $map = $app . '_' . $service . '_' . $logic . '_' . $action;

            // 查询操作方法
            $has = (new ModelAction)
                ->where([
                    ['name', '=', $map]
                ])
                ->find();

            // 创建新操作方法
            if (is_null($has)) {
                $res = (new ModelAction)
                    ->create([
                        'name'  => $map,
                        'title' => $_write_log,
                    ]);
                $has['id'] = $res->id;
            }

            // 写入操作日志
            (new ModelActionLog)
                ->create([
                    'action_id' => $has['id'],
                    'user_id'   => $this->uid,
                    'action_ip' => $this->request->ip(),
                    'module'    => 'admin',
                    'remark'    => $_write_log,
                ]);

            // 删除过期日志
            (new ModelActionLog)
                ->where([
                    ['create_time', '<=', strtotime('-180 days')]
                ])
                ->delete();
        }

        return $result ? false : [
            'debug' => false,
            'cache' => false,
            'code'  => 40006,
            'msg'   => '权限不足'
        ];
    }

    /**
     * API请求参数验证
     * @access protected
     * @param  array $_var_name
     * @return bool|array
     */
    protected function check_params(array $_var_name)
    {
        foreach ($_var_name as $name) {
            if ('limit' === $name) {
                $result = (int)$this->request->param('limit/f', 10);
                if (!$result || $result > 30) {
                    $result = false;
                    break;
                }
                $result = (int)$this->request->param('page/f');
                if (!$result) {
                    $result = false;
                    break;
                }
            } elseif ('date_format' === $name) {
                $result = (string)$this->request->param('date_format', 'Y-m-d H:i:s');
                if (!$result || !preg_match('/^[YmdHis]+$/u', str_replace(['-', ':'], '', $result))) {
                    $result = false;
                    break;
                }
            }
        }

        return $result ? false : [
            'debug' => false,
            'cache' => false,
            'code'  => 40002,
            'msg'   => '非法参数',
        ];
    }

    /**
     * 数据验证
     * @access protected
     * @param  string  $_validate
     * @param  array   $_data
     * @return bool|string
     */
    protected function validate(string $_validate, array $_data = [])
    {
        $_validate = str_replace('app\service\\', '', strtolower($_validate));
        list($_validate) = explode('::', $_validate, 2);

        // 支持场景
        if (false !== strpos($_validate, '.')) {
            list($_validate, $scene) = explode('.', $_validate);
        }

        $class = $this->app->parseClass('validate', $_validate);
        $v     = new $class;

        if (!empty($scene)) {
            $v->scene($scene);
        }

        $_data = !empty($_data) ? $_data : $this->request->param();

        if (false === $v->batch(false)->failException(false)->check($_data)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40006,
                'msg'   => $v->getError()
            ];
        } else {
            return false;
        }
    }

    /**
     * 上传文件
     * @access protected
     * @param
     * @return string|array
     */
    protected function uploadFile(string $_dir = '')
    {
        if ($this->request->isPost() && !empty($_FILES)) {
            $input_name = $this->request->param('input_name', 'upload');
            $result = (new Upload)->save($input_name, $_dir);
        } else {
            $result = 'upload error';
        }

        return $result;
    }
}
