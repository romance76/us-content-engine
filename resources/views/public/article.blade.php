@extends('public.layout')

@php
    $isEn = app()->getLocale() === 'en';
    $bodyHasImage = str_contains($article->body, '<img');
    $displayTitle = $isEn ? $article->translatedTitle() : $article->title;
    $displayExcerpt = $isEn ? $article->translatedExcerpt() : $article->excerpt;
    $displayBody = $isEn ? $article->translatedBody() : $article->body;
    $displayCategory = $isEn ? $article->translatedCategory() : $article->category;
    $displayMetaTitle = $isEn ? $article->translatedMetaTitle() : ($article->meta_title ?: $article->title);
    $displayMetaDescription = $isEn ? $article->translatedMetaDescription() : ($article->meta_description ?: $article->excerpt);
@endphp

@section('title', $displayMetaTitle)
@section('meta_description', $displayMetaDescription)
@section('og_type', 'article')
@if ($article->cover_image_url)
    @section('og_image', $article->cover_image_url)
@endif

@push('head')
    <meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
@endpush

@section('content')
    <article>
        <a href="{{ $article->category ? route('category.show', $article->category) : route('home') }}"
           class="text-sm text-gray-400 hover:text-gray-600">{{ __('site.back_to_list') }}</a>

        @if ($article->status !== 'published')
            <div class="mt-4 mb-2 px-3 py-2 bg-yellow-50 text-yellow-800 text-sm rounded">
                {{ __('site.preview_only', ['status' => $article->status]) }}
            </div>
        @endif

        @if ($displayCategory)
            <a href="{{ route('category.show', $article->category) }}" class="mt-4 inline-block text-xs font-medium text-blue-700">{{ $displayCategory }}</a>
        @endif
        <h1 class="mt-1 text-3xl font-bold leading-tight">{{ $displayTitle }}</h1>
        <time class="mt-2 block text-sm text-gray-400" datetime="{{ $article->published_at?->toIso8601String() }}">
            {{ $article->published_at?->format('M j, Y') }}
        </time>

        @if ($article->cover_image_url && ! $bodyHasImage)
            <img src="{{ $article->cover_image_url }}" alt="{{ $displayTitle }}" class="mt-6 rounded-lg w-full">
        @endif

        <div class="prose prose-neutral max-w-none mt-8">
            {!! $displayBody !!}
        </div>
    </article>

    @if ($older || $newer)
        <div class="mt-12 pt-6 border-t border-gray-100 grid grid-cols-2 gap-4">
            <div>
                @if ($older)
                    <a href="{{ route('articles.show', $older) }}" class="block">
                        <span class="text-xs text-gray-400">{{ __('site.previous_post') }}</span>
                        <span class="mt-1 block text-sm font-medium text-gray-700 hover:underline line-clamp-2">{{ $isEn ? $older->translatedTitle() : $older->title }}</span>
                    </a>
                @endif
            </div>
            <div class="text-right">
                @if ($newer)
                    <a href="{{ route('articles.show', $newer) }}" class="block">
                        <span class="text-xs text-gray-400">{{ __('site.next_post') }}</span>
                        <span class="mt-1 block text-sm font-medium text-gray-700 hover:underline line-clamp-2">{{ $isEn ? $newer->translatedTitle() : $newer->title }}</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16 pt-8 border-t border-gray-100">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">{{ __('site.related') }}</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($related as $item)
                    <li>
                        <a href="{{ route('articles.show', $item) }}" class="text-blue-700 hover:underline">{{ $isEn ? $item->translatedTitle() : $item->title }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $displayTitle,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at->toIso8601String(),
        'description' => $displayMetaDescription,
    ]) !!}
    </script>
@endsection
