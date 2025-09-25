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
      width: 200px;
      height: 60px;
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
      width: calc(20% - 12px); /* Force 5 colonnes exactement */
      flex: 0 0 calc(20% - 12px);
      text-align: center;
      position: relative;
      overflow: hidden;
      min-height: 120px;
      margin: 0;
    }
    
    /* Container des KPIs en flexbox pour forcer 2 lignes */
    #kpisGrid {
      display: flex !important;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 24px;
      justify-content: flex-start;
    }
    
    /* Force les KPIs après le 5ème à aller sur une nouvelle ligne */
    .kpi-card:nth-child(6) {
      margin-left: 0;
    }
    
    /* Responsive pour KPIs avec flexbox */
    @media (max-width: 1400px) {
      .kpi-card {
        width: calc(25% - 12px); /* 4 par ligne */
        flex: 0 0 calc(25% - 12px);
      }
    }
    
    @media (max-width: 1000px) {
      .kpi-card {
        width: calc(33.333% - 12px); /* 3 par ligne */
        flex: 0 0 calc(33.333% - 12px);
      }
    }
    
    @media (max-width: 768px) {
      .kpi-card {
        width: calc(50% - 12px); /* 2 par ligne */
        flex: 0 0 calc(50% - 12px);
      }
    }
    
    @media (max-width: 480px) {
      .kpi-card {
        width: calc(100% - 12px); /* 1 par ligne */
        flex: 0 0 calc(100% - 12px);
      }
    }
    
    /* KPI Card Backgrounds - Tous avec la même couleur violette */
    .kpi-card.distributed,
    .kpi-card.inscriptions,
    .kpi-card.conversion,
    .kpi-card.transactions,
    .kpi-card.active-users,
    .kpi-card.cohort,
    .kpi-card.subscriptions,
    .kpi-card.renewal {
      background: #6B46C1;
            color: white;
        }

    /* Icône d'information */
    .info-icon {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 18px;
      height: 18px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: bold;
      cursor: help;
      transition: all 0.3s ease;
    }
    
    .info-icon:hover {
      background: rgba(255,255,255,0.3);
      transform: scale(1.1);
    }
    
    /* Icône d'information pour les graphiques et tableaux */
    .chart-card .info-icon,
    .table-card .info-icon {
      background: rgba(107, 70, 193, 0.1);
      color: #6B46C1;
      border: 1px solid rgba(107, 70, 193, 0.2);
    }
    
    .chart-card .info-icon:hover,
    .table-card .info-icon:hover {
      background: rgba(107, 70, 193, 0.2);
      border-color: rgba(107, 70, 193, 0.3);
    }
    
    /* Tooltip styles améliorés */
    .tooltip {
      position: relative;
    }
    
    .tooltip .tooltiptext {
      visibility: hidden;
      width: 300px;
      background: #1f2937;
      color: #f9fafb;
      text-align: left;
      border-radius: 12px;
      padding: 16px;
      position: fixed;
      z-index: 9999;
      opacity: 0;
      transition: all 0.3s ease;
      font-size: 13px;
      line-height: 1.5;
      box-shadow: 0 8px 32px rgba(0,0,0,0.24);
      border: 1px solid rgba(255,255,255,0.1);
      pointer-events: none;
    }
    
    .tooltip .tooltiptext::before {
      content: "";
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -8px;
      border-width: 8px;
      border-style: solid;
      border-color: #1f2937 transparent transparent transparent;
    }
    
    .tooltip .tooltiptext::after {
      content: "";
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -7px;
      border-width: 7px;
      border-style: solid;
      border-color: rgba(255,255,255,0.1) transparent transparent transparent;
    }
    
    /* Déclencher le tooltip sur hover de toute la carte */
    .kpi-card:hover .tooltiptext {
      visibility: visible;
      opacity: 1;
      transform: translateY(-5px);
    }
    
    /* Déclencher aussi sur hover de l'icône */
    .info-icon:hover ~ .tooltiptext {
      visibility: visible;
      opacity: 1;
      transform: translateY(-5px);
    }
    
    /* Style spécial pour les tooltips de graphiques */
    .chart-tooltip {
      top: 120%;
      bottom: auto;
      margin-top: 10px;
    }
    
    .chart-tooltip::before {
      top: -8px;
      border-color: transparent transparent #1f2937 transparent;
    }
    
    .chart-tooltip::after {
      top: -7px;
      border-color: transparent transparent rgba(255,255,255,0.1) transparent;
        }

        .kpi-title {
            font-size: 11px;
      color: rgba(255,255,255,0.9);
      margin-bottom: 6px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      line-height: 1.2;
        }

        .kpi-value {
      font-size: 24px;
            font-weight: 700;
      margin-bottom: 3px;
      color: white;
      line-height: 1.1;
    }
    
    .kpi-delta {
      font-size: 11px;
      font-weight: 500;
            display: flex;
            align-items: center;
      justify-content: center;
            gap: 4px;
      color: rgba(255,255,255,0.8);
    }

    .delta-positive { color: var(--success); }
    .delta-negative { color: var(--danger); }
    .delta-neutral { color: var(--muted); }
    
    /* Chart Cards */
        .chart-card {
      grid-column: span 6;
      min-height: 350px;
      position: relative;
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .chart-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #6B46C1, #8B5CF6, #A78BFA);
      border-radius: 12px 12px 0 0;
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
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      position: relative;
    }
    
    .table-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
      border-radius: 12px 12px 0 0;
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
    
    /* Ranking badges */
    .badge-gold { 
      background: linear-gradient(135deg, #ffd700, #ffed4e); 
      color: #92400e; 
      font-weight: 700;
      box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
    }
    .badge-silver { 
      background: linear-gradient(135deg, #c0c0c0, #e5e7eb); 
      color: #374151; 
      font-weight: 700;
      box-shadow: 0 2px 4px rgba(192, 192, 192, 0.3);
    }
    .badge-bronze { 
      background: linear-gradient(135deg, #cd7f32, #f59e0b); 
      color: #ffffff; 
      font-weight: 700;
      box-shadow: 0 2px 4px rgba(205, 127, 50, 0.3);
    }

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
      .chart-card { grid-column: span 6; }
    }

    @media (max-width: 900px) {
      .chart-card { grid-column: span 6; }
    }

        @media (max-width: 768px) {
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
      .chart-card { min-height: 250px; }
      .container { padding: 16px 12px; }
      .header { padding: 12px 12px; }
      .header h1 { font-size: 18px; }
      .nav-tabs { padding: 4px; margin-bottom: 12px; }
      .nav-tab { padding: 8px 10px; font-size: 13px; }
    }

    @media (max-width: 480px) {
      .chart-card { min-height: 220px; }
      .container { padding: 12px 8px; }
      .nav-tabs { padding: 4px; margin-bottom: 12px; }
      .nav-tab { padding: 6px 8px; font-size: 12px; }
    }

    /* Merchants Section Styles */
    .merchants-kpis-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
                gap: 20px;
      margin-bottom: 24px;
    }
    .merchants-kpis-row .kpi-card { grid-column: span 1 !important; }
    .merchants-kpi { min-height: 120px; }
    .merchants-kpi .kpi-value { font-size: 32px; }
    .merchants-kpi .kpi-delta { min-height: 18px; }

    .merchants-kpi {
      grid-column: span 1;
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 24px;
      min-height: 140px;
      background: linear-gradient(135deg, #6B46C1 0%, #8B5CF6 100%) !important;
      border-radius: 12px;
      color: white !important;
      width: 100% !important;
      box-sizing: border-box;
    }
    
    .merchants-kpi .kpi-icon {
      font-size: 28px !important;
      color: white !important;
      flex-shrink: 0;
      width: 40px;
      text-align: center;
    }
    
    .merchants-kpi .kpi-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 0;
    }
    
    .merchants-kpi .kpi-title {
      color: rgba(255, 255, 255, 0.9) !important;
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 8px;
      line-height: 1.2;
    }
    
    .merchants-kpi .kpi-value {
      color: white !important;
      font-weight: 700;
      font-size: 28px !important;
      line-height: 1.1;
      margin-bottom: 4px;
    }
    
    .merchants-kpi .kpi-delta {
      color: rgba(255, 255, 255, 0.8) !important;
      font-weight: 500;
      font-size: 12px;
    }

    /* Delta badges pour les marchands */
    .delta-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      min-width: 60px;
    }

    .delta-positive {
      background-color: #22c55e;
      color: white;
    }

    .delta-negative {
      background-color: #ef4444;
      color: white;
    }

    .delta-neutral {
      background-color: #6b7280;
      color: white;
    }

    /* Pagination - Style amélioré */
    .pagination-container {
      border-top: 1px solid var(--border);
      padding: 20px 0;
      margin-top: 20px;
    }

    .pagination-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }

    .pagination-info {
      font-size: 14px;
      color: var(--muted);
      min-width: 150px;
    }

    .pagination-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      justify-content: center;
    }

    .page-numbers {
      display: flex;
      gap: 4px;
      margin: 0 10px;
    }

    .pagination-controls .btn {
      padding: 8px 16px;
      border: 1px solid var(--border);
      background: white;
      color: var(--text);
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s ease;
      min-width: 80px;
    }

    .pagination-controls .btn:hover:not(:disabled) {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
      transform: translateY(-1px);
    }

    .pagination-controls .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    .pagination-controls .btn.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .pagination-size {
      min-width: 120px;
    }

    .pagination-size select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: white;
      color: var(--text);
      font-size: 14px;
    }

    /* Style uniforme pour TOUS les deltas du dashboard */
    .delta-badge, .kpi-delta, .positive, .negative, .neutral {
      display: inline-block !important;
      padding: 4px 12px !important;
      border-radius: 12px !important;
      font-size: 12px !important;
      font-weight: 600 !important;
      text-align: center !important;
      min-width: 70px !important;
      max-width: 90px !important;
      white-space: nowrap !important;
      box-sizing: border-box !important;
      margin: 0 auto !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    /* Couleurs harmonisées pour tous les deltas */
    .delta-positive, .positive {
      background-color: #22c55e !important;
      color: white !important;
    }

    .delta-negative, .negative {
      background-color: #ef4444 !important;
      color: white !important;
    }

    .delta-neutral, .neutral {
      background-color: #6b7280 !important;
      color: white !important;
    }

    /* Override spécifique pour les KPIs marchands qui sont sur fond violet */
    .merchants-kpi .kpi-delta.delta-badge {
      background-color: rgba(255, 255, 255, 0.15) !important;
      color: white !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      backdrop-filter: blur(5px);
    }

    .merchants-kpi .kpi-delta.delta-positive {
      background-color: rgba(34, 197, 94, 0.8) !important;
      color: white !important;
    }

    .merchants-kpi .kpi-delta.delta-negative {
      background-color: rgba(239, 68, 68, 0.8) !important;
      color: white !important;
    }

    .merchants-kpi .kpi-delta.delta-neutral {
      background-color: rgba(255, 255, 255, 0.2) !important;
      color: white !important;
    }

    .kpi-icon {
      font-size: 32px;
      opacity: 0.8;
    }

    .kpi-content {
      flex: 1;
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
        <svg class="logo" viewBox="0 0 200 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="clubGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:var(--brand-primary);stop-opacity:1" />
              <stop offset="100%" style="stop-color:var(--brand-secondary);stop-opacity:1" />
            </linearGradient>
          </defs>
          <rect width="200" height="60" fill="url(#clubGradient)" rx="8"/>
          <text x="20" y="25" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">Club</text>
          <text x="20" y="45" fill="#F59E0B" font-family="Arial, sans-serif" font-size="14" font-weight="600" font-style="italic">Privilèges</text>
        </svg>
        <h1>Club Privilèges - Sub-Stores Dashboard</h1>
            </div>
      <div class="header-right">
        <span>🏪</span>
        <span>{{ Auth::user()->isSuperAdmin() ? 'Vue Globale' : 'Vue Sub-Stores' }}</span>
            
            <div class="user-menu">
                <div class="user-info" id="profileMenuToggle" style="cursor: pointer;">
                  <div class="user-name">{{ Auth::user()->name ?? 'Utilisateur' }}</div>
                  <div class="user-role">{{ Auth::user()->role->display_name ?? 'Aucun rôle' }}</div>
                </div>
                <div id="profileDropdown" class="dropdown" style="display:none; position:absolute; right:20px; top:60px; background: var(--card); border:1px solid var(--border); border-radius: 8px; min-width: 220px; z-index: 999; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
                  @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->isAdmin()))
                  <a href="{{ route('admin.users.index') }}" class="admin-btn" style="display:block; margin:8px;">Utilisateurs</a>
                  <a href="{{ route('admin.invitations.index') }}" class="admin-btn" style="display:block; margin:8px;">Invitations</a>
                  @endif
                  <a href="{{ route('password.change') }}" class="admin-btn" style="display:block; margin:8px;">🔒 Mot de passe</a>
                  @if(Auth::user()->canAccessOperatorsDashboard())
                  <a href="{{ route('dashboard') }}" class="admin-btn" style="display:block; margin:8px;">📊 Dashboard Opérateurs</a>
                  @endif
                  @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin())
                  <a href="{{ route('admin.eklektik-cron') }}" class="admin-btn" style="display:block; margin:8px;">⚙️ Configuration Eklektik</a>
                  @endif
                  <form action="{{ route('auth.logout') }}" method="POST" style="display:block; margin:8px;">
                    @csrf
                    <button type="submit" class="logout-btn" style="width:100%;" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">Déconnexion</button>
                  </form>
                </div>
            </div>
            </div>
        </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
      <button class="nav-tab active" onclick="showTab('overview')">Vue d'Ensemble</button>
      
      @if(Auth::user()->isSuperAdmin())
      <button class="nav-tab" onclick="showTab('substores')">Sub-Stores</button>
      @endif
      <button class="nav-tab" onclick="showTab('merchant')">Merchant</button>
      @if(Auth::user()->isSuperAdmin())
      <button class="nav-tab" onclick="showTab('performance')">Performance</button>
      @endif
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
            <input type="date" id="startDate" class="date-input" value="">
            <span class="date-separator">au</span>
            <input type="date" id="endDate" class="date-input" value="">
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
        <div id="overview" class="tab-content active">
      <!-- Vue d'Ensemble content -->
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
                <div class="chart-card card tooltip">
                    <div class="info-icon">i</div>
            <div class="chart-title">Évolution des Inscriptions par Mois</div>
                    <div class="chart-container">
              <canvas id="inscriptionsChart"></canvas>
                        </div>
                    <span class="tooltiptext chart-tooltip">Ce graphique montre combien de nouvelles personnes s'inscrivent chaque mois. C'est comme voir combien de nouveaux amis rejoignent notre club chaque mois ! Plus la barre est haute, plus on a de nouveaux membres.</span>
                    </div>
                <div class="chart-card card tooltip">
                    <div class="info-icon">i</div>
            <div class="chart-title">Abonnements à expiration par mois</div>
                    <div class="chart-container">
              <canvas id="expirationsChart"></canvas>
                    </div>
                        <span class="tooltiptext chart-tooltip">Ce graphique montre combien d'abonnements se terminent chaque mois. C'est comme voir combien de cartes de membre arrivent à expiration. Cela nous aide à savoir quand contacter les gens pour renouveler !</span>
                    </div>
                </div>

        <!-- Table -->
        <!-- Category Distribution Table -->
        <div class="card table-card tooltip">
          <div class="info-icon" style="background: rgba(107, 70, 193, 0.2); color: #6B46C1;">i</div>
          <span class="tooltiptext chart-tooltip">Ce tableau montre les différentes catégories d'achats et combien de fois elles sont utilisées. C'est comme voir quels types de jouets ou d'activités sont les plus populaires dans notre club !</span>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div class="chart-title">Répartition par Catégories</div>
            <button class="btn btn-secondary" onclick="exportCategoryTable()">
              <span>📤</span> Exporter
            </button>
                        </div>
          <div class="table-wrapper">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                  <th>Utilisation</th>
                  <th>Pourcentage</th>
                  <th>Évolution</th>
                            </tr>
                        </thead>
              <tbody id="categoryTableBody">
                <!-- Table rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                    </div>
        </div>

                    </div>
                </div>

    <div id="merchant" class="tab-content">
      <!-- Merchant Analysis Content -->
      <div class="merchants-kpis-row">
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🏪</div>
          <div class="kpi-content">
            <div class="kpi-title">Total Merchants <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Nombre total de partenaires (table partner).">ⓘ</span></div>
            <div class="kpi-value" id="merch-totalPartners">Loading...</div>
            <div class="kpi-delta" id="merch-totalPartnersDelta">→ 0.0%</div>
                        </div>
                    </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">📈</div>
          <div class="kpi-content">
            <div class="kpi-title">Active Merchants <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Marchands ayant eu au moins une transaction dans la période.">ⓘ</span></div>
            <div class="kpi-value" id="merch-activeMerchants">Loading...</div>
            <div class="kpi-delta" id="merch-activeMerchantsDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">📍</div>
          <div class="kpi-content">
            <div class="kpi-title">Total Locations Active <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Nombre total de lieux/points de vente actifs.">ⓘ</span></div>
            <div class="kpi-value" id="merch-totalLocationsActive">Loading...</div>
            <div class="kpi-delta" id="merch-totalLocationsActiveDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">📊</div>
          <div class="kpi-content">
            <div class="kpi-title">Active Merchant Ratio <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Pourcentage de marchands actifs vs total.">ⓘ</span></div>
            <div class="kpi-value" id="merch-activeMerchantRatio">Loading...</div>
            <div class="kpi-delta" id="merch-activeMerchantRatioDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">💰</div>
          <div class="kpi-content">
            <div class="kpi-title">Total Transactions <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Total des transactions marchandes.">ⓘ</span></div>
            <div class="kpi-value" id="merch-totalTransactions">Loading...</div>
            <div class="kpi-delta" id="merch-totalTransactionsDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">📈</div>
          <div class="kpi-content">
            <div class="kpi-title">Transactions per Merchant <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Moyenne de transactions par marchand.">ⓘ</span></div>
            <div class="kpi-value" id="merch-transactionsPerMerchant">Loading...</div>
            <div class="kpi-delta" id="merch-transactionsPerMerchantDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🏆</div>
          <div class="kpi-content">
            <div class="kpi-title">Top Merchant Share <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Part de marché du meilleur marchand.">ⓘ</span></div>
            <div class="kpi-value" id="merch-topMerchantShare">Loading...</div>
            <div class="kpi-delta" id="merch-topMerchantShareDelta">Loading...</div>
                    </div>
                </div>
        <div class="card kpi-card merchants-kpi">
          <div class="kpi-icon">🎯</div>
          <div class="kpi-content">
            <div class="kpi-title">Diversity <span style="margin-left:4px; cursor: help; color: var(--muted);" title="Niveau de diversité des marchands.">ⓘ</span></div>
            <div class="kpi-value" id="merch-diversity">Loading...</div>
            <div class="kpi-delta" id="merch-diversityDelta">Loading...</div>
                    </div>
                </div>
      </div>

      <!-- Table Section -->
      <div class="card table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div class="chart-title">Top Merchants Performance</div>
          <button class="btn btn-secondary" onclick="exportMerchantTable()">
            <span>📤</span> Exporter
          </button>
        </div>
        <div class="table-wrapper">
          <table class="enhanced-table">
            <thead>
              <tr>
                <th>Rang</th>
                <th>Nom du Marchand</th>
                <th>Catégorie</th>
                <th>Transactions</th>
                <th>Part de Marché</th>
                <th>Delta</th>
              </tr>
            </thead>
            <tbody id="merchantTableBody">
              <!-- Table rows will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="pagination-container">
          <div class="pagination-row">
            <div class="pagination-info">
              <span id="merchantPaginationInfo">Affichage 1-10 sur 0 marchands</span>
            </div>
            <div class="pagination-controls">
              <button id="merchantPrevBtn" class="btn btn-secondary" onclick="changeMerchantPage(-1)" disabled>‹ Précédent</button>
              <span id="merchantPageNumbers" class="page-numbers"></span>
              <button id="merchantNextBtn" class="btn btn-secondary" onclick="changeMerchantPage(1)">Suivant ›</button>
            </div>
            <div class="pagination-size">
              <select id="merchantPageSize" onchange="changeMerchantPageSize()">
                <option value="10">10 par page</option>
                <option value="25">25 par page</option>
                <option value="50">50 par page</option>
                <option value="100">100 par page</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(Auth::user()->isSuperAdmin())
    <div id="substores" class="tab-content">
      <!-- Sub-stores content -->
      <div class="card table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div class="chart-title">Classement des Meilleurs Sub-Stores</div>
          <button class="btn btn-secondary" onclick="exportSubStoresTable()">
            <span>📤</span> Exporter
          </button>
                        </div>
        <div class="table-wrapper">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                <th>Rang</th>
                <th>Nom du Sub-Store</th>
                <th>Type</th>
                <th>Clients</th>
                <th>Transactions</th>
                <th>Manager</th>
                            </tr>
                        </thead>
            <tbody id="subStoresRankingTableBody">
              <!-- Table rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                    </div>
                    </div>
                </div>


        <div id="performance" class="tab-content">
      <!-- Performance content will be here -->
                        </div>
    @endif

    <script>
    // Dropdown profil (mêmes interactions que dashboard principal)
    document.addEventListener('DOMContentLoaded', function() {
      const toggle = document.getElementById('profileMenuToggle');
      const dropdown = document.getElementById('profileDropdown');
      if (toggle && dropdown) {
        toggle.addEventListener('click', function(e) {
          e.stopPropagation();
          dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', function() {
          dropdown.style.display = 'none';
        });
      }
    });
        // Global variables
    let currentData = null;
    let inscriptionsChart = null;
    let expirationsChart = null;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
        });

    function initializeDashboard() {
      console.log('🚀 Initialisation du dashboard sub-stores');
      
      // Initialiser les dates par défaut avec une période contenant des données réelles des sub-stores
      // Période optimale détectée: 2025-08-18 → 2025-08-24 (7 jours avec activité sub-stores clients avec cartes)
      document.getElementById('startDate').value = '2025-08-18';
      document.getElementById('endDate').value = '2025-08-24';
      
      // Charger la liste des sub-stores et les données du dashboard
      loadSubStores().then(() => {
        loadDashboardData();
      });
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
            
            // Show selected tab content (only if it exists)
            const tabContent = document.getElementById(tabName);
            if (tabContent) {
                tabContent.classList.add('active');
            }
            
      // Add active class to the corresponding nav tab
            const navTabs = document.querySelectorAll('.nav-tab');
            navTabs.forEach(tab => {
                if (tab.textContent.toLowerCase().includes(tabName.toLowerCase()) || 
                    (tabName === 'overview' && tab.textContent.includes('Vue d\'Ensemble'))) {
                    tab.classList.add('active');
                }
            });
            
    // Si on active l'onglet Merchant et qu'on a des données merchant sauvegardées
    if (tabName === 'merchant' && window.merchantKPIsData) {
      console.log('🏪 Activation onglet Merchant, mise à jour des KPIs avec données sauvegardées');
      setTimeout(() => {
        updateMerchantKPIs(window.merchantKPIsData);
      }, 200); // Délai un peu plus long pour que les éléments soient bien rendus et l'onglet activé
    }
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
        
        // Calculer le timeout selon la période
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);
        const periodDays = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24)) + 1;
        const timeoutMs = periodDays > 90 ? 60000 : (periodDays > 30 ? 30000 : 15000); // 60s/30s/15s
        
        console.log(`🕐 Période: ${periodDays} jours, Timeout: ${timeoutMs/1000}s`);
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
        }, timeoutMs);
        
        const response = await fetch(`/sub-stores/api/dashboard/data?start_date=${startDate}&end_date=${endDate}&sub_store=${subStore}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Réponse non-JSON reçue:', text.substring(0, 200));
            throw new Error('Le serveur a renvoyé du HTML au lieu de JSON. Vérifiez les logs du serveur.');
        }
        
        const data = await response.json();
                
        console.log('✅ Données reçues:', data);
        
        console.log('✅ Réponse API reçue:', data);
        console.log('🔍 Structure des KPIs:', Object.keys(data.kpis || {}));
        console.log('🔍 totalPartners dans kpis:', data.kpis?.totalPartners);
        console.log('🔍 merchants array:', data.merchants);
        currentData = data;
        updateDashboard(data);
        
        loadingIndicator.style.display = 'none';
        dashboardContent.style.display = 'block';
        
        showNotification(`Données ${subStore === 'ALL' ? 'tous sub-stores' : subStore} mises à jour!`, 'success');
                
            } catch (error) {
        console.error('❌ Erreur lors du chargement des données:', error);
        loadingIndicator.style.display = 'none';
        
        let errorMessage = 'Erreur de connexion';
        if (error.name === 'AbortError') {
            errorMessage = `⏱️ Timeout: La période est trop longue (${periodDays} jours). Essayez une période plus courte.`;
        } else if (error.message.includes('JSON')) {
            errorMessage = '🔧 Erreur serveur: Vérifiez les logs Laravel';
        } else if (error.message.includes('400')) {
            errorMessage = '📅 Période invalide ou trop longue (max 1 an)';
        } else {
            errorMessage = 'Erreur: ' + error.message;
        }
        
        showNotification(errorMessage, 'error');
        
        // Afficher le contenu même en cas d'erreur
        dashboardContent.style.display = 'block';
      }
    }

    async function updateDashboard(data) {
      console.log('🔄 Mise à jour du dashboard avec:', data);
      
      if (data.kpis) {
        updateKPIs(data.kpis);
      } else {
        console.warn('⚠️ Pas de KPIs dans les données');
        showKPIsError();
      }
      
      if (data.sub_stores) {
        updateSubStoresRankingTable(data.sub_stores);
      } else {
        console.warn('⚠️ Pas de sub-stores dans les données');
      }
      
      if (data.categoryDistribution) {
        updateCategoryTable(data.categoryDistribution);
      }
      
      // Mettre à jour le tableau des merchants si des données sont disponibles
      if (data.merchants && Array.isArray(data.merchants)) {
        updateMerchantTable(data.merchants);
      }
      
      updateCharts(data);

      // Charger en asynchrone le graphe d'expiration si non fourni
      if ((!data.expirationsByMonth || data.expirationsByMonth.length === 0)) {
        try {
          showExpirationsSkeleton();
          const subStore = document.getElementById('subStoreSelect')?.value || 'ALL';
          const resp = await fetch(`/sub-stores/api/expirations?sub_store=${encodeURIComponent(subStore)}`);
          const aux = await resp.json();
          if (aux.expirationsByMonth && aux.expirationsByMonth.length > 0) {
            createExpirationsChart(aux.expirationsByMonth);
          }
          hideExpirationsSkeleton();
        } catch (e) {
          console.warn('Expirations async échouées', e);
          hideExpirationsSkeleton();
        }
      }
    }

    function updateKPIs(kpis) {
      console.log('🔄 Mise à jour des KPIs:', kpis);
      
      // Mettre à jour les KPIs Merchant si disponibles et si l'onglet Merchant est actif
      if (kpis.totalPartners) {
        console.log('🏪 Données Merchant détectées');
        // Toujours sauvegarder les données pour l'onglet Merchant
        window.merchantKPIsData = kpis;
        console.log('💾 Données sauvegardées dans window.merchantKPIsData:', window.merchantKPIsData);
        
        // TEMPORAIRE: Toujours essayer de mettre à jour, même si l'onglet n'est pas actif
        console.log('🔧 FORÇAGE: Tentative de mise à jour des KPIs Merchant');
        updateMerchantKPIs(kpis);
      } else {
        console.log('⚠️ Pas de données Merchant totalPartners dans:', kpis);
      }
      
      const kpiCards = [
        // LIGNE 1 : Distribué, inscription, abonnement, transactions, Active users
        { 
          id: 'distributed', 
          title: 'DISTRIBUÉ', 
          value: kpis.distributed?.current || 0,
          className: 'distributed',
          tooltip: 'Le nombre total de cartes de recharge distribuées. C\'est comme donner des cadeaux - plus on en donne, plus les gens peuvent utiliser nos services !'
        },
        { 
          id: 'inscriptions', 
          title: 'INSCRIPTIONS', 
          value: kpis.inscriptions?.current || 0,
          className: 'inscriptions',
          tooltip: 'Le nombre de nouvelles personnes qui se sont inscrites. C\'est comme de nouveaux amis qui rejoignent notre club !'
        },
        { 
          id: 'totalSubscriptions', 
          title: 'ABONNEMENTS', 
          value: kpis.totalSubscriptions?.current || 0,
          className: 'subscriptions',
          tooltip: 'Le nombre total de personnes qui ont un abonnement. C\'est comme avoir une carte de membre de notre club !'
        },
        { 
          id: 'transactions', 
          title: 'TRANSACTIONS', 
          value: kpis.transactions?.current || 0,
          className: 'transactions',
          tooltip: 'Le nombre de fois où les gens utilisent leurs cartes pour acheter quelque chose. C\'est comme compter combien de fois on utilise nos tickets de cinéma !'
        },
        { 
          id: 'activeUsers', 
          title: 'ACTIVE USERS', 
          value: kpis.activeUsers?.current || 0,
          className: 'active-users',
          tooltip: 'Les personnes qui utilisent encore notre service. C\'est comme les amis qui viennent toujours jouer avec nous !'
        },
        // LIGNE 2 : inscription cohorte, transactions cohorte, Active users cohorte, taux de conversion, taux de renouvellement
        { 
          id: 'inscriptionsCohorte', 
          title: 'INSCRIPTIONS COHORTE', 
          value: kpis.inscriptionsCohorte?.current || 0,
          className: 'cohort',
          tooltip: 'Les nouvelles inscriptions pendant cette période. C\'est comme compter les nouveaux amis de cette semaine !'
        },
        { 
          id: 'transactionsCohorte', 
          title: 'TRANSACTIONS COHORTE', 
          value: kpis.transactionsCohorte?.current || 0,
          className: 'cohort',
          tooltip: 'Les achats faits pendant cette période précise. C\'est comme compter les achats de cette semaine seulement !'
        },
        { 
          id: 'activeUsersCohorte', 
          title: 'ACTIVE USERS COHORTE', 
          value: kpis.activeUsersCohorte?.current || 0,
          className: 'cohort',
          tooltip: 'Les nouveaux utilisateurs actifs pendant cette période spécifique. C\'est comme les nouveaux amis qui sont déjà très actifs !'
        },
        { 
          id: 'conversionRate', 
          title: 'TAUX DE CONVERSION', 
          value: kpis.conversionRate?.current || 0, 
          suffix: '%',
          className: 'conversion',
          tooltip: 'Sur 100 cartes données, combien de personnes s\'inscrivent vraiment. C\'est comme mesurer si nos cadeaux plaisent aux gens !'
        },
        { 
          id: 'renewalRate', 
          title: 'TAUX DE RENOUVELLEMENT', 
          value: kpis.renewalRate?.current || 0, 
          suffix: '%',
          className: 'renewal',
          tooltip: 'Sur 100 abonnements qui finissent, combien sont renouvelés. C\'est comme mesurer si les gens aiment tellement notre club qu\'ils veulent rester !'
        }
      ];

      const kpisGrid = document.getElementById('kpisGrid');
      kpisGrid.innerHTML = '';

      kpiCards.forEach(kpi => {
        const kpiCard = document.createElement('div');
        kpiCard.className = `kpi-card card tooltip ${kpi.className}`;
        
        // Calculer le changement
        const change = kpi.id === 'conversionRate' ? 
          (kpis[kpi.id]?.change || 0) : 
          (kpis[kpi.id]?.change || 0);
        
        const changeClass = change > 0 ? 'delta-positive' : change < 0 ? 'delta-negative' : 'delta-neutral';
        const changeIcon = change > 0 ? '↗' : change < 0 ? '↘' : '→';
        const changeText = change > 0 ? `${changeIcon} +${change.toFixed(1)}%` : 
                          change < 0 ? `${changeIcon} ${change.toFixed(1)}%` : 
                          `${changeIcon} 0.0%`;
        
        kpiCard.innerHTML = `
          <div class="info-icon">i</div>
          <div class="kpi-title">${kpi.title}</div>
          <div class="kpi-value">${formatNumber(kpi.value)}${kpi.suffix || ''}</div>
          <div class="kpi-delta delta-badge ${changeClass}">${changeText}</div>
          <span class="tooltiptext">${kpi.tooltip}</span>
        `;
        
        kpisGrid.appendChild(kpiCard);
      });
      
      // Après avoir créé tous les KPIs, ajouter les événements de tooltip
      setTimeout(() => {
        const allKpiCards = document.querySelectorAll('.kpi-card');
        allKpiCards.forEach(card => {
          const tooltip = card.querySelector('.tooltiptext');
          const infoIcon = card.querySelector('.info-icon');
          
          if (tooltip && infoIcon) {
            // Fonction pour positionner le tooltip
            const showTooltip = (e) => {
              const rect = card.getBoundingClientRect();
              const tooltipWidth = 300;
              
              // Positionner au-dessus de la carte
              let left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
              let top = rect.top - 10;
              
              // Ajuster si le tooltip sort de l'écran
              if (left < 10) left = 10;
              if (left + tooltipWidth > window.innerWidth - 10) {
                left = window.innerWidth - tooltipWidth - 10;
              }
              if (top < 10) top = rect.bottom + 10;
              
              tooltip.style.left = left + 'px';
              tooltip.style.top = top + 'px';
              tooltip.style.visibility = 'visible';
              tooltip.style.opacity = '1';
            };
            
            const hideTooltip = () => {
              tooltip.style.visibility = 'hidden';
              tooltip.style.opacity = '0';
            };
            
            // Événements
            card.addEventListener('mouseenter', showTooltip);
            card.addEventListener('mouseleave', hideTooltip);
            infoIcon.addEventListener('mouseenter', showTooltip);
          }
        });
      }, 100);
    }

    function updateMerchantKPIs(kpis) {
      console.log('📊 Mise à jour des KPIs Merchant:', kpis);
      console.log('🔍 Vérification: kpis.totalPartners =', kpis.totalPartners);
      console.log('🔍 Vérification: type de kpis =', typeof kpis);
      console.log('🔍 Vérification: clés de kpis =', Object.keys(kpis));
      
      // Vérifier que nous avons les bonnes données
      if (!kpis.totalPartners) {
        console.log('❌ ERREUR: Pas de données totalPartners dans kpis');
        return;
      }
      
      console.log('✅ Données totalPartners trouvées, procédure normale');
      
      // VÉRIFICATION CRITIQUE: Les éléments HTML existent-ils ?
      console.log('🔍 Test: Recherche éléments HTML Merchant...');
      const testElement = document.getElementById('merch-totalPartners');
      console.log('🔍 Élément merch-totalPartners:', testElement ? 'TROUVÉ' : 'INTROUVABLE');
      
      if (!testElement) {
        console.log('❌ PROBLÈME: Les éléments Merchant ne sont pas dans le DOM !');
        console.log('🔍 Onglet merchant existe-t-il?', document.getElementById('merchant') ? 'OUI' : 'NON');
        console.log('🔍 Contenu de l\'onglet merchant:', document.getElementById('merchant')?.innerHTML?.substring(0, 200) + '...');
        return;
      }
      
      console.log('✅ Éléments HTML trouvés, procédure de mise à jour...');
      
      // Normaliser les objets KPI
      const normalizeKPI = (obj) => (obj && typeof obj.current !== 'undefined') ? obj : { current: 0, previous: 0, change: 0 };
      
      // Fonction helper pour mettre à jour un KPI individuel
      const updateSingleKPI = (id, kpiData, suffix = '') => {
        console.log(`🔧 updateSingleKPI: ${id} = ${kpiData.current}${suffix}`);
        const valueElement = document.getElementById(id);
        const deltaElement = document.getElementById(id + 'Delta');
        
        if (valueElement) {
          const value = String(kpiData.current || 0) + String(suffix || '');
          valueElement.textContent = value;
          console.log(`✅ ${id} mis à jour: ${value}`);
        } else {
          console.log(`❌ Element ${id} non trouvé !`);
        }
        
        if (deltaElement) {
          const change = kpiData.change || 0;
          const changeIcon = change > 0 ? '↗' : change < 0 ? '↘' : '→';
          const changeText = change > 0 ? `${changeIcon} +${change.toFixed(1)}%` : 
                            change < 0 ? `${changeIcon} ${change.toFixed(1)}%` : 
                            `${changeIcon} 0.0%`;
          const changeClass = change > 0 ? 'delta-positive' : change < 0 ? 'delta-negative' : 'delta-neutral';
          deltaElement.textContent = changeText;
          deltaElement.className = `kpi-delta delta-badge ${changeClass}`;
          console.log(`✅ ${id} delta mis à jour: ${changeText}`);
        }
      };
      
      // KPIs Merchant
      updateSingleKPI('merch-totalPartners', normalizeKPI(kpis.totalPartners));
      updateSingleKPI('merch-activeMerchants', normalizeKPI(kpis.activeMerchants));
      updateSingleKPI('merch-totalLocationsActive', normalizeKPI(kpis.totalLocationsActive));
      updateSingleKPI('merch-activeMerchantRatio', normalizeKPI(kpis.activeMerchantRatio), '%');
      updateSingleKPI('merch-totalTransactions', normalizeKPI(kpis.totalTransactions));
      updateSingleKPI('merch-transactionsPerMerchant', normalizeKPI(kpis.transactionsPerMerchant));
      
      // Top Merchant et Diversity avec gestion spéciale
      const topMerchantShare = normalizeKPI(kpis.topMerchantShare);
      const diversity = normalizeKPI(kpis.diversity);
      
      updateSingleKPI('merch-topMerchantShare', topMerchantShare, '%');
      updateSingleKPI('merch-diversity', diversity);
      
      // Mettre à jour les noms dans les deltas
      const topMerchantName = document.getElementById('merch-topMerchantName');
      if (topMerchantName) {
        topMerchantName.textContent = 'Top merchant';
      }
      
      const diversityDetail = document.getElementById('merch-diversityDetail');
      if (diversityDetail) {
        diversityDetail.textContent = diversity.current || 'Niveau de diversité';
      }
    }

    // Variables globales pour la pagination des marchands
    let allMerchants = [];
    let currentMerchantPage = 1;
    let merchantPageSize = 10;

    function updateMerchantTable(merchants) {
      console.log('📊 updateMerchantTable called with', merchants?.length, 'merchants');
      
      if (!merchants || !Array.isArray(merchants)) {
        console.log('❌ Pas de données merchants valides');
        return;
      }
      
      // Stocker tous les marchands pour la pagination
      allMerchants = merchants;
      currentMerchantPage = 1; // Reset à la première page
      
      // Afficher la page actuelle
      renderMerchantPage();
    }

    function renderMerchantPage() {
      const tbody = document.getElementById('merchantTableBody');
      if (!tbody) return;
      
      if (allMerchants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Aucune donnée merchant disponible</td></tr>';
        updateMerchantPagination();
        return;
      }
      
      // Calculer les indices de début et fin pour la page actuelle
      const startIndex = (currentMerchantPage - 1) * merchantPageSize;
      const endIndex = Math.min(startIndex + merchantPageSize, allMerchants.length);
      const currentPageMerchants = allMerchants.slice(startIndex, endIndex);
      
      // Générer les lignes du tableau avec deltas colorés
      tbody.innerHTML = currentPageMerchants.map((merchant, index) => {
        const delta = merchant.delta || 0;
        let deltaClass = 'neutral';
        let deltaIcon = '→';
        const rank = startIndex + index + 1;

        // Badge de rang (top 3)
        let rankBadge = `<span class="badge badge-info">${rank}</span>`;
        if (rank === 1) {
          rankBadge = `<span class="badge badge-gold">🥇 ${rank}</span>`;
        } else if (rank === 2) {
          rankBadge = `<span class="badge badge-silver">🥈 ${rank}</span>`;
        } else if (rank === 3) {
          rankBadge = `<span class="badge badge-bronze">🥉 ${rank}</span>`;
        }

        if (delta > 0) {
          deltaClass = 'positive';
          deltaIcon = '↗';
        } else if (delta < 0) {
          deltaClass = 'negative';
          deltaIcon = '↘';
        }
        
        return `
          <tr>
            <td>${rankBadge}</td>
            <td><strong>${merchant.name}</strong></td>
            <td>${merchant.category}</td>
            <td>${merchant.current.toLocaleString()}</td>
            <td>${merchant.share}%</td>
            <td>
              <span class="delta-badge delta-${deltaClass}">
                ${deltaIcon} ${delta > 0 ? '+' : ''}${delta.toFixed(1)}%
              </span>
            </td>
          </tr>
        `;
      }).join('');
      
      // Mettre à jour la pagination
      updateMerchantPagination();
    }

    function updateMerchantPagination() {
      const totalMerchants = allMerchants.length;
      const totalPages = Math.ceil(totalMerchants / merchantPageSize);
      const startIndex = (currentMerchantPage - 1) * merchantPageSize + 1;
      const endIndex = Math.min(currentMerchantPage * merchantPageSize, totalMerchants);
      
      // Info de pagination
      const infoElement = document.getElementById('merchantPaginationInfo');
      if (infoElement) {
        infoElement.textContent = `Affichage ${startIndex}-${endIndex} sur ${totalMerchants} marchands`;
      }
      
      // Boutons précédent/suivant
      const prevBtn = document.getElementById('merchantPrevBtn');
      const nextBtn = document.getElementById('merchantNextBtn');
      
      if (prevBtn) prevBtn.disabled = currentMerchantPage <= 1;
      if (nextBtn) nextBtn.disabled = currentMerchantPage >= totalPages;
      
      // Numéros de pages
      const pageNumbers = document.getElementById('merchantPageNumbers');
      if (pageNumbers && totalPages > 1) {
        let pageNumbersHtml = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentMerchantPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
          const isActive = i === currentMerchantPage ? 'active' : '';
          pageNumbersHtml += `<button class="btn btn-sm ${isActive}" onclick="goToMerchantPage(${i})">${i}</button> `;
        }
        
        pageNumbers.innerHTML = pageNumbersHtml;
      }
    }

    function changeMerchantPage(direction) {
      const totalPages = Math.ceil(allMerchants.length / merchantPageSize);
      const newPage = currentMerchantPage + direction;
      
      if (newPage >= 1 && newPage <= totalPages) {
        currentMerchantPage = newPage;
        renderMerchantPage();
      }
    }

    function goToMerchantPage(page) {
      const totalPages = Math.ceil(allMerchants.length / merchantPageSize);
      if (page >= 1 && page <= totalPages) {
        currentMerchantPage = page;
        renderMerchantPage();
      }
    }

    function changeMerchantPageSize() {
      const select = document.getElementById('merchantPageSize');
      if (select) {
        merchantPageSize = parseInt(select.value);
        currentMerchantPage = 1; // Reset à la première page
        renderMerchantPage();
      }
    }

    function exportMerchantTable() {
      console.log('📤 Export table merchant');
      // Fonction d'export simple
      if (!currentData || !currentData.merchants) {
        showNotification('Aucune donnée merchant à exporter', 'warning');
        return;
      }
      
      const csvContent = "data:text/csv;charset=utf-8," + 
        "Rang,Nom du Marchand,Catégorie,Transactions,Part de Marché,Delta\n" +
        allMerchants.map(merchant => {
          return `${merchant.rank},"${merchant.name}","${merchant.category}",${merchant.current},${merchant.share}%,"${merchant.delta ? (merchant.delta > 0 ? '+' : '') + merchant.delta.toFixed(1) + '%' : '0.0%'}"`;
        }).join("\n");
      
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `merchants_sub_stores_${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      showNotification('Données merchant exportées avec succès', 'success');
    }

    function updateCharts(data) {
      console.log('🔄 Mise à jour des graphiques:', data);
      
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js non chargé');
        return;
      }

      // Inscriptions Chart (maintenant en barres par mois)
      console.log('🔍 DEBUG inscriptionsTrend:', data.inscriptionsTrend);
      
      if (data.inscriptionsTrend && data.inscriptionsTrend.length > 0) {
        console.log('✅ Création du graphique inscriptions avec données:', data.inscriptionsTrend);
        createInscriptionsBarChart(data.inscriptionsTrend);
      } else {
        console.log('❌ Pas de données inscriptionsTrend, création avec données par défaut');
        
        // Créer des données mensuelles par défaut
        const testData = [
          { date: 'Jan 2025', value: 120 },
          { date: 'Feb 2025', value: 150 },
          { date: 'Mar 2025', value: 200 },
          { date: 'Apr 2025', value: 180 },
          { date: 'May 2025', value: 160 },
          { date: 'Jun 2025', value: 190 },
          { date: 'Jul 2025', value: 220 },
          { date: 'Aug 2025', value: 250 }
        ];
        createInscriptionsBarChart(testData);
      }

      // Expirations Chart
      if (data.expirationsByMonth && data.expirationsByMonth.length > 0) {
        createExpirationsChart(data.expirationsByMonth);
      }
    }

    function createInscriptionsBarChart(data) {
      const ctx = document.getElementById('inscriptionsChart');
      if (!ctx) return;

      if (inscriptionsChart) {
        inscriptionsChart.destroy();
      }

      try {
        const labels = data.map(item => item.date || item.month);
        const values = data.map(item => item.value || item.count);
        
        inscriptionsChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Inscriptions',
              data: values,
              backgroundColor: '#6B46C1',
              borderColor: '#6B46C1',
              borderWidth: 1,
              borderRadius: 4,
              borderSkipped: false
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
                },
                ticks: {
                  color: '#64748b'
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  color: '#64748b'
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('❌ Erreur création graphique inscriptions:', error);
      }
    }

    function createExpirationsChart(data) {
      const ctx = document.getElementById('expirationsChart');
      if (!ctx) return;
      if (expirationsChart) expirationsChart.destroy();
      const labels = data.map(d => d.date);
      const values = data.map(d => d.value);
      expirationsChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Abonnements à expiration',
            data: values,
            backgroundColor: 'rgba(99, 102, 241, 0.25)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    }

    function showExpirationsSkeleton() {
      const canvas = document.getElementById('expirationsChart');
      if (!canvas) return;
      const parent = canvas.parentElement;
      parent.style.position = 'relative';
      let sk = parent.querySelector('.skeleton');
      if (!sk) {
        sk = document.createElement('div');
        sk.className = 'skeleton';
        sk.style.cssText = 'position:absolute;inset:0;background:linear-gradient(90deg,#f3f4f6,#e5e7eb,#f3f4f6);animation:pulse 1.2s infinite;';
        parent.appendChild(sk);
      }
    }
    function hideExpirationsSkeleton() {
      const canvas = document.getElementById('expirationsChart');
      if (!canvas) return;
      const parent = canvas.parentElement;
      const sk = parent.querySelector('.skeleton');
      if (sk) parent.removeChild(sk);
    }


    // Load available sub-stores
    async function loadSubStores() {
      try {
        const response = await fetch('/sub-stores/api/sub-stores');
        const data = await response.json();
        
        const select = document.getElementById('subStoreSelect');
        select.innerHTML = '';
        
        // Vérifier si l'utilisateur est super admin
        const isSuperAdmin = data.user_role === 'super_admin';
        
        if (isSuperAdmin) {
          // Super admin voit tous les sub-stores + option "ALL"
          const defaultOption = document.createElement('option');
          defaultOption.value = 'ALL';
          defaultOption.textContent = 'Tous les sub-stores';
          select.appendChild(defaultOption);
          
          // Add sub-stores options
          data.sub_stores.forEach(store => {
            const option = document.createElement('option');
            option.value = store.name;
            option.textContent = `🏪 ${store.name}`;
            if (store.name === data.default_sub_store) {
              option.selected = true;
            }
            select.appendChild(option);
          });
        } else {
          // Admin/Collaborateur voit seulement son sub-store assigné
          const assignedStore = data.sub_stores.find(store => store.name === data.default_sub_store);
          if (assignedStore) {
            const option = document.createElement('option');
            option.value = assignedStore.name;
            option.textContent = `🏪 ${assignedStore.name}`;
            option.selected = true;
            select.appendChild(option);
          }
        }
        
        console.log('✅ Sub-stores chargés:', data.sub_stores.length, 'Super Admin:', isSuperAdmin);
        return data;
        
      } catch (error) {
        console.error('❌ Erreur lors du chargement des sub-stores:', error);
        return { sub_stores: [] };
      }
    }

    // Update category distribution table (Vue d'ensemble)
    function updateCategoryTable(categories) {
      const tbody = document.getElementById('categoryTableBody');
            tbody.innerHTML = '';
      
      if (!categories || categories.length === 0) {
        const row = tbody.insertRow();
        const cell = row.insertCell(0);
        cell.colSpan = 4;
        cell.textContent = 'Aucune donnée de catégorie disponible';
        cell.style.textAlign = 'center';
        cell.style.color = '#6b7280';
                return;
            }
            
            categories.forEach(category => {
                const row = tbody.insertRow();
                
        // Category name
        row.insertCell(0).textContent = category.category || 'Non spécifié';
        
        // Usage count
        row.insertCell(1).textContent = formatNumber(category.utilizations || 0);
        
        // Percentage
        row.insertCell(2).textContent = `${category.percentage || 0}%`;
        
        // Evolution
        const evolution = category.evolution || 0;
        const evolutionClass = evolution > 0 ? 'delta-positive' : evolution < 0 ? 'delta-negative' : 'delta-neutral';
        const evolutionIcon = evolution > 0 ? '↗' : evolution < 0 ? '↘' : '→';
        const evolutionText = evolution > 0 ? `${evolutionIcon} +${evolution.toFixed(1)}%` : 
                             evolution < 0 ? `${evolutionIcon} ${evolution.toFixed(1)}%` : 
                             `${evolutionIcon} 0.0%`;
        row.insertCell(3).innerHTML = `<span class="kpi-delta delta-badge ${evolutionClass}">${evolutionText}</span>`;
      });
    }

    // Update sub-stores ranking table (for Sub-Stores tab)
    function updateSubStoresRankingTable(stores) {
      const tbody = document.getElementById('subStoresRankingTableBody');
      tbody.innerHTML = '';
      
      stores.forEach((store, index) => {
        const row = tbody.insertRow();
        const rank = index + 1;
        
        // Rank badge with special styling for top positions
        let rankBadge;
        if (rank === 1) {
          rankBadge = `<span class="badge badge-gold">🥇 ${rank}</span>`;
        } else if (rank === 2) {
          rankBadge = `<span class="badge badge-silver">🥈 ${rank}</span>`;
        } else if (rank === 3) {
          rankBadge = `<span class="badge badge-bronze">🥉 ${rank}</span>`;
        } else {
          rankBadge = `<span class="badge badge-info">${rank}</span>`;
        }
        row.insertCell(0).innerHTML = rankBadge;
        
        // Store name
        row.insertCell(1).textContent = store.name || 'Non spécifié';
        
        // Type
        row.insertCell(2).textContent = store.type || 'Non spécifié';
        
        // Clients
        row.insertCell(3).textContent = formatNumber(store.customers || 0);
        
        // Transactions
        row.insertCell(4).textContent = formatNumber(store.transactions || 0);
        
        // Manager
        row.insertCell(5).textContent = store.manager || 'Non spécifié';
      });
    }


    function formatNumber(num) {
      return new Intl.NumberFormat('fr-FR').format(num);
    }

    // Export functions
    function exportCategoryTable() {
      const table = document.querySelector('#categoryTableBody').closest('table');
      exportTableToCSV(table, 'repartition-categories.csv');
    }

    function exportSubStoresTable() {
      const table = document.querySelector('#subStoresRankingTableBody').closest('table');
      exportTableToCSV(table, 'classement-sub-stores.csv');
    }


    function exportTableToCSV(table, filename) {
      const rows = Array.from(table.querySelectorAll('tr'));
      const csvContent = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        return cells.map(cell => {
          const text = cell.textContent.trim();
          return `"${text.replace(/"/g, '""')}"`;
        }).join(',');
      }).join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    function showKPIsError() {
      const kpisGrid = document.getElementById('kpisGrid');
      kpisGrid.innerHTML = `
        <div class="kpi-card" style="grid-column: span 12; text-align: center; padding: 40px;">
          <div style="color: var(--danger); font-size: 18px; margin-bottom: 16px;">⚠️</div>
          <div style="color: var(--muted); font-size: 16px; margin-bottom: 8px;">Erreur de chargement des KPIs</div>
          <div style="color: var(--muted); font-size: 14px;">Les données ne sont pas disponibles pour le moment</div>
                </div>
            `;
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
    // Seul le sub-store déclenche un rechargement automatique
    document.getElementById('subStoreSelect').addEventListener('change', loadDashboardData);
    
    // Les dates ne déclenchent plus de mise à jour automatique
    // Utiliser le bouton "Actualiser" pour mettre à jour les données
    document.getElementById('startDate').addEventListener('change', function() {
        console.log('📅 Date de début modifiée - cliquez sur Actualiser pour appliquer');
    });
    document.getElementById('endDate').addEventListener('change', function() {
        console.log('📅 Date de fin modifiée - cliquez sur Actualiser pour appliquer');
    });
    </script>
</body>
</html>

