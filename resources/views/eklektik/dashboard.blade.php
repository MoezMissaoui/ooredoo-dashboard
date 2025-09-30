@extends('layouts.app')

@section('title', 'Dashboard Eklektik Intégré')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<style>
    /* Responsive Design pour Dashboard Eklektik */
    @media (max-width: 1200px) {
        .col-lg-3 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    
    @media (max-width: 768px) {
        .col-lg-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 15px;
        }
        
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 15px;
        }
    }
    
    @media (max-width: 600px) {
        .col-lg-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 15px;
        }
        
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }
        
        .card-header .card-tools {
            margin-top: 10px;
        }
        
        .card-header .card-tools .btn {
            margin-bottom: 5px;
            width: 100%;
        }
        
        .info-box {
            margin-bottom: 15px;
        }
        
        .info-box-icon {
            width: 60px;
            height: 60px;
            font-size: 24px;
        }
        
        .info-box-content {
            padding-left: 70px;
        }
        
        .info-box-text {
            font-size: 12px;
        }
        
        .info-box-number {
            font-size: 18px;
        }
    }
    
    @media (max-width: 600px) {
        .col-lg-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 15px;
        }
        
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }
        
        .container-fluid {
            padding: 10px;
        }
        
        .card {
            margin-bottom: 15px;
        }
        
        .card-header {
            padding: 10px 15px;
        }
        
        .card-title {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .row.mb-4 {
            margin-bottom: 20px !important;
        }
        
        .form-control {
            font-size: 14px;
            padding: 8px 10px;
        }
        
        .btn {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        .info-box {
            padding: 10px;
        }
        
        .info-box-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }
        
        .info-box-content {
            padding-left: 60px;
        }
        
        .info-box-text {
            font-size: 11px;
        }
        
        .info-box-number {
            font-size: 16px;
        }
        
        canvas {
            max-height: 250px !important;
        }
    }
    
    @media (max-width: 480px) {
        .col-lg-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }
        
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 15px;
        }
        
        .container-fluid {
            padding: 5px;
        }
        
        .card-header {
            padding: 8px 10px;
        }
        
        .card-title {
            font-size: 14px;
        }
        
        .card-body {
            padding: 10px;
        }
        
        .form-control {
            font-size: 13px;
            padding: 6px 8px;
        }
        
        .btn {
            font-size: 11px;
            padding: 6px 10px;
        }
        
        .info-box {
            padding: 8px;
        }
        
        .info-box-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        
        .info-box-content {
            padding-left: 50px;
        }
        
        .info-box-text {
            font-size: 10px;
        }
        
        .info-box-number {
            font-size: 14px;
        }
        
        canvas {
            max-height: 200px !important;
        }
        
        .alert {
            font-size: 12px;
            padding: 8px 10px;
        }
    }
    
    /* Amélioration des graphiques pour mobile */
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
    }
    
    @media (max-width: 480px) {
        .chart-container {
            height: 200px;
        }
    }
    
    /* Amélioration des cartes d'opérateurs */
    .operators-stats .card {
        margin-bottom: 10px;
    }
    
    @media (max-width: 600px) {
        .operators-stats .card {
            margin-bottom: 8px;
        }
        
        .operators-stats .card-body {
            padding: 10px;
        }
        
        .operators-stats .card-title {
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .operators-stats .card-text {
            font-size: 12px;
            line-height: 1.4;
        }
    }
    
    @media (max-width: 480px) {
        .operators-stats .card-body {
            padding: 8px;
        }
        
        .operators-stats .card-title {
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .operators-stats .card-text {
            font-size: 11px;
            line-height: 1.3;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Dashboard Eklektik Intégré
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button class="btn btn-sm btn-info" onclick="clearCache()">
                            <i class="fas fa-trash"></i> Vider Cache
                        </button>
                        <button class="btn btn-sm btn-success" onclick="exportData()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <label for="start_date">Date de Début</label>
                            <input type="date" class="form-control" id="start_date" 
                                   value="{{ \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <label for="end_date">Date de Fin</label>
                            <input type="date" class="form-control" id="end_date" 
                                   value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <label for="operator_filter">Opérateur</label>
                            <select class="form-control" id="operator_filter">
                                <option value="ALL">Tous les opérateurs</option>
                                <option value="TT">TT</option>
                                <option value="Orange">Orange</option>
                                <option value="Taraji">Taraji</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary form-control" onclick="loadDashboard()">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                        </div>
                    </div>

                    <!-- Statut de Synchronisation -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-sync-alt"></i>
                                        Statut de Synchronisation
                                    </h5>
                                </div>
                                <div class="card-body" id="sync-status">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs Principaux -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-euro-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Revenus TTC</span>
                                    <span class="info-box-number" id="kpi-revenue-ttc">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-chart-bar"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Revenus HT</span>
                                    <span class="info-box-number" id="kpi-revenue-ht">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-handshake"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">CA BigDeal</span>
                                    <span class="info-box-number" id="kpi-ca-bigdeal">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-percentage"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">% BigDeal</span>
                                    <span class="info-box-number" id="kpi-bigdeal-percentage">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques -->
                    <div class="row">
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-line"></i>
                                        Évolution des Revenus
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenue-evolution-chart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-pie"></i>
                                        Répartition par Opérateur
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="operators-distribution-chart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-bar"></i>
                                        CA par Partenaire
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="ca-partners-chart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-mobile-alt"></i>
                                        Statistiques par Opérateur
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="operators-stats" class="operators-stats">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let revenueEvolutionChart = null;
let operatorsDistributionChart = null;
let caPartnersChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    loadSyncStatus();
});

function loadDashboard() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const operator = document.getElementById('operator_filter').value;

    // Charger les KPIs
    loadKPIs(startDate, endDate, operator);
    
    // Charger l'évolution des revenus
    loadRevenueEvolution(startDate, endDate, operator);
    
    // Charger la répartition par opérateur
    loadOperatorsDistribution(startDate, endDate);
}

function loadKPIs(startDate, endDate, operator) {
    // Afficher l'état de chargement
    document.getElementById('kpi-revenue-ttc').textContent = 'Chargement...';
    document.getElementById('kpi-revenue-ht').textContent = 'Chargement...';
    document.getElementById('kpi-ca-bigdeal').textContent = 'Chargement...';
    document.getElementById('kpi-bigdeal-percentage').textContent = 'Chargement...';
    
    fetch(`/api/eklektik-dashboard/kpis?start_date=${startDate}&end_date=${endDate}&operator=${operator}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('kpi-revenue-ttc').textContent = 
                    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.data.total_revenue_ttc);
                document.getElementById('kpi-revenue-ht').textContent = 
                    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.data.total_revenue_ht);
                document.getElementById('kpi-ca-bigdeal').textContent = 
                    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.data.total_ca_bigdeal);
                
                const percentage = data.data.total_revenue_ht > 0 ? 
                    (data.data.total_ca_bigdeal / data.data.total_revenue_ht) * 100 : 0;
                document.getElementById('kpi-bigdeal-percentage').textContent = 
                    percentage.toFixed(2) + '%';
            } else {
                // Afficher une erreur si les données ne sont pas disponibles
                document.getElementById('kpi-revenue-ttc').textContent = 'Erreur';
                document.getElementById('kpi-revenue-ht').textContent = 'Erreur';
                document.getElementById('kpi-ca-bigdeal').textContent = 'Erreur';
                document.getElementById('kpi-bigdeal-percentage').textContent = 'Erreur';
            }
        })
        .catch(error => {
            console.error('Erreur KPIs:', error);
            // Afficher une erreur en cas d'échec de la requête
            document.getElementById('kpi-revenue-ttc').textContent = 'Erreur';
            document.getElementById('kpi-revenue-ht').textContent = 'Erreur';
            document.getElementById('kpi-ca-bigdeal').textContent = 'Erreur';
            document.getElementById('kpi-bigdeal-percentage').textContent = 'Erreur';
        });
}

function loadRevenueEvolution(startDate, endDate, operator) {
    fetch(`/api/eklektik-dashboard/revenue-evolution?start_date=${startDate}&end_date=${endDate}&operator=${operator}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createRevenueEvolutionChart(data.data.chart);
            }
        })
        .catch(error => console.error('Erreur évolution revenus:', error));
}

