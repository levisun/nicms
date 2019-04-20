<?php
/**
 *
 * 备份类
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

class Backup
{
    private $savePath;

    public function auto(): void
    {
        Log::record('[BACKUP] 备份', 'alert');

        clearstatcache();
        $this->savePath = app()->getRuntimePath() .
                            'backup' . DIRECTORY_SEPARATOR .
                            'sys_auto' . DIRECTORY_SEPARATOR;

        ignore_user_abort(true);
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);
        }

        if (!is_file($this->savePath . 'backup.lock')) {
            file_put_contents($this->savePath . 'backup.lock', 'lock');
            $result = $this->queryTableName();
            foreach ($result as $key => $name) {
                file_put_contents($this->savePath . 'backup.lock', $name);
                if (rand(1, 2) === 1) {
                    continue;
                }
                $this->queryTableStructure($name);
                $this->queryTableInsert($name);
            }
            unlink($this->savePath . 'backup.lock');
        }
        ignore_user_abort(false);
        clearstatcache();
    }

    public function save()
    {
        clearstatcache();
        $this->savePath = app()->getRuntimePath() .
                            'backup' . DIRECTORY_SEPARATOR .
                            date('ymdHis') .DIRECTORY_SEPARATOR;

        clearstatcache();
        ignore_user_abort(true);
        if (!is_dir($this->savePath)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->savePath, 0777, true);

            $result = $this->queryTableName();
            foreach ($result as $key => $name) {
                $this->queryTableStructure($name);
                $this->queryTableInsert($name);
            }
        }
        ignore_user_abort(false);
        clearstatcache();
    }

    /**
     * 表数据
     * @access private
     * @param
     * @return void
     */
    private function queryTableInsert(string $_table_name, int $_num = 1000): void
    {
        set_time_limit(0);

        $result = Db::query('SHOW COLUMNS FROM `' . $_table_name . '`');
        $field = '';
        foreach ($result as $key => $value) {
            $field .= '`' . $value['Field'] . '`,';
        }
        $field = trim($field, ',');

        $total = Db::table($_table_name)->count();
        $total = ceil($total / $_num);

        $num = 1;
        $sql_file = $this->savePath . $_table_name . '_' . sprintf('%07d', $num) . '.sql';
        $insert_into = 'INSERT INTO `' . $_table_name . '` (' . $field . ') VALUES' . PHP_EOL;
        $insert_data = '';

        for ($i = 0; $i < $total; $i++) {
            if (is_file($sql_file) && filemtime($sql_file) >= strtotime('-1 hour')) {
                continue;
            }

            $first_row = $i * $_num;
            $data = Db::table($_table_name)
            ->field($field)
            ->limit($first_row, $_num)
            ->select();

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

                if ($key % 10 == 0 && strlen($insert_data) >= 1024 * 1024 * 3) {
                    if ($insert_data) {
                        $insert_data = '(' . trim($insert_data, ',' . PHP_EOL . '(') . ';' . PHP_EOL;
                        $this->write($sql_file, $insert_into . $insert_data);
                        $num++;
                        $sql_file = $this->savePath . $_table_name . '_' . sprintf('%07d', $num) . '.sql';
                        $insert_data = '';
                    }
                }
            }
        }

        if ($insert_data) {
            $insert_data = '(' . trim($insert_data, ',' . PHP_EOL . '(') . ';' . PHP_EOL;
            $this->write($sql_file, $insert_into . $insert_data);
        }
        unset($sql_file, $insert_data, $num, $data, $result);
    }

    /**
     * 表结构
     * @access private
     * @param
     * @return void
     */
    private function queryTableStructure(string $_table_name)
    {
        set_time_limit(0);
        $sql_file = $this->savePath . $_table_name . '.sql';

        if (is_file($sql_file) && filemtime($sql_file) >= strtotime('-1 hour')) {
            return false;
        }

        $tableRes = Db::query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (!empty($tableRes[0]['Create Table'])) {
            $time = '/* ' . date('Y-m-d H:i:s') . ' */';
            $structure  = 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
            $structure .= $tableRes[0]['Create Table'] . ';';
            $this->write($this->savePath . $_table_name . '.sql', $structure);
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
        set_time_limit(0);

        $result = Db::query('SHOW TABLES FROM ' . Config::get('database.database'));
        $tables = array();
        foreach ($result as $key => $value) {
            $value = current($value);
            $tables[str_replace(Config::get('database.prefix'), '', $value)] = $value;
        }
        return $tables;
    }

    /**
     * 读取SQL文件
     * @access public
     * @param  string $_sql
     * @return void
     */
    public function exec(string $_sql): void
    {
        $_sql = trim($_sql);
        $_sql = preg_replace('/(\/\*)(.*?)(\*\/)/si', '', $_sql);
        try {
            Db::query($_sql);
        } catch (Exception $e) {
            die();
        }
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
        $_data = '/*' . date('Y-m-d H:i:s') . '*/' . ($_data);
        file_put_contents($_file, $_data);
    }
}
