<?php

/**
 *
 * 数据维护类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\library;

class DataMaintenance
{
    private $savePath;

    public function __construct()
    {
        $this->savePath = app('config')->get('filesystem.disks.local.root') .
            DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;
        set_time_limit(28800);
    }

    /**
     * 自动优化,修复数据
     * @access public
     * @param
     * @return bool
     */
    public function autoOptimize(): bool
    {
        $lock = app()->getRuntimePath() . 'db_op.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-7 days')) {
            return false;
        }

        if ($fp = @fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                ignore_user_abort(true);

                $this->optimize();  // 优化表
                $this->repair();    // 修复表

                fwrite($fp, '优化|修复数据' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);

                ignore_user_abort(false);
            }
            fclose($fp);
        }
        return true;
    }

    /**
     * 自动备份
     * @access public
     * @param  int $_hour 相隔几小时更新备份
     * @return bool
     */
    public function autoBackup(int $_hour = 72): bool
    {
        $lock = app()->getRuntimePath() . 'db_auto_back.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-30 minute')) {
            return false;
        }

        if ($fp = fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                app('log')->record('[AUTO BACKUP] 自动备份数据库', 'info');

                $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
                if (!is_dir($this->savePath)) {
                    mkdir($this->savePath, 0755, true);
                }

                if (is_file($this->savePath . 'backup_time.json')) {
                    $btime = json_decode(file_get_contents($this->savePath . 'backup_time.json'), true);
                } else {
                    $btime = [];
                }

                ignore_user_abort(true);
                $table_name = $this->queryTableName();
                // shuffle($table_name);

                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';
                    if (!isset($btime[$name]) || !is_file($sql_file)) {
                        $sql = $this->queryTableStructure($name);
                        file_put_contents($sql_file, $sql, FILE_APPEND);
                        $btime[$name] = time();
                    } elseif (isset($btime[$name]) && $btime[$name] <= strtotime('-1 days')) {
                        $sql = $this->queryTableStructure($name);
                        file_put_contents($sql_file, $sql, FILE_APPEND);
                        $btime[$name] = time();
                        continue;
                    }

                    $total = $this->queryTableInsertTotal($name);
                    $field = $this->queryTableInsertField($name);
                    $num = 1;
                    for ($i = 0; $i < $total; $i++) {
                        $hour = '-' . mt_rand($_hour, $_hour + 3) . ' hour';
                        $sql_file = $this->savePath . $name . '_' . sprintf('%07d', $num) . '.sql';

                        if (isset($btime[$name . $num])) {
                            if (false !== $btime[$name . $num] && $btime[$name . $num] <= strtotime($hour) && is_file($sql_file)) {
                                unlink($sql_file);
                                $btime[$name . $num] = false;
                                break 2;
                            }
                            if (false === $btime[$name . $num]) {
                                $sql = $this->queryTableInsertData($name, $field, $i);
                                file_put_contents($sql_file, $sql, FILE_APPEND);
                            }
                        } elseif (!isset($btime[$name . $num])) {
                            $sql = $this->queryTableInsertData($name, $field, $i);
                            file_put_contents($sql_file, $sql, FILE_APPEND);
                            $btime[$name . $num] = false;
                        }

                        if (0 === ($i + 1) % 200) {
                            if (!isset($btime[$name . $num]) || false === $btime[$name . $num]) {
                                $btime[$name . $num] = time();
                            }
                            ++$num;
                        }
                    }
                    if (!isset($btime[$name . $num]) || false === $btime[$name . $num]) {
                        $btime[$name . $num] = time();
                    }
                }

                file_put_contents($this->savePath . 'backup_time.json', json_encode($btime));

                fwrite($fp, '自动备份数据库' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);
                ignore_user_abort(false);
            }
            fclose($fp);
        }

        return true;
    }

    /**
     * 备份
     * @access public
     * @param
     * @return bool
     */
    public function backup(): bool
    {
        $lock = app()->getRuntimePath() . 'db_back.lock';
        if ($fp = fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $this->savePath .= date('YmdHis') . DIRECTORY_SEPARATOR;
                if (!is_dir($this->savePath)) {
                    mkdir($this->savePath, 0755, true);
                }

                $table_name = $this->queryTableName();

                ignore_user_abort(true);
                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';
                    if ($sql = $this->queryTableStructure($name)) {
                        file_put_contents($sql_file, $sql, FILE_APPEND);
                    }

                    $total = $this->queryTableInsertTotal($name);
                    $field = $this->queryTableInsertField($name);
                    for ($limit = 0; $limit < $total; $limit++) {
                        $sql = $this->queryTableInsertData($name, $field, $limit);
                        file_put_contents($sql_file, $sql, FILE_APPEND);
                    }
                }

                fwrite($fp, '备份数据库' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);
                ignore_user_abort(false);
            }
            fclose($fp);
        }

        return true;
    }

    /**
     * 优化表
     * @access public
     * @param
     * @return bool
     */
    public function optimize(): bool
    {
        app('log')->record('[DATAMAINTENANCE OPTIMIZE] 优化表', 'info');
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->analyze($name)) {
                app('think\DbManager')->query('OPTIMIZE TABLE `' . $name . '`');
            }
        }
        return true;
    }

    /**
     * 修复表
     * @access public
     * @param
     * @return bool
     */
    public function repair(): bool
    {
        app('log')->record('[DATAMAINTENANCE REPAIR] 修复表', 'info');
        $config = app('think\DbManager')->getConfig();
        $config['params'][\PDO::ATTR_EMULATE_PREPARES] = true;
        app('think\DbManager')->connect($config, true);
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->check($name)) {
                app('think\DbManager')->query('REPAIR TABLE `' . $name . '`');
            }
        }
        return true;
    }

    /**
     * 分析表
     * @access private
     * @param  string  $_table_name
     * @return bool
     */
    private function analyze(string $_table_name): bool
    {
        $result = app('think\DbManager')->query('ANALYZE TABLE `' . $_table_name . '`');
        return strtolower($result[0]['Msg_type']) === 'status';
    }

    /**
     * 检查表
     * @access private
     * @param  string  $_table_name
     * @return bool
     */
    private function check(string $_table_name): bool
    {
        $result = app('think\DbManager')->query('CHECK TABLE `' . $_table_name . '`');
        return strtolower($result[0]['Msg_type']) === 'status';
    }

    /**
     * 查询表数据
     * @access private
     * @param
     * @return string
     */
    private function queryTableInsertData(string $_table_name, string $_field, int $_limit): string
    {
        $_limit = $_limit * 200;
        $data = app('think\DbManager')->table($_table_name)
            ->field($_field)
            ->limit($_limit, 200)
            ->select();

        $insert_into = 'INSERT INTO `' . $_table_name . '` (' . $_field . ') VALUES' . PHP_EOL;
        $insert_data = '';
        foreach ($data as $value) {
            $insert_data .= '(';
            foreach ($value as $vo) {
                if (is_integer($vo)) {
                    $insert_data .= $vo . ',';
                } elseif (is_null($vo) || $vo == 'null' || $vo == 'NULL') {
                    $insert_data .= 'NULL,';
                } else {
                    $insert_data .= '\'' . addslashes($vo) . '\',';
                }
            }
            $insert_data = trim($insert_data, ',') . '),' . PHP_EOL;
        }
        $insert_data = trim($insert_data, ',' . PHP_EOL) . ';';

        return $insert_into . $insert_data . PHP_EOL;
    }

    /**
     * 查询表字段
     * @access private
     * @param
     * @return string
     */
    private function queryTableInsertField(string $_table_name): string
    {
        $result = app('think\DbManager')->query('SHOW COLUMNS FROM `' . $_table_name . '`');
        $field = '';
        foreach ($result as $value) {
            $field .= '`' . $value['Field'] . '`,';
        }
        $field = trim($field, ',');

        return $field;
    }

    /**
     * 查询表数据总数
     * @access private
     * @param
     * @return int
     */
    private function queryTableInsertTotal(string $_table_name): int
    {
        $total = app('think\DbManager')->table($_table_name)->count();
        return $total ? (int) ceil($total / 200) : 0;
    }

    /**
     * 查询表结构
     * @access private
     * @param
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $tableRes = app('think\DbManager')->query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (empty($tableRes[0]['Create Table'])) {
            return false;
        }

        $structure  = 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
        $structure .= $tableRes[0]['Create Table'] . ';' . PHP_EOL;

        return preg_replace_callback('/(AUTO_INCREMENT=[0-9]+ DEFAULT)/si', function ($matches) {
            return 'DEFAULT';
        }, $structure);
    }

    /**
     * 查询数据库表名
     * @access private
     * @param
     * @return array
     */
    private function queryTableName(): array
    {
        $result = app('think\DbManager')->query('SHOW TABLES FROM ' . app('env')->get('database.database'));

        $tables = array();
        foreach ($result as $value) {
            $value = current($value);
            $tables[str_replace(app('env')->get('database.prefix'), '', $value)] = $value;
        }
        return $tables;
    }
}
