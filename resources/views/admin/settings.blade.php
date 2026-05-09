<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-black text-2xl text-slate-900 leading-tight uppercase tracking-tighter">
            Paramètres du Système
        </h2>
    </x-slot>

    <div class="max-w-4xl">
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
            @csrf

            @php $groupedSettings = $settings->groupBy('group'); @endphp

            @foreach($groupedSettings as $group => $items)
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/30">
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tighter">
                        {{ $group === 'ai_pricing' ? "Tarification de l'IA" : "Quotas par Modèle (Par Utilisateur)" }}
                    </h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">
                        {{ $group === 'ai_pricing' ? "Ajustez les prix pour les calculs de coûts (en dollars par million de tokens)" : "Définissez les limites par défaut quotidiennes individuelles par utilisateur pour chaque modèle d'IA" }}
                    </p>
                </div>

                <div class="p-8 space-y-6">
                    @foreach($items as $setting)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-sm font-black text-slate-700 uppercase tracking-tight">
                                    {{ $setting->description }}
                                </label>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">{{ $setting->key }}</p>
                            </div>
                            <div class="relative">
                                @if($group === 'ai_pricing')
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                                @endif
                                <input type="number" 
                                       step="{{ $group === 'ai_pricing' ? '0.001' : '1' }}" 
                                       name="settings[{{ $setting->key }}]" 
                                       value="{{ $setting->value }}" 
                                       class="w-full {{ $group === 'ai_pricing' ? 'pl-8' : 'px-4' }} pr-4 py-3 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
