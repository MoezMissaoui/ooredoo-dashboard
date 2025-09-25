{{-- Scripts JavaScript pour le Dashboard Eklektik Intégré --}}

<script>
// Variables globales pour les graphiques Eklektik
let eklektikCharts = {};

// Charger les statistiques Eklektik
async function loadEklektikStats() {
  try {
    console.log('🚀 [EKLEKTIK STATS] Début du chargement des statistiques');
    showEklektikStatsLoading();
    
    const operator = document.getElementById('eklektik-stats-operator-filter').value;
    const period = document.getElementById('eklektik-stats-period-filter').value;
    
    let startDate, endDate;
    
    if (period === 'custom') {
      startDate = document.getElementById('eklektik-start-date').value;
      endDate = document.getElementById('eklektik-end-date').value;
    } else {
      const days = parseInt(period);
      endDate = new Date().toISOString().split('T')[0];
      startDate = new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    }
    
    console.log(`📅 [EKLEKTIK STATS] Période: ${startDate} à ${endDate}, Opérateur: ${operator}`);
    
    // Charger toutes les données en parallèle avec les nouvelles APIs
    const [kpis, overviewChart, revenueEvolution, revenueDistribution] = await Promise.all([
      fetchEklektikStats('/api/eklektik-dashboard/kpis', { start_date: startDate, end_date: endDate, operator }),
      fetchEklektikStats('/api/eklektik-dashboard/overview-chart', { start_date: startDate, end_date: endDate, operator }),
      fetchEklektikStats('/api/eklektik-dashboard/revenue-evolution', { start_date: startDate, end_date: endDate, operator }),
      fetchEklektikStats('/api/eklektik-dashboard/revenue-distribution', { start_date: startDate, end_date: endDate })
    ]);
    
    // Mettre à jour l'affichage
    updateEklektikStatsDisplay(kpis.data);
    
    // Créer les graphiques
    await createEklektikStatsCharts({
      overviewChart: overviewChart.data,
      revenueEvolution: revenueEvolution.data,
      revenueDistribution: revenueDistribution.data
    });
    
    console.log('✅ [EKLEKTIK STATS] Statistiques chargées avec succès');
    
  } catch (error) {
    console.error('❌ [EKLEKTIK STATS] Erreur lors du chargement:', error);
    showEklektikStatsError(error.message);
  }
}

// Fonction utilitaire pour récupérer les statistiques
async function fetchEklektikStats(endpoint, params) {
  const url = new URL(endpoint, window.location.origin);
  Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
  
  const response = await fetch(url, {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return await response.json();
}

// Afficher l'état de chargement des statistiques
function showEklektikStatsLoading() {
  const elements = [
    'eklektik-revenue-ttc', 'eklektik-revenue-ht', 'eklektik-ca-bigdeal', 'eklektik-bigdeal-percentage',
    'eklektik-new-subscriptions', 'eklektik-unsubscriptions', 'eklektik-simchurn', 'eklektik-facturation'
  ];
  
  elements.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = 'Loading...';
    }
  });
}

// Mettre à jour l'affichage des statistiques
function updateEklektikStatsDisplay(data) {
  // Mettre à jour les KPIs avec les nouvelles données
  if (data.total_revenue_ttc !== undefined) {
    document.getElementById('eklektik-revenue-ttc').textContent = 
      new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND', maximumFractionDigits: 0 }).format(data.total_revenue_ttc);
    document.getElementById('eklektik-revenue-ttc-delta').textContent = 'Revenus TTC';
  }
  
  if (data.total_revenue_ht !== undefined) {
    document.getElementById('eklektik-revenue-ht').textContent = 
      new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND', maximumFractionDigits: 0 }).format(data.total_revenue_ht);
    document.getElementById('eklektik-revenue-ht-delta').textContent = 'Revenus HT';
  }
  
  if (data.total_ca_bigdeal !== undefined) {
    document.getElementById('eklektik-ca-bigdeal').textContent = 
      new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND', maximumFractionDigits: 0 }).format(data.total_ca_bigdeal);
    document.getElementById('eklektik-ca-bigdeal-delta').textContent = 'CA BigDeal';
  }
  
  if (data.total_revenue_ht > 0) {
    const percentage = (data.total_ca_bigdeal / data.total_revenue_ht) * 100;
    document.getElementById('eklektik-bigdeal-percentage').textContent = Math.round(percentage) + '%';
    document.getElementById('eklektik-bigdeal-percentage-delta').textContent = 'Part BigDeal';
  }
  
  if (data.total_new_subscriptions !== undefined) {
    document.getElementById('eklektik-new-subscriptions').textContent = data.total_new_subscriptions;
    document.getElementById('eklektik-new-subscriptions-delta').textContent = 'nouveaux';
  }
  
  if (data.total_unsubscriptions !== undefined) {
    document.getElementById('eklektik-unsubscriptions').textContent = data.total_unsubscriptions;
    document.getElementById('eklektik-unsubscriptions-delta').textContent = 'désabonnements';
  }
  
  if (data.total_simchurn !== undefined) {
    document.getElementById('eklektik-simchurn').textContent = data.total_simchurn;
    document.getElementById('eklektik-simchurn-delta').textContent = 'simchurn';
  }
  
  if (data.total_facturation !== undefined) {
    document.getElementById('eklektik-facturation').textContent = data.total_facturation;
    document.getElementById('eklektik-facturation-delta').textContent = 'facturations';
  }
}

