<?php

/**
 *
 * 模板驱动
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

use think\Image;
use think\facade\Config;
use think\facade\Filesystem;
use think\facade\Log;
use think\Validate;
use app\common\model\UploadFileLog as ModelUploadFileLog;

class UploadFile
{
    /**
     * 用户ID
     * @var int
     */
    private $uid = 0;

    /**
     * 表单name
     * @var string
     */
    private $element = '';

    /**
     * 上传文件实例
     * @var object
     */
    private $files = null;

    /**
     * 图片规定尺寸
     * @var array
     */
    private $thumbSize = [
        'width' => 0,
        'height' => 0,
        'type' => false,
    ];

    /**
     * 图片规定尺寸
     * @var bool
     */
    private $thumbWater = true;

    /**
     * 上传文件日志
     * @var string
     */
    private $uploadLogFile = '';

    /**
     * 获得上传文件信息
     * @access public
     * @param  int    $_uid 用户ID
     * @param  string $_element 表单名
     * @param  array  $_thumb 缩略图宽高,缩放比例
     * @param  bool   $_water 添加水印
     * @retuen array
     */
    public function getFileInfo(int $_uid, string $_element, array $_thumb, bool $_water): array
    {
        $files = app('request')->file($_element);
        $this->thumbSize = [
            'width'  => $_thumb['width'],
            'height' => $_thumb['height'],
            'type'   => $_thumb['type'] ? Image::THUMB_SCALING : Image::THUMB_FIXED,
        ];
        $this->thumbWater = $_water;

        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
            'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $this->uploadLogFile = $path . md5('upload_file_log' . date('Ymd') . $_uid) . '.php';

        // 校验上传文件
        if (!$result = $this->validate($_element, $files)) {
            // 单文件
            if (is_string($_FILES[$_element]['name'])) {
                $result = $this->save($_uid, $files);
            }

            // 多文件
            elseif (is_array($_FILES[$_element]['name'])) {
                $result = [];
                foreach ($files as $file) {
                    $result[] = $this->save($_uid, $file);
                }
            }
        }

        return $result;
    }

    /**
     * 校验上传文件
     * @access private
     * @return bool|string
     */
    private function validate(string &$_element, \think\File &$_files)
    {
        $size = (int) Config::get('app.upload_size', 1) * 1048576;
        $ext = Config::get('app.upload_type', 'doc,docx,gif,gz,jpeg,mp3,mp4,pdf,png,ppt,pptx,rar,xls,xlsx,zip');
        $mime = [
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'gif'  => 'image/gif',
            'gz'   => 'application/x-gzip',
            'jpeg' => 'image/jpeg',
            'mp3'  => 'audio/mpeg',
            'mp4'  => 'video/mp4',
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar'  => 'application/octet-stream',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip',
            '7z'   => 'application/x-7z-compressed'
        ];

        $validate = new Validate;
        $error = $validate->rule([
            $_element => [
                'fileExt'  => $ext,
                // 'fileMime' => $mime,
                'fileSize' => $size
            ]
        ])->batch(false)->failException(false)->check([$_element => $_files]);

        return $error ? false : $validate->getError();
    }

    /**
     * 保存上传文件
     * @access private
     * @param  \think\File $_files 文件
     * @return array
     */
    private function save(int &$_uid, \think\File &$_files): array
    {
        $_dir = $_uid
            ? '/u' . dechex(date('ym')) . dechex($_uid)
            : '/t' . dechex(date('ym'));

        $save_path = Config::get('filesystem.disks.public.url') . '/';
        $save_file = $save_path . Filesystem::disk('public')->putFile('uploads' . $_dir, $_files, 'uniqid');
        $this->writeUploadLog($save_file);   // 记录上传文件日志

        if (false !== strpos($_files->getMime(), 'image/')) {
            $image = Image::open(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);

            // 缩放图片到指定尺寸
            if ($this->thumbSize['width'] && $this->thumbSize['height']) {
                $image->thumb($this->thumbSize['width'], $this->thumbSize['height'], $this->thumbSize['type']);
            }
            // 规定图片最大尺寸
            elseif ($image->width() > 800) {
                $image->thumb(800, 800, Image::THUMB_SCALING);
            }

            // 添加水印
            if (true === $this->thumbWater) {
                $image->text(
                    app('request')->rootDomain(),
                    app()->getRootPath() . 'extend' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'simhei.ttf',
                    16,
                    '#00000000',
                    mt_rand(1, 9)
                );
            }

            // 转换webp格式
            $webp_file = str_replace('.' . $_files->extension(), '.webp', $save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $webp_file, 'webp');
            $this->writeUploadLog($webp_file);   // 记录上传文件日志
            // 删除非webp格式图片
            if ('webp' !== $_files->extension()) {
                unlink(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);
            }
            $save_file = $webp_file;

            unset($image);
        }

        return [
            // 'extension'    => $_files->extension(),
            'extension'    => pathinfo($save_file, PATHINFO_EXTENSION),
            'name'         => pathinfo($save_file, PATHINFO_BASENAME),
            // 'old_name'     => $_files->getOriginalName(),
            'original_url' => $save_file,
            // 'size'         => $_files->getSize(),
            // 'type'         => $_files->getMime(),
            'url'          => Config::get('app.cdn_host') . $save_file,
        ];
    }

    /**
     * 记录上传文件
     * @access public
     * @param  string $_file 文件
     * @param  int    $_module_id   模块ID
     * @param  int    $_module_type 模块类型 1用户头像 2栏目图标 3文章缩略图 4文章内容插图 5...
     * @return void
     */
    public function writeUploadLog(string $_file, int $_module_id = 0, int $_module_type = 0): void
    {
        (new ModelUploadFileLog)->save([
            'file'        => $_file,
            'module_id'   => $_module_id,
            'module_type' => $_module_type,
        ]);
    }

    /**
     * 删除入库上传文件
     * @access public
     * @param  int    $_module_id   模块ID
     * @param  int    $_module_type 模块类型 1用户头像 2栏目图标 3文章缩略图 4文章内容插图 5...
     * @return void
     */
    public function remove(int $_module_id, int $_module_type): void
    {
        $map = [
            ['type', '=', '1'],
            ['module_id', '=', '$_module_id'],
            ['module_type', '=', '$_module_type'],
        ];
        $result = (new ModelUploadFileLog)
            ->where($map)
            ->select();
        $result = $result ? $result->toArray() : [];

        $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        foreach ($result as $file) {
            $file['file'] = trim($file['file'], " \/,._-\t\n\r\0\x0B");
            $file['file'] = str_replace('/', DIRECTORY_SEPARATOR, $file['file']);
            if (is_file($path . $file['file'])) {
                @unlink($path . $file['file']);
            }
        }

        (new ModelUploadFileLog)
            ->where($map)
            ->delete();
    }

    /**
     * 删除上传垃圾文件
     * @access public
     * @return void
     */
    public function ReGarbage(): void
    {
        only_execute('remove_upload_garbage.lock', '-10 minute', function () {
            $sort_order = mt_rand(0, 1) ? 'upload_file_log.id DESC' : 'upload_file_log.id ASC';
            $result = (new ModelUploadFileLog)
                ->view('upload_file_log', ['id', 'file'])
                ->view('upload_file_log log', ['id' => 'log_id'], 'log.type=1 and log.file=upload_file_log.file', 'LEFT')
                ->where([
                    ['upload_file_log.type', '=', '0'],
                    ['log.id', '=', null],
                    ['upload_file_log.create_time', '<=', strtotime('-1 days')]
                ])
                ->order($sort_order)
                ->limit(10)
                ->select();
            $result = $result ? $result->toArray() : [];
            $id = [];

            $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
            foreach ($result as $file) {
                $id[] = (int) $file['id'];
                $file['file'] = trim($file['file'], " \/,._-\t\n\r\0\x0B");
                $file['file'] = str_replace('/', DIRECTORY_SEPARATOR, $file['file']);
                if (is_file($path . $file['file'])) {
                    @unlink($path . $file['file']);
                }
            }

            if (!empty($id) && 0 < count($id)) {
                (new ModelUploadFileLog)
                    ->where([
                        ['id', 'in', $id]
                    ])
                    ->delete();
            }
        });
    }
}
