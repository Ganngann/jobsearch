@foreach($jobOffers as $offer)
    @php
        $match = $offer->userMatch;
        $score = $match ? ($match->final_score ?? $match->pre_score) : null;
    @endphp
    @php
        $isOfferBlacklisted = $match?->is_blacklisted ?? false;
        // Optionnel: check réel si on veut être ultra-précis dans la liste aussi
    @endphp
    <div 
        @click="selectOffer('{{ $offer->forem_id }}')"
        data-offer-id="{{ $offer->forem_id }}"
        data-pre-score="{{ $match?->pre_score ?? 0 }}"
        data-ai-score="{{ $match?->final_score ?? '' }}"
        data-vector-score="{{ ($match?->vector_score !== null) ? round($match->vector_score) : '' }}"
        data-final-score="{{ $match?->final_score ?? 0 }}"
        :class="selectedId == '{{ $offer->forem_id }}' ? 'border-indigo-500 ring-2 ring-indigo-500/10 bg-white' : 'border-slate-100 hover:border-slate-300 bg-white'"
        class="p-5 rounded-2xl border cursor-pointer transition-all duration-300 shadow-sm hover:shadow-md group relative overflow-hidden {{ $isOfferBlacklisted ? 'opacity-50 grayscale-[0.5]' : '' }}"
    >
        <!-- Score Section -->
        <div class="absolute top-0 right-0 p-3">
            <div class="text-right">
                @php
                    $displayScore = $match?->final_score ?? 0;
                    $scoreColorClass = $displayScore >= 70 ? 'text-emerald-500' : ($displayScore >= 40 ? 'text-amber-500' : 'text-slate-400');
                @endphp
                <p class="text-xl font-black leading-none {{ $scoreColorClass }} score-confort">
                    <span x-text="scores['{{ $offer->forem_id }}']?.ia || scores['{{ $offer->forem_id }}']?.final || '{{ $displayScore }}'"></span><span class="text-[9px]">%</span>
                </p>
                <p class="text-[7px] font-black uppercase text-slate-400 tracking-tighter">{{ $match?->ai_status === 'completed' ? 'IA Match' : 'Score' }}</p>
            </div>
        </div>


        <div class="flex gap-4">
            <div class="shrink-0">
                @if($offer->employer->logo_base64)
                    <img src="{{ route('employers.logo', $offer->employer_id) }}" class="w-12 h-12 rounded-xl object-contain bg-slate-50 border border-slate-100 p-2" alt="Logo" loading="lazy">
                @else
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 font-black text-xs">
                        {{ substr($offer->employer->label, 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0 pr-24">
                <h4 class="text-sm font-black text-slate-900 truncate group-hover:text-indigo-600 transition-colors flex items-center gap-2">
                    {{ $offer->title }}
                    @if(isset($favoriteRomeCodes) && $offer->metier && in_array(substr($offer->metier->code, 0, 5), $favoriteRomeCodes))
                        <svg class="w-3.5 h-3.5 text-rose-500 fill-current shrink-0" viewBox="0 0 24 24" title="Famille favorie"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    @endif
                </h4>
                <p class="text-xs font-bold text-slate-500 mt-0.5 truncate">{{ $offer->employer->label }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                        {{ $offer->location }}
                    </span>
                    <span class="text-[9px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-md uppercase">
                        {{ $offer->contract_type }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endforeach
