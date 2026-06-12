<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Owner Dashboard') - EMasjid</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* Task 17.2: Prevent horizontal overflow on all viewports */
        html, body {
            overflow-x: hidden;
        }

        /* Task 17.2: Sidebar collapses out-of-flow on small screens */
        @@media (max-width: 767.98px) {
            #sidebar-wrapper {
                display: none !important;
            }
            #page-content-wrapper {
                width: 100% !important;
            }
        }

        /* Task 17.2: Sidebar fixed width on tablet+ */
        @@media (min-width: 768px) {
            #sidebar-wrapper {
                flex-shrink: 0;
            }
        }

        /* Task 17.2: Make all tables inside owner panel horizontally scrollable */
        .owner-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Task 16.1: Page-loading overlay */
        #page-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.15s ease;
        }
        #page-loading-overlay.hidden {
            display: none;
        }
        .loading-spinner-ring {
            width: 48px;
            height: 48px;
            border: 5px solid #dee2e6;
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }
        @@keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Task 16.1: Skeleton shimmer for stat card values */
        .skeleton {
            background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 4px;
            display: inline-block;
        }
        @@keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Task 16.1: Page-loading overlay (shown during navigation/form submission) --}}
    <div id="page-loading-overlay" class="hidden" role="status" aria-label="Loading…">
        <div class="loading-spinner-ring"></div>
    </div>

    <div class="d-flex" id="wrapper">

        <!-- Sidebar -->
        @include('owner.partials.sidebar')

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper" class="w-100">

            <!-- Top Navigation -->
            @include('owner.partials.navbar')

            <!-- Main Content -->
            <div class="container-fluid p-4">
                @yield('content')
            </div>

        </div>

    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Alpine.js 3.x -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Task 16.1: Show loading overlay on navigation and form submission --}}
    <script>
        (function () {
            var overlay = document.getElementById('page-loading-overlay');

            function showOverlay() {
                overlay.classList.remove('hidden');
            }

            // Show on any regular link click (skip anchor-only, external, and download links)
            document.addEventListener('click', function (e) {
                var anchor = e.target.closest('a[href]');
                if (!anchor) return;

                var href = anchor.getAttribute('href');
                // Skip: empty, hash-only, javascript:, external domains, or has download attr
                if (!href || href === '#' || href.startsWith('#') ||
                    href.startsWith('javascript:') || anchor.hasAttribute('download') ||
                    anchor.target === '_blank') return;

                // Skip external URLs
                try {
                    var url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                } catch (_) { return; }

                showOverlay();
            });

            // Show on form submission (filter, search forms)
            document.addEventListener('submit', function (e) {
                if (e.target.tagName === 'FORM') {
                    showOverlay();
                }
            });

            // Hide overlay when browser navigates back (bfcache restore)
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    overlay.classList.add('hidden');
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
