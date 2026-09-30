<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Keywords</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.keywords.store') }}" class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="term" value="Keyword / question" />
                <x-text-input id="term" name="term" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="source" value="Source" />
                <x-text-input id="source" name="source" class="mt-1 block w-full" placeholder="manual, reddit, gsc..." />
            </div>
            <div>
                <x-input-label for="search_volume" value="Volume" />
                <x-text-input id="search_volume" name="search_volume" type="number" class="mt-1 block w-24" />
            </div>
            <x-primary-button>Add</x-primary-button>
            <x-input-error :messages="$errors->get('term')" class="mt-1 basis-full" />
        </form>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Term</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Volume</th>
                        <th class="px-4 py-3">Articles</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($keywords as $keyword)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $keyword->term }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $keyword->source }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $keyword->search_volume }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $keyword->articles_count }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.keywords.update', $keyword) }}">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="text-xs border-gray-300 rounded">
                                        @foreach (['new', 'queued', 'used', 'rejected'] as $s)
                                            <option value="{{ $s }}" @selected($keyword->status === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.keywords.destroy', $keyword) }}" onsubmit="return confirm('Delete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No keywords yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $keywords->links() }}</div>
    </div>
</x-app-layout>
