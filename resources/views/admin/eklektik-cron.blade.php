@extends('layouts.app')

@section('title', 'Configuration Cron Eklektik')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        ⚙️ Configuration du Cron Eklektik
                        <span class="badge badge-info" id="cron-status-badge">Chargement...</span>
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-primary" onclick="testCron()">
                            🧪 Tester le Cron
                        </button>
                        <button class="btn btn-success" onclick="runCron()">
                            🚀 Exécuter Maintenant
                        </button>
                        <button class="btn btn-warning" onclick="resetConfig()">
                            🔄 Réinitialiser
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Statut du Cron -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Prochaine Exécution</span>
                                    <span class="info-box-number" id="next-execution">Calcul...</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Notifications Traitées</span>
                                    <span class="info-box-number" id="total-processed">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-database"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Entrées en Cache</span>
                                    <span class="info-box-number" id="cache-entries">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">KPIs Mis à Jour</span>
                                    <span class="info-box-number" id="kpi-updated">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de Configuration -->
                    <form id="cron-config-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>🔧 Configuration Générale</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="cron_enabled" name="cron_enabled">
                                                <label class="custom-control-label" for="cron_enabled">
                                                    Activer le Cron Eklektik
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_schedule">Planification (Format Cron)</label>
                                            <input type="text" class="form-control" id="cron_schedule" name="cron_schedule" 
                                                   placeholder="0 2 * * *" value="0 2 * * *">
                                            <small class="form-text text-muted">
                                                Format: minute heure jour mois jour_semaine<br>
                                                Exemples: 0 2 * * * (tous les jours à 02:00), 0 */6 * * * (toutes les 6 heures)
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_operators">Opérateurs à Traiter</label>
                                            <select class="form-control select2" id="cron_operators" name="cron_operators[]" multiple>
                                                <option value="ALL">Tous les opérateurs</option>
                                                <option value="TT">TT</option>
                                                <option value="Orange">Orange</option>
                                                <option value="Taraji">Taraji</option>
                                                <option value="Timwe">Timwe</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_retention_days">Rétention des Données (Jours)</label>
                                            <input type="number" class="form-control" id="cron_retention_days" name="cron_retention_days" 
                                                   min="1" max="365" value="90">
                                            <small class="form-text text-muted">
                                                Nombre de jours de conservation des données de cache
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>📧 Notifications</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="cron_notification_email">Email de Notification (Succès)</label>
                                            <input type="email" class="form-control" id="cron_notification_email" name="cron_notification_email" 
                                                   placeholder="admin@example.com">
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_error_email">Email d'Erreur</label>
                                            <input type="email" class="form-control" id="cron_error_email" name="cron_error_email" 
                                                   placeholder="errors@example.com">
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_batch_size">Taille des Lots</label>
                                            <input type="number" class="form-control" id="cron_batch_size" name="cron_batch_size" 
                                                   min="100" max="10000" value="1000">
                                            <small class="form-text text-muted">
                                                Nombre de notifications traitées par lot
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="cron_timeout">Timeout (Secondes)</label>
                                            <input type="number" class="form-control" id="cron_timeout" name="cron_timeout" 
                                                   min="60" max="3600" value="300">
                                            <small class="form-text text-muted">
                                                Délai maximum pour le traitement
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 Sauvegarder la Configuration
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="loadConfig()">
                                    🔄 Recharger
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs du Cron -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>📋 Logs d'Exécution</h4>
                </div>
                <div class="card-body">
                    <div id="cron-logs" class="bg-dark text-light p-3 rounded" style="height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Chargement des logs...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Test -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🧪 Test du Cron Eklektik</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="test_date">Date de Test</label>
                    <input type="date" class="form-control" id="test_date" value="{{ now()->subDay()->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label for="test_operator">Opérateur</label>
                    <select class="form-control" id="test_operator">
                        <option value="ALL">Tous les opérateurs</option>
                        <option value="TT">TT</option>
                        <option value="Orange">Orange</option>
                        <option value="Taraji">Taraji</option>
                        <option value="Timwe">Timwe</option>
                    </select>
                </div>
                <div id="test-results" class="mt-3" style="display: none;">
                    <h6>Résultats du Test:</h6>
                    <pre id="test-output" class="bg-light p-3 rounded"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="executeTest()">
                    <i class="fas fa-play"></i> Lancer le Test
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadConfig();
    loadStatistics();
    
    // Initialiser Select2
    $('.select2').select2({
        placeholder: 'Sélectionner les opérateurs',
        allowClear: true
    });

    // Gestion du formulaire
    $('#cron-config-form').on('submit', function(e) {
        e.preventDefault();
        saveConfig();
    });

    // Actualiser les statistiques toutes les 30 secondes
    setInterval(loadStatistics, 30000);
});

