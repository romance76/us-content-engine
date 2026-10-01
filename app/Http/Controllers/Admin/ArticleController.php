<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Keyword;
use App\Support\GenerationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $articles = Article::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Article::count(),
            Article::STATUS_DRAFT => Article::where('status', Article::STATUS_DRAFT)->count(),
            Article::STATUS_IN_REVIEW => Article::where('status', Article::STATUS_IN_REVIEW)->count(),
            Article::STATUS_PUBLISHED => Article::where('status', Article::STATUS_PUBLISHED)->count(),
        ];

        return view('admin.articles.index', compact('articles', 'status', 'counts'));
    }

    public function create(): View
    {
        $article = new Article(['status' => Article::STATUS_DRAFT]);
        $keywords = Keyword::orderBy('term')->get();

        return view('admin.articles.edit', compact('article', 'keywords'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Article::makeUniqueSlug($data['title']);

        $article = Article::create($data);

        return redirect()->route('admin.articles.edit', $article)
            ->with('status', '글이 생성되었습니다.');
    }

    public function edit(Article $article): View
    {
        $keywords = Keyword::orderBy('term')->get();

        return view('admin.articles.edit', compact('article', 'keywords'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validated($request, $article);
        $article->update($data);

        return redirect()->route('admin.articles.edit', $article)
            ->with('status', '저장되었습니다.');
    }

    public function publish(Article $article): RedirectResponse
    {
        $article->update([
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => $article->published_at ?? now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('status', '발행되었습니다.');
    }

    public function unpublish(Article $article): RedirectResponse
    {
        $article->update(['status' => Article::STATUS_IN_REVIEW]);

        return back()->with('status', '발행이 취소되었습니다 (검수중으로 전환).');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', '삭제되었습니다.');
    }

    /**
     * Marks an on-demand batch as requested. There's no on-server LLM, so
     * nothing generates text right here — a scheduled routine polls
     * GenerationStatus hourly (the platform's minimum scheduling interval)
     * and does the actual writing/publishing when it sees a pending
     * request. A short cooldown stops accidental double-clicks from
     * queuing two overlapping batches.
     */
    public function generate(): RedirectResponse
    {
        $cooldownKey = 'admin.generate.cooldown';

        if (Cache::has($cooldownKey)) {
            return back()->with('status', '방금 요청을 보냈습니다 — 몇 분 정도 더 기다려주세요.');
        }

        Cache::put($cooldownKey, true, now()->addMinutes(10));
        GenerationStatus::start(10, auth()->user()->email);

        return back()->with('status', '요청을 접수했습니다. 시간당 자동 처리 루틴이 확인해서 최대 1시간 이내에 새 글이 올라올 거예요.');
    }

    public function generationStatus(): JsonResponse
    {
        return response()->json(GenerationStatus::current() ?? ['status' => 'idle']);
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:articles,slug,'.($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,in_review,published'],
            'keyword_id' => ['nullable', 'exists:keywords,id'],
            'category' => ['nullable', 'string', 'in:'.implode(',', Article::CATEGORIES)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }
}
