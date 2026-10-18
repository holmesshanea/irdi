<?php

namespace App\Http\Controllers;

use App\Models\LandownerPermission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLandownerPermissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdministrator(), 403);

        $status = $request->query('status', 'all');
        abort_unless(is_string($status) && in_array($status, ['all', 'pending', 'approved', 'declined', 'withdrawn'], true), 404);

        $permissions = LandownerPermission::query()
            ->with('memberProfile.user')
            ->when($status === 'withdrawn', fn ($query) => $query->whereNotNull('withdrawn_at'))
            ->when($status !== 'all' && $status !== 'withdrawn', fn ($query) => $query->where('status', $status)->whereNull('withdrawn_at'))
            ->latest()->paginate(20)->withQueryString();

        return view('pages.permissions.admin', compact('permissions', 'status'));
    }
}
