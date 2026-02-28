{{-- Splitter Configuration Partial --}}
{{-- Variables: $prefix (odc/olt/cascade), $inputPowerDefault, $inputPowerLabel, $equalSplitters, $unequalSplitters --}}

<div id="{{ $prefix }}-splitter-config" class="border rounded p-3 bg-light mt-2">
    <h6 class="mb-3"><i class="fas fa-project-diagram mr-1"></i> Konfigurasi Splitter</h6>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-group mb-0">
                <label for="{{ $prefix }}_input_power">{{ $inputPowerLabel ?? 'Input Power' }}</label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control" 
                           id="{{ $prefix }}_input_power" value="{{ $inputPowerDefault ?? 4 }}"
                           placeholder="Power masuk">
                    <div class="input-group-append">
                        <span class="input-group-text">dBm</span>
                        @if($prefix === 'olt' || $prefix === 'odc')
                        <button type="button" class="btn btn-outline-info btn-fetch-tx-power" 
                                id="{{ $prefix }}_fetch_power" data-prefix="{{ $prefix }}"
                                title="Ambil Power dari database">
                            <i class="fas fa-database"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <small class="form-text" id="{{ $prefix }}_power_source">
                    @if($prefix === 'olt')
                    <span class="text-muted">Default: +4 dBm. Pilih OLT untuk auto-fetch atau klik <i class="fas fa-database"></i></span>
                    @elseif($prefix === 'odc')
                    <span class="text-muted">Default: -3 dBm. Pilih ODC untuk auto-fetch atau klik <i class="fas fa-database"></i></span>
                    @elseif($prefix === 'cascade')
                    <span class="text-muted">Otomatis dari Parent ODP yang dipilih</span>
                    @endif
                </small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                <label for="{{ $prefix }}_fiber_distance">Jarak Fiber</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" class="form-control fiber-distance-input" 
                           id="{{ $prefix }}_fiber_distance" value="0" placeholder="Jarak dalam km">
                    <div class="input-group-append">
                        <span class="input-group-text">km</span>
                    </div>
                </div>
                <small class="form-text text-muted">Loss fiber: 0.35 dB/km</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="{{ $prefix }}_splitter_type">Tipe Splitter <span class="text-danger">*</span></label>
                <select class="form-control splitter-type-select" id="{{ $prefix }}_splitter_type" data-prefix="{{ $prefix }}">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="equal">🔷 Splitter Equal (1:N) - Semua ke Pelanggan</option>
                    <option value="unequal">🔶 Splitter Rasio - Relay + Pelanggan</option>
                </select>
            </div>
        </div>
        
        {{-- Equal Splitter Options --}}
        <div class="col-md-4 equal-splitter-options" id="{{ $prefix }}-equal-options" style="display:none;">
            <div class="form-group">
                <label for="{{ $prefix }}_equal_splitter">Splitter <span class="text-danger">*</span></label>
                <select class="form-control equal-splitter-select" id="{{ $prefix }}_equal_splitter" data-prefix="{{ $prefix }}">
                    <option value="">-- Pilih Splitter --</option>
                    @foreach($equalSplitters as $splitter)
                        <option value="{{ $splitter->ratio }}" 
                                data-loss="{{ $splitter->branch_loss }}" 
                                data-ports="{{ $splitter->ports }}">
                            {{ $splitter->ratio }} ({{ $splitter->ports }} port, Loss: {{ $splitter->branch_loss }} dB)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        {{-- Unequal/Rasio Splitter Options --}}
        <div class="col-md-4 unequal-ratio-options" id="{{ $prefix }}-unequal-ratio-options" style="display:none;">
            <div class="form-group">
                <label for="{{ $prefix }}_unequal_ratio">Rasio Splitter <span class="text-danger">*</span></label>
                <select class="form-control unequal-ratio-select" id="{{ $prefix }}_unequal_ratio" data-prefix="{{ $prefix }}">
                    <option value="">-- Pilih Rasio --</option>
                    @foreach($unequalSplitters as $splitter)
                        <option value="{{ $splitter->ratio }}" 
                                data-branch-loss="{{ $splitter->branch_loss }}"
                                data-relay-loss="{{ $splitter->relay_loss }}"
                                data-branch-percent="{{ $splitter->branch_percent }}"
                                data-relay-percent="{{ $splitter->relay_percent }}"
                                data-branch-color="{{ $splitter->branch_color }}"
                                data-relay-color="{{ $splitter->relay_color }}">
                            {{ $splitter->ratio }} ({{ $splitter->branch_percent }}% Branch, {{ $splitter->relay_percent }}% Relay)
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    <span style="color: #007bff;">●</span> Branch = ke splitter pelanggan | 
                    <span style="color: #dc3545;">●</span> Relay = sisa ke ODP cascade
                </small>
            </div>
        </div>
        
        {{-- Branch Splitter for Unequal --}}
        <div class="col-md-4 branch-splitter-options" id="{{ $prefix }}-branch-splitter-options" style="display:none;">
            <div class="form-group">
                <label for="{{ $prefix }}_branch_splitter">
                    <span style="color: #007bff;">●</span> Splitter Branch (Pelanggan)
                </label>
                <select class="form-control branch-splitter-select" id="{{ $prefix }}_branch_splitter" data-prefix="{{ $prefix }}">
                    <option value="">-- Pilih Splitter --</option>
                    @foreach($equalSplitters as $splitter)
                        @if($splitter->ports <= 16)
                        <option value="{{ $splitter->ratio }}" 
                                data-loss="{{ $splitter->branch_loss }}" 
                                data-ports="{{ $splitter->ports }}">
                            {{ $splitter->ratio }} ({{ $splitter->ports }} port, Loss: {{ $splitter->branch_loss }} dB)
                        </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    
    {{-- Visual Power Flow --}}
    <div id="{{ $prefix }}-power-flow" class="mt-3" style="display:none;">
        <div class="card mb-0 border-0">
            <div class="card-body p-3 bg-white rounded">
                {{-- Power Flow Diagram --}}
                <div class="row align-items-center text-center">
                    {{-- Input Power --}}
                    <div class="col-md-2">
                        <div class="p-2 bg-success text-white rounded">
                            <small class="d-block">Input</small>
                            <strong id="{{ $prefix }}-display-input">+4 dBm</strong>
                        </div>
                    </div>
                    
                    {{-- Arrow --}}
                    <div class="col-md-1">
                        <i class="fas fa-arrow-right text-muted"></i>
                        <br><small class="text-muted" id="{{ $prefix }}-display-fiber-loss">-0 dB</small>
                    </div>
                    
                    {{-- After Fiber --}}
                    <div class="col-md-2">
                        <div class="p-2 bg-secondary text-white rounded">
                            <small class="d-block">Setelah Fiber</small>
                            <strong id="{{ $prefix }}-display-after-fiber">+4 dBm</strong>
                        </div>
                    </div>
                    
                    {{-- Splitter --}}
                    <div class="col-md-1">
                        <i class="fas fa-code-branch text-warning fa-lg"></i>
                        <br><small class="text-warning">Splitter</small>
                    </div>
                    
                    {{-- Output Section --}}
                    <div class="col-md-6">
                        <div class="row">
                            {{-- Branch Output (Blue) --}}
                            <div class="col-6">
                                <div class="p-2 rounded" style="background-color: #007bff; color: white;">
                                    <small class="d-block">
                                        <i class="fas fa-users mr-1"></i> Branch (Pelanggan)
                                    </small>
                                    <strong id="{{ $prefix }}-display-branch-power">-- dBm</strong>
                                    <br><small id="{{ $prefix }}-display-branch-info">--</small>
                                </div>
                            </div>
                            {{-- Relay Output (Red) --}}
                            <div class="col-6">
                                <div class="p-2 rounded relay-output" id="{{ $prefix }}-relay-box" style="background-color: #dc3545; color: white;">
                                    <small class="d-block">
                                        <i class="fas fa-share-alt mr-1"></i> Relay (Cascade)
                                    </small>
                                    <strong id="{{ $prefix }}-display-relay-power">-- dBm</strong>
                                    <br><small id="{{ $prefix }}-display-relay-info">Sisa untuk ODP berikut</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Status Badge --}}
                <div class="mt-3 text-center">
                    <span id="{{ $prefix }}-status-badge" class="badge badge-lg badge-secondary p-2">
                        Pilih tipe dan splitter untuk melihat kalkulasi
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
