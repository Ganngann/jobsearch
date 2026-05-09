<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-black text-2xl text-slate-900 leading-tight uppercase tracking-tighter">
            Retours Utilisateurs
        </h2>
    </x-slot>

    <div class="space-y-8">
        <!-- Feedbacks Table -->
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter">Messages de Feedback</h2>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Avis, bugs et suggestions d'idées</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Date</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Utilisateur</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Type</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Message</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Page</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($feedbacks as $feedback)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-8 py-6">
                                    <p class="text-sm font-bold text-slate-900">{{ $feedback->created_at->format('d/m/Y') }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $feedback->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xs">
                                            {{ substr($feedback->user->name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">{{ $feedback->user->name ?? 'Utilisateur inconnu' }}</p>
                                            <p class="text-xs text-slate-500">{{ $feedback->user->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @php
                                        $typeClasses = [
                                            'feedback' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                            'bug' => 'bg-rose-50 text-rose-600 border-rose-100',
                                            'idea' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        ];
                                        $typeLabels = [
                                            'feedback' => 'Avis',
                                            'bug' => 'Bug',
                                            'idea' => 'Idée',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $typeClasses[$feedback->type] ?? 'bg-slate-50 text-slate-600' }}">
                                        {{ $typeLabels[$feedback->type] ?? $feedback->type }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-md">
                                        <p class="text-sm text-slate-600 leading-relaxed">{{ $feedback->message }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    @if($feedback->page_url)
                                        <a href="{{ $feedback->page_url }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 underline truncate block max-w-[200px]">
                                            {{ str_replace(url('/'), '', $feedback->page_url) }}
                                        </a>
                                    @else
                                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    </div>
                                    <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">Aucun retour utilisateur pour le moment</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($feedbacks->hasPages())
                <div class="p-8 border-t border-slate-50 bg-slate-50/30">
                    {{ $feedbacks->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
