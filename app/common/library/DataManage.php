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
    private $lockPath;

    public function __construct()
    {
        $this->DB = app('think\DbManager');

        $this->savePath = runtime_path('backup');
        is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

        $this->lockPath = runtime_path('lock');
        is_dir($this->lockPath) or mkdir($this->lockPath, 0755, true);

        @set_time_limit(3600);
        @ini_set('max_execution_time', '3600');
        @ini_set('memory_limit', '128M');
    }

    /**
     * 优化表
     * @access public
     * @return bool
     */
    public function optimize(): bool
    {
        only_execute('db_optimize.lock', '-30 days', function () {
            ignore_user_abort(true);

            $tables = $this->queryTableName();
            foreach ($tables as $name) {
                $result = $this->DB->query('ANALYZE TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    $this->DB->query('OPTIMIZE TABLE `' . $name . '`');
                    Log::alert('[AUTO BACKUP] 优化表' . $name);
                }
            }

            ignore_user_abort(false);
        });

        return true;
    }

    public function repair()
    {
        only_execute('db_repair.lock', '-30 days', function () {
            ignore_user_abort(true);

            $tables = $this->queryTableName();
            foreach ($tables as $name) {
                $result = $this->DB->query('CHECK TABLE `' . $name . '`');
                $result = isset($result[0]['Msg_type']) ? strtolower($result[0]['Msg_type']) === 'status' : true;
                if (false === $result) {
                    $this->DB->query('REPAIR TABLE `' . $name . '`');
                    Log::alert('[AUTO BACKUP] 修复表' . $name);
                }
            }

            ignore_user_abort(false);
        });

        return true;
    }

    /**
     * 自动备份
     * @access public
     * @return bool
     */
    public function autoBackup(): bool
    {
        only_execute('db_auto_back.lock', '-10 minute', function () {
            ignore_user_abort(true);

            $this->savePath .= 'sys_auto' . DIRECTORY_SEPARATOR;
            is_dir($this->savePath) or mkdir($this->savePath, 0755, true);

            // 备份记录文件
            if (is_file($this->savePath . 'backup_time.json')) {
                $btime = json_decode(file_get_contents($this->savePath . 'backup_time.json'), true);
            } else {
                $btime = [];
            }

            // 获得库中所有的表名
            $table_name = $this->queryTableName();
            foreach ($table_name as $name) {
                $sql_file = $this->savePath . $name . '.sql';

                // 检查表结构备份是否存在或过期
                if (!isset($btime[$name]) || strtotime($btime[$name]) <= strtotime('-3 days')) {
                    // 记录新的备份时间
                    $btime[$name] = date('Y-m-d H:i:s');

                    // 获得表结构SQL语句
                    $sql = $this->queryTableStructure($name);
                    file_put_contents($sql_file, $sql);

                    // 压缩SQL文件
                    $zip_name = pathinfo($sql_file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
                        pathinfo($sql_file, PATHINFO_FILENAME) . '.zip';
                    $zip = new \ZipArchive;
                    if (true === $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                        $zip->addFile($sql_file, pathinfo($sql_file, PATHINFO_BASENAME));
                        $zip->close();
                        @unlink($sql_file);
                    }
                }

                // 获得表总数据
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

                            Log::alert('[AUTO BACKUP] 自动备份数据库' . $num);
                            break;
                        }
                    }
                }
            }
            file_put_contents($this->savePath . 'backup_time.json', json_encode($btime));

            ignore_user_abort(false);
        });

        return true;
    }

    /**
     * 备份
     * @access public
     * @return bool
     */
    public function backup(): bool
    {
        only_execute('db_back.lock', false, function () {
            ignore_user_abort(true);

            $this->savePath .= date('YmdHis') . DIRECTORY_SEPARATOR;
            if (!is_dir($this->savePath)) {
                mkdir($this->savePath, 0755, true);
            }

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
            $path = runtime_path('backup') . pathinfo($this->savePath, PATHINFO_BASENAME) . '.zip';
            if (true === $zip->open($path, \ZipArchive::CREATE)) {
                if ($dir = glob($this->savePath . '*')) {
                    foreach ($dir as $name) {
                        $zip->addFile($name, pathinfo($name, PATHINFO_BASENAME));
                    }
                    $zip->close();
                    foreach ($dir as $name) {
                        unlink($name);
                    }
                }
                // $dir = (array) glob($this->savePath . '*');
                // foreach ($dir as $name) {
                //     $zip->addFile($name, pathinfo($name, PATHINFO_BASENAME));
                // }
                // $zip->close();
                // foreach ($dir as $name) {
                //     unlink($name);
                // }
                rmdir($this->savePath);
            }

            ignore_user_abort(false);
        });

        return true;
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
            $sql = rtrim($sql, ',') . '),' . PHP_EOL;
        }
        return rtrim($sql, ',' . PHP_EOL) . ';' . PHP_EOL;
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
