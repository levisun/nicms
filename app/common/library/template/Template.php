<?php

declare(strict_types=1);

namespace app\common\library\template;

use Exception;
use think\App;
use think\contract\TemplateHandlerInterface;
use app\common\library\template\Parse;

class Template implements TemplateHandlerInterface
{
    protected $template;
    protected $content;
    protected $app;

    // 模板引擎参数
    protected $config = [
        'view_theme'         => '',                     // 模板主题
        'view_path'          => './theme/',             // 模板路径
        'view_suffix'        => 'html',                 // 默认模板文件后缀
        'view_depr'          => DIRECTORY_SEPARATOR,
        'tpl_deny_func_list' => 'echo,exit',            // 模板引擎禁用函数
        'tpl_deny_php'       => false,                  // 默认模板引擎是否禁用PHP原生代码
        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'tpl_compile'        => true,                   // 是否开启模板编译,设为false则每次都会重新编译
        'compile_path'       => '',
        'compile_suffix'     => 'php',                  // 默认模板编译后缀
        'compile_time'       => 28800,                  // 模板编译有效期 0 为永久，(以数字为值，单位:秒)

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'taglib_begin'       => '{', // 标签库标签开始标记
        'taglib_end'         => '}', // 标签库标签结束标记
        'taglib_load'        => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
        'taglib_build_in'    => 'cx', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔 注意解析顺序
        'taglib_pre_load'    => '', // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
        'display_cache'      => false, // 模板渲染缓存
        'cache_id'           => '', // 模板缓存ID
        'tpl_replace_string' => [],
        'tpl_var_identify'   => 'array', // .语法变量识别，array|object|'', 为空时自动识别
        'default_filter'     => 'htmlentities', // 默认过滤方法 用于普通标签输出

        'strip_space'        => false, // 是否去除模板文件里面的html空格与换行

    ];

    /**
     * 模板包含信息
     * @var array
     */
    private $includeFile = [];

    public function __construct(App $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, (array) $config);

