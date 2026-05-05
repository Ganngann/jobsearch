<div x-show="!loading && suggestions.length === 0" {{ $attributes->merge(['class' => 'text-center text-slate-400 py-20']) }}>
    {{ $slot }}
</div>
