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
use app\common\library\Filter;
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
        $this->request->filter('\app\common\library\Filter::strict');

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
     * 错误分页请求
     * 避免扫库
     * @access protected
     * @return int
     */
    protected function ERPCache(int $_last_page = 0): int
    {
        $flag = $this->getClassMethod();

        $page = $this->request->param('page/d', 1, 'abs');
        $page = (int) $this->cache->get($this->getCacheKey($flag), $page);

        if ($_last_page) {
            $_last_page = $_last_page > $page ? $page : $_last_page;
            $this->cache->tag('request')->set($this->getCacheKey($flag), $_last_page, 28800);
        }

        return $page;

    }

    /**
     * 获得缓存KEY
     * @access protected
     * @param  string $_flag
     * @param  string $_type
     * @return string
     */
    protected function getCacheKey(string $_flag = ''): string
    {
        // 执行的方法名(命名空间\类名::方法名)
        $cache_key = $this->getClassMethod();
        $cache_key .= $this->lang->getLangSet();

        // 用户信息 $this->userId
        $cache_key .= $this->authKey . $this->userRoleId . $this->userType;

        //
        $token = $this->request->param('token', '');
        $cache_key .= strtolower($token);

        // 审核
        $pass = $this->request->param('pass/d', 0, 'abs');
        $cache_key .= 3 < $pass ? 0 : $pass;

        // 属性(置顶 推荐 最热)
        $attribute = $this->request->param('attribute/d', 0, 'abs');
        $cache_key .= 3 < $attribute ? 0 : $attribute;

        // 状态
        $status = $this->request->param('status/d', 0, 'abs');
        $cache_key .= 3 < $status ? 0 : $status;

        // 模型
        $model_id = $this->request->param('model_id/d', 0, 'abs');
        $cache_key .= \app\common\model\Models::cache(1440)->max('id') < $model_id ? 0 : $model_id;

        // 查询条目
        $limit = $this->request->param('limit/d', 20, 'abs');
        $limit = 100 > $limit && 10 < $limit ? intval($limit / 10) * 10 : 20;
        $cache_key .= $limit;

        // 日期格式
        $date_format = $this->request->param('date_format', 'Y-m-d');
        $date_format = preg_replace('/[^ymdhis:\-_\/ ]+/uis', '', $date_format);
        $cache_key .= Filter::nonChsAlpha($date_format);

        // 排序
        $sort = $this->request->param('sort', '');
        $cache_key .= preg_replace('/[^\w\.,_ ]+/uis', '', strtolower($sort));

        // 搜索关键词
        $key = $this->request->param('key', '', '\app\common\library\Filter::nonChsAlpha');
        $cache_key .= strtolower($key);

        // 主键ID
        $id = $this->request->param('id', 0);
        $cache_key .= is_int($id) ? $id : \app\common\library\Base64::url62decode($id);

        // 栏目ID
        $category_id = $this->request->param('category_id', 0);
        $category_id = is_int($category_id) ? $category_id : \app\common\library\Base64::url62decode($category_id);
        $cache_key .= \app\common\model\Category::cache(1440)->max('id') < $category_id ? 0 : $category_id;

        // 类型
        $type_id = $this->request->param('type_id/d', 0, 'abs');
        $cache_key .= \app\common\model\Type::cache(1440)->max('id') < $type_id ? 0 : $type_id;

        // 书籍ID
        $book_id = $this->request->param('book_id', 0);
        $book_id = is_int($book_id) ? $book_id : \app\common\library\Base64::url62decode($book_id);
        $cache_key .= \app\common\model\Book::cache(1440)->max('id') < $book_id ? 0 : $book_id;

        // 书籍类型
        $book_type_id = $this->request->param('book_type_id', 0);
        $book_type_id = is_int($book_type_id) ? $book_type_id : \app\common\library\Base64::url62decode($book_type_id);
        $cache_key .= \app\common\model\BookType::cache(1440)->max('id') < $book_type_id ? 0 : $book_type_id;

        return md5(sha1($cache_key) . strtolower($_flag));
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
            $this->userId = (int) $this->session->get($this->authKey);
            $this->userRoleId = (int) $this->session->get($this->authKey . '_role');
            $this->userType = $this->authKey == 'user_auth_key' ? 'user' : 'admin';
        }

        return [
            'userId'     => $this->userId,
            'userRoleId' => $this->userRoleId,
            'userType'   => $this->userType,
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
        $class = get_class($this) . '::' . $backtrace[0]['function'];
        $class = str_replace(['app\\', 'logic\\'], '', $class);
        $class = str_replace(['\\', '::'], '_', $class);

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
            'user_id'   => $this->userId,
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
        $class = $this->getClassMethod();

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
        if ($this->request->isPost() && !empty($_FILES) && $this->userId) {
            $size = [
                'width'  => $this->request->param('width/d', 0, 'abs'),
                'height' => $this->request->param('height/d', 0, 'abs'),
                'type'   => $this->request->param('type/b', false),
            ];
            $water = $this->request->param('water/b', true);
            $element = $this->request->param('element', 'upload');

            $upload = new UploadFile($size, $water, $element);
            $result = $upload->getFileInfo([
                'userId'   => $this->userId,
                'userType' => $this->userType
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

    /**
     * 获得执行类名和方法名
     * @access protected
     * @return string
     */
    protected function getClassMethod(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        array_shift($backtrace);
        return get_class($this) . '::' . $backtrace[0]['function'];
    }
}
