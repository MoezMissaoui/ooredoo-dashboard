@extends('layouts.app')

@section('title', 'Dashboard Eklektik Intégré')

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
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="start_date">Date de Début</label>
                            <input type="date" class="form-control" id="start_date" 
                                   value="{{ \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date">Date de Fin</label>
                            <input type="date" class="form-control" id="end_date" 
                                   value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="operator_filter">Opérateur</label>
                            <select class="form-control" id="operator_filter">
                                <option value="ALL">Tous les opérateurs</option>
                                <option value="TT">TT</option>
                                <option value="Orange">Orange</option>
                                <option value="Taraji">Taraji</option>
                            </select>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-line"></i>
                                        Évolution des Revenus
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenue-evolution-chart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-pie"></i>
                                        Répartition par Opérateur
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="operators-distribution-chart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-bar"></i>
                                        CA par Partenaire
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="ca-partners-chart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-mobile-alt"></i>
                                        Statistiques par Opérateur
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="operators-stats">
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
            }
        })
        .catch(error => console.error('Erreur KPIs:', error));
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
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                        }
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
                }
            }
        }
    });
}

function createOperatorsDistributionChart(chartData) {
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
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                        }
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
    const statusColor = status.status === 'healthy' ? 'success' : 
                       status.status === 'warning' ? 'warning' : 'danger';
    
    const lastSync = status.last_sync ? 
        new Date(status.last_sync).toLocaleString('fr-FR') : 'Jamais';
    
    const html = `
        <div class="alert alert-${statusColor}">
            <h6><i class="fas fa-info-circle"></i> Statut: ${status.status.toUpperCase()}</h6>
            <p><strong>Dernière synchronisation:</strong> ${lastSync}</p>
            <p><strong>Total enregistrements:</strong> ${status.total_records}</p>
            <p><strong>Opérateurs avec données:</strong> ${Object.values(status.operators_status).filter(op => op.has_data).length}/3</p>
        </div>
    `;
    
    document.getElementById('sync-status').innerHTML = html;
}

function refreshDashboard() {
    loadDashboard();
    loadSyncStatus();
}

function clearCache() {
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
</script>
@endsection

