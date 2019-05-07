<?php
/**
 *
 * 模板类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\exception\HttpException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Lang;
use think\facade\Request;
use app\library\Filter;

class Template
{
    /**
     * 模板路径
     * @var string
     */
    private $templatePath;

    /**
     * 模板路径
     * @var string
     */
    private $buildPath;

    /**
     * 主题
     * @var string
     */
    protected $theme = '';

    /**
     * 替换变量
     * @var string
     */
    protected $templateReplace = [];


    protected $varData = [];

    protected $scriptReady = '';

    /**
     * 架构函数
     * @access public
     * @param  array $config
     */
    public function __construct()
    {
        $this->templatePath  = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        $this->templatePath .= 'template' . DIRECTORY_SEPARATOR;

        $this->buildPath  = app()->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR;
        $this->buildPath .= Lang::getLangSet() . '-';
        $this->buildPath .= str_replace('.', '', Request::subDomain()) . DIRECTORY_SEPARATOR;

        $theme = Config::get('app.cdn_host') . '/template/' . str_replace('\\', '/', $this->theme);
        $this->setReplace([
            'version'     => Env::get('admin.version', '1.0.1'),
            'theme'       => $theme . 'theme/',
            'css'         => $theme . 'css/',
            'img'         => $theme . 'img/',
            'js'          => $theme . 'js/',
            'static'      => Config::get('app.cdn_host') . '/static/',
            'name'        => 'nicms',
            'title'       => 'nicms',
            'keywords'    => 'nicms',
            'description' => 'nicms',
            'bottom_msg'  => '',
            'copyright'   => '',
            'script'      => '<script type="text/javascript"></script>',
        ]);
        unset($theme);
    }

    /**
     * [assign description]
     * @param  array  $_vars [description]
     * @return [type]        [description]
     */
    protected function assign(array $_vars = [])
    {
        $this->varData = array_merge($this->varData, $_vars);
        return $this;
    }

    /**
     * [fetch description]
     * @param  string $_template [description]
     * @return [type]            [description]
     */
    protected function fetch(string $_template = '')
    {
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);

        if (!$content = $this->templateBuildRead()) {
            $this->templateConfig = $this->parseTemplateConfig();

            echo $this->parseTemplateHead();

            $content = file_get_contents($this->parseTemplateFile($_template));

            $content = $this->parseTemplateLayout($content);                    // 解析模板模板模式
            $content = $this->parseTemplateInclude($content);                   // 解析模板引入文件
            $content = $this->parseTemplateReplace($content);                   // 解析模板替换字符
            $content = $this->parseTemplateVars($content);                      // 解析模板变量
            $content = $this->parseTemplateFunc($content);                      // 解析模板函数

            // 过滤危害代码
            $content = Filter::FUN($content);
            $content = Filter::XSS($content);
            // 过滤空格回车等无用字符
            $content = Filter::ENTER($content);
            $content = preg_replace(['/(<!--)(.*?)(-->)/si'], '', $content);

            // 解析模板标签方法
            $content = $this->parseTemplateTags($content);

            if (false === strpos($content, '<body')) {
                echo '<body>';
            }

            echo $content;
            echo $this->parseTemplateFoot();
            echo '<!-- Static:';
            echo Config::get('app.app_debug') ? 'close' : 'success';
            echo ' Date:' . date('Y-m-d H:i:s') . ' -->';
        } else {
            echo $content;
        }

        $content = ob_get_clean();

        $this->templateBuildWrite($content);

        $content = str_replace([
            '{:__AUTHORIZATION__}', '{:__TIMESTAMP__}'
        ], [
            create_authorization(), time()
        ], $content);

        // if (!headers_sent() && !Config::get('app.app_debug') && function_exists('gzencode')) {
        //     $content = gzencode($content, 4);
        //     header('Content-Encoding:gzip');
        //     header('Content-Length:' . strlen($content));
        // }

        echo $content;
    }

    /**
     * 设置模板替换字符
     * @access protected
     * @param  array $_replace
     * @return void
     */
    protected function setReplace(array $_replace)
    {
        $rep = [];
        foreach ($_replace as $key => $value) {
            $rep['__' . strtoupper($key) . '__'] = $value;
        }
        $this->templateReplace = array_merge($this->templateReplace, $rep);
    }

    /**
     * 设置模板主题
     * @access protected
     * @param  string $_name
     * @return void
     */
    protected function setTheme(string $_name)
    {
        $this->theme = str_replace('/', DIRECTORY_SEPARATOR, $_name) . DIRECTORY_SEPARATOR;
    }

    /**
     * 解析foot
     * @access private
     * @param
     * @return string 底部HTML
     */
    private function parseTemplateFoot(): string
    {
        $foot = PHP_EOL;

        if (!empty($this->templateConfig['js'])) {
            foreach ($this->templateConfig['js'] as $js) {
                $foot .= '<script type="text/javascript" src="' . $js . '?v=' . $this->templateConfig['theme_version'] . '"></script>' .  PHP_EOL;
            }
        }

        if ($this->scriptReady) {
            $foot .= '<script type="text/javascript">' . $this->scriptReady . '</script>';
        }

        // 插件加载

        // 底部JS脚本
        $foot .= $this->templateReplace['__SCRIPT__'];

        return $foot . PHP_EOL . '</body>' . PHP_EOL . '</html>';
    }

    /**
     * 解析head
     * @access private
     * @param
     * @return string 头部HTML
     */
    private function parseTemplateHead(): string
    {
        $head =
        '<!DOCTYPE html>' . PHP_EOL .
        '<html lang="' . Lang::getLangSet() . '">' . PHP_EOL .
        '<head>' . PHP_EOL .
        '<meta charset="utf-8" />' . PHP_EOL .
        '<meta name="fragment" content="!" />' . PHP_EOL .                                // 支持蜘蛛ajax
        '<meta name="robots" content="all" />' . PHP_EOL .                                // 蜘蛛抓取
        '<meta name="revisit-after" content="1 days" />' . PHP_EOL .                      // 蜘蛛重访
        '<meta name="renderer" content="webkit" />' . PHP_EOL .                           // 强制使用webkit渲染
        '<meta name="force-rendering" content="webkit" />' . PHP_EOL .
        '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' . PHP_EOL .

        '<meta name="generator" content="nicms" />' . PHP_EOL .
        '<meta name="author" content="levisun.mail@gmail.com" />' . PHP_EOL .
        '<meta name="copyright" content="2013-' . date('Y') . ' nicms all rights reserved" />' . PHP_EOL .

        '<meta http-equiv="Window-target" content="_blank">' . PHP_EOL .
        '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' . PHP_EOL .

        '<meta http-equiv="Cache-Control" content="no-siteapp" />' . PHP_EOL .            // 禁止baidu转码
        '<meta http-equiv="Cache-Control" content="no-transform" />' . PHP_EOL .

        '<meta http-equiv="x-dns-prefetch-control" content="on" />' . PHP_EOL .           // DNS缓存
        '<link rel="dns-prefetch" href="' . Config::get('app.api_host') . '" />' . PHP_EOL .
        '<link rel="dns-prefetch" href="' . Config::get('app.cdn_host') . '" />' . PHP_EOL .

        '<link href="' . Config::get('app.cdn_host') . '/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .  PHP_EOL;

        // 网站标题 关键词 描述
        $head .= '<title>' . $this->templateReplace['__TITLE__'] . '</title>' .  PHP_EOL;
        $head .= '<meta name="keywords" content="' . $this->templateReplace['__KEYWORDS__'] . '" />' .  PHP_EOL;
        $head .= '<meta name="description" content="' . $this->templateReplace['__DESCRIPTION__'] . '" />' .  PHP_EOL;
        $head .= '<meta property="og:title" content="' . $this->templateReplace['__NAME__'] . '">' .  PHP_EOL;
        $head .= '<meta property="og:type" content="website">' .  PHP_EOL;
        $head .= '<meta property="og:url" content="' . Request::url(true) . '">' .  PHP_EOL;
        $head .= '<meta property="og:image" content="">' .  PHP_EOL;

        if (!empty($this->templateConfig['meta'])) {
            foreach ($this->templateConfig['meta'] as $m) {
                $head .= '<meta ' . $m['type'] . ' ' . $m['content'] . ' />' .  PHP_EOL;
            }
        }
        if (!empty($this->templateConfig['link'])) {
            foreach ($this->templateConfig['link'] as $m) {
                $head .= '<link rel="' . $m['rel'] . '" href="' . $m['href'] . '" />' .  PHP_EOL;
            }
        }

        if (!empty($this->templateConfig['css'])) {
            foreach ($this->templateConfig['css'] as $css) {
                $head .= '<link rel="stylesheet" type="text/css" href="' . $css . '?v=' . $this->templateConfig['theme_version'] . '" />' .  PHP_EOL;
            }
        }

        list($root) = explode('.', Request::rootDomain(), 2);
        $head .= '<script type="text/javascript">' .
        'var NICMS={' .
            'domain:"' . '//' . Request::subDomain() . '.' . Request::rootDomain() . '",' .
            'url:"' . Request::baseUrl(true) . '",' .
            'param:' . json_encode(Request::param()) . ',' .
            'api:{' .
                'url:"' . Config::get('app.api_host') . '",' .
                'root:"' . $root . '",' .
                'version:"' . $this->templateConfig['api_version'] . '",' .
                'appid:"' . $this->templateConfig['api_appid'] . '",' .
                'appsecret:"' . $this->templateConfig['api_appsecret'] . '",' .
                'authorization:"{:__AUTHORIZATION__}",' .
                'timestamp:"{:__TIMESTAMP__}",' .
                'param:' . json_encode(Request::param()) .
            '},' .
            'cdn:{' .
                'static:"' . $this->templateReplace['__STATIC__'] . '",' .
                'theme:"' .  $this->templateReplace['__THEME__'] . '",' .
                'css:"' .    $this->templateReplace['__CSS__'] . '",' .
                'img:"' .    $this->templateReplace['__IMG__'] . '",' .
                'js:"' .     $this->templateReplace['__JS__'] . '"' .
            '}' .
        '};';

        if (!Request::isMobile() && Env::get('admin.entry', 'admin') !== Request::subDomain()) {
            $head .= 'if(navigator.userAgent.match(/(iPhone|iPod|Android|ios|SymbianOS)/i)){' .
                'location.replace("//m.' . Request::rootDomain() . '");' .
            '}';
            unset($sub);
        }
        $head .= '</script>' .  PHP_EOL;
        unset($root);

        $head .= '</head>' .  PHP_EOL;

        return $head;
    }

    /**
     * 读取模板静态文件
     * @access private
     * @param
     * @return bool|string
     */
    private function templateBuildRead()
    {
        if (!is_dir($this->buildPath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->buildPath, 0777, true);
        }

        $url = explode('/', Request::path());
        $url = array_unique($url);
        $url = implode('-', $url);
        $url = $url ? $url . '.html' : 'index.html';
        $this->buildPath = $this->buildPath . $url;

        clearstatcache();
        if (false === Config::get('app.app_debug') && is_file($this->buildPath)) {
            return file_get_contents($this->buildPath);
        } elseif (is_file($this->buildPath)) {
            unlink($this->buildPath);
        }

        return false;
    }

    /**
     * 生成模板静态文件
     * @access private
     * @param  string $_content
     * @return void
     */
    private function templateBuildWrite(string $_content): void
    {
        clearstatcache();
        if (false === Config::get('app.app_debug') && !is_file($this->buildPath)) {
            file_put_contents($this->buildPath, $_content);
        }
    }

    /**
     * 解析模板变量
     * @access private
     * @param  string $_content
     * @return string content
     */
    private function parseTemplateTags(string $_content): string
    {
        if (false !== preg_match_all('/({tag:)([a-zA-Z]+)(})(.*?)({\/tag})/si', $_content, $matches)) {
            foreach ($matches[2] as $key => $tags) {
                $tags = strtolower($tags);
                $matches[4][$key] = call_user_func(['\taglib\Tags', $tags], $matches[4][$key]);
                if (in_array($tags, ['script'])) {
                    $_content = str_replace($matches[0][$key], '', $_content);
                    $this->scriptReady .= $matches[4][$key];
                } else {
                    $_content = str_replace($matches[0][$key], $matches[4][$key], $_content);
                }
            }
        }
        return $_content;
    }

    /**
     * 解析模板函数
     * @access private
     * @param  string $_content
     * @return string content
     */
    private function parseTemplateFunc(string $_content): string
    {
        $safe_func = [
            'echo',
            'str_replace',
            'strlen',
            'strtoupper',
            'lang',
            'url',
            'date'
        ];

        if (false !== preg_match_all('/({:)([a-zA-Z_]+\()(.*?)(\)})/si', $_content, $matches)) {
            foreach ($matches[2] as $key => $func) {
                $func = rtrim($func, '(');
                if (in_array($func, $safe_func)) {
                    eval('$func = ' . $func . '(' . $matches[3][$key] . ');');
                }

                $_content = str_replace($matches[0][$key], $func, $_content);
            }
        }

        return $_content;
    }

    /**
     * 解析模板变量
     * @access private
     * @param  string $_content
     * @return string content
     */
    private function parseTemplateVars(string $_content): string
    {
        if (false !== preg_match_all('/({:\$)([a-zA-Z_.]+)(})/si', $_content, $matches)) {
            foreach ($matches[2] as $key => $var_name) {
                if (false === strpos($var_name, '.')) {
                    $var_type = '';
                    $vars = $var_name;
                } else {
                    list($var_type, $vars) = explode('.', $var_name, 2);
                    $var_type = strtoupper(trim($var_type));
                }
                switch ($var_type) {
                    case 'SERVER':
                        $var_type = '$_SERVER';
                        $var_name = $vars;
                        break;

                    case 'GET':
                        $var_type = '$_GET';
                        $var_name = $vars;
                        break;

                    case 'POST':
                        $var_type = '$_POST';
                        $var_name = $vars;
                        break;

                    case 'COOKIE':
                        $var_type = '$_COOKIE';
                        $var_name = $vars;
                        break;

                    case 'SESSION':
                        $var_type = '$_SESSION';
                        $var_name = $vars;
                        break;

                    case 'ENV':
                        $var_type = '$_ENV';
                        $var_name = $vars;
                        break;

                    case 'REQUEST':
                        $var_type = '$_REQUEST';
                        $var_name = $vars;
                        break;

                    case 'CONST':
                        $var_type = 'CONST';
                        $var_name = strtoupper($vars);
                        break;

                    default:
                        $var_type = '$this->varData';
                        break;
                }
                unset($vars);

                if ($var_type === 'CONST') {
                    eval('$var_name = defined(\'' . $var_name . '\') ? ' . $var_name . ' : null;');
                } else {
                    $var_name = $var_type . '[\'' . str_replace('.', '\'][\'', $var_name) . '\']';
                    eval('$var_name = isset(' . $var_name . ') ? ' . $var_name . ' : null;');
                }
                if (is_null($var_name)) {
                    // throw new HttpException(200, '未定义!');
                }
                $_content = str_replace($matches[0][$key], $var_name, $_content);
                unset($var_type);
            }
        }

        return $_content;
    }

    /**
     * 解析模板引入文件
     * @access private
     * @param  string $_content 模板内容
     * @return string
     */
    private function parseTemplateInclude(string $_content): string
    {
        if (false !== preg_match_all('/({:include file=["|\'])([a-zA-Z_]+)(["|\']})/si', $_content, $matches)) {
            foreach ($matches[2] as $key => $value) {
                if ($value && is_file($this->templatePath . $this->theme . $value . '.html')) {
                    $file = file_get_contents($this->templatePath . $this->theme . $value . '.html');
                    $_content = str_replace($matches[0][$key], $file, $_content);
                }
            }
        }

        return $_content;
    }

    /**
     * 解析布局模板
     * @access private
     * @param  string $_template 模板名
     * @return string 模板路径
     */
    private function parseTemplateLayout(string $_content): string
    {
        if (true == $this->templateConfig['layout']) {
            if (false === strpos($_content, '{:NOT_LAYOUT}')) {
                if (is_file($this->templatePath . $this->theme . 'layout.html')) {
                    $layout = file_get_contents($this->templatePath . $this->theme . 'layout.html');
                    $_content = str_replace('{:__CONTENT__}', $_content, $layout);
                } else {
                    throw new HttpException(200, '布局模板不存在.');
                }
            } else {
                $_content = str_replace('{:NOT_LAYOUT}', '', $_content);
            }
        }

        return $_content;
    }

    /**
     * 解析模板配置
     * @access private
     * @param  string $_template 模板名
     * @return string 模板路径
     */
    private function parseTemplateConfig(): array
    {
        if (is_file($this->templatePath . $this->theme . 'config.json')) {
            $config = file_get_contents($this->templatePath . $this->theme . 'config.json');
            $config = strip_tags($config);
            $config = $this->parseTemplateReplace($config);
            $config = json_decode($config, true);
            if (!empty($config) && is_array($config)) {
                $keys = array_keys($config);
                if (!in_array('layout', $keys)) {
                    throw new HttpException(200, '模板配置文件错误.');
                } elseif (!in_array('api_version', $keys)) {
                    throw new HttpException(200, '模板配置文件错误.');
                } elseif (!in_array('theme_version', $keys)) {
                    throw new HttpException(200, '模板配置文件错误.');
                }

                return $config;
            }
        }

        throw new HttpException(200, '模板配置文件错误.' . $this->theme);
    }

    /**
     * 解析模板替换字符
     * @access private
     * @param  string $_content 模板内容
     * @return string 模板路径
     */
    private function parseTemplateReplace(string $_content): string
    {
        if (!empty($this->templateReplace)) {
            $search = array_keys($this->templateReplace);
            $replace = array_values($this->templateReplace);

            $_content = trim($_content);
            $_content = str_replace($search, $replace, $_content);
        }

        return $_content;
    }

    /**
     * 解析模板路径
     * @access private
     * @param  string $_template 模板名
     * @return string 模板路径
     */
    private function parseTemplateFile(string $_template): string
    {
        $_template  = str_replace(['\\', ':'], DIRECTORY_SEPARATOR, $_template);
        $_template .= '.html';

        // 微信和移动端访问时,判断模板是否存在.
        if (isWechat() && is_file($this->templatePath . $this->theme . 'wechat' . DIRECTORY_SEPARATOR . $_template)) {
            $_template = 'wechat' . DIRECTORY_SEPARATOR . $_template;
        } elseif (Request::isMobile() && is_file($this->templatePath . $this->theme . 'mobile' . DIRECTORY_SEPARATOR . $_template)) {
            $_template = 'mobile' . DIRECTORY_SEPARATOR . $_template;
        }

        if (is_file($this->templatePath . $this->theme .  $_template)) {
            return $this->templatePath . $this->theme .  $_template;
        }

        throw new HttpException(200, '模板不存在:' . $this->theme . $_template);
    }
}
