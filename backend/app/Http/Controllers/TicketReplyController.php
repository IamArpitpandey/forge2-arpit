<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketReplyController extends Controller
{
    use AuthorizesRequests;

    /**
     * List replies for a ticket.
     * Staff see all replies; customers only see public replies.
     */
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $query = $ticket->replies()
            ->with('author:id,name,email');

        // Customers never see internal notes
        if ($request->user()->isCustomer()) {
            $query->where('type', 'public');
        }

        return response()->json(
            $query->latest()->paginate($request->input('per_page', 20))
        );
    }

    /**
     * Create a reply on a ticket.
     * Customers can only post public replies; staff can post public or internal.
     */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string'],
            'type' => ['sometimes', 'in:public,internal'],
        ]);

        // Force public for customers
        if ($request->user()->isCustomer()) {
            $validated['type'] = 'public';
        }

        $validated['author_id'] = $request->user()->id;

        $reply = $ticket->replies()->create($validated);

        return response()->json(
            $reply->load('author:id,name,email'),
            201
        );
    }
}
