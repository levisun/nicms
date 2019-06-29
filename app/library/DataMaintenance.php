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

use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class DataMaintenance
{
    private $savePath;

    public function __construct()
    {
        $this->savePath = app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR;
        clearstatcache();
        set_time_limit(0);
    }

    /**
     * 自动更新
     * @access public
     * @param  int $_hour 相隔几小时更新备份
     * @return bool
     */
    public function autoBackup(int $_hour = 72): bool
    {
        $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);
        }

        $path = app()->getRuntimePath() . 'db_auto_back.lock';
        if ($fp = fopen($path, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                Log::record('[AUTO BACKUP] 自动备份数据库', 'alert');

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
                        $this->write($sql_file, $sql);
                        $btime[$name] = time();
                    } elseif (isset($btime[$name]) && $btime[$name] <= strtotime('-1 days')) {
                        $sql = $this->queryTableStructure($name);
                        $this->write($sql_file, $sql);
                        $btime[$name] = time();
                        continue;
                    }

                    $total = $this->queryTableInsertTotal($name);
                    $field = $this->queryTableInsertField($name);
                    $num = 1;
                    for ($i = 0; $i < $total; $i++) {
                        $hour = '-' . rand($_hour, $_hour + 7) . ' hour';
                        $sql_file = $this->savePath . $name . '_' . sprintf('%07d', $num) . '.sql';

                        if (isset($btime[$name . $num])) {
                            if (false !== $btime[$name . $num] && $btime[$name . $num] <= strtotime($hour) && is_file($sql_file)) {
                                unlink($sql_file);
                                $btime[$name . $num] = false;
                                break 2;
                            }
                            if (false === $btime[$name . $num]) {
                                $sql = $this->queryTableInsertData($name, $field, $i);
                                $this->write($sql_file, $sql);
                            }
                        } elseif (!isset($btime[$name . $num])) {
                            $sql = $this->queryTableInsertData($name, $field, $i);
                            $this->write($sql_file, $sql);
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
                ignore_user_abort(false);

                fwrite($fp, '自动备份数据库' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);
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
        $this->savePath .= date('YmdHis') . DIRECTORY_SEPARATOR;
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);
        }

        $path = app()->getRuntimePath() . 'db_back.lock';
        if ($fp = fopen($path, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $table_name = $this->queryTableName();

                ignore_user_abort(true);
                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';
                    if ($sql = $this->queryTableStructure($name)) {
                        $this->write($sql_file, $sql);
                    }

                    $total = $this->queryTableInsertTotal($name);
                    $field = $this->queryTableInsertField($name);
                    for ($limit = 0; $limit < $total; $limit++) {
                        $sql = $this->queryTableInsertData($name, $field, $limit);
                        $this->write($sql_file, $sql);
                    }
                }
                ignore_user_abort(false);

                fwrite($fp, '备份数据库' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);
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
        Log::record('[DATAMAINTENANCE OPTIMIZE] 优化表', 'alert');
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->analyze($name)) {
                Db::query('OPTIMIZE TABLE `' . $name . '`');
                Log::record($name, 'alert');
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
        Log::record('[DATAMAINTENANCE REPAIR] 修复表', 'alert');
        $config = Db::getConfig();
        $config['params'][\PDO::ATTR_EMULATE_PREPARES] = true;
        Db::connect($config, true);
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->check($name)) {
                Db::query('REPAIR TABLE `' . $name . '`');
                Log::record($name, 'alert');
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
        $result = Db::query('ANALYZE TABLE `' . $_table_name . '`');
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
        $result = Db::query('CHECK TABLE `' . $_table_name . '`');
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
        $_limit = $_limit * 500;
        $data = Db::table($_table_name)
            ->field($_field)
            ->limit($_limit, 500)
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
        $result = Db::query('SHOW COLUMNS FROM `' . $_table_name . '`');
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
        $total = Db::table($_table_name)->count();
        if ($total) {
            return (int) ceil($total / 500);
        } else {
            return 0;
        }
    }

    /**
     * 查询表结构
     * @access private
     * @param
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $tableRes = Db::query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (!empty($tableRes[0]['Create Table'])) {
            $structure  = 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
            $structure .= $tableRes[0]['Create Table'] . ';' . PHP_EOL;
            return preg_replace_callback('/(AUTO_INCREMENT=[0-9]+ DEFAULT)/si', function ($matches) {
                return 'DEFAULT';
            }, $structure);
        } else {
            return false;
        }
    }

    /**
     * 查询数据库表名
     * @access private
     * @param
     * @return array
     */
    private function queryTableName(): array
    {
        $result = Db::query('SHOW TABLES FROM ' . Config::get('database.database'));
        $tables = array();
        foreach ($result as $value) {
            $value = current($value);
            $tables[str_replace(Config::get('database.prefix'), '', $value)] = $value;
        }
        return $tables;
    }

    /**
     * 写入SQL文件
     * @access private
     * @param  string $_file
     * @param  string $_data
     * @return void
     */
    private function write(string $_file, string $_data): void
    {
        // Log::record(pathinfo($_file, PATHINFO_BASENAME), 'alert');
        file_put_contents($_file, $_data, FILE_APPEND);
    }
}
