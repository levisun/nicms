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
use think\facade\Lang;
use think\facade\Request;
use app\library\DataFilter;

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
     * @var array
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

        $theme = Config::get('app.cdn_host') . '/template/' . str_replace('\\', '/', $this->theme);
        $this->setReplace([
            'version'     => Config::get('app.version', '1.0.1'),
            'theme'       => $theme . 'theme/',
            'css'         => $theme . 'css/',
            'img'         => $theme . 'img/',
            'js'          => $theme . 'js/',
            'static'      => Config::get('app.cdn_host') . '/static/',
            'name'        => 'nicms',
            'title'       => 'nicms',
            'keywords'    => 'nicms',
            'description' => 'nicms',
            'bottom_msg'  => 'nicms',
            'copyright'   => 'nicms',
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

        if (!$content = $this->templateBuildRead($_template)) {
            $this->templateConfig = $this->parseTemplateConfig();

            echo $this->parseTemplateHead();

            $content = file_get_contents($this->parseTemplateFile($_template));
            $content = $this->parseTemplateLayout($content);        // 解析布局模板
            $content = $this->parseTemplateInclude($content);       // 解析模板引入文件
            $content = $this->parseTemplateReplace($content);       // 解析模板替换字符
            $content = $this->parseTemplateVars($content);          // 解析模板变量
            $content = $this->parseTemplateFunc($content);          // 解析模板函数
            $content = $this->parseTemplateTags($content);          // 解析模板标签方法
            $content = DataFilter::string($content);

            echo false === strpos($content, '<body') ? '<body>' : '';
            echo $content;
            echo $this->parseTemplateFoot();
        } else {
            echo $content;
        }

        $content = ob_get_clean();

        $this->templateBuildWrite($content);

        echo str_replace('{:__AUTHORIZATION__}', create_authorization(), $content);
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
        $foot = '';

        if (!empty($this->templateConfig['js'])) {
            foreach ($this->templateConfig['js'] as $js) {
                // $foot .= '<script type="text/css" name=' . pathinfo($js, PATHINFO_BASENAME) . '>';
                // $foot .= file_get_contents(str_replace(Config::get('app.cdn_host'), app()->getRootPath() . 'public', $js));
                // $foot .= '</script>';
                $foot .= '<script type="text/javascript" src="' . $js . '?v=' . $this->templateConfig['theme_version'] . '"></script>';
            }
        }

        if ($this->scriptReady) {
            $foot .= $this->scriptReady;
        }

        // 插件加载

        // 底部JS脚本
        $foot .= $this->templateReplace['__SCRIPT__'];

        return $foot . '</body></html>';
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
            '<!DOCTYPE html>' .
            '<html lang="' . Lang::getLangSet() . '">' .
            '<head>' .
            '<meta charset="utf-8" />' .
            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访
            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .

            '<meta name="generator" content="nicms" />' .
            '<meta name="author" content="levisun.mail@gmail.com" />' .
            '<meta name="copyright" content="2013-' . date('Y') . ' nicms all rights reserved" />' .

            '<meta http-equiv="Window-target" content="_blank">' .
            '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .

            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存
            '<link rel="dns-prefetch" href="' . Config::get('app.api_host') . '" />' .
            '<link rel="dns-prefetch" href="' . Config::get('app.cdn_host') . '" />' .

            '<link href="' . Config::get('app.cdn_host') . '/favicon.ico" rel="shortcut icon" type="image/x-icon" />';

        // 网站标题 关键词 描述
        $head .= '<title>' . $this->templateReplace['__TITLE__'] . '</title>';
        $head .= '<meta name="keywords" content="' . $this->templateReplace['__KEYWORDS__'] . '" />';
        $head .= '<meta name="description" content="' . $this->templateReplace['__DESCRIPTION__'] . '" />';
        $head .= '<meta property="og:title" content="' . $this->templateReplace['__NAME__'] . '">';
        $head .= '<meta property="og:type" content="website">';
        $head .= '<meta property="og:url" content="' . Request::url(true) . '">';
        $head .= '<meta property="og:image" content="">';

        if (!empty($this->templateConfig['meta'])) {
            foreach ($this->templateConfig['meta'] as $m) {
                $head .= '<meta ' . $m['type'] . ' ' . $m['content'] . ' />';
            }
        }
        if (!empty($this->templateConfig['link'])) {
            foreach ($this->templateConfig['link'] as $m) {
                $head .= '<link rel="' . $m['rel'] . '" href="' . $m['href'] . '" />';
            }
        }

        if (!empty($this->templateConfig['css'])) {
            foreach ($this->templateConfig['css'] as $css) {
                // $head .= '<style type="text/css" name="' . pathinfo($css, PATHINFO_BASENAME) . '">';
                // $head .= file_get_contents(str_replace(Config::get('app.cdn_host'), app()->getRootPath() . 'public', $css));
                // $head .= '</style>';
                $head .= '<link rel="stylesheet" type="text/css" href="' . $css . '?v=' . $this->templateConfig['theme_version'] . '" />';
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

        // if (!Request::isMobile() && Config::get('app.entry') !== Request::subDomain()) {
        //     $head .= 'if(navigator.userAgent.match(/(iPhone|iPod|Android|ios|SymbianOS)/i)){' .
        //         'location.replace("//m.' . Request::rootDomain() . '");' .
        //         '}';
        //     unset($sub);
        // }
        $head .= '</script></head>';

        return $head;
    }

    /**
     * 读取模板静态文件
     * @access private
     * @param
     * @return bool|string
     */
    private function templateBuildRead(string $_template)
    {
        if (!is_dir($this->buildPath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->buildPath, 0777, true);
        }

        $_template .= Lang::getLangSet() . Request::subDomain();
        $this->buildPath .= md5($_template) . '.html';

        clearstatcache();
        if (true === Config::get('app.debug') && is_file($this->buildPath)) {
            unlink($this->buildPath);
        } elseif (is_file($this->buildPath)) {
            return include_once($this->buildPath);
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
        if (false === Config::get('app.debug') && !is_file($this->buildPath)) {
            $_content = '<!-- ' . Request::pathinfo() . ' -->' . $_content;
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
        return preg_replace_callback('/{tag:([a-zA-Z0-9 =\'\"]+)}(.*?){\/tag}/si', function ($matches) {
            if (false !== strpos($matches[1], ' ')) {
                list($action, $params) = explode(' ', $matches[1], 2);
                $action = strtolower($action);
                $params = str_replace(['"', "'", ' '], ['', '', '&'], $params);
                parse_str($params, $params);
                $params['content'] = $matches[2];
            } else {
                $action = strtolower($matches[1]);
                $params = [
                    'content' => $matches[2]
                ];
            }

            if (false !== strpos($action, ':')) {
                list($tags, $action) = explode(':', 2);
                $tags = '\taglib\\' . ucfirst($tags);
            } else {
                $tags = '\taglib\Core';
            }

            if (class_exists($tags) && method_exists($tags, $action)) {
                $result = call_user_func([$tags, $action], $params);
                $pattern = [
                    '/>(\n|\r|\f)+/'      => '>',
                    '/(\n|\r|\f)+</'      => '<',
                    '/<\!\-\-.*?\-\->/si' => '',
                    '/\/\*.*?\*\//si'     => '',
                    '/\/\/.*?(\n|\r)+/i'  => '',
                    '/\n|\r|\f/'          => '',
                    '/( ){2,}/si'         => '',
                ];
                $result = preg_replace(array_keys($pattern), array_values($pattern), $result);

                if ('script' === $action) {
                    $this->scriptReady .= $result;
                } else {
                    return $result;
                }
            } else {
                return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
            }
        }, $_content);
    }

    /**
     * 解析模板函数
     * @access private
     * @param  string $_content
     * @return string content
     */
    private function parseTemplateFunc(string $_content): string
    {
        return preg_replace_callback('/{:([a-zA-Z_]+)\((.*?)\)}/si', function ($matches) {
            $safe_func = ['echo', 'str_replace', 'strlen', 'mb_strlen', 'strtoupper', 'lang', 'url', 'date'];
            if (in_array($matches[1], $safe_func) && function_exists($matches[1])) {
                return eval('return ' . $matches[1] . '(' . $matches[2] . ');');
            } else {
                return '<!-- 无法解析:' . $matches[1] .htmlspecialchars_decode($matches[0]) . ' -->';
            }
        }, $_content);
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
        return preg_replace_callback('/{:include file=["|\']+([a-zA-Z_]+)["|\']+}/si', function ($matches) {
            if ($matches[1] && is_file($this->templatePath . $this->theme . $matches[1] . '.html')) {
                return file_get_contents($this->templatePath . $this->theme . $matches[1] . '.html');
            }
        }, $_content);
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
