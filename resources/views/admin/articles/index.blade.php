<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Articles</h2>
            <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">
                + New article
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        <div class="mb-4 flex gap-2 text-sm">
            @foreach (['all' => 'All', 'draft' => 'Draft', 'in_review' => 'In review', 'published' => 'Published'] as $key => $label)
                <a href="{{ $key === 'all' ? route('admin.articles.index') : route('admin.articles.index', ['status' => $key]) }}"
                   class="px-3 py-1 rounded-full border {{ ($status ?? 'all') === $key ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300' }}">
                    {{ $label }} ({{ $counts[$key] ?? 0 }})
                </a>
            @endforeach
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($articles as $article)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="hover:underline">{{ $article->title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'px-2 py-1 rounded text-xs',
                                    'bg-gray-200 text-gray-700' => $article->status === 'draft',
                                    'bg-yellow-100 text-yellow-800' => $article->status === 'in_review',
                                    'bg-green-100 text-green-800' => $article->status === 'published',
                                ])>{{ $article->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $article->category ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $article->generated_by }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $article->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="text-blue-600 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No articles yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $articles->links() }}</div>
    </div>
</x-app-layout>
