@php
    $theme = 'club_privileges';
    $isOoredoo = false;
    $isClubPrivileges = true;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Club Privilèges - Sub-Stores Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root {
      @if($isOoredoo)
      --brand-primary: #E30613;
      --brand-secondary: #DC2626;
      --theme-name: 'Ooredoo';
      @else
      --brand-primary: #6B46C1;
      --brand-secondary: #8B5CF6;
      --theme-name: 'Club Privilèges';
      @endif
      --brand-dark: #1f2937;
      --bg: #f8fafc;
      --card: #ffffff;
      --muted: #64748b;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --accent: #3b82f6;
      --border: #e2e8f0;
      /* Backward compatibility */
      --brand-red: var(--brand-primary);
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
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      width: 100%;
      box-sizing: border-box;
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
      position: sticky;
      top: 0;
      z-index: 100;
      overflow-x: auto;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }
    
    .nav-tabs::-webkit-scrollbar {
      display: none;
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
      flex-shrink: 0;
      white-space: nowrap;
      min-width: fit-content;
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
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    
    @media (max-width: 600px) {
      .filters {
        grid-template-columns: 1fr;
        gap: 12px;
      }
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
      overflow-x: auto;
    }
    
    .table-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    
    .enhanced-table {
      min-width: 600px;
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
    .badge-secondary { background: #f3f4f6; color: #374151; }

    /* Loading */
    .loading {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
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

    /* Notifications */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 8px;
      color: white;
      font-weight: 500;
      z-index: 1000;
      animation: slideIn 0.3s ease;
    }

    .notification.success { background: var(--success); }
    .notification.error { background: var(--danger); }
    .notification.warning { background: var(--warning); }

    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    /* Responsive Design */
    @media (min-width: 1400px) {
      .container { max-width: 1600px; }
      .header { padding: 16px 20px; }
    }

    @media (max-width: 1200px) {
      .kpi-card { grid-column: span 4; }
    }

    @media (max-width: 900px) {
      .kpi-card { grid-column: span 4; }
      .chart-card { grid-column: span 6; }
      .kpi-value { font-size: 24px; }
    }

    @media (max-width: 768px) {
      .kpi-card { grid-column: span 6; }
      .chart-card { 
        grid-column: span 12;
        min-height: 280px;
      }
      .header {
        padding: 14px 16px;
        flex-wrap: wrap;
      }
      .header h1 { font-size: 20px; }
      .nav-tabs { padding: 6px; }
      .nav-tab { padding: 10px 12px; font-size: 14px; }
    }

    @media (max-width: 600px) {
      .kpi-card { grid-column: span 6; }
      .chart-card { min-height: 250px; }
      .container { padding: 16px 12px; }
      .header { padding: 12px 12px; }
      .header h1 { font-size: 18px; }
      .nav-tabs { padding: 4px; margin-bottom: 12px; }
      .nav-tab { padding: 8px 10px; font-size: 13px; }
    }

    @media (max-width: 480px) {
      .kpi-card { grid-column: span 12; }
      .chart-card { min-height: 220px; }
      .container { padding: 12px 8px; }
      .nav-tabs { padding: 4px; margin-bottom: 12px; }
      .nav-tab { padding: 6px 8px; font-size: 12px; }
    }

    /* Sub-store specific styles */
    .substore-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .substore-icon {
      width: 40px;
      height: 40px;
      background: var(--brand-primary);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
    }

    .substore-title {
      font-size: 24px;
      font-weight: 700;
      color: var(--brand-dark);
      margin: 0;
    }

    .substore-subtitle {
      font-size: 14px;
      color: var(--muted);
      margin-top: 4px;
    }

    /* Period selection card */
    .period-selection-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .period-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    @media (max-width: 768px) {
      .period-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
    }

    .period-section {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .period-label {
      font-size: 14px;
      font-weight: 600;
      color: var(--brand-dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .period-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
    }

    .period-dot.primary { background: var(--brand-primary); }
    .period-dot.comparison { background: var(--warning); }

    .date-inputs {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .date-input {
      flex: 1;
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      font-size: 14px;
      background: var(--card);
    }

    .date-separator {
      color: var(--muted);
      font-weight: 500;
    }

    .substore-selector {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .substore-dropdown {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
      background: var(--card);
      color: var(--brand-dark);
    }

    .action-buttons {
      display: flex;
      gap: 12px;
      margin-top: 16px;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary { background: var(--brand-primary); color: white; }
    .btn-secondary { background: var(--accent); color: white; }
    .btn-success { background: var(--success); color: white; }
    .btn-warning { background: var(--warning); color: white; }

    .btn:hover { opacity: 0.9; transform: translateY(-1px); }

    @media (max-width: 600px) {
      .action-buttons {
        flex-direction: column;
      }
      .btn {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="header-left">
        <div class="substore-header">
          <div class="substore-icon">🏪</div>
          <div>
            <h1>Dashboard Sub-Stores</h1>
            <div class="substore-subtitle">Vue Globale</div>
          </div>
        </div>
      </div>
      <div class="header-right">
        <div class="user-menu">
          <div class="user-info">
            <div class="user-name">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
            <div class="user-role">{{ Auth::user()->role->name ?? 'Super Administrateur' }}</div>
          </div>
          <a href="/" class="admin-btn">Dashboard Opérateurs</a>
          <a href="/admin" class="admin-btn">Administration</a>
          <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="logout-btn">Déconnexion</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
      <button class="nav-tab" onclick="showTab('overview')">Vue d'Ensemble</button>
      <button class="nav-tab active" onclick="showTab('substores')">Sub-Stores</button>
      <button class="nav-tab" onclick="showTab('categories')">Catégories</button>
      <button class="nav-tab" onclick="showTab('performance')">Performance</button>
    </div>

    <!-- Period Selection -->
    <div class="period-selection-card">
      <div class="period-grid">
        <div class="period-section">
          <div class="period-label">
            <div class="period-dot primary"></div>
            Période Principale
          </div>
          <div class="date-inputs">
            <input type="date" id="startDate" class="date-input" value="2025-03-15">
            <span class="date-separator">au</span>
            <input type="date" id="endDate" class="date-input" value="2025-09-15">
          </div>
        </div>
        <div class="period-section">
          <div class="period-label">
            <div class="period-dot comparison"></div>
            Période de Comparaison
          </div>
          <div class="date-inputs">
            <input type="date" id="comparisonStartDate" class="date-input" value="2024-09-15">
            <span class="date-separator">au</span>
            <input type="date" id="comparisonEndDate" class="date-input" value="2025-03-14">
          </div>
        </div>
      </div>
    </div>

    <!-- Sub-Store Selector -->
    <div class="substore-selector">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div class="substore-icon">🏪</div>
        <div>
          <div style="font-weight: 600; color: var(--brand-dark);">Sub-Store</div>
          <div style="font-size: 14px; color: var(--muted);">Accès global à tous les sub-stores</div>
        </div>
      </div>
      <select id="subStoreSelect" class="substore-dropdown">
        <option value="ALL">Tous les sub-stores</option>
        <!-- Options will be populated by JavaScript -->
      </select>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="refreshData()">
          <span>🔄</span> Actualiser
        </button>
        <button class="btn btn-secondary" onclick="autoComparison()">
          <span>📊</span> Comparaison Auto
        </button>
        <button class="btn btn-success" onclick="exportData()">
          <span>📤</span> Exporter
        </button>
        <button class="btn btn-warning" onclick="showHelp()">
          <span>❓</span> Aide
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div id="overview" class="tab-content">
      <!-- Overview content will be here -->
    </div>

    <div id="substores" class="tab-content active">
      <!-- Sub-stores content will be here -->
      <div class="loading" id="loadingIndicator">
        <div class="spinner"></div>
        Chargement des données...
      </div>
      
      <div id="dashboardContent" style="display: none;">
        <!-- KPIs will be loaded here -->
        <div class="grid" id="kpisGrid">
          <!-- KPI cards will be populated by JavaScript -->
        </div>
        
        <!-- Charts will be loaded here -->
        <div class="grid">
          <div class="chart-card">
            <div class="chart-title">Répartition par Catégories</div>
            <div class="chart-container">
              <canvas id="categoryChart"></canvas>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-title">Évolution des Inscriptions</div>
            <div class="chart-container">
              <canvas id="inscriptionsChart"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Table -->
        <div class="card table-card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="chart-title">Top Sub-Stores Performance</div>
            <button class="btn btn-secondary" onclick="exportTable()">
              <span>📤</span> Exporter
            </button>
          </div>
          <div class="table-wrapper">
            <table class="enhanced-table">
              <thead>
                <tr>
                  <th>Rang</th>
                  <th>Nom du Sub-Store</th>
                  <th>Catégorie</th>
                  <th>Transactions</th>
                  <th>Clients Uniques</th>
                  <th>Localisation</th>
                  <th>Variation</th>
                </tr>
              </thead>
              <tbody id="subStoresTableBody">
                <!-- Data will be populated by JavaScript -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div id="categories" class="tab-content">
      <!-- Categories content will be here -->
    </div>

    <div id="performance" class="tab-content">
      <!-- Performance content will be here -->
    </div>
  </div>

  <script>
    // Global variables
    let currentData = null;
    let categoryChart = null;
    let inscriptionsChart = null;

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      initializeDashboard();
    });

    function initializeDashboard() {
      console.log('🚀 Initialisation du dashboard sub-stores');
      loadDashboardData();
    }

    function showTab(tabName) {
      // Hide all tab contents
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Remove active class from all nav tabs
      document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show selected tab content
      document.getElementById(tabName).classList.add('active');
      
      // Add active class to clicked nav tab
      event.target.classList.add('active');
    }

    async function loadDashboardData() {
      const loadingIndicator = document.getElementById('loadingIndicator');
      const dashboardContent = document.getElementById('dashboardContent');
      
      try {
        loadingIndicator.style.display = 'flex';
        dashboardContent.style.display = 'none';
        
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const subStore = document.getElementById('subStoreSelect').value;
        
        console.log('📊 Chargement des données:', { startDate, endDate, subStore });
        
        const response = await fetch(`/sub-stores/api/sub-stores?start_date=${startDate}&end_date=${endDate}&sub_store=${subStore}`);
        const data = await response.json();
        
        console.log('✅ Données reçues:', data);
        
        currentData = data;
        updateDashboard(data);
        
        loadingIndicator.style.display = 'none';
        dashboardContent.style.display = 'block';
        
        showNotification(`Données ${subStore === 'ALL' ? 'tous sub-stores' : subStore} mises à jour!`, 'success');
        
      } catch (error) {
        console.error('❌ Erreur lors du chargement des données:', error);
        loadingIndicator.style.display = 'none';
        showNotification('Erreur de connexion: ' + error.message, 'error');
      }
    }

    function updateDashboard(data) {
      updateKPIs(data.kpis);
      updateCharts(data);
      updateSubStoresTable(data.sub_stores);
    }

    function updateKPIs(kpis) {
      const kpiCards = [
        { id: 'distributed', title: 'DISTRIBUÉ', value: kpis.distributed },
        { id: 'inscriptions', title: 'INSCRIPTIONS', value: kpis.inscriptions },
        { id: 'conversionRate', title: 'TAUX DE CONVERSION', value: kpis.conversionRate, suffix: '%' },
        { id: 'transactions', title: 'TRANSACTIONS', value: kpis.transactions },
        { id: 'activeUsers', title: 'ACTIVE USERS', value: kpis.activeUsers },
        { id: 'activeUsersCohorte', title: 'ACTIVE USERS COHORTE', value: kpis.activeUsersCohorte },
        { id: 'transactionsCohorte', title: 'TRANSACTIONS COHORTE', value: kpis.transactionsCohorte },
        { id: 'inscriptionsCohorte', title: 'INSCRIPTIONS COHORTE', value: kpis.inscriptionsCohorte }
      ];

      const kpisGrid = document.getElementById('kpisGrid');
      kpisGrid.innerHTML = '';

      kpiCards.forEach(kpi => {
        const kpiCard = document.createElement('div');
        kpiCard.className = 'kpi-card';
        kpiCard.innerHTML = `
          <div class="kpi-title">${kpi.title}</div>
          <div class="kpi-value">${formatNumber(kpi.value)}${kpi.suffix || ''}</div>
          <div class="kpi-delta delta-neutral">-</div>
        `;
        kpisGrid.appendChild(kpiCard);
      });
    }

    function updateCharts(data) {
      console.log('🔄 Mise à jour des graphiques:', data);
      
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js non chargé');
        return;
      }

      // Category Chart
      if (data.categoryDistribution && data.categoryDistribution.length > 0) {
        createCategoryDonutChart(data.categoryDistribution);
      }

      // Inscriptions Chart
      if (data.inscriptionsTrend && data.inscriptionsTrend.length > 0) {
        createInscriptionsChart(data.inscriptionsTrend);
      }
    }

    function createCategoryDonutChart(data) {
      const ctx = document.getElementById('categoryChart');
      if (!ctx) return;

      if (categoryChart) {
        categoryChart.destroy();
      }

      try {
        categoryChart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: data.map(item => item.name),
            datasets: [{
              data: data.map(item => item.utilization),
              backgroundColor: [
                '#6B46C1', '#8B5CF6', '#A78BFA', '#C4B5FD',
                '#DDD6FE', '#EDE9FE', '#F3F4F6', '#E5E7EB'
              ],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  usePointStyle: true
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('❌ Erreur création graphique catégories:', error);
      }
    }

    function createInscriptionsChart(data) {
      const ctx = document.getElementById('inscriptionsChart');
      if (!ctx) return;

      if (inscriptionsChart) {
        inscriptionsChart.destroy();
      }

      try {
        inscriptionsChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: data.map(item => item.date),
            datasets: [{
              label: 'Inscriptions',
              data: data.map(item => item.value),
              borderColor: '#6B46C1',
              backgroundColor: 'rgba(107, 70, 193, 0.1)',
              borderWidth: 2,
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
                beginAtZero: true,
                grid: {
                  color: '#f1f5f9'
                }
              },
              x: {
                grid: {
                  color: '#f1f5f9'
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('❌ Erreur création graphique inscriptions:', error);
      }
    }

    function updateSubStoresTable(stores) {
      const tbody = document.getElementById('subStoresTableBody');
      tbody.innerHTML = '';
      
      stores.forEach(store => {
        const row = tbody.insertRow();
        
        // Rang
        const rankBadge = `<span class="badge badge-info">${store.rank}</span>`;
        row.insertCell(0).innerHTML = rankBadge;
        
        // Nom
        row.insertCell(1).textContent = store.name;
        
        // Catégorie
        row.insertCell(2).textContent = store.category;
        
        // Transactions
        row.insertCell(3).textContent = formatNumber(store.transactions || 0);
        
        // Clients
        row.insertCell(4).textContent = formatNumber(store.customers || 0);
        
        // Localisation
        row.insertCell(5).textContent = store.location;
        
        // Variation
        const growth = store.growth || 0;
        const growthClass = growth > 0 ? 'delta-positive' : growth < 0 ? 'delta-negative' : 'delta-neutral';
        const growthText = growth > 0 ? `+${growth}%` : `${growth}%`;
        row.insertCell(6).innerHTML = `<span class="kpi-delta ${growthClass}">${growthText}</span>`;
      });
    }

    function formatNumber(num) {
      return new Intl.NumberFormat('fr-FR').format(num);
    }

    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.textContent = message;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.remove();
      }, 3000);
    }

    function refreshData() {
      loadDashboardData();
    }

    function autoComparison() {
      // Auto comparison logic
      showNotification('Comparaison automatique activée', 'success');
    }

    function exportData() {
      // Export logic
      showNotification('Export en cours...', 'success');
    }

    function exportTable() {
      // Table export logic
      showNotification('Table exportée', 'success');
    }

    function showHelp() {
      // Help logic
      showNotification('Aide disponible', 'success');
    }

    // Event listeners
    document.getElementById('subStoreSelect').addEventListener('change', loadDashboardData);
    document.getElementById('startDate').addEventListener('change', loadDashboardData);
    document.getElementById('endDate').addEventListener('change', loadDashboardData);
  </script>
</body>
</html>