        $this->config['compile_path'] = $this->config['compile_path']
            ?: $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'compiler' . DIRECTORY_SEPARATOR . $this->app->http->getName() . DIRECTORY_SEPARATOR;
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplateFile($template);
        }

        return is_file($template);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param string $template 模板文件
     * @param array  $data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $data = []): void
    {
        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $_template = $this->parseTemplateFile($_template);
        }

        // 模板不存在 抛出异常
        if (!is_file($_template)) {
            throw new Exception('template not exists:' . $_template);
        }

        $compiler_file = $this->config['view_theme'] . '_' . md5($this->config['layout_on'] . $this->config['layout_name'] . $_template) . '.' . ltrim($this->config['compile_suffix'], '.');

        if (!$this->checkCompiler($compiler_file)) {
            // 缓存无效 重新模板编译
            $content = file_get_contents($_template);
            $this->compiler($content, $compiler_file);
        }

        $this->template = $_template;

        extract($data, EXTR_OVERWRITE);

        include $this->template;
    }

    /**
     * 渲染模板内容
     * @access public
     * @param string $content 模板内容
     * @param array  $data    模板变量
     * @return void
     */
    public function display(string $content, array $data = []): void
    {
        $this->content = $content;

        extract($data, EXTR_OVERWRITE);
        eval('?>' . $this->content);
    }

    /**
     * 编译模板文件内容
     * @access private
     * @param  string $content 模板内容
     * @param  string $cacheFile 缓存文件名
     * @return void
     */
    private function compiler(string &$_content, string $_compiler_file)
    {
        // 判断是否启用布局
        if ($this->config['layout_on']) {
            if (false !== strpos($_content, '{__NOLAYOUT__}')) {
                // 可以单独定义不使用布局
                $_content = str_replace('{__NOLAYOUT__}', '', $_content);
            } else {
                // 读取布局模板
                $layoutFile = $this->parseTemplateFile($this->config['layout_name']);

                if ($layoutFile) {
                    // 替换布局的主体内容
                    $_content = str_replace($this->config['layout_item'], $_content, file_get_contents($layoutFile));
                }
            }
        } else {
            $_content = str_replace('{__NOLAYOUT__}', '', $_content);
        }

        // 模板解析
        $this->parse($_content);

        if ($this->config['strip_space']) {
            /* 去除html空格与换行 */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $_content = preg_replace($find, $replace, $_content);
        }

        // 优化生成的php代码
        $_content = preg_replace('/\?>\s*<\?php\s(?!echo\b|\bend)/s', '', $_content);

        // 模板过滤输出
        $replace = $this->config['tpl_replace_string'];
        $_content = str_replace(array_keys($replace), array_values($replace), $_content);

        // 添加安全代码及模板引用记录
        $_content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . "\n" . $_content;
        $this->includeFile = [];

        // 检测模板目录
        $dir = dirname($_compiler_file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 生成模板缓存文件
        if (false === file_put_contents($_compiler_file, $_content)) {
            throw new Exception('compiler write error:' . $_compiler_file, 11602);
        }
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parse(string &$_content)
    {
        // 内容为空不解析
        if (empty($_content)) {
            return;
        }
    }

    /**
     * 检查编译缓存是否有效
     * 如果无效则需要重新编译
     * @access private
     * @param  string $_compiler_file 缓存文件名
     * @return bool
     */
    private function checkCompiler(string $_compiler_file): bool
    {
        if (!$this->config['tpl_compile'] || !is_file($_compiler_file) || !$handle = @fopen($_compiler_file, "r")) {
            return false;
        }

        // 读取第一行
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $includeFile = unserialize($matches[1]);

        if (!is_array($includeFile)) {
            return false;
        }

        // 检查模板文件是否有更新
        foreach ($includeFile as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                // 模板文件如果有更新则缓存需要更新
                return false;
            }
        }

        // 检查编译存储是否有效
        // 缓存文件不存在, 直接返回false
        if (!file_exists($_compiler_file)) {
            return false;
        }

        if (0 != $this->config['cache_time'] && time() > filemtime($_compiler_file) + $this->config['cache_time']) {
            // 缓存是否在有效期
            return false;
        }

        return true;
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param string $_template 模板文件规则
     * @return string
     */
    private function parseTemplateFile(string $_template): string
    {
        // 获取视图根目录
        if (strpos($_template, '@')) {
            // 跨应用调用
            [$app, $_template] = explode('@', $_template);
        }

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($this->config['view_path'], '\/.')) . DIRECTORY_SEPARATOR;

        // 应用
        $path .= isset($app) ? $app . DIRECTORY_SEPARATOR : $this->app->http->getName() . DIRECTORY_SEPARATOR;

        // 主题
        $path .= !empty($this->config['view_theme']) ? $this->config['view_theme'] . DIRECTORY_SEPARATOR : '';

        // 模板目录
        if (is_dir($this->app->getAppPath() . $path)) {
            $path = $this->app->getAppPath() . $path;
        } elseif (is_dir($this->app->getRootPath() . $path)) {
            $path = $this->app->getRootPath() . $path;
        } else {
            $path = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $path;
        }

        // 移动端目录
        if ($this->app->request->isMobile() && is_dir($path . 'mobile')) {
            $path .= 'mobile' . DIRECTORY_SEPARATOR;
        } elseif (false !== stripos($this->app->request->server('HTTP_USER_AGENT'), 'MicroMessenger') && is_dir($path . 'wechat')) {
            $path .= 'wechat' . DIRECTORY_SEPARATOR;
        }

        // 如果模板文件名为空 按照默认规则定位
        if (!$_template) {
            $_template = $this->app->request->controller() . DIRECTORY_SEPARATOR . $this->app->request->action(true);
        } else {
            $depr = $this->config['view_depr'];
            $_template = str_replace(['/', ':'], $depr, trim($_template, '\/.'));
        }

        $_template = $path . ltrim($_template, '/') . '.' . ltrim($this->config['view_suffix'], '.');

        if (is_file($_template)) {
            // 记录模板文件的更新时间
            $this->includeFile[$_template] = filemtime($_template);

            return $_template;
        }

        throw new Exception('template not exists:' . $_template);
    }

    /**
     * 配置模板引擎
     * @access private
     * @param array $_config 参数
     * @return void
     */
    public function config(array $_config): void
    {
        $this->config = array_merge($this->config, $_config);
    }

    /**
     * 获取模板引擎配置
     * @access public
     * @param string $_name 参数名
     * @return mixed
     */
    public function getConfig(string $_name)
    {
        return $this->config[$_name] ?? null;
    }
}
