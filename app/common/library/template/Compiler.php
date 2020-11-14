<?php

declare(strict_types=1);

namespace app\common\library\template;

use Exception;
use app\common\library\template\Parse;

class Compiler extends Parse
{

    /**
     * 读取编译编译
     * @access public
     * @param  string  $_compiler_file 缓存的文件名
     * @param  array   $_vars 变量数组
     * @return void
     */
    public function read(string $_compiler_file, array $_vars = []): void
    {
        if (!empty($_vars) && is_array($_vars)) {
            // 模板阵列变量分解成为独立变量
            extract($_vars, EXTR_OVERWRITE);
        }

        //载入模版缓存文件
        include $_compiler_file;
    }

    /**
     * 编译模板文件内容
     * @access public
     * @param  string $content 模板内容
     * @param  string $cacheFile 缓存文件名
     * @return void
     */
    public function write(string &$_content, string $_compiler_file): void
    {
        // 模板解析
        $this->parse($_content);

        // 添加安全代码及模板引用记录
        $_content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . PHP_EOL . $_content;
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
     * 检查编译缓存是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param  string $_compiler_file 缓存文件名
     * @return bool
     */
    public function check(string $_compiler_file): bool
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

        if (0 != $this->config['compile_time'] && time() > filemtime($_compiler_file) + $this->config['compile_time']) {
            // 缓存是否在有效期
            return false;
        }

        return true;
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access public
     * @param  string $content 要解析的模板内容
     * @return void
     */
    public function parse(string &$_content): void
    {
        // 内容为空不解析
        if (empty($_content)) {
            return;
        }

        $this->parseLayout($_content);
        $this->parseInclude($_content);
        $this->parseTaglib($_content);
        $this->parseFunc($_content);
        $this->parseVar($_content);
        $this->parseTag($_content);
        $this->parseScript($_content);
        $this->parseReplaceStr($_content);
        $this->parseFilter($_content);
    }
}
