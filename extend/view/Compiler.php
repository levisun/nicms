<?php

/**
 *
 * 模板编译
 *
 * @package   NICMS
 * @category  view
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace view;

class Compiler
{
    /**
     * 布局模板开启状态
     * @var bool
     */
    private $layout_on = false;

    /**
     * 布局模板名
     * @var string
     */
    private $layout_name = 'layout.html';

    /**
     * 编译文件后缀
     * @var string
     */
    private $suffix = 'php';

    /**
     * 编译文件路径
     * @var string
     */
    private $path = '';

    /**
     * 更新编译
     * @var bool
     */
    private $tpl_compile = true;

    /**
     * 引入文件
     * @var array
     */
    public $includeFile = [];

    /**
     * 架构函数
     * @access public
     * @param  array  $_config
     * @return void
     */
    public function __construct(array $_config)
    {
        $this->layout_on = isset($_config['layout_on']) ? $_config['layout_on'] : false;
        $this->layout_name = isset($_config['layout_name']) ? $_config['layout_name'] : 'layout.html';
        $this->suffix = isset($_config['suffix']) ? $_config['suffix'] : 'php';
        $this->path = isset($_config['path']) ? $_config['path'] : '';
        $this->tpl_compile = isset($_config['tpl_compile']) ? $_config['tpl_compile'] : false;
    }

    /**
     * 获得编译文件路径
     * @access public
     * @param  string  $_template
     * @return string
     */
    public function getHashFile(string &$_template): string
    {
        return $this->path . md5($this->layout_on . $this->layout_name . $_template) . '.' . $this->suffix;
    }

    /**
     * 检测编译文件
     * @access public
     * @param  string  $_compiler_file
     * @return string
     */
    public function check(string &$_compiler_file): bool
    {
        if (false === $this->tpl_compile) {
            return false;
        }

        if (!is_file($_compiler_file)) {
            return false;
        }

        if (!$handle = @fopen($_compiler_file, 'r')) {
            return false;
        }

        // 读取第一行
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);
        if (!isset($matches[1])) {
            return false;
        }

        $include_file = unserialize($matches[1]);
        if (!is_array($include_file)) {
            return false;
        }

        // 检查模板文件是否有更新
        foreach ($include_file as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                // 模板文件如果有更新则缓存需要更新
                return false;
            }
        }

        return true;
    }

    /**
     * 生成编译文件
     * @access public
     * @param  string  $_content
     * @param  string  $_compiler_file
     * @return void
     */
    public function create(string &$_content, string &$_compiler_file): void
    {
        /* 去除html空格与换行 */
        $pattern = [
            '~>\s+<~'               => '><',
            '~>(\s+\n|\r)~'         => '>',
            '/( ){2,}/s'            => ' ',
            '/(\s+\n|\r)/s'         => '',
            '/(\t|\n|\r|\0|\x0B)/s' => '',
            '/<\/(body|html)>/s'    => '',
        ];
        $compiler = preg_replace(array_keys($pattern), array_values($pattern), $_content);
        $compiler .= '</body></html>';

        // 优化生成的php代码
        $compiler = preg_replace([
            '/\?>\s*<\?php\s(?!echo\b|\bend)/s',
            '/\?>\s*<\?php/s',
            // '/<\/script>\s*<script[a-z "\/=]*>/s',
            // '/<\/script>\s*<script (type=)*>/s',
        ], '', $compiler);
        $compiler = str_replace('\/', '/', $compiler);

        // 添加安全代码及模板引用记录
        $compiler = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . PHP_EOL . trim($compiler);

        // 编译存储
        $dir = dirname($_compiler_file);
        is_dir($dir) or mkdir($dir, 0755, true);

        file_put_contents($_compiler_file, $compiler);
    }
}
