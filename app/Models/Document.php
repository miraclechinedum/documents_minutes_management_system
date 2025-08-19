<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Document extends Model
{
    use HasFactory, Searchable, LogsActivity;

    const STATUS_QUARANTINED = 'quarantined';
    const STATUS_SCANNING = 'scanning';
    const STATUS_INFECTED = 'infected';
    const STATUS_RECEIVED = 'received';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'title',
        'reference_number',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'checksum',
        'pages',
        'thumbnail_path',
        'status',
        'ocr_text',
        'created_by',
        'assigned_to_user_id',
        'assigned_to_department_id',
        'due_date',
        'priority',
        'description',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'pages' => 'integer',
        'file_size' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'assigned_to_user_id', 'assigned_to_department_id', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reference_number' => $this->reference_number,
            'file_name' => $this->file_name,
            'ocr_text' => $this->ocr_text,
            'description' => $this->description,
            'created_by_name' => $this->creator?->name,
            'department_name' => $this->assignedToDepartment?->name,
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedToDepartment()
    {
        return $this->belongsTo(Department::class, 'assigned_to_department_id');
    }

    public function minutes()
    {
        return $this->hasMany(Minute::class)->orderBy('created_at', 'desc');
    }

    public function routes()
    {
        return $this->hasMany(DocumentRoute::class)->orderBy('created_at', 'desc');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_QUARANTINED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_SCANNING => 'bg-blue-100 text-blue-800',
            self::STATUS_INFECTED => 'bg-red-100 text-red-800',
            self::STATUS_RECEIVED => 'bg-green-100 text-green-800',
            self::STATUS_IN_PROGRESS => 'bg-orange-100 text-orange-800',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'bg-red-100 text-red-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getCurrentAssignee(): ?string
    {
        if ($this->assigned_to_user_id) {
            return $this->assignedToUser?->name;
        }
        
        if ($this->assigned_to_department_id) {
            return $this->assignedToDepartment?->name . ' (Department)';
        }

        return null;
    }
}