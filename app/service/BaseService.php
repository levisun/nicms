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

declare(strict_types=1);

namespace app\service;

use think\App;
use app\library\Ip;
use app\library\Rbac;
use app\library\Upload;
use app\model\Action as ModelAction;
use app\model\ActionLog as ModelActionLog;

abstract class BaseService
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
     * Cookie实例
     * @var \think\Cookie
     */
    protected $cookie;

    /**
     * Env实例
     * @var \think\Env
     */
    protected $env;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

    /**
     * Log实例
     * @var \think\Log
     */
    protected $log;

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * Response实例
     * @var \think\Response
     */
    protected $response;

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
     * IP信息
     * @var array
     */
    protected $ipinfo = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app      = $_app;
        $this->cache    = $this->app->cache;
        $this->config   = $this->app->config;
        $this->cookie   = $this->app->cookie;
        $this->env      = $this->app->env;
        $this->lang     = $this->app->lang;
        $this->log      = $this->app->log;
        $this->request  = $this->app->request;
        $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('default_filter');

        if ($this->session->has($this->authKey) && $this->session->has($this->authKey . '_role')) {
            $this->uid = $this->session->get($this->authKey);
            $this->urole = $this->session->get($this->authKey . '_role');
        }

        $this->ipinfo = Ip::info($this->request->ip());

        @ini_set('memory_limit', '16M');
        set_time_limit(30);

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
        list($_method, $action) = explode('::', $_method, 2);
        list($app, $service, $logic) = explode('\\', $_method, 3);

        $result = (new Rbac)->authenticate($this->uid, $app, $service, $logic, $action, $this->notAuth);

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
                $result = (int) $this->request->param('limit/f', 10);
                if (!$result || $result > 30) {
                    $result = false;
                    break;
                }
                $result = (int) $this->request->param('page/f');
                if (!$result) {
                    $result = false;
                    break;
                }
            } elseif ('date_format' === $name) {
                $result = (string) $this->request->param('date_format', 'Y-m-d H:i:s');
                if (!$result || !preg_match('/^[YymdHhis\-: ]+$/u', $result)) {
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
     * @param  string       子目录
     * @return string|array
     */
    // protected function uploadFile(string $_dir = '')
    // {
    //     if ($this->request->isPost() && !empty($_FILES) && $this->uid) {
    //         $input_name = $this->request->param('input_name', 'upload');
    //         $result = (new Upload)->save($input_name, $_dir);
    //     }

    //     return [
    //         'debug' => false,
    //         'cache' => false,
    //         'msg'   => isset($result) ? 'upload success' : 'upload error',
    //         'data'  => isset($result) ? $result : []
    //     ];
    // }

    /**
     * 上传文件
     * @access protected
     * @param  string $_dir        子目录
     * @param  string $_input_name 表单名 默认upload
     * @return array
     */
    protected function uploadFile(string $_dir = '', string $_input_name = 'upload')
    {
        if ($this->request->isPost() && !empty($_FILES) && $this->uid) {
            $files = $this->request->file($_input_name);

            // $size = (int) $this->config->get('app.upload_size', 1) * 1048576;
            // $ext = $this->config->get('app.upload_type', 'gif,jpg,jpeg,png,zip,rar');
            // $mime = [
            //     'doc'  => 'application/msword',
            //     'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            //     'gif'  => 'image/gif',
            //     'gz'   => 'application/x-gzip',
            //     'jpeg' => 'image/jpeg',
            //     'mp4'  => 'video/mp4',
            //     'pdf'  => 'application/pdf',
            //     'png'  => 'image/png',
            //     'ppt'  => 'application/vnd.ms-powerpoint',
            //     'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            //     'rar'  => 'application/octet-stream',
            //     'xls'  => 'application/vnd.ms-excel',
            //     'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //     'zip'  => 'application/zip'
            // ];
            // $error = $this->app->validate->rule([
            //     $_input_name => [
            //         'fileExt'  => $ext,
            //         'fileMime' => $mime,
            //         'fileSize' => $size
            //     ]
            // ])->batch(false)->failException(false)->check($files);

            $error = '';
            if (false === $error) {
                $msg = $this->app->validate->getError();
            } elseif (is_array($_FILES[$_input_name]['name'])) {
                $result = [];
                foreach ($files as $file) {
                    $_dir = $_dir ?: $file->extension();
                    $_dir .= '/' . date('Ym');
                    $save_file = $this->config->get('filesystem.disks.public.url') . '/' .
                        $this->app->filesystem->disk('public')->putFile($_dir, $file, function () {
                            return $this->uid . substr($this->authKey, 0, 1) . date('ymdHis') . uniqid();
                        });
                    $result[] = [
                        'extension'    => $file->extension(),
                        'name'         => $save_file,
                        'old_name'     => $file->getOriginalName(),
                        'original_url' => $save_file,
                        'size'         => $file->getSize(),
                        'type'         => $file->getMime(),
                        'url'          => $this->config->get('app.cdn_host') . $save_file,
                    ];
                }
            } else {
                $_dir = $_dir ?: $files->extension();
                $_dir .= '/' . date('Ym');
                $save_file = $this->config->get('filesystem.disks.public.url') . '/' .
                    $this->app->filesystem->disk('public')->putFile($_dir, $files, function () {
                        return $this->uid . substr($this->authKey, 0, 1) . date('ymdHis') . uniqid();
                    });
                $result = [
                    'extension'    => $files->extension(),
                    'name'         => $save_file,
                    'old_name'     => $files->getOriginalName(),
                    'original_url' => $save_file,
                    'size'         => $files->getSize(),
                    'type'         => $files->getMime(),
                    'url'          => $this->config->get('app.cdn_host') . $save_file,
                ];
            }
        } else {
            $msg = 'upload error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => isset($msg) ? $msg : 'upload success缺少验证',
            'data'  => isset($result) ? $result : []
        ];
    }
}
