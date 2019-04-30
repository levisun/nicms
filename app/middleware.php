<?php

return [
    'app\middleware\AllowCrossDomain',
    'app\middleware\Monitor',

    'think\middleware\CheckRequestCache',
    'think\middleware\LoadLangPack',
    'think\middleware\SessionInit',
    'think\middleware\TraceDebug',
];
