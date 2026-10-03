<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return $this->render();
    }

    public function category(string $category): View
    {
        return $this->render($category);
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        return $this->render(null, $query);
    }

    private function render(?string $category = null, ?string $searchQuery = null): View
    {
        $articles = Article::published()
            ->when($category, fn ($q) => $q->category($category))
            ->when($searchQuery, fn ($q) => $q->where(function ($q) use ($searchQuery) {
                $q->where('title', 'like', "%{$searchQuery}%")
                    ->orWhere('excerpt', 'like', "%{$searchQuery}%")
                    ->orWhere('body', 'like', "%{$searchQuery}%");
            }))
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        $categories = Article::published()
            ->selectRaw('category, count(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('count')
            ->pluck('count', 'category');

        $recent = Article::published()
            ->latest('published_at')
            ->take(5)
            ->get(['title', 'slug', 'published_at']);

        return view('public.home', [
            'articles' => $articles,
            'categories' => $categories,
            'recent' => $recent,
            'activeCategory' => $category,
            'searchQuery' => $searchQuery,
        ]);
    }
}
