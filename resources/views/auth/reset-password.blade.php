<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <header class="bg-primary text-white py-4 mb-5 shadow-sm">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0 fs-3">Welcome to Our Application</h1>
                <nav>
                    <ul class="nav">
                        <li class="nav-item">
                            <a href="{{ route('home') }}" class="nav-link text-white">Home</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Change Password</h2>
                        @if ($flash = flash())
                            <div class="alert alert-{{ $flash['type'] }}">
                                {{ $flash['message'] }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter new password">
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-white text-center py-4 mt-5 border-top">
        <p class="mb-0 text-muted">&copy; {{ date('Y') }} Welcome App</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist
/js/bootstrap.bundle.min.js"></script>
</body>
</html>