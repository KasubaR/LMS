<div class="grid gap-4 mb-4" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    @foreach ($stats as $stat)
        <div class="card elev-sm p-4 gap-2">
            <div class="text-xs text-muted-500">{{ $stat['label'] }}</div>
            <div class="font-mono text-xl tabular-nums">{{ $stat['value'] }}</div>
        </div>
    @endforeach
</div>
