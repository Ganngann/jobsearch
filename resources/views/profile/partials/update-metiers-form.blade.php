<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            {{ __('Métiers Préférés (ROME)') }}
        </h2>
        <p class="mt-1 text-sm text-slate-600 font-medium">
            {{ __('Sélectionnez les métiers pour lesquels vous souhaitez recevoir des offres.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.metiers.update') }}" class="mt-8 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($allMetiers as $metier)
                <label class="relative flex items-start p-4 cursor-pointer rounded-2xl border-2 transition-all {{ $user->preferredMetiers->contains($metier->id) ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200 bg-white' }}">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="metiers[]" value="{{ $metier->id }}" 
                               {{ $user->preferredMetiers->contains($metier->id) ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 border-slate-300 rounded-lg focus:ring-indigo-600 focus:ring-offset-0 transition-all">
                    </div>
                    <div class="ml-4 text-sm">
                        <span class="block font-black text-slate-900 leading-tight">{{ $metier->label }}</span>
                        <span class="block mt-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Code ROME : {{ $metier->code }}</span>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="flex items-center gap-4 pt-4">
            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 rounded-2xl px-10 py-4 shadow-lg shadow-indigo-100">
                {{ __('Enregistrer les Métiers') }}
            </x-primary-button>

            @if (session('status') === 'metiers-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-bold text-emerald-600 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    {{ __('Enregistré.') }}
                </p>
            @endif
        </div>
    </form>

    @if($user->blacklistedMetiers->count() > 0)
        <div class="mt-12 pt-10 border-t border-slate-100">
            <h3 class="text-md font-black text-slate-900 uppercase tracking-widest mb-6">Métiers en liste noire</h3>
            <div class="flex flex-wrap gap-3">
                @foreach($user->blacklistedMetiers as $metier)
                    <div class="group flex items-center gap-3 pl-4 pr-2 py-2 bg-rose-50 border border-rose-100 rounded-xl">
                        <span class="text-xs font-bold text-rose-700">{{ $metier->label }}</span>
                        <button 
                            onclick="unblacklistMetier({{ $metier->id }}, '{{ addslashes($metier->label) }}')"
                            class="p-1 text-rose-300 hover:text-rose-600 hover:bg-rose-100 rounded-lg transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <script>
        function unblacklistMetier(id, label) {
            fetch(`/profile/metiers/${id}/blacklist`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => window.location.reload());
        }
    </script>
</section>
