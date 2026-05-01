<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'info' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Tabs Navigation --}}
            <div class="flex flex-wrap gap-2 mb-8 bg-gray-200/50 p-1 rounded-2xl w-fit">
                <button @click="tab = 'info'" :class="tab === 'info' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-600 hover:text-gray-900'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Identité & Récit
                </button>
                <button @click="tab = 'facts'" :class="tab === 'facts' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-600 hover:text-gray-900'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Récits & Expériences
                </button>
                <button @click="tab = 'skills'" :class="tab === 'skills' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-600 hover:text-gray-900'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Compétences
                </button>
                <button @click="tab = 'mobility'" :class="tab === 'mobility' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-600 hover:text-gray-900'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Mobilité & Langues
                </button>
                <button @click="tab = 'security'" :class="tab === 'security' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-600 hover:text-gray-900'" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Sécurité
                </button>
            </div>

            <div class="space-y-8">
                {{-- Tab 1: Human Dimension & Info --}}
                <div x-show="tab === 'info'" x-transition class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-indigo-500">
                    <div class="max-w-6xl mx-auto">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                {{-- Tab 2: Facts --}}
                <div x-show="tab === 'facts'" x-transition class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-amber-500">
                    <div class="max-w-6xl mx-auto">
                        @include('profile.partials.manage-facts-list')
                    </div>
                </div>

                {{-- Tab 3: Skills --}}
                <div x-show="tab === 'skills'" x-transition class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-purple-500">
                    <div class="max-w-full">
                        @include('profile.partials.update-skills-form')
                    </div>
                </div>

                {{-- Tab 3: Mobility, Languages & Permits --}}
                <div x-show="tab === 'mobility'" x-transition class="space-y-8 max-w-5xl mx-auto">
                    {{-- Section Mobilité --}}
                    <div class="p-6 sm:p-10 bg-white shadow-xl shadow-indigo-100/20 sm:rounded-3xl border border-gray-100">
                        @include('profile.partials.update-mobility-form')
                    </div>

                    {{-- Section Langues --}}
                    <div class="p-6 sm:p-10 bg-white shadow-xl shadow-indigo-100/20 sm:rounded-3xl border border-gray-100">
                        @include('profile.partials.update-languages-form')
                    </div>

                    {{-- Section Permis --}}
                    <div class="p-6 sm:p-10 bg-white shadow-xl shadow-indigo-100/20 sm:rounded-3xl border border-gray-100">
                        @include('profile.partials.update-permits-form')
                    </div>
                </div>

                {{-- Tab 4: Security & Account --}}
                <div x-show="tab === 'security'" x-transition class="space-y-8">
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
