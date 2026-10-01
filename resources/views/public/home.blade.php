@extends('public.layout')

@php
    $isEn = app()->getLocale() === 'en';
    $activeCategoryLabel = $activeCategory ? (\App\Models\Article::CATEGORY_LABELS_EN[$activeCategory] ?? $activeCategory) : null;
@endphp

@section('title', $activeCategory
    ? ($isEn ? $activeCategoryLabel : $activeCategory).' — '.config('app.name')
    : config('app.name').' — '.config('app.tagline', 'Latest articles'))
@section('meta_description', config('app.tagline', ''))

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-10">
        <aside class="space-y-8">
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">{{ __('site.categories') }}</h2>
                <ul class="space-y-1 text-sm">
                    <li>
                        <a href="{{ route('home') }}"
                           class="block px-2 py-1.5 rounded {{ ! $activeCategory ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                            {{ __('site.all') }}
                        </a>
                    </li>
                    @foreach ($categories as $name => $count)
                        <li>
                            <a href="{{ route('category.show', $name) }}"
                               class="flex items-center justify-between px-2 py-1.5 rounded {{ $activeCategory === $name ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                <span>{{ $isEn ? (\App\Models\Article::CATEGORY_LABELS_EN[$name] ?? $name) : $name }}</span>
                                <span class="text-xs {{ $activeCategory === $name ? 'text-gray-300' : 'text-gray-400' }}">{{ $count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($recent->isNotEmpty())
                <div>
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">{{ __('site.recent_posts') }}</h2>
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($recent as $item)
                            <li>
                                <a href="{{ route('articles.show', $item) }}" class="text-gray-600 hover:text-gray-900 hover:underline leading-snug block">
                                    {{ $isEn ? $item->translatedTitle() : $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>

        <div>
            @if ($activeCategory)
                <div class="flex items-center gap-2 mb-6">
                    <h1 class="text-xl font-bold">{{ $isEn ? $activeCategoryLabel : $activeCategory }}</h1>
                    <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('site.view_all') }}</a>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-6">
                @forelse ($articles as $article)
                    <article class="group">
                        <a href="{{ route('articles.show', $article) }}" class="block">
                            <div class="aspect-[16/10] rounded-lg overflow-hidden bg-gray-100">
                                @if ($article->cover_image_url)
                                    <img src="{{ $article->cover_image_url }}" alt="{{ $article->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                                @endif
                            </div>
                            <div class="mt-3">
                                @if ($article->category)
                                    <span class="text-xs font-medium text-blue-700">{{ $isEn ? $article->translatedCategory() : $article->category }}</span>
                                @endif
                                <h2 class="mt-1 text-lg font-semibold leading-snug group-hover:underline">{{ $isEn ? $article->translatedTitle() : $article->title }}</h2>
                                @if ($article->excerpt)
                                    <p class="mt-1.5 text-sm text-gray-500 line-clamp-2">{{ $isEn ? $article->translatedExcerpt() : $article->excerpt }}</p>
                                @endif
                                <time class="mt-2 block text-xs text-gray-400" datetime="{{ $article->published_at?->toIso8601String() }}">
                                    {{ $article->published_at?->format('M j, Y') }}
                                </time>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="text-gray-400 col-span-2">{{ __('site.no_articles') }}</p>
                @endforelse
            </div>

            <div class="mt-10">{{ $articles->links() }}</div>
        </div>
    </div>
@endsection
