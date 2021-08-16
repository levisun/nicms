<?php

/**
 *
 * Office Excel表格
 *
 * @package   NICMS
 * @category  app\common\library\office
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library\office;

use app\common\library\File;
use app\common\library\Filter;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class OfficeWord
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

        $ext = strtolower(pathinfo($_file, PATHINFO_EXTENSION));
        if (in_array($ext, ['docx', 'doc'])) {
            $php_word = IOFactory::load($_file);
            $result = $php_word->getSection(1)->toArray();
        }

        return $result ?: null;
    }

    /**
     * 生成Excel
     * @access public
     * @param
     * @return mixed
     */
    public function write(string $_data)
    {
        if (!$_data) {
            return false;
        }

        $_data = nl2br($_data);
        $_data = explode('<br />', $_data);
        $_data = array_map(function ($value) {
            $value = Filter::htmlEncode($value);
            return Filter::htmlDecode($value);
        }, $_data);

        $php_word = new PhpWord;
        $section = $php_word->addSection();

        foreach ($_data as $key => $value) {
            $ext = pathinfo($value, PATHINFO_EXTENSION);
            if (0 === $key) {
                $section->addText($value, ['size' => 14, 'bold' => true, 'alignment' => 'center']);
            } elseif ($ext && in_array($ext, ['gif', 'png', 'jpg', 'jpeg', 'webp'])) {
                $section->addImage($value, ['width' => 150, 'height' => 150]);
            } else {
                $section->addText($value, ['size' => 12]);
            }
        }

        $writer = IOFactory::createWriter($php_word, 'Word2007');

        $path =runtime_path('temp');
        if (!is_dir($path)) mkdir($path, 0755, true);
        $file = uniqid() . '.doc';
        $writer->save($path . $file);

        return $path . $file;
    }
}
