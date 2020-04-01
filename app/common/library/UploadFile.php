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
use think\facade\Request;
use think\facade\Filesystem;
use think\Validate;
use app\common\library\DataFilter;
use app\common\model\UploadFileLog as ModelUploadFileLog;

class UploadFile
{
    /**
     * 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
     * @var array
     */
    private $fileExtension = [
        'jpg', 'gif', 'png', 'webp',
        'mp3', 'mp4',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
        'zip'
    ];
    private $fileMime = [
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

    /**
     * 图片规定尺寸
     * @var array
     */
    private $thumbSize = [
        'width'  => 0,
        'height' => 0,
        'type'   => false,
    ];

    /**
     * 图片水印
     * @var bool
     */
    private $imgWater = true;

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
        @ini_set('max_execution_time', '600');
        @set_time_limit(600);

        $files = Request::file($_element);
        $this->thumbSize = [
            'width'  => !empty($_thumb['width']) ? $_thumb['width'] : 0,
            'height' => !empty($_thumb['height']) ? $_thumb['height'] : 0,
            // THUMB_SCALING:等比例缩放
            // THUMB_FIXED:固定尺寸缩放
            'type'   => !empty($_thumb['type']) ? Image::THUMB_SCALING : Image::THUMB_FIXED,
        ];
        $this->imgWater = $_water;

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
        $ext = Config::get('app.upload_type');
        // 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
        $ext = explode(',', $ext);
        foreach ($ext as $key => $value) {
            if (!in_array($value, $this->fileExtension)) {
                unset($ext[$key]);
            }
        }
        $ext = implode(',', $ext);

        $validate = new Validate;
        $error = $validate->rule([
            $_element => [
                'fileExt'  => $ext,
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

        // 用户目录[删除用户时可删除目录]
        // 应用名第一个字符作为用户类型标记
        if ($_uid) {
            // 文件保存目录
            $_dir = 'uploads' . DIRECTORY_SEPARATOR .
                substr(app('http')->getName(), 0, 1) . Base64::dechex($_uid) .
                DIRECTORY_SEPARATOR . Base64::dechex((int) date('Y') + $_uid);
        } else {
            $_dir = 'guest';
        }

        // 子目录
        // $_dir .= DIRECTORY_SEPARATOR . Base64::dechex((int) date('Ym'));

        $save_path = Config::get('filesystem.disks.public.url') . '/';
        $save_file = $save_path . Filesystem::disk('public')->putFile($_dir, $_files, 'uniqid');
        $this->writeUploadLog($save_file);   // 记录上传文件日志

        if (false !== strpos($_files->getMime(), 'image/')) {
            $save_file = $this->thumbAndWater($_files, $save_file);
        }

        $save_file = str_replace(DIRECTORY_SEPARATOR, '/', $save_file);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return [
            'extension' => pathinfo($save_file, PATHINFO_EXTENSION),
            'name'      => pathinfo($save_file, PATHINFO_BASENAME),
            'save_path' => $save_file,
            'size'      => filesize(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file),
            'type'      => finfo_file($finfo, app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file),
            'url'       => Config::get('app.img_host') . $save_file,
        ];
    }

    /**
     * 图片缩略图和水印
     * @access private
     * @param  string $_file 文件
     * @param  string $_save_file 文件名
     * @return void
     */
    private function thumbAndWater(\think\File &$_files, string $_save_file): string
    {
        @ini_set('memory_limit', '128M');

        $image = Image::open(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_save_file);

        // 缩放图片到指定尺寸
        if ($this->thumbSize['width'] && $this->thumbSize['height']) {
            $image->thumb($this->thumbSize['width'], $this->thumbSize['height'], $this->thumbSize['type']);
        }
        // 规定图片最大尺寸
        elseif ($image->width() >= 800) {
            $image->thumb(800, 800, Image::THUMB_SCALING);
        }

        // 添加水印
        if (true === $this->imgWater) {
            $ttf = app()->getRootPath() . 'extend' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'simhei.ttf';
            $image->text(Request::rootDomain(), $ttf, 16, '#00000000', mt_rand(1, 9));
        }

        // 转换webp格式
        if (function_exists('imagewebp')) {
            $webp_file = str_replace('.' . $_files->extension(), '.webp', $_save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $webp_file, 'webp');
            $this->writeUploadLog($webp_file);   // 记录上传文件日志
            // 删除非webp格式图片
            if ('webp' !== $_files->extension()) {
                unlink(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_save_file);
            }
            $_save_file = $webp_file;
        }

        // 转换jpg格式
        elseif ('gif' !== $_files->extension()) {
            $jpg_file = str_replace('.' . $_files->extension(), '.jpg', $_save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $jpg_file, 'jpg');
            $this->writeUploadLog($jpg_file);   // 记录上传文件日志
            // 删除非jpg格式图片
            if ('jpg' !== $_files->extension()) {
                unlink(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_save_file);
            }
            $_save_file = $jpg_file;
        }

        unset($image);

        return $_save_file;
    }

    /**
     * 记录上传文件
     * @access public
     * @param  string $_file 文件
     * @param  int    $_type
     * @return void
     */
    public function writeUploadLog(string $_file, int $_type = 0): void
    {
        if (!$_file) {
            return;
        }

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

    /**
     * 删除上传垃圾文件
     * @access public
     * @return void
     */
    public function ReGarbage(): void
    {
        only_execute('remove_upload_garbage.lock', '-12 hour', function () {
            $sort_order = mt_rand(0, 1) ? 'upload_file_log.id DESC' : 'upload_file_log.id ASC';

            // 查询文件记录
            $result = ModelUploadFileLog::view('upload_file_log', ['id', 'file'])
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

            $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
            $id = [];
            foreach ($result as $file) {
                // 记录ID
                $id[] = (int) $file['id'];

                // 过滤非法字符
                $file['file'] = DataFilter::filter($file['file']);
                $file['file'] = $path . str_replace('/', DIRECTORY_SEPARATOR, $file['file']);

                if (is_file($file['file'])) {
                    // 删除系统生成的缩略图
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['file']);
                    if (false !== strpos($mime, 'image/')) {
                        $extension = '.' . pathinfo($file['file'], PATHINFO_EXTENSION);
                        for ($i = 1; $i <= 8; $i++) {
                            $size = $i * 100;
                            $thumb = str_replace($extension, '_' . $size . $extension, $file['file']);
                            if (is_file($thumb)) {
                                @unlink($thumb);
                            }
                        }
                    }

                    // 删除文件
                    @unlink($file['file']);
                }
            }

            if (!empty($id) && 0 < count($id)) {
                ModelUploadFileLog::where([
                    ['id', 'in', $id]
                ])->delete();
            }
        });
    }

    /**
     * 删除入库上传文件
     * @access public
     * @param  string $_file
     * @return void
     */
    public function remove(string $_file): void
    {
        $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        // 过滤非法字符
        $abs_file = DataFilter::filter($_file);
        $abs_file = $path . str_replace('/', DIRECTORY_SEPARATOR, $abs_file);

        if (is_file($abs_file)) {
            // 删除系统生成的缩略图
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $abs_file);
            if (false !== strpos($mime, 'image/')) {
                $extension = '.' . pathinfo($abs_file, PATHINFO_EXTENSION);
                for ($i = 1; $i <= 8; $i++) {
                    $size = $i * 100;
                    $thumb = str_replace($extension, '_' . $size . $extension, $abs_file);
                    if (is_file($thumb)) {
                        @unlink($thumb);
                    }
                }
            }

            // 删除文件
            @unlink($abs_file);
        }

        ModelUploadFileLog::where([
            ['file', '=', $_file]
        ])->delete();
    }
}
