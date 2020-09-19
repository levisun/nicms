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

use think\facade\Env;
use think\facade\Log;

class DataManage
{
    private $DB = null;

    private $savePath;
    private $tempPath;
    private $lockPath;

    public function __construct()
    {
        $this->DB = app('think\DbManager');

        $this->savePath = runtime_path('backup');
        is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

        $this->tempPath = runtime_path('temp');
        is_dir($this->tempPath) or mkdir($this->tempPath, 0755, true);

        $this->lockPath = runtime_path('lock');
        is_dir($this->lockPath) or mkdir($this->lockPath, 0755, true);

        @set_time_limit(3600);
        @ini_set('max_execution_time', '3600');
        @ini_set('memory_limit', '128M');

        ignore_user_abort(true);
    }

    public function __destruct()
    {
        ignore_user_abort(false);
    }

    /**
     * 优化表
     * @access public
     * @return bool
     */
    public function optimize(): bool
    {
        only_execute('db_optimize.lock', '-30 days', function () {
            $tables = $this->queryTableName();
            foreach ($tables as $name) {
                $result = $this->DB->query('ANALYZE TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    $this->DB->query('OPTIMIZE TABLE `' . $name . '`');
                    Log::alert('[AUTO BACKUP] 优化表' . $name);
                }
            }
        });

        return true;
    }

    public function repair()
    {
        only_execute('db_repair.lock', '-30 days', function () {
            $tables = $this->queryTableName();
            foreach ($tables as $name) {
                $result = $this->DB->query('CHECK TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    $this->DB->query('REPAIR TABLE `' . $name . '`');
                    Log::alert('[AUTO BACKUP] 修复表' . $name);
                }
            }
        });

        return true;
    }

