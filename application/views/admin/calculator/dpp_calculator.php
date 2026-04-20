<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dpp-calc-wrap { max-width: 1200px; margin: 0 auto; }
.dpp-calc-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e4e8eb; }
.dpp-calc-header-left { flex: 0 0 auto; }
.dpp-calc-header-logo { flex: 0 0 auto; }
.dpp-calc-header-logo img { max-height: 48px; max-width: 160px; object-fit: contain; }
.dpp-calc-header-right { flex: 0 0 auto; text-align: right; }
.dpp-calc-title { font-size: 1.5rem; font-weight: 700; margin: 0; color: #2c3e50; }
.dpp-calc-formula { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; }
.dpp-calc-billable-note { font-size: 0.9rem; color: #495057; }
.dpp-calc-grid { display: grid; grid-template-columns: 280px 1fr 280px; gap: 2rem; align-items: start; }
@media (max-width: 992px) { .dpp-calc-grid { grid-template-columns: 1fr; } }
.dpp-col-inputs .form-group { margin-bottom: 1rem; }
.dpp-col-inputs label { font-weight: 600; color: #374151; margin-bottom: 0.35rem; display: block; }
.dpp-col-inputs .form-control { border-radius: 6px; border: 1px solid #d1d5db; }
.dpp-calc-buttons { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
.dpp-btn-calc { background: #2563eb; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
.dpp-btn-calc:hover { background: #1d4ed8; }
.dpp-btn-reset { background: #6b7280; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 500; cursor: pointer; }
.dpp-btn-reset:hover { background: #4b5563; }
.dpp-col-mid { background: #f8fafc; border-radius: 8px; padding: 1.25rem; }
.dpp-mid-title { font-weight: 700; font-size: 0.9rem; color: #374151; margin-bottom: 1rem; }
.dpp-mid-row { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
.dpp-mid-row:last-child { border-bottom: none; }
.dpp-mid-label { color: #6b7280; }
.dpp-mid-value { font-weight: 600; color: #111; }
.dpp-discount-yes { color: #059669; font-weight: 600; }
.dpp-col-final { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1.5rem; }
.dpp-final-title { font-size: 1rem; font-weight: 700; color: #1e40af; margin-bottom: 0.75rem; }
.dpp-final-price { font-size: 2rem; font-weight: 800; color: #1e3a8a; margin: 0.5rem 0 1rem; }
.dpp-final-row { display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.9rem; }
.dpp-final-total { font-weight: 700; font-size: 1.1rem; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 2px solid #3b82f6; }
</style>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <div class="dpp-calc-wrap">
                    <?php $dpp_logo = get_admin_header_logo_url(); ?>
                    <div class="dpp-calc-header">
                        <div class="dpp-calc-header-left">
                            <h4 class="dpp-calc-title"><?= _l('ddp_pricing_calculator'); ?></h4>
                            <span class="dpp-calc-billable-note"><?= _l('ddp_billable_note'); ?> (1 CBM = <?= (int)($settings['kg_per_cbm'] ?? 500); ?> KG)</span>
                        </div>
                        <div class="dpp-calc-header-logo">
                            <?php if ($dpp_logo): ?>
                                <a href="<?= admin_url(); ?>"><img src="<?= htmlspecialchars($dpp_logo); ?>" alt="<?= e(get_option('companyname')); ?>"></a>
                            <?php else: ?>
                                <span class="tw-text-xl tw-font-semibold"><?= e(get_option('companyname')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dpp-calc-header-right">
                            
                        </div>
                    </div>

                    <div class="dpp-calc-grid">
                        <!-- Left: Inputs -->
                        <div class="dpp-col-inputs">
                            <div class="form-group">
                                <label for="dpp-category"><?= _l('category'); ?></label>
                                <select id="dpp-category" class="form-control" data-width="100%">
                                    <option value=""><?= _l('ddp_select_category'); ?></option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int)$c['id']; ?>"><?= htmlspecialchars($c['category']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dpp-material"><?= _l('material'); ?></label>
                                <select id="dpp-material" class="form-control" data-width="100%">
                                    <option value=""><?= _l('ddp_select_material'); ?></option>
                                    <?php foreach ($materials as $m): ?>
                                        <option value="<?= htmlspecialchars($m['material']); ?>"><?= htmlspecialchars($m['material']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dpp-paymode"><?= _l('ddp_payment_mode'); ?></label>
                                <select id="dpp-paymode" class="form-control">
                                    <option value="A">Total Order Value Payment</option>
                                    <option value="B">Only Service Value Payment</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dpp-product-value"><?= _l('ddp_product_value_eur'); ?></label>
                                <input type="number" id="dpp-product-value" class="form-control" step="0.01" min="0" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label for="dpp-actual-cbm"><?= _l('ddp_actual_cbm'); ?></label>
                                <input type="number" id="dpp-actual-cbm" class="form-control" step="0.01" min="0" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label for="dpp-weight-kg"><?= _l('ddp_weight_kg'); ?></label>
                                <input type="number" id="dpp-weight-kg" class="form-control" step="0.01" min="0" placeholder="0">
                            </div>
                            <div class="dpp-calc-buttons">
                                <button type="button" id="dpp-btn-calculate" class="dpp-btn-calc"><?= _l('ddp_calculate'); ?></button>
                                <button type="button" id="dpp-btn-reset" class="dpp-btn-reset"><?= _l('ddp_reset'); ?></button>
                            </div>
                        </div>

                        <!-- Middle: Intermediate results -->
                        <div class="dpp-col-mid">
                            <div class="dpp-mid-title"><?= _l('ddp_intermediate_results'); ?></div>
                            <div class="dpp-mid-row"><span class="dpp-mid-label"><?= _l('ddp_billable_cbm'); ?></span><span class="dpp-mid-value" id="out-billable-cbm">—</span></div>
                            <div class="dpp-mid-row"><span class="dpp-mid-label"><?= _l('ddp_material_excise'); ?></span><span class="dpp-mid-value" id="out-excise">—</span></div>
                            <div class="dpp-mid-row"><span class="dpp-mid-label"><?= _l('ddp_discount_applied'); ?></span><span class="dpp-mid-value" id="out-discount-applied">—</span></div>
                            <div class="dpp-mid-row"><span class="dpp-mid-label"><?= _l('ddp_discount_value'); ?></span><span class="dpp-mid-value" id="out-discount-value">—</span></div>
                        </div>

                        <!-- Right: Final DDP price -->
                        <div class="dpp-col-final">
                            <div class="dpp-final-title"><?= _l('ddp_final_ddp_price'); ?></div>
                            <div class="dpp-final-price" id="out-final-price">—</div>
                            <div class="dpp-final-row"><span><?= _l('ddp_service_fee_final'); ?></span><span id="out-summary-service-fee">—</span></div>
                            <div class="dpp-final-row"><span><?= _l('ddp_discount'); ?></span><span id="out-summary-discount">—</span></div>
                            <div class="dpp-final-row"><span><?= _l('ddp_customs_duty'); ?></span><span id="out-summary-customs-duty">—</span></div>
                            <div class="dpp-final-row"><span><?= _l('ddp_vat'); ?></span><span id="out-summary-vat">—</span></div>
                            <div class="dpp-final-row"><span><?= _l('ddp_excise'); ?></span><span id="out-summary-excise">—</span></div>
                            <div class="dpp-final-row dpp-final-total"><span><?= _l('ddp_total'); ?></span><span id="out-summary-total">—</span></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function() {
    var settings = <?= json_encode($settings); ?>;
    var materialMaster = <?= json_encode(array_column($materials, null, 'material')); ?>;
    var ladderRates = <?= json_encode(isset($ladder_rates) ? $ladder_rates : []); ?>;
    var currentCategory = {};

    function num(x) { return typeof x === 'number' && !isNaN(x) ? x : parseFloat(x) || 0; }
    function fmtEur(x) { return num(x).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }

    function getTierRate(cbm) {
        if (!ladderRates || !ladderRates.length) return 750;
        for (var i = 0; i < ladderRates.length; i++) {
            var r = ladderRates[i];
            var min = num(r.min_cbm);
            var max = num(r.max_cbm);
            if (cbm >= min && cbm <= max) return num(r.rate);
        }
        if (cbm < num(ladderRates[0].min_cbm)) return num(ladderRates[0].rate);
        return num(ladderRates[ladderRates.length - 1].rate);
    }

    function runDpp() {
        var materialName = (document.getElementById('dpp-material').value || '').trim();
        var payMode = (document.getElementById('dpp-paymode').value || 'A').toUpperCase();
        var productPrice = num(document.getElementById('dpp-product-value').value);
        var actualCbm = num(document.getElementById('dpp-actual-cbm').value);
        var weightKg = num(document.getElementById('dpp-weight-kg').value);

        var kgPerCbm = num(settings.kg_per_cbm) || 500;
        var othersThreshold = num(settings.others_tiering_threshold) || 800;
        var discountValueDensityThreshold = num(settings.discount_value_density_threshold) || 1000;
        var discountFeePerCbmThreshold = num(settings.discount_fee_per_cbm_threshold) || 1000;
        var discountRate = num(settings.discount_rate) || 0.05;
        var paymodeBSurcharge = num(settings.paymode_b_surcharge) || 1.02;

        // 1. Billable CBM = MAX(ActualCBM, WeightKG / KG_per_CBM)
        var billableCbm = Math.max(actualCbm, weightKg / kgPerCbm);

        var cat = currentCategory;
        var percentFloor = num(cat.floor_percent_of_pv) / 100 || 0;
        var eurPerCbmFloor = num(cat.floor_euro_per_cbm) || 0;
        // Others = when category has floor_percent_of_pv = 0 (not by name)
        var isOthers = (num(cat.floor_percent_of_pv) === 0);

        // 2. Rate per CBM (Others logic: only tier when value density > threshold, else 750)
        var ratePerCbm;
        if (isOthers) {
            if (billableCbm > 0 && productPrice / billableCbm > othersThreshold) {
                ratePerCbm = getTierRate(billableCbm);
            } else {
                ratePerCbm = 750;
            }
        } else {
            ratePerCbm = getTierRate(billableCbm);
        }

        // 3. Service Fee Base = BillableCBM × RatePerCBM
        var serviceFeeBase = billableCbm * ratePerCbm;

        // 4 & 5. Category floors (already have percentFloor, eurPerCbmFloor)

        // 6. Category minimum fee (including product floor % from settings, e.g. 45% = ProductPrice × 0.45)
        var floorPercentProduct = num(settings.floor_percent_product) / 100 || 0.45;
        var floorFromProduct = productPrice * floorPercentProduct;
        var minFee = Math.max(productPrice * percentFloor, billableCbm * eurPerCbmFloor, floorFromProduct);

        // 7. Final service fee
        var feeFinal = Math.max(serviceFeeBase, minFee);
        // 8 & 9. Material excise
        var mat = materialMaster[materialName] || {};
        var exciseType = (mat.excise_type || '').toLowerCase();
        var exciseValue = num(mat.excise_value) || 0;

        // 10. Excise amount
        var excise = (exciseType === 'per_kg') ? (weightKg * exciseValue) : 0;

        // 11. Value density (80%)
        var valueDensity80 = billableCbm > 0 ? (0.8 * productPrice) / billableCbm : 0;

        // 12. Fee per CBM
        var feePerCbm = billableCbm > 0 ? feeFinal / billableCbm : 0;

        // 13. Discount (trigger: ValueDensity80 >= threshold AND FeePerCBM >= threshold; value = rate × FeeFinal)
        var discount = (valueDensity80 >= discountValueDensityThreshold && feePerCbm >= discountFeePerCbmThreshold)
            ? (discountRate * feeFinal) : 0;

        // 14 & 15. Totals (A = product price + service fee; B = fee part + 2% on (fee+product))
        var totalA = productPrice + feeFinal - discount + excise;
        var surchargeRate = paymodeBSurcharge - 1;
        var totalB = (feeFinal - discount + excise) + (productPrice + feeFinal) * surchargeRate;

        // Round up to nearest 5 (2280.34→2285, 2223→2225, 2226→2230)
        function ceilTo5(x) { return Math.ceil(num(x) / 5) * 5; }
        totalA = ceilTo5(totalA);
        totalB = ceilTo5(totalB);

        // 16. Final output
        var finalOutput = (payMode === 'A') ? totalA : (payMode === 'B') ? totalB : '';

        // Outputs
        document.getElementById('out-billable-cbm').textContent = billableCbm > 0 ? billableCbm.toFixed(2) : '—';
        document.getElementById('out-excise').textContent = fmtEur(excise);
        document.getElementById('out-discount-applied').textContent = discount > 0 ? 'YES' : 'NO';
        document.getElementById('out-discount-applied').className = 'dpp-mid-value' + (discount > 0 ? ' dpp-discount-yes' : '');
        document.getElementById('out-discount-value').textContent = fmtEur(discount);

        document.getElementById('out-final-price').textContent = finalOutput !== '' ? fmtEur(finalOutput) : '—';
        document.getElementById('out-summary-service-fee').textContent = fmtEur(feeFinal);
        document.getElementById('out-summary-discount').textContent = discount > 0 ? '-' + fmtEur(discount) : '0.00 €';
        var customsDuty = (cat.customs_duty !== undefined && cat.customs_duty !== null && cat.customs_duty !== '') ? num(cat.customs_duty) : 0;
        var vatPercent = (cat.vat_integre_percent !== undefined && cat.vat_integre_percent !== null && cat.vat_integre_percent !== '') ? num(cat.vat_integre_percent) : 0;
        document.getElementById('out-summary-customs-duty').textContent = customsDuty + '%';
        document.getElementById('out-summary-vat').textContent = vatPercent + '%';
        document.getElementById('out-summary-excise').textContent = fmtEur(excise);
        document.getElementById('out-summary-total').textContent = finalOutput !== '' ? fmtEur(finalOutput) : '—';
    }

    document.getElementById('dpp-btn-calculate').addEventListener('click', runDpp);
    document.getElementById('dpp-btn-reset').addEventListener('click', function() {
        document.getElementById('dpp-product-value').value = '';
        document.getElementById('dpp-actual-cbm').value = '';
        document.getElementById('dpp-weight-kg').value = '';
        document.getElementById('dpp-category').value = '';
        document.getElementById('dpp-material').value = '';
        document.getElementById('dpp-paymode').value = 'A';
        currentCategory = {};
        runDpp();
    });

    document.getElementById('dpp-category').addEventListener('change', function() {
        var id = (this.value || '').trim();
        if (!id) {
            currentCategory = {};
            runDpp();
            return;
        }
        $.get(admin_url + 'calculators/get_category/' + id, function(res) {
            var data = typeof res === 'string' ? JSON.parse(res) : res;
            currentCategory = data || {};
            runDpp();
        }).fail(function() {
            currentCategory = {};
            runDpp();
        });
    });
    document.getElementById('dpp-material').addEventListener('change', runDpp);
    document.getElementById('dpp-paymode').addEventListener('change', runDpp);

    runDpp();
})();
</script>
</body>
</html>
