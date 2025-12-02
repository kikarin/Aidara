<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BlameableObserver
{
    public function creating(Model $model)
    {
        if (@Auth::user()->id) {
            $model->created_by = Auth::user()->id;
            $model->updated_by = Auth::user()->id;
        }
    }

    public function updating(Model $model)
    {
        if (@Auth::user()->id) {
            $model->updated_by = Auth::user()->id;
        }
    }

    public function deleting(Model $model)
    {
        if (@Auth::user()->id) {
            $model->deleted_by = Auth::user()->id;
        }
    }
}
