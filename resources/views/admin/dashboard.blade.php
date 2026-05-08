<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Header Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Utilisateurs Totaux</p>
                    <h3 class="text-3xl font-black text-slate-900">{{ $stats['total_users'] }}</h3>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Appels IA (Total)</p>
                    <h3 class="text-3xl font-black text-indigo-600">{{ $stats['total_ai_calls'] }}</h3>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Actifs aujourd'hui</p>
                    <h3 class="text-3xl font-black text-emerald-500">{{ $stats['active_users_today'] }}</h3>
                </div>
            </div>

            <!-- AI Details Stats -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100">
                    <h2 class="text-xl font-black text-slate-900">Consommation IA par Modèle & Catégorie</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                    <div class="p-6">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Tokens Entrants</p>
                        <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_tokens_in']) }}</p>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Tokens Sortants</p>
                        <p class="text-2xl font-black text-slate-900">{{ number_format($stats['total_tokens_out']) }}</p>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Coût Estimé (Flash)</p>
                        <p class="text-2xl font-black text-emerald-600">~{{ number_format(($stats['total_tokens_in'] * 0.0001 / 1000) + ($stats['total_tokens_out'] * 0.0003 / 1000), 4) }} $</p>
                    </div>
                    <div class="p-6">
                        <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Appels Totaux</p>
                        <p class="text-2xl font-black text-indigo-600">{{ $stats['total_ai_calls'] }}</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Modèle</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Catégorie</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Appels</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Tokens In</th>
                                <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Tokens Out</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs">
                            @foreach($stats['ai_details'] as $detail)
                            <tr>
                                <td class="px-6 py-3 font-bold text-slate-700">{{ $detail->model }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold uppercase text-[9px]">{{ $detail->category }}</span>
                                </td>
                                <td class="px-6 py-3 text-center font-bold">{{ $detail->count }}</td>
                                <td class="px-6 py-3 text-right text-slate-500">{{ number_format($detail->total_in) }}</td>
                                <td class="px-6 py-3 text-right text-slate-500">{{ number_format($detail->total_out) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-xl font-black text-slate-900">Gestion des Utilisateurs & Quotas IA</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Appels IA</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Usage Jour</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Limite Quotidienne</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Dernière activité</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($users as $user)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400 overflow-hidden">
                                            @if($user->avatar)
                                                <img src="{{ $user->avatar }}" alt="">
                                            @else
                                                {{ substr($user->name, 0, 1) }}
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 flex items-center gap-2">
                                                {{ $user->name }}
                                                @if($user->is_admin)
                                                    <span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded uppercase font-black">Admin</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-700">
                                    {{ $user->ai_calls_count }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black {{ $user->daily_ai_usage >= $user->daily_ai_limit ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }}">
                                        {{ $user->daily_ai_usage }} / {{ $user->daily_ai_limit }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.users.update-limit', $user) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        <input type="number" name="limit" value="{{ $user->daily_ai_limit }}" class="w-20 px-3 py-1.5 bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500/20">
                                        <button type="submit" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-medium">
                                    {{ $user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'Jamais' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs font-black uppercase tracking-tighter {{ $user->is_admin ? 'text-rose-500 hover:text-rose-700' : 'text-indigo-600 hover:text-indigo-800' }}">
                                            {{ $user->is_admin ? 'Retirer Admin' : 'Nommer Admin' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-6 bg-slate-50 border-t border-slate-100">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