function loadConfig() {
    $.ajax({
        url: '/admin/eklektik-cron/config',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const config = response.data;
                
                // Remplir le formulaire
                $('#cron_enabled').prop('checked', config.cron_enabled === 'true');
                $('#cron_schedule').val(config.cron_schedule);
                $('#cron_operators').val(JSON.parse(config.cron_operators)).trigger('change');
                $('#cron_retention_days').val(config.cron_retention_days);
                $('#cron_notification_email').val(config.cron_notification_email);
                $('#cron_error_email').val(config.cron_error_email);
                $('#cron_batch_size').val(config.cron_batch_size);
                $('#cron_timeout').val(config.cron_timeout);
                
                // Mettre à jour le statut
                updateCronStatus(config);
            }
        },
        error: function() {
            showAlert('Erreur lors du chargement de la configuration', 'error');
        }
    });
}

function saveConfig() {
    const formData = {
        cron_enabled: $('#cron_enabled').is(':checked'),
        cron_schedule: $('#cron_schedule').val(),
        cron_operators: $('#cron_operators').val(),
        cron_retention_days: parseInt($('#cron_retention_days').val()),
        cron_notification_email: $('#cron_notification_email').val(),
        cron_error_email: $('#cron_error_email').val(),
        cron_batch_size: parseInt($('#cron_batch_size').val()),
        cron_timeout: parseInt($('#cron_timeout').val())
    };

    $.ajax({
        url: '/admin/eklektik-cron/config',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('Configuration sauvegardée avec succès', 'success');
                loadConfig();
            } else {
                showAlert(response.message || 'Erreur lors de la sauvegarde', 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response.message || 'Erreur lors de la sauvegarde', 'error');
        }
    });
}

function loadStatistics() {
    $.ajax({
        url: '/admin/eklektik-cron/statistics',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#total-processed').text(stats.total_processed);
                $('#cache-entries').text(stats.cache_entries);
                $('#kpi-updated').text(stats.kpi_updated);
                $('#next-execution').text(stats.next_execution);
            }
        }
    });
}

function updateCronStatus(config) {
    const status = config.enabled ? 'Actif' : 'Inactif';
    const badgeClass = config.enabled ? 'badge-success' : 'badge-danger';
    $('#cron-status-badge').text(status).removeClass('badge-info badge-success badge-danger').addClass(badgeClass);
}

function testCron() {
    $('#testModal').modal('show');
}

function executeTest() {
    const date = $('#test_date').val();
    const operator = $('#test_operator').val();
    
    $('#test-results').show();
    $('#test-output').text('Exécution du test en cours...');
    
    $.ajax({
        url: '/admin/eklektik-cron/test',
        method: 'POST',
        data: { date: date, operator: operator },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#test-output').text(`Test réussi!\nDurée: ${response.duration}s\n\n${response.output}`);
                showAlert('Test exécuté avec succès', 'success');
            } else {
                $('#test-output').text(`Erreur: ${response.message}`);
                showAlert('Erreur lors du test', 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            $('#test-output').text(`Erreur: ${response.message || 'Erreur inconnue'}`);
            showAlert('Erreur lors du test', 'error');
        }
    });
}

function runCron() {
    if (!confirm('Êtes-vous sûr de vouloir exécuter le cron maintenant ?')) {
        return;
    }
    
    showAlert('Exécution du cron en cours...', 'info');
    
    $.ajax({
        url: '/admin/eklektik-cron/run',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(`Cron exécuté avec succès! Durée: ${response.total_duration}s`, 'success');
                loadStatistics();
            } else {
                showAlert(response.message || 'Erreur lors de l\'exécution', 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response.message || 'Erreur lors de l\'exécution', 'error');
        }
    });
}

function resetConfig() {
    if (!confirm('Êtes-vous sûr de vouloir réinitialiser la configuration ?')) {
        return;
    }
    
    $.ajax({
        url: '/admin/eklektik-cron/reset',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert('Configuration réinitialisée avec succès', 'success');
                loadConfig();
            } else {
                showAlert(response.message || 'Erreur lors de la réinitialisation', 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response.message || 'Erreur lors de la réinitialisation', 'error');
        }
    });
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'info' ? 'alert-info' : 'alert-warning';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    
    $('.container').prepend(alert);
    
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}
</script>
@endsection

