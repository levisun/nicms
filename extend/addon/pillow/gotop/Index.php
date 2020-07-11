<?php

declare(strict_types=1);

namespace addon\pillow\gotop;

use \addon\Base;

class Index extends Base
{

    public function run()
    {
        return '<style>
        #addon-gotop {
            position:fixed;
            bottom:80px;
            right:80px;
            display:none;
            width:64px;
            height:64px;
        }
        </style>
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
