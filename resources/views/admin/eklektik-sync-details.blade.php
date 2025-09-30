@extends('layouts.app')

@section('title', 'Détails de la Synchronisation Eklektik')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Détails de la Synchronisation
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.eklektik.sync-tracking') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations générales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informations Générales</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID de Synchronisation:</strong></td>
                                    <td><code>{{ $sync->sync_id }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Date synchronisée:</strong></td>
                                    <td>{{ $sync->sync_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Opérateur:</strong></td>
                                    <td><span class="badge badge-info">{{ $sync->operator }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td><span class="badge badge-secondary">{{ $sync->sync_type }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Statut:</strong></td>
                                    <td>
                                        @switch($sync->status)
                                            @case('success')
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Réussi
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times"></i> Échoué
                                                </span>
                                                @break
                                            @case('partial')
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Partiel
                                                </span>
                                                @break
                                            @case('running')
                                                <span class="badge badge-primary">
                                                    <i class="fas fa-spinner fa-spin"></i> En cours
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Timing</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Début:</strong></td>
                                    <td>{{ $sync->started_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fin:</strong></td>
                                    <td>
                                        @if($sync->completed_at)
                                            {{ $sync->completed_at->format('d/m/Y H:i:s') }}
                                        @else
                                            <span class="text-muted">En cours...</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Durée:</strong></td>
                                    <td>
                                        @if($sync->duration_seconds)
                                            {{ $sync->duration_seconds }} secondes
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Source:</strong></td>
                                    <td>{{ $sync->source }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Serveur:</strong></td>
                                    <td>{{ $sync->server_info }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Utilisateur:</strong></td>
                                    <td>{{ $sync->execution_user }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Résultats -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Résultats de la Synchronisation</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-database"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Traités</span>
                                            <span class="info-box-number">{{ number_format($sync->records_processed) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-plus"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Créés</span>
                                            <span class="info-box-number">{{ number_format($sync->records_created) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-edit"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Mis à jour</span>
                                            <span class="info-box-number">{{ number_format($sync->records_updated) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-skip-forward"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Ignorés</span>
                                            <span class="info-box-number">{{ number_format($sync->records_skipped) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats par opérateur -->
                    @if($sync->operators_results && is_array($sync->operators_results))
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Résultats par Opérateur</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Opérateur</th>
                                                <th>Enregistrements</th>
                                                <th>Créés</th>
                                                <th>Mis à jour</th>
                                                <th>Ignorés</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sync->operators_results['operators'] ?? [] as $operator => $results)
                                                <tr>
                                                    <td><span class="badge badge-info">{{ $operator }}</span></td>
                                                    <td>{{ number_format($results['synced'] ?? 0) }}</td>
                                                    <td>{{ number_format($results['created'] ?? 0) }}</td>
                                                    <td>{{ number_format($results['updated'] ?? 0) }}</td>
                                                    <td>{{ number_format($results['skipped'] ?? 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Erreurs -->
                    @if($sync->error_message)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Erreur</h5>
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Message d'erreur:</h6>
                                    <p>{{ $sync->error_message }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Métadonnées -->
                    @if($sync->sync_metadata)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Métadonnées</h5>
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($sync->sync_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group">
                                @if($sync->status === 'failed')
                                    <button class="btn btn-warning" onclick="retrySync({{ $sync->id }})">
                                        <i class="fas fa-redo"></i> Relancer la synchronisation
                                    </button>
                                @endif
                                <a href="{{ route('admin.eklektik.sync-tracking') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour à la liste
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function retrySync(syncId) {
    if (confirm('Êtes-vous sûr de vouloir relancer cette synchronisation ?')) {
        fetch(`/admin/eklektik-sync-tracking/${syncId}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Synchronisation relancée avec succès !');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du relancement de la synchronisation');
        });
    }
}
</script>
@endsection
