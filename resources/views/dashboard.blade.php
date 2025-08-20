<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ooredoo Privileges - Comprehensive Performance Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .header-left {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .logo {
      width: 120px;
      height: auto;
    }
    
    .header h1 {
      font-size: 24px;
      font-weight: 700;
      margin: 0;
      color: var(--brand-dark);
    }
    
    .header-right {
      font-size: 14px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 16px;
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
    
    .admin-btn {
      background: var(--brand-red);
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    
    .admin-btn:hover {
      background: #c20510;
      text-decoration: none;
    }
    
    .logout-btn {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
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
    
    /* Tab Content */
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    /* Filters */
    .filters {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    
    .filter-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 16px;
    }
    
    .filter-label {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 8px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .filter-value {
      font-weight: 600;
      font-size: 14px;
    }
    
    /* Grid Layout */
    .grid {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 20px;
      margin-bottom: 24px;
    }
    
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    /* KPI Cards */
    .kpi-card {
      grid-column: span 3;
      text-align: center;
    }
    
    .kpi-title {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 8px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .kpi-value {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--brand-dark);
    }
    
    .kpi-delta {
      font-size: 12px;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }
    
    .delta-positive { color: var(--success); }
    .delta-negative { color: var(--danger); }
    .delta-neutral { color: var(--muted); }
    
    /* Chart Cards */
    .chart-card {
      grid-column: span 6;
      min-height: 350px;
    }
    
    .chart-card.full-width {
      grid-column: span 12;
    }
    
    .chart-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 16px;
      color: var(--brand-dark);
    }
    
    .chart-container {
      height: 300px;
      position: relative;
    }
    
    /* Table */
    .table-card {
      grid-column: span 12;
    }
    
    .table-container {
      overflow-x: auto;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    
    th, td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    
    th {
      background: #f8fafc;
      font-weight: 600;
      color: var(--brand-dark);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    tr:hover {
      background: #f8fafc;
    }
    
    /* Badges */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    
    /* Progress Bar */
    .progress-bar {
      width: 100%;
      height: 8px;
      background: #e2e8f0;
      border-radius: 4px;
      overflow: hidden;
      margin-top: 8px;
    }
    
    .progress-fill {
      height: 100%;
      background: var(--brand-red);
      transition: width 0.3s ease;
    }
    
    /* Insights Section */
    .insights-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    
    .insight-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
    }
    
    .insight-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .insight-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .insight-list li {
      padding: 8px 0;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
    }
    
    .insight-list li:last-child {
      border-bottom: none;
    }
    
    /* Loading State */
    .loading {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 200px;
      color: var(--muted);
    }
    
    .spinner {
      width: 20px;
      height: 20px;
      border: 2px solid var(--border);
      border-top: 2px solid var(--brand-red);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 8px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    
    /* Date Input Styles */
    .date-input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      font-size: 14px;
      font-family: inherit;
      background: white;
      transition: border-color 0.2s;
    }
    
    .date-input:focus {
      outline: none;
      border-color: var(--brand-red);
      box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
    }
    
    .btn-refresh {
      width: 100%;
      padding: 8px 12px;
      background: var(--brand-red);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .btn-refresh:hover {
      background: #c41e3a;
    }
    
    .btn-refresh:active {
      transform: translateY(1px);
    }
    
    .btn-refresh:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }
    
    /* Loading and notification styles */
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
    
    /* Operator selector styling */
    .operator-select {
      width: 100%;
      padding: 8px 12px;
      border: 2px solid var(--border);
      border-radius: 8px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .operator-select:hover {
      border-color: var(--brand-red);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .operator-select:focus {
      outline: none;
      border-color: var(--brand-red);
      box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
    }
    
    .operator-select option {
      background: white;
      color: var(--brand-dark);
      padding: 8px;
    }
    
    /* Enhanced insights styling */
    .insight-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .insight-item:last-child {
      border-bottom: none;
    }
    
    .insight-icon {
      font-size: 16px;
      margin-top: 2px;
      flex-shrink: 0;
    }
    
    .insight-text {
      flex: 1;
      line-height: 1.4;
    }
    
    .high-priority {
      background: rgba(239, 68, 68, 0.1);
      padding: 8px;
      border-radius: 6px;
      border-left: 3px solid #ef4444;
    }
    
    .medium-priority {
      background: rgba(245, 158, 11, 0.1);
      padding: 8px;
      border-radius: 6px;
      border-left: 3px solid #f59e0b;
    }
    
    .action-item {
      background: rgba(59, 130, 246, 0.1);
      padding: 8px;
      border-radius: 6px;
      border-left: 3px solid #3b82f6;
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Enhanced Filters Bar */
    .enhanced-filters-bar {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 32px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .date-selection-section {
      margin-bottom: 24px;
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 18px;
      font-weight: 600;
      color: var(--brand-dark);
      margin-bottom: 20px;
    }

    .section-icon {
      font-size: 20px;
    }

    .date-periods {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    .date-period {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
    }

    .period-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
    }

    .period-icon {
      font-size: 16px;
    }

    .period-label {
      font-weight: 600;
      color: var(--brand-dark);
    }

    .date-inputs {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .date-input-group {
      flex: 1;
    }

    .date-input-group label {
      display: block;
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
      font-weight: 500;
    }

    .enhanced-date-input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: white;
      transition: all 0.2s;
    }

    .enhanced-date-input:focus {
      outline: none;
      border-color: var(--brand-red);
      box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
    }

    .date-separator {
      color: var(--muted);
      font-weight: 500;
      margin-top: 20px;
    }

    .period-display {
      font-size: 13px;
      color: var(--muted);
      font-style: italic;
      text-align: center;
    }

    .controls-section {
      display: flex;
      align-items: flex-end;
      gap: 24px;
      flex-wrap: wrap;
    }

    .control-group {
      min-width: 200px;
    }

    .control-label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 600;
      color: var(--brand-dark);
      margin-bottom: 8px;
    }

    .control-icon {
      font-size: 16px;
    }

    .enhanced-select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: white;
      cursor: pointer;
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

    /* Merchants Section Styles */
    .merchants-kpis-row {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 20px;
      margin-bottom: 32px;
    }

    .merchants-kpi {
      grid-column: span 1;
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px;
      min-height: 120px;
    }

    .kpi-icon {
      font-size: 32px;
      opacity: 0.8;
    }

    .kpi-content {
      flex: 1;
    }

    .merchants-charts-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 32px;
    }

    .merchants-chart {
      grid-column: span 1;
    }

    .chart-header {
      border-bottom: 1px solid var(--border);
      padding-bottom: 16px;
      margin-bottom: 20px;
    }

    .chart-subtitle {
      font-size: 13px;
      color: var(--muted);
      margin-top: 4px;
    }

    .merchants-table-section {
      margin-bottom: 32px;
    }

    .merchants-table {
      width: 100%;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 20px;
    }

    .table-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--brand-dark);
    }

    .table-actions {
      display: flex;
      gap: 12px;
    }

    .enhanced-table {
      width: 100%;
      border-collapse: collapse;
    }

    .enhanced-table th {
      background: #f8fafc;
      font-weight: 600;
      color: var(--brand-dark);
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 16px;
      border-bottom: 2px solid var(--border);
    }

    .enhanced-table td {
      padding: 16px;
      border-bottom: 1px solid #f1f5f9;
    }

    .enhanced-table tr:hover {
      background: #f8fafc;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .merchants-kpis-row {
        grid-template-columns: repeat(3, 1fr);
      }
      
      .merchants-kpi:nth-child(4),
      .merchants-kpi:nth-child(5) {
        grid-column: span 1;
      }
    }

    @media (max-width: 768px) {
      .kpi-card { grid-column: span 6; }
      .chart-card { grid-column: span 12; }
      .nav-tabs { flex-direction: column; }
      .nav-tab { text-align: left; }
      
      .merchants-kpis-row {
        grid-template-columns: 1fr;
      }
      
      .merchants-charts-row {
        grid-template-columns: 1fr;
      }
      
      .date-periods {
        grid-template-columns: 1fr;
      }
      
      .controls-section {
        flex-direction: column;
        align-items: stretch;
      }
      
      .action-buttons {
        margin-left: 0;
        justify-content: center;
      }
    }
    
    @media (max-width: 480px) {
      .kpi-card { grid-column: span 12; }
      .container { padding: 12px; }
      
      .enhanced-filters-bar {
        padding: 16px;
      }
      
      .date-inputs {
        flex-direction: column;
        gap: 8px;
      }
      
      .date-separator {
        text-align: center;
        margin: 8px 0;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="header-left">
        <img src="{{ asset('images/ooredoo-logo.png') }}" alt="Ooredoo" class="logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <svg class="logo" viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
          <rect width="200" height="60" fill="#E30613"/>
          <text x="20" y="35" fill="white" font-family="Arial, sans-serif" font-size="24" font-weight="bold">ooredoo</text>
        </svg>
        <h1>Ooredoo Privileges - Performance Dashboard</h1>
      </div>
      <div class="header-right">
        <span>📊</span>
        <span>{{ Auth::user()->isSuperAdmin() ? 'Vue Globale' : 'Vue ' . (Auth::user()->getPrimaryOperatorName() ?? 'Opérateur') }}</span>
        
        <div class="user-menu">
          <div class="user-info">
            <div class="user-name">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
            <div class="user-role">{{ Auth::user()->role->display_name ?? 'Aucun rôle' }}</div>
          </div>
          
          @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()))
            <a href="{{ route('admin.users.index') }}" class="admin-btn">Utilisateurs</a>
            <a href="{{ route('admin.invitations.index') }}" class="admin-btn">Invitations</a>
          @endif
          
          <form action="{{ route('auth.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="logout-btn" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
              Déconnexion
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
      <button class="nav-tab active" onclick="showTab('overview')">Overview</button>
      <button class="nav-tab" onclick="showTab('subscriptions')">Subscriptions</button>
      <button class="nav-tab" onclick="showTab('transactions')">Transactions</button>
      <button class="nav-tab" onclick="showTab('merchants')">Merchants</button>
      <button class="nav-tab" onclick="showTab('comparison')">Comparison</button>
      <button class="nav-tab" onclick="showTab('insights')">Insights</button>
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
            <span class="control-icon">📱</span>
            <span>Opérateur</span>
          </div>
          <select id="operator-select" class="enhanced-select" onchange="handleOperatorChange()">
            <!-- Les opérateurs seront chargés dynamiquement -->
          </select>
          <div id="operator-info" class="control-info">
            Chargement des opérateurs...
          </div>
        </div>

        <div class="control-group">
          <div class="control-label">
            <span class="control-icon">⚡</span>
            <span>Status</span>
          </div>
          <div class="status-badge active">Launch Phase</div>
        </div>

        <div class="action-buttons">
          <button class="btn-primary enhanced-btn" onclick="loadDashboardData()" id="refresh-btn">
            <span id="refresh-text">📊 Actualiser</span>
            <span id="refresh-loading" style="display: none;">⏳ Chargement...</span>
          </button>
          
          <button class="btn-secondary enhanced-btn" onclick="setSmartComparison()">
            🔄 Comparaison Auto
          </button>
          
          <button class="btn-accent enhanced-btn" onclick="toggleDatePickerMode()">
            📆 Raccourcis
          </button>
        </div>
      </div>
    </div>

    <!-- Tab 1: Overview -->
    <div id="overview" class="tab-content active">
      <div class="grid">
        <!-- Key KPIs -->
        <div class="card kpi-card">
          <div class="kpi-title">Activated Subscriptions</div>
          <div class="kpi-value" id="activatedSubscriptions">Loading...</div>
          <div class="kpi-delta" id="activatedSubscriptionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Active Subscriptions</div>
          <div class="kpi-value" id="activeSubscriptions">Loading...</div>
          <div class="kpi-delta" id="activeSubscriptionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Total Transactions</div>
          <div class="kpi-value" id="totalTransactions">Loading...</div>
          <div class="kpi-delta" id="totalTransactionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Conversion Rate</div>
          <div class="kpi-value" id="conversionRate">Loading...</div>
          <div class="kpi-delta delta-negative">vs 30% benchmark</div>
        </div>

        <!-- Overview Chart -->
        <div class="card chart-card full-width">
          <div class="chart-title">Performance Overview - Period Comparison</div>
          <div class="chart-container">
            <canvas id="overviewChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: Detailed Subscription Analysis -->
    <div id="subscriptions" class="tab-content">
      <div class="grid">
        <!-- Subscription KPIs -->
        <div class="card kpi-card">
          <div class="kpi-title">Activated Subscriptions</div>
          <div class="kpi-value" id="sub-activatedSubscriptions">Loading...</div>
          <div class="kpi-delta" id="sub-activatedSubscriptionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Active Subscriptions</div>
          <div class="kpi-value" id="sub-activeSubscriptions">Loading...</div>
          <div class="kpi-delta" id="sub-activeSubscriptionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Deactivated</div>
          <div class="kpi-value" id="sub-deactivatedSubscriptions">Loading...</div>
          <div class="kpi-delta" id="sub-deactivatedSubscriptionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Retention Rate</div>
          <div class="kpi-value" id="sub-retentionRate">Loading...</div>
          <div class="kpi-delta" id="sub-retentionRateDelta">Loading...</div>
        </div>

        <!-- Subscription Trends -->
        <div class="card chart-card">
          <div class="chart-title">Daily Activated Subscriptions</div>
          <div class="chart-container">
            <canvas id="subscriptionTrendChart"></canvas>
          </div>
        </div>

        <div class="card chart-card">
          <div class="chart-title">Retention Rate Trend</div>
          <div class="chart-container">
            <canvas id="retentionChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 3: Detailed Transaction Analysis -->
    <div id="transactions" class="tab-content">
      <div class="grid">
        <!-- Transaction KPIs -->
        <div class="card kpi-card">
          <div class="kpi-title">Total Transactions</div>
          <div class="kpi-value" id="trans-totalTransactions">Loading...</div>
          <div class="kpi-delta" id="trans-totalTransactionsDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Transacting Users</div>
          <div class="kpi-value" id="trans-transactingUsers">Loading...</div>
          <div class="kpi-delta" id="trans-transactingUsersDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Transactions/User</div>
          <div class="kpi-value" id="trans-transactionsPerUser">Loading...</div>
          <div class="kpi-delta" id="trans-transactionsPerUserDelta">Loading...</div>
        </div>
        <div class="card kpi-card">
          <div class="kpi-title">Conversion Rate</div>
          <div class="kpi-value" id="trans-conversionRate">Loading...</div>
          <div class="progress-bar">
            <div class="progress-fill" id="trans-conversionProgress" style="width: 0%"></div>
          </div>
          <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">Target: 30%</div>
        </div>

        <!-- Transaction Charts -->
        <div class="card chart-card">
          <div class="chart-title">Daily Transaction Volume</div>
          <div class="chart-container">
            <canvas id="transactionVolumeChart"></canvas>
          </div>
        </div>

        <div class="card chart-card">
          <div class="chart-title">Transacting Users Trend</div>
          <div class="chart-container">
            <canvas id="transactingUsersChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 4: Merchant Analysis -->
    <div id="merchants" class="tab-content">
      <!-- KPIs Section - 5 cartes en ligne -->
      <div class="merchants-kpis-row">
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🏪</div>
          <div class="kpi-content">
            <div class="kpi-title">Total Merchants</div>
            <div class="kpi-value" id="merch-totalActivePartnersDB">Loading...</div>
            <div class="kpi-delta" id="merch-totalActivePartnersDBDelta">→ 0.0%</div>
          </div>
        </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">📈</div>
          <div class="kpi-content">
            <div class="kpi-title">Active Merchants</div>
            <div class="kpi-value" id="merch-activeMerchants">Loading...</div>
            <div class="kpi-delta" id="merch-activeMerchantsDelta">Loading...</div>
          </div>
        </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">💳</div>
          <div class="kpi-content">
            <div class="kpi-title">Transactions/Merchant</div>
            <div class="kpi-value" id="merch-transactionsPerMerchant">Loading...</div>
            <div class="kpi-delta" id="merch-transactionsPerMerchantDelta">Loading...</div>
          </div>
        </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🏆</div>
          <div class="kpi-content">
            <div class="kpi-title">Top Merchant</div>
            <div class="kpi-value" id="merch-topMerchantShare">Loading...</div>
            <div class="kpi-delta" id="merch-topMerchantName">Loading...</div>
          </div>
        </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🎯</div>
          <div class="kpi-content">
            <div class="kpi-title">Diversity</div>
            <div class="kpi-value" id="merch-diversity">Loading...</div>
            <div class="kpi-delta" id="merch-diversityDetail">Loading...</div>
          </div>
        </div>
      </div>

      <!-- Charts Section - 2 graphiques côte à côte -->
      <div class="merchants-charts-row">
        <div class="card chart-card merchants-chart">
          <div class="chart-header">
            <div class="chart-title">🏪 Top Merchants by Volume</div>
            <div class="chart-subtitle">Transactions par marchand</div>
          </div>
          <div class="chart-container">
            <canvas id="topMerchantsChart"></canvas>
          </div>
        </div>

        <div class="card chart-card merchants-chart">
          <div class="chart-header">
            <div class="chart-title">📊 Distribution by Category</div>
            <div class="chart-subtitle">Répartition par catégorie</div>
          </div>
          <div class="chart-container">
            <canvas id="categoryChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Table Section - Tableau pleine largeur -->
      <div class="merchants-table-section">
        <div class="card table-card merchants-table">
          <div class="table-header">
            <div class="table-title">📋 Performance Détaillée des Marchands</div>
            <div class="table-actions">
              <button class="btn-secondary" onclick="exportMerchantsData()">📥 Exporter</button>
            </div>
          </div>
          <div class="table-container">
            <table class="enhanced-table">
              <thead>
                <tr>
                  <th>Merchant</th>
                  <th>Category</th>
                  <th>Current</th>
                  <th>Previous</th>
                  <th>Change</th>
                  <th>Market Share</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="merchantsTableBody">
                <tr>
                  <td colspan="7" class="loading">
                    <div class="spinner"></div>
                    Chargement des données marchands...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 5: Comparison -->
    <div id="comparison" class="tab-content">
      <div class="grid">
        <!-- Comparison Table -->
        <div class="card table-card">
          <div class="chart-title">Period-over-Period Comparison</div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Metric</th>
                  <th>Current Period</th>
                  <th>Previous Period</th>
                  <th>Absolute Change</th>
                  <th>% Change</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="comparisonTableBody">
                <tr>
                  <td colspan="6" class="loading">
                    <div class="spinner"></div>
                    Loading comparison data...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Comparison Chart -->
        <div class="card chart-card full-width">
          <div class="chart-title">Key Metrics Comparison</div>
          <div class="chart-container">
            <canvas id="comparisonChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 6: Insights -->
    <div id="insights" class="tab-content">
      <div class="insights-grid">
        <!-- Positive Insights -->
        <div class="insight-card">
          <div class="insight-title">
            <span style="color: var(--success);">✅</span>
            Positive Insights
          </div>
          <ul class="insight-list" id="positiveInsights">
            <li class="loading">
              <div class="spinner"></div>
              Loading insights...
            </li>
          </ul>
        </div>

        <!-- Challenges -->
        <div class="insight-card">
          <div class="insight-title">
            <span style="color: var(--warning);">⚠️</span>
            Challenges & Areas for Improvement
          </div>
          <ul class="insight-list" id="challenges">
            <li class="loading">
              <div class="spinner"></div>
              Loading challenges...
            </li>
          </ul>
        </div>

        <!-- Strategic Recommendations -->
        <div class="insight-card">
          <div class="insight-title">
            <span style="color: var(--accent);">🎯</span>
            Strategic Recommendations
          </div>
          <ul class="insight-list" id="recommendations">
            <li class="loading">
              <div class="spinner"></div>
              Loading recommendations...
            </li>
          </ul>
        </div>

        <!-- Next Steps -->
        <div class="insight-card">
          <div class="insight-title">
            <span style="color: var(--brand-red);">🚀</span>
            Next Steps
          </div>
          <ul class="insight-list" id="nextSteps">
            <li class="loading">
              <div class="spinner"></div>
              Loading next steps...
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Global variables for charts and data
    let dashboardData = null;
    let charts = {};

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      setDefaultDates();
      updateDateRange();
      initializeDashboard();
      
      // Auto-refresh every 5 minutes
      setInterval(loadDashboardData, 5 * 60 * 1000);
    });

    // Initialize dashboard in correct order
    async function initializeDashboard() {
      try {
        // 1. Load operators first
        await loadOperators();
        // 2. Then load dashboard data with the correct operator
        await loadDashboardData();
      } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error);
        showNotification('Erreur lors de l\'initialisation du dashboard', 'error');
      }
    }

    // Tab switching functionality
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

    // Load dashboard data
    async function loadDashboardData() {
      try {
        showLoading();
        
        // Get date values for both periods
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const comparisonStartDate = document.getElementById('comparison-start-date').value;
        const comparisonEndDate = document.getElementById('comparison-end-date').value;
        const selectedOperator = document.getElementById('operator-select').value;
        
        // Build API URL with date parameters
        let apiUrl = '/api/dashboard/data';
        const params = new URLSearchParams();
        
        if (startDate && endDate) {
          params.append('start_date', startDate);
          params.append('end_date', endDate);
        }
        
        if (comparisonStartDate && comparisonEndDate) {
          params.append('comparison_start_date', comparisonStartDate);
          params.append('comparison_end_date', comparisonEndDate);
        }
        
        if (selectedOperator) {
          params.append('operator', selectedOperator);
        }
        
        if (params.toString()) {
          apiUrl += '?' + params.toString();
        }
        
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Dashboard data loaded:', data);
        
        updateDashboard(data);
        hideLoading();
        
        // Show appropriate notification based on operator
        const operatorLabel = selectedOperator === 'ALL' ? 'globales' : selectedOperator;
        showNotification(`✅ Données ${operatorLabel} mises à jour avec succès!`, 'success');
      } catch (error) {
        console.error('Error loading dashboard data:', error);
        hideLoading();
        showNotification('Erreur de connexion: ' + error.message, 'error');
      }
    }
    
    // Enhanced loading state management
    function showLoadingState() {
      // Update button state
      const refreshBtn = document.getElementById('refresh-btn');
      const refreshText = document.getElementById('refresh-text');
      const refreshLoading = document.getElementById('refresh-loading');
      
      if (refreshBtn) refreshBtn.disabled = true;
      if (refreshText) refreshText.style.display = 'none';
      if (refreshLoading) refreshLoading.style.display = 'inline';
      
      // Show main loading indicator
      showLoading();
    }
    
    function hideLoadingState() {
      // Reset button state
      const refreshBtn = document.getElementById('refresh-btn');
      const refreshText = document.getElementById('refresh-text');
      const refreshLoading = document.getElementById('refresh-loading');
      
      if (refreshBtn) refreshBtn.disabled = false;
      if (refreshText) refreshText.style.display = 'inline';
      if (refreshLoading) refreshLoading.style.display = 'none';
      
      // Hide main loading indicator
      hideLoading();
    }
    
    // Enhanced notification system
    function showNotification(message, type = 'info') {
      // Remove existing notifications
      const existing = document.querySelectorAll('.notification');
      existing.forEach(n => n.remove());
      
      // Create new notification
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 8px;">
          <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
          <span>${message}</span>
        </div>
      `;
      
      document.body.appendChild(notification);
      
      // Auto-remove after 4 seconds
      setTimeout(() => {
        if (document.body.contains(notification)) {
          notification.style.animation = 'slideIn 0.3s ease reverse';
          setTimeout(() => {
            if (document.body.contains(notification)) {
              document.body.removeChild(notification);
            }
          }, 300);
        }
      }, 4000);
    }
    
    // Load available operators
    async function loadOperators() {
      try {
        const response = await fetch('/api/operators');
        const data = await response.json();
        
        if (data.operators && data.operators.length > 0) {
          const select = document.getElementById('operator-select');
          const operatorInfo = document.getElementById('operator-info');
          
          // Clear existing options
          select.innerHTML = '';
          
          // Add operators to select
          data.operators.forEach(operator => {
            const option = document.createElement('option');
            option.value = operator.value;
            option.textContent = `📱 ${operator.label}`;
            // Use the default_operator from API response
            if (operator.value === data.default_operator) {
              option.selected = true;
            }
            select.appendChild(option);
          });
          
          // Update info text based on user role
          if (data.user_role === 'super_admin') {
            operatorInfo.textContent = `Vue globale disponible (${data.operators.length} opérateurs)`;
          } else {
            operatorInfo.textContent = `${data.operators.length} opérateur(s) assigné(s)`;
          }
          
          console.log('Opérateurs chargés:', data.operators.length, 'Défaut:', data.default_operator);
          
        } else {
          console.warn('Aucun opérateur disponible');
          document.getElementById('operator-info').textContent = 'Aucun opérateur disponible';
        }
        
      } catch (error) {
        console.error('Erreur lors du chargement des opérateurs:', error);
        document.getElementById('operator-info').textContent = 'Erreur de chargement';
        throw error; // Re-throw to handle in initialization
      }
    }
    
    // Handle operator change
    function handleOperatorChange() {
      const selectedOperator = document.getElementById('operator-select').value;
      const operatorInfo = document.getElementById('operator-info');
      
      if (selectedOperator === 'ALL') {
        operatorInfo.textContent = 'Vue globale - Tous les opérateurs';
      } else {
        operatorInfo.textContent = `Données limitées à l'opérateur ${selectedOperator}`;
      }
      
      // Reload dashboard data with new operator
      loadDashboardData();
    }

    // Set default dates (last 14 days for primary, previous 14 for comparison)
    function setDefaultDates() {
      const endDate = new Date();
      const startDate = new Date();
      startDate.setDate(endDate.getDate() - 13);
      
      // Comparison period (14 days before the primary period)
      const comparisonEndDate = new Date(startDate);
      comparisonEndDate.setDate(comparisonEndDate.getDate() - 1);
      const comparisonStartDate = new Date(comparisonEndDate);
      comparisonStartDate.setDate(comparisonStartDate.getDate() - 13);

      document.getElementById('start-date').value = startDate.toISOString().split('T')[0];
      document.getElementById('end-date').value = endDate.toISOString().split('T')[0];
      document.getElementById('comparison-start-date').value = comparisonStartDate.toISOString().split('T')[0];
      document.getElementById('comparison-end-date').value = comparisonEndDate.toISOString().split('T')[0];
    }
    
    // Set smart comparison period (same duration as primary, just before)
    function setSmartComparison() {
      const startDate = new Date(document.getElementById('start-date').value);
      const endDate = new Date(document.getElementById('end-date').value);
      
      if (startDate && endDate) {
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        
        const comparisonEndDate = new Date(startDate);
        comparisonEndDate.setDate(comparisonEndDate.getDate() - 1);
        const comparisonStartDate = new Date(comparisonEndDate);
        comparisonStartDate.setDate(comparisonStartDate.getDate() - duration);
        
        document.getElementById('comparison-start-date').value = comparisonStartDate.toISOString().split('T')[0];
        document.getElementById('comparison-end-date').value = comparisonEndDate.toISOString().split('T')[0];
        
        updateDateRange();
        loadDashboardData();
      }
    }

    // Update date range display
    function updateDateRange() {
      const startDate = document.getElementById('start-date').value;
      const endDate = document.getElementById('end-date').value;
      
      if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const primaryPeriod = `${start.toLocaleDateString('fr-FR')} - ${end.toLocaleDateString('fr-FR')}`;
        document.getElementById('primaryPeriod').textContent = primaryPeriod;
      }
    }

    // Show loading state
    function showLoading() {
      // Add loading indicators to KPI cards
      const kpiValues = document.querySelectorAll('.kpi-value');
      kpiValues.forEach(el => {
        el.innerHTML = '<div class="spinner"></div>';
      });
    }

    // Hide loading state
    function hideLoading() {
      // Loading will be hidden when data is updated
    }

    // Show error message
    function showError(message) {
      const kpiValues = document.querySelectorAll('.kpi-value');
      kpiValues.forEach(el => {
        el.textContent = 'Erreur';
      });
      
      // You could also show a toast notification here
      alert(message);
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      setDefaultDates();
      updateDateRange();
      loadDashboardData();
    });

    // Load fallback data (static data for demo)
    function loadFallbackData() {
      dashboardData = {
        periods: {
          primary: "August 1-14, 2025",
          comparison: "July 18-31, 2025"
        },
        kpis: {
          activatedSubscriptions: { current: 12321, previous: 2129, change: 478.8 },
          activeSubscriptions: { current: 11586, previous: 1800, change: 543.7 },
          deactivatedSubscriptions: { current: 735, previous: 329, change: 123.4 },
          totalTransactions: { current: 32, previous: 33, change: -3.0 },
          transactingUsers: { current: 28, previous: 27, change: 3.7 },
          transactionsPerUser: { current: 1.1, previous: 1.2, change: -8.3 },
          activeMerchants: { current: 16, previous: 12, change: 33.3 },
          transactionsPerMerchant: { current: 2.0, previous: 3.0, change: -33.3 },
          conversionRate: { current: 0.24, previous: 0.18, change: 33.3 }
        },
        merchants: [
          { name: "MABROUK", current: 12, previous: 4, share: 37.5 },
          { name: "DR PARA", current: 3, previous: 4, share: 9.4 },
          { name: "PURE JUICE", current: 2, previous: 1, share: 6.3 },
          { name: "Others", current: 15, previous: 24, share: 46.8 }
        ],
        insights: {
          positive: [
            "Exceptional subscription growth of +478.8% demonstrates strong market demand",
            "High retention rate of 94.0% indicates customer satisfaction with the service",
            "Merchant network expansion with 33.3% more active partners",
            "Improved conversion rate compared to previous period (+33.3%)"
          ],
          challenges: [
            "Transaction conversion rate (0.24%) significantly below Club Privilèges benchmark (30%)",
            "Decline in transactions per user (-8.3%) suggests engagement challenges",
            "Lower transactions per merchant (-33.3%) indicates distribution inefficiency"
          ],
          recommendations: [
            "Implement targeted customer education campaigns about service benefits",
            "Develop merchant training programs to improve transaction facilitation",
            "Create incentive programs to encourage first-time transactions",
            "Analyze user journey to identify conversion barriers"
          ],
          nextSteps: [
            "Launch comprehensive user onboarding program within 2 weeks",
            "Establish merchant support team for transaction optimization",
            "Implement A/B testing for different engagement strategies",
            "Set up weekly monitoring of conversion metrics"
          ]
        }
      };
      
      updateDashboard(dashboardData);
    }

    // Update dashboard with data
    function updateDashboard(data) {
      // Update periods
      const primaryPeriodEl = document.getElementById('primaryPeriod');
      if (primaryPeriodEl) {
        primaryPeriodEl.textContent = data.periods.primary;
      }
      
      const comparisonPeriodEl = document.getElementById('comparisonPeriod');
      if (comparisonPeriodEl) {
        comparisonPeriodEl.textContent = data.periods.comparison;
      }
      
      // Update KPIs
      updateKPIs(data.kpis);
      
      // Update charts
      updateCharts(data);
      
      // Update tables
      updateTables(data);
      
      // Update merchant KPI info with real merchants data
      updateMerchantKPIs(data.merchants, data.kpis);
      
      // Update insights
      updateInsights(data.insights);
    }

    // Update KPI values
    function updateKPIs(kpis) {
      // Overview KPIs
      updateKPI('activatedSubscriptions', kpis.activatedSubscriptions);
      updateKPI('activeSubscriptions', kpis.activeSubscriptions);
      updateKPI('totalTransactions', kpis.totalTransactions);
      updateKPI('conversionRate', kpis.conversionRate, '%');
      
      // Subscription KPIs
      updateKPI('sub-activatedSubscriptions', kpis.activatedSubscriptions);
      updateKPI('sub-activeSubscriptions', kpis.activeSubscriptions);
      updateKPI('sub-deactivatedSubscriptions', kpis.deactivatedSubscriptions);
      updateKPI('sub-retentionRate', kpis.retentionRate, '%');
      
      // Transaction KPIs
      updateKPI('trans-totalTransactions', kpis.totalTransactions);
      updateKPI('trans-transactingUsers', kpis.transactingUsers);
      updateKPI('trans-transactionsPerUser', kpis.transactionsPerUser);
      updateKPI('trans-conversionRate', kpis.conversionRate, '%');
      
      // Update conversion progress bar
      const conversionProgress = document.getElementById('trans-conversionProgress');
      if (conversionProgress) {
        conversionProgress.style.width = `${(kpis.conversionRate.current / 30) * 100}%`;
      }
      
      // Merchant KPIs
      updateKPI('merch-totalActivePartnersDB', kpis.totalActivePartnersDB);
      updateKPI('merch-activeMerchants', kpis.activeMerchants);
      updateKPI('merch-transactionsPerMerchant', kpis.transactionsPerMerchant);
      
      // Top merchant info sera mis à jour dans updateTables() avec les nouvelles données
    }

            // Update merchant KPI info with real data
        function updateMerchantKPIs(merchants, kpis) {
        const topMerchantShareEl = document.getElementById('merch-topMerchantShare');
        const topMerchantNameEl = document.getElementById('merch-topMerchantName');
        const diversityEl = document.getElementById('merch-diversity');
        const diversityDetailEl = document.getElementById('merch-diversityDetail');
        
            // Nouvelles métriques
            const totalEverActiveEl = document.getElementById('merch-totalEverActive');
            const totalTransactionsPeriodEl = document.getElementById('merch-totalTransactionsPeriod');
            
            // Mettre à jour les nouvelles données
            if (totalEverActiveEl && kpis.totalMerchantsEverActive) {
                totalEverActiveEl.textContent = `Total ayant déjà transigé: ${kpis.totalMerchantsEverActive}`;
            }
            if (totalTransactionsPeriodEl && kpis.allTransactionsPeriod) {
                totalTransactionsPeriodEl.textContent = `Total transactions période: ${kpis.allTransactionsPeriod}`;
            }
            
            if (merchants && merchants.length > 0) {
                const topMerchant = merchants[0];
                
                if (topMerchantShareEl) {
                    topMerchantShareEl.textContent = `${topMerchant.share}%`;
                }
                if (topMerchantNameEl) {
                    const merchantName = topMerchant.name.length > 20 ? 
                        topMerchant.name.substring(0, 20) + '...' : topMerchant.name;
                    topMerchantNameEl.textContent = merchantName;
                    topMerchantNameEl.title = topMerchant.name; // Tooltip avec le nom complet
                }
                
                // Calcul de la diversité basé sur le nombre de marchands
                const merchantCount = kpis.activeMerchants.current;
                let diversityLevel = 'Faible';
                if (merchantCount >= 15) diversityLevel = 'Élevée';
                else if (merchantCount >= 8) diversityLevel = 'Moyenne';
                
                if (diversityEl) diversityEl.textContent = diversityLevel;
                if (diversityDetailEl) {
                    diversityDetailEl.textContent = `${merchantCount} marchands actifs`;
                }
            } else {
                // Gestion du cas où il n'y a pas de marchands
                if (topMerchantShareEl) topMerchantShareEl.textContent = '0%';
                if (topMerchantNameEl) topMerchantNameEl.textContent = 'Aucun marchand';
                if (diversityEl) diversityEl.textContent = 'Aucune';
                if (diversityDetailEl) diversityDetailEl.textContent = 'Aucun marchand actif';
      }
    }

    // Update individual KPI
    function updateKPI(elementId, data, suffix = '') {
      const valueElement = document.getElementById(elementId);
      const deltaElement = document.getElementById(elementId + 'Delta');
      
      // Normalisation: éviter les erreurs si data est undefined/null
      const safe = (data && typeof data.current !== 'undefined')
        ? data
        : { current: 0, previous: 0, change: 0 };
      
      if (valueElement) {
        valueElement.textContent = formatNumber(safe.current) + suffix;
      }
      
      if (deltaElement) {
        const change = Number.isFinite(safe.change) ? safe.change : 0;
        const isPositive = change > 0;
        const isNegative = change < 0;
        
        deltaElement.textContent = `${isPositive ? '↗' : isNegative ? '↘' : '→'} ${isPositive ? '+' : ''}${change.toFixed(1)}%`;
        deltaElement.className = `kpi-delta ${isPositive ? 'delta-positive' : isNegative ? 'delta-negative' : 'delta-neutral'}`;
      }
    }

    // Format numbers for display
    function formatNumber(num) {
      if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
      }
      return num.toLocaleString();
    }

    // Update charts
    function updateCharts(data) {
      // Overview Chart
      createOverviewChart(data);
      
      // Subscription Charts
      createSubscriptionTrendChart(data);
      createRetentionChart(data);
      
      // Transaction Charts
      createTransactionVolumeChart(data);
      createTransactingUsersChart(data);
      
      // Merchant Charts
      createTopMerchantsChart(data);
      createCategoryChart(data);
      
      // Comparison Chart
      createComparisonChart(data);
    }

    // Create overview chart
    function createOverviewChart(data) {
      const ctx = document.getElementById('overviewChart');
      if (!ctx) return;
      
      if (charts.overview) {
        charts.overview.destroy();
      }
      
      charts.overview = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Activated Subscriptions', 'Active Subscriptions', 'Total Transactions', 'Active Merchants'],
          datasets: [
            {
              label: 'Current Period',
              data: [
                data.kpis.activatedSubscriptions.current,
                data.kpis.activeSubscriptions.current,
                data.kpis.totalTransactions.current,
                data.kpis.activeMerchants.current
              ],
              backgroundColor: '#E30613',
              borderRadius: 4
            },
            {
              label: 'Previous Period',
              data: [
                data.kpis.activatedSubscriptions.previous,
                data.kpis.activeSubscriptions.previous,
                data.kpis.totalTransactions.previous,
                data.kpis.activeMerchants.previous
              ],
              backgroundColor: '#64748b',
              borderRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top'
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    // Create subscription trend chart
    function createSubscriptionTrendChart(data) {
      const ctx = document.getElementById('subscriptionTrendChart');
      if (!ctx) return;
      
      if (charts.subscriptionTrend) {
        charts.subscriptionTrend.destroy();
      }
      
      // Generate sample daily data
      const days = Array.from({length: 14}, (_, i) => `Day ${i + 1}`);
      const dailyData = Array.from({length: 14}, (_, i) => 
        Math.floor(data.kpis.activatedSubscriptions.current / 14 * (0.5 + Math.random()))
      );
      
      charts.subscriptionTrend = new Chart(ctx, {
        type: 'line',
        data: {
          labels: days,
          datasets: [{
            label: 'Daily Activated Subscriptions',
            data: dailyData,
            borderColor: '#E30613',
            backgroundColor: 'rgba(227, 6, 19, 0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    // Create retention chart
    function createRetentionChart(data) {
      const ctx = document.getElementById('retentionChart');
      if (!ctx) return;
      
      if (charts.retention) {
        charts.retention.destroy();
      }
      
      const days = Array.from({length: 14}, (_, i) => `Day ${i + 1}`);
      const retentionData = Array.from({length: 14}, (_, i) => 
        90 + Math.random() * 8
      );
      
      charts.retention = new Chart(ctx, {
        type: 'line',
        data: {
          labels: days,
          datasets: [{
            label: 'Retention Rate (%)',
            data: retentionData,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              min: 85,
              max: 100
            }
          }
        }
      });
    }

    // Create transaction volume chart
    function createTransactionVolumeChart(data) {
      const ctx = document.getElementById('transactionVolumeChart');
      if (!ctx) return;
      
      if (charts.transactionVolume) {
        charts.transactionVolume.destroy();
      }
      
      const days = Array.from({length: 14}, (_, i) => `Day ${i + 1}`);
      const transactionData = Array.from({length: 14}, (_, i) => 
        Math.floor(data.kpis.totalTransactions.current / 14 * (0.3 + Math.random() * 1.4))
      );
      
      charts.transactionVolume = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: days,
          datasets: [{
            label: 'Daily Transactions',
            data: transactionData,
            backgroundColor: '#3b82f6',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    // Create transacting users chart
    function createTransactingUsersChart(data) {
      const ctx = document.getElementById('transactingUsersChart');
      if (!ctx) return;
      
      if (charts.transactingUsers) {
        charts.transactingUsers.destroy();
      }
      
      const days = Array.from({length: 14}, (_, i) => `Day ${i + 1}`);
      const userData = Array.from({length: 14}, (_, i) => 
        Math.floor(data.kpis.transactingUsers.current / 14 * (0.3 + Math.random() * 1.4))
      );
      
      charts.transactingUsers = new Chart(ctx, {
        type: 'line',
        data: {
          labels: days,
          datasets: [{
            label: 'Daily Transacting Users',
            data: userData,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    // Create top merchants chart
    function createTopMerchantsChart(data) {
      const ctx = document.getElementById('topMerchantsChart');
      if (!ctx) return;
      
      if (charts.topMerchants) {
        charts.topMerchants.destroy();
      }
      
      const merchantNames = data.merchants.map(m => m.name);
      const merchantValues = data.merchants.map(m => m.current);
      
      charts.topMerchants = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: merchantNames,
          datasets: [{
            data: merchantValues,
            backgroundColor: [
              '#E30613',
              '#3b82f6',
              '#10b981',
              '#f59e0b'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    }

    // Create category chart (dynamique)
    function createCategoryChart(data) {
      const ctx = document.getElementById('categoryChart');
      if (!ctx) return;
      
      if (charts.category) {
        charts.category.destroy();
      }
      
      const dist = (data.categoryDistribution || []).slice(0, 10);
      const labels = dist.map(d => d.category);
      const values = dist.map(d => d.percentage);
      const colors = ['#E30613','#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4','#f97316','#64748b'];
      
      charts.category = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: labels.length ? labels : ['Aucune catégorie'],
          datasets: [{
            data: values.length ? values : [100],
            backgroundColor: colors.slice(0, Math.max(1, labels.length)),
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const item = dist[ctx.dataIndex];
                  return item ? `${item.category}: ${item.transactions} tx (${item.percentage}%)` : '';
                }
              }
            }
          }
        }
      });
    }

    // Create comparison chart
    function createComparisonChart(data) {
      const ctx = document.getElementById('comparisonChart');
      if (!ctx) return;
      
      if (charts.comparison) {
        charts.comparison.destroy();
      }
      
      charts.comparison = new Chart(ctx, {
        type: 'radar',
        data: {
          labels: ['Subscriptions', 'Transactions', 'Merchants', 'Conversion', 'Retention'],
          datasets: [
            {
              label: 'Current Period',
              data: [100, 20, 80, 1, 94],
              borderColor: '#E30613',
              backgroundColor: 'rgba(227, 6, 19, 0.2)',
              pointBackgroundColor: '#E30613'
            },
            {
              label: 'Previous Period',
              data: [20, 25, 60, 1, 86],
              borderColor: '#64748b',
              backgroundColor: 'rgba(100, 116, 139, 0.2)',
              pointBackgroundColor: '#64748b'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top'
            }
          },
          scales: {
            r: {
              beginAtZero: true,
              max: 100
            }
          }
        }
      });
    }

    // Update tables
    function updateTables(data) {
      updateMerchantsTable(data.merchants);
      updateComparisonTable(data.kpis);
    }

    // Update merchants table with enhanced data
    function updateMerchantsTable(merchants) {
      const tbody = document.getElementById('merchantsTableBody');
      if (!tbody) return;
      
      tbody.innerHTML = merchants.map((merchant, index) => {
        // Calcul du changement plus robuste
        let change = 0;
        let badgeClass = 'badge-info';
        let changeText = 'Nouveau';
        let statusClass = 'badge-success';
        let statusText = 'Actif';
        
        if (merchant.previous > 0) {
          change = ((merchant.current - merchant.previous) / merchant.previous * 100);
          const isPositive = change > 0;
          badgeClass = isPositive ? 'badge-success' : 'badge-danger';
          changeText = `${isPositive ? '+' : ''}${change.toFixed(1)}%`;
        } else if (merchant.current > 0) {
          badgeClass = 'badge-success';
          changeText = 'Nouveau';
        }
        
        // Déterminer le statut basé sur la performance
        if (merchant.current === 0) {
          statusClass = 'badge-danger';
          statusText = 'Inactif';
        } else if (change < -20) {
          statusClass = 'badge-warning';
          statusText = 'En baisse';
        } else if (change > 20) {
          statusClass = 'badge-success';
          statusText = 'En croissance';
        }
        
        // Icône basée sur la position
        const positionIcon = index < 3 ? '🏆' : index < 10 ? '⭐' : '📊';
        
        return `
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">${positionIcon}</span>
                <div>
                  <strong>${merchant.name}</strong>
                  <div style="font-size: 12px; color: #666; margin-top: 2px;">
                    Position: #${index + 1}
                  </div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge badge-info" style="background: #e0f2fe; color: #0277bd;">
                ${merchant.category}
              </span>
            </td>
            <td>
              <strong style="color: var(--brand-red);">${merchant.current.toLocaleString()}</strong>
            </td>
            <td>
              <span style="color: #666;">${merchant.previous.toLocaleString()}</span>
            </td>
            <td>
              <span class="badge ${badgeClass}">${changeText}</span>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 8px;">
                <strong>${merchant.share}%</strong>
                <div style="width: 60px; height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden;">
                  <div style="width: ${Math.min(merchant.share * 2, 100)}%; height: 100%; background: var(--brand-red);"></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge ${statusClass}">${statusText}</span>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Add export function for merchants data
    function exportMerchantsData() {
      if (!dashboardData || !dashboardData.merchants) {
        showNotification('Aucune donnée à exporter', 'warning');
        return;
      }
      
      const csvContent = "data:text/csv;charset=utf-8," + 
        "Merchant,Category,Current,Previous,Change,Market Share,Status\n" +
        dashboardData.merchants.map(merchant => {
          const change = merchant.previous > 0 ? 
            ((merchant.current - merchant.previous) / merchant.previous * 100).toFixed(1) + '%' : 
            'Nouveau';
          return `"${merchant.name}","${merchant.category}",${merchant.current},${merchant.previous},"${change}",${merchant.share}%,"Active"`;
        }).join("\n");
      
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `merchants_data_${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      showNotification('Données exportées avec succès', 'success');
    }

    // Add date picker shortcuts functionality
    function toggleDatePickerMode() {
      const shortcuts = [
        { label: '7 derniers jours', days: 7 },
        { label: '14 derniers jours', days: 14 },
        { label: '30 derniers jours', days: 30 },
        { label: 'Ce mois', type: 'month' },
        { label: 'Mois dernier', type: 'lastMonth' }
      ];
      
      // Create modal for shortcuts
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
        z-index: 10000;
      `;
      
      const content = document.createElement('div');
      content.style.cssText = `
        background: white; padding: 24px; border-radius: 12px; min-width: 300px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      `;
      
      content.innerHTML = `
        <h3 style="margin: 0 0 16px 0; color: var(--brand-dark);">📆 Raccourcis de Date</h3>
        <div id="shortcut-buttons"></div>
        <button onclick="this.closest('.modal').remove()" style="
          width: 100%; margin-top: 16px; padding: 8px; border: 1px solid #ccc;
          border-radius: 6px; background: white; cursor: pointer;
        ">Annuler</button>
      `;
      
      const buttonsContainer = content.querySelector('#shortcut-buttons');
      shortcuts.forEach(shortcut => {
        const btn = document.createElement('button');
        btn.textContent = shortcut.label;
        btn.style.cssText = `
          width: 100%; margin-bottom: 8px; padding: 12px; border: none;
          border-radius: 6px; background: var(--brand-red); color: white;
          cursor: pointer; font-weight: 500;
        `;
        btn.onclick = () => {
          applyDateShortcut(shortcut);
          modal.remove();
        };
        buttonsContainer.appendChild(btn);
      });
      
      modal.className = 'modal';
      modal.appendChild(content);
      document.body.appendChild(modal);
    }

    function applyDateShortcut(shortcut) {
      const today = new Date();
      let startDate, endDate;
      
      if (shortcut.days) {
        endDate = new Date(today);
        startDate = new Date(today);
        startDate.setDate(startDate.getDate() - shortcut.days + 1);
      } else if (shortcut.type === 'month') {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        endDate = new Date(today);
      } else if (shortcut.type === 'lastMonth') {
        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        endDate = new Date(today.getFullYear(), today.getMonth(), 0);
      }
      
      document.getElementById('start-date').value = startDate.toISOString().split('T')[0];
      document.getElementById('end-date').value = endDate.toISOString().split('T')[0];
      
      // Auto-set comparison period
      setSmartComparison();
      updateDateRange();
      loadDashboardData();
      
      showNotification(`Période appliquée: ${shortcut.label}`, 'success');
    }

    // Update comparison table
    function updateComparisonTable(kpis) {
      const tbody = document.getElementById('comparisonTableBody');
      if (!tbody) return;
      
      const metrics = [
        { name: 'Activated Subscriptions', data: kpis.activatedSubscriptions },
        { name: 'Active Subscriptions', data: kpis.activeSubscriptions },
        { name: 'Total Transactions', data: kpis.totalTransactions },
        { name: 'Transacting Users', data: kpis.transactingUsers },
        { name: 'Active Merchants', data: kpis.activeMerchants },
        { name: 'Conversion Rate (%)', data: kpis.conversionRate }
      ];
      
      tbody.innerHTML = metrics.map(metric => {
        const change = metric.data.change;
        const isPositive = change > 0;
        const badgeClass = isPositive ? 'badge-success' : change < 0 ? 'badge-danger' : 'badge-info';
        const absoluteChange = metric.data.current - metric.data.previous;
        
        return `
          <tr>
            <td><strong>${metric.name}</strong></td>
            <td>${formatNumber(metric.data.current)}</td>
            <td>${formatNumber(metric.data.previous)}</td>
            <td>${absoluteChange > 0 ? '+' : ''}${formatNumber(absoluteChange)}</td>
            <td>${change > 0 ? '+' : ''}${change.toFixed(1)}%</td>
            <td><span class="badge ${badgeClass}">${isPositive ? 'Improved' : change < 0 ? 'Declined' : 'Stable'}</span></td>
          </tr>
        `;
      }).join('');
    }

    // Update insights
    function updateInsights(insights) {
      updateInsightList('positiveInsights', insights.positive);
      updateInsightList('challenges', insights.challenges);
      updateInsightList('recommendations', insights.recommendations);
      updateInsightList('nextSteps', insights.nextSteps);
    }

    // Update individual insight list
    function updateInsightList(elementId, items) {
      const list = document.getElementById(elementId);
      if (!list) return;
      
      list.innerHTML = items.map(item => `<li>${item}</li>`).join('');
    }
  </script>
</body>
</html>

