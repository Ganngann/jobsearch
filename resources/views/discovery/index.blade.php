<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen" 
         x-data="{}" 
         x-init="$store.discovery.setData({ 
            suggestions: {{ Js::from($initialSuggestions) }}, 
            savedMetiers: {{ Js::from($savedMetiers) }},
            config: {
                suggestRoute: {{ Js::from(route('discovery.suggest')) }},
                csrfToken: {{ Js::from(csrf_token()) }}
            }
         })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <x-discovery.header>
                Laissez l'IA explorer le marché de l'emploi pour dénicher les métiers qui résonnent avec votre personnalité profonde.
            </x-discovery.header>

            <x-discovery.error-message />

            <x-discovery.magic-button />

            <x-discovery.loading-state />

            <!-- Suggestions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8" x-show="$store.discovery.suggestions.length > 0">
                <template x-for="s in $store.discovery.suggestions" :key="s.code">
                    <x-discovery.suggestion-card />
                </template>
            </div>

            <x-discovery.empty-state>
                <p>Cliquez sur le bouton ci-dessus pour lancer votre exploration.</p>
            </x-discovery.empty-state>

            <!-- Mes Métiers Cibles & Recherche -->
            <div class="mt-16 mb-12 grid grid-cols-1 lg:grid-cols-3 gap-8 border-t border-slate-200 pt-16">
                <x-discovery.search-manual />
                <x-discovery.saved-list />
            </div>

        </div>
    </div>

</x-app-layout>
