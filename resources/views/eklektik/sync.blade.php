@extends('layouts.eklektik-config')

@section('title', 'Gestion des Synchronisations Eklektik')

@section('eklektik-content')
<div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sync-alt"></i>
                        Gestion des Synchronisations Eklektik
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Statistiques Générales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-database"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Enregistrements</span>
                                    <span class="info-box-number">{{ number_format($stats['total_records']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Dernière Sync</span>
                                    <span class="info-box-number">
                                        @if($stats['last_sync'])
                                            {{ \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() }}
                                        @else
                                            Jamais
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-calendar"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Période</span>
                                    <span class="info-box-number">
                                        @if($stats['date_range']['first'])
                                            {{ \Carbon\Carbon::parse($stats['date_range']['first'])->format('d/m/Y') }}
                                            -
                                            {{ \Carbon\Carbon::parse($stats['date_range']['last'])->format('d/m/Y') }}
                                        @else
                                            Aucune donnée
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-mobile-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Opérateurs</span>
                                    <span class="info-box-number">{{ count($stats['operators']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Synchronisation Manuelle -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-play"></i>
                                Synchronisation Manuelle
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="syncForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="start_date">Date de Début</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                   value="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="end_date">Date de Fin</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                   value="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="operator">Opérateur</label>
                                            <select class="form-control" id="operator" name="operator">
                                                <option value="ALL">Tous les opérateurs</option>
                                                <option value="TT">TT</option>
                                                <option value="Orange">Orange</option>
                                                <option value="Taraji">Taraji</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="syncBtn">
                                            <i class="fas fa-sync-alt"></i>
                                            Lancer la Synchronisation
                                        </button>
                                        <button type="button" class="btn btn-info" id="checkStatusBtn">
                                            <i class="fas fa-info-circle"></i>
                                            Vérifier le Statut
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Résultats de Synchronisation -->
                    <div class="card" id="syncResults" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Résultats de la Synchronisation
                            </h5>
                        </div>
                        <div class="card-body" id="syncResultsBody">
                            <!-- Contenu dynamique -->
                        </div>
                    </div>

                    <!-- Logs de Synchronisation -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-file-alt"></i>
                                Logs de Synchronisation
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <button type="button" class="btn btn-secondary" id="refreshLogsBtn">
                                    <i class="fas fa-refresh"></i>
                                    Actualiser les Logs
                                </button>
                            </div>
                            <pre id="logsContent" style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                                Chargement des logs...
                            </pre>
                        </div>
                    </div>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Synchronisation manuelle
    document.getElementById('syncForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const syncBtn = document.getElementById('syncBtn');
        const originalText = syncBtn.innerHTML;
        
        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronisation en cours...';
        syncBtn.disabled = true;
        
        const formData = new FormData(this);
        
        fetch('{{ route("admin.eklektik.sync") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSyncResults(data.results);
                showAlert('Synchronisation réussie!', 'success');
            } else {
                showAlert('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('Erreur: ' + error.message, 'error');
        })
        .finally(() => {
            syncBtn.innerHTML = originalText;
            syncBtn.disabled = false;
        });
    });
    
    // Vérifier le statut
    document.getElementById('checkStatusBtn').addEventListener('click', function() {
        fetch('{{ route("admin.eklektik.status") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus(data.status);
            } else {
                showAlert('Erreur lors de la vérification du statut', 'error');
            }
        });
    });
    
    // Actualiser les logs
    document.getElementById('refreshLogsBtn').addEventListener('click', function() {
        loadLogs();
    });
    
    // Charger les logs au démarrage
    loadLogs();
});

function showSyncResults(results) {
    const resultsDiv = document.getElementById('syncResults');
    const resultsBody = document.getElementById('syncResultsBody');
    
    let html = '<div class="row">';
    html += '<div class="col-12"><h6>Résumé de la Synchronisation</h6></div>';
    html += '<div class="col-md-6"><strong>Total synchronisé:</strong> ' + results.total_synced + ' enregistrements</div>';
    
    if (results.operators) {
        html += '<div class="col-12 mt-3"><h6>Détails par Opérateur</h6></div>';
        for (const [operator, data] of Object.entries(results.operators)) {
            html += '<div class="col-md-4">';
            html += '<div class="card">';
            html += '<div class="card-body">';
            html += '<h6>' + operator + '</h6>';
            html += '<p><strong>Synchronisé:</strong> ' + data.synced + ' enregistrements</p>';
            html += '<p><strong>Récupéré:</strong> ' + data.records + ' enregistrements</p>';
            if (data.offers) {
                html += '<h6>Offres:</h6>';
                for (const [offerId, offerData] of Object.entries(data.offers)) {
                    html += '<p>ID ' + offerId + ': ' + offerData.synced + ' sync / ' + offerData.records + ' récupérés</p>';
                }
            }
            html += '</div></div></div>';
        }
    }
    
    if (results.errors && results.errors.length > 0) {
        html += '<div class="col-12 mt-3"><h6>Erreurs</h6></div>';
        html += '<div class="col-12">';
        results.errors.forEach(error => {
            html += '<div class="alert alert-danger">' + error + '</div>';
        });
        html += '</div>';
    }
    
    html += '</div>';
    
    resultsBody.innerHTML = html;
    resultsDiv.style.display = 'block';
}

function showStatus(status) {
    let message = 'Statut des Synchronisations:\n\n';
    message += 'Dernière sync: ' + (status.last_sync ? new Date(status.last_sync).toLocaleString() : 'Jamais') + '\n';
    message += 'Sync récente: ' + (status.is_recent ? 'Oui' : 'Non') + '\n';
    message += 'Total enregistrements: ' + status.total_records + '\n\n';
    
    message += 'Statut par opérateur:\n';
    for (const [operator, data] of Object.entries(status.operators_status)) {
        message += operator + ': ' + (data.has_data ? 'Données disponibles' : 'Aucune donnée') + '\n';
        if (data.last_sync) {
            message += '  Dernière sync: ' + new Date(data.last_sync).toLocaleString() + '\n';
        }
        message += '  Enregistrements: ' + data.records_count + '\n\n';
    }
    
    alert(message);
}

function loadLogs() {
    fetch('{{ route("admin.eklektik.logs") }}')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('logsContent').textContent = data.logs.join('\n');
        } else {
            document.getElementById('logsContent').textContent = 'Erreur lors du chargement des logs';
        }
    });
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert ' + alertClass + ' alert-dismissible fade show';
    alertDiv.innerHTML = message + '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    
    document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.card-body').firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection

