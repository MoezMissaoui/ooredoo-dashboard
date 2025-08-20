<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sub-Stores - Ooredoo Club Privilèges</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            --purple: #8b5cf6;
            --orange: #f97316;
        }

        * { box-sizing: border-box; }
        html, body { 
            margin: 0; 
            padding: 0; 
            background: var(--bg); 
            color: var(--brand-dark); 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--brand-red);
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--brand-dark);
            margin: 0;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg);
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-weight: 600;
            color: var(--brand-dark);
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: var(--muted);
        }

        .nav-btn, .logout-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .nav-btn {
            background: var(--brand-red);
            color: white;
        }

        .nav-btn:hover {
            background: #c20510;
            text-decoration: none;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
        }

        .logout-btn:hover {
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            background: var(--card);
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .nav-tab {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: var(--muted);
        }

        .nav-tab.active {
            background: var(--brand-red);
            color: white;
        }

        .nav-tab:hover:not(.active) {
            background: #f1f5f9;
        }

        /* Enhanced Filters Bar */
        .enhanced-filters-bar {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 32px;
            align-items: stretch;
        }

        .date-selection-section {
            flex: 2;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--brand-dark);
        }

        .date-periods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .date-period {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
        }

        .period-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .period-icon {
            font-size: 12px;
        }

        .date-inputs {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-input-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-input-group label {
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
        }

        .enhanced-date-input {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .enhanced-date-input:focus {
            outline: none;
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
        }

        .date-separator {
            font-size: 16px;
            color: var(--muted);
            margin-top: 16px;
        }

        .period-display {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
            font-style: italic;
        }

        .controls-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            color: var(--brand-dark);
        }

        .enhanced-select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }

        .enhanced-select:focus {
            outline: none;
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
        }

        .control-info {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-left: auto;
        }

        .enhanced-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--brand-red);
            color: white;
        }

        .btn-primary:hover {
            background: #c41e3a;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--accent);
            color: white;
        }

        .btn-secondary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-accent {
            background: var(--success);
            color: white;
        }

        .btn-accent:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-info {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #2bbac6 0%, #4a75d3 100%);
            transform: translateY(-1px);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* KPIs Grid */
        .kpis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--brand-red);
            transition: all 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .kpi-card.accent-blue { border-left-color: var(--accent); }
        .kpi-card.accent-green { border-left-color: var(--success); }
        .kpi-card.accent-purple { border-left-color: var(--purple); }
        .kpi-card.accent-orange { border-left-color: var(--orange); }

        .kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .kpi-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kpi-icon {
            font-size: 20px;
        }

        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--brand-dark);
            line-height: 1;
            margin-bottom: 8px;
        }

        .kpi-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .kpi-change.positive { color: var(--success); }
        .kpi-change.negative { color: var(--danger); }
        .kpi-change.neutral { color: var(--muted); }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Tables */
        .table-card {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .table-container {
            overflow-x: auto;
        }

        .enhanced-table {
            width: 100%;
            border-collapse: collapse;
        }

        .enhanced-table th {
            background: #f8f9fa;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--brand-dark);
            font-size: 14px;
            border-bottom: 1px solid var(--border);
        }

        .enhanced-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .enhanced-table tr:hover {
            background: #f8f9fa;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .rank-1 { background: #fbbf24; }
        .rank-2 { background: #9ca3af; }
        .rank-3 { background: #d97706; }
        .rank-other { background: var(--muted); }

        .status-badge-table {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active { background: #dcfce7; color: #16a34a; }
        .status-inactive { background: #fee2e2; color: #dc2626; }

        /* Loading and Notifications */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--brand-red);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 1000;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
        }

        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .notification.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .notification.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            
            .enhanced-filters-bar {
                flex-direction: column;
                gap: 20px;
            }
            
            .date-periods {
                grid-template-columns: 1fr;
            }
            
            .kpis-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo">🏪</div>
                <h1 class="header-title">Dashboard Sub-Stores</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                    <div class="user-role">{{ Auth::user()->role->display_name ?? 'Utilisateur' }}</div>
                </div>
                
                <a href="{{ route('dashboard') }}" class="nav-btn">
                    📊 Dashboard Opérateurs
                </a>
                
                @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="nav-btn">
                        👥 Administration
                    </a>
                @endif
                
                <form action="{{ route('auth.logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>
        </div>

        <!-- Enhanced Date & Filters Bar -->
        <div class="enhanced-filters-bar">
            <!-- Date Selection Section -->
            <div class="date-selection-section">
                <div class="section-title">
                    <span class="section-icon">📅</span>
                    <span>Sélection des Périodes</span>
                </div>
                
                <div class="date-periods">
                    <!-- Période Principale -->
                    <div class="date-period primary-period">
                        <div class="period-header">
                            <span class="period-icon">🔵</span>
                            <span class="period-label">Période Principale</span>
                        </div>
                        <div class="date-inputs">
                            <div class="date-input-group">
                                <label>Du</label>
                                <input type="date" id="start-date" class="enhanced-date-input" onchange="updateDateRange()">
                            </div>
                            <div class="date-separator">→</div>
                            <div class="date-input-group">
                                <label>Au</label>
                                <input type="date" id="end-date" class="enhanced-date-input" onchange="updateDateRange()">
                            </div>
                        </div>
                        <div class="period-display" id="primaryPeriod">Chargement...</div>
                    </div>

                    <!-- Période de Comparaison -->
                    <div class="date-period comparison-period">
                        <div class="period-header">
                            <span class="period-icon">🟡</span>
                            <span class="period-label">Période de Comparaison</span>
                        </div>
                        <div class="date-inputs">
                            <div class="date-input-group">
                                <label>Du</label>
                                <input type="date" id="comparison-start-date" class="enhanced-date-input" onchange="updateDateRange()">
                            </div>
                            <div class="date-separator">→</div>
                            <div class="date-input-group">
                                <label>Au</label>
                                <input type="date" id="comparison-end-date" class="enhanced-date-input" onchange="updateDateRange()">
                            </div>
                        </div>
                        <div class="period-display" id="comparisonPeriod">Chargement...</div>
                    </div>
                </div>
            </div>

            <!-- Filters & Controls Section -->
            <div class="controls-section">
                <div class="control-group">
                    <div class="control-label">
                        <span class="control-icon">🏪</span>
                        <span>Sub-Store</span>
                    </div>
                    <select id="sub-store-select" class="enhanced-select" onchange="handleSubStoreChange()">
                        <!-- Les sub-stores seront chargés dynamiquement -->
                    </select>
                    <div id="sub-store-info" class="control-info">
                        Chargement des sub-stores...
                    </div>
                </div>

                <div class="control-group">
                    <div class="control-label">
                        <span class="control-icon">⚡</span>
                        <span>Status</span>
                    </div>
                    <div class="status-badge active">Sub-Stores Analytics</div>
                </div>

                <div class="action-buttons">
                    <button class="btn-primary enhanced-btn" onclick="loadDashboardData()" id="refresh-btn">
                        <span id="refresh-text">🔄 Actualiser</span>
                        <span id="refresh-loading" style="display: none;">⏳ Chargement...</span>
                    </button>
                    
                    <button class="btn-secondary enhanced-btn" onclick="setSmartComparison()">
                        📊 Comparaison Auto
                    </button>
                    
                    <button class="btn-accent enhanced-btn" onclick="exportSubStoreData()">
                        📥 Exporter
                    </button>
                    
                    <button class="btn-info enhanced-btn" onclick="showKeyboardShortcutsHelp()">
                        ⌨️ Aide
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('overview')">📊 Vue d'Ensemble</button>
            <button class="nav-tab" onclick="showTab('stores')">🏪 Sub-Stores</button>
            <button class="nav-tab" onclick="showTab('categories')">📂 Catégories</button>
            <button class="nav-tab" onclick="showTab('performance')">⚡ Performance</button>
        </div>

        <!-- Tab 1: Vue d'Ensemble -->
        <div id="overview" class="tab-content active">
            <!-- KPIs Grid -->
            <div class="kpis-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-title">
                            <span class="kpi-icon">🏪</span>
                            Nouveaux Sub-Stores
                        </div>
                    </div>
                    <div class="kpi-value" id="new-sub-stores-value">0</div>
                    <div class="kpi-change neutral" id="new-sub-stores-change">
                        <span>📊</span>
                        <span>+0%</span>
                    </div>
                </div>

                <div class="kpi-card accent-blue">
                    <div class="kpi-header">
                        <div class="kpi-title">
                            <span class="kpi-icon">⚡</span>
                            Sub-Stores Actifs
                        </div>
                    </div>
                    <div class="kpi-value" id="active-sub-stores-value">0</div>
                    <div class="kpi-change neutral" id="active-sub-stores-change">
                        <span>📊</span>
                        <span>+0%</span>
                    </div>
                </div>

                <div class="kpi-card accent-green">
                    <div class="kpi-header">
                        <div class="kpi-title">
                            <span class="kpi-icon">👥</span>
                            Total Clients
                        </div>
                    </div>
                    <div class="kpi-value" id="total-clients-value">0</div>
                    <div class="kpi-change neutral" id="total-clients-change">
                        <span>📊</span>
                        <span>+0%</span>
                    </div>
                </div>

                <div class="kpi-card accent-orange">
                    <div class="kpi-header">
                        <div class="kpi-title">
                            <span class="kpi-icon">💰</span>
                            Revenus Estimés
                        </div>
                    </div>
                    <div class="kpi-value" id="estimated-revenue-value">0€</div>
                    <div class="kpi-change neutral" id="estimated-revenue-change">
                        <span>📊</span>
                        <span>+0%</span>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <span>📈</span>
                            Performance des Sub-Stores
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="storePerformanceChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <span>🎯</span>
                            Répartition par Catégories
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Sub-Stores -->
        <div id="stores" class="tab-content">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <span>🏪</span>
                        Top Sub-Stores Performance
                    </div>
                    <div class="table-actions">
                        <button class="btn-secondary" onclick="exportSubStoreData()">📥 Exporter</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Nom du Sub-Store</th>
                                <th>Catégorie</th>
                                <th>Transactions</th>
                                <th>Clients Uniques</th>
                                <th>Localisation</th>
                                <th>Croissance</th>
                            </tr>
                        </thead>
                        <tbody id="subStoresTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Catégories -->
        <div id="categories" class="tab-content">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <span>📂</span>
                        Performance par Catégorie
                    </div>
                </div>
                <div class="table-container">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Nombre de Stores</th>
                                <th>Total Transactions</th>
                                <th>Part de Marché</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Performance -->
        <div id="performance" class="tab-content">
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <span>📊</span>
                            Évolution Mensuelle
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <span>🎯</span>
                            Taux d'Activation
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="activationRateChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let dashboardData = null;
        let charts = {};

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            setDefaultDates();
            updateDateRange();
            initializeDashboard();
        });

        // Initialize dashboard in correct order
        async function initializeDashboard() {
            try {
                // 1. Load sub-stores first
                await loadSubStores();
                // 2. Then load dashboard data
                await loadDashboardData();
            } catch (error) {
                console.error('Erreur lors de l\'initialisation:', error);
                showNotification('Erreur lors de l\'initialisation du dashboard', 'error');
            }
        }

        // Set default dates
        function setDefaultDates() {
            const today = new Date();
            const sixMonthsAgo = new Date(today);
            sixMonthsAgo.setMonth(today.getMonth() - 6);
            
            const twelveMonthsAgo = new Date(today);
            twelveMonthsAgo.setMonth(today.getMonth() - 12);
            
            const sixMonthsAgoEnd = new Date(sixMonthsAgo);
            sixMonthsAgoEnd.setDate(sixMonthsAgo.getDate() - 1);

            document.getElementById('start-date').value = sixMonthsAgo.toISOString().split('T')[0];
            document.getElementById('end-date').value = today.toISOString().split('T')[0];
            document.getElementById('comparison-start-date').value = twelveMonthsAgo.toISOString().split('T')[0];
            document.getElementById('comparison-end-date').value = sixMonthsAgoEnd.toISOString().split('T')[0];
        }

        // Update date range display
        function updateDateRange() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const compStartDate = document.getElementById('comparison-start-date').value;
            const compEndDate = document.getElementById('comparison-end-date').value;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                document.getElementById('primaryPeriod').textContent = 
                    `${start.toLocaleDateString('fr-FR')} - ${end.toLocaleDateString('fr-FR')}`;
            }

            if (compStartDate && compEndDate) {
                const start = new Date(compStartDate);
                const end = new Date(compEndDate);
                document.getElementById('comparisonPeriod').textContent = 
                    `${start.toLocaleDateString('fr-FR')} - ${end.toLocaleDateString('fr-FR')}`;
            }
        }

        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to selected tab
            event.target.classList.add('active');
            
            // Resize charts when tab becomes visible
            setTimeout(() => {
                Object.values(charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }, 100);
        }

        // Load available sub-stores
        async function loadSubStores() {
            try {
                const response = await fetch('/sub-stores/api/sub-stores');
                const data = await response.json();
                
                const select = document.getElementById('sub-store-select');
                select.innerHTML = '';
                
                data.sub_stores.forEach(store => {
                    const option = document.createElement('option');
                    option.value = store.name;
                    option.textContent = `🏪 ${store.name}`;
                    if (store.name === data.default_sub_store) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                
                // Update info text
                const infoText = data.user_role === 'super_admin' ? 
                    'Accès global à tous les sub-stores' : 
                    'Sub-stores autorisés selon vos permissions';
                    
                document.getElementById('sub-store-info').textContent = infoText;
                
            } catch (error) {
                console.error('Error loading sub-stores:', error);
                showNotification('Erreur lors du chargement des sub-stores', 'error');
            }
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                showLoading();
                
                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;
                const comparisonStartDate = document.getElementById('comparison-start-date').value;
                const comparisonEndDate = document.getElementById('comparison-end-date').value;
                const selectedSubStore = document.getElementById('sub-store-select').value;
                
                let apiUrl = '/sub-stores/api/dashboard/data';
                const params = new URLSearchParams();
                
                if (startDate && endDate) {
                    params.append('start_date', startDate);
                    params.append('end_date', endDate);
                }
                
                if (comparisonStartDate && comparisonEndDate) {
                    params.append('comparison_start_date', comparisonStartDate);
                    params.append('comparison_end_date', comparisonEndDate);
                }
                
                if (selectedSubStore) {
                    params.append('sub_store', selectedSubStore);
                }
                
                if (params.toString()) {
                    apiUrl += '?' + params.toString();
                }
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                console.log('Sub-Store dashboard data loaded:', data);
                
                updateDashboard(data);
                hideLoading();
                
                const storeLabel = selectedSubStore === 'ALL' ? 'tous sub-stores' : selectedSubStore;
                showNotification(`✅ Données ${storeLabel} mises à jour!`, 'success');
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                hideLoading();
                showNotification('Erreur de connexion: ' + error.message, 'error');
            }
        }

        // Update dashboard with data
        function updateDashboard(data) {
            // Store data globally
            dashboardData = data;
            
            // Update periods
            const primaryPeriodEl = document.getElementById('primaryPeriod');
            if (primaryPeriodEl && data.periods) {
                primaryPeriodEl.textContent = data.periods.primary;
            }
            
            const comparisonPeriodEl = document.getElementById('comparisonPeriod');
            if (comparisonPeriodEl && data.periods) {
                comparisonPeriodEl.textContent = data.periods.comparison;
            }
            
            // Update KPIs
            updateKPIs(data.kpis);
            
            // Update tables
            updateSubStoresTable(data.sub_stores || []);
            updateCategoriesTable(data.categoryDistribution || []);
            
            // Update charts
            updateCharts(data);
        }

        // Update KPIs
        function updateKPIs(kpis) {
            updateKPI('new-sub-stores', kpis?.newSubStores);
            updateKPI('active-sub-stores', kpis?.activeSubStores);
            updateKPI('total-clients', kpis?.totalClients);
            updateKPI('estimated-revenue', kpis?.estimatedRevenue, '€');
        }

        function updateKPI(prefix, data, suffix = '') {
            const valueEl = document.getElementById(`${prefix}-value`);
            const changeEl = document.getElementById(`${prefix}-change`);
            
            if (valueEl && data) {
                valueEl.textContent = data.current.toLocaleString() + suffix;
            }
            
            if (changeEl && data) {
                const change = data.change;
                const changeText = change > 0 ? `+${change}%` : `${change}%`;
                changeEl.innerHTML = `<span>📊</span><span>${changeText}</span>`;
                
                changeEl.className = 'kpi-change ' + 
                    (change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral');
            }
        }

        // Update sub-stores table
        function updateSubStoresTable(stores) {
            const tbody = document.getElementById('subStoresTableBody');
            tbody.innerHTML = '';
            
            stores.forEach(store => {
                const row = tbody.insertRow();
                
                // Rang
                const rankBadge = `<span class="rank-badge rank-${store.rank <= 3 ? store.rank : 'other'}">${store.rank}</span>`;
                row.insertCell(0).innerHTML = rankBadge;
                
                // Nom
                row.insertCell(1).textContent = store.name;
                
                // Catégorie
                row.insertCell(2).textContent = store.category;
                
                // Transactions
                row.insertCell(3).textContent = store.transactions.toLocaleString();
                
                // Clients
                row.insertCell(4).textContent = store.customers.toLocaleString();
                
                // Localisation
                row.insertCell(5).textContent = store.location;
                
                // Croissance
                const growthClass = store.growth > 0 ? 'positive' : store.growth < 0 ? 'negative' : 'neutral';
                const growthText = store.growth > 0 ? `+${store.growth}%` : `${store.growth}%`;
                row.insertCell(6).innerHTML = `<span class="kpi-change ${growthClass}">${growthText}</span>`;
            });
        }

        // Update categories table
        function updateCategoriesTable(categories) {
            const tbody = document.getElementById('categoriesTableBody');
            tbody.innerHTML = '';
            
            categories.forEach(category => {
                const row = tbody.insertRow();
                
                row.insertCell(0).textContent = category.category;
                row.insertCell(1).textContent = category.stores.toLocaleString();
                row.insertCell(2).textContent = category.transactions.toLocaleString();
                row.insertCell(3).textContent = `${category.percentage}%`;
            });
        }

        // Update charts
        function updateCharts(data) {
            // Implement chart updates based on data
            console.log('Updating charts with data:', data);
        }

        // Handle sub-store change
        function handleSubStoreChange() {
            loadDashboardData();
        }

        // Export sub-store data
        function exportSubStoreData() {
            if (!dashboardData) {
                showNotification('Aucune donnée à exporter', 'error');
                return;
            }
            
            console.log('Exporting sub-store data...');
            showNotification('Export en cours...', 'info');
        }

        // Set smart comparison
        function setSmartComparison() {
            const startDate = new Date(document.getElementById('start-date').value);
            const endDate = new Date(document.getElementById('end-date').value);
            
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const compEndDate = new Date(startDate);
            compEndDate.setDate(startDate.getDate() - 1);
            
            const compStartDate = new Date(compEndDate);
            compStartDate.setDate(compEndDate.getDate() - diffDays);
            
            document.getElementById('comparison-start-date').value = compStartDate.toISOString().split('T')[0];
            document.getElementById('comparison-end-date').value = compEndDate.toISOString().split('T')[0];
            
            updateDateRange();
            loadDashboardData();
            
            showNotification('Période de comparaison intelligente appliquée', 'success');
        }

        // Show keyboard shortcuts help
        function showKeyboardShortcutsHelp() {
            showNotification('Raccourcis clavier: R=Actualiser, 1-4=Onglets, E=Export, H=Aide', 'info');
        }

        // Simple loading management
        function showLoading() {
            const refreshBtn = document.getElementById('refresh-btn');
            const refreshText = document.getElementById('refresh-text');
            const refreshLoading = document.getElementById('refresh-loading');
            
            if (refreshBtn) refreshBtn.disabled = true;
            if (refreshText) refreshText.style.display = 'none';
            if (refreshLoading) refreshLoading.style.display = 'inline';
            
            showSimpleOverlay();
        }

        function showSimpleOverlay() {
            const existingOverlay = document.getElementById('loading-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }

            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <div style="margin-top: 15px; font-weight: 500;">Chargement des données sub-stores...</div>
                </div>
            `;

            document.body.appendChild(overlay);
        }
        
        function hideLoading() {
            const refreshBtn = document.getElementById('refresh-btn');
            const refreshText = document.getElementById('refresh-text');
            const refreshLoading = document.getElementById('refresh-loading');
            
            if (refreshBtn) refreshBtn.disabled = false;
            if (refreshText) refreshText.style.display = 'inline';
            if (refreshLoading) refreshLoading.style.display = 'none';
            
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 4000) {
            const existing = document.querySelectorAll(`.notification.${type}`);
            existing.forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px; position: relative;">
                    <span style="font-size: 16px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
                    <span style="flex: 1; font-weight: 500;">${message}</span>
                    <button onclick="closeNotification(this)" style="background: none; border: none; font-size: 18px; cursor: pointer; color: inherit; opacity: 0.7;">×</button>
                </div>
            `;
            
            notification.style.position = 'fixed';
            notification.style.zIndex = '10000';
            notification.style.marginBottom = '10px';
            
            const existingNotifications = document.querySelectorAll('.notification');
            const offset = existingNotifications.length * 80;
            notification.style.top = (20 + offset) + 'px';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    notification.style.animation = 'slideIn 0.3s ease reverse';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (document.body.contains(notification)) {
                            document.body.removeChild(notification);
                            repositionNotifications();
                        }
                    }, 300);
                }
            }, duration);
        }
        
        function closeNotification(button) {
            const notification = button.closest('.notification');
            if (notification) {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                        repositionNotifications();
                    }
                }, 300);
            }
        }
        
        function repositionNotifications() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach((notification, index) => {
                notification.style.top = (20 + index * 80) + 'px';
            });
        }
    </script>
</body>
</html>
