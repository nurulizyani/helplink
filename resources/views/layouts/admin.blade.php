<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'HelpLink Admin')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background-color: #f4f6f8;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #0f172a;
            color: #e5e7eb;
            padding: 24px 16px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: width .25s ease;
        }

        body.sidebar-collapsed .sidebar {
            width: 80px;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #fff;
        }

        .sidebar-brand-icon {
            display: none;
            font-size: 1.4rem;
        }

        body.sidebar-collapsed .sidebar-brand-text {
            display: none;
        }

        body.sidebar-collapsed .sidebar-brand-icon {
            display: block;
        }

        .sidebar-menu {
            flex: 1;
        }

        .sidebar-brand a:hover {
            color: #3b82f6; /* biru terang */
        }

        .sidebar-divider {
            height: 1px;
            width: 100%;
            margin: 14px 0 22px;
            background: linear-gradient(
                to right,
                transparent,
                rgba(255,255,255,0.25),
                transparent
            );
        }
        .sidebar-divider.bottom {
            opacity: .6;
        }

        .nav-link {
            color: #cbd5e1;
            padding: 20px 18px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 6px;
            font-size: 1rem;
            font-weight: 500;
            transition: all .2s ease;
        }

        .nav-link i {
            font-size: 1.1rem;
            min-width: 22px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }

        .nav-link.active {
            background: rgba(59,130,246,.25);
            color: #fff;
            font-weight: 600;
        }

        body.sidebar-collapsed .menu-text {
            display: none;
        }

        body.sidebar-collapsed .nav-link {
            justify-content: center;
        }

        /* LOGOUT */
        .logout-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,.15);
            opacity: .9;
            color: #e5e7eb;
            padding: 10px;
            border-radius: 10px;
            font-size: .9rem;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,.1);
        }
        .sidebar form button {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
        }

        /* ================= CONTENT ================= */
        .content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all .25s ease;
        }

        body.sidebar-collapsed .content-wrapper {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        /* ================= TOPBAR ================= */
        .topbar {
            height: 72px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 110;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
        }

        .topbar-right {
            gap: 12px;
        }

        .main-content {
            padding: 24px;
        }

        /* ================= MOBILE ================= */
        @media (max-width: 991px) {
            .sidebar {
                left: -260px;
            }

            body.sidebar-open .sidebar {
                left: 0;
            }

            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
        .page-title {
            color: #1e3a8a;
        }
        .page-title i {
            color: inherit;
        }
        .page-count {
            background: transparent;
            color: #64748b; /* slate */
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0;
        }

    </style>
</head>

<body>

<div class="admin-wrapper">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
    <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-white">
        <i class="fas fa-hand-holding-heart sidebar-brand-icon"></i>
        <span class="sidebar-brand-text">HelpLink Admin</span>
    </a>
</div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-menu">
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span class="menu-text">Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i>
            <span class="menu-text">Users</span>
        </a>

        <a href="{{ route('admin.offers.index') }}"
           class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
            <i class="fas fa-gift"></i>
            <span class="menu-text">Offers</span>
        </a>

        <a href="{{ route('admin.requests.index') }}"
           class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
            <i class="fas fa-box-open"></i>
            <span class="menu-text">Requests</span>
        </a>

        <a href="{{ route('admin.notifications.index') }}"
           class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <i class="fas fa-bell"></i>
            <span class="menu-text">Notifications</span>
        </a>
    </div>

    <div class="sidebar-divider"></div>

    <!-- Logout trigger only -->
    <button type="button"
            class="btn logout-btn w-100"
            data-bs-toggle="modal"
            data-bs-target="#logoutConfirmModal">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-text ms-2">Logout</span>
    </button>
</aside>

<div class="modal fade" id="logoutConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">

            <div class="modal-header">
                <h5 class="fw-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Logout
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to log out from the admin panel?
                </p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">
                    Cancel
                </button>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        Yes, Logout
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>



<!-- ================= CONTENT ================= -->
<div class="content-wrapper">

<header class="topbar">
    <div class="topbar-left">
        <button id="sidebarToggle" class="btn btn-light">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="topbar-right">
    @include('admin.partials.notification-bell')
    @yield('topbar-right')
</div>

</header>

<main class="main-content">
    @yield('content')
</main>

</div>
</div>

<!-- ================= SCRIPTS ================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('sidebarToggle')
    .addEventListener('click', () => {
        if (window.innerWidth < 992) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
        }
    });
</script>

@yield('scripts')
        <script>
        function loadAdminNotifications() {
            fetch('{{ route('admin.notifications.unread') }}')
                .then(res => res.json())
                .then(data => {

                    const dot  = document.getElementById('adminNotifDot');
                    const list = document.getElementById('adminNotifList');

                    if (!dot || !list) return;

                    // RED DOT
                    if (data.count > 0) {
                        dot.classList.remove('d-none');
                    } else {
                        dot.classList.add('d-none');
                    }

                    // LIST
                    let html = `
                        <li class="dropdown-header fw-semibold">
                            Notifications (${data.count})
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    `;

                    if (data.notifications.length === 0) {
                        html += `
                            <li class="text-center text-muted py-3 small">
                                No new notifications
                            </li>
                        `;
                    } else {
                        data.notifications.forEach(n => {
                            html += `
                                <li class="px-3 py-2">
                                    <div class="fw-semibold/compiler">${n.title}</div>
                                    <div class="small text-muted">${n.message}</div>
                                    <div class="small text-secondary">${n.time}</div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            `;
                        });
                    }

                    list.innerHTML = html;
                })
                .catch(err => console.error(err));
        }

        // INITIAL LOAD
        document.addEventListener('DOMContentLoaded', () => {
            loadAdminNotifications();
            setInterval(loadAdminNotifications, 15000); // 15s refresh
        });
        </script>

</body>
</html>
