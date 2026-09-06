<x-app-layout>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold tracking-tight">Profile</h1>
            <a href="{{ route('home') }}" class="text-sm text-teal-800 hover:underline">Back to pantry</a>
        </div>

        <div class="space-y-4 rounded-2xl border border-stone-200 bg-white p-4">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="space-y-4 rounded-2xl border border-stone-200 bg-white p-4">
            @include('profile.partials.update-password-form')
        </div>

        <div class="space-y-4 rounded-2xl border border-stone-200 bg-white p-4">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
