@extends('layouts.app')

@section('content')
<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Promote users to admin to grant full event management access.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

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
                        <td>
                            @if($user->is_admin)
                                <span class="badge badge-admin">Admin</span>
                            @else
                                <span class="badge badge-user">User</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            @if(!$user->is_admin)
                                <form method="POST" action="{{ route('admin.users.promote', $user) }}" style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-accent btn-sm">Promote to Admin</button>
                                </form>
                            @else
                                <span class="text-muted" style="font-size:0.85rem;">Already admin</span>
                            @endif
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="margin:0;"
                                      onsubmit="return confirm('Delete {{ addslashes($user->name) }}\'s account? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--muted);padding:2rem;">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">{{ $users->links() }}</div>
</div>
@endsection
