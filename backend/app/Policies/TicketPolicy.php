<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * ViewAny: any authenticated user within a tenant can list tickets
     * (the controller further scopes customers to their own).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a specific ticket.
     * Staff see all org tickets; customers see only their own.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return $user->organization_id === $ticket->organization_id;
        }

        return $user->id === $ticket->requester_id;
    }

    /**
     * Create tickets.
     * All authenticated users can create tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Update tickets.
     * Staff can update any org ticket; customers can only update their own
     * and only if the ticket is still open.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return $user->organization_id === $ticket->organization_id;
        }

        return $user->id === $ticket->requester_id && $ticket->status === 'open';
    }

    /**
     * Delete tickets.
     * Admins and agents only. Customers can never delete.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $user->organization_id === $ticket->organization_id;
    }
}
