{{-- Recorrido guiado de bienvenida — solo se incluye cuando
     DashboardController detecta que el usuario logueado todavía no lo
     vio (User::tour_seen_at null). Ver public/js/admin-tour.js. --}}
<div id="admin-tour-root"
     data-dismiss-url="{{ route('admin.tour.dismiss') }}"
     data-csrf="{{ csrf_token() }}"
     hidden></div>
<script src="{{ asset('js/admin-tour.js') }}?v={{ filemtime(public_path('js/admin-tour.js')) }}"></script>
