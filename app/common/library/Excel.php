<?php

/**
 *
 * 爬虫
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel
{

    /**
     * 读取Excel数据
     * @access public
     * @param
     * @return mixed
     */
    public function read(string $_file, int $_sheet = 0)
    {
        if (!$_file) {
            return false;
        }

        $cache_key = $_file . $_sheet;

        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $ext = strtolower(pathinfo($_file, PATHINFO_EXTENSION));
            if (in_array($ext, ['xlsx', 'xls'])) {
                $spreadsheet = IOFactory::load($_file);
                $result = $spreadsheet->getSheet($_sheet)->toArray();
                Cache::set($cache_key, $result);
            }
        }

        return $result ?: null;
    }

    /**
     * 生成Excel
     * @access public
     * @param
     * @return mixed
     */
    public function write(array $_data, int $_sheet = 0)
    {
        if (!$_data) {
            return false;
        }

        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($_sheet);

        foreach ($_data as $line_no => $column) {
            if (!empty($column)) {
                $line_no += 1;
                foreach ($column as $column_no => $value) {
                    $value = trim($value);
                    if (!empty($value)) {
                        $column_no += 1;
                        $worksheet->setCellValueByColumnAndRow($column_no, $line_no, $value);
                    }
                }
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $file = runtime_path('temp') . uniqid() . '.xlsx';
        $writer->save($file);

        return $file;
    }
}
