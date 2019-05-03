<?php

return [
    'app\middleware\Monitor',
    'app\middleware\AllowCrossDomain',

    'think\middleware\CheckRequestCache',
    'think\middleware\LoadLangPack',
    'think\middleware\SessionInit',
    'think\middleware\TraceDebug',
];
