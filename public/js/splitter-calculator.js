/**
 * Splitter Configuration Calculator
 * Handles power calculation for OLT, ODC, and Cascade connection types
 * 
 * Data from database: splitter_ratios table
 * - Equal: 1:2, 1:4, 1:8, 1:16, 1:32, 1:64
 * - Unequal: 10:90, 15:85, 20:80, 25:75, 30:70, 40:60, 50:50
 * 
 * Rasio format: Branch:Relay
 * - Branch (biru) = persentase power ke splitter pelanggan
 * - Relay (merah) = sisa power untuk ODP cascade berikutnya
 */

window.SplitterCalculator = {
    // Fiber loss per km (G.652 standard)
    fiberLossPerKm: 0.35,
    
    // Power thresholds for status
    thresholds: {
        optimal: -25,
        warning: -28,
        critical: -30
    },

    /**
     * Initialize calculator for a prefix (odc, olt, cascade)
     */
    init: function(prefix) {
        var self = this;
        
        // Splitter type change
        $('#' + prefix + '_splitter_type').on('change', function() {
            self.handleTypeChange(prefix, $(this).val());
        });
        
        // Equal splitter change
        $('#' + prefix + '_equal_splitter').on('change', function() {
            self.calculate(prefix, 'equal');
        });
        
        // Unequal ratio change
        $('#' + prefix + '_unequal_ratio').on('change', function() {
            if ($(this).val()) {
                $('#' + prefix + '-branch-splitter-options').show();
            } else {
                $('#' + prefix + '-branch-splitter-options').hide();
            }
            self.calculate(prefix, 'unequal');
        });
        
        // Branch splitter change
        $('#' + prefix + '_branch_splitter').on('change', function() {
            self.calculate(prefix, 'unequal');
        });
        
        // Input power change
        $('#' + prefix + '_input_power').on('change keyup', function() {
            var type = $('#' + prefix + '_splitter_type').val();
            if (type) self.calculate(prefix, type);
        });
        
        // Fiber distance change
        $('#' + prefix + '_fiber_distance').on('change keyup', function() {
            var type = $('#' + prefix + '_splitter_type').val();
            if (type) self.calculate(prefix, type);
        });
    },

    /**
     * Handle splitter type selection change
     */
    handleTypeChange: function(prefix, type) {
        // Hide all options first
        $('#' + prefix + '-equal-options').hide();
        $('#' + prefix + '-unequal-ratio-options').hide();
        $('#' + prefix + '-branch-splitter-options').hide();
        $('#' + prefix + '-power-flow').hide();
        
        if (type === 'equal') {
            $('#' + prefix + '-equal-options').show();
            // Hide relay box for equal splitter
            $('#' + prefix + '-relay-box').addClass('d-none');
        } else if (type === 'unequal') {
            $('#' + prefix + '-unequal-ratio-options').show();
            // Show relay box for unequal splitter
            $('#' + prefix + '-relay-box').removeClass('d-none');
        }
    },

    /**
     * Calculate power based on splitter configuration
     */
    calculate: function(prefix, type) {
        var inputPower = parseFloat($('#' + prefix + '_input_power').val()) || 0;
        var fiberDistance = parseFloat($('#' + prefix + '_fiber_distance').val()) || 0;
        var fiberLoss = fiberDistance * this.fiberLossPerKm;
        var powerAfterFiber = inputPower - fiberLoss;
        
        var branchPower, relayPower, ports, splitterRatio;
        var branchPercent = null, relayPercent = null;
        
        if (type === 'equal') {
            var $splitter = $('#' + prefix + '_equal_splitter option:selected');
            var loss = parseFloat($splitter.data('loss')) || 0;
            ports = parseInt($splitter.data('ports')) || 0;
            splitterRatio = $splitter.val();
            
            if (!splitterRatio) {
                $('#' + prefix + '-power-flow').hide();
                return;
            }
            
            branchPower = powerAfterFiber - loss;
            relayPower = null; // No relay for equal splitter
            
        } else if (type === 'unequal') {
            var $ratio = $('#' + prefix + '_unequal_ratio option:selected');
            var branchLoss = parseFloat($ratio.data('branch-loss')) || 0;
            var relayLoss = parseFloat($ratio.data('relay-loss')) || 0;
            branchPercent = parseInt($ratio.data('branch-percent')) || 0;
            relayPercent = parseInt($ratio.data('relay-percent')) || 0;
            var ratioVal = $ratio.val();
            
            if (!ratioVal) {
                $('#' + prefix + '-power-flow').hide();
                return;
            }
            
            // Get branch splitter (optional, for additional split to customers)
            var $branchSplitter = $('#' + prefix + '_branch_splitter option:selected');
            var branchSplitterLoss = parseFloat($branchSplitter.data('loss')) || 0;
            ports = parseInt($branchSplitter.data('ports')) || 0;
            var branchSplitterVal = $branchSplitter.val();
            
            // Power calculations
            // Branch: power yang masuk ke splitter pelanggan (dikurangi branch loss)
            var powerToBranch = powerAfterFiber - branchLoss;
            // Relay: sisa power untuk cascade (dikurangi relay loss, yang kecil)
            relayPower = powerAfterFiber - relayLoss;
            
            if (branchSplitterVal) {
                // Jika ada branch splitter tambahan
                branchPower = powerToBranch - branchSplitterLoss;
                splitterRatio = ratioVal + ' + ' + branchSplitterVal;
            } else {
                // Langsung ke pelanggan tanpa splitter tambahan
                branchPower = powerToBranch;
                splitterRatio = ratioVal;
                ports = 1; // Single customer port from branch
            }
        }
        
        // Update visual display
        this.updateDisplay(prefix, {
            inputPower: inputPower,
            fiberLoss: fiberLoss,
            powerAfterFiber: powerAfterFiber,
            branchPower: branchPower,
            relayPower: relayPower,
            ports: ports,
            branchPercent: branchPercent,
            relayPercent: relayPercent,
            splitterRatio: splitterRatio,
            type: type
        });
        
        // Update hidden form fields
        this.updateFormFields(prefix, {
            inputPower: inputPower,
            fiberDistance: fiberDistance,
            fiberLoss: fiberLoss,
            branchPower: branchPower,
            relayPower: relayPower,
            splitterRatio: splitterRatio,
            type: type
        });
    },

    /**
     * Update visual display elements
     */
    updateDisplay: function(prefix, data) {
        var p = '#' + prefix;
        
        // Show power flow
        $(p + '-power-flow').show();
        
        // Input power
        $(p + '-display-input').text((data.inputPower >= 0 ? '+' : '') + data.inputPower.toFixed(1) + ' dBm');
        
        // Fiber loss
        $(p + '-display-fiber-loss').text('-' + data.fiberLoss.toFixed(2) + ' dB');
        
        // After fiber
        $(p + '-display-after-fiber').text(data.powerAfterFiber.toFixed(2) + ' dBm');
        
        // Branch power (to customers)
        $(p + '-display-branch-power').text(data.branchPower.toFixed(2) + ' dBm');
        var branchInfo = '';
        if (data.branchPercent) {
            branchInfo = data.branchPercent + '% ';
        }
        if (data.ports > 0) {
            branchInfo += data.ports + ' port';
        }
        $(p + '-display-branch-info').text(branchInfo || '--');
        
        // Relay power (to next ODP)
        if (data.relayPower !== null) {
            $(p + '-display-relay-power').text(data.relayPower.toFixed(2) + ' dBm');
            var relayInfo = data.relayPercent ? data.relayPercent + '% sisa' : 'Sisa';
            $(p + '-display-relay-info').text(relayInfo);
            $(p + '-relay-box').removeClass('d-none');
        } else {
            $(p + '-display-relay-power').text('--');
            $(p + '-display-relay-info').text('Tidak ada relay');
            $(p + '-relay-box').addClass('d-none');
        }
        
        // Status badge
        var statusBadge = $(p + '-status-badge');
        statusBadge.removeClass('badge-success badge-warning badge-danger badge-info badge-secondary');
        
        if (data.branchPower >= this.thresholds.optimal) {
            statusBadge.addClass('badge-success').html('<i class="fas fa-check-circle mr-1"></i> OPTIMAL - Power bagus untuk pelanggan');
        } else if (data.branchPower >= this.thresholds.warning) {
            statusBadge.addClass('badge-info').html('<i class="fas fa-info-circle mr-1"></i> CUKUP - Margin terbatas');
        } else if (data.branchPower >= this.thresholds.critical) {
            statusBadge.addClass('badge-warning').html('<i class="fas fa-exclamation-triangle mr-1"></i> MARGINAL - Perhatikan jarak kabel ke pelanggan');
        } else {
            statusBadge.addClass('badge-danger').html('<i class="fas fa-times-circle mr-1"></i> TIDAK CUKUP - ONU mungkin tidak sync');
        }
        
        // Color coding for branch power
        var branchPowerEl = $(p + '-display-branch-power').parent();
        branchPowerEl.removeClass('bg-success bg-info bg-warning bg-danger');
        if (data.branchPower >= this.thresholds.optimal) {
            branchPowerEl.css('background-color', '#28a745'); // Green
        } else if (data.branchPower >= this.thresholds.warning) {
            branchPowerEl.css('background-color', '#17a2b8'); // Info blue
        } else if (data.branchPower >= this.thresholds.critical) {
            branchPowerEl.css('background-color', '#ffc107'); // Yellow
            branchPowerEl.find('*').css('color', '#212529'); // Dark text
        } else {
            branchPowerEl.css('background-color', '#dc3545'); // Red
        }
    },

    /**
     * Update hidden form fields for submission
     */
    updateFormFields: function(prefix, data) {
        // Main hidden fields
        $('#input_power').val(data.inputPower);
        $('#fiber_distance').val(data.fiberDistance);
        $('#fiber_loss').val(data.fiberLoss.toFixed(2));
        $('#splitter_ratio').val(data.splitterRatio);
        $('#output_power').val(data.branchPower ? data.branchPower.toFixed(2) : '');
        $('#cascade_output_power').val(data.relayPower ? data.relayPower.toFixed(2) : '');
        
        // Config type
        $('#splitter_config_type').val(data.type === 'unequal' ? 'cascade' : 'equal');
        
        // Unequal specific
        if (data.type === 'unequal') {
            $('#unequal_ratio').val($('#' + prefix + '_unequal_ratio').val());
            $('#branch_splitter').val($('#' + prefix + '_branch_splitter').val());
            
            var $unequalOpt = $('#' + prefix + '_unequal_ratio option:selected');
            var $branchOpt = $('#' + prefix + '_branch_splitter option:selected');
            
            // unequal_loss = relay loss (small, for cascade power)
            // branch_loss = customer splitter loss
            $('#unequal_loss').val($unequalOpt.data('relay-loss') || '');
            $('#branch_loss').val($branchOpt.data('loss') || '');
            
            // Total loss = fiber + branch_loss (unequal's branch) + customer splitter loss
            var unequalBranchLoss = parseFloat($unequalOpt.data('branch-loss')) || 0;
            var customerSplitterLoss = parseFloat($branchOpt.data('loss')) || 0;
            var totalLoss = data.fiberLoss + unequalBranchLoss + customerSplitterLoss;
            $('#total_loss').val(totalLoss.toFixed(2));
        } else {
            $('#unequal_ratio').val('');
            $('#branch_splitter').val('');
            $('#unequal_loss').val('');
            
            var $equalOpt = $('#' + prefix + '_equal_splitter option:selected');
            var equalLoss = parseFloat($equalOpt.data('loss')) || 0;
            $('#branch_loss').val(equalLoss.toFixed(2));
            $('#total_loss').val((data.fiberLoss + equalLoss).toFixed(2));
        }
    },

    /**
     * Get power status label and class
     */
    getPowerStatus: function(power) {
        if (power >= this.thresholds.optimal) {
            return { label: 'OPTIMAL', class: 'success' };
        } else if (power >= this.thresholds.warning) {
            return { label: 'CUKUP', class: 'info' };
        } else if (power >= this.thresholds.critical) {
            return { label: 'MARGINAL', class: 'warning' };
        } else {
            return { label: 'TIDAK CUKUP', class: 'danger' };
        }
    },

    /**
     * Set input power for a prefix and recalculate
     */
    setInputPower: function(prefix, power) {
        $('#' + prefix + '_input_power').val(power);
        var type = $('#' + prefix + '_splitter_type').val();
        if (type) {
            this.calculate(prefix, type);
        }
    },

    /**
     * Fetch OLT TX power from database
     */
    fetchOltTxPower: function(oltId, ponPort, callback) {
        var self = this;
        
        if (!oltId) {
            if (callback) callback(null, 'OLT belum dipilih');
            return;
        }
        
        $.ajax({
            url: '/admin/odps/olt-pon-ports',
            method: 'GET',
            data: { olt_id: oltId },
            success: function(response) {
                if (response.success && response.pon_ports) {
                    // Find matching PON port
                    var txPower = null;
                    var source = 'default';
                    
                    // Parse ponPort to find matching record
                    var portNum = parseInt(ponPort) || 1;
                    var matchingPort = response.pon_ports.find(function(p) {
                        return p.port == portNum;
                    });
                    
                    if (matchingPort && matchingPort.has_data && matchingPort.tx_power !== null) {
                        txPower = parseFloat(matchingPort.tx_power);
                        source = 'database';
                    } else {
                        txPower = response.olt.default_tx_power || 4.0;
                        source = 'default';
                    }
                    
                    if (callback) {
                        callback({
                            success: true,
                            tx_power: txPower,
                            source: source,
                            olt_name: response.olt.name,
                            pon_ports: response.pon_ports
                        });
                    }
                } else {
                    if (callback) callback(null, response.message || 'Gagal mengambil data');
                }
            },
            error: function(xhr) {
                if (callback) callback(null, 'Error koneksi ke server');
            }
        });
    },

    /**
     * Fetch ODC output power - calculated from OLT TX minus ODC splitter loss
     * ODC typically uses 1:4 splitter = ~7dB loss, so if OLT TX = +4, ODC output ≈ -3 dBm
     */
    fetchOdcPower: function(odcId, callback) {
        if (!odcId) {
            if (callback) callback(null, 'ODC belum dipilih');
            return;
        }
        
        // Fetch ODC data including OLT info
        $.ajax({
            url: '/admin/odps/source-power',
            method: 'GET',
            data: { 
                connection_type: 'odc',
                odc_id: odcId 
            },
            success: function(response) {
                if (response.success && response.source_power !== null) {
                    callback({
                        success: true,
                        power: response.source_power,
                        source: response.is_auto ? 'database' : 'default',
                        odc_name: response.source_name,
                        message: response.message
                    });
                } else {
                    // Default ODC output power
                    callback({
                        success: true,
                        power: -3.0,
                        source: 'default',
                        odc_name: '',
                        message: 'Menggunakan default -3 dBm'
                    });
                }
            },
            error: function(xhr) {
                callback(null, 'Error koneksi ke server');
            }
        });
    }
};

