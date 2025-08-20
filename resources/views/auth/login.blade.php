<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Ooredoo Club Privilèges Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --brand-red: #E30613;
            --brand-dark: #1f2937;
            --bg: #f8fafc;
            --card: #ffffff;
            --muted: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --accent: #3b82f6;
            --border: #e2e8f0;
        }
        
        * { box-sizing: border-box; }
        html, body { 
            margin: 0; 
            padding: 0; 
            background: linear-gradient(135deg, var(--bg) 0%, #e2e8f0 100%);
            color: var(--brand-dark); 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 48px;
            width: 100%;
            max-width: 420px;
            margin: 20px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo h1 {
            color: var(--brand-red);
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        
        .logo p {
            color: var(--muted);
            font-size: 14px;
            margin: 8px 0 0 0;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            color: var(--brand-dark);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: var(--brand-red);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .btn:hover {
            background: #c20510;
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(227, 6, 19, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            background: var(--muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .otp-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .otp-link a {
            color: var(--brand-red);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .otp-link a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Ooredoo Club Privilèges</h1>
            <p>Dashboard Administrateur</p>
        </div>
        
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        <form id="loginForm" action="{{ route('auth.login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">Adresse e-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    required 
                    value="{{ old('email') }}"
                    placeholder="admin@exemple.com"
                >
                @error('email')
                    <div class="alert alert-danger" style="margin-top: 8px; margin-bottom: 0;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    required
                    placeholder="••••••••"
                >
                @error('password')
                    <div class="alert alert-danger" style="margin-top: 8px; margin-bottom: 0;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <span class="loading hidden"></span>
                <span id="loginText">Se connecter</span>
            </button>
        </form>
        
        <div class="otp-link">
            <a href="{{ route('auth.otp.request') }}">Connexion par code OTP</a>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const loading = btn.querySelector('.loading');
            const text = document.getElementById('loginText');
            
            btn.disabled = true;
            loading.classList.remove('hidden');
            text.textContent = 'Connexion...';
        });
    </script>
</body>
</html>
