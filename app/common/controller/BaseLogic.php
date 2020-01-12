<?php

/**
 *
 * 业务层
 * logic
 * 基础方法
 *
 * @method BaseLogic actionLog(__METHOD__, ?操作日志) 操作日志
 * @method BaseLogic validate(验证器, ?数据) 验证方法
 * @method BaseLogic uploadFile(子目录, ?表单名) 文件上传方法
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
use app\common\library\UploadFile;
use app\common\model\Action as ModelAction;
use app\common\model\ActionLog as ModelActionLog;

abstract class BaseLogic
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
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * Session实例
     * @var \think\Session
     */
    protected $session;

    /**
     * 权限认证KEY
     * @var string
     */
    protected $authKey = 'user_auth_key';

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
        $this->app      = &$_app;
        $this->cache    = &$this->app->cache;
        $this->config   = &$this->app->config;
        $this->lang     = &$this->app->lang;
        $this->request  = &$this->app->request;
        $this->session  = &$this->app->session;

        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\DataFilter::filter');

        // 设置会话信息(用户ID,用户组)
        if ($this->session->has($this->authKey) && $this->session->has($this->authKey . '_role')) {
            $this->uid = (int) $this->session->get($this->authKey);
            $this->urole = (int) $this->session->get($this->authKey . '_role');
        }

        $this->initialize();
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
     * 权限验证
     * @access protected
     * @param  string  $_method
     * @param  string  $_write_log
     * @return bool|array
     */
    protected function actionLog(string $_method, string $_write_log = '')
    {
        $_method = str_replace(['app\\', 'logic\\', '\\', '::'], ['', '', '_', '_'], $_method);
        // 查询操作方法
        $has = (new ModelAction)
            ->where([
                ['name', '=', $_method]
            ])
            ->find();

        // 创建新操作方法
        if (is_null($has)) {
            $modelAction = new ModelAction;
            $modelAction->save([
                'name'  => $_method,
                'title' => $_write_log,
            ]);
            $has['id'] = $modelAction->id;
        }

        // 写入操作日志
        (new ModelActionLog)
            ->save([
                'action_id' => $has['id'],
                'user_id'   => $this->uid,
                'action_ip' => $this->request->ip(),
                'module'    => 'admin',
                'remark'    => $_write_log,
            ]);

        // 删除过期日志
        if (1 === mt_rand(1, 100)) {
            (new ModelActionLog)
                ->where([
                    ['create_time', '<=', strtotime('-90 days')]
                ])
                ->limit(100)
                ->delete();
        }
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
        $pattern = '/app\\\([a-zA-Z]+)\\\logic\\\([a-zA-Z]+)\\\([a-zA-Z]+)::([a-zA-Z]+)/si';
        $_validate = preg_replace_callback($pattern, function ($matches) {
            return is_array($matches)
                ? strtolower($matches[1] . '.' . $matches[2] . '.' . $matches[3] . '.' . $matches[4])
                : '';
        }, $_validate);
        list($app, $logic, $method) = explode('.', $_validate, 4);

        $class = '\app\\' . $app . '\validate\\' . $logic . '\\' . ucfirst($method);
        // 校验类是否存在
        if (!class_exists($class)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 30001,
                'msg'   => '请求错误'
            ];
        }

        $v = new $class;

        $_data = !empty($_data) ? $_data : $this->request->param();

        if (false === $v->batch(false)->failException(false)->check($_data)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 30002,
                'msg'   => $v->getError()
            ];
        } else {
            return false;
        }
    }

    /**
     * 删除文件
     * @access protected
     * @param  string $_file
     * @return void
     */
    public function removeFile(string $_file): void
    {
        (new UploadFile)->remove($_file);
    }

    /**
     * 写入日志
     * @access protected
     * @param  string $_file
     * @return void
     */
    public function writeFileLog(string $_file): void
    {
        (new UploadFile)->writeUploadLog($_file, 1);
    }

    /**
     * 上传文件
     * @access protected
     * @param  string $_element 表单名 默认upload
     * @return array
     */
    protected function uploadFile(): array
    {
        if ($this->request->isPost() && !empty($_FILES) && $this->uid) {
            $element = $this->request->param('element', 'upload');
            $thumb = [
                'width'  => $this->request->param('width/d', 0),
                'height' => $this->request->param('height/d', 0),
                'type'   => $this->request->param('type/b', false),
            ];
            $water = $this->request->param('water/b', true);

            $result = (new UploadFile)->getFileInfo($this->uid, $element, $thumb, $water);
        } else {
            $result = 'upload error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'code'  => is_string($result) ? 44001 : 10000,
            'msg'   => is_string($result) ? $result : 'success',
            'data'  => is_string($result) ? [] : $result
        ];
    }
}