    /**
     * 还原
     * @access public
     * @return void
     */
    public function restores(string $_backup): void
    {
        only_execute('db_backup.lock', false, function () use (&$_backup) {
            // 清空上次残留垃圾文件
            if ($files = glob($this->tempPath . '*')) {
                array_map('unlink', $files);
            }

            // 打开压缩包并解压文件到指定目录
            $zip = new \ZipArchive;
            if (true === $zip->open($this->savePath . $_backup)) {
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
                            $this->DB->query($sql);
                        } catch (\Exception $e) {
                            halt($sql, $e->getFile() . $e->getLine() . $e->getMessage());
                        }

                        // 持续查询状态并不利于处理任务，每10ms执行一次，此时释放CPU，降低机器负载
                        usleep(10000);
                    }
                    fclose($file);

                    // 修改原表名为旧数据表,并修改备份表名为原表名,保证还原时不会损坏原数据
                    try {
                        $table_name = pathinfo($filename, PATHINFO_FILENAME);
                        $this->DB->query('ALTER  TABLE `' . $table_name . '` RENAME TO `old_' . $table_name . '`');
                        $this->DB->query('ALTER  TABLE `backup_' . $table_name . '` RENAME TO `' . $table_name . '`');
                        $this->DB->query('DROP TABLE `old_' . $table_name . '`');
                    } catch (\Exception $e) {
                        halt($sql, $e->getFile() . $e->getLine() . $e->getMessage());
                    }

                    unlink($filename);
                }

                @rmdir($this->tempPath);
            }
        });
    }

    /**
     * 备份
     * @access public
     * @return void
     */
    public function backup(): void
    {
        only_execute('db_backup.lock', false, function () {
            // 清空上次残留垃圾文件
            if ($files = glob($this->tempPath . '*')) {
                array_map('unlink', $files);
            }

            $table_name = $this->queryTableName();
            shuffle($table_name);
            foreach ($table_name as $name) {
                $sql_file = $this->tempPath. $name . '.sql';

                // 获得表结构SQL语句
                $sql = $this->queryTableStructure($name);
                file_put_contents($sql_file, $sql);

                // 获得表字段和主键
                $field = $this->queryTableInsertField($name);

                $this->DB->table($name)->chunk(10, function ($result) use ($name, $field, $sql_file) {
                    $result = $result->toArray();
                    $sql = $this->getTableInsertData($name, $field, $result);
                    file_put_contents($sql_file, $sql, FILE_APPEND);
                    // 持续查询状态并不利于处理任务，每10ms执行一次，此时释放CPU，降低机器负载
                    usleep(10000);
                });
            }

            if ($files = glob($this->tempPath . '*')) {
                foreach ($files as $key => $filename) {
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if ('sql' !== $ext) {
                        unset($files[$key]);
                    }
                }

                if (!empty($files)) {
                    $zip_name = $this->savePath . date('YmdHis') . '.zip';
                    $zip = new \ZipArchive;
                    $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    foreach ($files as $filename) {
                        $zip->addFile($filename, pathinfo($filename, PATHINFO_BASENAME));
                    }
                    $zip->close();
                    foreach ($files as $filename) {
                        @unlink($filename);
                    }

                    @rmdir($this->tempPath);
                }
            }
        });
    }

    /**
     * 表数据SQL
     * @access private
     * @param  string $_table_name  表名
     * @param  string $_table_field 表字段
     * @param  array  $_data        表数据
     * @return string
     */
    private function getTableInsertData(string $_table_name, string $_table_field, array $_data): string
    {
        $sql = 'INSERT INTO `backup_' . $_table_name . '` (' . $_table_field . ') VALUES';

        foreach ($_data as $value) {
            $sql .= '(';
            foreach ($value as $vo) {
                // 过滤回车空格tab等符号
                $vo = preg_replace('/\s+/s', ' ', $vo);
                // 过滤多余空格
                $vo = preg_replace('/ {2,}/s', ' ', $vo);

                $vo = trim($vo);

                if (is_integer($vo)) {
                    $vo = (int) $vo;
                    $sql .= $vo . ',';
                } elseif (is_float($vo)) {
                    $vo = (float) $vo;
                    $sql .= $vo . ',';
                } elseif (is_null($vo) || $vo == 'null' || $vo == 'NULL') {
                    $sql .= 'NULL,';
                } else {
                    $sql .= '\'' . addslashes($vo) . '\',';
                }
            }
            $sql = rtrim($sql, ',') . '),';
        }
        return rtrim($sql, ',') . ';' . PHP_EOL;
    }

    /**
     * 查询表字段
     * @access private
     * @param  string $_table_name 表名
     * @return string
     */
    private function queryTableInsertField(string $_table_name): string
    {
        $result = $this->DB->query('SHOW COLUMNS FROM `' . $_table_name . '`');
        $field = '';
        foreach ($result as $value) {
            $field .= '`' . $value['Field'] . '`,';
        }
        $field = rtrim($field, ',');

        return $field;
    }

    /**
     * 查询表结构
     * @access private
     * @param  string $_table_name 表名
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $tableRes = $this->DB->query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (empty($tableRes[0]['Create Table'])) {
            return false;
        }
        $structure  = '-- 备份时间 ' . date('Y-m-d H:i:s') . PHP_EOL;
        $structure .= 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
        // 清除多余空格回车制表符等
        $structure .= preg_replace(['/\s+/s', '/ {2,}/si'], ' ', $tableRes[0]['Create Table']) . ';';
        $structure = trim($structure);

        // 原表名替换成备份表名,此操作避免恢复数据失败时直接覆盖原数据导致的不可逆转错误
        $structure = str_replace($_table_name, 'backup_' . $_table_name, $structure);

        // 删除自增主键记录
        $structure = preg_replace_callback('/(AUTO_INCREMENT=[0-9]+ DEFAULT)/si', function () {
            return 'DEFAULT';
        }, $structure);

        return $structure . PHP_EOL;
    }

    /**
     * 查询数据库表名
     * @access private
     * @return array
     */
    private function queryTableName(): array
    {
        $result = $this->DB->query('SHOW TABLES FROM ' . Env::get('database.database'));

        $tables = array();
        foreach ($result as $value) {
            $value = current($value);
            $tables[str_replace(Env::get('database.prefix'), '', $value)] = $value;
        }

        return $tables;
    }
}
