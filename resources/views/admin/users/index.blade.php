@extends('layouts.admin')

@section('title', 'Manage Users')
@section('page-title', 'Manage Users')

@section('content')
<div class="container py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="page-title fw-bold mb-1">
                <i class="fas fa-users me-2"></i> Manage Users
            </h2>
            <small class="text-muted">
                View and manage registered users. Only verified users can access the mobile app.
            </small>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="page-count">
                {{ $users->count() }} Users
            </span>

            <a href="{{ route('admin.users.export') }}"
               class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
        </div>
    </div>

    @include('includes.alert')

    {{-- ================= SEARCH BAR ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" id="userSearch"
                           class="form-control border-start-0"
                           placeholder="Search by name or email...">
                </div>
            </div>
        </div>
    </div>

    {{-- ================= USERS TABLE ================= --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 users-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:80px">ID</th>
                            <th>Name & Email</th>
                            <th class="text-center" style="width:100px">Status</th>
                            <th class="text-center" style="width:130px">Joined</th>
                            <th class="text-center" style="width:220px">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($users as $user)
                        <tr class="user-row">
                            <td class="text-center text-muted fw-semibold">#{{ $user->id }}</td>

                            <td>
                                <div class="fw-semibold user-name">{{ $user->name }}</div>
                                <div class="text-muted small user-email">{{ $user->email }}</div>
                            </td>

                            <td class="text-center">
                                <span class="badge px-3 py-1
                                    {{ $user->email_verified_at
                                        ? 'bg-success-subtle text-success'
                                        : 'bg-warning-subtle text-warning' }}">
                                    {{ $user->email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>

                            <td class="text-center text-muted">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">

                                    {{-- VIEW --}}
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewUserModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-phone="{{ $user->phone_number }}"
                                        data-address="{{ $user->address }}"
                                        data-status="{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}"
                                        data-registered="{{ $user->created_at->format('d M Y, h:i A') }}">
                                        View
                                    </button>

                                    {{-- EDIT --}}
                                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-phone="{{ $user->phone_number }}"
                                        data-address="{{ $user->address }}">
                                        Edit
                                    </button>

                                    {{-- DELETE --}}
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteUserModal"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}">
                                        Delete
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= VIEW MODAL (SAME LAYOUT AS EDIT) ================= --}}
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">

            <div class="modal-header">
                <h5 class="fw-bold text-primary">
                    <i class="fas fa-user me-2"></i>User Details
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="v_name" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="text" id="v_email" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" id="v_phone" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea id="v_address" class="form-control" rows="2" readonly></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        <small class="text-muted">Registered At</small>
                        <div id="v_registered" class="fw-semibold"></div>
                    </div>
                    <span id="v_status" class="badge px-3 py-1"></span>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

{{-- ================= EDIT MODAL ================= --}}
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">

            <form method="POST" id="editUserForm">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="fw-bold text-warning">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="e_name"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="e_email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone_number" id="e_phone"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="e_address"
                                  class="form-control" rows="2"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning">Save Changes</button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ================= DELETE MODAL ================= --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">

            <form method="POST" id="deleteUserForm">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="fw-bold text-danger">
                        <i class="fas fa-trash me-2"></i>Delete User
                    </h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-0">
                        Are you sure you want to delete
                        <strong id="deleteUserName"></strong>?
                    </p>
                    <small class="text-muted">
                        This action cannot be undone.
                    </small>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button class="btn btn-danger">
                        Yes, Delete
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


@endsection

@section('scripts')
<script>
/* SEARCH */
document.getElementById('userSearch').addEventListener('keyup', function () {
    const k = this.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(r => {
        r.style.display =
            r.innerText.toLowerCase().includes(k) ? '' : 'none';
    });
});

/* VIEW MODAL */
document.getElementById('viewUserModal')
.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;

    document.getElementById('v_name').value = b.dataset.name;
    document.getElementById('v_email').value = b.dataset.email;
    document.getElementById('v_phone').value = b.dataset.phone || '';
    document.getElementById('v_address').value = b.dataset.address || '';
    document.getElementById('v_registered').innerText = b.dataset.registered;

    const badge = document.getElementById('v_status');
    badge.innerText = b.dataset.status;
    badge.className =
        b.dataset.status === 'Verified'
            ? 'badge bg-success-subtle text-success px-3 py-1'
            : 'badge bg-warning-subtle text-warning px-3 py-1';
});

/* EDIT MODAL */
document.getElementById('editUserModal')
.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;

    document.getElementById('e_name').value = b.dataset.name;
    document.getElementById('e_email').value = b.dataset.email;
    document.getElementById('e_phone').value = b.dataset.phone || '';
    document.getElementById('e_address').value = b.dataset.address || '';

    document.getElementById('editUserForm').action =
        `/admin/users/${b.dataset.id}`;
});

document.getElementById('deleteUserModal')
.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;

    document.getElementById('deleteUserName').innerText =
        b.dataset.userName;

    document.getElementById('deleteUserForm').action =
        `/admin/users/${b.dataset.userId}`;
});
</script>
@endsection
