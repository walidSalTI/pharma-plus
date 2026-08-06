<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    PermissionServiceProvider::class,
];
