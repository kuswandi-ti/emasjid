<nav class="navbar navbar-light bg-white border-bottom px-4">
    <div class="container-fluid">
        {{-- Current page title --}}
        <span class="navbar-brand mb-0 h5 fw-semibold">
            @yield('title', 'Owner Dashboard')
        </span>

        {{-- User dropdown --}}
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                   href="#"
                   id="userDropdown"
                   role="button"
                   data-bs-toggle="dropdown"
                   aria-expanded="false">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px; font-size: 0.85rem;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'O', 0, 1)) }}
                    </div>
                    <span class="d-none d-md-inline text-dark fw-medium">
                        {{ auth()->user()->name ?? 'Owner' }}
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <span class="dropdown-item-text text-muted small">
                            {{ auth()->user()->email ?? '' }}
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
