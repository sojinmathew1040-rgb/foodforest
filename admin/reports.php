<?php
// =========================================================================
// Food Forest Sanctuary — Executive Analytics & Business Intelligence Hub
// Comprehensive Evaluations: Weekly, Monthly, Quarterly, Half-Yearly & Yearly
// Accommodation Stays, In-Cottage Dining & Item-Wise Dish Sales Evaluation
// Modeled after world-class resort PMS (Opera, Cloudbeds, Toast POS)
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();

// -------------------------------------------------------------------------
// 1. TIMEFRAME & EVALUATION PERIOD ENGINE
// -------------------------------------------------------------------------
$period = strtolower(trim($_GET['period'] ?? 'monthly'));
if (!in_array($period, ['weekly', 'monthly', 'quarterly', 'half_yearly', 'yearly', 'custom', 'all'])) {
    $period = 'monthly';
}

$today = date('Y-m-d');
$start_date = '';
$end_date = $today;
$period_label = 'Monthly Evaluation (Last 30 Days)';

switch ($period) {
    case 'weekly':
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $prev_start = date('Y-m-d', strtotime('-13 days'));
        $prev_end   = date('Y-m-d', strtotime('-7 days'));
        $period_label = 'Weekly Evaluation (Last 7 Days)';
        $grouping = 'day';
        break;

    case 'monthly':
        $start_date = date('Y-m-d', strtotime('-29 days'));
        $prev_start = date('Y-m-d', strtotime('-59 days'));
        $prev_end   = date('Y-m-d', strtotime('-30 days'));
        $period_label = 'Monthly Evaluation (Last 30 Days)';
        $grouping = 'day';
        break;

    case 'quarterly':
        $start_date = date('Y-m-d', strtotime('-89 days'));
        $prev_start = date('Y-m-d', strtotime('-179 days'));
        $prev_end   = date('Y-m-d', strtotime('-90 days'));
        $period_label = 'Quarterly Evaluation (Last 90 Days / 3 Months)';
        $grouping = 'week';
        break;

    case 'half_yearly':
        $start_date = date('Y-m-d', strtotime('-179 days'));
        $prev_start = date('Y-m-d', strtotime('-359 days'));
        $prev_end   = date('Y-m-d', strtotime('-180 days'));
        $period_label = 'Half-Yearly Evaluation (Last 180 Days / 6 Months)';
        $grouping = 'month';
        break;

    case 'yearly':
        $start_date = date('Y-m-d', strtotime('-364 days'));
        $prev_start = date('Y-m-d', strtotime('-729 days'));
        $prev_end   = date('Y-m-d', strtotime('-365 days'));
        $period_label = 'Yearly Evaluation (Last 365 Days / 12 Months)';
        $grouping = 'month';
        break;

    case 'custom':
        $custom_start = trim($_GET['start_date'] ?? '');
        $custom_end   = trim($_GET['end_date'] ?? '');
        $start_date   = !empty($custom_start) ? date('Y-m-d', strtotime($custom_start)) : date('Y-m-d', strtotime('-29 days'));
        $end_date     = !empty($custom_end) ? date('Y-m-d', strtotime($custom_end)) : $today;
        if ($start_date > $end_date) {
            $tmp = $start_date; $start_date = $end_date; $end_date = $tmp;
        }
        $diff_days = max(1, (int)round((strtotime($end_date) - strtotime($start_date)) / 86400));
        $prev_start = date('Y-m-d', strtotime($start_date . " -{$diff_days} days"));
        $prev_end   = date('Y-m-d', strtotime($start_date . " -1 day"));
        $period_label = 'Custom Range (' . date('d M Y', strtotime($start_date)) . ' to ' . date('d M Y', strtotime($end_date)) . ')';
        $grouping = ($diff_days <= 31) ? 'day' : (($diff_days <= 120) ? 'week' : 'month');
        break;

    case 'all':
        $min_d = $pdo->query("SELECT MIN(checkin_date) FROM bookings")->fetchColumn() ?: '2026-01-01';
        $start_date = $min_d;
        $prev_start = '2025-01-01';
        $prev_end   = $start_date;
        $period_label = 'All-Time Lifetime Evaluation';
        $grouping = 'month';
        break;
}

$period_days = max(1, (int)round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);

