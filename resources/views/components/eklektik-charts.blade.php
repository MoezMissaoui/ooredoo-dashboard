{{-- Vue d'ensemble Multi-Axes --}}
<div class="grid">
    <div class="card chart-card full-width">
        <div class="chart-title">
            📊 Vue d'ensemble Multi-Axes
            <div style="float: right;">
                <select id="eklektik-operator-select" class="enhanced-select" style="font-size: 14px;">
                    <option value="ALL">Tous les opérateurs</option>
                    <option value="Orange">Orange</option>
                    <option value="TT">TT</option>
                    <option value="Ooredoo">Ooredoo</option>
                    <option value="Taraji">Taraji</option>
                </select>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="eklektik-overview-chart"></canvas>
        </div>
    </div>
</div>

{{-- Grille des graphiques --}}
<div class="grid">
    {{-- Graphique Évolution CA BigDeal par Opérateur --}}
        <div class="card chart-card" style="grid-column: span 6;">
            <div class="chart-title">💰 Revenus par Opérateur + CA BigDeal</div>
        <div class="chart-container">
            <canvas id="eklektik-revenue-evolution-chart"></canvas>
        </div>
    </div>

    {{-- Graphique Répartition par Opérateur --}}
    <div class="card chart-card" style="grid-column: span 6;">
        <div class="chart-title">📱 Répartition par Opérateur</div>
        <div class="chart-container">
            <canvas id="eklektik-operators-distribution-chart"></canvas>
        </div>
    </div>
</div>

<div class="grid">
    {{-- Graphique Évolution Active Subs et Abonnements Facturés --}}
    <div class="card chart-card full-width">
        <div class="chart-title">📈 Évolution Active Subs et Abonnements Facturés</div>
        <div class="chart-container">
            <canvas id="eklektik-ca-partners-chart"></canvas>
        </div>
    </div>
</div>


<script>
// Configuration Chart.js optimisée pour éliminer le sautillement
(function() {
    'use strict';
    
    // Configuration spécifique pour les graphiques Eklektik (pas de modification globale)
    console.log('🎨 Configuration des graphiques Eklektik...');
    
    // Palette de couleurs cohérente avec "Distribution by Category"
    const eklektikColors = {
        primary: '#E30613',      // Rouge principal
        secondary: '#3b82f6',    // Bleu
        success: '#10b981',      // Vert
        warning: '#f59e0b',      // Orange/Jaune
        purple: '#8b5cf6',       // Violet
        cyan: '#06b6d4',         // Cyan
        orange: '#f97316',       // Orange vif
        gray: '#64748b',         // Gris
        // Versions avec transparence
        primaryAlpha: 'rgba(227, 6, 19, 0.8)',
        secondaryAlpha: 'rgba(59, 130, 246, 0.8)',
        successAlpha: 'rgba(16, 185, 129, 0.8)',
        warningAlpha: 'rgba(245, 158, 11, 0.8)',
        purpleAlpha: 'rgba(139, 92, 246, 0.8)',
        cyanAlpha: 'rgba(6, 182, 212, 0.8)',
        orangeAlpha: 'rgba(249, 115, 22, 0.8)',
        grayAlpha: 'rgba(100, 116, 139, 0.8)'
    };
    
    // Palette de couleurs pour les graphiques multi-opérateurs
    const operatorColors = [
        eklektikColors.primary,      // Rouge - Orange
        eklektikColors.secondary,    // Bleu - TT  
        eklektikColors.success,      // Vert - Ooredoo
        eklektikColors.warning,      // Orange - Taraji
        eklektikColors.purple,       // Violet - autres
        eklektikColors.cyan,         // Cyan
        eklektikColors.orange,       // Orange vif
        eklektikColors.gray          // Gris
    ];
    
    // Fonction pour mettre à jour les KPIs Eklektik
    function updateEklektikKPIs(data) {
        console.log('📊 Mise à jour des KPIs Eklektik:', data);
        
        // Revenus TTC
        if (data.total_revenue_ttc !== undefined) {
            const element = document.getElementById('eklektik-revenue-ttc');
            const deltaElement = document.getElementById('eklektik-revenue-ttc-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.total_revenue_ttc);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Revenus TTC';
            }
        }
        
        // Revenus HT
        if (data.total_revenue_ht !== undefined) {
            const element = document.getElementById('eklektik-revenue-ht');
            const deltaElement = document.getElementById('eklektik-revenue-ht-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.total_revenue_ht);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Revenus HT';
            }
        }
        
        // CA BigDeal
        if (data.total_ca_bigdeal !== undefined) {
            const element = document.getElementById('eklektik-ca-bigdeal');
            const deltaElement = document.getElementById('eklektik-ca-bigdeal-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.total_ca_bigdeal);
            }
            if (deltaElement) {
                deltaElement.textContent = 'CA BigDeal';
            }
        }
        
        // Active Subs
        if (data.total_active_subscribers !== undefined) {
            const element = document.getElementById('eklektik-active-subs');
            const deltaElement = document.getElementById('eklektik-active-subs-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR').format(data.total_active_subscribers);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Abonnés Actifs';
            }
        }
        
        // Nouveaux abonnements
        if (data.total_new_subscriptions !== undefined) {
            const element = document.getElementById('eklektik-new-subscriptions');
            const deltaElement = document.getElementById('eklektik-new-subscriptions-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR').format(data.total_new_subscriptions);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Nouveaux abonnements';
            }
        }
        
        // Désabonnements
        if (data.total_unsubscriptions !== undefined) {
            const element = document.getElementById('eklektik-unsubscriptions');
            const deltaElement = document.getElementById('eklektik-unsubscriptions-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR').format(data.total_unsubscriptions);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Désabonnements';
            }
        }
        
        // Simchurn
        if (data.total_simchurn !== undefined) {
            const element = document.getElementById('eklektik-simchurn');
            const deltaElement = document.getElementById('eklektik-simchurn-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR').format(data.total_simchurn);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Simchurn';
            }
        }
        
        // Abonnements Facturés (total au lieu de moyenne)
        if (data.total_facturation !== undefined) {
            const element = document.getElementById('eklektik-facturation');
            const deltaElement = document.getElementById('eklektik-facturation-delta');
            if (element) {
                element.textContent = new Intl.NumberFormat('fr-FR').format(data.total_facturation);
            }
            if (deltaElement) {
                deltaElement.textContent = 'Abonnements Facturés';
            }
        }
        
        console.log('✅ KPIs Eklektik mis à jour');
    }
    
    // Variables globales pour les graphiques
    let eklektikCharts = {};
    let isCreatingCharts = false;
    
    // Fonction générique pour créer un graphique de manière sécurisée
    function createChartSafely(canvasId, chartKey, chartData, chartType, options, delay = 0) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !chartData) {
            console.log(`❌ Canvas ou données manquantes pour ${chartKey}`);
            return;
        }
        
        const createChart = () => {
            try {
                // Destruction complète de toute instance existante
                if (eklektikCharts[chartKey]) {
                    console.log(`🗑️ Destruction du graphique existant ${chartKey}`);
                    eklektikCharts[chartKey].destroy();
                    eklektikCharts[chartKey] = null;
                }
                
                // Nettoyer le canvas complètement
                if (ctx.chart) {
                    console.log(`🗑️ Nettoyage du canvas ${chartKey}`);
                    ctx.chart.destroy();
                    ctx.chart = null;
                }
                
                // Récupérer toutes les instances Chart.js sur ce canvas
                const instances = Chart.getChart(ctx);
                if (instances) {
                    console.log(`🗑️ Destruction de l'instance Chart.js existante pour ${chartKey}`);
                    instances.destroy();
                }
                
                // Attendre un peu pour s'assurer que le canvas est libre
                setTimeout(() => {
                    eklektikCharts[chartKey] = new Chart(ctx, {
                        type: chartType,
                        data: chartData,
                        options: options
                    });
                    console.log(`✅ Graphique ${chartKey} créé avec succès`);
                }, 10);
                
            } catch (error) {
                console.error(`❌ Erreur lors de la création du graphique ${chartKey}:`, error);
                // Tentative de récupération
                setTimeout(() => {
                    try {
                        const retryInstances = Chart.getChart(ctx);
                        if (retryInstances) {
                            retryInstances.destroy();
                        }
                        eklektikCharts[chartKey] = new Chart(ctx, {
                            type: chartType,
                            data: chartData,
                            options: options
                        });
                        console.log(`✅ Graphique ${chartKey} recréé avec succès après erreur`);
                    } catch (retryError) {
                        console.error(`❌ Impossible de recréer le graphique ${chartKey}:`, retryError);
                    }
                }, 100);
            }
        };
        
        if (delay > 0) {
            setTimeout(createChart, delay);
        } else {
            createChart();
        }
    }
    
    // Fonction pour créer le graphique multi-axes
    function createEklektikOverviewChart(chartData) {
        const ctx = document.getElementById('eklektik-overview-chart');
        if (!ctx || !chartData) {
            console.log('❌ Canvas ou données manquantes pour overview chart');
            return;
        }
        
        // Vérifier si le graphique existe déjà
        if (eklektikCharts.overview) {
            console.log('⚠️ Graphique overview existe déjà, destruction...');
            try {
                eklektikCharts.overview.destroy();
            } catch (e) {
                console.log('Graphique overview déjà détruit');
            }
            eklektikCharts.overview = null;
        }
        
        // Vérifier que le canvas est libre
        if (ctx.chart) {
            console.log('⚠️ Canvas overview déjà utilisé, nettoyage...');
            try {
                ctx.chart.destroy();
            } catch (e) {
                console.log('Canvas chart déjà détruit');
            }
            ctx.chart = null;
        }
        
        // Attendre un peu pour s'assurer que le canvas est libre
        setTimeout(() => {
            createOverviewChartInternal(ctx, chartData);
        }, 50);
    }
    
    function createOverviewChartInternal(ctx, chartData) {
        
        const options = {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            animations: { duration: 0 },
            elements: {
                point: { hoverRadius: 0 },
                line: { tension: 0 }
            },
            plugins: {
                legend: { animation: false },
                tooltip: { animation: false }
            },
            scales: {
                x: {
                    display: true,
                    title: { display: true, text: 'Date' }
                },
                'y-revenue': {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue TTC (K TND)',
                        color: 'rgb(54, 162, 235)'
                    },
                    ticks: {
                        color: 'rgb(54, 162, 235)',
                        callback: function(value) {
                            return value + 'K';
                        }
                    },
                    grid: { drawOnChartArea: false }
                },
                'y-active': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Active Sub',
                        color: 'rgb(255, 99, 132)'
                    },
                    ticks: {
                        color: 'rgb(255, 99, 132)',
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR').format(value);
                        }
                    },
                    grid: { drawOnChartArea: false }
                },
                'y-rate': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Taux Facturation / Part BigDeal (%)',
                        color: 'rgb(75, 192, 192)'
                    },
                    ticks: {
                        color: 'rgb(75, 192, 192)',
                        callback: function(value) {
                            return value.toFixed(1) + '%';
                        }
                    },
                    grid: { drawOnChartArea: false }
                }
            }
        };
        
        try {
            // Vérifier une dernière fois que le canvas est libre
            if (ctx.chart) {
                console.log('⚠️ Canvas encore utilisé, destruction forcée...');
                ctx.chart.destroy();
                ctx.chart = null;
            }
            
            eklektikCharts.overview = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: options
            });
            console.log('✅ Graphique overview créé avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de la création du graphique overview:', error);
            // Essayer de nettoyer et recréer
            if (ctx.chart) {
                try {
                    ctx.chart.destroy();
                    ctx.chart = null;
                } catch (e) {
                    console.log('Erreur lors du nettoyage forcé:', e);
                }
            }
        }
    }
    
    // Fonction pour créer le graphique d'évolution des revenus
    function createEklektikRevenueEvolutionChart(chartData) {
        const ctx = document.getElementById('eklektik-revenue-evolution-chart');
        if (!ctx || !chartData) return;
        
        if (eklektikCharts.revenueEvolution) {
            try {
                eklektikCharts.revenueEvolution.destroy();
            } catch (e) {
                console.log('Graphique revenueEvolution déjà détruit');
            }
            eklektikCharts.revenueEvolution = null;
        }
        
        // Vérifier que le canvas est libre
        if (ctx.chart) {
            try {
                ctx.chart.destroy();
            } catch (e) {
                console.log('Canvas chart déjà détruit');
            }
            ctx.chart = null;
        }
        
        try {
            eklektikCharts.revenueEvolution = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    animations: { duration: 0 },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Graphique revenueEvolution créé avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de la création du graphique revenueEvolution:', error);
        }
    }
    
    // Fonction pour créer le graphique de répartition par opérateur
    function createEklektikOperatorsDistributionChart(chartData) {
        const ctx = document.getElementById('eklektik-operators-distribution-chart');
        if (!ctx || !chartData) return;
        
        if (eklektikCharts.operatorsDistribution) {
            try {
                eklektikCharts.operatorsDistribution.destroy();
            } catch (e) {
                console.log('Graphique operatorsDistribution déjà détruit');
            }
            eklektikCharts.operatorsDistribution = null;
        }
        
        // Vérifier que le canvas est libre
        if (ctx.chart) {
            try {
                ctx.chart.destroy();
            } catch (e) {
                console.log('Canvas chart déjà détruit');
            }
            ctx.chart = null;
        }
        
        try {
            eklektikCharts.operatorsDistribution = new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    animations: { duration: 0 }
                }
            });
            console.log('✅ Graphique operatorsDistribution créé avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de la création du graphique operatorsDistribution:', error);
        }
    }
    
    // Fonction pour créer le graphique CA par partenaire
    function createEklektikCAPartnersChart(chartData) {
        const ctx = document.getElementById('eklektik-ca-partners-chart');
        if (!ctx || !chartData) return;
        
        if (eklektikCharts.caPartners) {
            try {
                eklektikCharts.caPartners.destroy();
            } catch (e) {
                console.log('Graphique caPartners déjà détruit');
            }
            eklektikCharts.caPartners = null;
        }
        
        // Vérifier que le canvas est libre
        if (ctx.chart) {
            try {
                ctx.chart.destroy();
            } catch (e) {
                console.log('Canvas chart déjà détruit');
            }
            ctx.chart = null;
        }
        
        try {
            eklektikCharts.caPartners = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    animations: { duration: 0 },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Graphique caPartners créé avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de la création du graphique caPartners:', error);
        }
    }
    
    // Fonction pour charger les données
    async function loadEklektikCharts() {
        // Éviter les créations multiples
        if (isCreatingCharts) {
            console.log('⚠️ Création de graphiques déjà en cours, ignoré');
            return;
        }
        
        isCreatingCharts = true;
        
        try {
            const operator = document.getElementById('eklektik-operator-select')?.value || 'ALL';
            
            // Utiliser les dates de la section "Sélection des Périodes"
            const startDateElement = document.getElementById('start-date');
            const endDateElement = document.getElementById('end-date');
            
            let startDateStr, endDateStr;
            if (startDateElement && endDateElement && startDateElement.value && endDateElement.value) {
                startDateStr = startDateElement.value;
                endDateStr = endDateElement.value;
                console.log('📅 Utilisation des dates de la Sélection des Périodes');
            } else {
                // Fallback: utiliser les 30 derniers jours par défaut
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - 30);
                startDateStr = startDate.toISOString().split('T')[0];
                endDateStr = endDate.toISOString().split('T')[0];
                console.log('📅 Utilisation des dates par défaut (30 derniers jours)');
            }
            
            console.log(`🔄 Chargement des vraies données Eklektik pour ${startDateStr} - ${endDateStr}...`);
            
            // Nettoyer d'abord tous les graphiques existants
            clearEklektikCharts();
            
            // Charger les vraies données de l'API Eklektik
            const [kpis, overviewChart, revenueEvolution, revenueDistribution, subsEvolution] = await Promise.all([
                fetchData('/api/eklektik-dashboard/kpis', { start_date: startDateStr, end_date: endDateStr, operator }),
                fetchData('/api/eklektik-dashboard/overview-chart', { start_date: startDateStr, end_date: endDateStr, operator }),
                fetchData('/api/eklektik-dashboard/revenue-evolution', { start_date: startDateStr, end_date: endDateStr, operator }),
                fetchData('/api/eklektik-dashboard/revenue-distribution', { start_date: startDateStr, end_date: endDateStr }),
                fetchData('/api/eklektik-dashboard/subs-evolution', { start_date: startDateStr, end_date: endDateStr, operator })
            ]);
            
            console.log('📊 Données Eklektik chargées:', { kpis, overviewChart, revenueEvolution, revenueDistribution, subsEvolution });
            
            // Vérifier si toutes les données sont valides
            if (!kpis?.success || !overviewChart?.success || !revenueEvolution?.success || !revenueDistribution?.success || !subsEvolution?.success) {
                console.error('❌ Certaines APIs ont échoué, abandon du chargement');
                throw new Error('API Error: Une ou plusieurs APIs ont échoué');
            }
            
            // Mettre à jour les KPIs
            if (kpis && kpis.data) {
                updateEklektikKPIs(kpis.data);
            }
            
            // Modifier les couleurs du graphique Overview avec la palette cohérente
            if (overviewChart.data?.chart?.datasets) {
                overviewChart.data.chart.datasets.forEach((dataset, index) => {
                    switch(dataset.label) {
                        case 'Active Sub':
                            dataset.backgroundColor = eklektikColors.primaryAlpha;
                            dataset.borderColor = eklektikColors.primary;
                            break;
                        case 'CA BigDeal':
                            dataset.backgroundColor = eklektikColors.warningAlpha;
                            dataset.borderColor = eklektikColors.warning;
                            break;
                        default:
                            dataset.backgroundColor = operatorColors[index % operatorColors.length] + '80';
                            dataset.borderColor = operatorColors[index % operatorColors.length];
                    }
                });
            }
            
            // Créer les graphiques avec les vraies données avec un délai pour éviter les conflits
            createChartSafely('eklektik-overview-chart', 'overview', overviewChart.data?.chart, 'bar', {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                animations: { 
                    duration: 0,
                    hover: { duration: 0 },
                    active: { duration: 0 }
                },
                elements: {
                    point: { hoverRadius: 4 },
                    line: { tension: 0 }
                },
                plugins: {
                    legend: { animation: false },
                    tooltip: { 
                        animation: false,
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    x: {
                        display: true,
                        title: { display: true, text: 'Date' }
                    },
                    'y-active': {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Active Subscribers',
                            color: eklektikColors.primary
                        },
                        ticks: {
                            color: eklektikColors.primary,
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR').format(value);
                            }
                        }
                    },
                    'y-bigdeal': {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'CA BigDeal (TND)',
                            color: eklektikColors.warning
                        },
                        ticks: {
                            color: eklektikColors.warning,
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                            }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }, 100);
            
            // Modifier les couleurs du graphique Revenue Evolution
            if (revenueEvolution.data?.chart?.datasets) {
                revenueEvolution.data.chart.datasets.forEach((dataset, index) => {
                    dataset.borderColor = operatorColors[index % operatorColors.length];
                    dataset.backgroundColor = operatorColors[index % operatorColors.length] + '20';
                    dataset.pointBackgroundColor = operatorColors[index % operatorColors.length];
                    dataset.pointBorderColor = operatorColors[index % operatorColors.length];
                });
            }
            
            createChartSafely('eklektik-revenue-evolution-chart', 'revenueEvolution', revenueEvolution.data?.chart, 'line', {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                animations: { 
                    duration: 0,
                    hover: { duration: 0 },
                    active: { duration: 0 }
                },
                elements: {
                    point: { hoverRadius: 4 },
                    line: { tension: 0.4 }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: { 
                        animation: false,
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Date: ' + tooltipItems[0].label;
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed.y);
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'CA BigDeal (TND)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(value);
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Période'
                        }
                    }
                }
            }, 200);
            
            // Modifier les couleurs du graphique Operators Distribution
            if (revenueDistribution.data?.pie_chart?.datasets?.[0]) {
                revenueDistribution.data.pie_chart.datasets[0].backgroundColor = operatorColors;
                revenueDistribution.data.pie_chart.datasets[0].borderColor = operatorColors;
                revenueDistribution.data.pie_chart.datasets[0].borderWidth = 2;
            }
            
            createChartSafely('eklektik-operators-distribution-chart', 'operatorsDistribution', revenueDistribution.data?.pie_chart, 'doughnut', {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                animations: { 
                    duration: 0,
                    hover: { duration: 0 },
                    active: { duration: 0 }
                },
                plugins: {
                    tooltip: { 
                        animation: false,
                        enabled: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                }
            }, 300);
            
            // Modifier les couleurs du graphique Subs Evolution
            if (subsEvolution.data?.chart?.datasets) {
                subsEvolution.data.chart.datasets.forEach((dataset, index) => {
                    switch(dataset.label) {
                        case 'Active Subs':
                            dataset.backgroundColor = eklektikColors.primaryAlpha;
                            dataset.borderColor = eklektikColors.primary;
                            break;
                        case 'Abonnements Facturés':
                            dataset.backgroundColor = eklektikColors.secondaryAlpha;
                            dataset.borderColor = eklektikColors.secondary;
                            break;
                        default:
                            dataset.backgroundColor = operatorColors[index % operatorColors.length] + '80';
                            dataset.borderColor = operatorColors[index % operatorColors.length];
                    }
                    dataset.borderWidth = 1;
                });
            }
            
            createChartSafely('eklektik-ca-partners-chart', 'subsEvolution', subsEvolution.data?.chart, 'line', {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                animations: { 
                    duration: 0,
                    hover: { duration: 0 },
                    active: { duration: 0 }
                },
                elements: {
                    point: { hoverRadius: 4 },
                    line: { tension: 0.4 }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: { 
                        animation: false,
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR').format(value);
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Période'
                        }
                    }
                }
            }, 400);
            // Ancien code CA Partners supprimé - remplacé par Subs Evolution
            
            
        } catch (error) {
            console.error('❌ Erreur lors du chargement des graphiques Eklektik:', error);
            console.log('🚫 Pas de fallback - affichage d\'un message d\'erreur');
            
            // Afficher un message d'erreur au lieu du fallback
            const containers = ['eklektik-overview-chart', 'eklektik-revenue-evolution-chart', 'eklektik-operators-distribution-chart', 'eklektik-ca-partners-chart'];
            containers.forEach(containerId => {
                const container = document.getElementById(containerId);
                if (container) {
                    container.parentElement.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: center; height: 200px; color: #dc3545;">
                            <div style="text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                                <div>Erreur lors du chargement des données Eklektik</div>
                                <div style="font-size: 12px; margin-top: 5px;">Vérifiez la synchronisation des données</div>
                            </div>
                        </div>
                    `;
                }
            });
        } finally {
            isCreatingCharts = false;
        }
    }
    
    // Fonction de test supprimée - utilisation exclusive des vraies APIs
    
    // Fonction pour récupérer les données
    async function fetchData(endpoint, params) {
        const url = new URL(endpoint, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    
    // Fonction pour vider les graphiques
    function clearEklektikCharts() {
        Object.entries(eklektikCharts).forEach(([key, chart]) => {
            if (chart) {
                try {
                    chart.destroy();
                    console.log(`✅ Graphique ${key} détruit`);
                } catch (e) {
                    console.log(`⚠️ Erreur lors de la destruction du graphique ${key}:`, e);
                }
            }
        });
        eklektikCharts = {};
        
        // Nettoyer aussi les canvas
        const canvasIds = [
            'eklektik-overview-chart',
            'eklektik-revenue-evolution-chart',
            'eklektik-operators-distribution-chart',
            'eklektik-ca-partners-chart'
        ];
        
        canvasIds.forEach(id => {
            const canvas = document.getElementById(id);
            if (canvas && canvas.chart) {
                try {
                    canvas.chart.destroy();
                    canvas.chart = null;
                } catch (e) {
                    console.log(`⚠️ Erreur lors du nettoyage du canvas ${id}:`, e);
                }
            }
        });
    }
    
    // Exposer les fonctions globalement
    window.loadEklektikCharts = loadEklektikCharts;
    window.clearEklektikCharts = clearEklektikCharts;
    
    // Charger les graphiques au démarrage
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(loadEklektikCharts, 100);
    });
    
    // Écouter les changements d'opérateur
    document.addEventListener('change', function(e) {
        if (e.target.id === 'eklektik-operator-select') {
            // Éviter les rechargements multiples
            if (!isCreatingCharts) {
                console.log('🔄 Changement d\'opérateur détecté, rechargement des graphiques...');
                loadEklektikCharts();
            }
        }
        
        // Écouter les changements de dates de la section principale
        if (e.target.id === 'start-date' || e.target.id === 'end-date') {
            if (!isCreatingCharts) {
                console.log('🔄 Changement de dates détecté, rechargement des graphiques Eklektik...');
                setTimeout(() => {
                    loadEklektikCharts();
                }, 500); // Petit délai pour s'assurer que les deux dates sont mises à jour
            }
        }
    });
})();
</script>
