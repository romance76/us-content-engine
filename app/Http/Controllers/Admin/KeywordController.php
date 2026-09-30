<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeywordController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $keywords = Keyword::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->withCount('articles')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.keywords.index', compact('keywords', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'term' => ['required', 'string', 'max:255', 'unique:keywords,term'],
            'source' => ['nullable', 'string', 'max:100'],
            'search_volume' => ['nullable', 'integer', 'min:0'],
            'competition' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Keyword::create($data);

        return back()->with('status', '키워드가 추가되었습니다.');
    }

    public function update(Request $request, Keyword $keyword): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,queued,used,rejected'],
        ]);

        $keyword->update($data);

        return back()->with('status', '상태가 변경되었습니다.');
    }

    public function destroy(Keyword $keyword): RedirectResponse
    {
        $keyword->delete();

        return back()->with('status', '삭제되었습니다.');
    }
}
