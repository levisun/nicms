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
 * @author    失眠小枕头 [312630173@qq.com]
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
     * Cookie实例
     * @var \think\Cookie
     */
    protected $cookie;

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

    const CACHE_TOTAL_KEY = 'total';
    const CACHE_PAGE_KEY  = 'page';

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = &$_app;
        $this->cache   = &$this->app->cache;
        $this->config  = &$this->app->config;
        $this->cookie  = &$this->app->cookie;
        $this->lang    = &$this->app->lang;
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

        // 用户会话信息(用户ID,用户组)
        $this->getUserSession();

        $this->initialize();
    }

    public function __destruct()
    {
        ignore_user_abort(false);
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
     * 获得缓存KEY
     * @access protected
     * @param  string $_flag
     * @return string
     */
    protected function getCacheKey(string $_flag = '')
    {
        $_flag = strtolower($_flag);

        $pass = $this->request->param('pass/d', 0, 'abs');
        $pass = 3 < $pass ? 0 : $pass;

        $attribute = $this->request->param('attribute/d', 0, 'abs');
        $attribute = 3 < $attribute ? 0 : $attribute;

        $status = $this->request->param('status/d', 0, 'abs');
        $status = 3 < $status ? 0 : $status;

        $model_id = $this->request->param('model_id/d', 0, 'abs');
        $model_id = \app\common\model\Models::cache(28800)->count() < $model_id ? 0 : $model_id;

        $id = $this->request->param('id', 0);
        $id = is_int($id) ? $id : \app\common\library\Base64::url62decode($id);

        $category_id = $this->request->param('category_id', 0);
        $category_id = is_int($category_id) ? $category_id : \app\common\library\Base64::url62decode($category_id);
        $category_id = \app\common\model\Category::cache(28800)->count() < $category_id ? 0 : $category_id;

        $type_id = $this->request->param('type_id/d', 0, 'abs');
        $type_id = \app\common\model\Type::count() < $type_id ? 0 : $type_id;

        $book_id = $this->request->param('book_id', 0);
        $book_id = is_int($book_id) ? $book_id : \app\common\library\Base64::url62decode($book_id);
        $book_id = \app\common\model\Book::cache(28800)->count() < $book_id ? 0 : $book_id;

        $book_type_id = $this->request->param('book_type_id', 0);
        $book_type_id = is_int($book_type_id) ? $book_type_id : \app\common\library\Base64::url62decode($book_type_id);
        $book_type_id = \app\common\model\BookType::cache(28800)->count() < $book_type_id ? 0 : $book_type_id;

        $key = $this->request->param('key', null, '\app\common\library\Filter::non_chs_alpha');
        $sort = $this->request->param('sort');

        $limit = $this->request->param('limit/d', 20, 'abs');
        $limit = 100 > $limit && 10 < $limit ? intval($limit / 10) * 10 : 20;

        $date_format = $this->request->param('date_format', 'Y-m-d');
        $date_format = preg_match('/[ymdhis\-: ]{3,}/uis', $date_format) ? $date_format : 'Y-m-d';

        $page = $this->request->param('page/d', 1, 'abs');

        $token = $this->request->param('token');

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        array_shift($backtrace);
        $class  = $backtrace[0]['class'] . '::' . $backtrace[0]['function'];

        $key = $class;
        $key .= $pass . $attribute . $status . $model_id . $id . $category_id . $type_id . $key . $sort;
        if ($_flag === self::CACHE_TOTAL_KEY) {
            $key .= '';
        } elseif ($_flag === self::CACHE_PAGE_KEY) {
            $key .= $limit;
        } else {
            $key .= $limit . $page . $date_format;
        }
        $key .= $book_id . $book_type_id;
        $key .= $this->authKey . $this->user_id . $this->user_role_id . $this->user_type;
        $key .= $this->lang->getLangSet() . $token . $_flag;

        return md5(sha1($key) . $_flag);
    }

    /**
     * 设置用户会话信息
     * @access protected
     * @param  int    $_id
     * @param  int    $_role
     * @param  string $_type
     * @return array
     */
    protected function setUserSession(int $_id, int $_role, string $_type = '')
    {
        $this->session->set($this->authKey, $_id);
        $this->session->set($this->authKey . '_role', $_role);

        if (!$_type) {
            $_type = $this->authKey == 'user_auth_key' ? 'user' : 'admin';
        }

        $this->session->set($this->authKey . '_type', $_type);

        $this->getUserSession();
    }

    /**
     * 获得用户会话信息
     * @access protected
     * @return array
     */
    protected function getUserSession(): array
    {
        if ($this->session->has($this->authKey) && $this->session->has($this->authKey . '_role')) {
            $this->user_id = (int) $this->session->get($this->authKey);
            $this->user_role_id = (int) $this->session->get($this->authKey . '_role');
            $this->user_type = $this->session->get($this->authKey . '_type');
        }

        return [
            'user_id'      => $this->user_id,
            'user_role_id' => $this->user_role_id,
            'user_type'    => $this->user_type,
        ];
    }

    /**
     * 操作日志
     * @access protected
     * @param  string  $_write_log
     * @return bool|array
     */
    protected function actionLog(string $_write_log = '')
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        array_shift($backtrace);
        $class  = str_replace(['app\\', 'logic\\', '\\'], ['', '', '_'], $backtrace[0]['class']);
        $class .= '_' . $backtrace[0]['function'];

        // 查询操作方法
        $has = ModelAction::where('name', '=', $class)->find();

        // 创建新操作方法
        if (is_null($has)) {
            $modelAction = new ModelAction;
            $modelAction->save([
                'name'  => $class,
                'title' => $_write_log,
            ]);
            $has['id'] = $modelAction->id;
        }

        // 写入操作日志
        ModelActionLog::create([
            'action_id' => $has['id'],
            'user_id'   => $this->user_id,
            'action_ip' => $this->request->ip(),
            'module'    => 'admin',
            'remark'    => $_write_log,
        ]);

        // 删除过期日志
        if (1 === mt_rand(1, 100)) {
            ModelActionLog::where('create_time', '<', strtotime('-90 days'))->limit(100)->delete();
        }
    }

    /**
     * 数据验证
     * @access protected
     * @param  array   $_data
     * @return bool|string
     */
    protected function validate(array $_data = [])
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        array_shift($backtrace);
        $class = $backtrace[0]['class'] . '::' . $backtrace[0]['function'];

        $pattern = '/app\\\([a-zA-Z]+)\\\logic\\\([a-zA-Z]+)\\\([a-zA-Z]+)::([a-zA-Z]+)/si';
        $class = preg_replace_callback($pattern, function ($matches) {
            return strtolower($matches[1] . '.' . $matches[2] . '.' . $matches[3] . '.' . $matches[4]);
        }, $class);

        list($app, $logic, $method) = explode('.', $class, 4);

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
     * 上传文件
     * @access public
     * @param  string $_element 表单名 默认upload
     * @return array
     */
    public function upload(): array
    {
        $this->actionLog('upload_file', 'user upload');

        $result = 'upload error';
        if ($this->request->isPost() && !empty($_FILES) && $this->user_id) {
            $size = [
                'width'  => $this->request->param('width/d', 0, 'abs'),
                'height' => $this->request->param('height/d', 0, 'abs'),
                'type'   => $this->request->param('type/b', false),
            ];
            $water = $this->request->param('water/b', true);
            $element = $this->request->param('element', 'upload');

            $upload = new UploadFile($size, $water, $element);
            $result = $upload->getFileInfo([
                'user_id'   => $this->user_id,
                'user_type' => $this->user_type
            ]);
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
