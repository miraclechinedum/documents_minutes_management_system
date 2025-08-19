<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Minute extends Model
{
    use HasFactory, Searchable, LogsActivity;

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_DEPARTMENT = 'department';
    const VISIBILITY_INTERNAL = 'internal';

    protected $fillable = [
        'document_id',
        'body',
        'visibility',
        'page_number',
        'pos_x',
        'pos_y',
        'box_style',
        'created_by',
        'forwarded_to_type',
        'forwarded_to_id',
        'attachment_path',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'pos_x' => 'float',
        'pos_y' => 'float',
        'box_style' => 'json',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_id', 'body', 'visibility', 'forwarded_to_type', 'forwarded_to_id'])
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
            'body' => $this->body,
            'created_by_name' => $this->creator?->name,
            'document_title' => $this->document?->title,
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function forwardedToUser()
    {
        return $this->belongsTo(User::class, 'forwarded_to_id');
    }

    public function forwardedToDepartment()
    {
        return $this->belongsTo(Department::class, 'forwarded_to_id');
    }

    public function getForwardedToName(): ?string
    {
        if ($this->forwarded_to_type === 'user' && $this->forwardedToUser) {
            return $this->forwardedToUser->name;
        }
        
        if ($this->forwarded_to_type === 'department' && $this->forwardedToDepartment) {
            return $this->forwardedToDepartment->name . ' (Department)';
        }

        return null;
    }

    public function hasOverlay(): bool
    {
        return $this->page_number !== null && $this->pos_x !== null && $this->pos_y !== null;
    }

    public function canViewBy(User $user): bool
    {
        // Creator can always view
        if ($this->created_by === $user->id) {
            return true;
        }

        // Admin and auditor can view all
        if ($user->hasAnyRole(['admin', 'auditor'])) {
            return true;
        }

        // Check visibility rules
        switch ($this->visibility) {
            case self::VISIBILITY_PUBLIC:
                // Public minutes can be viewed by anyone who can view the document
                return $user->canViewDocument($this->document);
            
            case self::VISIBILITY_DEPARTMENT:
                // Department minutes can be viewed by department members
                return $user->department_id === $this->creator->department_id;
            
            case self::VISIBILITY_INTERNAL:
                // Internal minutes can only be viewed by creator and admins
                return false;
            
            default:
                return false;
        }
    }
}