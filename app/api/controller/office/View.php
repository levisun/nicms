<?php

/**
 *
 * API接口层
 * office 预览
 *
 * @package   NICMS
 * @category  app\api\controller\office
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\office;

use think\Response;
use app\common\controller\BaseApi;

class View extends BaseApi
{

    /**
     *
     * @access public
     * @param
     * @return Response
     */
    public function iframe()
    {
        if (!$this->ValidateReferer() || !$file = $this->request->param('file')) {
            return miss(404);
        }

        $html = '<html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>office 预览</title></head><body style="padding:0;margin:0"><iframe id="office" name="office" frameborder="0" scrolling="no" width="100%" height ="100%" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($file) . '"></iframe></body></html>';

        return Response::create($html)
            ->allowCache(true)
            ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + $this->apiExpire) . ' GMT');
    }
}
