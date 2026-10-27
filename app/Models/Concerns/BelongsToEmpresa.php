<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder) {
            $user = Auth::user();

            if ($user && ! $user->esSuperAdmin() && $user->empresa_id) {
                $builder->where($builder->getModel()->qualifyColumn('empresa_id'), $user->empresa_id);
            }
        });

        static::creating(function ($model) {
            $user = Auth::user();

            if (empty($model->empresa_id) && $user && ! $user->esSuperAdmin() && $user->empresa_id) {
                $model->empresa_id = $user->empresa_id;
            }
        });
    }
}
