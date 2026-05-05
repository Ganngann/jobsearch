<div x-show="$store.discovery.loading" {{ $attributes->merge(['class' => 'text-center mb-12 animate-pulse']) }}>
    <p class="text-indigo-600 font-medium text-lg" x-text="$store.discovery.loadingMessage"></p>
</div>
