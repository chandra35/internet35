@extends('layouts.admin')

@section('title', 'Buat Invoice')

@section('page-title', 'Buat Invoice Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">Buat Baru</li>
@endsection

@push('css')
<style>
    .item-row {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    .remove-item {
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<form action="{{ route('admin.invoices.store') }}" method="POST" id="invoiceForm">
    @csrf
    
    <div class="row">
        <div class="col-md-8">
            <!-- Invoice Items -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-list mr-2"></i>Item Invoice
                    </h3>
                </div>
                <div class="card-body">
                    <div id="itemsContainer">
                        <div class="item-row" data-index="0">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Deskripsi <span class="text-danger">*</span></label>
                                        <input type="text" name="items[0][description]" 
                                               class="form-control" required
                                               placeholder="Contoh: Layanan Internet Paket Home 20 Mbps">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Jumlah <span class="text-danger">*</span></label>
                                        <input type="number" name="items[0][amount]" 
                                               class="form-control item-amount" required
                                               min="0" step="1000" placeholder="0"
                                               onchange="calculateTotal()">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-block remove-item" 
                                                onclick="removeItem(this)" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                        <i class="fas fa-plus mr-1"></i> Tambah Item
                    </button>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sticky-note mr-2"></i>Catatan
                    </h3>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Catatan tambahan untuk invoice...">{{ $popSetting?->invoice_notes }}</textarea>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Customer & Period -->
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title text-white">
                        <i class="fas fa-user mr-2"></i>Pelanggan & Periode
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Pelanggan <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-control select2" required 
                                id="customerSelect" onchange="loadCustomerPackage()">
                            <option value="">-- Pilih Pelanggan --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
                                        data-package="{{ $customer->package?->name }}"
                                        data-price="{{ $customer->package?->price ?? 0 }}">
                                    {{ $customer->name }} ({{ $customer->customer_id }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div id="packageInfo" class="alert alert-light" style="display:none;">
                        <strong>Paket:</strong> <span id="packageName">-</span><br>
                        <strong>Harga:</strong> Rp <span id="packagePrice">0</span>
                        <button type="button" class="btn btn-xs btn-primary float-right" onclick="usePackagePrice()">
                            Gunakan
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Invoice <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" 
                               value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" 
                               value="{{ old('due_date', $dueDate->format('Y-m-d')) }}" required>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <label>Periode Awal <span class="text-danger">*</span></label>
                        <input type="date" name="period_start" class="form-control" 
                               value="{{ old('period_start', $periodStart->format('Y-m-d')) }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Periode Akhir <span class="text-danger">*</span></label>
                        <input type="date" name="period_end" class="form-control" 
                               value="{{ old('period_end', $periodEnd->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title text-white">
                        <i class="fas fa-calculator mr-2"></i>Ringkasan
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">Rp <span id="subtotal">0</span></td>
                        </tr>
                        <tr>
                            <td>
                                Diskon
                                <input type="number" name="discount_amount" id="discountInput"
                                       class="form-control form-control-sm d-inline-block ml-2" 
                                       style="width: 100px" min="0" value="0"
                                       onchange="calculateTotal()">
                            </td>
                            <td class="text-right text-danger">- Rp <span id="discount">0</span></td>
                        </tr>
                        @if($popSetting?->ppn_enabled)
                        <tr>
                            <td>PPN ({{ $popSetting->ppn_percentage }}%)</td>
                            <td class="text-right">Rp <span id="tax">0</span></td>
                        </tr>
                        @endif
                        <tr class="font-weight-bold">
                            <td>Total</td>
                            <td class="text-right text-primary">Rp <span id="total">0</span></td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Simpan Invoice
                    </button>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 1;
const ppnEnabled = {{ $popSetting?->ppn_enabled ? 'true' : 'false' }};
const ppnPercentage = {{ $popSetting?->ppn_percentage ?? 0 }};

function addItem() {
    const container = document.getElementById('itemsContainer');
    const html = `
        <div class="item-row" data-index="${itemIndex}">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Deskripsi <span class="text-danger">*</span></label>
                        <input type="text" name="items[${itemIndex}][description]" 
                               class="form-control" required
                               placeholder="Deskripsi item">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Jumlah <span class="text-danger">*</span></label>
                        <input type="number" name="items[${itemIndex}][amount]" 
                               class="form-control item-amount" required
                               min="0" step="1000" placeholder="0"
                               onchange="calculateTotal()">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-block remove-item" onclick="removeItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    itemIndex++;
    updateRemoveButtons();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    calculateTotal();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.remove-item');
        btn.style.display = rows.length > 1 ? 'block' : 'none';
    });
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-amount').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const taxable = subtotal - discount;
    const tax = ppnEnabled ? (taxable * ppnPercentage / 100) : 0;
    const total = taxable + tax;
    
    document.getElementById('subtotal').textContent = formatNumber(subtotal);
    document.getElementById('discount').textContent = formatNumber(discount);
    if (document.getElementById('tax')) {
        document.getElementById('tax').textContent = formatNumber(tax);
    }
    document.getElementById('total').textContent = formatNumber(total);
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(Math.round(num));
}

function loadCustomerPackage() {
    const select = document.getElementById('customerSelect');
    const option = select.options[select.selectedIndex];
    const packageInfo = document.getElementById('packageInfo');
    
    if (option.value) {
        const packageName = option.dataset.package || '-';
        const packagePrice = option.dataset.price || 0;
        
        document.getElementById('packageName').textContent = packageName;
        document.getElementById('packagePrice').textContent = formatNumber(packagePrice);
        packageInfo.style.display = 'block';
    } else {
        packageInfo.style.display = 'none';
    }
}

function usePackagePrice() {
    const select = document.getElementById('customerSelect');
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.dataset.price) || 0;
    const packageName = option.dataset.package || 'Layanan Internet';
    
    // Set first item
    const firstDesc = document.querySelector('input[name="items[0][description]"]');
    const firstAmount = document.querySelector('input[name="items[0][amount]"]');
    
    firstDesc.value = 'Layanan Internet ' + packageName;
    firstAmount.value = price;
    
    calculateTotal();
}

// Initialize
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4'
    });
    calculateTotal();
});
</script>
@endpush
