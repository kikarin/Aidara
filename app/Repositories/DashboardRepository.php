<?php

namespace App\Repositories;

use App\Traits\RepositoryTrait;

class DashboardRepository
{
    use RepositoryTrait;

    public function __construct(
    ) {
    }

    public function customIndex($data)
    {
        return $data;
    }
}
