<?php

return [
    'think\middleware\CheckRequestCache',
    'think\middleware\LoadLangPack',
    'think\middleware\SessionInit',
    'think\middleware\TraceDebug',

    'app\middleware\AllowCrossDomain',
    'app\middleware\Monitor',
];
