<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as ModelsPermission;

class Permission extends ModelsPermission
{
    use HasFactory;

    public function category_permission()
    {
        return $this->belongsTo(CategoryPermission::class, 'category_permission_id');
    }

    public function scopeFilter($query, $data)
    {
        return $query;
    }

    public function scopeIdInNotIn($query, $data)
    {
        if (isset($data['id_not_in'])) {
            $query->whereNotIn('id', $data['id_not_in']);
        }
        if (isset($data['id_in'])) {
            $query->whereIn('id', $data['id_in']);
        }
    }
}
