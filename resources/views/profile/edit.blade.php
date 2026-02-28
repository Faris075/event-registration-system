<x-app-layout>
    <div class="page-wrap" style="max-width:760px;">

        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
        </div>

        <div class="card" style="margin-bottom:1.25rem;">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card" style="margin-bottom:1.25rem;">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card" style="margin-bottom:1.25rem;">
            @include('profile.partials.update-currency-form')
        </div>

        <div class="card">
            @include('profile.partials.delete-user-form')
        </div>

    </div>
</x-app-layout>
