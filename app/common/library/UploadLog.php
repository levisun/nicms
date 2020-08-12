<?php

/**
 *
 * 上传文件
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

use app\common\library\Filter;
use app\common\model\UploadFileLog as ModelUploadFileLog;

class UploadLog
{

    /**
     * 记录上传文件
     * @access public
     * @static
     * @param  string $_file 文件
     * @param  int    $_type 0临时 1正式
     * @return void
     */
    public static function write(string $_file, int $_type = 0): void
    {
        if ($_file) {
            $_file = str_replace(DIRECTORY_SEPARATOR, '/', $_file);

            $has = ModelUploadFileLog::where([
                ['file', '=', $_file]
            ])->value('file');

            if ($has) {
                ModelUploadFileLog::update([
                    'type' => $_type
                ], ['file' => $_file]);
            } else {
                ModelUploadFileLog::create([
                    'file' => $_file,
                    'type' => $_type
                ]);
            }
        }
    }

    /**
     * 修改记录
     * @access public
     * @static
     * @param  string $_file 文件
     * @param  int    $_type 0临时 1正式
     * @return void
     */
    public static function update(string $_file, int $_type = 0)
    {
        if ($_file) {
            $_file = str_replace(DIRECTORY_SEPARATOR, '/', $_file);

            ModelUploadFileLog::update([
                'type' => $_type
            ], ['file' => $_file]);
        }
    }

    /**
     * 删除入库上传文件
     * @access public
     * @static
     * @param  string $_file
     * @return void
     */
    public static function remove(string $_file): void
    {
        $path = public_path();
        // 过滤非法字符
        $abs_file = Filter::safe($_file);
        $abs_file = $path . str_replace('/', DIRECTORY_SEPARATOR, $abs_file);

        // 删除文件
        if (is_file($abs_file)) {
            @unlink($abs_file);
        }

        ModelUploadFileLog::where([
            ['file', '=', $_file]
        ])->delete();
    }

    /**
     * 清除上传临时文件
     * @access public
     * @static
     * @return void
     */
    public static function clearGarbage(): void
    {
        $result = ModelUploadFileLog::field(['id', 'file'])
            ->where([
                ['type', '=', 0],
                ['create_time', '<=', strtotime('-1 days')]
            ])
            ->limit(10)
            ->select();

        $result = $result ? $result->toArray() : [];

        $path = public_path();
        foreach ($result as $file) {
            // 记录ID
            $id[] = (int) $file['id'];

            // 过滤非法字符
            $file['file'] = Filter::safe($file['file']);
            $file['file'] = $path . str_replace('/', DIRECTORY_SEPARATOR, $file['file']);

            if (is_file($file['file'])) {
                @unlink($file['file']);
            }
        }

        if (!empty($id) && 0 < count($id)) {
            ModelUploadFileLog::where([
                ['id', 'in', $id]
            ])->delete();
        }
    }
}