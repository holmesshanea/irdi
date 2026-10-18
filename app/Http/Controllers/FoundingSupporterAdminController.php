<?php

namespace App\Http\Controllers;

use App\Models\FoundingSupporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoundingSupporterAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('pages.admin.founding-supporters', [
            'supporters' => FoundingSupporter::query()->orderByRaw("CASE tier WHEN 'founder' THEN 1 WHEN 'charter' THEN 2 ELSE 3 END")
                ->orderBy('display_name')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $this->validated($request);
        FoundingSupporter::create($data);

        return redirect()->route('admin.founding-supporters.index')->with('status', 'Supporter added.');
    }

    public function update(Request $request, FoundingSupporter $supporter): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $supporter->update($this->validated($request, $supporter));

        return redirect()->route('admin.founding-supporters.index')->with('status', 'Supporter updated.');
    }

    public function destroy(Request $request, FoundingSupporter $supporter): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $supporter->delete();

        return redirect()->route('admin.founding-supporters.index')->with('status', 'Supporter removed.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdministrator(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?FoundingSupporter $supporter = null): array
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'tier' => ['required', Rule::in(['founder', 'charter', 'launch'])],
            'kickstarter_backer_id' => ['nullable', 'string', 'max:255', Rule::unique('founding_supporters', 'kickstarter_backer_id')->ignore($supporter?->id)],
            'is_public' => ['required', 'boolean'],
        ]);

        // Explicit consent must be recorded before public recognition is enabled.
        return $data;
    }
}
