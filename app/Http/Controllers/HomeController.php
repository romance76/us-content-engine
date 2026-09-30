<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $articles = Article::published()
            ->latest('published_at')
            ->paginate(12);

        return view('public.home', compact('articles'));
    }
}
