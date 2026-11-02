{{-- Quick Add Package Modal --}}
<div class="modal fade" id="quickAddPackageModal" tabindex="-1" role="dialog" aria-labelledby="quickAddPackageModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="quickAddPackageModalLabel">
                    <i class="fas fa-box-open mr-2"></i>Tambah Paket Baru
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quickPackageForm">
                <input type="hidden" id="quickPackageRouterId" name="router_id">
                <input type="hidden" id="quickPackageProfileId" name="profile_id">

                <div class="modal-body">
                    {{-- Selected Router info --}}
                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-server mr-2"></i>Router:
                            <strong id="quickPackageRouterName">-</strong>
                        </div>
                        <span class="badge badge-light text-primary">MikroTik</span>
                    </div>

                    {{-- Profile selection section --}}
                    <div class="form-group">
                        <label>Pilih PPP Profile MikroTik <span class="text-danger">*</span></label>
                        <div id="quickProfileLoading" class="text-center py-3 text-muted">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                            <p class="mb-0 small">Memuat PPP Profile dari router...</p>
                        </div>
                        <div id="quickProfileSelectWrapper" style="display: none;">
                            <select class="form-control" id="quickProfileSelect" required>
                                <option value="">-- Pilih PPP Profile --</option>
                            </select>
                            <small class="text-muted">PPP Profile yang tersinkronisasi dari router ini.</small>
                        </div>
                        <div id="quickProfileEmpty" class="alert alert-warning py-2 px-3 mt-2" style="display: none;">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Tidak ada PPP Profile baru yang tersedia pada router ini.
                            <small class="d-block mt-1">Pastikan Anda sudah melakukan sinkronisasi profile di menu <a href="{{ route('admin.ppp-profiles.index') }}" target="_blank" class="font-weight-bold">PPP Profiles</a>.</small>
                        </div>
                    </div>

                    {{-- Form package details (shown when profile is chosen) --}}
                    <div id="quickPackageDetails" style="display: none;">
                        {{-- Profile Info Card --}}
                        <div class="card bg-light mb-3">
                            <div class="card-body py-2 px-3">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <small class="text-muted d-block">Profile MikroTik</small>
                                        <strong id="quickDisplayProfileName">-</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted d-block">Rate Limit</small>
                                        <strong id="quickDisplayRateLimit" class="text-info">-</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <small class="text-muted d-block">Remote Address / Pool</small>
                                        <span id="quickDisplayRemoteAddress">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nama Paket <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quickPackageName" name="name" required placeholder="Contoh: 10 Mbps Home">
                            <small class="text-muted">Nama paket yang ditampilkan pada sistem dan tagihan</small>
                        </div>

                        <div class="form-group">
                            <label>Nama Paket untuk Invoice</label>
                            <select class="form-control" id="quickPackageInvoiceLabel" name="invoice_label">
                                <option value="">Otomatis dari kecepatan profile</option>
                                <option value="1 Mbps">1 Mbps</option>
                                <option value="2 Mbps">2 Mbps</option>
                                <option value="5 Mbps">5 Mbps</option>
                                <option value="10 Mbps">10 Mbps</option>
                                <option value="15 Mbps">15 Mbps</option>
                                <option value="20 Mbps">20 Mbps</option>
                                <option value="30 Mbps">30 Mbps</option>
                                <option value="50 Mbps">50 Mbps</option>
                                <option value="100 Mbps">100 Mbps</option>
                                <option value="__custom__">Nama lain...</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="quickPackageInvoiceLabelCustom"
                                   placeholder="Contoh: Internet Premium 10 Mbps">
                            <small class="text-muted">Label komersial pada invoice pelanggan</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Harga (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quickPackagePrice" name="price" 
                                           min="0" step="500" required placeholder="Contoh: 150000">
                                    <div id="quickPpnCalculation" class="mt-2 p-2 bg-light rounded" style="display:none; font-size: 0.85rem;">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Harga sebelum PPN:</span>
                                            <span class="font-weight-bold" id="quickPriceBeforePpn">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">PPN 11%:</span>
                                            <span class="text-info" id="quickPpnAmount">-</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Total (include PPN):</span>
                                            <span class="font-weight-bold text-success" id="quickTotalWithPpn">-</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">Harga sudah termasuk PPN 11%</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Masa Berlaku (hari) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quickPackageValidity" name="validity_days" 
                                           min="1" value="30" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi <small class="text-muted">(opsional)</small></label>
                            <textarea class="form-control" id="quickPackageDescription" name="description" rows="2" 
                                      placeholder="Deskripsi singkat paket untuk pelanggan"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="quickPackageActive" name="is_active" value="1" checked>
                                    <label class="custom-control-label" for="quickPackageActive">Paket Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="quickPackagePublic" name="is_public" value="1" checked>
                                    <label class="custom-control-label" for="quickPackagePublic">Tampil di Portal</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnQuickSavePackage" style="display: none;">
                        <i class="fas fa-save mr-1"></i> Simpan & Pilih Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    let quickProfilesData = [];
    let quickSelectedProfile = null;

    function quickFormatRupiah(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function quickCalculatePpn(priceWithPpn) {
        if (!priceWithPpn || priceWithPpn <= 0) {
            $('#quickPpnCalculation').hide();
            return;
        }
        const ppnRate = 0.11;
        const priceBeforePpn = Math.round(priceWithPpn / (1 + ppnRate));
        const ppnAmount = priceWithPpn - priceBeforePpn;

        $('#quickPriceBeforePpn').text('Rp ' + quickFormatRupiah(priceBeforePpn));
        $('#quickPpnAmount').text('+ Rp ' + quickFormatRupiah(ppnAmount));
        $('#quickTotalWithPpn').text('Rp ' + quickFormatRupiah(priceWithPpn));
        $('#quickPpnCalculation').show();
    }

    function quickSetInvoiceLabel(value) {
        const select = $('#quickPackageInvoiceLabel');
        const custom = $('#quickPackageInvoiceLabelCustom');
        const optionExists = value && select.find('option').filter(function() { return this.value === value; }).length;
        if (optionExists) {
            select.val(value);
            custom.val('').addClass('d-none');
        } else if (value) {
            select.val('__custom__');
            custom.val(value).removeClass('d-none');
        } else {
            select.val('');
            custom.val('').addClass('d-none');
        }
    }

    function quickSetInvoiceLabelFromRate(rateLimit) {
        const match = String(rateLimit || '').match(/([0-9]+(?:\.[0-9]+)?)([KMG])(?:b)?/i);
        if (!match) return quickSetInvoiceLabel('');
        let mbps = parseFloat(match[1]);
        const unit = match[2].toUpperCase();
        if (unit === 'K') mbps /= 1000;
        if (unit === 'G') mbps *= 1000;
        quickSetInvoiceLabel(`${mbps} Mbps`);
    }

    $('#quickPackageInvoiceLabel').on('change', function() {
        const isCustom = $(this).val() === '__custom__';
        $('#quickPackageInvoiceLabelCustom').toggleClass('d-none', !isCustom);
        if (!isCustom) $('#quickPackageInvoiceLabelCustom').val('');
    });

    $('#quickPackagePrice').on('input', function() {
        const price = parseInt($(this).val()) || 0;
        quickCalculatePpn(price);
    });

    // Trigger open Quick Add Package modal
    $(document).on('click', '#btnQuickAddPackage', function(e) {
        e.preventDefault();
        const routerId = $('#router_id').val();
        if (!routerId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Router Terlebih Dahulu',
                    text: 'Silakan pilih Router terlebih dahulu di tab Layanan sebelum menambahkan paket baru.',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Silakan pilih Router terlebih dahulu di tab Layanan sebelum menambahkan paket baru.');
            }
            return;
        }

        const routerName = $('#router_id option:selected').text().trim() || 'Router';
        $('#quickPackageRouterName').text(routerName);
        $('#quickPackageRouterId').val(routerId);
        $('#quickPackageProfileId').val('');
        quickSelectedProfile = null;

        // Reset UI states
        $('#quickProfileLoading').show();
        $('#quickProfileSelectWrapper').hide();
        $('#quickProfileEmpty').hide();
        $('#quickPackageDetails').hide();
        $('#btnQuickSavePackage').hide();
        $('#quickPpnCalculation').hide();
        $('#quickProfileSelect').html('<option value="">-- Pilih PPP Profile --</option>');

        $('#quickAddPackageModal').modal('show');

        // Fetch profiles for selected router
        $.get(`{{ url('admin/packages/profiles') }}/${routerId}`, function(response) {
            $('#quickProfileLoading').hide();
            if (response.success && response.data && response.data.length > 0) {
                quickProfilesData = response.data;
                let html = '<option value="">-- Pilih PPP Profile --</option>';
                let availableCount = 0;

                response.data.forEach(function(profile, index) {
                    const hasPackage = profile.has_package;
                    const rateLimit = profile.rate_limit || 'Unlimited';
                    if (!hasPackage) availableCount++;

                    html += `<option value="${profile.id}" data-index="${index}" ${hasPackage ? 'disabled' : ''}>
                        ${profile.name} (${rateLimit})${hasPackage ? ' - [Sudah ada paket]' : ''}
                    </option>`;
                });

                $('#quickProfileSelect').html(html);
                $('#quickProfileSelectWrapper').show();

                if (availableCount === 0) {
                    $('#quickProfileEmpty').show();
                }
            } else {
                $('#quickProfileEmpty').show();
            }
        }).fail(function(xhr) {
            $('#quickProfileLoading').hide();
            toastr.error(xhr.responseJSON?.message || 'Gagal memuat PPP Profile dari router');
        });
    });

    // Profile select change
    $('#quickProfileSelect').on('change', function() {
        const profileId = $(this).val();
        if (!profileId) {
            quickSelectedProfile = null;
            $('#quickPackageProfileId').val('');
            $('#quickPackageDetails').hide();
            $('#btnQuickSavePackage').hide();
            return;
        }

        const index = $(this).find(':selected').data('index');
        quickSelectedProfile = quickProfilesData[index];

        if (quickSelectedProfile) {
            $('#quickPackageProfileId').val(quickSelectedProfile.id);
            $('#quickDisplayProfileName').text(quickSelectedProfile.name);
            $('#quickDisplayRateLimit').text(quickSelectedProfile.rate_limit || 'Unlimited');
            $('#quickDisplayRemoteAddress').text(quickSelectedProfile.remote_address || '-');

            $('#quickPackageName').val(quickSelectedProfile.name);
            quickSetInvoiceLabelFromRate(quickSelectedProfile.rate_limit);
            $('#quickPackagePrice').val('');
            $('#quickPackageValidity').val(30);
            $('#quickPackageDescription').val('');
            $('#quickPackageActive').prop('checked', true);
            $('#quickPackagePublic').prop('checked', true);
            $('#quickPpnCalculation').hide();

            $('#quickPackageDetails').slideDown();
            $('#btnQuickSavePackage').show();
        }
    });

    // Submit package form
    $('#quickPackageForm').on('submit', function(e) {
        e.preventDefault();

        const profileId = $('#quickPackageProfileId').val();
        if (!profileId) {
            toastr.error('Silakan pilih PPP Profile terlebih dahulu.');
            return;
        }

        const invoiceLabelSelect = $('#quickPackageInvoiceLabel');
        let invoiceLabelVal = invoiceLabelSelect.val();
        if (invoiceLabelVal === '__custom__') {
            invoiceLabelVal = $('#quickPackageInvoiceLabelCustom').val().trim();
        }

        const btn = $('#btnQuickSavePackage');
        const origHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...').prop('disabled', true);

        const formData = {
            _token: '{{ csrf_token() }}',
            router_id: $('#quickPackageRouterId').val(),
            profile_id: profileId,
            name: $('#quickPackageName').val(),
            invoice_label: invoiceLabelVal,
            price: $('#quickPackagePrice').val(),
            validity_days: $('#quickPackageValidity').val(),
            description: $('#quickPackageDescription').val(),
            is_active: $('#quickPackageActive').is(':checked') ? 1 : 0,
            is_public: $('#quickPackagePublic').is(':checked') ? 1 : 0,
        };

        $.ajax({
            url: '{{ route("admin.packages.store") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                btn.html(origHtml).prop('disabled', false);
                if (response.success) {
                    toastr.success(response.message || 'Paket berhasil dibuat!');
                    $('#quickAddPackageModal').modal('hide');

                    const newPkg = response.data;
                    if (newPkg) {
                        const pkgName = newPkg.name;
                        const pkgPrice = newPkg.price || 0;
                        const formattedPrice = quickFormatRupiah(pkgPrice);
                        const optionText = `${pkgName} - Rp ${formattedPrice}`;

                        const $packageSelect = $('#package_id');
                        
                        // Enable package select if disabled
                        $packageSelect.prop('disabled', false);

                        // If "Pilih Router dulu..." or "Memuat..." is the only option, remove it
                        $packageSelect.find('option[value=""]').remove();
                        $packageSelect.prepend('<option value="">-- Pilih Paket --</option>');

                        // Check if option already exists
                        let $existingOption = $packageSelect.find(`option[value="${newPkg.id}"]`);
                        if ($existingOption.length > 0) {
                            $existingOption.text(optionText).attr('data-price', pkgPrice);
                        } else {
                            $packageSelect.append(
                                `<option value="${newPkg.id}" data-price="${pkgPrice}">${optionText}</option>`
                            );
                        }

                        // Add to packagesData array if exists (used in create.blade.php)
                        if (typeof packagesData !== 'undefined' && Array.isArray(packagesData)) {
                            packagesData.push({
                                id: newPkg.id,
                                name: newPkg.name,
                                price: newPkg.price,
                                speed_down: newPkg.speed_down,
                                speed_up: newPkg.speed_up,
                                rate_limit: newPkg.rate_limit
                            });
                        }

                        // Select the new package and trigger change event
                        $packageSelect.val(newPkg.id).trigger('change');

                        // If select2 is used, notify select2 of changes
                        if ($.fn.select2 && $packageSelect.hasClass('select2')) {
                            $packageSelect.trigger('change.select2');
                        }
                    }
                } else {
                    toastr.error(response.message || 'Gagal membuat paket.');
                }
            },
            error: function(xhr) {
                btn.html(origHtml).prop('disabled', false);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    let msg = '';
                    for (let f in errors) {
                        msg += errors[f].join('<br>') + '<br>';
                    }
                    toastr.error(msg);
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan paket.');
                }
            }
        });
    });
});
</script>
