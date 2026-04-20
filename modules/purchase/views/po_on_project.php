<?php
defined('BASEPATH') or exit('No direct script access allowed');

$currency = get_base_currency_pur()->symbol;
$project_id = isset($project_id) ? $project_id : 0;

$this->db->select('po.id,
                   po.pur_order_number,
                   po.total,
                   po.deposit,
                   po.vendor,
                   po.order_date,
                   p.name as project_name,
                   po.delivery_date,
                   po.number,
                   po.delivery_status,
                   c.company as client_name,
                   v.company as vendor_name,
                   IFNULL(SUM(pod.total_cbm),0) as total_cbm');
$this->db->from(db_prefix() . 'pur_orders po');
$this->db->join(db_prefix() . 'pur_vendor v', 'v.userid = po.vendor', 'left');
$this->db->join(db_prefix() . 'pur_order_detail pod', 'pod.pur_order = po.id', 'left');
$this->db->join(db_prefix() . 'projects p', 'p.id = po.project', 'left');
$this->db->join(db_prefix() . 'clients c', 'c.userid = po.clients', 'left');


if ($project_id > 0) {
    $this->db->where('po.project', $project_id);
}

$this->db->group_by('po.id');

$this->db->order_by('po.delivery_date', 'ASC');

$orders = $this->db->get()->result_array();
?>

<table class="table table-bordered table-hover">
    <thead>
        <tr>

            <th>Purchase Order Number</th>
            <th>P.O Value</th>
            <th>Deposit</th>
            <th>Volume</th>
            <th>Delivery Date</th>
            <th>Project </th>
            <th>Vendor</th>
            <th>Customer </th>

            <!-- <th><?php echo _l('order_date'); ?></th>
            <th><?php echo _l('number'); ?></th>
            <th><?php echo _l('delivery_status'); ?></th> -->
        </tr>
    </thead>
    <tbody>
        <?php
        $sum_cbm = 0;
        $sum_total = 0;

        foreach ($orders as $o) {
            $delivery_status = $o['delivery_status'] == 1 
                ? '<span class="label label-success">'._l('received').'</span>' 
                : '<span class="label label-warning">'._l('waiting_to_receive').'</span>';

            $sum_cbm += $o['total_cbm'];
            $sum_total += $o['total'];
            $sum_deposit += $o['deposit'];
        ?>
            <tr>
                <td><a href="<?php echo admin_url('purchase/pur_order/' . $o['id']); ?>"><?php echo $o['pur_order_number']; ?></a></td>
                <td><?php echo app_format_money($o['total'], $currency); ?></td>
                <td><?php echo app_format_money($o['deposit'], $currency); ?></td>
                <td><?php echo number_format($o['total_cbm'], 2) . ' m³'; ?></td>
                <td><?php echo _d($o['delivery_date']); ?></td>
                <td><?php echo $o['project_name']; ?></td>
                <td><?php echo $o['vendor_name']; ?></td>
                <td><?php echo $o['client_name']; ?></td>
                <!-- <td><?php echo _d($o['order_date']); ?></td>
                <td><?php echo $o['number']; ?></td>
                <td><?php echo $delivery_status; ?></td> -->
            </tr>
        <?php } ?>

        <tr class="total-row">
            <td colspan="1" class="text-left">Total</td>
            <td><?php echo app_format_money($sum_total, $currency); ?></td>
            <td><?php echo app_format_money($sum_deposit, $currency); ?></td>
            <td><?php echo number_format($sum_cbm, 2) . ' m³'; ?></td>
            <td colspan="5"></td>
        </tr>
    </tbody>
</table>

<style>
.total-row td {
    background-color: #F4B084 !important;
    color: #030303ff !important;
    font-weight: bold !important;
}
</style>