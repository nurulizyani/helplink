<div class="dropdown">

    {{-- BELL BUTTON --}}
    <button class="btn btn-light position-relative"
            id="adminNotifDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false">

        <i class="fas fa-bell"></i>

        {{-- RED DOT --}}
        <span id="adminNotifDot"
              class="position-absolute top-0 start-100 translate-middle
                     p-1 bg-danger border border-light rounded-circle d-none">
        </span>
    </button>

    {{-- DROPDOWN --}}
    <ul class="dropdown-menu dropdown-menu-end shadow-sm"
        style="width: 340px;"
        id="adminNotifList">

        <li class="dropdown-header fw-semibold">
            Notifications
        </li>

        <li><hr class="dropdown-divider"></li>

        <li class="text-center text-muted py-3 small">
            Loading notifications...
        </li>
    </ul>
</div>