// -------------------------------------------------------------------------
// 2. CSV EXPORT DISPATCHER (1-Click Business Export)
// -------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=FoodForest_Executive_Report_' . $period . '_' . date('Ymd_His') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    fputcsv($out, ['FOOD FOREST ECO SANCTUARY — EXECUTIVE PERFORMANCE & OPERATIONS REPORT']);
    fputcsv($out, ['Evaluation Period:', $period_label]);
    fputcsv($out, ['Date Range:', $start_date . ' to ' . $end_date . ' (' . $period_days . ' Days)']);
    fputcsv($out, ['Generated On:', date('d M Y, H:i:s')]);
    fputcsv($out, []);

    // 1. Fetch Bookings for CSV
    $stmt_csv = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE ((checkin_date BETWEEN ? AND ?) OR (created_at BETWEEN ? AND ?))
        ORDER BY checkin_date DESC
    ");
    $stmt_csv->execute([$start_date, $end_date, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $csv_bookings = $stmt_csv->fetchAll();

    // Summary metrics
    $csv_total_rev = 0; $csv_stay_rev = 0; $csv_food_rev = 0; $csv_nights = 0;
    foreach ($csv_bookings as $b) {
        if ($b['status'] !== 'cancelled') {
            $csv_total_rev += (float)$b['total_amount'];
            $csv_stay_rev  += (float)$b['room_amount'];
            $csv_food_rev  += (float)$b['food_amount'];
            $csv_nights    += max(1, (int)$b['nights']);
        }
    }

    fputcsv($out, ['EXECUTIVE KPI SUMMARY']);
    fputcsv($out, ['Total Gross Revenue (₹)', number_format($csv_total_rev, 2)]);
    fputcsv($out, ['Villa Stay Revenue (₹)', number_format($csv_stay_rev, 2)]);
    fputcsv($out, ['Food & Dining Revenue (₹)', number_format($csv_food_rev, 2)]);
    fputcsv($out, ['Total Reservations', count($csv_bookings)]);
    fputcsv($out, ['Total Room Nights Sold', $csv_nights]);
    fputcsv($out, []);

    // Item-wise dish sales in CSV
    fputcsv($out, ['ITEM-WISE FOOD ORDERING EVALUATION']);
    fputcsv($out, ['Rank', 'Item / Dish Name', 'Category', 'Dietary', 'Units Sold', 'Unit Price (₹)', 'Total Gross Revenue (₹)', '% Share of Dining']);

    $item_sales = [];
    foreach ($csv_bookings as $b) {
        if (!empty($b['food_items'])) {
            $f_arr = json_decode($b['food_items'], true);
            if (is_array($f_arr)) {
                foreach ($f_arr as $it) {
                    $name = trim($it['heading'] ?? ($it['name'] ?? 'Custom Dish'));
                    $cat  = ucfirst(trim($it['category'] ?? 'Dining'));
                    $diet = ucfirst(trim($it['dietary_type'] ?? 'Veg'));
                    $qty  = max(1, (int)($it['quantity'] ?? 1));
                    $price = (float)($it['price'] ?? 0);
                    $sub   = (float)($it['subtotal'] ?? ($price * $qty));

                    if (!isset($item_sales[$name])) {
                        $item_sales[$name] = [
                            'name' => $name, 'category' => $cat, 'dietary' => $diet,
                            'qty' => 0, 'revenue' => 0.0, 'price' => $price
                        ];
                    }
                    $item_sales[$name]['qty'] += $qty;
                    $item_sales[$name]['revenue'] += $sub;
                }
            }
        }
    }
    uasort($item_sales, function($a, $b) { return $b['revenue'] <=> $a['revenue']; });

    $r_rank = 1;
    foreach ($item_sales as $it) {
        $share = ($csv_food_rev > 0) ? round(($it['revenue'] / $csv_food_rev) * 100, 1) : 0;
        fputcsv($out, [
            $r_rank++, $it['name'], $it['category'], $it['dietary'],
            $it['qty'], number_format($it['price'], 2), number_format($it['revenue'], 2), $share . '%'
        ]);
    }
    fputcsv($out, []);

    // Individual bookings
    fputcsv($out, ['DETAILED RESERVATIONS & DINING TRANSACTION LOG']);
    fputcsv($out, ['Booking ID', 'Reference', 'Guest Name', 'Phone', 'Villa Type', 'Check-In', 'Check-Out', 'Nights', 'Room Tariff (₹)', 'Food Amount (₹)', 'Total Bill (₹)', 'Advance Paid (₹)', 'Status', 'Payment Status']);
    foreach ($csv_bookings as $b) {
        fputcsv($out, [
            $b['id'], $b['reference_code'], $b['guest_name'], $b['guest_phone'],
            $b['villa_type'], $b['checkin_date'], $b['checkout_date'], $b['nights'],
            number_format($b['room_amount'], 2), number_format($b['food_amount'], 2),
            number_format($b['total_amount'], 2), number_format($b['advance_paid'], 2),
            ucfirst($b['status']), ucfirst($b['payment_status'])
        ]);
    }

    fclose($out);
    exit;
}

// -------------------------------------------------------------------------
// 3. DATABASE AGGREGATION & BUSINESS METRICS (CURRENT & PREV PERIOD)
// -------------------------------------------------------------------------
$rooms_list = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();
$total_available_rooms = max(1, count($rooms_list));
$total_capacity_room_nights = $total_available_rooms * $period_days;

// Current period valid bookings
$stmt_curr = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE (checkin_date BETWEEN ? AND ?)
       OR (created_at BETWEEN ? AND ?)
    ORDER BY checkin_date ASC
");
$stmt_curr->execute([$start_date, $end_date, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$current_bookings = $stmt_curr->fetchAll();

// Previous period valid bookings for growth rate comparison
$stmt_prev = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE (checkin_date BETWEEN ? AND ?)
       OR (created_at BETWEEN ? AND ?)
");
$stmt_prev->execute([$prev_start, $prev_end, $prev_start . ' 00:00:00', $prev_end . ' 23:59:59']);
$prev_bookings = $stmt_prev->fetchAll();

// Custom invoices in period (walk-in dining, treks, extra products)
$stmt_inv = $pdo->prepare("
    SELECT * FROM custom_invoices 
    WHERE (DATE(created_at) BETWEEN ? AND ?)
       OR (DATE(invoice_date) BETWEEN ? AND ?)
");
$stmt_inv->execute([$start_date, $end_date, $start_date, $end_date]);
$custom_invoices = $stmt_inv->fetchAll();

// -------------------------------------------------------------------------
// Current Period Computations
// -------------------------------------------------------------------------
$total_gross_revenue = 0.0;
$total_stay_revenue  = 0.0;
$total_food_revenue  = 0.0;
$total_tax_collected = 0.0;
$total_advance_collected = 0.0;
$total_balance_due   = 0.0;

$count_total_bookings = count($current_bookings);
$count_completed = 0;
$count_inhouse   = 0;
$count_confirmed = 0;
$count_pending   = 0;
$count_cancelled = 0;

$total_nights_sold = 0;
$total_adults_count = 0;
$total_kids_count   = 0;

// Food analytics
$total_dining_orders_count = 0;
$total_dishes_ordered_count = 0;
$dish_sales_matrix = [];
$meal_session_breakdown = [
    'Breakfast' => ['revenue' => 0.0, 'qty' => 0],
    'Lunch'     => ['revenue' => 0.0, 'qty' => 0],
    'Dinner'    => ['revenue' => 0.0, 'qty' => 0],
    'Snacks'    => ['revenue' => 0.0, 'qty' => 0],
    'Beverages' => ['revenue' => 0.0, 'qty' => 0],
    'Other'     => ['revenue' => 0.0, 'qty' => 0],
];
$villa_dining_leaderboard = [];

// Villa Performance Map
$villa_perf_map = [];
foreach ($rooms_list as $r) {
    $villa_perf_map[$r['slug']] = [
        'id'          => $r['id'],
        'title'       => $r['title'],
        'stay_type'   => $r['stay_type'],
        'base_rate'   => (float)$r['rate_per_night'],
        'nights_sold' => 0,
        'stay_revenue'=> 0.0,
        'food_revenue'=> 0.0,
        'bookings'    => 0,
        'guests'      => 0
    ];
}

// Time-series chart buckets
$chart_timeline = [];
// Pre-fill dates for daily grouping
if ($grouping === 'day' && $period_days <= 35) {
    for ($i = 0; $i < $period_days; $i++) {
        $d = date('Y-m-d', strtotime($start_date . " +{$i} days"));
        $chart_timeline[$d] = [
            'label' => date('d M', strtotime($d)),
            'stays' => 0.0,
            'food'  => 0.0,
            'total' => 0.0
        ];
    }
}

foreach ($current_bookings as $b) {
    $st = strtolower($b['status']);
    if ($st === 'completed') $count_completed++;
    elseif ($st === 'inhouse') $count_inhouse++;
    elseif ($st === 'confirmed') $count_confirmed++;
    elseif ($st === 'pending') $count_pending++;
    elseif ($st === 'cancelled') $count_cancelled++;

    // Skip cancelled from financial totals
    if ($st === 'cancelled') continue;

    $b_room  = (float)($b['room_amount'] ?? 0);
    $b_food  = (float)($b['food_amount'] ?? 0);
    $b_tax   = (float)($b['tax_amount'] ?? 0);
    $b_total = (float)($b['total_amount'] ?? ($b_room + $b_food + $b_tax));
    $b_adv   = (float)($b['advance_paid'] ?? 0);

    $total_gross_revenue += $b_total;
    $total_stay_revenue  += $b_room;
    $total_food_revenue  += $b_food;
    $total_tax_collected += $b_tax;
    $total_advance_collected += $b_adv;
    $total_balance_due   += max(0, $b_total - $b_adv);

    $nights = max(1, (int)($b['nights'] ?? 1));
    $total_nights_sold += $nights;

    $adults = max(1, (int)($b['adults_count'] ?? 1));
    $kids   = (int)($b['kids_count'] ?? 0);
    $total_adults_count += $adults;
    $total_kids_count   += $kids;

    // Villa Performance Attribution
    $v_type = trim($b['villa_type'] ?? '');
    $matched_key = null;
    foreach ($rooms_list as $r) {
        if ($r['slug'] === $v_type || stripos($r['slug'], $v_type) !== false || stripos($v_type, $r['slug']) !== false || stripos($r['title'], $v_type) !== false) {
            $matched_key = $r['slug'];
            break;
        }
    }
    if (!$matched_key && !empty($rooms_list)) {
        $matched_key = $rooms_list[0]['slug'];
    }

    if ($matched_key && isset($villa_perf_map[$matched_key])) {
        $villa_perf_map[$matched_key]['nights_sold'] += $nights;
        $villa_perf_map[$matched_key]['stay_revenue'] += $b_room;
        $villa_perf_map[$matched_key]['food_revenue'] += $b_food;
        $villa_perf_map[$matched_key]['bookings']++;
        $villa_perf_map[$matched_key]['guests'] += ($adults + $kids);
    }

    // Villa Dining Leaderboard
    $v_display_title = $villa_perf_map[$matched_key]['title'] ?? $v_type;
    if ($b_food > 0) {
        if (!isset($villa_dining_leaderboard[$v_display_title])) {
            $villa_dining_leaderboard[$v_display_title] = ['revenue' => 0.0, 'orders' => 0, 'dishes' => 0];
        }
        $villa_dining_leaderboard[$v_display_title]['revenue'] += $b_food;
        $villa_dining_leaderboard[$v_display_title]['orders']++;
    }

    // Chart Time-series Attribution
    $c_date = date('Y-m-d', strtotime($b['checkin_date'] ?: $b['created_at']));
    if ($grouping === 'day') {
        $key = $c_date;
        $lbl = date('d M', strtotime($c_date));
    } elseif ($grouping === 'week') {
        $key = date('Y-\WW', strtotime($c_date));
        $lbl = 'Wk ' . date('W', strtotime($c_date));
    } else {
        $key = date('Y-m', strtotime($c_date));
        $lbl = date('M Y', strtotime($c_date));
    }

    if (!isset($chart_timeline[$key])) {
        $chart_timeline[$key] = ['label' => $lbl, 'stays' => 0.0, 'food' => 0.0, 'total' => 0.0];
    }
    $chart_timeline[$key]['stays'] += $b_room;
    $chart_timeline[$key]['food']  += $b_food;
    $chart_timeline[$key]['total'] += $b_total;

    // Item-Wise Food Ordering Extraction
    if (!empty($b['food_items'])) {
        $f_items = json_decode($b['food_items'], true);
        if (is_array($f_items) && !empty($f_items)) {
            $total_dining_orders_count++;
            foreach ($f_items as $fit) {
                $name = trim($fit['heading'] ?? ($fit['name'] ?? 'Sanctuary Dish'));
                $cat  = ucfirst(strtolower(trim($fit['category'] ?? ($fit['category_title'] ?? 'Other'))));
                if (!in_array($cat, ['Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Beverages'])) {
                    if (stripos($cat, 'break') !== false) $cat = 'Breakfast';
                    elseif (stripos($cat, 'lunch') !== false) $cat = 'Lunch';
                    elseif (stripos($cat, 'din') !== false) $cat = 'Dinner';
                    elseif (stripos($cat, 'snack') !== false || stripos($cat, 'tea') !== false) $cat = 'Snacks';
                    elseif (stripos($cat, 'bev') !== false || stripos($cat, 'juice') !== false) $cat = 'Beverages';
                    else $cat = 'Other';
                }

                $diet = strtolower(trim($fit['dietary_type'] ?? 'veg'));
                $diet_label = (stripos($diet, 'non') !== false) ? 'Non-Veg' : 'Veg';
                $qty  = max(1, (int)($fit['quantity'] ?? 1));
                $price = (float)($fit['price'] ?? 0);
                $sub   = (float)($fit['subtotal'] ?? ($price * $qty));

                $total_dishes_ordered_count += $qty;

                // Meal session breakdown
                if (isset($meal_session_breakdown[$cat])) {
                    $meal_session_breakdown[$cat]['revenue'] += $sub;
                    $meal_session_breakdown[$cat]['qty']     += $qty;
                }

                // Add to villa dining dishes count
                if (isset($villa_dining_leaderboard[$v_display_title])) {
                    $villa_dining_leaderboard[$v_display_title]['dishes'] += $qty;
                }

                // Item Sales Matrix aggregation
                if (!isset($dish_sales_matrix[$name])) {
                    $dish_sales_matrix[$name] = [
                        'name'     => $name,
                        'category' => $cat,
                        'dietary'  => $diet_label,
                        'price'    => $price,
                        'qty'      => 0,
                        'revenue'  => 0.0,
                        'orders'   => 0
                    ];
                }
                $dish_sales_matrix[$name]['qty'] += $qty;
                $dish_sales_matrix[$name]['revenue'] += $sub;
                $dish_sales_matrix[$name]['orders']++;
            }
        }
    }
}

// Add Custom Invoices Revenue
$custom_inv_total = 0.0;
foreach ($custom_invoices as $ci) {
    $custom_inv_total += (float)($ci['grand_total'] ?? 0);
}
$total_gross_revenue += $custom_inv_total;

// Sort item sales by revenue descending
uasort($dish_sales_matrix, function($a, $b) {
    return $b['revenue'] <=> $a['revenue'];
});

// Sort villa performance by revenue descending
uasort($villa_perf_map, function($a, $b) {
    return $b['stay_revenue'] <=> $a['stay_revenue'];
});

// Sort villa dining leaderboard by revenue descending
uasort($villa_dining_leaderboard, function($a, $b) {
    return $b['revenue'] <=> $a['revenue'];
});

// Sort chart timeline by date key
ksort($chart_timeline);

// -------------------------------------------------------------------------
// Previous Period Computations for Growth Trend (% changes)
// -------------------------------------------------------------------------
$prev_gross_rev = 0.0;
$prev_stay_rev  = 0.0;
$prev_food_rev  = 0.0;
$prev_nights    = 0;
$prev_bookings_count = 0;

foreach ($prev_bookings as $pb) {
    if ($pb['status'] !== 'cancelled') {
        $prev_gross_rev += (float)$pb['total_amount'];
        $prev_stay_rev  += (float)$pb['room_amount'];
        $prev_food_rev  += (float)$pb['food_amount'];
        $prev_nights    += max(1, (int)$pb['nights']);
        $prev_bookings_count++;
    }
}

if (!function_exists('calc_growth_pct')) {
    function calc_growth_pct($curr, $prev) {
        if ($prev <= 0) return ($curr > 0) ? 100.0 : 0.0;
        return round((($curr - $prev) / $prev) * 100, 1);
    }
}

$rev_growth_pct   = calc_growth_pct($total_gross_revenue, $prev_gross_rev);
$stay_growth_pct  = calc_growth_pct($total_stay_revenue, $prev_stay_rev);
$food_growth_pct  = calc_growth_pct($total_food_revenue, $prev_food_rev);
$nights_growth_pct = calc_growth_pct($total_nights_sold, $prev_nights);

// Core Hospitality Performance Metrics (Industry Standard PMS formulas)
$occupancy_rate = min(100.0, round(($total_nights_sold / max(1, $total_capacity_room_nights)) * 100, 1));
$adr = ($total_nights_sold > 0) ? round($total_stay_revenue / $total_nights_sold, 2) : 0.0;
$revpar = round($total_stay_revenue / max(1, $total_capacity_room_nights), 2);
$alos = ($count_completed + $count_inhouse + $count_confirmed > 0) 
    ? round($total_nights_sold / ($count_completed + $count_inhouse + $count_confirmed), 1) : 0.0;
$avg_order_value = ($total_dining_orders_count > 0) ? round($total_food_revenue / $total_dining_orders_count, 2) : 0.0;
$dining_attach_rate = ($count_total_bookings > 0) ? round(($total_dining_orders_count / $count_total_bookings) * 100, 1) : 0.0;
$cancellation_rate = ($count_total_bookings > 0) ? round(($count_cancelled / $count_total_bookings) * 100, 1) : 0.0;

// Maximum value in chart for SVG scaling
$chart_max_val = 1000;
foreach ($chart_timeline as $pt) {
    if ($pt['total'] > $chart_max_val) $chart_max_val = $pt['total'];
}
$chart_max_val = ceil($chart_max_val / 5000) * 5000; // Round up to clean multiple

$page_title = 'Sanctuary Analytics & Reports';
$page_subtitle = 'Executive reservations, revenue & dining operations evaluation';
require_once __DIR__ . '/includes/header.php';
?>

<!-- =======================================================================
     REPORTS HEADER TOOLBAR & EVALUATION TIMEFRAME SELECTORS
     ======================================================================= -->
<div class="adm-reports-toolbar" style="margin-bottom: 22px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        
        <!-- Left: Evaluation Header & Current Period Indicator -->
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <span class="adm-pulse-dot" style="background: var(--adm-gold); box-shadow: 0 0 10px var(--adm-gold);"></span>
                <h2 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                    <?php echo htmlspecialchars($period_label); ?>
                </h2>
                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.18); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.4); font-size: 10px; padding: 2px 8px;">
                    <?php echo $period_days; ?> Days Evaluated
                </span>
            </div>
            <p style="font-size: 12px; color: var(--adm-text-secondary); margin: 0;">
                Window: <strong><?php echo date('d M Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('d M Y', strtotime($end_date)); ?></strong> • Real-time PMS &amp; Kitchen Ledger
            </p>
        </div>

        <!-- Right: Export & Print Action Buttons -->
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a href="reports.php?period=<?php echo urlencode($period); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&export=csv" class="adm-btn-action gold" style="padding: 8px 16px; font-size: 12px; font-weight: 700;" title="Download full evaluated report as CSV spreadsheet">
                <i class="fa-solid fa-file-csv"></i>
                <span>Export CSV Report</span>
            </a>

            <button type="button" onclick="window.print();" class="adm-btn-action outline" style="padding: 8px 16px; font-size: 12px;" title="Print Executive Report / Save as PDF">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>

    </div>

    <!-- Timeframe Evaluation Filter Pills Bar -->
    <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" id="reports-period-pills">
            <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; margin-right: 4px;">
                <i class="fa-solid fa-calendar-week" style="margin-right: 4px;"></i> Timeframe:
            </span>

            <a href="reports.php?period=weekly" class="adm-crop-ratio-btn <?php echo ($period === 'weekly') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-bolt"></i> Weekly (7D)
            </a>

            <a href="reports.php?period=monthly" class="adm-crop-ratio-btn <?php echo ($period === 'monthly') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-calendar-days"></i> Monthly (30D)
            </a>

            <a href="reports.php?period=quarterly" class="adm-crop-ratio-btn <?php echo ($period === 'quarterly') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-chart-pie"></i> Quarterly (90D)
            </a>

            <a href="reports.php?period=half_yearly" class="adm-crop-ratio-btn <?php echo ($period === 'half_yearly') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-chart-line"></i> Half-Yearly (180D)
            </a>

            <a href="reports.php?period=yearly" class="adm-crop-ratio-btn <?php echo ($period === 'yearly') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-trophy"></i> Yearly (365D)
            </a>

            <a href="reports.php?period=all" class="adm-crop-ratio-btn <?php echo ($period === 'all') ? 'active' : ''; ?>" style="text-decoration: none; padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-globe"></i> All-Time
            </a>

            <button type="button" onclick="toggleCustomRangePanel();" class="adm-crop-ratio-btn <?php echo ($period === 'custom') ? 'active' : ''; ?>" style="padding: 6px 14px; font-size: 11.5px;">
                <i class="fa-solid fa-sliders"></i> Custom Range
            </button>
        </div>

        <div style="font-size: 11.5px; color: var(--adm-text-secondary); display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrows-spin" style="color: var(--adm-gold);"></i>
            Comparing vs previous <?php echo $period_days; ?>-day cycle:
            <span style="color: <?php echo ($rev_growth_pct >= 0) ? '#2ECC71' : '#F87171'; ?>; font-weight: 700;">
                <?php echo ($rev_growth_pct >= 0 ? '+' : '') . $rev_growth_pct; ?>%
            </span>
        </div>
    </div>

    <!-- Collapsible Custom Date Range Selector Form -->
    <div id="reports-custom-range-box" style="display: <?php echo ($period === 'custom') ? 'block' : 'none'; ?>; margin-top: 14px; padding: 14px 18px; background: rgba(6, 17, 10, 0.7); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 10px;">
        <form method="GET" action="reports.php" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <input type="hidden" name="period" value="custom">
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 11.5px; color: #CBD5E1; font-weight: 600;">Start Date:</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="adm-form-control" style="font-size: 12px; padding: 6px 10px; width: auto;" required>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 11.5px; color: #CBD5E1; font-weight: 600;">End Date:</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="adm-form-control" style="font-size: 12px; padding: 6px 10px; width: auto;" required>
            </div>

            <button type="submit" class="adm-btn-action gold" style="padding: 7px 18px; font-size: 12px; font-weight: 700;">
                <i class="fa-solid fa-filter"></i> Apply Custom Range
            </button>

            <a href="reports.php?period=monthly" class="adm-btn-action" style="padding: 7px 14px; font-size: 12px; background: rgba(255,255,255,0.06); color: var(--adm-text-secondary); text-decoration: none;">
                Reset
            </a>
        </form>
    </div>
</div>

<!-- =======================================================================
     5 EXECUTIVE KPI METRIC CARDS (Industry PMS Standard)
     ======================================================================= -->
<section class="adm-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px;">
    
    <!-- KPI 1: Total Gross Revenue -->
    <div class="adm-kpi-card gold">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Total Gross Revenue</span>
            <div class="adm-kpi-number" style="font-size: 23px;">₹<?php echo number_format($total_gross_revenue, 0, '.', ','); ?></div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 11px;">
                <span class="adm-kpi-trend <?php echo ($rev_growth_pct >= 0) ? 'positive' : 'negative'; ?>">
                    <i class="fa-solid <?php echo ($rev_growth_pct >= 0) ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; ?>"></i>
                    <?php echo ($rev_growth_pct >= 0 ? '+' : '') . $rev_growth_pct; ?>%
                </span>
                <span style="color: var(--adm-text-muted);">vs prev cycle</span>
            </div>
            <div style="font-size: 10.5px; color: #CBD5E1; margin-top: 6px;">
                Stays: ₹<?php echo number_format($total_stay_revenue, 0); ?> • Dining: ₹<?php echo number_format($total_food_revenue, 0); ?>
            </div>
        </div>
        <div class="adm-kpi-icon-wrap" style="color: var(--adm-gold);">
            <i class="fa-solid fa-coins"></i>
        </div>
    </div>

    <!-- KPI 2: Occupancy Rate & Room Nights -->
    <div class="adm-kpi-card emerald">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Occupancy Rate</span>
            <div class="adm-kpi-number" style="font-size: 23px; color: #2ECC71;"><?php echo $occupancy_rate; ?>%</div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 11px;">
                <span class="adm-kpi-trend <?php echo ($nights_growth_pct >= 0) ? 'positive' : 'negative'; ?>">
                    <i class="fa-solid <?php echo ($nights_growth_pct >= 0) ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; ?>"></i>
                    <?php echo ($nights_growth_pct >= 0 ? '+' : '') . $nights_growth_pct; ?>%
                </span>
                <span style="color: var(--adm-text-muted);">room nights</span>
            </div>
            <div style="font-size: 10.5px; color: #CBD5E1; margin-top: 6px;">
                <strong><?php echo $total_nights_sold; ?></strong> nights sold of <strong><?php echo $total_capacity_room_nights; ?></strong> capacity
            </div>
        </div>
        <div class="adm-kpi-icon-wrap" style="color: #2ECC71;">
            <i class="fa-solid fa-bed"></i>
        </div>
    </div>

    <!-- KPI 3: ADR (Average Daily Rate) & RevPAR -->
    <div class="adm-kpi-card cyan" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.12) 0%, rgba(2, 6, 4, 0.95) 100%); border: 1px solid rgba(56, 189, 248, 0.35);">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label" style="color: #38BDF8;">ADR &amp; RevPAR</span>
            <div class="adm-kpi-number" style="font-size: 23px; color: #38BDF8;">₹<?php echo number_format($adr, 0); ?></div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 11px;">
                <span style="color: #A855F7; font-weight: 700;">RevPAR: ₹<?php echo number_format($revpar, 0); ?></span>
            </div>
            <div style="font-size: 10.5px; color: #CBD5E1; margin-top: 6px;">
                ALOS: <strong><?php echo $alos; ?></strong> nights per stay
            </div>
        </div>
        <div class="adm-kpi-icon-wrap" style="color: #38BDF8;">
            <i class="fa-solid fa-chart-line"></i>
        </div>
    </div>

    <!-- KPI 4: Food & Dining Gross Revenue -->
    <div class="adm-kpi-card amber" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(2, 6, 4, 0.95) 100%); border: 1px solid rgba(245, 158, 11, 0.35);">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label" style="color: #F59E0B;">Dining &amp; Kitchen</span>
            <div class="adm-kpi-number" style="font-size: 23px; color: #F59E0B;">₹<?php echo number_format($total_food_revenue, 0); ?></div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 11px;">
                <span class="adm-kpi-trend <?php echo ($food_growth_pct >= 0) ? 'positive' : 'negative'; ?>">
                    <i class="fa-solid <?php echo ($food_growth_pct >= 0) ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'; ?>"></i>
                    <?php echo ($food_growth_pct >= 0 ? '+' : '') . $food_growth_pct; ?>%
                </span>
                <span style="color: var(--adm-text-muted);">AOV: ₹<?php echo number_format($avg_order_value, 0); ?></span>
            </div>
            <div style="font-size: 10.5px; color: #CBD5E1; margin-top: 6px;">
                <strong><?php echo $total_dishes_ordered_count; ?></strong> dishes in <strong><?php echo $total_dining_orders_count; ?></strong> orders
            </div>
        </div>
        <div class="adm-kpi-icon-wrap" style="color: #F59E0B;">
            <i class="fa-solid fa-utensils"></i>
        </div>
    </div>

    <!-- KPI 5: Total Reservations & Guests -->
    <div class="adm-kpi-card purple" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.12) 0%, rgba(2, 6, 4, 0.95) 100%); border: 1px solid rgba(168, 85, 247, 0.35);">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label" style="color: #C084FC;">Reservations &amp; Guests</span>
            <div class="adm-kpi-number" style="font-size: 23px; color: #C084FC;"><?php echo $count_total_bookings; ?></div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 11px;">
                <span style="color: #2ECC71;"><i class="fa-solid fa-check"></i> <?php echo $count_completed + $count_inhouse + $count_confirmed; ?> Valid</span>
                • <span style="color: #F87171;"><i class="fa-solid fa-ban"></i> <?php echo $count_cancelled; ?> Cancel</span>
            </div>
            <div style="font-size: 10.5px; color: #CBD5E1; margin-top: 6px;">
                <strong><?php echo $total_adults_count + $total_kids_count; ?></strong> Total Guests (<?php echo $total_adults_count; ?>A, <?php echo $total_kids_count; ?>K)
            </div>
        </div>
        <div class="adm-kpi-icon-wrap" style="color: #C084FC;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

