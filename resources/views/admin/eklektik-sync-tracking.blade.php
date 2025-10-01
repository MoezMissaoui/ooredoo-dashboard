@extends('layouts.eklektik-config')

@section('title', 'Suivi des Synchronisations Eklektik')

@section('eklektik-content')
<div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sync-alt"></i>
                        Suivi des Synchronisations Eklektik
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-primary" onclick="refreshData()">
                            <i class="fas fa-refresh"></i> Actualiser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistiques générales -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-sync"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Syncs</span>
                                    <span class="info-box-number">{{ $stats['total_syncs'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Réussies</span>
                                    <span class="info-box-number">{{ $stats['successful_syncs'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-times"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Échouées</span>
                                    <span class="info-box-number">{{ $stats['failed_syncs'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Partielles</span>
                                    <span class="info-box-number">{{ $stats['partial_syncs'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-play"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">En cours</span>
                                    <span class="info-box-number">{{ $stats['running_syncs'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary">
                                    <i class="fas fa-database"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Enregistrements</span>
                                    <span class="info-box-number">{{ number_format($stats['total_records_processed']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="status" class="mr-2">Statut:</label>
                                    <select name="status" id="status" class="form-control form-control-sm">
                                        <option value="">Tous</option>
                                        @foreach($statuses as $key => $label)
                                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <label for="operator" class="mr-2">Opérateur:</label>
                                    <select name="operator" id="operator" class="form-control form-control-sm">
                                        <option value="">Tous</option>
                                        @foreach($operators as $op)
                                            <option value="{{ $op }}" {{ request('operator') == $op ? 'selected' : '' }}>
                                                {{ $op }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <label for="sync_type" class="mr-2">Type:</label>
                                    <select name="sync_type" id="sync_type" class="form-control form-control-sm">
                                        <option value="">Tous</option>
                                        @foreach($syncTypes as $key => $label)
                                            <option value="{{ $key }}" {{ request('sync_type') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <label for="date_from" class="mr-2">Du:</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" 
                                           value="{{ request('date_from') }}">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="date_to" class="mr-2">Au:</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" 
                                           value="{{ request('date_to') }}">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <a href="{{ route('admin.eklektik.sync-tracking') }}" class="btn btn-sm btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Effacer
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau des synchronisations -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Sync</th>
                                    <th>Date</th>
                                    <th>Opérateur</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Durée</th>
                                    <th>Enregistrements</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($syncs as $sync)
                                    <tr>
                                        <td>
                                            <code>{{ $sync->sync_id }}</code>
                                        </td>
                                        <td>{{ $sync->sync_date->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $sync->operator }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $syncTypes[$sync->sync_type] ?? $sync->sync_type }}</span>
                                        </td>
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
                                                @default
                                                    <span class="badge badge-secondary">{{ $sync->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $sync->started_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            @if($sync->completed_at)
                                                {{ $sync->completed_at->format('d/m/Y H:i:s') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sync->duration_seconds)
                                                {{ $sync->duration_seconds }}s
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>Total: {{ number_format($sync->records_processed) }}</div>
                                                @if($sync->records_created > 0)
                                                    <div class="text-success">Créés: {{ number_format($sync->records_created) }}</div>
                                                @endif
                                                @if($sync->records_updated > 0)
                                                    <div class="text-info">Mis à jour: {{ number_format($sync->records_updated) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.eklektik.sync-details', $sync->id) }}" 
                                                   class="btn btn-info btn-sm" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($sync->status === 'failed')
                                                    <button class="btn btn-warning btn-sm" 
                                                            onclick="retrySync({{ $sync->id }})" 
                                                            title="Relancer">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            <i class="fas fa-inbox"></i> Aucune synchronisation trouvée
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted">
                                Affichage de {{ $syncs->firstItem() }} à {{ $syncs->lastItem() }} 
                                sur {{ $syncs->total() }} synchronisations
                            </p>
                        </div>
                        <div>
                            {{ $syncs->links() }}
                        </div>
                    </div>
                </div>
            </div>

<script>
function refreshData() {
    location.reload();
}

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

// Auto-refresh toutes les 30 secondes si il y a des synchronisations en cours
@if($stats['running_syncs'] > 0)
setTimeout(() => {
    location.reload();
}, 30000);
@endif
</script>
@endsection
