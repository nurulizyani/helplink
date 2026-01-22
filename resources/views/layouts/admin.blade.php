<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'HelpLink Admin')</title>

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

        /* ================= TOPBAR (GLOBAL FIX) ================= */
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


        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #1f2937, #111827);
            color: #e5e7eb;
            padding: 24px 16px;
            z-index: 1000;
            transition: width .25s ease;
            display: flex;
            flex-direction: column;
        }

        body.sidebar-collapsed .sidebar {
            width: 80px;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 24px;
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

        .nav-link {
            color: #d1d5db;
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
            font-size: .95rem;
        }

        .nav-link.active {
            background-color: #2563eb;
            color: #fff;
            font-weight: 600;
        }

        body.sidebar-collapsed .menu-text {
            display: none;
        }

        body.sidebar-collapsed .nav-link {
            justify-content: center;
        }

        .sidebar-menu {
            flex: 1;
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

       @media (max-width: 576px) {
    .topbar {
        padding: 0 16px;
    }

    .main-content {
        padding: 16px;
    }
}

        .main-content {
            padding: 24px;
        }

/* ================= NOTIFICATION UI (COMPACT) ================= */
.notification-card {
    padding: 8px 12px;              /* ↓ kurang tinggi */
    border-radius: 8px;
    margin: 4px 8px;                /* ↓ jarak antara card */
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    transition: all .15s ease;
}

.notification-card:hover {
    background: #f9fafb;
}

.notification-card.unread {
    background: #eef4ff;
    border-left: 3px solid #2563eb; /* ↓ nipiskan sikit */
}

/* TITLE */
.notif-title {
    font-weight: 600;
    font-size: 0.85rem;             /* ↓ kecil sikit */
    color: #111827;
    margin-bottom: 2px;             /* ↓ rapatkan */
    line-height: 1.2;
}

/* MESSAGE */
.notif-message {
    font-size: 0.8rem;              /* ↓ */
    color: #374151;
    line-height: 1.25;              /* ↓ rapat */
}

/* TIME */
.notif-time {
    font-size: 0.7rem;              /* ↓ */
    color: #6b7280;
    margin-top: 2px;                /* ↓ besar sangat sebelum ni */
}

/* DROPDOWN */
.dropdown-menu {
    max-height: 65vh;
    overflow-y: auto;
}




        @media (max-width: 991px) {
    .sidebar {
        position: fixed;
        left: -260px;
        width: 250px;
        transition: left .3s ease;
    }

    body.sidebar-open .sidebar {
        left: 0;
    }

    .content-wrapper {
        margin-left: 0;
        width: 100%;
    }
}

    </style>
</head>

<body>

<div class="admin-wrapper">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-hand-holding-heart sidebar-brand-icon"></i>
        <span class="sidebar-brand-text">HelpLink Admin</span>
    </div>

    <div class="sidebar-menu">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i><span class="menu-text">Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="fas fa-users"></i><span class="menu-text">Manage Users</span>
        </a>

        <a href="{{ route('admin.requests.index') }}" class="nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
            <i class="fas fa-box-open"></i><span class="menu-text">Requests</span>
        </a>

        <a href="{{ route('admin.offers.index') }}" class="nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
            <i class="fas fa-gift"></i><span class="menu-text">Offers</span>
        </a>

        <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <i class="fas fa-bell"></i><span class="menu-text">Notifications</span>
        </a>
    </div>

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button class="btn btn-danger w-100">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text ms-2">Logout</span>
        </button>
    </form>
</aside>

<!-- ================= CONTENT ================= -->
<div class="content-wrapper">

<header class="topbar">
    <div class="topbar-left">
        <button id="sidebarToggle" class="btn btn-light">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="topbar-right">
        <div class="dropdown">
            <button class="btn btn-light position-relative"
                    data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span id="notif-count"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow"
                id="notif-list"
                style="max-width:90vw; width:320px">
                <li class="dropdown-header fw-bold">Notifications</li>
                <li class="text-center small text-muted py-2">Loading...</li>
            </ul>
        </div>
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


async function loadNotifications() {
    const res = await fetch("{{ route('admin.notifications.unread') }}");
    const data = await res.json();

    const list = document.getElementById('notif-list');
    const badge = document.getElementById('notif-count');

    list.innerHTML = '<li class="dropdown-header fw-bold">Notifications</li>';

    if (data.count > 0) {
        badge.classList.remove('d-none');
        badge.innerText = data.count;
    } else {
        badge.classList.add('d-none');
    }

    if (data.notifications.length === 0) {
        list.innerHTML += '<li class="text-center small text-muted py-2">No new notifications</li>';
        return;
    }

    data.notifications.forEach(n => {
    list.innerHTML += `
        <li class="notification-card ${n.read_at ? '' : 'unread'}"
            onclick="markAsRead('${n.id}')">
            <div class="notif-title">
                ${n.title ?? 'Notification'}
            </div>
            <div class="notif-message">
                ${n.message}
            </div>
            <div class="notif-time">
                ${n.time ?? ''}
            </div>
        </li>
    `;
});


    list.innerHTML += `
        <li><hr class="dropdown-divider"></li>
        <li>
            <a href="{{ route('admin.notifications.index') }}"
               class="dropdown-item text-center small text-primary">
               View all notifications
            </a>
        </li>`;
}

        async function markAsRead(id) {
            await fetch(`/admin/notifications/read/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            loadNotifications();
        }

        loadNotifications();
        setInterval(loadNotifications, 15000);
        </script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    })
</script>

</body>
</html>
