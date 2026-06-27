<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use AuthorizesRequests;

    /**
     * List users in the current tenant (for dropdowns).
     */
    public function users(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->select('id', 'name', 'email', 'role')
            ->get();

        return response()->json($users);
    }

    /**
     * List tags for the current tenant (for dropdowns).
     */
    public function tags(Request $request)
    {
        $this->authorize('viewAny', Tag::class);

        $tags = Tag::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }
}
