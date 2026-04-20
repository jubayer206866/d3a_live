<table class="table items items-preview purchase-order-items-preview" data-type="purchase_order">
    <thead>
        <tr>
            <th align="center">#</th>
            <th><?php echo _l('product_code'); ?></th>
            <th><?php echo _l('product_name'); ?></th>
            <th><?php echo _l('cartons'); ?></th>
            <th><?php echo _l('pieces_carton'); ?></th>
            <th><?php echo _l('total_pieces'); ?></th>
            <th><?php echo _l('price'); ?></th>
            <th><?php echo _l('price_total'); ?></th>
            <th>G.W</th>
            <th>T.G.W</th>
            <th>N.W</th>
            <th>T.N.W</th>
            <th>CBM</th>
            <th>Total CBM</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $count = 1;
        $cartons = 0;
        $totalPieces = 0;
        $Pricetotal = 0;
        $Total_net_weight = 0;
        $Total_gross_weight = 0;
        $Total_cbm = 0;

        foreach($purchase_order_detail as $po){ ?>
            <tr>
                <td align="center"><?php echo $count; ?></td>
                <td><?php echo !empty($po['item_code']) ? pur_html_entity_decode($po['item_code']) : '-'; ?></td>
                <td><?php echo !empty($po['item_name']) ? pur_html_entity_decode($po['item_name']) : '-'; ?></td>
                <td align="right"><?php echo !empty($po['koli']) ? $po['koli'] : '0'; ?></td>
                <td align="right"><?php echo !empty($po['cope_koli']) ? $po['cope_koli'] : '0'; ?></td>
                <td align="right"><?php echo !empty($po['total_koli']) ? $po['total_koli'] : '0'; ?></td>
                <td align="right"><?php echo app_format_money($po['price'], ''); ?></td>
                <td align="right"><?php echo app_format_money($po['price_total'], ''); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['gross_weight']) ? $po['gross_weight'] : '0'); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['total_gross_weight']) ? $po['total_gross_weight'] : '0'); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['net_weight']) ? $po['net_weight'] : '0'); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['total_net_weight']) ? $po['total_net_weight'] : '0'); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['cbm_koli']) ? $po['cbm_koli'] : '0'); ?></td>
                <td align="right"><?php echo clean_number(!empty($po['total_cbm']) ? $po['total_cbm'] : '0'); ?></td>
            </tr>
        <?php 
            $cartons += (float)$po['koli'];
            $totalPieces += (float)$po['total_koli'];
            $Pricetotal += (float)$po['price_total'];  
            $Total_net_weight += (float)clean_number($po['total_net_weight']);
            $Total_gross_weight += (float)clean_number($po['total_gross_weight']);
            $Total_cbm += (float)clean_number($po['total_cbm']);
            $count++;
        } ?>
    </tbody>
</table>

<div class="col-md-5 col-md-offset-7">
    <table class="table text-right">
        <tbody>
            <tr>
                <td><span class="bold"><?php echo _l('cartons'); ?></span></td>
                <td><?php echo $cartons; ?></td>
            </tr>
            <tr>
                <td><span class="bold"><?php echo _l('total_pieces'); ?></span></td>
                <td><?php echo $totalPieces; ?></td>
            </tr>
            <tr>
                <td><span class="bold"><?php echo _l('price_total'); ?></span></td>
                <td><?php echo app_format_money($Pricetotal, '¥'); ?></td>
            </tr>
            <tr>
                <td><span class="bold"><?php echo _l('total_gross_weight'); ?></span></td>
                <td><?php echo clean_number($Total_gross_weight); ?></td>
            </tr>
            <tr>
                <td><span class="bold"><?php echo _l('total_net_weight'); ?></span></td>
                <td><?php echo clean_number($Total_net_weight); ?></td>
            </tr>
            <tr>
                <td><span class="bold"><?php echo _l('total_cbm'); ?></span></td>
                <td><?php echo clean_number($Total_cbm); ?></td>
            </tr>
        </tbody>
    </table>
</div>