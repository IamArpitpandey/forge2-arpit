<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketController extends Controller
{
    use AuthorizesRequests;

    /**
     * List tickets for the current tenant.
     * Staff (admin/agent) see all org tickets; customers see only their own.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::query()
            ->with(['requester:id,name,email', 'assignee:id,name,email', 'tags:id,name']);

        // Customers can only see their own tickets
        if ($request->user()->isCustomer()) {
            $query->where('requester_id', $request->user()->id);
        }

        // Optional filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        return response()->json(
            $query->latest()->paginate($request->input('per_page', 15))
        );
    }

    /**
     * Create a new ticket.
     * Customers are auto-assigned as requester; staff can set requester_id.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validate([
            'subject'      => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'priority'     => ['sometimes', 'in:low,medium,high,urgent'],
            'requester_id' => ['sometimes', 'exists:users,id'],
            'assignee_id'  => ['sometimes', 'nullable', 'exists:users,id'],
            'status'       => ['sometimes', 'in:open,pending,resolved,closed'],
        ]);

        // Customers always create tickets as themselves
        if ($request->user()->isCustomer()) {
            $validated['requester_id'] = $request->user()->id;
            unset($validated['assignee_id'], $validated['status']);
        }

        $ticket = Ticket::create($validated);

        return response()->json($ticket->fresh(), 201);
    }

    /**
     * Show a single ticket with replies and tags.
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester:id,name,email',
            'assignee:id,name,email',
            'tags:id,name',
            'replies' => fn ($q) => $q->with('author:id,name,email')->latest(),
        ]);

        return response()->json($ticket);
    }

    /**
     * Update a ticket.
     * Customers can only update subject/description of their own open tickets.
     * Staff can update everything.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $rules = [
            'subject'      => ['sometimes', 'string', 'max:255'],
            'description'  => ['sometimes', 'string'],
        ];

        // Staff-only fields
        if ($request->user()->isStaff()) {
            $rules['status']       = ['sometimes', 'in:open,pending,resolved,closed'];
            $rules['priority']     = ['sometimes', 'in:low,medium,high,urgent'];
            $rules['assignee_id']  = ['sometimes', 'nullable', 'exists:users,id'];
            $rules['requester_id'] = ['sometimes', 'exists:users,id'];
        }

        $validated = $request->validate($rules);
        $ticket->update($validated);

        return response()->json($ticket->fresh(['requester:id,name,email', 'assignee:id,name,email', 'tags:id,name']));
    }

    /**
     * Delete a ticket. Staff only — customers cannot delete.
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
