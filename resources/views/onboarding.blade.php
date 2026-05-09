<x-guest-layout>
    <div class="max-w-2xl mx-auto py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-600/20 rounded-3xl mb-6 border border-indigo-500/20">
                <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-black text-white mb-4">Salut {{ Auth::user()->name }} !</h1>
            <p class="text-slate-400 leading-relaxed text-lg">
                Ravi de t'avoir parmi nous. Pour que je puisse scanner les <span class="text-white font-bold">{{ \App\Models\JobOffer::where('status', 'active')->count() }}</span> offres du Forem et te trouver celles qui te correspondent vraiment, j'ai besoin de te connaître un peu.
            </p>
        </div>

        <!-- Errors -->
        @if($errors->any())
            <div class="p-5 bg-rose-500/10 border border-rose-500/20 rounded-3xl mb-8 animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="flex gap-3">
                    <svg class="w-6 h-6 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-rose-500 font-bold mb-1">Oups ! Petit souci...</h4>
                        <ul class="list-disc list-inside text-rose-400/80 text-sm space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Choices -->
        <div class="space-y-6" x-data="{ 
            uploading: false, 
            loadingStep: 1,
            error: null,
            submitForm() {
                const fileInput = this.$refs.fileInput;
                if (!fileInput.files.length) return;
                
                this.uploading = true;
                this.error = null;
                
                // Simulation d'étapes visuelles pour le confort
                let stepInterval = setInterval(() => {
                    if (this.loadingStep < 3) this.loadingStep++;
                }, 3000);

                const formData = new FormData();
                formData.append('resume', fileInput.files[0]);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route('profile.upload-resume') }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                })
                .then(async response => {
                    const data = await response.json();
                    if (response.ok) {
                        this.loadingStep = 4;
                        setTimeout(() => {
                            window.location.href = data.redirect || '{{ route('profile.builder') }}';
                        }, 500);
                    } else {
                        clearInterval(stepInterval);
                        this.uploading = false;
                        this.loadingStep = 1;
                        this.error = data.message || 'Une erreur est survenue lors de l\'analyse.';
                    }
                })
                .catch(err => {
                    clearInterval(stepInterval);
                    this.uploading = false;
                    this.loadingStep = 1;
                    this.error = 'Erreur de connexion. Vérifiez la taille de votre fichier.';
                });
            }
        }">
            <!-- Premium Loading Overlay -->
            <template x-if="uploading">
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-md p-6">
                    <div class="max-w-sm w-full text-center">
                        <!-- Orbiting Animation -->
                        <div class="relative w-32 h-32 mx-auto mb-8">
                            <div class="absolute inset-0 border-4 border-indigo-500/20 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-t-indigo-500 rounded-full animate-spin"></div>
                            <div class="absolute inset-4 border-4 border-violet-500/20 rounded-full"></div>
                            <div class="absolute inset-4 border-4 border-b-violet-500 rounded-full animate-[spin_2s_linear_infinite_reverse]"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-12 h-12 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Stepper Text -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-black text-white tracking-tight" x-show="loadingStep === 1">Lecture du fichier...</h3>
                            <h3 class="text-2xl font-black text-white tracking-tight" x-show="loadingStep === 2" style="display:none">Analyse par l'IA...</h3>
                            <h3 class="text-2xl font-black text-white tracking-tight" x-show="loadingStep === 3" style="display:none">Extraction des compétences...</h3>
                            <h3 class="text-2xl font-black text-green-400 tracking-tight" x-show="loadingStep === 4" style="display:none">C'est prêt !</h3>
                            
                            <div class="flex justify-center gap-1.5">
                                <template x-for="i in 4">
                                    <div class="h-1.5 rounded-full transition-all duration-500" 
                                         :class="loadingStep >= i ? (loadingStep === 4 ? 'w-8 bg-green-500' : 'w-8 bg-indigo-500') : 'w-2 bg-white/10'"></div>
                                </template>
                            </div>

                            <p class="text-slate-400 text-sm animate-pulse">
                                <span x-show="loadingStep === 1">On déballe ton parcours...</span>
                                <span x-show="loadingStep === 2" style="display:none">Gemini cherche tes forces cachées...</span>
                                <span x-show="loadingStep === 3" style="display:none">Dernières touches sur ton profil...</span>
                                <span x-show="loadingStep === 4" style="display:none">On te redirige vers le coach.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Choice 1: AI Builder -->
            @php 
                $chatModel = config('services.gemini.models.chat');
                $remainingChat = Auth::user()->getAiRemainingPoints($chatModel);
            @endphp
            <a href="{{ $remainingChat > 0 ? route('profile.builder') : '#' }}" 
               class="group block p-6 bg-white/5 border border-white/10 rounded-[2rem] {{ $remainingChat > 0 ? 'hover:border-indigo-500/50 hover:bg-indigo-500/5 cursor-pointer' : 'opacity-50 cursor-not-allowed' }} transition-all duration-300"
               x-show="!uploading">
                <div class="flex items-center gap-6">
                    <div class="flex-shrink-0 w-16 h-16 {{ $remainingChat > 0 ? 'bg-indigo-600' : 'bg-slate-700' }} rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-xl font-bold text-white mb-1">Discuter avec mon coach IA</h3>
                        <p class="text-slate-400 text-sm">Le moyen le plus sympa. On discute 2 minutes et l'IA s'occupe de tout.</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-widest {{ $remainingChat > 0 ? 'text-indigo-400' : 'text-rose-400' }}">
                                {{ $remainingChat > 0 ? "$remainingChat discussions restantes" : "Quota épuisé" }}
                            </span>
                        </div>
                    </div>
                    <div class="text-slate-600 group-hover:text-indigo-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>

            <!-- Choice 2: CV Import -->
            @php 
                $ocrModel = config('services.gemini.models.ocr');
                $remainingOcr = Auth::user()->getAiRemainingPoints($ocrModel);
            @endphp
            <div class="m-0">
                <label class="block group p-6 bg-white/5 border border-white/10 rounded-[2rem] {{ $remainingOcr > 0 ? 'hover:border-violet-500/50 hover:bg-violet-500/5 cursor-pointer' : 'opacity-50 cursor-not-allowed' }} transition-all duration-300">
                    <input type="file" x-ref="fileInput" name="resume" class="hidden" accept=".pdf,.docx,.txt,.jpg,.jpeg,.png,.webp" 
                           @change="{{ $remainingOcr > 0 ? 'submitForm()' : '' }}"
                           {{ $remainingOcr > 0 ? '' : 'disabled' }}>
                    
                    <div class="flex items-center gap-6">
                        <div class="flex-shrink-0 w-16 h-16 {{ $remainingOcr > 0 ? 'bg-violet-600/20' : 'bg-slate-800' }} border border-violet-500/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 {{ $remainingOcr > 0 ? 'text-violet-400' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <h3 class="text-xl font-bold text-white mb-1">Importer mon CV</h3>
                            <p class="text-slate-400 text-sm">Si tu as déjà un CV prêt (PDF ou Word), l'IA va l'analyser en quelques secondes.</p>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-widest {{ $remainingOcr > 0 ? 'text-violet-400' : 'text-rose-400' }}">
                                    {{ $remainingOcr > 0 ? "$remainingOcr imports restants" : "Quota épuisé" }}
                                </span>
                            </div>
                        </div>
                        <div class="text-slate-600 group-hover:text-violet-400 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-white/5 flex items-center justify-between">
                        <span class="text-xs text-slate-500 italic font-medium opacity-50">PDF, DOCX, Images (Max {{ $max_size }})</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Footer Help -->
        <div class="mt-12 text-center">
            <p class="text-slate-500 text-sm italic">
                "Promis, ça prend moins de temps que de lire 3 annonces au hasard."
            </p>
        </div>
    </div>
</x-guest-layout>
