<style>
    .log-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .log-header .action-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
    }
    .log-header .action-badge.login { background: #28a745; }
    .log-header .action-badge.logout { background: #6c757d; }
    .log-header .action-badge.create { background: #17a2b8; }
    .log-header .action-badge.update { background: #ffc107; color: #333; }
    .log-header .action-badge.delete { background: #dc3545; }
    .log-header .action-badge.view { background: #6f42c1; }
    .log-header .action-badge.scan { background: #20c997; }
    .log-header .action-badge.default { background: #6c757d; }
    .log-user {
        display: flex;
        align-items: center;
        margin-top: 1rem;
    }
    .log-user img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 3px solid rgba(255,255,255,0.3);
        margin-right: 1rem;
    }
    .log-user-info h5 {
        margin: 0;
        font-weight: 600;
    }
    .log-user-info small {
        opacity: 0.8;
    }
    .info-card {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .info-card-header {
        padding: 0.75rem 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
    }
    .info-card-header i {
        width: 25px;
        text-align: center;
        margin-right: 0.5rem;
    }
    .info-card-header.network { color: #17a2b8; background: #e7f8fb; }
    .info-card-header.location { color: #28a745; background: #e8f5e9; }
    .info-card-header.device { color: #fd7e14; background: #fff3e0; }
    .info-card-header.description { color: #6c757d; background: #f8f9fa; }
    .info-card-header.changes { color: #6f42c1; background: #f3e5f5; }
    .info-card-body {
        padding: 1rem;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px dashed #e9ecef;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .info-item .label {
        color: #6c757d;
        font-size: 0.85rem;
    }
    .info-item .value {
        font-weight: 500;
        text-align: right;
    }
    .info-item .value code {
        background: #f8f9fa;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
    }
    .device-icon {
        font-size: 2.5rem;
        color: #6c757d;
        margin-right: 1rem;
    }
    .device-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        text-align: center;
    }
    .device-info-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
    .device-info-item i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .device-info-item .label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
    }
    .device-info-item .value {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .data-diff {
        display: flex;
        gap: 1rem;
    }
    .data-diff-panel {
        flex: 1;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .data-diff-panel.old {
        border: 2px solid #dc3545;
    }
    .data-diff-panel.new {
        border: 2px solid #28a745;
    }
    .data-diff-panel .panel-header {
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .data-diff-panel.old .panel-header {
        background: #dc3545;
        color: white;
    }
    .data-diff-panel.new .panel-header {
        background: #28a745;
        color: white;
    }
    .data-diff-panel pre {
        margin: 0;
        padding: 1rem;
        background: #f8f9fa;
        font-size: 0.8rem;
        max-height: 300px;
        overflow: auto;
    }
    .map-container {
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    #logMap {
        height: 300px;
        width: 100%;
    }
    .location-summary {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .location-summary i {
        font-size: 2.5rem;
        margin-right: 1rem;
        opacity: 0.8;
    }
    .location-summary .location-text h6 {
        margin: 0;
        font-weight: 600;
    }
    .location-summary .location-text small {
        opacity: 0.8;
    }
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1rem;
    }
    .timeline-item:before {
        content: '';
        position: absolute;
        left: 7px;
        top: 0;
        bottom: -1rem;
        width: 2px;
        background: #e9ecef;
    }
    .timeline-item:last-child:before {
        display: none;
    }
    .timeline-item .dot {
        position: absolute;
        left: 0;
        top: 0;
        width: 16px;
        height: 16px;
        background: #667eea;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #667eea;
    }
    .user-agent-box {
        background: #2d3748;
        color: #a0aec0;
        padding: 1rem;
        border-radius: 0.5rem;
        font-family: monospace;
        font-size: 0.8rem;
        word-break: break-all;
    }
</style>

<!-- Log Header -->
<div class="log-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <span class="action-badge {{ $log->action }}">
                <i class="fas fa-{{ $log->action === 'login' ? 'sign-in-alt' : ($log->action === 'logout' ? 'sign-out-alt' : ($log->action === 'create' ? 'plus' : ($log->action === 'update' ? 'edit' : ($log->action === 'delete' ? 'trash' : 'bolt')))) }} mr-1"></i>
                {{ strtoupper($log->action) }}
            </span>
            <span class="badge badge-light ml-2">{{ ucfirst($log->module) }}</span>
        </div>
        <div class="text-right">
            <div><i class="far fa-clock mr-1"></i> {{ $log->created_at->format('d M Y, H:i:s') }}</div>
            <small>{{ $log->created_at->diffForHumans() }}</small>
        </div>
    </div>
    <div class="log-user">
        @if($log->user)
        <img src="{{ $log->user->avatar_url }}" alt="{{ $log->user->name }}">
        <div class="log-user-info">
            <h5>{{ $log->user->name }}</h5>
            <small><i class="fas fa-envelope mr-1"></i> {{ $log->user->email }}</small>
        </div>
        @else
        <div class="log-user-info">
            <h5><i class="fas fa-robot mr-2"></i>System / Guest</h5>
            <small>Aktivitas tanpa user login</small>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-6">
        <!-- Network Info -->
        <div class="info-card">
            <div class="info-card-header network">
                <i class="fas fa-network-wired"></i> Informasi Jaringan
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <span class="label">IP Address</span>
                    <span class="value"><code>{{ $log->ip_address }}</code></span>
                </div>
                @if($log->local_ip)
                <div class="info-item">
                    <span class="label">Local IP</span>
                    <span class="value"><code>{{ $log->local_ip }}</code></span>
                </div>
                @endif
                @if($log->isp)
                <div class="info-item">
                    <span class="label">ISP</span>
                    <span class="value">{{ $log->isp }}</span>
                </div>
                @endif
                @if($log->organization)
                <div class="info-item">
                    <span class="label">Organization</span>
                    <span class="value">{{ $log->organization }}</span>
                </div>
                @endif
                @if($log->as_number)
                <div class="info-item">
                    <span class="label">AS Number</span>
                    <span class="value"><code>{{ $log->as_number }}</code></span>
                </div>
                @endif
            </div>
        </div>

        <!-- Device Info -->
        <div class="info-card">
            <div class="info-card-header device">
                <i class="fas fa-laptop"></i> Informasi Device
            </div>
            <div class="info-card-body">
                <div class="device-info-grid">
                    <div class="device-info-item">
                        <i class="fas fa-{{ $log->is_mobile ? 'mobile-alt' : ($log->is_tablet ? 'tablet-alt' : 'desktop') }} text-primary"></i>
                        <div class="label">Device</div>
                        <div class="value">
                            @if($log->is_mobile) Mobile
                            @elseif($log->is_tablet) Tablet
                            @elseif($log->is_desktop) Desktop
                            @else Unknown
                            @endif
                        </div>
                    </div>
                    <div class="device-info-item">
                        <i class="fab fa-{{ strtolower($log->browser) === 'chrome' ? 'chrome' : (strtolower($log->browser) === 'firefox' ? 'firefox' : (strtolower($log->browser) === 'safari' ? 'safari' : (strtolower($log->browser) === 'edge' ? 'edge' : 'globe'))) }} text-info"></i>
                        <div class="label">Browser</div>
                        <div class="value">{{ $log->browser ?? 'Unknown' }} {{ $log->browser_version ?? '' }}</div>
                    </div>
                    <div class="device-info-item">
                        <i class="fab fa-{{ strtolower($log->platform) === 'windows' ? 'windows' : (strtolower($log->platform) === 'macos' || strtolower($log->platform) === 'os x' ? 'apple' : (strtolower($log->platform) === 'linux' ? 'linux' : (strtolower($log->platform) === 'android' ? 'android' : 'desktop'))) }} text-success"></i>
                        <div class="label">Platform</div>
                        <div class="value">{{ $log->platform ?? 'Unknown' }} {{ $log->platform_version ?? '' }}</div>
                    </div>
                </div>
                @if($log->device || $log->device_model)
                <div class="info-item mt-3">
                    <span class="label">Device Model</span>
                    <span class="value">{{ $log->device }} {{ $log->device_model }}</span>
                </div>
                @endif
                @if($log->is_robot)
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-robot mr-2"></i> Terdeteksi sebagai Bot/Robot
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-6">
        <!-- Location Info -->
        <div class="info-card">
            <div class="info-card-header location">
                <i class="fas fa-map-marker-alt"></i> Informasi Lokasi
            </div>
            <div class="info-card-body">
                @if($log->city || $log->country)
                <div class="location-summary">
                    <i class="fas fa-globe-asia"></i>
                    <div class="location-text">
                        <h6>{{ $log->city ?? 'Unknown City' }}, {{ $log->region ?? '' }}</h6>
                        <small>{{ $log->country ?? 'Unknown Country' }} {{ $log->country_code ? "({$log->country_code})" : '' }}</small>
                    </div>
                </div>
                @endif
                <div class="info-item">
                    <span class="label">Negara</span>
                    <span class="value">{{ $log->country ?? '-' }} {{ $log->country_code ? "({$log->country_code})" : '' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Region/Provinsi</span>
                    <span class="value">{{ $log->region ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Kota</span>
                    <span class="value">{{ $log->city ?? '-' }}</span>
                </div>
                @if($log->district)
                <div class="info-item">
                    <span class="label">District</span>
                    <span class="value">{{ $log->district }}</span>
                </div>
                @endif
                @if($log->postal_code)
                <div class="info-item">
                    <span class="label">Kode Pos</span>
                    <span class="value">{{ $log->postal_code }}</span>
                </div>
                @endif
                @if($log->timezone)
                <div class="info-item">
                    <span class="label">Timezone</span>
                    <span class="value"><i class="far fa-clock mr-1"></i> {{ $log->timezone }}</span>
                </div>
                @endif
                @if($log->latitude && $log->longitude)
                <div class="info-item">
                    <span class="label">Koordinat</span>
                    <span class="value"><code>{{ $log->latitude }}, {{ $log->longitude }}</code></span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Description -->
@if($log->description)
<div class="info-card">
    <div class="info-card-header description">
        <i class="fas fa-info-circle"></i> Deskripsi Aktivitas
    </div>
    <div class="info-card-body">
        <p class="mb-0">{{ $log->description }}</p>
    </div>
</div>
@endif

<!-- Data Changes -->
@if($log->old_data || $log->new_data)
<div class="info-card">
    <div class="info-card-header changes">
        <i class="fas fa-exchange-alt"></i> Perubahan Data
    </div>
    <div class="info-card-body">
        <div class="data-diff">
            @if($log->old_data)
            <div class="data-diff-panel old">
                <div class="panel-header">
                    <i class="fas fa-minus-circle mr-1"></i> Data Sebelum
                </div>
                <pre><code>{{ is_array($log->old_data) ? json_encode($log->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode(json_decode($log->old_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
            @endif
            @if($log->new_data)
            <div class="data-diff-panel new">
                <div class="panel-header">
                    <i class="fas fa-plus-circle mr-1"></i> Data Sesudah
                </div>
                <pre><code>{{ is_array($log->new_data) ? json_encode($log->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode(json_decode($log->new_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Map -->
@if($log->latitude && $log->longitude)
<div class="info-card">
    <div class="info-card-header location">
        <i class="fas fa-map"></i> Peta Lokasi
    </div>
    <div class="info-card-body p-0">
        <div class="map-container">
            <div id="logMap" data-lat="{{ $log->latitude }}" data-lng="{{ $log->longitude }}"></div>
        </div>
    </div>
</div>
@endif

<!-- User Agent -->
@if($log->user_agent)
<div class="info-card">
    <div class="info-card-header" style="color: #4a5568; background: #edf2f7;">
        <i class="fas fa-code"></i> User Agent
    </div>
    <div class="info-card-body p-0">
        <div class="user-agent-box">{{ $log->user_agent }}</div>
    </div>
</div>
@endif