// Créer les graphiques des statistiques Eklektik
async function createEklektikStatsCharts(data) {
  const { overviewChart, revenueEvolution, revenueDistribution } = data;
  
  console.log('🎨 [CHARTS] Création des graphiques avec données:', data);
  
  // Détruire les graphiques existants
  console.log('🗑️ [CHARTS] Destruction des graphiques existants:', Object.keys(eklektikCharts));
  Object.values(eklektikCharts).forEach(chart => {
    if (chart) {
      console.log('🗑️ [CHARTS] Destruction d\'un graphique');
      chart.destroy();
    }
  });
  eklektikCharts = {};
  
  console.log('📊 [CHARTS] Création des nouveaux graphiques...');
  
  // Graphique multi-axes principal (Vue d'ensemble)
  createEklektikOverviewChart(overviewChart?.chart);
  
  // Graphique d'évolution des revenus
  createEklektikRevenueEvolutionChart(revenueEvolution?.chart);
  
  // Graphique de répartition par opérateur
  createEklektikOperatorsDistributionChart(revenueDistribution?.pie_chart);
  
  // Graphique CA par partenaire
  createEklektikCAPartnersChart(revenueDistribution?.bar_chart);
  
  // Afficher les statistiques par opérateur
  displayEklektikOperatorsStats(revenueDistribution?.distribution);
}

// Graphique multi-axes principal (Vue d'ensemble)
function createEklektikOverviewChart(chartData) {
  const ctx = document.getElementById('eklektik-overview-chart');
  if (!ctx || !chartData) {
    console.log('❌ [OVERVIEW CHART] Pas de données ou contexte manquant');
    return;
  }
  
  // Détruire le graphique existant s'il existe
  if (eklektikCharts.overview) {
    eklektikCharts.overview.destroy();
  }
  
  eklektikCharts.overview = new Chart(ctx, {
    type: 'bar',
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true,
            text: 'Date'
          }
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
          grid: {
            drawOnChartArea: false,
          }
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
          grid: {
            drawOnChartArea: false,
          }
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
          grid: {
            drawOnChartArea: false,
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            usePointStyle: true,
            padding: 20
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              
              if (context.dataset.yAxisID === 'y-revenue') {
                label += new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed.y * 1000);
              } else if (context.dataset.yAxisID === 'y-active') {
                label += new Intl.NumberFormat('fr-FR').format(context.parsed.y);
              } else if (context.dataset.yAxisID === 'y-rate') {
                label += context.parsed.y.toFixed(2) + '%';
              }
              
              return label;
            }
          }
        }
      }
    }
  });
}

