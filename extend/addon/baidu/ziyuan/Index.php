<?php

declare(strict_types=1);

namespace addon\baidu\ziyuan;

use \addon\Base;

class Index extends Base
{

    public function run()
    {
        $script  = $this->api();
        $script .= $this->script();
        return $script;
    }

    private function api(): string
    {
        $api = 'http://data.zz.baidu.com/urls?site=' .
            $this->config['site'] . '&token=' .
            $this->config['token'];

        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $this->request->url(true) . "\n",
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return '<script type="text/javascript">console.log(' . $result . ');</script>';
        // $result = json_decode($result, true);
        // halt($result);
    }

    private function script(): string
    {
        return '<script type="text/javascript">(function(){var bp=document.createElement("script");var curProtocol=window.location.protocol.split(":")[0];if(curProtocol==="https"){bp.src="https://zz.bdstatic.com/linksubmit/push.js"}else{bp.src="http://push.zhanzhang.baidu.com/push.js"}var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(bp,s)})();</script>';
    }
}
