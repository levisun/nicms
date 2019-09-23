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

class Data
{
    private $DB = null;
    private $cache = null;

    private $savePath;
    private $lockPath;

    public function __construct()
    {
        $this->DB = app('think\DbManager');
        $this->cache = app('cache');

        $this->savePath = app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0755, true);
        }

        $this->lockPath = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->lockPath)) {
            mkdir($this->lockPath, 0755, true);
        }

        @ini_set('memory_limit', '64M');
        set_time_limit(28800);
    }

    /**
     * 备份
     * @access public
     * @param
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

                $table_name = $this->queryTableName();

                ignore_user_abort(true);
                foreach ($table_name as $name) {
                    $sql_file = $this->savePath . $name . '.sql';

                    // 写入表结构文件
                    if ($sql = $this->queryTableStructure($name)) {
                        file_put_contents($sql_file, $sql);
                    }

                    if ($field = $this->queryTableInsertField($name)) {
                        $this->DB->table($name)
                            ->chunk(100, function ($result) use ($name, $field, $sql_file) {
                                $sql  = '-- ' . date('Y-m-d H:i:s') . PHP_EOL;
                                $sql .= 'INSERT INTO `' . $name . '` (' . $field . ') VALUES' . PHP_EOL;
                                $result = $result->toArray();
                                foreach ($result as $value) {
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
                                $sql = trim($sql, ',' . PHP_EOL) . ';';
                                file_put_contents($sql_file, $sql, FILE_APPEND);
                            });
                    }
                }

                $zip = new \ZipArchive;
                $path = app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR .
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
     * 查询表字段
     * @access private
     * @param
     * @return string
     */
    private function queryTableInsertField(string $_table_name): string
    {
        $cache_key = md5(__METHOD__ . $_table_name);
        if (!$this->cache->has($cache_key) || !$field = $this->cache->get($cache_key)) {
            $result = $this->DB->query('SHOW COLUMNS FROM `' . $_table_name . '`');
            $field = '';
            foreach ($result as $value) {
                $field .= '`' . $value['Field'] . '`,';
            }
            $field = trim($field, ',');

            $this->cache->tag('SYSTEM')->set($cache_key, $field);
        }

        return $field;
    }

    /**
     * 查询表结构
     * @access private
     * @param
     * @return bool|string
     */
    private function queryTableStructure(string $_table_name)
    {
        $cache_key = md5(__METHOD__ . $_table_name);
        if (!$this->cache->has($cache_key) || !$structure = $this->cache->get($cache_key)) {
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

            $this->cache->tag('SYSTEM')->set($cache_key, $structure);
        }

        return $structure;
    }

    /**
     * 查询数据库表名
     * @access private
     * @param
     * @return array
     */
    private function queryTableName(): array
    {
        $cache_key = md5(__METHOD__);
        if (!$this->cache->has($cache_key) || !$tables = $this->cache->get($cache_key)) {
            $result = $this->DB->query('SHOW TABLES FROM ' . app('env')->get('database.database'));

            $tables = array();
            foreach ($result as $value) {
                $value = current($value);
                $tables[str_replace(app('env')->get('database.prefix'), '', $value)] = $value;
            }

            $this->cache->tag('SYSTEM')->set($cache_key, $tables);
        }

        return $tables;
    }
}
