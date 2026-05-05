<div class="flex items-center justify-between gap-3 p-2 hover:bg-white rounded-xl transition-colors group/item">
    <span class="text-xs font-medium text-slate-600 line-clamp-2" x-text="v.label"></span>
    <div class="flex items-center gap-1 shrink-0">
        <!-- Favorite (+20) -->
        <button 
            @click="setMetierStatus(v, 'favorite')"
            class="p-1 rounded-md transition-all"
            :class="v.status === 'favorite' ? 'bg-red-50 text-red-500' : 'text-slate-300 hover:text-red-400'"
            title="Coup de cœur (+20 pts)"
        >
            <svg class="w-4 h-4" :fill="v.status === 'favorite' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </button>

        <!-- Neutral (0) -->
        <button 
            @click="setMetierStatus(v, 'neutral')"
            class="p-1 rounded-md transition-all"
            :class="v.status === 'neutral' ? 'bg-slate-100 text-slate-500' : 'text-slate-300 hover:text-slate-500'"
            title="Neutre (0 pt)"
        >
            <svg class="w-4 h-4" :fill="v.status === 'neutral' ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </button>
        
        <!-- Refused (-20) -->
        <button 
            @click="setMetierStatus(v, 'refused')"
            class="p-1 rounded-md transition-all"
            :class="v.status === 'refused' ? 'bg-black text-white' : 'text-slate-200 hover:text-slate-800'"
            title="Pas pour moi (-20 pts)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </button>
    </div>
</div>