</section>

<!-- =======================================================================
     INTERACTIVE REVENUE TREND GRAPH & REVENUE MIX PANEL
     ======================================================================= -->
<div class="adm-card" style="margin-bottom: 24px; padding: 22px 24px; background: linear-gradient(145deg, #0d1e13 0%, #08140c 100%); border: 1px solid var(--adm-gold-border); border-radius: 14px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-family: var(--adm-font-title); font-size: 16px; color: #FFFFFF; margin: 0; letter-spacing: 0.5px;">
                <i class="fa-solid fa-chart-column" style="color: var(--adm-gold); margin-right: 6px;"></i>
                Revenue &amp; Dining Velocity Timeline
            </h3>
            <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 3px 0 0;">
                Comparative pace of Villa Stays vs In-Cottage Dining across the evaluated timeframe
            </p>
        </div>

        <!-- Legend Pills -->
        <div style="display: flex; align-items: center; gap: 14px; font-size: 11.5px; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 6px; color: #2ECC71;">
                <span style="width: 12px; height: 12px; background: #2ECC71; border-radius: 3px; display: inline-block;"></span>
                Villa Accommodations (₹<?php echo number_format($total_stay_revenue, 0); ?>)
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; color: #F59E0B;">
                <span style="width: 12px; height: 12px; background: #F59E0B; border-radius: 3px; display: inline-block;"></span>
                Food &amp; Dining (₹<?php echo number_format($total_food_revenue, 0); ?>)
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; color: var(--adm-gold);">
                <span style="width: 12px; height: 2px; background: var(--adm-gold); display: inline-block;"></span>
                Gross Total Trend
            </span>
        </div>
    </div>

    <!-- Responsive SVG Visual Chart -->
    <div style="width: 100%; overflow-x: auto;">
        <div style="min-width: 600px; height: 220px; position: relative; padding: 10px 0;">
            <?php 
            $num_bars = count($chart_timeline);
            $chart_width = max(600, $num_bars * 40);
            $chart_height = 180;
            ?>
            <svg viewBox="0 0 <?php echo $chart_width; ?> <?php echo $chart_height + 30; ?>" style="width: 100%; height: 100%; overflow: visible;" id="reports-svg-chart">
                <!-- Grid Horizontal Lines -->
                <?php for ($g = 1; $g <= 4; $g++): 
                    $y_pos = $chart_height - ($chart_height * ($g / 4));
                    $val_step = ($chart_max_val * ($g / 4));
                ?>
                    <line x1="0" y1="<?php echo $y_pos; ?>" x2="<?php echo $chart_width; ?>" y2="<?php echo $y_pos; ?>" stroke="rgba(255,255,255,0.06)" stroke-dasharray="4" />
                    <text x="5" y="<?php echo $y_pos - 4; ?>" fill="rgba(255,255,255,0.3)" font-size="9" font-family="monospace">₹<?php echo number_format($val_step, 0); ?></text>
                <?php endfor; ?>

                <!-- Bars & Trend Points -->
                <?php 
                $bar_idx = 0;
                $slot_width = $chart_width / max(1, $num_bars);
                $bar_width = max(10, min(24, $slot_width * 0.55));
                $line_points = [];

                foreach ($chart_timeline as $t_key => $p_data):
                    $x_center = ($bar_idx * $slot_width) + ($slot_width / 2);
                    $stay_h = ($chart_max_val > 0) ? ($p_data['stays'] / $chart_max_val) * $chart_height : 0;
                    $food_h = ($chart_max_val > 0) ? ($p_data['food'] / $chart_max_val) * $chart_height : 0;
                    $tot_h  = ($chart_max_val > 0) ? ($p_data['total'] / $chart_max_val) * $chart_height : 0;

                    $y_stay = $chart_height - $stay_h;
                    $y_food = $y_stay - $food_h;
                    $y_tot  = $chart_height - $tot_h;

                    $line_points[] = "{$x_center},{$y_tot}";
                ?>
                    <!-- Stacked Bar for Stays & Food -->
                    <g class="chart-bar-group" style="cursor: pointer;">
                        <title><?php echo $p_data['label']; ?>: Stays ₹<?php echo number_format($p_data['stays']); ?> | Dining ₹<?php echo number_format($p_data['food']); ?> | Total ₹<?php echo number_format($p_data['total']); ?></title>
                        
                        <!-- Stays Bar (Emerald) -->
                        <rect x="<?php echo $x_center - ($bar_width / 2); ?>" y="<?php echo $y_stay; ?>" width="<?php echo $bar_width; ?>" height="<?php echo max(1, $stay_h); ?>" fill="#2ECC71" opacity="0.85" rx="3" />
                        
                        <!-- Food Bar (Warm Gold Stacked on top) -->
                        <?php if ($food_h > 0): ?>
                            <rect x="<?php echo $x_center - ($bar_width / 2); ?>" y="<?php echo $y_food; ?>" width="<?php echo $bar_width; ?>" height="<?php echo max(1, $food_h); ?>" fill="#F59E0B" opacity="0.9" rx="3" />
                        <?php endif; ?>

                        <!-- X Axis Date Label -->
                        <text x="<?php echo $x_center; ?>" y="<?php echo $chart_height + 18; ?>" fill="rgba(255,255,255,0.55)" font-size="9.5" text-anchor="middle" font-family="sans-serif">
                            <?php echo htmlspecialchars($p_data['label']); ?>
                        </text>
                    </g>
                <?php 
                    $bar_idx++;
                endforeach; 
                ?>

                <!-- Trend Line Connection -->
                <?php if (count($line_points) > 1): ?>
                    <polyline points="<?php echo implode(' ', $line_points); ?>" fill="none" stroke="var(--adm-gold)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.9" />
                    <?php foreach ($line_points as $lp): 
                        list($cx, $cy) = explode(',', $lp);
                    ?>
                        <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="3.5" fill="#FFFFFF" stroke="var(--adm-gold)" stroke-width="2" />
                    <?php endforeach; ?>
                <?php endif; ?>
            </svg>
        </div>
    </div>

    <!-- Revenue Mix Summary Progress Bar -->
    <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08);">
        <?php 
        $st_pct = ($total_gross_revenue > 0) ? round(($total_stay_revenue / $total_gross_revenue) * 100, 1) : 0;
        $fd_pct = ($total_gross_revenue > 0) ? round(($total_food_revenue / $total_gross_revenue) * 100, 1) : 0;
        $tx_pct = max(0, round(100 - $st_pct - $fd_pct, 1));
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; margin-bottom: 6px;">
            <span style="color: #CBD5E1;"><strong>Revenue Mix Distribution:</strong></span>
            <span style="color: var(--adm-text-muted);">
                Accommodations: <strong style="color: #2ECC71;"><?php echo $st_pct; ?>%</strong> • 
                Dining: <strong style="color: #F59E0B;"><?php echo $fd_pct; ?>%</strong> • 
                Taxes &amp; Extra Folios: <strong style="color: #C084FC;"><?php echo $tx_pct; ?>%</strong>
            </span>
        </div>
        <div style="height: 8px; width: 100%; background: rgba(255,255,255,0.06); border-radius: 6px; overflow: hidden; display: flex;">
            <div style="width: <?php echo $st_pct; ?>%; background: #2ECC71;" title="Accommodations: <?php echo $st_pct; ?>%"></div>
            <div style="width: <?php echo $fd_pct; ?>%; background: #F59E0B;" title="Dining: <?php echo $fd_pct; ?>%"></div>
            <div style="width: <?php echo $tx_pct; ?>%; background: #C084FC;" title="Taxes & Extras: <?php echo $tx_pct; ?>%"></div>
        </div>
    </div>
