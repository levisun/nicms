<?php
/**
 *
 * 数据库备份类
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

class DbBackup
{
    private $savePath;

    public function __construct()
    {
        $this->savePath = app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR;
        clearstatcache();
        set_time_limit(0);
    }

    public function auto()
    {
        $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);
        }

        if (!is_file($this->savePath . 'backup.lock')) {
            file_put_contents($this->savePath . 'backup.lock', 'lock');
            $table_name = $this->queryTableName();

            foreach ($table_name as $name) {
                $sql_file = $this->savePath . $name . '.sql';
                if (!is_file($sql_file) || (is_file($sql_file) && filemtime($sql_file) >= strtotime('-' . rand(24, 48) . ' hour'))) {
                    if ($sql = $this->queryTableStructure($name)) {
                        $this->write($sql_file, $sql);
                        continue;
                    }
                }

                $total = $this->queryTableInsertTotal($name);
                $field = $this->queryTableInsertField($name);

                $num = 1;
                $sql = '';
                $sql_file = $this->savePath . $name . '_' . sprintf('%07d', $num) . '.sql';
                for ($limit = 0; $limit < $total; $limit++) {
                    if (!is_file($sql_file) || (is_file($sql_file) && filemtime($sql_file) >= strtotime('-' . rand(24, 48) . ' hour'))) {
                        $sql .= $this->queryTableInsertData($name, $field, $limit);
                        if (strlen($sql) >= 1024 * 1024 * 5) {
                            $this->write($sql_file, $sql);
                            $sql = '';
                            ++$num;
                        } elseif ($limit + 1 == $total) {
                            $this->write($sql_file, $sql);
                            $sql = '';
                            ++$num;
                            continue;
                        }
                    }
                }
            }
        }
    }

    private function queryTableInsertData(string $_table_name, string $_field, int $_limit)
    {
        $_limit = $_limit * 500;
        $data =
        Db::table($_table_name)
        ->field($_field)
        ->limit($_limit, 500)
        ->select();

        $insert_into = 'INSERT INTO `' . $_table_name . '` (' . $_field . ') VALUES' . PHP_EOL;
        foreach ($data as $key => $value) {
            foreach ($value as $vo) {
                if (is_integer($vo)) {
                    $insert_data .= $vo . ',';
                } elseif (is_null($vo) || $vo == 'null' || $vo == 'NULL') {
                    $insert_data .= 'NULL,';
                } else {
                    $insert_data .= '\'' . addslashes($vo) . '\',';
                }
            }
            $insert_data = trim($insert_data, ',') . '),' . PHP_EOL . '(';
        }
        $insert_data = '(' . trim($insert_data, ',' . PHP_EOL . '(') . ';' . PHP_EOL;

        return $insert_into . $insert_data;
    }

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

    private function queryTableInsertTotal(string $_table_name): int
    {
        $total = Db::table($_table_name)->count();
        if ($total) {
            return ceil($total / 500);
        } else {
            return 0;
        }
    }

    /**
     * 表结构
     * @access private
     * @param
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $tableRes = Db::query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (!empty($tableRes[0]['Create Table'])) {
            $time = '/* ' . date('Y-m-d H:i:s') . ' */';
            $structure  = 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
            $structure .= $tableRes[0]['Create Table'] . ';';
            return $structure;
        } else {
            return false;
        }
    }

    /**
     * 数据库表名
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
        Log::record('[BACKUP] #' . pathinfo($_file, PATHINFO_BASENAME), 'alert');
        $_data = '/*' . date('Y-m-d H:i:s') . '*/' . $_data;
        file_put_contents($_file, gzcompress($_data));
    }
}
