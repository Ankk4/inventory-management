<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Receipt History') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lines</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($receipts as $receipt)
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="text-indigo-600 hover:underline">
                                        {{ $receipt->store_name ?? 'Unknown store' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2">{{ $receipt->purchased_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    @if ($receipt->total)
                                        {{ number_format($receipt->total, 2) }} {{ $receipt->currency }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $receipt->lines_count }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $receipt->parser_source }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No receipts imported yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $receipts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