</div>

<!-- =======================================================================
     4 DETAILED EVALUATION SECTIONS (TABBED RESORT SUITE)
     ======================================================================= -->
<div class="adm-table-card" style="margin-bottom: 24px; border: 1.5px solid var(--adm-gold-border); border-radius: 14px; overflow: hidden;">
    
    <!-- Tab Navigation Header -->
    <div style="background: rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" id="reports-tab-buttons">
            <button type="button" class="adm-btn-action active" id="tab-btn-items" onclick="switchReportsTab('items');" style="padding: 7px 16px; font-size: 12px; font-weight: 700;">
                <i class="fa-solid fa-utensils" style="color: #F59E0B;"></i>
                <span>Item-Wise Dining Sales (<?php echo count($dish_sales_matrix); ?> Dishes)</span>
            </button>

            <button type="button" class="adm-btn-action outline" id="tab-btn-stays" onclick="switchReportsTab('stays');" style="padding: 7px 16px; font-size: 12px;">
                <i class="fa-solid fa-hotel" style="color: #2ECC71;"></i>
                <span>Villa &amp; Stay Performance (<?php echo count($rooms_list); ?> Cottages)</span>
            </button>

            <button type="button" class="adm-btn-action outline" id="tab-btn-dining" onclick="switchReportsTab('dining');" style="padding: 7px 16px; font-size: 12px;">
                <i class="fa-solid fa-fire-burner" style="color: var(--adm-gold);"></i>
                <span>Dining &amp; Kitchen Velocity</span>
            </button>

            <button type="button" class="adm-btn-action outline" id="tab-btn-ledger" onclick="switchReportsTab('ledger');" style="padding: 7px 16px; font-size: 12px;">
                <i class="fa-solid fa-receipt" style="color: #38BDF8;"></i>
                <span>Transaction Log &amp; Taxes</span>
            </button>
        </div>

        <span style="font-size: 11px; color: var(--adm-text-muted);">
            <i class="fa-solid fa-circle-info" style="color: var(--adm-gold);"></i> Filter by category or search below
        </span>
    </div>

    <!-- ===================================================================
         PANEL 1: ITEM-WISE DISH SALES EVALUATION (USER EXPLICIT REQUEST!)
         =================================================================== -->
    <div id="reports-panel-items" class="reports-tab-panel" style="display: block; padding: 20px;">
        
        <!-- Category Filter & Search Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" id="dish-category-filter">
                <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase;">Category:</span>
                <button type="button" class="adm-crop-ratio-btn active" onclick="filterDishCategory('all', this);" style="padding: 4px 10px; font-size: 11px;">All Dishes</button>
                <button type="button" class="adm-crop-ratio-btn" onclick="filterDishCategory('breakfast', this);" style="padding: 4px 10px; font-size: 11px;">Breakfast</button>
                <button type="button" class="adm-crop-ratio-btn" onclick="filterDishCategory('lunch', this);" style="padding: 4px 10px; font-size: 11px;">Lunch</button>
                <button type="button" class="adm-crop-ratio-btn" onclick="filterDishCategory('dinner', this);" style="padding: 4px 10px; font-size: 11px;">Dinner</button>
                <button type="button" class="adm-crop-ratio-btn" onclick="filterDishCategory('snacks', this);" style="padding: 4px 10px; font-size: 11px;">Snacks &amp; Tea</button>
                <button type="button" class="adm-crop-ratio-btn" onclick="filterDishCategory('beverages', this);" style="padding: 4px 10px; font-size: 11px;">Beverages</button>
            </div>

            <!-- Instant Search Input -->
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 11px; color: var(--adm-text-muted);"></i>
                <input type="text" id="dish-search-input" placeholder="Search dish / ingredient..." onkeyup="filterDishTable(this.value);" style="background: rgba(0,0,0,0.3); border: 1px solid var(--adm-gold-border); border-radius: 16px; padding: 6px 12px 6px 28px; font-size: 11.5px; color: #FFF; outline: none; width: 190px;">
            </div>
        </div>

        <!-- Item-Wise Sales Table -->
        <div class="adm-table-responsive">
            <table class="adm-table" id="dish-sales-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1.5px solid rgba(197, 160, 89, 0.3);">
                        <th style="width: 60px; text-align: center;">Rank</th>
                        <th>Item / Dish Title</th>
                        <th style="width: 120px;">Category</th>
                        <th style="width: 90px; text-align: center;">Dietary</th>
                        <th style="width: 100px; text-align: right;">Unit Price</th>
                        <th style="width: 110px; text-align: right;">Portions Sold</th>
                        <th style="width: 140px; text-align: right;">Gross Sales (₹)</th>
                        <th style="width: 120px; text-align: center;">Share of Dining</th>
                        <th style="width: 130px; text-align: center;">Velocity Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dish_sales_matrix)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 36px; color: var(--adm-text-muted);">
                                <i class="fa-solid fa-utensils" style="font-size: 24px; margin-bottom: 8px; display: block; opacity: 0.4;"></i>
                                No food ordering items recorded during this evaluation timeframe.
                            </td>
                        </tr>
                    <?php else: 
                        $rank = 1;
                        foreach ($dish_sales_matrix as $dish):
                            $share = ($total_food_revenue > 0) ? round(($dish['revenue'] / $total_food_revenue) * 100, 1) : 0;
                            
                            // Velocity Rating
                            if ($share >= 15 || $dish['qty'] >= 10) {
                                $rating_badge = '<span class="adm-badge" style="background: rgba(245, 158, 11, 0.2); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.4);"><i class="fa-solid fa-fire"></i> Star Seller</span>';
                            } elseif ($share >= 7 || $dish['qty'] >= 5) {
                                $rating_badge = '<span class="adm-badge" style="background: rgba(46, 204, 113, 0.2); color: #2ECC71; border: 1px solid rgba(46, 204, 113, 0.4);"><i class="fa-solid fa-leaf"></i> High Demand</span>';
                            } else {
                                $rating_badge = '<span class="adm-badge" style="background: rgba(255, 255, 255, 0.08); color: #CBD5E1; border: 1px solid rgba(255, 255, 255, 0.15);">Steady</span>';
                            }
                    ?>
                        <tr class="dish-row" data-category="<?php echo strtolower($dish['category']); ?>" data-title="<?php echo strtolower(htmlspecialchars($dish['name'])); ?>" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <td style="text-align: center; font-weight: 700; color: <?php echo ($rank <= 3) ? 'var(--adm-gold)' : 'var(--adm-text-muted)'; ?>;">
                                <?php if ($rank === 1): ?>🥇
                                <?php elseif ($rank === 2): ?>🥈
                                <?php elseif ($rank === 3): ?>🥉
                                <?php else: ?>#<?php echo $rank; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #FFFFFF; font-size: 13px;"><?php echo htmlspecialchars($dish['name']); ?></strong>
                                <span style="display: block; font-size: 10.5px; color: var(--adm-text-muted);">
                                    Dispatched across <?php echo $dish['orders']; ?> reservation order(s)
                                </span>
                            </td>
                            <td>
                                <span class="adm-badge" style="background: rgba(56, 189, 248, 0.12); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.28); font-size: 10px;">
                                    <?php echo htmlspecialchars($dish['category']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($dish['dietary'] === 'Veg'): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; color: #2ECC71; font-size: 11px; font-weight: 700;">
                                        <i class="fa-solid fa-circle" style="font-size: 8px;"></i> Veg
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; color: #F87171; font-size: 11px; font-weight: 700;">
                                        <i class="fa-solid fa-play" style="font-size: 8px; transform: rotate(-90deg);"></i> Non-Veg
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 12.5px; color: #CBD5E1;">
                                ₹<?php echo number_format($dish['price'], 2); ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 13px; color: #FFFFFF;">
                                <?php echo $dish['qty']; ?> <span style="font-size: 10px; font-weight: normal; color: var(--adm-text-muted);">portions</span>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-weight: 700; font-size: 13.5px; color: var(--adm-gold);">
                                ₹<?php echo number_format($dish['revenue'], 2); ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <div style="width: 45px; height: 5px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo min(100, $share * 3); ?>%; height: 100%; background: #F59E0B;"></div>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 700; color: #F59E0B;"><?php echo $share; ?>%</span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php echo $rating_badge; ?>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================
         PANEL 2: VILLA & ACCOMMODATION PERFORMANCE EVALUATION
         =================================================================== -->
    <div id="reports-panel-stays" class="reports-tab-panel" style="display: none; padding: 20px;">
        <div style="margin-bottom: 14px;">
            <h4 style="font-family: var(--adm-font-title); font-size: 14.5px; color: #FFFFFF; margin: 0;">
                Cottage &amp; Villa Performance Matrix
            </h4>
            <p style="font-size: 11.5px; color: var(--adm-text-secondary); margin: 3px 0 0;">
                Occupancy rate, room nights realized, and revenue generated across all Sanctuary cottages
            </p>
        </div>

        <div class="adm-table-responsive">
            <table class="adm-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1.5px solid rgba(197, 160, 89, 0.3);">
                        <th>Villa Entity</th>
                        <th style="width: 120px;">Stay Architecture</th>
                        <th style="width: 110px; text-align: right;">Base Night Tariff</th>
                        <th style="width: 110px; text-align: right;">Nights Sold</th>
                        <th style="width: 120px; text-align: center;">Occupancy %</th>
                        <th style="width: 140px; text-align: right;">Stay Revenue (₹)</th>
                        <th style="width: 130px; text-align: right;">Average Daily Rate</th>
                        <th style="width: 100px; text-align: center;">Guests Hosted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($villa_perf_map as $v_slug => $v): 
                        $v_occ = min(100.0, round(($v['nights_sold'] / $period_days) * 100, 1));
                        $v_adr = ($v['nights_sold'] > 0) ? round($v['stay_revenue'] / $v['nights_sold'], 2) : $v['base_rate'];
                    ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <td>
                                <strong style="color: #FFFFFF; font-size: 13px;"><?php echo htmlspecialchars($v['title']); ?></strong>
                                <span style="display: block; font-size: 10.5px; color: var(--adm-text-muted);">
                                    <?php echo $v['bookings']; ?> reservation(s) in period
                                </span>
                            </td>
                            <td>
                                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.12); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); font-size: 10px;">
                                    <?php echo ucfirst(htmlspecialchars($v['stay_type'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 12.5px; color: #CBD5E1;">
                                ₹<?php echo number_format($v['base_rate'], 0); ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 13px; color: #FFFFFF;">
                                <?php echo $v['nights_sold']; ?> / <?php echo $period_days; ?> <span style="font-size: 10px; color: var(--adm-text-muted);">nts</span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <div style="width: 45px; height: 5px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo $v_occ; ?>%; height: 100%; background: #2ECC71;"></div>
                                    </div>
                                    <span style="font-size: 11.5px; font-weight: 700; color: #2ECC71;"><?php echo $v_occ; ?>%</span>
                                </div>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-weight: 700; font-size: 13.5px; color: #2ECC71;">
                                ₹<?php echo number_format($v['stay_revenue'], 2); ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 12.5px; color: #38BDF8;">
                                ₹<?php echo number_format($v_adr, 0); ?>
                            </td>
                            <td style="text-align: center; font-weight: 600; color: #C084FC;">
                                <i class="fa-solid fa-users" style="font-size: 11px;"></i> <?php echo $v['guests']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================
         PANEL 3: DINING SESSIONS & COTTAGE LEADERBOARD
         =================================================================== -->
    <div id="reports-panel-dining" class="reports-tab-panel" style="display: none; padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <!-- A. Meal Session Distribution -->
            <div style="background: rgba(6, 17, 10, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px;">
                <h4 style="margin: 0 0 12px 0; font-family: var(--adm-font-title); font-size: 14px; color: #FFFFFF;">
                    <i class="fa-solid fa-clock" style="color: var(--adm-gold); margin-right: 6px;"></i>
                    Meal Session Velocity
                </h4>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($meal_session_breakdown as $sess_name => $sess_data): 
                        if ($sess_name === 'Other' && $sess_data['revenue'] <= 0) continue;
                        $sess_share = ($total_food_revenue > 0) ? round(($sess_data['revenue'] / $total_food_revenue) * 100, 1) : 0;
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 4px;">
                                <span style="color: #FFFFFF; font-weight: 600;">
                                    <?php echo htmlspecialchars($sess_name); ?> 
                                    <span style="font-size: 10.5px; color: var(--adm-text-muted); font-weight: normal;">(<?php echo $sess_data['qty']; ?> portions)</span>
                                </span>
                                <span style="font-family: monospace; font-weight: 700; color: #F59E0B;">
                                    ₹<?php echo number_format($sess_data['revenue'], 2); ?> (<?php echo $sess_share; ?>%)
                                </span>
                            </div>
                            <div style="height: 6px; width: 100%; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $sess_share; ?>%; height: 100%; background: #F59E0B; border-radius: 4px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- B. Cottage Dining Volume Leaderboard -->
            <div style="background: rgba(6, 17, 10, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px;">
                <h4 style="margin: 0 0 12px 0; font-family: var(--adm-font-title); font-size: 14px; color: #FFFFFF;">
                    <i class="fa-solid fa-trophy" style="color: var(--adm-gold); margin-right: 6px;"></i>
                    Cottage Dining Leaderboard
                </h4>

                <?php if (empty($villa_dining_leaderboard)): ?>
                    <div style="text-align: center; padding: 24px; color: var(--adm-text-muted); font-size: 12px;">
                        No cottage dining orders recorded in this timeframe.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php 
                        $l_rank = 1;
                        foreach ($villa_dining_leaderboard as $v_title => $v_data): 
                        ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 700; color: <?php echo ($l_rank === 1) ? 'var(--adm-gold)' : 'var(--adm-text-muted)'; ?>;">
                                        #<?php echo $l_rank++; ?>
                                    </span>
                                    <div>
                                        <strong style="color: #FFF; font-size: 12px;"><?php echo htmlspecialchars($v_title); ?></strong>
                                        <span style="display: block; font-size: 10.5px; color: var(--adm-text-muted);">
                                            <?php echo $v_data['orders']; ?> order(s) • <?php echo $v_data['dishes']; ?> dishes
                                        </span>
                                    </div>
                                </div>
                                <span style="font-family: monospace; font-weight: 700; color: #F59E0B; font-size: 13px;">
                                    ₹<?php echo number_format($v_data['revenue'], 2); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ===================================================================
         PANEL 4: TRANSACTION LOG & GST TAX AUDIT
         =================================================================== -->
    <div id="reports-panel-ledger" class="reports-tab-panel" style="display: none; padding: 20px;">
        
        <!-- Tax Audit Summary Banner -->
        <div style="background: rgba(197, 160, 89, 0.08); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div>
                <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); text-transform: uppercase; display: block; margin-bottom: 2px;">
                    GST Tax Breakdown &amp; Collections
                </span>
                <span style="font-size: 12px; color: var(--adm-text-secondary);">
                    GSTIN: <strong><?php echo htmlspecialchars(get_setting('gst_number', '32AAECF1234M1Z5')); ?></strong> (Food Forest Eco Sanctuary)
                </span>
            </div>

            <div style="display: flex; gap: 16px; font-size: 12px;">
                <span>Total Tax Collected: <strong style="color: #2ECC71;">₹<?php echo number_format($total_tax_collected, 2); ?></strong></span>
                <span>Advance Paid: <strong style="color: var(--adm-gold);">₹<?php echo number_format($total_advance_collected, 2); ?></strong></span>
                <span>Balance Due: <strong style="color: <?php echo ($total_balance_due > 0) ? '#F87171' : '#2ECC71'; ?>;">₹<?php echo number_format($total_balance_due, 2); ?></strong></span>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="adm-table-responsive">
            <table class="adm-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1.5px solid rgba(197, 160, 89, 0.3);">
                        <th>Ref</th>
                        <th>Guest Details</th>
                        <th>Villa / Stay</th>
                        <th style="width: 140px;">Dates</th>
                        <th style="width: 90px; text-align: right;">Stays (₹)</th>
                        <th style="width: 90px; text-align: right;">Dining (₹)</th>
                        <th style="width: 100px; text-align: right;">Total Bill</th>
                        <th style="width: 100px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_bookings as $b): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <td>
                                <strong style="font-family: monospace; color: var(--adm-gold); font-size: 12px;">
                                    <?php echo htmlspecialchars($b['reference_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <strong style="color: #FFF; font-size: 12.5px;"><?php echo htmlspecialchars($b['guest_name']); ?></strong>
                                <span style="display: block; font-size: 10.5px; color: var(--adm-text-muted);"><?php echo htmlspecialchars($b['guest_phone']); ?></span>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: #CBD5E1;"><?php echo htmlspecialchars($b['villa_type']); ?></span>
                            </td>
                            <td style="font-size: 11px; color: var(--adm-text-secondary);">
                                <?php echo date('d M', strtotime($b['checkin_date'])); ?> → <?php echo date('d M Y', strtotime($b['checkout_date'])); ?>
                                (<?php echo $b['nights']; ?>n)
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 12px; color: #2ECC71;">
                                ₹<?php echo number_format($b['room_amount'], 2); ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 12px; color: #F59E0B;">
                                ₹<?php echo number_format($b['food_amount'], 2); ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-weight: 700; font-size: 13px; color: #FFFFFF;">
                                ₹<?php echo number_format($b['total_amount'], 2); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="adm-badge" style="font-size: 9.5px;">
                                    <?php echo ucfirst(htmlspecialchars($b['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- =======================================================================
     JAVASCRIPT SUITE: TAB SWITCHING, CATEGORY FILTER & LIVE SEARCH
     ======================================================================= -->
<script>
function toggleCustomRangePanel() {
    var box = document.getElementById('reports-custom-range-box');
    if (box) {
        box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
        if (box.style.display === 'block') {
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

function switchReportsTab(tabKey) {
    // Hide all tab panels
    document.querySelectorAll('.reports-tab-panel').forEach(function(p) {
        p.style.display = 'none';
    });
    // Show active panel
    var activePanel = document.getElementById('reports-panel-' + tabKey);
    if (activePanel) activePanel.style.display = 'block';

    // Update buttons styling
    document.querySelectorAll('#reports-tab-buttons button').forEach(function(b) {
        b.className = 'adm-btn-action outline';
    });
    var btn = document.getElementById('tab-btn-' + tabKey);
    if (btn) btn.className = 'adm-btn-action active';
}

function filterDishCategory(cat, btn) {
    var c = (cat || 'all').toLowerCase();
    document.querySelectorAll('#dish-category-filter button').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');

    var rows = document.querySelectorAll('#dish-sales-table tbody .dish-row');
    rows.forEach(function(r) {
        var rowCat = (r.getAttribute('data-category') || '').toLowerCase();
        if (c === 'all' || rowCat.indexOf(c) !== -1 || (c === 'snacks' && (rowCat.indexOf('snack') !== -1 || rowCat.indexOf('tea') !== -1))) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function filterDishTable(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#dish-sales-table tbody .dish-row');
    rows.forEach(function(r) {
        var text = (r.getAttribute('data-title') || '') + ' ' + (r.getAttribute('data-category') || '');
        if (!q || text.indexOf(q) !== -1) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>

<style>
@media print {
    .adm-sidebar, .adm-header, .adm-reports-toolbar, #reports-tab-buttons, #dish-category-filter, #dish-search-input, .adm-btn-action {
        display: none !important;
    }
    body, .adm-main-content {
        background: #FFFFFF !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .adm-kpi-card, .adm-card, .adm-table-card {
        border: 1px solid #CCCCCC !important;
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    .reports-tab-panel {
        display: block !important;
    }
    .adm-table th, .adm-table td {
        color: #000000 !important;
        border-bottom: 1px solid #DDDDDD !important;
    }
    svg text {
        fill: #000000 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
