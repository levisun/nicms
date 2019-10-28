<?php

/**
 *
 * 数据维护类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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
    private $lockPath;

    public function __construct()
    {
        $this->DB = app('think\DbManager');

        $this->savePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;
        is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

        $this->lockPath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($this->lockPath) or mkdir($this->lockPath, 0755, true);

        @ini_set('memory_limit', '64M');
        set_time_limit(28800);
    }

    /**
     * 优化表
     * @access public
     * @return bool
     */
    public function optimize(): bool
    {
        $lock = $this->lockPath . 'db_optimize.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-7 days')) {
            return false;
        }

        if ($fp = @fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                ignore_user_abort(true);

                $tables = $this->queryTableName();
                foreach ($tables as $name) {
                    $result = $this->DB->query('ANALYZE TABLE `' . $name . '`');
                    $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                    if (false === $result) {
                        $this->DB->query('OPTIMIZE TABLE `' . $name . '`');
                        Log::record('[AUTO BACKUP] 优化表' . $name, 'alert');
                    }

                    $result = $this->DB->query('CHECK TABLE `' . $name . '`');
                    $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                    if (false === $result) {
                        $this->DB->query('REPAIR TABLE `' . $name . '`');
                        Log::record('[AUTO BACKUP] 修复表' . $name, 'alert');
                    }
                }

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
     * @return bool
     */
    public function autoBackup(): bool
    {
        $lock = $this->lockPath . 'db_auto_back.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-10 minute')) {
            return false;
        }

        if ($fp = fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
                is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

                // 备份记录文件
                if (is_file($this->savePath . 'backup_time.json')) {
                    $btime = json_decode(file_get_contents($this->savePath . 'backup_time.json'), true);
                } else {
                    $btime = [];
                }

                ignore_user_abort(true);
                $table_name = $this->queryTableName();
                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';

                    if (!isset($btime[$name]) || strtotime($btime[$name]) <= strtotime('-3 days')) {
                        $btime[$name] = date('Y-m-d H:i:s');
                        $sql = $this->queryTableStructure($name);
                        file_put_contents($sql_file, $sql);

                        $zip_name = pathinfo($sql_file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
                            pathinfo($sql_file, PATHINFO_FILENAME) . '.zip';
                        $zip = new \ZipArchive;
                        if (true === $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                            $zip->addFile($sql_file, pathinfo($sql_file, PATHINFO_BASENAME));
                            $zip->close();
                            @unlink($sql_file);
                        }
                    }



                    if ($total = $this->DB->table($name)->count()) {
                        $total = $total ? (int) ceil($total / 100000) : 0;
                        $field = $this->queryTableInsertField($name);
                        $pk = $this->DB->table($name)->getPk();
                        for ($i = 1; $i <= $total; $i++) {
                            $num = $name . '_' . sprintf('%07d', $i);
                            if (!isset($btime[$num]) || strtotime($btime[$num]) <= strtotime('-3 days')) {
                                $btime[$num] = date('Y-m-d H:i:s');
                                $sql_file = $this->savePath . $num . '.sql';
                                $this->DB->table($name)
                                    ->where([
                                        [$pk, '>', ($i - 1) * 100000],
                                        [$pk, '<=', $i * 100000]
                                    ])
                                    ->chunk(100, function ($result) use ($name, $field, $sql_file) {
                                        $result = $result->toArray();
                                        $sql = $this->getTableInsertData($name, $field, $result);
                                        file_put_contents($sql_file, $sql, FILE_APPEND);
                                    });

                                $zip_name = pathinfo($sql_file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
                                    pathinfo($sql_file, PATHINFO_FILENAME) . '.zip';
                                $zip = new \ZipArchive;
                                if (true === $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                                    $zip->addFile($sql_file, pathinfo($sql_file, PATHINFO_BASENAME));
                                    $zip->close();
                                    @unlink($sql_file);
                                }

                                Log::record('[AUTO BACKUP] 自动备份数据库' . $num, 'alert');
                                break;
                            }
                        }
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
     * @return bool
     */
    public function backup(): bool
    {
        $lock = $this->lockPath . 'db_back.lock';

        if ($fp = fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $this->savePath .= date('YmdHis') . DIRECTORY_SEPARATOR;
                if (!is_dir($this->savePath)) {
                    mkdir($this->savePath, 0755, true);
                }

                ignore_user_abort(true);
                $table_name = $this->queryTableName();
                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';

                    // 写入表结构文件
                    if ($sql = $this->queryTableStructure($name)) {
                        file_put_contents($sql_file, $sql);
                    }

                    if ($field = $this->queryTableInsertField($name)) {
                        $this->DB->table($name)
                            ->chunk(100, function ($result) use ($name, $field, $sql_file) {
                                $result = $result->toArray();
                                $sql = $this->getTableInsertData($name, $field, $result);
                                file_put_contents($sql_file, $sql, FILE_APPEND);
                            });
                    }
                }

                $zip = new \ZipArchive;
                $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
                    'backup' . DIRECTORY_SEPARATOR .
                    pathinfo($this->savePath, PATHINFO_BASENAME) . '.zip';
                if (true === $zip->open($path, \ZipArchive::CREATE)) {
                    $dir = (array) glob($this->savePath . '*');
                    foreach ($dir as $name) {
                        $zip->addFile($name, pathinfo($name, PATHINFO_BASENAME));
                    }
                    $zip->close();
                    foreach ($dir as $name) {
                        unlink($name);
                    }
                    rmdir($this->savePath);
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
     * 表数据SQL
     * @access private
     * @return string
     */
    private function getTableInsertData(string $_table_name, string $_table_field, array $_data): string
    {
        $sql  = '-- ' . date('Y-m-d H:i:s') . PHP_EOL;
        $sql .= 'INSERT INTO `' . $_table_name . '` (' . $_table_field . ') VALUES' . PHP_EOL;

        foreach ($_data as $value) {
            $sql .= '(';
            foreach ($value as $vo) {
                if (is_integer($vo)) {
                    $sql .= $vo . ',';
                } elseif (is_null($vo) || $vo == 'null' || $vo == 'NULL') {
                    $sql .= 'NULL,';
                } else {
                    $sql .= '\'' . addslashes($vo) . '\',';
                }
            }
            $sql = trim($sql, ',') . '),' . PHP_EOL;
        }
        return trim($sql, ',' . PHP_EOL) . ';' . PHP_EOL;
    }

    /**
     * 查询表字段
     * @access private
     * @return string
     */
    private function queryTableInsertField(string $_table_name): string
    {
        $result = $this->DB->query('SHOW COLUMNS FROM `' . $_table_name . '`');
        $field = '';
        foreach ($result as $value) {
            $field .= '`' . $value['Field'] . '`,';
        }
        $field = trim($field, ',');

        return $field;
    }

    /**
     * 查询表结构
     * @access private
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $tableRes = $this->DB->query('SHOW CREATE TABLE `' . $_table_name . '`');
        if (empty($tableRes[0]['Create Table'])) {
            return false;
        }

        $structure  = '-- ' . date('Y-m-d H:i:s') . PHP_EOL;
        $structure .= 'DROP TABLE IF EXISTS `' . $_table_name . '`;' . PHP_EOL;
        $structure .= $tableRes[0]['Create Table'] . ';' . PHP_EOL;

        $structure = preg_replace_callback('/(AUTO_INCREMENT=[0-9]+ DEFAULT)/si', function () {
            return 'DEFAULT';
        }, $structure);

        return $structure;
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
