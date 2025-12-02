<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    // Todo: Relation
    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    // Todo: End Relation

    // Todo: Attribute

    public function getIsActiveBadgeAttribute()
    {
        return convertTrueFalse($this->is_active, ['Nonaktif', 'Aktif']);
    }

    public function getCreatedAtDiffAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getCheckFileAttribute()
    {
        $media = $this->getFirstMedia('images');

        return $media
            ? ($media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : $media->getUrl())
            : null;
    }

    // Todo: End Attribute

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
