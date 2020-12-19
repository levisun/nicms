<?php

/**
 *
 * 数据维护类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Db;
use think\facade\Log;

class DataManage
{
    private $savePath;
    private $tempPath;
    private $lockPath;

    private $error_log = [];

    public function __construct()
    {
        $this->savePath = runtime_path('backup');
        is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

        $this->tempPath = runtime_path('temp');
        is_dir($this->tempPath) or mkdir($this->tempPath, 0755, true);

        $this->lockPath = runtime_path('lock');
        is_dir($this->lockPath) or mkdir($this->lockPath, 0755, true);

        @set_time_limit(3600);
        @ini_set('max_execution_time', '3600');
        @ini_set('memory_limit', '32M');
    }

    public function __destruct()
    {
        ignore_user_abort(false);
    }

    public function processList()
    {
        // only_execute('db_process_list.lock', '-1 hour', function () {
        $result = Db::query('show full processlist');
        foreach ($result as $value) {
            if (5 > $value['Time']) {
                continue;
            }

            if ('sleep' === strtolower($value['Command'])) {
                // system('kill '. $value['Id']);
            } elseif ('locked' === strtolower($value['State'])) {
                // system('kill '. $value['Id']);
            }

            $log = request()->ip() . ' ' . request()->method(true) . ' ' . $value['Time'] . 's ' .
                request()->url(true) . PHP_EOL .
                'command:' . $value['Command'] . ' State:' . $value['State'] . ' Sql:' . $value['Info'];

            Log::sql($log);
        }
        // });
    }

    /**
     * 优化表
     * @access public
     * @return bool
     */
    public function optimize(): bool
    {
        only_execute('db_optimize.lock', '-30 days', function () {
            $tables = Db::getTables();
            foreach ($tables as $name) {
                $result = Db::query('ANALYZE TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    Db::query('OPTIMIZE TABLE `' . $name . '`');
                    Log::alert('优化表' . $name);
                }
            }
        });

        return true;
    }

    public function repair()
    {
        only_execute('db_repair.lock', '-30 days', function () {
            $tables = Db::getTables();
            foreach ($tables as $name) {
                $result = Db::query('CHECK TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    Db::query('REPAIR TABLE `' . $name . '`');
                    Log::alert('修复表' . $name);
                }
            }
        });

        return true;
    }

    public function site(string $_dir = '')
    {
        $each = function (string $_dir, array &$_files) use (&$each) {
            if ($result = glob($_dir . '*')) {
                foreach ($result as $filename) {
                    if (is_file($filename)) {
                        $_files[] = $filename;
                    } elseif (is_dir($filename)) {
                        $each($filename . DIRECTORY_SEPARATOR, $_files);
                    }
                }
            }
        };

        $zip_files = [];
        if ($_dir) {
            $each($_dir, $zip_files);
        } else {
            $each(root_path('app'), $zip_files);
            $each(root_path('config'), $zip_files);
            $each(root_path('extend'), $zip_files);
            $each(public_path('static'), $zip_files);
            $each(public_path('theme'), $zip_files);
            $each(root_path('vendor'), $zip_files);
            if ($result = glob(public_path() . '*')) {
                foreach ($result as $filename) {
                    if (is_file($filename)) {
                        $zip_files[] = $filename;
                    }
                }
            }
            if ($result = glob(root_path() . '*')) {
                foreach ($result as $filename) {
                    if (is_file($filename)) {
                        $zip_files[] = $filename;
                    }
                }
            }
        }

        $zip = new \ZipArchive;
        $zip_name = $this->savePath . uniqid() . '_web.zip';
        $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($zip_files as $filename) {
            $zip->addFile($filename, str_replace(root_path(), '', $filename));
        }
        $zip->close();
    }

    /**
     * 还原
     * @access public
     * @param  string $_name 文件
     * @return mixed
     */
    public function restores(string $_name)
    {
        if (!class_exists('ZipArchive')) {
            Log::warning('环境不支持 ZipArchive 方法,系统备份功能无法使用');
            halt('<info>环境不支持 ZipArchive 方法,系统备份功能无法使用</info>');
        }

        only_execute('db_backup.lock', false, function () use (&$_name) {
            // 清空上次残留垃圾文件
            if ($files = glob($this->tempPath . '*')) {
                array_map('unlink', $files);
            }

            ignore_user_abort(true);

            // 打开压缩包并解压文件到指定目录
            $zip = new \ZipArchive;
            if (true === $zip->open($this->savePath . $_name)) {
                $zip->extractTo($this->tempPath);
                $zip->close();
            }

            // 获得解压后的文件
            if ($files = glob($this->tempPath . '*')) {
                shuffle($files);

                foreach ($files as $filename) {
                    // 读取每行数据
                    $file = fopen($filename, 'r');
                    while (!feof($file) && $sql = fgets($file)) {
                        $sql = trim($sql);

                        if (0 === strpos($sql, '--')) {
                            continue;
                        }

                        // 执行SQL
                        try {
                            Db::query($sql);
                        } catch (\Exception $e) {
                            ignore_user_abort(false);
                            Log::warning('数据库还原错误' . $sql);
                            $this->error_log[] = $sql;
                        }
                    }
                    fclose($file);

                    // 修改原表名为旧数据表,并修改备份表名为原表名,保证还原时不会损坏原数据
                    try {
                        $table_name = pathinfo($filename, PATHINFO_FILENAME);
                        Db::query('ALTER  TABLE `' . $table_name . '` RENAME TO `old_' . $table_name . '`');
                        Db::query('ALTER  TABLE `backup_' . $table_name . '` RENAME TO `' . $table_name . '`');
                        Db::query('DROP TABLE `old_' . $table_name . '`');
                    } catch (\Exception $e) {
                        ignore_user_abort(false);
                        Log::warning('数据库还原错误' . $sql);
                        $this->error_log[] = $sql;
                    }

                    unlink($filename);
                }
            }

            ignore_user_abort(false);
        });

        return !empty($this->error_log) ? $this->error_log : false;
    }

    /**
     * 备份
     * @access public
     * @return void
     */
    public function backup(): void
    {
        if (!class_exists('ZipArchive')) {
            Log::warning('环境不支持 ZipArchive 方法,系统备份功能无法使用');
            halt('<info>环境不支持 ZipArchive 方法,系统备份功能无法使用</info>');
        }

        only_execute('db_backup.lock', false, function () {
            // 清空上次残留垃圾文件
            if ($files = glob($this->tempPath . '*')) {
                array_map('unlink', $files);
            }

            ignore_user_abort(true);

            $table_name = Db::getTables();
            foreach ($table_name as $name) {
                $filename = $this->tempPath . $name . '.db_back';

                $sql = '-- 备份时间 ' . date('Y-m-d H:i:s') . PHP_EOL;
                file_put_contents($filename, $sql);

                // 获得表结构SQL语句
                $sql = $this->queryTableStructure($name) . PHP_EOL;
                file_put_contents($filename, $sql);

                // 获得主键
                $primary = Db::getPk($name);

                // 表字段
                $field = Db::getTableFields($name);
                $field = '`' . implode('`, `', $field) . '`';

                Db::table($name)->order($primary . ' ASC')->chunk(10, function ($result) use (&$name, &$field, &$filename) {
                    try {
                        $result = $result->toArray() ?: [];
                        if ($sql = $this->getTableData($name, $field, $result)) {
                            file_put_contents($filename, $sql . PHP_EOL, FILE_APPEND);
                        }

                        // 持续查询状态并不利于处理任务，每10ms执行一次，此时释放CPU，降低机器负载
                        usleep(10000);
                    } catch (\Exception $e) {
                        ignore_user_abort(false);
                        Log::warning($e->getMessage());
                        halt($e->getMessage());
                    }
                });
            }

            if ($files = glob($this->tempPath . '*')) {
                foreach ($files as $key => $filename) {
                    if ('db_back' !== pathinfo($filename, PATHINFO_EXTENSION)) {
                        unset($files[$key]);
                    }
                }

                if (!empty($files)) {
                    $zip_name = $this->savePath . uniqid() . '_db.zip';
                    $zip = new \ZipArchive;
                    $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    foreach ($files as $filename) {
                        $zip->addFile($filename, pathinfo($filename, PATHINFO_BASENAME));
                    }
                    $zip->close();
                    foreach ($files as $filename) {
                        @unlink($filename);
                    }
                }
            }

            ignore_user_abort(false);
        });
    }

    /**
     * 表数据SQL
     * @access private
     * @param  string $_name  表名
     * @param  string $_field 表字段
     * @param  array  $_data  表数据
     * @return string
     */
    private function getTableData(string &$_name, string &$_field, array &$_data): string
    {
        if (empty($_data)) {
            return '';
        }

        $sql = 'INSERT INTO `backup_' . $_name . '` (' . $_field . ') VALUES';

        foreach ($_data as $value) {
            $value = array_map(function ($vo) {
                $vo = preg_replace(['/\s+/s', '/ {2,}/s'], ' ', $vo);
                $vo = trim($vo);
                if (is_integer($vo)) {
                    $vo = (int) $vo;
                } elseif (is_float($vo)) {
                    $vo = (float) $vo;
                } elseif (is_null($vo) || $vo === 'null' || $vo === 'NULL') {
                    $vo = 'NULL';
                } else {
                    $vo = '\'' . addslashes((string) $vo) . '\'';
                }
                return $vo;
            }, $value);
            $sql .= '(' . implode(',', $value) . '),';
        }
        return rtrim($sql, ',') . ';';
    }

    /**
     * 查询表结构
     * @access private
     * @param  string $_table_name 表名
     * @return bool|string
     */
    private function queryTableStructure(string &$_table_name)
    {
        $tableRes = Db::query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (empty($tableRes[0]['Create Table'])) {
            return false;
        }
        $structure = 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
        // 清除多余空格回车制表符等
        $structure .= preg_replace(['/\s+/s', '/ {2,}/si'], ' ', $tableRes[0]['Create Table']) . ';';
        $structure = trim($structure);

        // 原表名替换成备份表名,此操作避免恢复数据失败时直接覆盖原数据导致的不可逆转错误
        $structure = str_replace($_table_name, 'backup_' . $_table_name, $structure);

        // 删除自增主键记录
        $structure = preg_replace_callback('/(AUTO_INCREMENT=[\d]+ DEFAULT)/si', function () {
            return 'DEFAULT';
        }, $structure);

        return $structure;
    }
}
