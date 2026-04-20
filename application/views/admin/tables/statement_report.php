<?php defined('BASEPATH') or exit('No direct script access allowed');
$aColumns = [
    'p.start_date as start_date',
    'p.name as project_name',
    'IFNULL(gr.total_goods_value,0) as total_goods_value',
    'IFNULL(e.service_fee,0) as service_fee',
    'IFNULL(e.inland_transport,0) as inland_transport',
    'IFNULL(e.inland_transport_b,0) as inland_transport_b',
    'IFNULL(e.others_expenses,0) as others_expenses',
    '(IFNULL(gr.total_goods_value,0)+IFNULL(e.service_fee,0)+IFNULL(e.inland_transport,0)+IFNULL(e.others_expenses,0)) as total',
    'IFNULL(inv.invoice_amount,0) as invoice_amount',
    'IFNULL(inv.invoice_total,0) as invoice_total',
    'inv.currency_name as currency_name',
    '(IFNULL(inv.invoice_total,0)- IFNULL(gr.total_goods_value,0)- IFNULL(e.service_fee,0)- IFNULL(e.inland_transport,0)- IFNULL(e.others_expenses,0)) as balance',
    'cl.company as company',
    'adm.admin_names as admin_names',
    'IFNULL(e.sea_transport,0) as sea_transport',
    'IFNULL(e.sea_transport_b,0) as sea_transport_b',
];

$sIndexColumn = 'po.id';
$sTable = '(SELECT MIN(id) AS id, project, clients FROM ' . db_prefix() . 'pur_orders GROUP BY project, clients) as po';

$additionalSelect = [
    'po.id as id',
    'po.project as project_id',
    'po.clients as client_id',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients as cl ON cl.userid = po.clients',
    'LEFT JOIN ' . db_prefix() . 'projects as p ON p.id = po.project',

    // Goods receipt
    'LEFT JOIN (
        SELECT po.clients, po.project, SUM(gr.total_goods_money) AS total_goods_value
        FROM ' . db_prefix() . 'pur_orders po
        LEFT JOIN ' . db_prefix() . 'goods_receipt gr ON gr.pr_order_id = po.id
        GROUP BY po.clients, po.project
    ) gr ON gr.clients = po.clients AND gr.project = po.project',

    // Expenses
    'LEFT JOIN (
        SELECT clientid, project_id,
            SUM(CASE WHEN category = 2 THEN amount ELSE 0 END) AS service_fee,
            SUM(CASE WHEN category = 1 THEN amount ELSE 0 END) AS inland_transport,
            SUM(CASE WHEN category = 1 THEN amount_2 ELSE 0 END) AS inland_transport_b,
            SUM(CASE WHEN category = 3 THEN amount ELSE 0 END) AS sea_transport,
            SUM(CASE WHEN category = 3 THEN amount_2 ELSE 0 END) AS sea_transport_b,
            SUM(CASE WHEN category NOT IN (1,2,3) THEN amount ELSE 0 END) AS others_expenses
        FROM ' . db_prefix() . 'expenses
        GROUP BY clientid, project_id
    ) e ON e.clientid = po.clients AND e.project_id = po.project',

    // Invoices
    'LEFT JOIN (
        SELECT 
            i.clientid,
            i.project_id,
            SUM(i.invoice_amount) AS invoice_amount,
            SUM(i.total) AS invoice_total,
            MAX(c.name) AS currency_name
        FROM ' . db_prefix() . 'invoices i
        LEFT JOIN ' . db_prefix() . 'currencies c ON c.id = i.invoice_currency
        GROUP BY i.clientid, i.project_id
    ) inv ON inv.clientid = po.clients AND inv.project_id = po.project',

    'LEFT JOIN (
        SELECT ca.customer_id,
            GROUP_CONCAT(DISTINCT CONCAT(s.firstname," ",s.lastname) SEPARATOR ", ") AS admin_names
        FROM ' . db_prefix() . 'customer_admins AS ca
        INNER JOIN ' . db_prefix() . 'staff AS s ON s.staffid = ca.staff_id
        GROUP BY ca.customer_id
    ) adm ON adm.customer_id = po.clients',
];

$ci =& get_instance();
$where = [];

$client = $ci->input->post('client');
if ($client !== null && $client !== '') {
    $where[] = 'AND po.clients=' . (int) $client;
}

$project = $ci->input->post('project');
if ($project !== null && $project !== '') {
    $where[] = 'AND po.project=' . (int) $project;
}

