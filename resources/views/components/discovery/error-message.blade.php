<div x-show="$store.discovery.errorMessage" {{ $attributes->merge(['class' => 'max-w-2xl mx-auto mb-8 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl text-center font-medium']) }}>
    <p x-text="$store.discovery.errorMessage"></p>
</div>
