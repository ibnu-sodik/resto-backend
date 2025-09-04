<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tables extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'tables';
    protected $fillable = [
        'code',
        'capacity',
        'status',
        'reserved_by'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
