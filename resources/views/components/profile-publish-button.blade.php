@props(['label' => 'Enregistrer le profil', 'size' => 'md'])

@php
    $sizeClasses = [
        'sm' => 'px-3 py-1 text-[9px] rounded-lg gap-2',
        'md' => 'px-4 py-2 text-[10px] rounded-xl gap-2',
        'lg' => 'px-6 py-4 text-xs rounded-2xl gap-3',
    ][$size] ?? 'px-4 py-2 text-[10px] rounded-xl gap-2';

    $iconSize = [
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
    ][$size] ?? 'w-4 h-4';
@endphp

@if(Auth::user()->isProfileDirty())
    <form action="{{ route('profile.publish') }}" method="POST" {{ $attributes->merge(['class' => 'inline-block']) }}>
        @csrf
        <button 
            type="submit"
            class="flex items-center {{ $sizeClasses }} bg-amber-500 hover:bg-amber-600 text-white font-black uppercase tracking-widest shadow-xl shadow-amber-500/20 transition-all transform hover:scale-105 animate-pulse"
            title="Stabiliser le profil et mettre à jour le matching"
        >
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            {{ $label }}
        </button>
    </form>
@endif