// Graphique d'évolution des revenus
function createEklektikRevenueEvolutionChart(chartData) {
  const ctx = document.getElementById('eklektik-revenue-evolution-chart');
  if (!ctx || !chartData) {
    console.log('❌ [REVENUE EVOLUTION CHART] Pas de données ou contexte manquant');
    return;
  }
  
  // Détruire le graphique existant s'il existe
  if (eklektikCharts.revenueEvolution) {
    eklektikCharts.revenueEvolution.destroy();
  }
  
  eklektikCharts.revenueEvolution = new Chart(ctx, {
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

// Graphique de répartition par opérateur
function createEklektikOperatorsDistributionChart(chartData) {
  const ctx = document.getElementById('eklektik-operators-distribution-chart');
  if (!ctx || !chartData) {
    console.log('❌ [OPERATORS DISTRIBUTION CHART] Pas de données ou contexte manquant');
    return;
  }
  
  // Détruire le graphique existant s'il existe
  if (eklektikCharts.operatorsDistribution) {
    eklektikCharts.operatorsDistribution.destroy();
  }
  
  eklektikCharts.operatorsDistribution = new Chart(ctx, {
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

// Graphique CA par partenaire
function createEklektikCAPartnersChart(chartData) {
  const ctx = document.getElementById('eklektik-ca-partners-chart');
  if (!ctx || !chartData) {
    console.log('❌ [CA PARTNERS CHART] Pas de données ou contexte manquant');
    return;
  }
  
  // Détruire le graphique existant s'il existe
  if (eklektikCharts.caPartners) {
    eklektikCharts.caPartners.destroy();
  }
  
  eklektikCharts.caPartners = new Chart(ctx, {
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

// Afficher les statistiques par opérateur
function displayEklektikOperatorsStats(distribution) {
  const container = document.getElementById('eklektik-operators-stats');
  if (!container || !distribution) {
    console.log('❌ [OPERATORS STATS] Pas de données ou conteneur manquant');
    return;
  }
  
  let html = '';
  
  for (const [operator, data] of Object.entries(distribution)) {
    html += `
      <div class="card mb-2" style="border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
        <div class="card-body" style="padding: 0;">
          <h6 class="card-title" style="margin: 0 0 8px 0; font-weight: 600; color: var(--brand-dark);">${operator}</h6>
          <div style="font-size: 12px; line-height: 1.4;">
            <div><strong>Revenus TTC:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.revenue_ttc)}</div>
            <div><strong>Revenus HT:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.revenue_ht)}</div>
            <div><strong>CA BigDeal:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_bigdeal)}</div>
            <div><strong>CA Opérateur:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_operateur)}</div>
            <div><strong>CA Agrégateur:</strong> ${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(data.ca_agregateur)}</div>
          </div>
        </div>
      </div>
    `;
  }
  
  container.innerHTML = html;
}

// Afficher l'erreur des statistiques
function showEklektikStatsError(message) {
  const elements = [
    'eklektik-revenue-ttc', 'eklektik-revenue-ht', 'eklektik-ca-bigdeal', 'eklektik-bigdeal-percentage',
    'eklektik-new-subscriptions', 'eklektik-unsubscriptions', 'eklektik-simchurn', 'eklektik-facturation'
  ];
  
  elements.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = 'Erreur';
    }
  });
  
  console.error('❌ [EKLEKTIK STATS] Erreur:', message);
}

// Vérifier le statut de synchronisation Eklektik
async function checkEklektikSyncStatus() {
  try {
    const response = await fetch('/api/eklektik-dashboard/sync-status');
    const data = await response.json();
    
    if (data.success) {
      const status = data.data;
      const statusColor = status.status === 'healthy' ? 'success' : 
                         status.status === 'warning' ? 'warning' : 'danger';
      
      const lastSync = status.last_sync ? 
        new Date(status.last_sync).toLocaleString('fr-FR') : 'Jamais';
      
      const message = `
        <div class="alert alert-${statusColor}" style="margin: 10px 0;">
          <h6><i class="fas fa-info-circle"></i> Statut: ${status.status.toUpperCase()}</h6>
          <p><strong>Dernière synchronisation:</strong> ${lastSync}</p>
          <p><strong>Total enregistrements:</strong> ${status.total_records}</p>
          <p><strong>Opérateurs avec données:</strong> ${Object.values(status.operators_status).filter(op => op.has_data).length}/3</p>
        </div>
      `;
      
      // Afficher dans une modal ou alert
      alert(`Statut Eklektik: ${status.status.toUpperCase()}\nDernière sync: ${lastSync}\nEnregistrements: ${status.total_records}`);
    }
  } catch (error) {
    console.error('❌ [EKLEKTIK SYNC] Erreur lors de la vérification du statut:', error);
    alert('Erreur lors de la vérification du statut de synchronisation');
  }
}

// Vider le cache Eklektik
async function clearEklektikCache() {
  try {
    const response = await fetch('/api/eklektik-dashboard/clear-cache', { method: 'POST' });
    const data = await response.json();
    
    if (data.success) {
      alert('Cache vidé avec succès!');
      loadEklektikStats(); // Recharger les données
    } else {
      alert('Erreur lors du vidage du cache: ' + data.message);
    }
  } catch (error) {
    console.error('❌ [EKLEKTIK CACHE] Erreur lors du vidage du cache:', error);
    alert('Erreur lors du vidage du cache');
  }
}

// Exporter les statistiques Eklektik
function exportEklektikStats() {
  // TODO: Implémenter l'export des statistiques
  alert('Fonction d\'export en cours de développement');
}
</script>
