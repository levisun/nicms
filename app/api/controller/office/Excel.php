<?php

/**
 *
 * API接口层
 * office Excel读取导出
 *
 * @package   NICMS
 * @category  app\api\controller\office
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\office;

use think\Response;
use app\common\library\api\Async;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel extends Async
{

    /**
     * 读取Excel数据
     * @access public
     * @param
     * @return Response
     */
    public function read()
    {
        if ($this->validate->referer() && $file = $this->request->param('file', false)) {
            if ($file = filepath_decode($file, true)) {
                $sheet = $this->request->param('sheet/d', 0, 'abs');

                $cache_key = md5($file . $sheet);

                if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['xlsx', 'xls'])) {
                        $spreadsheet = IOFactory::load($file);
                        $result = $spreadsheet->getSheet($sheet)->toArray();
                        $this->cache->set($cache_key, $result);
                    }
                }

                return $result
                    ? $this->cache(true)->success('Excel read success', $result)
                    : $this->error('Excel read error');
            }
        }

        return miss(404, false);
    }

    /**
     * 导出Excel数据
     * @access public
     * @param
     * @return Response
     */
    public function writer()
    {
        if ($this->validate->referer() && $data = $this->request->param('data/a', false)) {
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();

            $sheet = $this->request->param('sheet/d', 0, 'abs');
            $worksheet->setTitle($sheet);

            foreach ($data as $line_no => $column) {
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
            $file = runtime_path('temp') . md5(date('YmdHis') . $this->request->server('HTTP_USER_AGENT')) . '.xlsx';
            $writer->save($file);
            unset($spreadsheet, $worksheet, $writer);

            return Response::create($file, 'file')
                ->name(pathinfo($file, PATHINFO_FILENAME))
                ->isContent(false)
                ->expire(28800);
        }

        return miss(404, false);
    }
}