// Initialize on document ready
$(function() {
    // Initialize for all prefixes
    SplitterCalculator.init('odc');
    SplitterCalculator.init('olt');
    SplitterCalculator.init('cascade');
    
    // Handle OLT selection - auto fetch TX power
    $('#olt_id').on('change', function() {
        var oltId = $(this).val();
        var ponPort = $('#olt_pon_port').val() || 1;
        
        if (oltId) {
            // Auto fetch TX power when OLT is selected
            SplitterCalculator.fetchOltTxPower(oltId, ponPort, function(result, error) {
                if (result && result.success) {
                    $('#olt_input_power').val(result.tx_power);
                    
                    var sourceText = result.source === 'database' 
                        ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>TX Power dari ' + result.olt_name + ': ' + result.tx_power + ' dBm</span>'
                        : '<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Default TX Power: ' + result.tx_power + ' dBm (data belum tersedia di database)</span>';
                    
                    $('#olt_power_source').html(sourceText);
                    
                    // Recalculate if splitter type is selected
                    var type = $('#olt_splitter_type').val();
                    if (type) SplitterCalculator.calculate('olt', type);
                } else {
                    $('#olt_power_source').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>' + (error || 'Gagal mengambil data') + '</span>');
                }
            });
        }
    });
    
    // Handle PON port change - refetch TX power
    $('#olt_pon_port').on('change', function() {
        var oltId = $('#olt_id').val();
        var ponPort = $(this).val() || 1;
        
        if (oltId) {
            SplitterCalculator.fetchOltTxPower(oltId, ponPort, function(result, error) {
                if (result && result.success) {
                    $('#olt_input_power').val(result.tx_power);
                    
                    var sourceText = result.source === 'database' 
                        ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>TX Power PON ' + ponPort + ': ' + result.tx_power + ' dBm</span>'
                        : '<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Default TX Power: ' + result.tx_power + ' dBm</span>';
                    
                    $('#olt_power_source').html(sourceText);
                    
                    var type = $('#olt_splitter_type').val();
                    if (type) SplitterCalculator.calculate('olt', type);
                }
            });
        }
    });
    
    // Handle ODC selection - auto fetch power
    $('#odc_id').on('change', function() {
        var odcId = $(this).val();
        
        if (odcId) {
            SplitterCalculator.fetchOdcPower(odcId, function(result, error) {
                if (result && result.success) {
                    $('#odc_input_power').val(result.power);
                    
                    var sourceText = result.source === 'database' 
                        ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Power dari ' + result.odc_name + ': ' + result.power + ' dBm</span>'
                        : '<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Default Power: ' + result.power + ' dBm</span>';
                    
                    $('#odc_power_source').html(sourceText);
                    
                    var type = $('#odc_splitter_type').val();
                    if (type) SplitterCalculator.calculate('odc', type);
                } else {
                    $('#odc_power_source').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>' + (error || 'Gagal mengambil data') + '</span>');
                }
            });
        }
    });
    
    // Handle manual fetch button click
    $(document).on('click', '.btn-fetch-tx-power', function() {
        var prefix = $(this).data('prefix');
        var btn = $(this);
        var originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        if (prefix === 'olt') {
            var oltId = $('#olt_id').val();
            var ponPort = $('#olt_pon_port').val() || 1;
            
            if (!oltId) {
                $('#olt_power_source').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Pilih OLT terlebih dahulu</span>');
                btn.prop('disabled', false).html(originalHtml);
                return;
            }
            
            SplitterCalculator.fetchOltTxPower(oltId, ponPort, function(result, error) {
                btn.prop('disabled', false).html(originalHtml);
                
                if (result && result.success) {
                    $('#olt_input_power').val(result.tx_power);
                    
                    var sourceText = result.source === 'database' 
                        ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>TX Power dari database: ' + result.tx_power + ' dBm</span>'
                        : '<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Default TX Power: ' + result.tx_power + ' dBm (sync OLT untuk update)</span>';
                    
                    $('#olt_power_source').html(sourceText);
                    
                    var type = $('#olt_splitter_type').val();
                    if (type) SplitterCalculator.calculate('olt', type);
                } else {
                    $('#olt_power_source').html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' + (error || 'Gagal mengambil data') + '</span>');
                }
            });
        } else if (prefix === 'odc') {
            var odcId = $('#odc_id').val();
            
            if (!odcId) {
                $('#odc_power_source').html('<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Pilih ODC terlebih dahulu</span>');
                btn.prop('disabled', false).html(originalHtml);
                return;
            }
            
            SplitterCalculator.fetchOdcPower(odcId, function(result, error) {
                btn.prop('disabled', false).html(originalHtml);
                
                if (result && result.success) {
                    $('#odc_input_power').val(result.power);
                    
                    var sourceText = result.source === 'database' 
                        ? '<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Power dari database: ' + result.power + ' dBm</span>'
                        : '<span class="text-info"><i class="fas fa-info-circle mr-1"></i>Default Power: ' + result.power + ' dBm</span>';
                    
                    $('#odc_power_source').html(sourceText);
                    
                    var type = $('#odc_splitter_type').val();
                    if (type) SplitterCalculator.calculate('odc', type);
                } else {
                    $('#odc_power_source').html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' + (error || 'Gagal mengambil data') + '</span>');
                }
            });
        }
    });
    
    // Handle parent ODP selection for cascade
    $('#parent_odp_id').on('change', function() {
        var $selected = $(this).find('option:selected');
        var cascadePower = parseFloat($selected.data('cascade-power')) || null;
        var outputPower = parseFloat($selected.data('output-power')) || null;
        var splitterLevel = parseInt($selected.data('splitter-level')) || 1;
        
        // Use cascade power if available, otherwise output power
        var parentPower = cascadePower || outputPower;
        
        if (parentPower !== null) {
            $('#parent_power_display').val(parentPower.toFixed(2) + ' dBm');
            $('#cascade_input_power').val(parentPower);
            
            // Update status indicator
            var status = SplitterCalculator.getPowerStatus(parentPower);
            if (status.class === 'success') {
                $('#parent_power_status').html('✅').attr('title', 'Power bagus');
            } else if (status.class === 'info' || status.class === 'warning') {
                $('#parent_power_status').html('⚠️').attr('title', 'Power cukup');
            } else {
                $('#parent_power_status').html('❌').attr('title', 'Power rendah');
            }
            
            // Show cascade splitter config
            $('#cascade-splitter-wrapper').show();
            $('#splitter_level_display').val('Level ' + (splitterLevel + 1));
            
            // Store parent power for calculations
            window.parentOdpPower = parentPower;
        } else {
            $('#parent_power_display').val('-- dBm');
            $('#parent_power_status').html('❓').attr('title', 'Belum ada data power');
            $('#splitter_level_display').val('Level ' + (splitterLevel + 1));
            $('#cascade-splitter-wrapper').hide();
            window.parentOdpPower = null;
        }
    });
});