function loadOperatorsDistribution(startDate, endDate) {
    fetch(`/api/eklektik-dashboard/revenue-distribution?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createOperatorsDistributionChart(data.data.pie_chart);
                createCAPartnersChart(data.data.bar_chart);
                displayOperatorsStats(data.data.distribution);
            }
        })
        .catch(error => console.error('Erreur répartition opérateurs:', error));
}

function loadSyncStatus() {
    fetch('/api/eklektik-dashboard/sync-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySyncStatus(data.data);
            }
        })
        .catch(error => console.error('Erreur statut sync:', error));
}

function createRevenueEvolutionChart(chartData) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé');
        return;
    }
    
    const ctx = document.getElementById('revenue-evolution-chart').getContext('2d');
    
    if (revenueEvolutionChart) {
        revenueEvolutionChart.destroy();
    }
    
    revenueEvolutionChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD',
                        displayFormats: {
                            day: 'DD/MM',
                            month: 'MM/YYYY'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Montant (TND)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed.y);
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}

function createOperatorsDistributionChart(chartData) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé');
        return;
    }
    
    const ctx = document.getElementById('operators-distribution-chart').getContext('2d');
    
    if (operatorsDistributionChart) {
        operatorsDistributionChart.destroy();
    }
    
    operatorsDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + 
                                new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed);
                        }
                    }
                }
            }
        }
    });
}

function createCAPartnersChart(chartData) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé');
        return;
    }
    
    const ctx = document.getElementById('ca-partners-chart').getContext('2d');
    
    if (caPartnersChart) {
        caPartnersChart.destroy();
    }
    
    caPartnersChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Partenaires'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Montant (TND)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed.y);
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}

function displayOperatorsStats(distribution) {
    let html = '';
    
    for (const [operator, data] of Object.entries(distribution)) {
        html += `
            <div class="card mb-2">
                <div class="card-body">
                    <h6 class="card-title">${operator}</h6>
                    <p class="card-text">
                        <strong>Revenus TTC:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.revenue_ttc)}<br>
                        <strong>Revenus HT:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.revenue_ht)}<br>
                        <strong>CA BigDeal:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_bigdeal)}<br>
                        <strong>CA Opérateur:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_operateur)}<br>
                        <strong>CA Agrégateur:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_agregateur)}
                    </p>
                </div>
            </div>
        `;
    }
    
    document.getElementById('operators-stats').innerHTML = html;
}

function displaySyncStatus(status) {
    // Vérifier que status existe et a les propriétés nécessaires
    if (!status || typeof status !== 'object') {
        console.error('Status invalide:', status);
        return;
    }
    
    const statusValue = status.status || 'unknown';
    const statusColor = statusValue === 'healthy' ? 'success' : 
                       statusValue === 'warning' ? 'warning' : 'danger';
    
    const lastSync = status.last_sync ? 
        new Date(status.last_sync).toLocaleString('fr-FR') : 'Jamais';
    
    const totalRecords = status.total_records || 0;
    const operatorsStatus = status.operators_status || {};
    const operatorsWithData = Object.values(operatorsStatus).filter(op => op && op.has_data).length;
    
    const html = `
        <div class="alert alert-${statusColor}">
            <h6><i class="fas fa-info-circle"></i> Statut: ${statusValue.toUpperCase()}</h6>
            <p><strong>Dernière synchronisation:</strong> ${lastSync}</p>
            <p><strong>Total enregistrements:</strong> ${totalRecords}</p>
            <p><strong>Opérateurs avec données:</strong> ${operatorsWithData}/3</p>
        </div>
    `;
    
    document.getElementById('sync-status').innerHTML = html;
}

function refreshDashboard() {
    loadDashboard();
    loadSyncStatus();
}

function clearCache() {
    if (confirm('Êtes-vous sûr de vouloir vider le cache ?')) {
        fetch('/api/eklektik-dashboard/clear-cache', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cache vidé avec succès!');
                    refreshDashboard();
                } else {
                    alert('Erreur lors du vidage du cache: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur vidage cache:', error);
                alert('Erreur lors du vidage du cache');
            });
    }
}

function exportData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const operator = document.getElementById('operator_filter').value;
    
    // Créer un lien de téléchargement pour l'export
    const exportUrl = `/api/eklektik-dashboard/export?start_date=${startDate}&end_date=${endDate}&operator=${operator}`;
    window.open(exportUrl, '_blank');
}

// Charger le dashboard au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que Chart.js est chargé
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js n\'est pas chargé. Les graphiques ne fonctionneront pas.');
        // Afficher un message d'erreur à l'utilisateur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.innerHTML = '<strong>Erreur:</strong> Chart.js n\'est pas chargé. Veuillez recharger la page.';
        document.querySelector('.card-body').insertBefore(errorDiv, document.querySelector('.card-body').firstChild);
    } else {
        console.log('✅ Chart.js chargé avec succès');
    }
    
    // Charger les données initiales
    loadDashboard();
    loadSyncStatus();
});
</script>
@endsection

