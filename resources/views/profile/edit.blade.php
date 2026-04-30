<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- Section 1: Human Dimension & Info --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-indigo-500">
                <div class="max-w-4xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Section 2: Skills --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-purple-500">
                <div class="max-w-4xl">
                    @include('profile.partials.update-skills-form')
                </div>
            </div>

            {{-- Section 3: Languages & Permits --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-green-500">
                    @include('profile.partials.update-languages-form')
                </div>
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl border-l-4 border-yellow-500">
                    @include('profile.partials.update-permits-form')
                </div>
            </div>

            {{-- Section 4: Security & Account --}}
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
</x-app-layout>
