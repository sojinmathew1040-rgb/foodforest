<?php
// =========================================================================
// Food Forest Sanctuary — Admin Assistant & Helpdesk Chatbot API
// Intelligent conversational guide for panel settings, workflows & step doubts
// =========================================================================

require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true) ?: $_POST;

$query = trim($data['query'] ?? '');
$context = $data['context'] ?? [];
$current_topic = trim($context['current_topic'] ?? '');
$step_number = (int)($context['step_number'] ?? 0);

if (empty($query)) {
    echo json_encode([
        'success' => false,
        'reply' => "Please ask a question or doubt about any setting or feature in this admin panel.",
        'follow_up_doubts' => [
            "How do I change villa tariffs?",
            "How to set up Bank & UPI QR code?",
            "How does guest billing and invoicing work?",
            "How to sync calendar with Airbnb?"
        ]
    ]);
    exit;
}

/**
 * Normalizes query string for robust keyword & semantic matching.
 */
function normalize_text($str) {
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

$normalized = normalize_text($query);

// =========================================================================
// MASTER KNOWLEDGE BASE FOR FOOD FOREST ADMIN PANEL
// Each item contains:
// - id
// - title
// - keywords (English, Malayalam terms, Manglish)
// - location & direct_url
// - procedure (detailed steps)
// - tips & cautions
// - step_doubts (specific explanations for each step if asked)
// - follow_up_doubts (pills suggested to the user)
// =========================================================================

$kb = [
    // ---------------------------------------------------------------------
    // 1. ROOMS, VILLAS & TARIFFS
    // ---------------------------------------------------------------------
    'rooms' => [
        'id' => 'rooms',
        'title' => 'Villas, Cottages, Rates & 360° Tours',
        'card' => 'Card 04 (Villas & Stays)',
        'direct_url' => 'edit_section.php?section=rooms',
        'direct_label' => 'Open Rooms & Tariffs Editor',
        'keywords' => [
            'room', 'rooms', 'villa', 'villas', 'cottage', 'cottages', 'treehouse', 'mudhouse',
            'tariff', 'tariffs', 'price', 'pricing', 'rate', 'rates', 'rent', 'occupancy', 'guest',
            'base rate', 'extra guest', 'extra child', '360', 'panorama', 'tour', 'photos', 'amenities',
            'room rate change', 'villa price change', 'vilayil', 'vaadaka', 'dhaaram', 'vilakku'
        ],
        'summary' => 'You can configure Villa names, descriptions, base nightly tariffs, extra person charges, capacity, amenities, 360° virtual tours, and photo galleries in **Card 04 (Villas & Stays)**.',
        'steps' => [
            '1' => 'Click on **Estate Settings** in the left sidebar menu, or navigate directly to **Card 04 (Villas & Stays)** in the Settings Hub.',
            '2' => 'Choose the villa you want to edit (e.g. **Alpine Wood House** or **Earthen Mudhouse**) from the tabs at the top of the editor.',
            '3' => 'Under **Basic Tariffs & Occupancy**, adjust the following fields:<br>• **Base Rate per Night (₹)**: Standard rate for standard guest count.<br>• **Base Guests**: Number of guests covered in the base rate (usually 2).<br>• **Max Guests**: Maximum capacity of the villa.<br>• **Extra Adult Rate (₹)**: Per-night charge for each additional adult.<br>• **Extra Child Rate (₹)**: Per-night charge for additional children.',
            '4' => 'Under **Media & 360° Panoramic Tour**, upload fresh photos or set the 360° panorama image for the interactive virtual tour.',
            '5' => 'Click the golden **"Save Changes"** button at the bottom. Once saved, click **"Purge Cache"** in the topbar to ensure guests see the updated rates instantly!'
        ],
        'tips' => [
            '💡 **Pro Tip**: If you want to temporarily increase prices for a holiday weekend, you can also use the **Interactive Calendar** (`calendar.php`) to set custom rate overrides for specific dates without changing the base rate!',
            '⚠️ **Note**: Changing base rates applies to all *future bookings*. Any reservations already confirmed will retain their original booked price.'
        ],
        'step_doubts' => [
            '1' => 'You can access this by clicking "Estate Settings" on the left menu, then clicking the 4th card titled "04 • Villas, Cottages & Stays". Or use the direct link button above.',
            '2' => 'Each villa (Wood House and Mud House) has its own independent tab in the editor so you can manage their pricing, photos, and descriptions separately.',
            '3' => '<strong>Base Rate vs Extra Guest Rate:</strong> The Base Rate applies up to the "Base Guests" count (usually 2 adults). If 3 adults book, the system automatically charges: `Base Rate + (1 × Extra Adult Rate)` per night. Extra Child rates only apply if children exceed the room base allowance.',
            '4' => '<strong>360° Tour & Photos:</strong> Upload standard high-res landscape photos (16:9 ratio recommended). For 360° virtual tours, upload an equirectangular panorama image (JPEG format) so the web viewer can spin in 360 degrees.',
            '5' => 'Always purge cache after saving so that website visitors get fresh prices immediately without browser cache delay.'
        ],
        'follow_up_doubts' => [
            "What is the difference between Base Rate and Extra Guest Rate?",
            "Will changing room rates affect existing bookings?",
            "How do I upload a 360° virtual tour panorama?",
            "Can I set special rates for weekends on the Calendar?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 2. BANK DETAILS & UPI QR CODE (FOR BILLING & INVOICES)
    // ---------------------------------------------------------------------
    'bank' => [
        'id' => 'bank',
        'title' => 'Official Bank Account, UPI ID & Payment QR Code',
        'card' => 'Card 14 (Bank & UPI QR Code)',
        'direct_url' => 'edit_section.php?section=bank',
        'direct_label' => 'Open Bank & UPI QR Editor',
        'keywords' => [
            'bank', 'bank details', 'upi', 'qr', 'qr code', 'payment', 'gpay', 'phonepe',
            'ifsc', 'account number', 'branch', 'transfer', 'bill bank', 'invoice qr',
            'bill qr code', 'pay qr', 'gpay qr', 'bank account', 'upi id', 'vpa', 'scanner',
            'bank vivaragal', 'panam adakkan'
        ],
        'summary' => 'You can set your official Estate Bank Account, IFSC Code, Account Name, Branch, UPI ID (VPA), and upload the Payment QR Code in **Card 14 (Bank & UPI QR)**. These details automatically print on all official guest folios, settlement bills, and A4 tax invoices!',
        'steps' => [
            '1' => 'Open **Estate Settings** and click on **Card 14 (Bank & Official Payment QR Code)**, or click the direct shortcut button above.',
            '2' => 'Enter your official **Bank Name** (e.g. State Bank of India, Federal Bank, HDFC).',
            '3' => 'Fill in the **Account Holder Name**, **Account Number**, **IFSC Code**, and **Branch Name**.',
            '4' => 'Enter your **UPI VPA / GPay / PhonePe ID** (e.g. `foodforest@okhdfcbank` or `sanctuary@upi`).',
            '5' => 'In the **Payment QR Code Image** upload box, upload a clean, square image of your UPI QR Code (from Google Pay Business, PhonePe, Paytm, or your bank).',
            '6' => 'Click **"Save Bank Details & QR"**. Once saved, all guest checkout bills (`billing.php`) and printable A4 invoices (`print_bill.php`) will display your official QR code and bank account.'
        ],
        'tips' => [
            '💡 **Pro Tip**: Use a high-contrast square PNG or JPG image for the QR code so smartphone cameras can scan it effortlessly even when printed on paper or viewed on a phone screen.',
            '⚠️ **Check**: You can test how it looks by going to **Billing & Invoices**, opening any booking folio, and clicking **"Print A4 Tax Invoice"**.'
        ],
        'step_doubts' => [
            '1' => 'Card 14 is located in Estate Settings near the bottom of the 16-card grid.',
            '2' => 'The bank name entered here appears in the "Bank Transfer Settlement" box on both web receipts and printable A4 PDFs.',
            '3' => 'Make sure the IFSC code is in capital letters (e.g. `SBIN0001234`) with no spaces.',
            '4' => 'The UPI ID is clickable on mobile phones, allowing guests to open their GPay or PhonePe app directly from the digital receipt.',
            '5' => 'If you don\'t upload a QR code image, the system displays your Bank Account Number, IFSC, and UPI ID as text instructions on the bill.'
        ],
        'follow_up_doubts' => [
            "Where does the QR code appear on the guest bill?",
            "Can guests scan the QR code to pay via GPay or PhonePe?",
            "What if I need to change the bank account later?",
            "How do I print an A4 bill with the bank details?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 3. MULTI-TIER GST TAX SETTINGS
    // ---------------------------------------------------------------------
    'gst' => [
        'id' => 'gst',
        'title' => 'Multi-Tier GST Tax Rates & Legal Invoicing',
        'card' => 'General Settings Hub (GST Tax Section)',
        'direct_url' => 'settings.php?open_general=gst',
        'direct_label' => 'Open GST Settings Hub',
        'keywords' => [
            'gst', 'tax', 'tax rate', 'taxes', 'gstin', 'cgst', 'sgst', 'igst', 'percentage',
            'gst percentage', 'food gst', 'cottage gst', 'room gst', 'other gst', 'tax calculation',
            'invoice gst', 'bill tax', 'gst legal name', 'gst number', 'gst calculate', 'gst calculation',
            'gst on food', 'gst on room', 'how does gst calculate', 'tax slabs', 'gst slabs'
        ],
        'summary' => 'The system supports India Multi-Tier GST regulations with distinct tax slabs for Cottages/Villas, Food & Dining, and Extra Activities, plus your official GSTIN and Legal Business Name.',
        'steps' => [
            '1' => 'Navigate to **Estate Settings** (`settings.php`).',
            '2' => 'Look for the **"Multi-Tier GST Tax Rates & Invoicing"** section on the main settings page.',
            '3' => 'Configure the three statutory tax percentages:<br>• **Cottage / Accommodation GST Rate**: Usually 12% (or 18% depending on room tariff).<br>• **Food & Gastronomy GST Rate**: Usually 5% for restaurant & estate dining.<br>• **Other Services GST Rate**: 18% for special activities, transport, or custom services.',
            '4' => 'Enter your **15-digit GSTIN Number** (e.g. `32AAAAA0000A1Z5`) and your registered **Legal Business Name**.',
            '5' => 'Click the golden button **"Save GST Settings"**. The billing engine will immediately apply these exact split calculations (CGST 50% + SGST 50%) to all guest bills.'
        ],
        'tips' => [
            '💡 **Automatic Split**: The billing system automatically calculates both CGST and SGST at half the total rate for intra-state Kerala transactions.',
            '💡 **Customizable Per Bill**: When generating an invoice in `billing.php`, you can still toggle GST on or off or customize rates for specific VIP folios if needed.'
        ],
        'step_doubts' => [
            '1' => 'The GST settings are located directly on the main Estate Settings hub page (`settings.php`), so you don\'t need to open a split editor.',
            '3' => '<strong>Why Multi-Tier?</strong> Accommodation and restaurant food are taxed under different GST slabs under Indian law. The Food Forest billing engine automatically taxes room charges at your cottage rate and food orders at your food rate on the same invoice!',
            '4' => 'The GSTIN and Legal Business Name will appear in the invoice header and footer for statutory tax compliance.'
        ],
        'follow_up_doubts' => [
            "How does the system split CGST and SGST?",
            "Can I turn off GST for specific guest bills?",
            "Where do I enter our official GSTIN number?",
            "What GST percentage should I use for food vs room?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 4. IN-COTTAGE FOOD ORDERING TOGGLE
    // ---------------------------------------------------------------------
    'food_toggle' => [
        'id' => 'food_toggle',
        'title' => 'Guest In-Cottage Food Ordering Toggle',
        'card' => 'General Settings Hub (Food Ordering Switch)',
        'direct_url' => 'settings.php',
        'direct_label' => 'Open Settings Hub Toggle',
        'keywords' => [
            'food ordering toggle', 'disable food ordering', 'enable food ordering', 'turn off food',
            'guest food ordering', 'kitchen ordering switch', 'in cottage ordering', 'stop food orders',
            'allow food orders', 'food ordering enabled', 'food ordering disabled', 'food order stop',
            'food off cheyyan', 'food on cheyyan', 'disable in cottage food ordering', 'cottage food ordering',
            'turn off food ordering', 'stop food ordering', 'food ordering switch', 'in cottage food'
        ],
        'summary' => 'You can turn in-cottage food ordering ON or OFF with a single toggle switch in the Estate Settings Hub. When disabled, guests cannot place new digital food orders from their villa portal or booking flow.',
        'steps' => [
            '1' => 'Go to **Estate Settings** (`settings.php`).',
            '2' => 'At the top of the page, locate the **"Guest In-Cottage Food Ordering"** toggle card.',
            '3' => 'Toggle the switch to **Enabled (Active)** to allow guests to order meals directly from their rooms, or **Disabled (Inactive)** to close kitchen ordering (e.g. during off-hours, kitchen renovation, or when operating on a fixed complimentary plan only).',
            '4' => 'The status saves instantly via AJAX and notifies you with a confirmation badge.'
        ],
        'tips' => [
            '💡 When disabled, guests visiting the guest portal or booking modal will see an informational message that kitchen ordering is currently resting or on fixed chef spreads.',
            '💡 Even when disabled for guests, administrators can still manually add food items to a guest\'s bill in **Billing & Invoices**.'
        ],
        'step_doubts' => [
            '2' => 'The toggle is right near the top of `settings.php` for lightning-fast access by front-desk staff.',
            '3' => 'When disabled, guests cannot submit food carts, avoiding kitchen confusion when the chef is off-duty.'
        ],
        'follow_up_doubts' => [
            "Can admin still add food charges to the bill when ordering is disabled?",
            "What will guests see when food ordering is turned off?",
            "How do I change food menu items and prices?",
            "Where does the kitchen see incoming food orders?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 5. FOOD MENU HUB & MEAL TIMINGS
    // ---------------------------------------------------------------------
    'food_menu' => [
        'id' => 'food_menu',
        'title' => 'Food Menu Hub, Dining Sets & Meal Slots',
        'card' => 'Card 06 (Food Menu & Gastronomy)',
        'direct_url' => 'edit_section.php?section=menu',
        'direct_label' => 'Open Food Menu Hub',
        'keywords' => [
            'food', 'menu', 'food menu', 'dishes', 'dishes list', 'meal', 'meals', 'dining',
            'breakfast', 'lunch', 'dinner', 'snacks', 'tea', 'sadya', 'claypot', 'appam',
            'food price', 'add dish', 'delete dish', 'edit food', 'meal timing', 'meal slot',
            'complimentary breakfast', 'bhojanam', 'aahaaram', 'food list'
        ],
        'summary' => 'Manage your estate dining offerings, categorized by meal slot (Breakfast, Lunch, Evening Snacks, Dinner), item descriptions, plate prices, inclusions, and dietary tags in **Card 06 (Food Menu)**.',
        'steps' => [
            '1' => 'Go to **Estate Settings** -> click **Card 06 (Food Menu Hub & Dining Sets)**, or click the direct button above.',
            '2' => 'To edit an existing dish: Locate the card under the respective category tab (Breakfast, Lunch, Snacks, Dinner), update the title, price per plate, and inclusions.',
            '3' => 'To add a new dish: Click the **"+ Add New Food Item"** button at the top, select the **Meal Category** (Breakfast, Lunch, Snacks, or Dinner), enter the **Dish Heading**, subtitle, price in ₹, and upload a tempting food photo.',
            '4' => 'To set dietary tags: Mark whether the dish is Vegetarian, Non-Veg, Organic Farm-Grown, or Chef\'s Special.',
            '5' => 'Click **"Save Menu Item"**.<br>All dishes immediately become available for guests in the booking modal, guest in-room portal, and kitchen billing folios!'
        ],
        'tips' => [
            '💡 **Complimentary Breakfast**: Guests receiving complimentary breakfast receive the fixed daily chef spread at ₹0. Any à la carte breakfast dishes or customized selections can be charged separately.',
            '💡 **1-Click Billing**: All active menu items appear in a convenient dropdown in **Billing & Invoices**, so staff can add meals to a guest folio with 1 click!'
        ],
        'step_doubts' => [
            '2' => 'You can switch between Breakfast, Lunch, High-Range Snacks, and Dinner tabs to organize the menu neatly.',
            '3' => 'Be sure to list the key ingredients/inclusions (e.g. "Appam with Vegetable Stew, Steamed Puttu, Fresh Fruit Platter") so guests know exactly what comes with each set.',
            '5' => 'If an item is seasonally unavailable, you can toggle its availability to "Inactive" instead of deleting it permanently.'
        ],
        'follow_up_doubts' => [
            "How does complimentary breakfast work in bookings?",
            "How do I add a food item to a guest's checkout bill?",
            "Where does the kitchen staff see food orders?",
            "Can I temporarily hide a dish that is out of stock?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 6. BOOKINGS & GUEST FOLIOS LIFECYCLE
    // ---------------------------------------------------------------------
    'bookings' => [
        'id' => 'bookings',
        'title' => 'Reservations Management & Guest Lifecycle',
        'card' => 'Reservations Console',
        'direct_url' => 'bookings.php',
        'direct_label' => 'Open Reservations Console',
        'keywords' => [
            'booking', 'bookings', 'reservation', 'reservations', 'guest', 'guests', 'checkin', 'checkout',
            'check in', 'check out', 'confirm booking', 'inhouse', 'in house', 'cancel booking',
            'id proof', 'aadhaar', 'passport', 'guest status', 'download id', 'folio',
            'booking cancel cheyyan', 'check in cheyyan', 'guest list'
        ],
        'summary' => 'Manage all guest stays through their lifecycle: **Pending Verification → Confirmed → In-House → Checked-Out → Settled**, along with ID proof documents, contact details, and special requests in `bookings.php`.',
        'steps' => [
            '1' => 'Click on **"Reservations"** in the left sidebar menu (or go to `bookings.php`).',
            '2' => 'Use the top status filters to view **Pending** (requires review), **Confirmed** (upcoming arrivals), **In-House** (currently staying), or **Completed** stays.',
            '3' => 'Click on any booking card or the **"View & Manage"** button to open the full Guest Folio modal.',
            '4' => 'To update guest status:<br>• **Confirm**: When advance payment or reservation is approved.<br>• **Mark In-House**: When the guest arrives at the sanctuary and checks in.<br>• **Complete Check-Out**: When the guest departs (this prompts settlement in Billing).<br>• **Cancel**: If the reservation is cancelled.',
            '5' => 'To view ID Proof: Click **"View / Download ID Proof"** to inspect or print the guest\'s Aadhaar, Passport, or Driving License uploaded during booking.'
        ],
        'tips' => [
            '💡 **WhatsApp Direct**: You can click the WhatsApp icon next to the guest\'s phone number to send them arrival instructions or booking confirmations directly with one click!',
            '💡 **Security**: Guest ID proofs are stored securely with cryptographic filenames and can only be downloaded by authenticated administrators.'
        ],
        'step_doubts' => [
            '3' => 'The folio modal shows the booked villa, dates, number of adults/children, room rate, food selections, advance paid, and pending balance.',
            '4' => 'When you mark a guest as "In-House", their stay appears with a live green indicator, and the Kitchen dashboard knows they are currently on the property.',
            '5' => 'If a guest didn\'t upload ID during booking, front desk staff can upload it directly from the folio modal during check-in.'
        ],
        'follow_up_doubts' => [
            "How do I mark a guest as Checked-In (In-House)?",
            "Where do I download the guest's uploaded ID proof?",
            "How do I transfer a booking to the Billing & Invoice page?",
            "What happens when I cancel a booking?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 7. BILLING, INVOICES & LUXURY A4 PRINTING
    // ---------------------------------------------------------------------
    'billing' => [
        'id' => 'billing',
        'title' => 'Guest Folios, Itemized Settlement & Luxury A4 Invoices',
        'card' => 'Billing & Invoices Hub',
        'direct_url' => 'billing.php',
        'direct_label' => 'Open Billing & Invoices Hub',
        'keywords' => [
            'billing', 'bill', 'bills', 'invoice', 'invoices', 'receipt', 'print bill', 'print invoice',
            'settlement', 'folio', 'tax invoice', 'a4', 'a4 print', 'pdf bill', 'discount',
            'extra charges', 'advance payment', 'balance payment', 'payment mode', 'whatsapp bill',
            'bill print', 'bill engane adikkan', 'invoice undakkan', 'payment record'
        ],
        'summary' => 'Generate itemized tax invoices, add custom extra charges (bonfires, jeep safari, laundry, extra dining), record advance and final payments, print luxury A4 tax invoices, or share them via WhatsApp in `billing.php`.',
        'steps' => [
            '1' => 'Click **"Billing & Invoices"** in the left sidebar menu (or go to `billing.php`).',
            '2' => 'Find the guest\'s folio from the list (filter by In-House or search by Guest Name / Booking Reference Code).',
            '3' => 'Click **"Customize & Settle Bill"** to open the live itemized billing editor.',
            '4' => 'Review the itemized breakdown:<br>• **Villa Accommodation**: Automatically computed based on nights and extra guests.<br>• **Food & Dining**: Automatically populated from dining orders.<br>• **Add Custom Item**: Click "+ Add Custom Line Item" to add campfire, guided trek, damage fee, or laundry.<br>• **Discount**: Enter any discount in ₹ or percentage.<br>• **GST**: Automatically calculated using your configured GST tax slabs.',
            '5' => 'Record Payment: Enter the payment mode (Cash, UPI / GPay, Credit Card, Bank Transfer) and amount paid.',
            '6' => 'Click **"Print Official A4 Tax Invoice"** for a luxury printable PDF layout, or click **"Share on WhatsApp"** to send the formatted bill summary and payment link directly to the guest\'s phone!'
        ],
        'tips' => [
            '💡 **Bank & QR Included**: The A4 printable invoice automatically includes your official Bank Account details, IFSC, and UPI QR Code configured in Card 14.',
            '💡 **Need an ad-hoc bill?** For walk-in restaurant guests or day visitors without a room reservation, use **"Create Custom Bill"** (`custom_bill.php`)!'
        ],
        'step_doubts' => [
            '3' => 'The billing editor is fully interactive — you can edit room tariffs, change food quantities, or add custom charges on the fly before finalizing.',
            '4' => 'Custom line items can have their own individual tax classification (e.g. food 5%, service 18%, or exempt 0%).',
            '6' => 'The A4 print layout is optimized for high-end hospitality: crisp fonts, high-contrast totals, QR payment box, and legal GSTIN footer.'
        ],
        'follow_up_doubts' => [
            "How do I add extra charges like Campfire or Jeep Safari to the bill?",
            "How do I print an A4 PDF invoice for the guest?",
            "How do I send the bill to the guest's WhatsApp?",
            "Can I create a bill for a walk-in guest who didn't book a room?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 8. CUSTOM BILLS (FOR DAY VISITS / WALK-INS)
    // ---------------------------------------------------------------------
    'custom_bill' => [
        'id' => 'custom_bill',
        'title' => 'Custom Invoices for Walk-Ins & Day Dining',
        'card' => 'Custom Bill Generator',
        'direct_url' => 'custom_bill.php',
        'direct_label' => 'Open Custom Bill Generator',
        'keywords' => [
            'custom bill', 'walk in', 'walkin', 'day visit', 'restaurant bill', 'dining bill',
            'non resident', 'day guest', 'farm tour bill', 'print custom bill', 'quick bill'
        ],
        'summary' => 'Create quick, professional tax invoices for day visitors, restaurant walk-ins, farm tours, or corporate day events without needing a room reservation in `custom_bill.php`.',
        'steps' => [
            '1' => 'Go to **Billing & Invoices** (`billing.php`) and click the top action button **"Create Custom Bill"**, or navigate to `custom_bill.php`.',
            '2' => 'Enter the **Guest / Company Name**, **Phone Number**, and optional GSTIN.',
            '3' => 'Add items to the bill: Choose dishes from the Food Menu or type custom line items with quantities and unit rates.',
            '4' => 'Select the payment method and enter any discount.',
            '5' => 'Click **"Generate & Print Bill"** to view and print the official luxury invoice (`print_custom_bill.php`).'
        ],
        'tips' => [
            '💡 Ideal for non-staying guests dining at the organic farm restaurant or purchasing farm harvest (passion fruit, organic coffee, honey).'
        ],
        'step_doubts' => [
            '3' => 'You can add multiple line items, including food dishes and activity charges.'
        ],
        'follow_up_doubts' => [
            "How do I select food menu items on a custom bill?",
            "Can I print this bill on standard A4 paper?",
            "Does it support GST calculation for walk-in guests?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 9. KITCHEN ORDER DASHBOARD
    // ---------------------------------------------------------------------
    'kitchen' => [
        'id' => 'kitchen',
        'title' => 'Live Kitchen Order Dashboard for Chefs',
        'card' => 'Kitchen Operations Console',
        'direct_url' => 'kitchen.php',
        'direct_label' => 'Open Kitchen Dashboard',
        'keywords' => [
            'kitchen', 'chef', 'food orders', 'cooking', 'meals today', 'breakfast orders',
            'lunch orders', 'dinner orders', 'kitchen status', 'pending orders', 'preparing',
            'ready', 'delivered', 'kitchen dashboard', 'aduppukootil', 'dining orders'
        ],
        'summary' => 'The Kitchen Dashboard (`kitchen.php`) is designed specifically for head chefs and kitchen staff to view live food orders grouped by meal timing (Breakfast, Lunch, Evening Tea/Snacks, Dinner) with preparation status toggles.',
        'steps' => [
            '1' => 'Click on **"Kitchen"** in the left sidebar navigation (or go to `kitchen.php`).',
            '2' => 'The dashboard displays all food orders for **Today\'s Active Stays** grouped into tabs: **Breakfast**, **Lunch**, **Snacks**, and **Dinner**.',
            '3' => 'Each order card shows: Villa name, Guest name, Guest count, Specific dishes ordered with quantities, and guest dietary notes.',
            '4' => 'Kitchen staff can update order status: **Pending → Preparing → Ready for Service → Delivered**.',
            '5' => 'Use the **"Print Kitchen KOT / Prep Sheet"** button to print a physical ticket for the cookhouse.'
        ],
        'tips' => [
            '💡 **Automatic Synchronization**: As soon as a guest completes a booking or submits an in-room food order, the kitchen order card appears in real-time!',
            '💡 **Complimentary Breakfast Indicator**: Complimentary chef spreads are highlighted with a green badge so the kitchen knows the exact guest headcount.'
        ],
        'step_doubts' => [
            '2' => 'You can filter by date using the date selector at the top to prepare for tomorrow\'s meals in advance.',
            '4' => 'Status updates help front desk and butler staff know exactly when dishes are ready for cottage service.'
        ],
        'follow_up_doubts' => [
            "How does the chef know what to cook for breakfast tomorrow?",
            "Can kitchen staff print an order ticket (KOT)?",
            "Where do in-room food orders submitted by guests show up?",
            "How do I disable food ordering when the kitchen is closed?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 10. INTERACTIVE CALENDAR & DATE BLOCKING
    // ---------------------------------------------------------------------
    'calendar' => [
        'id' => 'calendar',
        'title' => 'Interactive Booking Calendar & Date Blocking',
        'card' => 'Booking Calendar Console',
        'direct_url' => 'calendar.php',
        'direct_label' => 'Open Booking Calendar',
        'keywords' => [
            'calendar', 'dates', 'block dates', 'block room', 'unblock', 'maintenance',
            'date block', 'room block', 'availability', 'rate override', 'weekend price',
            'booked dates', 'calendar view', 'calendaril block cheyyan'
        ],
        'summary' => 'Visual monthly calendar (`calendar.php`) displaying occupancy across all cottages, allowing administrators to block dates for maintenance/repairs, create direct holds, or view guest arrivals.',
        'steps' => [
            '1' => 'Click on **"Booking Calendar"** in the left navigation (or open `calendar.php`).',
            '2' => 'Select the cottage you want to inspect (e.g. Alpine Wood House or Earthen Mudhouse).',
            '3' => 'The calendar displays confirmed stays in green/gold and blocked dates in dark slate.',
            '4' => 'To block dates for maintenance or VIP hold: Click the **"Block Dates"** button, pick the start and end dates, select the reason (e.g. Annual Cob Maintenance or Private Family Hold), and click **"Confirm Block"**.',
            '5' => 'To unblock: Click on any existing blocked date block and select **"Remove Block"**.'
        ],
        'tips' => [
            '💡 **Instant Sync**: Blocked dates immediately become unavailable on the public website booking modal so guests cannot book them.',
            '💡 **OTA Export**: If you have Airbnb/Booking.com synced via iCal, blocked dates are automatically transmitted to external portals!'
        ],
        'step_doubts' => [
            '4' => 'You can block dates for either one specific villa or all villas simultaneously.',
            '5' => 'Unblocking immediately re-opens the dates for online reservations.'
        ],
        'follow_up_doubts' => [
            "How do I block dates for room renovation or maintenance?",
            "Will blocked dates sync to Airbnb and Booking.com?",
            "How do I unblock a date that was previously blocked?",
            "Can I see guest details directly by clicking a date on the calendar?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 11. OTA & CHANNEL SYNC (AIRBNB, BOOKING.COM, ICAL)
    // ---------------------------------------------------------------------
    'channel_sync' => [
        'id' => 'channel_sync',
        'title' => 'OTA Channel Sync (Airbnb, Booking.com, iCal Feeds)',
        'card' => 'OTA Channel Sync Console',
        'direct_url' => 'channel_sync.php',
        'direct_label' => 'Open OTA Channel Sync',
        'keywords' => [
            'channel sync', 'ota', 'airbnb', 'booking.com', 'agoda', 'ical', 'calendar sync',
            'sync bookings', 'export ical', 'import ical', 'ota sync', 'double booking'
        ],
        'summary' => 'Synchronize cottage availability automatically with Airbnb, Booking.com, and other travel platforms using standard 2-way iCal calendar feeds to prevent double bookings in `channel_sync.php`.',
        'steps' => [
            '1' => 'Click **"OTA Channel Sync"** in the left sidebar menu (or go to `channel_sync.php`).',
            '2' => 'Copy your **Sanctuary Export iCal URL** for the respective villa (e.g. `api/ical_sync.php?room=woodhouse`) and paste it into your Airbnb or Booking.com calendar import settings.',
            '3' => 'Paste your Airbnb / Booking.com iCal calendar export links into the **Import Feeds** box for that cottage.',
            '4' => 'Click **"Save Feeds & Sync Now"**.',
            '5' => 'The system will fetch all external reservations and block those dates on your Food Forest website automatically!'
        ],
        'tips' => [
            '💡 The synchronization runs automatically in the background, but you can click "Sync Now" anytime for an instant refresh before confirming a manual booking.'
        ],
        'step_doubts' => [
            '2' => 'Each villa has its own unique iCal URL to keep calendars completely separate.',
            '3' => 'You can add multiple external links (e.g. one for Airbnb and one for Booking.com) for the same room.'
        ],
        'follow_up_doubts' => [
            "How do I find my Airbnb iCal export link?",
            "Where do I paste the Food Forest iCal link into Airbnb?",
            "How often does the system sync automatically?",
            "How does this prevent double-booking?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 12. SANCTUARY MAP, SPOTS & WALKING TRAILS
    // ---------------------------------------------------------------------
    'sanctuary_map' => [
        'id' => 'sanctuary_map',
        'title' => 'Sanctuary Interactive Map, Trail Routes & Waypoints',
        'card' => 'Card 08 (Sanctuary Map & Trails)',
        'direct_url' => 'edit_section.php?section=sanctuary_map',
        'direct_label' => 'Open Sanctuary Map Editor',
        'keywords' => [
            'map', 'sanctuary map', 'spots', 'trail', 'trails', 'walking trail', 'routes',
            'waypoint', 'waypoints', 'coordinates', 'viewpoints', 'entrance', 'estate exit',
            'card 08', 'map pins', 'route line', 'color line'
        ],
        'summary' => 'Configure the interactive botanical estate map in **Card 08 (Sanctuary Map)**. Manage numbered landmark spots (Apple Orchard, Cob Hearth, Spring Viewpoint), Entrance/Exit coordinates, and draw customized colored walking trails connecting waypoints.',
        'steps' => [
            '1' => 'Go to **Estate Settings** -> click **Card 08 (Sanctuary Map & Trails)**, or use the direct button above.',
            '2' => 'Use the visual map coordinate picker to place or reposition spots: Click anywhere on the map graphic to set X% and Y% coordinates for viewpoints.',
            '3' => 'To edit a Spot: Provide the Spot Number (1-18), Title, Description, and Icon.',
            '4' => 'To customize Walking Trails: Under **Trail Routes**, select a trail (e.g. "Main Sanctuary Walking Trail"), choose the line color, line width, and style (solid or dashed), and click nodes to connect the entrance and waypoints.',
            '5' => 'Click **"Save Map Configuration"** to publish the interactive trail map to the live guest website.'
        ],
        'tips' => [
            '💡 Guests can click any spot on the live website to see photos, elevations, and botanical notes for that exact coordinate on the farm.'
        ],
        'step_doubts' => [
            '2' => 'Coordinates are stored as percentages (0% to 100%) so the pins align accurately across mobile phones, tablets, and desktop screens.'
        ],
        'follow_up_doubts' => [
            "How do I add a new viewpoint spot to the map?",
            "How do I draw or change the color of walking trails?",
            "Where is the map displayed on the guest website?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 13. EXPERIENCES & SANCTUARY RITUALS
    // ---------------------------------------------------------------------
    'experiences' => [
        'id' => 'experiences',
        'title' => 'Curated Sanctuary Experiences & Rituals',
        'card' => 'Card 05 (Curated Experiences)',
        'direct_url' => 'edit_section.php?section=experiences',
        'direct_label' => 'Open Experiences Editor',
        'keywords' => [
            'experiences', 'rituals', 'activities', 'campfire', 'harvest', 'mud workshop',
            'sunset trail', 'orchard walk', 'card 05', 'timings', 'activity price', 'add experience'
        ],
        'summary' => 'Configure signature estate rituals like Orchard Harvest Walks, Vernacular Cob Workshops, Sunset Ridgeline Treks, and Starlight Campfires in **Card 05 (Experiences)**.',
        'steps' => [
            '1' => 'Open **Estate Settings** -> click **Card 05 (Curated Experiences)**.',
            '2' => 'To edit an existing ritual: Change the title, time schedule (e.g. `04:30 PM • 90 MINS`), narrative description, and upload a scenic photo.',
            '3' => 'To add a new activity: Click **"+ Add Experience"**, enter the badge, heading, subtitle, timing, and details.',
            '4' => 'Click **"Save Experiences"**.'
        ],
        'tips' => [
            '💡 Highlight whether an experience is complimentary or requires prior concierge arrangement.'
        ],
        'step_doubts' => [
            '2' => 'Include practical advice such as recommended footwear or ideal weather.'
        ],
        'follow_up_doubts' => [
            "Can I charge guests for specific experiences on their bill?",
            "How do I add a new ritual with photo and timing?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 14. VISUAL PHOTO GALLERY (8-PHOTO DIARY)
    // ---------------------------------------------------------------------
    'gallery' => [
        'id' => 'gallery',
        'title' => 'Visual Photo Chronicle & Gallery',
        'card' => 'Card 10 (Visual Diary & Gallery)',
        'direct_url' => 'edit_section.php?section=gallery',
        'direct_label' => 'Open Gallery Editor',
        'keywords' => [
            'gallery', 'photos', 'photo diary', 'images', 'upload photo', 'card 10',
            'visual diary', 'photography', 'photo gallery', 'pictures', 'replace photo'
        ],
        'summary' => 'Curate the 8-photo luxury visual diary on the homepage in **Card 10 (Visual Diary)**. Set high-resolution images, tags (e.g. `EARTHEN ARCHITECTURE`, `CANOPY DWELLING · 1,640M`), captions, and display order.',
        'steps' => [
            '1' => 'Navigate to **Estate Settings** -> click **Card 10 (Visual Diary & Gallery)**.',
            '2' => 'Each of the 8 visual frames has its own slot. Click **"Change Image"** on any frame to upload a new high-resolution photo.',
            '3' => 'Update the **Photo Title**, **Category Tag**, and **Poetic Caption**.',
            '4' => 'Click **"Save Gallery"**, then click **"Purge Cache"** in the topbar to see the new photos live instantly!'
        ],
        'tips' => [
            '💡 Use the Media Optimizer (Card 16) to automatically compress large smartphone or camera photos into fast-loading WebP format.'
        ],
        'step_doubts' => [
            '4' => 'If an old photo still appears on your phone, click "Purge Cache" in the topbar to bust browser image cache.'
        ],
        'follow_up_doubts' => [
            "Why is my new photo not showing immediately?",
            "What image size and resolution is best for the gallery?",
            "How do I change the category tags on photos?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 15. GUEST REFLECTIONS & TESTIMONIALS
    // ---------------------------------------------------------------------
    'testimonials' => [
        'id' => 'testimonials',
        'title' => 'Guest Reflections, Reviews & Testimonials',
        'card' => 'Card 11 (Guest Reflections)',
        'direct_url' => 'edit_section.php?section=testimonials',
        'direct_label' => 'Open Reviews & Reflections Editor',
        'keywords' => [
            'testimonials', 'reviews', 'reflections', 'guest review', 'ratings', 'stars',
            'approve review', 'card 11', 'guest quotes', 'add review', 'feedback'
        ],
        'summary' => 'Moderate, approve, edit, or add verified guest testimonials and reviews in **Card 11 (Guest Reflections)** to highlight genuine guest experiences on the homepage.',
        'steps' => [
            '1' => 'Go to **Estate Settings** -> click **Card 11 (Guest Reflections & Testimonials)**.',
            '2' => 'Pending reviews submitted by guests appear under the **"Pending Verification"** tab. Click **"Approve"** to publish them to the live website.',
            '3' => 'To manually add a review (e.g. from Google Maps or Airbnb): Click **"+ Add Guest Reflection"**, enter the Guest Name, Origin City, Star Rating (1-5), Date of Stay, and their Quote.',
            '4' => 'Click **"Save Testimonials"**.'
        ],
        'tips' => [
            '💡 You can feature the top 3 best reviews to appear prominently in the hero carousel.'
        ],
        'step_doubts' => [
            '2' => 'You can edit typos or format quotes before approving.'
        ],
        'follow_up_doubts' => [
            "How do I approve reviews submitted by guests?",
            "Can I manually add reviews from Google or Airbnb?",
            "How do I delete or unpublish an unwanted review?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 16. WHATSAPP CONCIERGE, PHONE & LOCATION
    // ---------------------------------------------------------------------
    'whatsapp' => [
        'id' => 'whatsapp',
        'title' => 'WhatsApp Concierge Hotline, Phone & Address',
        'card' => 'Card 12 (WhatsApp & Contact)',
        'direct_url' => 'edit_section.php?section=whatsapp',
        'direct_label' => 'Open WhatsApp & Contact Editor',
        'keywords' => [
            'whatsapp', 'phone', 'contact', 'telephone', 'mobile', 'concierge number',
            'address', 'location', 'google map link', 'card 12', 'phone number change'
        ],
        'summary' => 'Set the official WhatsApp Concierge number, direct hotline, email address, physical estate address, and Google Maps GPS navigation link in **Card 12 (WhatsApp & Contact)**.',
        'steps' => [
            '1' => 'Go to **Estate Settings** -> click **Card 12 (WhatsApp Concierge & Hotline)**.',
            '2' => 'Enter the **WhatsApp Number** with country code (e.g. `+91 94000 00000` or `919400000000`).',
            '3' => 'Enter the **Direct Calling Hotline** and **Email Address**.',
            '4' => 'Enter the **Estate Physical Address** (Kanthalloor, Idukki, Kerala) and paste the **Google Maps Share URL**.',
            '5' => 'Click **"Save Contact Settings"**.'
        ],
        'tips' => [
            '💡 All "Book via WhatsApp" buttons on the public website automatically link to the WhatsApp number you enter here with a pre-filled arrival message!'
        ],
        'step_doubts' => [
            '2' => 'Include the 91 country code for India so international and domestic guests can initiate WhatsApp chats with one tap.'
        ],
        'follow_up_doubts' => [
            "How does the floating WhatsApp button work on the website?",
            "How do I update the Google Maps pin link?",
            "Can I set custom pre-filled WhatsApp inquiry messages?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 17. 360° VIRTUAL WALKTHROUGH ENGINE
    // ---------------------------------------------------------------------
    'walkthrough_360' => [
        'id' => 'walkthrough_360',
        'title' => '360° Virtual Walkthrough Toggle & Room Links',
        'card' => 'Settings Hub (360° Walkthrough Control)',
        'direct_url' => 'settings.php',
        'direct_label' => 'Open 360° Walkthrough Settings',
        'keywords' => [
            '360 walkthrough', 'walkthrough', '360 tour', 'virtual tour', 'walkthrough toggle',
            'turn on 360', 'turn off 360', 'link room walkthrough', 'woodhouse pano', 'mudhouse pano'
        ],
        'summary' => 'Toggle the interactive 360° Virtual Walkthrough experience on the homepage ON or OFF and link which specific Woodhouse and Mudhouse rooms provide the 360° panoramas.',
        'steps' => [
            '1' => 'Navigate to **Estate Settings** (`settings.php`).',
            '2' => 'Find the **"360° Virtual Walkthrough Experience"** card.',
            '3' => 'Toggle the switch to **Enabled** to display the immersive 360° tour on the public homepage, or **Disabled** to hide it.',
            '4' => 'Under **Linked Rooms**, select which Woodhouse and Mudhouse inventory rooms supply the 360° equirectangular panoramas.',
            '5' => 'Click **"Save Walkthrough Settings"**.'
        ],
        'tips' => [
            '💡 To upload new 360° panorama images, open **Card 04 (Villas & Stays)** and upload the equirectangular image under the respective room.'
        ],
        'step_doubts' => [
            '3' => 'Disabling hides the section completely from guests if you don\'t have 360 photos ready yet.'
        ],
        'follow_up_doubts' => [
            "How do I upload a 360° panorama photo?",
            "Can I temporarily hide the 360 tour from the homepage?",
            "Which rooms are currently linked to the 360 walkthrough?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 18. CLEAR DEMO / DUMMY DATA SAFELY
    // ---------------------------------------------------------------------
    'clear_data' => [
        'id' => 'clear_data',
        'title' => 'Clear Demo / Dummy Data Tool (Safe Reset)',
        'card' => 'User Manual & Maintenance (Step 7)',
        'direct_url' => 'user_manual.php#section-clear-data',
        'direct_label' => 'Open Safe Clear Demo Data Tool',
        'keywords' => [
            'clear demo data', 'clear dummy data', 'delete test bookings', 'reset data', 'clean bookings',
            'truncate', 'dummy data', 'delete test data', 'remove demo', 'fresh start', 'reset menu',
            'dummy data delete cheyyan', 'test bookings remove cheyyan', 'clear dummy', 'clear dummy bookings',
            'delete dummy bookings', 'clear test bookings', 'delete test data', 'clear demo bookings'
        ],
        'summary' => 'When launching the estate for real guests, use the safe **Clear Demo Data Utility** in `user_manual.php` to erase test bookings and test inquiries without touching your real villa rates, bank details, or branding!',
        'steps' => [
            '1' => 'Click on **"User Manual"** in the left sidebar navigation, or scroll to the bottom of `user_manual.php`.',
            '2' => 'Locate **Step 7: Clear Dummy / Demo Data Tool (ഡമ്മി ഡാറ്റ ക്ലിയർ ചെയ്യൽ)**.',
            '3' => 'Check the boxes for what you want to clean:<br>• **Clear Test Bookings & Guest Folios** (resets reservation table to 0).<br>• **Clear Test Inquiries** (resets contact messages).<br>• Optional: Reset Food Menu or Gallery to clean defaults.',
            '4' => 'For safety, type the word **RESET** in capital letters into the confirmation input box.',
            '5' => 'Click the red button **"Execute Safe Data Clearing"**.<br>All demo test bookings will be permanently erased, giving you a 100% clean dashboard ready for live operations!'
        ],
        'tips' => [
            '⚠️ **Safety Guarantee**: This tool only clears selected test tables (like bookings or inquiries). Your room settings, pricing, bank details, and admin login are 100% protected and NEVER deleted!'
        ],
        'step_doubts' => [
            '4' => 'Typing "RESET" prevents accidental clicks by staff.'
        ],
        'follow_up_doubts' => [
            "Will clearing demo data delete my room prices or bank settings?",
            "Can I delete only test bookings and keep the food menu?",
            "How do I confirm the reset was successful?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 19. INSTANT CACHE PURGE
    // ---------------------------------------------------------------------
    'purge_cache' => [
        'id' => 'purge_cache',
        'title' => 'Instant Cache Purge & Fast Refresh',
        'card' => 'Topbar Bolt Button & Settings Hub',
        'direct_url' => 'settings.php?action=purge_cache',
        'direct_label' => 'Purge Cache Now',
        'keywords' => [
            'cache', 'purge cache', 'refresh cache', 'clear cache', 'photo not updating',
            'text not updating', 'image not changing', 'cache clear', 'fast refresh', 'bolt button'
        ],
        'summary' => 'If you upload a new photo, change a tariff, or edit text and don\'t see it change immediately on the public website, use the **Purge Cache** tool to force instant browser and server cache re-validation.',
        'steps' => [
            '1' => 'Look at the **Top Header Bar** on any admin page.',
            '2' => 'Click the **"Purge Cache"** button with the lightning bolt icon `<i class="fa-solid fa-bolt"></i>`.',
            '3' => 'Click "OK" on the confirmation prompt.',
            '4' => 'The server purges all compiled schema caches, asset version hashes, and query caches. Now refresh your public website (`F5` or `Ctrl+F5`) to see the fresh content!'
        ],
        'tips' => [
            '💡 All uploaded photos feature auto-busting version query strings (`?v=179...`), so purging cache guarantees clients download the latest files immediately.'
        ],
        'step_doubts' => [
            '2' => 'The button is always visible in the top right of the screen on every admin page.'
        ],
        'follow_up_doubts' => [
            "Why do uploaded photos sometimes show old versions?",
            "How often should I purge the cache?",
            "Does purging cache affect active guest sessions?"
        ]
    ],

    // ---------------------------------------------------------------------
    // 20. USER PROFILE, PASSWORDS & ACCOUNT MANAGEMENT
    // ---------------------------------------------------------------------
    'profile' => [
        'id' => 'profile',
        'title' => 'Admin Credentials, Passwords & User Management',
        'card' => 'Profile & User Management Modal',
        'direct_url' => 'javascript:if(window.openProfileModal)openProfileModal();',
        'direct_label' => 'Open Profile & Users Modal',
        'keywords' => [
            'password', 'change password', 'username', 'admin user', 'create admin', 'delete admin',
            'avatar', 'profile photo', 'login credentials', 'security', 'user management', 'account'
        ],
        'summary' => 'Manage admin account credentials, change your password, update profile avatars, or create additional concierge administrator accounts in the **Credentials & User Management Modal**.',
        'steps' => [
            '1' => 'Click on your **User Avatar / Name** in the top-right header or bottom-left sidebar.',
            '2' => 'Select **"Account Settings & Profile"** to open the modal.',
            '3' => 'Under **"My Profile & Security"**:<br>• Change your **Full Name** or **Email**.<br>• Upload a personal photo avatar or select a sanctuary emblem.<br>• Enter your **New Password** to update your login credentials.',
            '4' => 'Under **"All Users & Admins"** (Super Admin only):<br>• View all active administrator accounts.<br>• Click "+ Add New Administrator" to create accounts for front desk or kitchen staff with specific roles.<br>• Reset passwords or delete inactive accounts.',
            '5' => 'Click **"Save Profile & Security"**.'
        ],
        'tips' => [
            '💡 Always use a strong password with at least 8 characters including letters and numbers.'
        ],
        'step_doubts' => [
            '3' => 'Leave the new password field empty if you only want to update your name or profile picture.'
        ],
        'follow_up_doubts' => [
            "How do I add a new admin account for front desk staff?",
            "What if I forget my admin password?",
            "Can other admins delete my account?"
        ]
    ]
];

// =========================================================================
// QUERY MATCHING ENGINE
// Matches query against keywords and current context
// =========================================================================

$best_match = null;
$best_score = 0;

// Check if user is asking about a specific step of the current topic!
$is_step_doubt = false;
$target_step = 0;
if (!empty($current_topic) && isset($kb[$current_topic])) {
    // Look for step numbers in the query
    if (preg_match('/(?:step|point|number|stpe)\s*([1-9])/i', $query, $matches)) {
        $is_step_doubt = true;
        $target_step = (int)$matches[1];
    } elseif (preg_match('/(?:in|at|for|about)\s*(?:the\s*)?(?:first|1st)\s*step/i', $query)) {
        $is_step_doubt = true;
        $target_step = 1;
    } elseif (preg_match('/(?:in|at|for|about)\s*(?:the\s*)?(?:second|2nd)\s*step/i', $query)) {
        $is_step_doubt = true;
        $target_step = 2;
    } elseif (preg_match('/(?:in|at|for|about)\s*(?:the\s*)?(?:third|3rd)\s*step/i', $query)) {
        $is_step_doubt = true;
        $target_step = 3;
    } elseif (preg_match('/(?:in|at|for|about)\s*(?:the\s*)?(?:fourth|4th)\s*step/i', $query)) {
        $is_step_doubt = true;
        $target_step = 4;
    } elseif (preg_match('/(?:in|at|for|about)\s*(?:the\s*)?(?:fifth|5th)\s*step/i', $query)) {
        $is_step_doubt = true;
        $target_step = 5;
    }
}

// If user is directly asking a doubt about a step of the current topic:
if ($is_step_doubt && $target_step > 0 && isset($kb[$current_topic])) {
    $topic = $kb[$current_topic];
    $step_str = (string)$target_step;
    $step_text = $topic['steps'][$step_str] ?? '';
    $step_explanation = $topic['step_doubts'][$step_str] ?? "Here is the detailed procedure for Step {$target_step}: {$step_text}";

    $reply_html = "<div class='adm-bot-answer'>";
    $reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-circle-question'></i> Doubt Clarification: Step {$target_step} of {$topic['title']}</div>";
    $reply_html .= "<h4 class='adm-bot-title'>Clarification for Step {$target_step}</h4>";
    $reply_html .= "<div class='adm-bot-card-highlight' style='margin: 10px 0; padding: 12px 16px; background: rgba(197, 160, 89, 0.1); border-left: 3px solid var(--adm-gold); border-radius: 6px;'>";
    $reply_html .= "<strong>Original Step {$target_step}:</strong> {$step_text}";
    $reply_html .= "</div>";
    $reply_html .= "<div class='adm-bot-desc' style='line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary); margin-bottom: 14px;'>";
    $reply_html .= $step_explanation;
    $reply_html .= "</div>";

    // Direct action button
    $reply_html .= "<div class='adm-bot-actions' style='margin-top: 14px;'>";
    $reply_html .= "<a href='{$topic['direct_url']}' class='adm-btn-action gold' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding: 8px 16px; font-size: 12.5px;'>";
    $reply_html .= "<i class='fa-solid fa-arrow-up-right-from-square'></i> <span>{$topic['direct_label']}</span>";
    $reply_html .= "</a>";
    $reply_html .= "</div>";
    $reply_html .= "</div>";

    echo json_encode([
        'success' => true,
        'topic' => $current_topic,
        'reply' => $reply_html,
        'follow_up_doubts' => $topic['follow_up_doubts'] ?? []
    ]);
    exit;
}

// Otherwise, score knowledge base items
foreach ($kb as $key => $item) {
    $score = 0;
    foreach ($item['keywords'] as $kw) {
        $kw_norm = normalize_text($kw);
        if (empty($kw_norm)) continue;

        $kw_words = explode(' ', $kw_norm);
        $num_words = count($kw_words);

        // 1. Exact full match
        if ($normalized === $kw_norm) {
            $score += 120;
        }
        // 2. Exact multi-word phrase match inside query (e.g. "food ordering" inside query)
        elseif (strpos($normalized, $kw_norm) !== false) {
            $score += ($num_words > 1) ? (45 + ($num_words * 25)) : (12 + strlen($kw_norm));
        }
        // 3. Multi-word all individual words present in query (e.g. "clear" and "dummy" both present)
        elseif ($num_words > 1) {
            $all_present = true;
            foreach ($kw_words as $w) {
                if (strlen($w) > 2 && strpos($normalized, $w) === false) {
                    $all_present = false;
                    break;
                }
            }
            if ($all_present) {
                $score += 35 + ($num_words * 12);
            }
        }
    }

    // Boost score if this was the current active topic
    if (!empty($current_topic) && $key === $current_topic) {
        $score += 8;
    }

    if ($score > $best_score) {
        $best_score = $score;
        $best_match = $item;
    }
}

// If no strong match found, check for general greeting or fallback
if ($best_score < 10) {
    // Check for greetings
    if (preg_match('/^(hi|hello|hey|namaste|vanakkam|good morning|good afternoon|good evening|help|options)\b/i', $normalized)) {
        $reply_html = "<div class='adm-bot-answer'>";
        $reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-seedling'></i> Sanctuary Concierge Bot</div>";
        $reply_html .= "<h4 class='adm-bot-title'>Welcome to Food Forest Admin Assistant! 🌿</h4>";
        $reply_html .= "<p style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 12px;'>";
        $reply_html .= "I am here to guide you step-by-step through any setting or task in this admin console, and resolve any doubts about each step. What would you like to do?";
        $reply_html .= "</p>";
        $reply_html .= "<div style='font-size: 12.5px; font-weight: 700; color: var(--adm-gold); margin-bottom: 8px;'>Popular Inquiries & Setup Guides:</div>";
        $reply_html .= "<ul style='padding-left: 20px; font-size: 13px; line-height: 1.7; color: var(--adm-text-secondary); margin-bottom: 12px;'>";
        $reply_html .= "<li><strong>Tariffs & Rooms:</strong> How to edit villa prices, base guests, and extra person rates.</li>";
        $reply_html .= "<li><strong>Bank & UPI QR:</strong> How to add bank details and QR codes for guest billing.</li>";
        $reply_html .= "<li><strong>GST Settings:</strong> Multi-tier 5% Food and 12% Room tax setup.</li>";
        $reply_html .= "<li><strong>Billing & Invoices:</strong> How to generate and print luxury A4 folios.</li>";
        $reply_html .= "<li><strong>Calendar & OTA:</strong> Date blocking and 2-way Airbnb sync.</li>";
        $reply_html .= "<li><strong>Clear Demo Data:</strong> How to safely delete test bookings.</li>";
        $reply_html .= "</ul>";
        $reply_html .= "</div>";

        echo json_encode([
            'success' => true,
            'topic' => '',
            'reply' => $reply_html,
            'follow_up_doubts' => [
                "How do I change villa tariffs?",
                "How to set up Bank & UPI QR code?",
                "How does guest billing and invoicing work?",
                "How to sync calendar with Airbnb?"
            ]
        ]);
        exit;
    }

    // Friendly intelligent fallback with relevant suggestions
    $reply_html = "<div class='adm-bot-answer'>";
    $reply_html .= "<div class='adm-bot-badge red'><i class='fa-solid fa-circle-info'></i> Query Helpdesk</div>";
    $reply_html .= "<h4 class='adm-bot-title'>I want to ensure you get the exact procedure!</h4>";
    $reply_html .= "<p style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 12px;'>";
    $reply_html .= "Could you clarify what you'd like to set up? You can ask about changing room rates, uploading bank details/QR code, billing and GST, food menu, calendar date blocking, or clearing demo data.";
    $reply_html .= "</p>";
    $reply_html .= "<p style='font-size: 12.5px; color: var(--adm-text-muted); margin: 0;'>";
    $reply_html .= "💡 <em>Tip: You can also ask in Malayalam or Manglish (e.g., 'room rate change cheyyunnath engane', 'bill print cheyyaan procedure').</em>";
    $reply_html .= "</p>";
    $reply_html .= "</div>";

    echo json_encode([
        'success' => true,
        'topic' => '',
        'reply' => $reply_html,
        'follow_up_doubts' => [
            "How do I change villa tariffs?",
            "How to set up Bank & UPI QR code?",
            "How does guest billing and invoicing work?",
            "How to sync calendar with Airbnb?"
        ]
    ]);
    exit;
}

// We have a matched knowledge item! Build detailed step-by-step response
$item = $best_match;

$reply_html = "<div class='adm-bot-answer'>";
$reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-compass'></i> {$item['card']}</div>";
$reply_html .= "<h4 class='adm-bot-title'>{$item['title']}</h4>";
$reply_html .= "<p class='adm-bot-summary' style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 14px;'>{$item['summary']}</p>";

// Direct Jump Shortcut Button
if (!empty($item['direct_url']) && !empty($item['direct_label'])) {
    $reply_html .= "<div style='margin-bottom: 16px;'>";
    $reply_html .= "<a href='{$item['direct_url']}' class='adm-btn-action gold' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding: 7px 16px; font-size: 12.5px;'>";
    $reply_html .= "<i class='fa-solid fa-arrow-up-right-from-square'></i> <span>{$item['direct_label']}</span>";
    $reply_html .= "</a>";
    $reply_html .= "</div>";
}

// Step-by-Step Procedure
$reply_html .= "<div class='adm-bot-steps-wrapper' style='background: rgba(16, 31, 21, 0.5); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px; margin-bottom: 16px;'>";
$reply_html .= "<div style='font-size: 12.5px; font-weight: 700; color: #FFFFFF; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; display:flex; align-items:center; gap:8px;'>";
$reply_html .= "<i class='fa-solid fa-list-ol' style='color: var(--adm-gold);'></i> Step-by-Step Procedure:";
$reply_html .= "</div>";
$reply_html .= "<ol class='adm-bot-steps-list' style='padding-left: 20px; margin: 0; line-height: 1.7; font-size: 13px; color: var(--adm-text-secondary);'>";
foreach ($item['steps'] as $step_idx => $step_desc) {
    $reply_html .= "<li style='margin-bottom: 8px;'>{$step_desc}</li>";
}
$reply_html .= "</ol>";
$reply_html .= "</div>";

// Tips and Cautions
if (!empty($item['tips'])) {
    $reply_html .= "<div class='adm-bot-tips' style='margin-bottom: 16px; display:flex; flex-direction:column; gap:8px;'>";
    foreach ($item['tips'] as $tip) {
        $reply_html .= "<div style='font-size: 12.5px; line-height: 1.5; padding: 10px 14px; background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--adm-gold); border-radius: 4px; color: #E2E8F0;'>{$tip}</div>";
    }
    $reply_html .= "</div>";
}

// Ask doubts about these steps prompt
$reply_html .= "<div style='font-size: 12px; font-weight: 700; color: var(--adm-gold); margin-bottom: 4px; display:flex; align-items:center; gap:6px;'>";
$reply_html .= "<i class='fa-solid fa-comments-question-check'></i> Have doubts about any of these steps?";
$reply_html .= "</div>";
$reply_html .= "<div style='font-size: 12px; color: var(--adm-text-muted); margin-bottom: 10px;'>Click any question below or type your doubt directly (e.g. <em>'explain step 2'</em> or <em>'what is base guests?'</em>):</div>";

$reply_html .= "</div>";

echo json_encode([
    'success' => true,
    'topic' => $item['id'],
    'reply' => $reply_html,
    'follow_up_doubts' => $item['follow_up_doubts'] ?? []
]);
exit;
