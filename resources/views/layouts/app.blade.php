<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Teamboard') &middot; Teamboard</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="nav">
        <a class="logo" href="{{ url('/') }}">Teamboard</a>
        <nav class="links">
            @auth
                @isset($workspace)
                    <a href="{{ route('projects.index') }}">Projects</a>
                    @if (in_array($workspaceRole, ['owner', 'admin']))
                        <a href="{{ route('members.index') }}">Members</a>
                    @endif
                    @if ($workspaceRole === 'owner')
                        <a href="{{ route('plan.show') }}">Plan</a>
                    @endif
                    <span class="muted">{{ $workspace->name }}</span>
                @endisset
                <a href="{{ route('workspaces.create') }}">Workspaces</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-btn" type="submit">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
