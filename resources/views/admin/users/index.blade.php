@extends('layouts.app')

@section('content')
<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage registered user accounts.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}"
          style="display:flex;gap:0.6rem;align-items:center;margin-bottom:1.25rem;">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or email…"
               class="form-input" style="flex:1;max-width:360px;padding:0.45rem 0.7rem;">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        @if(!empty($search))
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
    </form>

    {{-- Admins table --}}
    @php $superAdminId = $admins->first()?->id; @endphp
    <h2 style="font-size:1rem;font-weight:700;margin-bottom:0.6rem;color:var(--primary);">Admins</h2>
    <div class="data-table-wrap" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td style="font-weight:600;">{{ $admin->name }}</td>
                        <td>{{ $admin->email }}</td>
                        <td>
                            @if($admin->id === $superAdminId)
                                <span class="badge badge-admin">Super Admin</span>
                            @else
                                <span class="badge badge-admin">Admin</span>
                            @endif
                        </td>
                        <td>
                            @if($admin->id === $superAdminId)
                                <span class="text-muted" style="font-size:0.85rem;">Protected</span>
                            @else
                                <form method="POST" action="{{ route('admin.users.demote', $admin) }}" style="margin:0;"
                                      onsubmit="return confirm('Demote {{ addslashes($admin->name) }} to user?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-sm">Demote</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem;">No admins found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Users table --}}
    <h2 style="font-size:1rem;font-weight:700;margin-bottom:0.6rem;color:var(--primary);">Users</h2>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="font-weight:600;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge badge-user">User</span></td>
                        <td style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <form method="POST" action="{{ route('admin.users.promote', $user) }}" style="margin:0;"
                                  onsubmit="return confirm('Promote {{ addslashes($user->name) }} to admin?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-primary btn-sm">Promote</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="margin:0;"
                                  onsubmit="return confirm('Delete {{ addslashes($user->name) }}\'s account? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem;">
                            {{ !empty($search) ? 'No users match your search.' : 'No users found.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">{{ $users->links() }}</div>
</div>
@endsection
