<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Keyword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * Pings the AI pipeline's webhook so it picks up the request and writes
     * a fresh batch of articles — there's no on-server LLM to generate text
     * directly, so this just relays the request to wherever the pipeline is
     * listening. A short cooldown stops accidental double-clicks from firing
     * two overlapping generation runs.
     */
    public function generate(): RedirectResponse
    {
        $cooldownKey = 'admin.generate.cooldown';

        if (Cache::has($cooldownKey)) {
            return back()->with('status', '방금 요청을 보냈습니다 — 몇 분 정도 더 기다려주세요.');
        }

        $webhookUrl = config('services.generation.webhook_url');

        if (! $webhookUrl) {
            return back()->with('status', '자동 생성 webhook이 설정되지 않았습니다.');
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'action' => 'generate_articles',
                'count' => 10,
                'requested_by' => auth()->user()->email,
                'requested_at' => now()->toIso8601String(),
            ]);
            Cache::put($cooldownKey, true, now()->addMinutes(10));

            return back()->with('status', '요청을 보냈습니다. 몇 분 안에 새 글이 올라올 거예요.');
        } catch (\Throwable $e) {
            Log::warning('Generation webhook call failed', ['error' => $e->getMessage()]);

            return back()->with('status', '요청 전송에 실패했습니다. 잠시 후 다시 시도해주세요.');
        }
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
