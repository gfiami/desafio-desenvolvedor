<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Helpers\DateHelper;

class FileUpload extends Model
{
    protected $table = 'file_uploads';

    protected $fillable = [
        'file_name',
        'file_hash',
        'path',
    ];

    protected $appends = ['created_at_formatted', 'download_link'];

    public function getCreatedAtFormattedAttribute(): string
    {
        return DateHelper::formatToBrazilianDate($this->created_at);
    }

    public function getDownloadLinkAttribute(): string
    {
        $filePath = 'uploads/' . $this->file_hash . '.' . pathinfo($this->file_name, PATHINFO_EXTENSION);

        $fileUrl = '';
        if (Storage::disk('public')->exists($filePath)) {
            $fileUrl = asset('storage/' . $filePath);
        }

        return $fileUrl;
    }

    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $order = $request->input('order');
        $name = $request->input('name');
        $date = $request->input('date');

        $orderOptions = [
            'created_asc' => ['column' => 'created_at', 'direction' => 'asc'],
            'created_desc' => ['column' => 'created_at', 'direction' => 'desc'],
        ];

        $orderData = $orderOptions[$order] ?? null;

        return $query
            ->when($name, function ($query) use ($name) {
                return $query->where('file_name', 'LIKE', "%$name%");
            })
            ->when($date, function ($query) use ($date) {
                return $query->whereDate('created_at', $date);
            })
            ->when($orderData, function ($query) use ($orderData) {
                return $query->orderBy($orderData['column'], $orderData['direction']);
            });
    }
}
