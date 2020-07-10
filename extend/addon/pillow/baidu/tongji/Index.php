<?php

declare(strict_types=1);

namespace addon\pillow\baidu\tongji;

use \addon\Base;

class Index extends Base
{

    public function run()
    {
        return '<script type="text/javascript">var _hmt = _hmt || [];(function() {var hm = document.createElement("script");hm.src = "https://hm.baidu.com/hm.js?' . $this->settings['token'] . '";var s = document.getElementsByTagName("script")[0];s.parentNode.insertBefore(hm, s);})();(function(){var bp = document.createElement("script");var curProtocol = window.location.protocol.split(":")[0];if (curProtocol === "https") {bp.src = "https://zz.bdstatic.com/linksubmit/push.js";}else {bp.src = "http://push.zhanzhang.baidu.com/push.js";}var s = document.getElementsByTagName("script")[0];s.parentNode.insertBefore(bp, s);})();</script>';
    }
}
