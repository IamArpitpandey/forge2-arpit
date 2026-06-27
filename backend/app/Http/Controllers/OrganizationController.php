<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * List users in the current tenant (for dropdowns).
     */
    public function users(Request $request)
    {
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
        $tags = Tag::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }
}
