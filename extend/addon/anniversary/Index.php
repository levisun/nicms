<?php

declare(strict_types=1);

namespace addon\gotop;

class Index
{

    public function run()
    {
        return '<style>
        body {
            -webkit-filter:grayscale(100%);
        }
        </style>';
    }
}
