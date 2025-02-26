<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FileUpload extends Model
{
    protected $table = 'file_uploads';

    protected $fillable = [
        'file_name',
        'file_hash',
        'path',
    ];


    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $order = $request->input('order');
        $name = $request->input('name');

        $orderOptions = [
            'created_asc' => ['column' => 'created_at', 'direction' => 'asc'],
            'created_desc' => ['column' => 'created_at', 'direction' => 'desc'],
        ];

        $orderData = $orderOptions[$order] ?? null;

        return $query
            ->when($name, function ($query) use ($name) {
                return $query->where('file_name', 'LIKE', "%$name%");
            })
            ->when($orderData, function ($query) use ($orderData) {
                return $query->orderBy($orderData['column'], $orderData['direction']);
            });
    }
}
