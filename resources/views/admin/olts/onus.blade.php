@extends('layouts.admin')

@section('title', 'ONU ' . $olt->name . (request('port') ? ' - PON ' . request('port') : ''))

@section('page-title')
    ONU: {{ $olt->name }}
    @if(request('port'))
        <small class="text-muted">- PON Port {{ request('port') }}</small>
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.index') }}">OLT</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.olts.show', $olt) }}">{{ $olt->name }}</a></li>
    <li class="breadcrumb-item active">
        @if(request('port'))
            PON {{ request('port') }}
        @else
            ONU
        @endif
    </li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-hdd mr-2"></i>
                    @if(request('port'))
                        ONU pada PON Port {{ request('port') }}
                    @else
                        Semua ONU
                    @endif
                    <span class="badge badge-secondary ml-2">{{ $onus->total() }} ONU</span>
                </h3>
                <div class="card-tools">
                    <!-- Filter -->
                    <form action="{{ route('admin.olts.onus', $olt) }}" method="GET" class="d-inline-flex align-items-center">
                        @if(request('port'))
                            <input type="hidden" name="port" value="{{ request('port') }}">
                        @endif
                        <select name="status" class="form-control form-control-sm mr-2" style="width:auto;" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="los" {{ request('status') == 'los' ? 'selected' : '' }}>LOS</option>
                        </select>
                        <div class="input-group input-group-sm" style="width:200px;">
                            <input type="text" name="search" class="form-control" placeholder="Cari..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                    <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-default btn-sm ml-2">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($onus->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>PON/ONU</th>
                                <th>Nama</th>
                                <th>Zone</th>
                                <th>ODP</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>ONU/OLT Rx Signal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onus as $idx => $onu)
                            <tr>
                                <td class="text-muted">{{ $onus->firstItem() + $idx }}</td>
                                <td><strong>{{ $onu->slot }}/{{ $onu->port }}/{{ $onu->onu_id }}</strong></td>
                                <td>{{ $onu->name ?: '-' }}</td>
                                <td>{{ $onu->zone->name ?? '-' }}</td>
                                <td>{{ $onu->odp->name ?? '-' }}</td>
                                <td><code>{{ $onu->serial_number }}</code></td>
                                <td>
                                    @if($onu->status == 'online')
                                        <span class="badge badge-success">Online</span>
                                    @elseif($onu->status == 'offline')
                                        <span class="badge badge-danger">Offline</span>
                                    @elseif($onu->status == 'los')
                                        <span class="badge badge-warning">LOS</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($onu->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $onuRx = $onu->rx_power;
                                        $oltRx = $onu->olt_rx_power;
                                        $signal = $onuRx ?? $oltRx;
                                        $signalClass = 'secondary';
                                        if ($signal !== null) {
                                            $signalClass = $signal >= -25 ? 'success' : ($signal >= -27 ? 'warning' : 'danger');
                                        }
                                    @endphp
                                    <span class="text-{{ $signalClass }}">
                                        @if($onuRx !== null || $oltRx !== null)
                                            {{ $onuRx !== null ? number_format($onuRx, 2) : '-' }}
                                            / {{ $oltRx !== null ? number_format($oltRx, 2) : '-' }} dBm
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.onus.show', $onu) }}" class="btn btn-xs btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    {{ $onus->links() }}
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Tidak ada ONU ditemukan</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
