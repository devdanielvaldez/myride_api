<?php

namespace Modules\VehicleManagement\Interfaces;

use App\Repositories\Interfaces\BaseRepositoryInterface;

interface VehicleInterface extends BaseRepositoryInterface
{
    public function trashed(array $attributes);

    public function restore(string $id);
    public function permanentDelete(string $id);
}