$date_range = $ci->input->post('date_range');
if ($date_range && $date_range !== 'all') {
    if ($date_range === 'custom') {
        $from = $ci->input->post('custom_from');
        $to   = $ci->input->post('custom_to');
        if (!empty($from) && !empty($to)) {
            $from = $ci->db->escape_str($from);
            $to   = $ci->db->escape_str($to);
            $where[] = "AND p.start_date BETWEEN '{$from}' AND '{$to}'";
        }
    } else {
        $range = get_dates_from_select_range($date_range);
        if (!empty($range['from']) && !empty($range['to'])) {
            $from = $ci->db->escape_str($range['from']);
            $to   = $ci->db->escape_str($range['to']);
            $where[] = "AND p.start_date BETWEEN '{$from}' AND '{$to}'";
        }
    }
}

$staff = $ci->input->post('staff');
if ($staff && $staff !== '') {
    $where[] = 'AND adm.admin_names LIKE "%'.$ci->db->escape_like_str($staff).'%"';
}

$balance = $ci->input->post('balance');

if ($balance === 'positive') {
    $where[] = 'AND (IFNULL(inv.invoice_total,0) - IFNULL(gr.total_goods_value,0) - IFNULL(e.service_fee,0) - IFNULL(e.inland_transport,0) - IFNULL(e.others_expenses,0)) > 0';
} elseif ($balance === 'negative') {
    $where[] = 'AND (IFNULL(inv.invoice_total,0) - IFNULL(gr.total_goods_value,0) - IFNULL(e.service_fee,0) - IFNULL(e.inland_transport,0) - IFNULL(e.others_expenses,0)) < 0';
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    $additionalSelect,
    ''
);

$output  = $result['output'];
$rResult = $result['rResult'];

// Initialize total variables
$total_goods_value = $total_service_fee = $total_inland_transport_b = $total_inland_transport =
$total_others_expenses = $total_total = $total_invoice_amount = $total_invoice_total =
$total_balance = $total_sea_transport_b = $total_sea_transport = 0;

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = _d($aRow['start_date']);
    $row[] = e($aRow['project_name']);

    $row[] = app_format_money($aRow['total_goods_value'], '¥');
    $row[] = app_format_money($aRow['service_fee'], '¥');
    $row[] = app_format_money($aRow['inland_transport_b'], '¥');
    $row[] = app_format_money($aRow['inland_transport'], '¥');
    $row[] = app_format_money($aRow['others_expenses'], '¥');
    $row[] = app_format_money($aRow['total'], '¥');
    $row[] = app_format_money($aRow['invoice_amount'],$aRow['currency_name']);
    $row[] = app_format_money($aRow['invoice_total'], '¥');
    $row[] = app_format_money($aRow['balance'], '¥');

    $row[] = e($aRow['company']);
    $row[] = e($aRow['admin_names']);
    $row[] = app_format_money($aRow['sea_transport_b'], '$');
    $row[] = app_format_money($aRow['sea_transport'], '$');

    // Add to total
    $total_goods_value += $aRow['total_goods_value'];
    $total_service_fee += $aRow['service_fee'];
    $total_inland_transport_b += $aRow['inland_transport_b'];
    $total_inland_transport += $aRow['inland_transport'];
    $total_others_expenses += $aRow['others_expenses'];
    $total_total += $aRow['total'];
    $total_invoice_amount += $aRow['invoice_amount'];
    $total_invoice_total += $aRow['invoice_total'];
    $total_balance += $aRow['balance'];
    $total_sea_transport_b += $aRow['sea_transport_b'];
    $total_sea_transport += $aRow['sea_transport'];

    $output['aaData'][] = $row;
}

$total_row = [];
$total_row[] = '<span style="display:block; text-align:center; font-weight:900; color:#423d3d;" colspan="2">Total</span>';

$total_row[] = '';

$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_goods_value, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_service_fee, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_inland_transport_b, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_inland_transport, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_others_expenses, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:-#423d3d;">' . app_format_money($total_total, '¥') . '</span>';

$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_invoice_amount, $rResult[0]['currency_name'] ?? '¥') . '</span>';

$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_invoice_total, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_balance, '¥') . '</span>';

$total_row[] = '';
$total_row[] = '';

$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_sea_transport_b, '¥') . '</span>';
$total_row[] = '<span style="font-weight:900; color:#423d3d;">' . app_format_money($total_sea_transport, '¥') . '</span>';

$output['aaData'][] = $total_row;


