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
                        <h2 class="text-center mb-4">Reset Password</h2>

                            @if ($flash = flash())
                                <div class="alert alert-{{ $flash['type'] }}">
                                    {{ $flash['message'] }}
                                </div>
                                @if ($flash['type'] !== 'success')
                                    <form method="POST" action="{{ route('password.email') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" id="email" name="email" class="form-control" required value="{{ old('email') }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Send Password Reset Link</button>
                                    </form>
                                @endif
                            @else
                                <form method="POST" action="{{ route('password.email') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email" class="form-control" required value="{{ old('email') }}">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Send Password Reset Link</button>
                                </form>
                            @endif



                        <p class="text-center mt-3">
                            <a href="{{ route('login') }}">Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>