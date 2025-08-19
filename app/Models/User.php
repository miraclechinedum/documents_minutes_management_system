<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'is_active',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'department_id', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdDocuments()
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function assignedDocuments()
    {
        return $this->hasMany(Document::class, 'assigned_to_user_id');
    }

    public function minutes()
    {
        return $this->hasMany(Minute::class, 'created_by');
    }

    public function canViewDocument(Document $document): bool
    {
        // Admin and auditor can view all documents
        if ($this->hasAnyRole(['admin', 'auditor'])) {
            return true;
        }

        // Document creator can always view
        if ($document->created_by === $this->id) {
            return true;
        }

        // Direct assignee can view
        if ($document->assigned_to_user_id === $this->id) {
            return true;
        }

        // Department assignee can view if user is in that department
        if ($document->assigned_to_department_id === $this->department_id) {
            return true;
        }

        return false;
    }
}