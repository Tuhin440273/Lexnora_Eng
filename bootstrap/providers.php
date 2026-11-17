<?php

use App\Modules\Learning\LearningServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    LearningServiceProvider::class,
];
