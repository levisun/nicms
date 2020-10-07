<?php

declare(strict_types=1);

namespace addon\gotop;

class Index
{

    public function run()
    {
        return '<style>
        #addon-elevator {
            display: none;
            position: fixed;
            right: 20px;
            bottom: 40px;
            color: #96a0a8;
            border: solid 1px #e0e0e0;
            background-color: #fff;
            z-index: 1000;
            border-radius: 4px;
            padding: 0;
        }
        #addon-elevator>li {
            display: block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-bottom: solid 1px #ededed;
            position: relative;
        }
        #addon-elevator>li .container {
            width: 0;
            height: 0;
            border: 10px solid;
            border-color: transparent transparent red transparent;
            position: relative;
            top: -15px;
        }
        #addon-elevator>li .container::after {
            content: "";
            position: absolute;
            top: -25px;
            left: -50px;
            border: 50px solid;
            border-color: transparent transparent white transparent;
        }
        #addon-gotop {
            position:fixed;
            bottom:80px;
            right:80px;
            display:none;
            width:64px;
            height:64px;
        }
        </style>

        <ul id="addon-elevator" style="display: block;">
            <li><span id="elevator_gotop" title="↑ 返回顶部"><i class="container"></i></span></li>
            <li><span id="elevator_gotop" title="微信公众号"></span></li>
            <li><span id="elevator_gotop" title="手机版"></span></li>
        </ul>

        <div id="addon-gotop">回到顶端</div>
        <script type="text/javascript">
        jQuery(window).scroll(function () {
            if (jQuery(window).scrollTop() >= 100) {
                jQuery("#addon-gotop").fadeIn(1500);
            } else {
                jQuery("#addon-gotop").fadeOut(1500);
            }
        });
        jQuery("#addon-gotop").click(function (){
            jQuery("html,body").animate({ scrollTop: 0 }, 500);
        });
        </script>';
    }
}
