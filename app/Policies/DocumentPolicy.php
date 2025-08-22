<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('documents.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if (!$user->hasPermissionTo('documents.view')) {
            return false;
        }

        return $user->canViewDocument($document);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('documents.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    // public function update(User $user, Document $document): bool
    // {
    //     if (!$user->hasPermissionTo('documents.edit')) {
    //         return false;
    //     }

    //     // Only creator or admin can edit
    //     return $document->created_by === $user->id || $user->hasRole('admin');
    // }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        if (!$user->hasPermissionTo('documents.delete')) {
            return false;
        }

        // Only creator or admin can delete
        return $document->created_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can forward the document.
     */
    public function forward(User $user, Document $document): bool
    {
        if (!$user->hasPermissionTo('documents.forward')) {
            return false;
        }

        return $user->canViewDocument($document);
    }

    // In app/Policies/DocumentPolicy.php

    public function update(User $user, Document $document)
    {
        // Users can edit documents they created or if they're admins
        return $user->id === $document->created_by || $user->hasRole('admin');
    }

    public function export(User $user, Document $document)
    {
        // Users can export documents they have access to
        return $user->can('view', $document);
    }

    /**
     * Determine whether the user can export the document.
     */
    // public function export(User $user, Document $document): bool
    // {
    //     if (!$user->hasPermissionTo('print.export')) {
    //         return false;
    //     }

    //     return $user->canViewDocument($document);
    // }
}
