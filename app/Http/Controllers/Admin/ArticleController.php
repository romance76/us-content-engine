<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Keyword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    private function validated(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:articles,slug,'.($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,in_review,published'],
            'keyword_id' => ['nullable', 'exists:keywords,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }
}
