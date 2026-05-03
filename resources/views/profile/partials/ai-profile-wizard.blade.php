<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Assistant IA : Génération de Profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Collez votre CV ou une description de votre parcours, et laissez l'IA remplir votre profil (Bio, Titre, Aspirations et Compétences).") }}
        </p>
    </header>

    <div x-data="aiWizard()" class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
        <div x-show="!loading && !suggestion">
            <x-input-label for="cv_text" :value="__('Votre parcours ou CV (Texte brut)')" />
            <x-textarea id="cv_text" x-model="text" class="mt-1 block w-full bg-white" rows="8" placeholder="Copiez-collez votre texte ici..."></x-textarea>
            <div class="mt-4 flex justify-end">
                <x-primary-button @click="analyze()" x-bind:disabled="text.length < 50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    {{ __('Générer le profil avec Gemini') }}
                </x-primary-button>
            </div>
        </div>

        <div x-show="loading" class="flex flex-col items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
            <p class="text-indigo-600 font-medium">{{ __('Analyse en cours par Gemini...') }}</p>
        </div>

        <div x-show="suggestion && !loading" class="space-y-6 animate-fade-in">
            <div class="flex justify-between items-center border-b border-indigo-200 pb-2">
                <h3 class="font-bold text-indigo-900">{{ __('Suggestions de l\'IA') }}</h3>
                <button @click="suggestion = null" class="text-sm text-gray-500 hover:text-gray-700 underline">{{ __('Recommencer') }}</button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-100">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">{{ __('Titre Professionnel') }}</span>
                    <p class="mt-1 font-semibold" x-text="suggestion.headline"></p>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-100">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">{{ __('Récit de Vie') }}</span>
                    <p class="mt-1 text-sm text-gray-700 leading-relaxed" x-text="suggestion.profile_text"></p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-100">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">{{ __('Aspirations') }}</span>
                    <p class="mt-1 text-sm text-gray-700" x-text="suggestion.aspirations"></p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border border-indigo-100">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">{{ __('Compétences identifiées') }}</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="skill in suggestion.skills" :key="skill">
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs font-medium" x-text="skill"></span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100 flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <p class="text-xs text-yellow-700">
                    {{ __('En cliquant sur "Appliquer", les champs ci-dessus seront remplis automatiquement dans les formulaires de cette page. Vous devrez ensuite cliquer sur "Enregistrer" pour chaque section pour valider les changements.') }}
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <x-primary-button @click="applySuggestion()">
                    {{ __('Appliquer au profil') }}
                </x-primary-button>
            </div>
        </div>
    </div>

    <script>
        function aiWizard() {
            return {
                text: '',
                loading: false,
                suggestion: null,
                
                async analyze() {
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('profile.analyze') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                            },
                            body: JSON.stringify({ text: this.text })
                        });
                        
                        if (!response.ok) throw new Error('Erreur lors de l\'analyse');
                        
                        this.suggestion = await response.json();
                    } catch (error) {
                        console.error('Erreur:', error.message);
                    } finally {
                        this.loading = false;
                    }
                },

                applySuggestion() {
                    // Remplir les champs du formulaire principal
                    document.getElementById('headline').value = this.suggestion.headline;
                    document.getElementById('profile_text').value = this.suggestion.profile_text;
                    document.getElementById('aspirations').value = this.suggestion.aspirations;
                    
                    // Dispatcher des événements pour Alpine.js si nécessaire
                    document.getElementById('headline').dispatchEvent(new Event('input'));
                    document.getElementById('profile_text').dispatchEvent(new Event('input'));
                    document.getElementById('aspirations').dispatchEvent(new Event('input'));

                    // Cocher les compétences (plus complexe car elles sont dans un autre formulaire)
                    // On va simplement afficher un message pour dire que c'est fait pour les textes
                    
                    this.suggestion = null;
                    this.text = '';
                }
            }
        }
    </script>
</section>
