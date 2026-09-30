<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends Controller
{
    public function show(Article $article): View
    {
        if ($article->status !== Article::STATUS_PUBLISHED && ! auth()->check()) {
            throw new NotFoundHttpException;
        }

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->when($article->keyword_id, fn ($q) => $q->where('keyword_id', $article->keyword_id))
            ->latest('published_at')
            ->take(4)
            ->get();

        $older = Article::published()
            ->where('published_at', '<', $article->published_at)
            ->latest('published_at')
            ->first(['title', 'slug']);

        $newer = Article::published()
            ->where('published_at', '>', $article->published_at)
            ->oldest('published_at')
            ->first(['title', 'slug']);

        return view('public.article', compact('article', 'related', 'older', 'newer'));
    }
}
