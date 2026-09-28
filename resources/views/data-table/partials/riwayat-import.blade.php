@foreach ($batches as $batch)
    <tr class="border-t border-gray-100 align-top">
        <td class="px-4 py-2">
            {{ $batch->file_name }}
            @if ($batch->error)
                <div class="mt-1 text-xs text-red-600">{{ $batch->error }}</div>
            @endif
        </td>
        <td class="px-4 py-2">
            <span @class([
                'whitespace-nowrap rounded px-2 py-0.5 text-xs',
                'bg-gray-100 text-gray-700' => $batch->status === \App\Models\ImportBatch::STATUS_QUEUED,
                'bg-amber-100 text-amber-800' => in_array($batch->status, [\App\Models\ImportBatch::STATUS_PREPARING, \App\Models\ImportBatch::STATUS_PROCESSING], true),
                'bg-green-100 text-green-800' => $batch->status === \App\Models\ImportBatch::STATUS_COMPLETED,
                'bg-red-100 text-red-800' => $batch->status === \App\Models\ImportBatch::STATUS_FAILED,
            ])>{{ $batch->status }}</span>
        </td>
        <td class="w-full px-4 py-2 md:w-64">
            <div class="mb-1 text-xs text-gray-600">
                {{ number_format($batch->processed_rows, 0, ',', '.') }}
                / {{ number_format($batch->total_rows, 0, ',', '.') }}
                baris ({{ $batch->progressPercent() }}%)
            </div>
            <div class="h-2 w-full rounded bg-gray-100">
                <div class="h-2 rounded bg-blue-600" style="width: {{ min(100, $batch->progressPercent()) }}%"></div>
            </div>
            <div class="mt-1 text-[11px] text-gray-400">
                @if ($batch->status === \App\Models\ImportBatch::STATUS_PREPARING)
                    {{ $batch->chunks_total > 0 ? $batch->chunks_total.' chunk disiapkan' : 'menyiapkan chunk' }} &middot; file sedang dibaca & dipecah&hellip;
                @elseif ($batch->status === \App\Models\ImportBatch::STATUS_QUEUED)
                    menunggu worker mengambil job&hellip;
                @elseif ($batch->chunks_total > 0)
                    chunk {{ $batch->chunksProcessed() }}/{{ $batch->chunks_total }}
                    &middot; {{ number_format($batch->chunk_size, 0, ',', '.') }} baris per job
                @endif
            </div>
        </td>
        <td class="hidden px-4 py-2 text-xs text-gray-600 md:table-cell">{{ $batch->chunks_total }} chunk</td>
        <td class="hidden px-4 py-2 text-xs text-gray-600 md:table-cell">{{ $batch->started_at?->format('H:i:s') ?? '-' }}</td>
        <td class="hidden px-4 py-2 text-xs text-gray-600 md:table-cell">{{ $batch->finished_at?->format('H:i:s') ?? '-' }}</td>
    </tr>
@endforeach
