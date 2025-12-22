@props([
    'status' => [],
])

<span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border-2 {{ $status['badge'] }}">
    <i class="fas {{ $status['icon'] }} mr-2"></i>
    {{ $status['name'] }}
</span>

