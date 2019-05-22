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
declare (strict_types = 1);

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
     * 备份
     * @access public
     * @param
     * @return bool
     */
    public function backup(string $_type = 'date'): bool
    {
        if ($_type == 'date') {
            $this->savePath .= date('YmdHis') . DIRECTORY_SEPARATOR;
        } else {
            $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
        }
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);
        }

        if (!is_file($this->savePath . 'backup.lock')) {
            file_put_contents($this->savePath . 'backup.lock', 'lock');
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
            unlink($this->savePath . 'backup.lock');
            ignore_user_abort(false);
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
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->analyze($name)) {
                Db::query('OPTIMIZE TABLE `' . $name . '`');
                Log::record('[DATAMAINTENANCE OPTIMIZE] #' . $name, 'alert');
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
        $config = Db::getConfig();
        $config['params'][\PDO::ATTR_EMULATE_PREPARES] = true;
        Db::connect($config, true);
        $tables = $this->queryTableName();
        foreach ($tables as $name) {
            if (false === $this->check($name)) {
                Db::query('REPAIR TABLE `' . $name . '`');
                Log::record('[DATAMAINTENANCE REPAIR] #' . $name, 'alert');
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
        foreach ($data as $key => $value) {
            $insert_data = '(';
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
        foreach ($result as $key => $value) {
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
            return (int)ceil($total / 500);
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
        foreach ($result as $key => $value) {
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
        Log::record('[DATAMAINTENANCE BACKUP] #' . pathinfo($_file, PATHINFO_BASENAME), 'alert');
        file_put_contents($_file, $_data, FILE_APPEND);
    }
}
