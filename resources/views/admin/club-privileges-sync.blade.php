@extends('layouts.eklektik-config')

@section('title', 'Synchronisation Club Privilèges')

@section('eklektik-content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-sync-alt"></i>
            Synchronisation Club Privilèges
        </h3>
    </div>
    <div class="card-body">
        <!-- Informations sur la synchronisation -->
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle"></i> Informations</h6>
            <p><strong>URL de synchronisation:</strong> <code>https://clubprivileges.app/sync-dashboard-data</code></p>
            <p><strong>Fonctionnement:</strong> Visite automatique du lien toutes les heures pour déclencher la synchronisation</p>
            <p><strong>Fréquence:</strong> Toutes les heures (00:00, 01:00, 02:00, etc.)</p>
        </div>

        <!-- Contrôles de synchronisation -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-play"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <button id="visitSyncBtn" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-sync-alt"></i> Visiter le Lien de Synchronisation
                        </button>
                        <button id="testConnectionBtn" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-plug"></i> Tester la Connexion
                        </button>
                        <button id="refreshStatusBtn" class="btn btn-secondary btn-block">
                            <i class="fas fa-refresh"></i> Actualiser le Statut
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Statut</h5>
                    </div>
                    <div class="card-body">
                        <div id="syncStatus">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement du statut...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des synchronisations -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Historique des Visites</h5>
            </div>
            <div class="card-body">
                <div id="syncHistory">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement de l'historique...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher les détails -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Visite</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="detailsContent" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Charger le statut initial
    loadStatus();
    loadHistory();

    // Visiter le lien de synchronisation
    $('#visitSyncBtn').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Visite en cours...');
        
        $.ajax({
            url: '{{ route("admin.cp-sync.visit") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Visite du lien de synchronisation réussie');
                    loadStatus();
                    loadHistory();
                } else {
                    showAlert('danger', 'Erreur: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('danger', 'Erreur: ' + (response?.message || 'Erreur de connexion'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Visiter le Lien de Synchronisation');
            }
        });
    });

    // Tester la connexion
    $('#testConnectionBtn').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Test en cours...');
        
        $.ajax({
            url: '{{ route("admin.cp-sync.test") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Test de connexion réussi');
                } else {
                    showAlert('danger', 'Erreur: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('danger', 'Erreur: ' + (response?.message || 'Erreur de connexion'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Tester la Connexion');
            }
        });
    });

    // Actualiser le statut
    $('#refreshStatusBtn').click(function() {
        loadStatus();
        loadHistory();
    });

    // Charger le statut
    function loadStatus() {
        $.ajax({
            url: '{{ route("admin.cp-sync.status") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayStatus(response.data);
                } else {
                    $('#syncStatus').html('<div class="alert alert-danger">Erreur: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#syncStatus').html('<div class="alert alert-danger">Erreur lors du chargement du statut</div>');
            }
        });
    }

    // Charger l'historique
    function loadHistory() {
        $.ajax({
            url: '{{ route("admin.cp-sync.history") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayHistory(response.data);
                } else {
                    $('#syncHistory').html('<div class="alert alert-danger">Erreur: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#syncHistory').html('<div class="alert alert-danger">Erreur lors du chargement de l\'historique</div>');
            }
        });
    }

    // Afficher le statut
    function displayStatus(data) {
        let html = '';
        
        if (data.last_visit) {
            const lastVisit = data.last_visit;
            const statusClass = lastVisit.success ? 'success' : 'danger';
            const statusIcon = lastVisit.success ? 'check-circle' : 'times-circle';
            const lastVisitTime = new Date(lastVisit.timestamp).toLocaleString('fr-FR');
            
            html += `
                <div class="alert alert-${statusClass}">
                    <h6><i class="fas fa-${statusIcon}"></i> Dernière Visite</h6>
                    <p><strong>Date:</strong> ${lastVisitTime}</p>
                    <p><strong>Statut:</strong> ${lastVisit.status}</p>
                    <p><strong>Succès:</strong> ${lastVisit.success ? 'Oui' : 'Non'}</p>
                </div>
            `;
        } else {
            html += '<div class="alert alert-warning">Aucune visite enregistrée</div>';
        }

        if (data.next_scheduled) {
            const nextScheduled = new Date(data.next_scheduled).toLocaleString('fr-FR');
            html += `
                <div class="alert alert-info">
                    <h6><i class="fas fa-clock"></i> Prochaine Visite Programmée</h6>
                    <p><strong>Date:</strong> ${nextScheduled}</p>
                </div>
            `;
        }

        $('#syncStatus').html(html);
    }

    // Afficher l'historique
    function displayHistory(history) {
        if (history.length === 0) {
            $('#syncHistory').html('<div class="alert alert-info">Aucun historique disponible</div>');
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped">';
        html += '<thead><tr><th>Date</th><th>Statut</th><th>Succès</th><th>Actions</th></tr></thead><tbody>';

        history.reverse().forEach(function(visit) {
            const date = new Date(visit.timestamp).toLocaleString('fr-FR');
            const statusClass = visit.success ? 'success' : 'danger';
            const statusIcon = visit.success ? 'check' : 'times';
            
            html += `
                <tr>
                    <td>${date}</td>
                    <td><span class="badge badge-${statusClass}">${visit.status}</span></td>
                    <td><i class="fas fa-${statusIcon} text-${statusClass}"></i></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="showDetails('${visit.timestamp}')">
                            <i class="fas fa-eye"></i> Détails
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        $('#syncHistory').html(html);
    }

    // Afficher les détails
    window.showDetails = function(timestamp) {
        const history = window.syncHistory || [];
        const visit = history.find(v => v.timestamp === timestamp);
        
        if (visit) {
            $('#detailsContent').text(JSON.stringify(visit, null, 2));
            $('#detailsModal').modal('show');
        }
    };

    // Afficher une alerte
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        $('.card-body').prepend(alertHtml);
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endsection
