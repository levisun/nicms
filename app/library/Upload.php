<?php
/**
 *
 * 上传类
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

use think\Image;
use think\facade\Config;
use think\facade\Request;

class Upload
{
    protected $savePath = '';
    protected $rule = [];
    protected $water = [];

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    {
        $this->savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

        $size = (int)Config::get('app.upload_size', '1');
        $ext = Config::get('app.upload_type', 'gif,jpg,png,zip,rar');

        set_time_limit(30);
        ini_set('memory_limit', '32M');

        $this->rule = [
            'size' => $size * 1048576,
            'ext' => explode(',', $ext)
        ];

        $this->water = [
            'type' => 'text',
            'text' => Request::rootDomain(),
            'font' => app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'simhei.ttf',
            'size' => 14,
        ];
    }

    /**
     * 保存文件
     * @access public
     * @param  string $_input_name 表单名
     * @return array  文件信息
     */
    public function save(string $_input_name = 'upload', string $_dir = ''): array
    {
        $file = Request::file($_input_name);
        $this->savePath .= $_dir ? $_dir . DIRECTORY_SEPARATOR : '';
        $this->savePath .= date('Ym') . DIRECTORY_SEPARATOR;

        $result = [];

        // 多文件上传
        if (is_array($file)) {
            foreach ($file as $key => $object) {
                $result[] = $this->saveFile($object);
            }
        }

        // 单文件上传
        elseif (is_object($file)) {
            $result = $this->saveFile($file);
        }

        return $result;
    }

    /**
     * 保存文件
     * @access private
     * @param  object $_object
     * @return array
     */
    private function saveFile(object $_object): array
    {
        if ($result = $_object->validate($this->rule)->rule('uniqid')->move($this->savePath)) {
            $extension = strtolower(pathinfo($result->getSaveName(), PATHINFO_EXTENSION));

            // 图片文件 压缩图片
            if (in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png'])) {
                $save_name = $result->getSaveName();
                $image = Image::open($this->savePath . $save_name);

                // 按指定图片大小缩放图片
                // 如果没有指定大小,图片大于800像素 统一缩放到800像素
                $width = (int)Request::param('width/f', 800);
                $width = $width > 800 ? 800 : $width;
                $height = (int)Request::param('height/f', 800);
                $height = $height > 800 ? 800 : $height;

                if ($image->width() > $width || $image->height() > $height) {
                    $image->thumb($width, $height, Image::THUMB_SCALING);
                }
                if ($water = Request::param('water/f', 0)) {
                    $image->text($this->water['text'], $this->water['font'], $this->water['size'], '#00000000', 1);
                    $image->text($this->water['text'], $this->water['font'], $this->water['size'], '#00000000', 5);
                    $image->text($this->water['text'], $this->water['font'], $this->water['size'], '#00000000', 9);
                }

                $image->save($this->savePath . $save_name, null, 60);
            }

            $url = str_replace(DIRECTORY_SEPARATOR, '/', $this->savePath);
            $url = explode('/uploads', $url);
            $url = '/uploads' . $url[1];

            return [
                'extension'    => $extension,
                'name'         => $result->getSaveName(),
                'old_name'     => $result->getName(),
                'original_url' => $url .  $result->getSaveName(),
                'size'         => filesize($this->savePath . $result->getSaveName()),
                'type'         => $result->getMime(),
                'url'          => Config::get('app.cdn_host') . $url . $result->getSaveName(),
            ];
        } else {
            return [
                'error' => $result->getError(),
            ];
        }
    }
}
