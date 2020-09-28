<?php

declare(strict_types=1);

namespace addon\webshell;

class Index
{
    private $harmFun = [
        'apache_setenv', 'base64_decode', 'call_user_func', 'call_user_func_array', 'chgrp', 'chown', 'chroot', 'dl', 'eval', 'exec', 'file_get_contents', 'file_put_contents', 'imap_open', 'ini_alter', 'ini_restore', 'invoke', 'openlog', 'passthru', 'pcntl_alarm', 'pcntl_exec', 'pcntl_fork', 'pcntl_get_last_error', 'pcntl_getpriority', 'pcntl_setpriority', 'pcntl_signal', 'pcntl_signal_dispatch', 'pcntl_sigprocmask', 'pcntl_sigtimedwait', 'pcntl_sigwaitinfo', 'pcntl_strerror', 'pcntl_wait', 'pcntl_waitpid', 'pcntl_wexitstatus', 'pcntl_wifcontinued', 'pcntl_wifexited', 'pcntl_wifsignaled', 'pcntl_wifstopped', 'pcntl_wstopsig', 'pcntl_wtermsig', 'popen', 'popepassthru', 'proc_open', 'putenv', 'readlink', 'shell_exec', 'symlink', 'syslog', 'system',
        'header_register_callback',
        'stream_wrapper_register',
    ];

    private $harmExt = ['jpg', 'gif', 'png', 'webp', 'mp3', 'mp4', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'php', 'js', 'css', 'html', 'xml', 'json', 'svg', 'map', 'ico'];

    private $dirTotal = 0;
    private $fileTotal = 0;

    private $log = '';

    public function __construct()
    {
        @set_time_limit(3600);
        @ini_set('max_execution_time', '3600');
        @ini_set('memory_limit', '128M');
    }

    public function __get(string $_name)
    {
        return isset($this->$_name) ? $this->$_name : null;
    }

    public function run(array $_settings)
    {
    }

    /**
     * 查找非法变量
     * @access private
     * @param  string $_path 文件
     * @param  string $_code
     * @param  int    $_line
     * @return void
     */
    private function each(string $_path = ''): void
    {
        if ($files = glob(rtrim($_path, '\/.') . DIRECTORY_SEPARATOR . '*')) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->dirTotal++;
                    $this->each($file . DIRECTORY_SEPARATOR);
                } elseif (is_file($file)) {
                    $this->fileTotal++;

                    $this->extension($file);

                    $this->read($file, function ($path, $code, $line) {
                        $this->func($path, $code, $line);
                        $this->vars($path, $code, $line);
                    });
                }
            }
        }
    }

    /**
     * 查找非法变量
     * @access private
     * @param  string $_path 文件
     * @param  string $_code
     * @param  int    $_line
     * @return void
     */
    private function vars(string $_path, string $_code, int $_line)
    {
        $status = false;

        $_code = (string) preg_replace_callback('/\$_[A-Z]+/s', function ($var) use (&$status) {
            $status = true;
            return '<font style="color:red">' . $var[0] . '</font>';
        }, $_code);

        if ($status) {
            $this->record($_path, $_code, $_line);
        }
    }

    /**
     * 查找非法方法(函数)
     * @access private
     * @param  string $_path 文件
     * @param  string $_code
     * @param  int    $_line
     * @return void
     */
    private function func(string $_path, string $_code, int $_line)
    {
        $status = false;

        $_code = (string) preg_replace_callback('/([\w\d]+)\(/si', function ($fun) use (&$status) {
            if (in_array($fun['1'], $this->harmFun)) {
                $status = true;
                return str_ireplace($fun[1], '<font style="color:red">' . $fun[1] . '</font>', $fun[0]);
            }
        }, $_code);

        if ($status) {
            $this->record($_path, $_code, $_line);
        }
    }

    /**
     * 查找非法文件
     * @access private
     * @param  string $_path 文件
     * @return void
     */
    private function extension(string $_file): void
    {
        $extension = pathinfo($_file, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->harmExt)) {
            $this->record($_file, '非法文件', 0);
        }
    }

    /**
     * 记录非法位置信息
     * @access private
     * @param  string $_path 文件
     * @param  string $_code
     * @param  int    $_line
     * @return void
     */
    private function record(string $_file_path, string $_code, int $_line): void
    {
        $path = str_replace('\extend\addon\pillow\WebShell', '', __DIR__);
        $path = str_replace($path, '', $_file_path);
        $this->log .= '<p>' . $path . ($_line ? '::' . $_line : '') . '<br />' . $_code . '</p>';
    }

    /**
     * 逐行读取文件内容
     * @access private
     * @param  string   $_file_path 文件
     * @param  callable $_callback
     * @return void
     */
    private function read(string $_file_path, callable $_callback): void
    {
        if (strtotime('-30 day') < filemtime($_file_path)) {
            $this->record($_file_path, '文件近期发生改变', 0);
        }

        $line = 0;
        $file = fopen($_file_path, 'r');
        while (!feof($file) && $code = fgets($file)) {
            $line++;

            $code = (string) preg_replace_callback('/\/{2}.*?[\r\n]+/si', function ($annotate) {
                return;
            }, $code);

            $code = (string) preg_replace_callback('/\/\*.*?\*\//si', function ($annotate) {
                return;
            }, $code);

            call_user_func_array($_callback, [$_file_path, trim($code), $line]);

            // 持续查询状态并不利于处理任务，每10ms执行一次，此时释放CPU，降低机器负载
            // usleep(10000);
        }
        fclose($file);
    }
}
