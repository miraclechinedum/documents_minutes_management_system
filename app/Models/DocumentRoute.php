<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DocumentRoute extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'document_id',
        'from_user_id',
        'to_type',
        'to_id',
        'minute_id',
        'notes',
        'routed_at',
    ];

    protected $casts = [
        'routed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_id', 'from_user_id', 'to_type', 'to_id', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_id');
    }

    public function minute()
    {
        return $this->belongsTo(Minute::class);
    }

    public function getToName(): string
    {
        if ($this->to_type === 'user' && $this->toUser) {
            return $this->toUser->name;
        }
        
        if ($this->to_type === 'department' && $this->toDepartment) {
            return $this->toDepartment->name . ' (Department)';
        }

        return 'Unknown';
    }
}