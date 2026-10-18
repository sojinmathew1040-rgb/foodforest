<?php
// =========================================================================
// Food Forest Sanctuary — Admin Assistant & Helpdesk Chatbot API
// Intelligent Multi-Lingual Conversational Guide (English, Malayalam & Tamil)
// Complete Knowledge Base (20 Topics + Direct Doubt FAQ Resolver Engine)
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
$requested_lang = strtolower(trim($data['lang'] ?? 'en'));
if (!in_array($requested_lang, ['en', 'ml', 'ta'])) {
    $requested_lang = 'en';
}

$context = $data['context'] ?? [];
$current_topic = trim($context['current_topic'] ?? '');

// -------------------------------------------------------------------------
// INTELLIGENT LANGUAGE DETECTION (Script + Manglish / Tanglish)
// -------------------------------------------------------------------------
$detected_lang = $requested_lang;

// 1. Script-based detection
if (preg_match('/[\x{0D00}-\x{0D7F}]/u', $query)) {
    $detected_lang = 'ml';
} elseif (preg_match('/[\x{0B80}-\x{0BFF}]/u', $query)) {
    $detected_lang = 'ta';
} else {
    // 2. Manglish detection (Malayalam written in Latin script)
    $manglish_markers = [
        'engane', 'cheyyaam', 'cheyyam', 'cheyyunnath', 'maattaam', 'maattam', 'vaadaka',
        'nokkam', 'evide', 'adukkala', 'muri', 'panam', 'kaash', 'vannu', 'kalayan',
        'kodukkam', 'pattumo', 'ente', 'ithu', 'ividam', 'kure', 'thudangaan', 'nirthan'
    ];
    foreach ($manglish_markers as $m) {
        if (preg_match('/\b' . $m . '\b/i', $query)) {
            $detected_lang = 'ml';
            break;
        }
    }

    // 3. Tanglish detection (Tamil written in Latin script)
    if ($detected_lang === $requested_lang) {
        $tanglish_markers = [
            'eppadi', 'seivathu', 'kattanam', 'vadagai', 'theriyala', 'panna',
            'ennaku', 'mudiyuma', 'enga', 'yenna', 'venum', 'illai', 'nalla'
        ];
        foreach ($tanglish_markers as $t) {
            if (preg_match('/\b' . $t . '\b/i', $query)) {
                $detected_lang = 'ta';
                break;
            }
        }
    }
}
$lang = $detected_lang;

/**
 * Normalizes query string for robust keyword & semantic matching across English, Malayalam and Tamil.
 * Preserves unicode letters (\p{L}), combining vowel marks (\p{M}), numbers (\p{N}) and spaces.
 */
function normalize_text($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^\p{L}\p{M}\p{N}\s]/u', ' ', $str);
    $str = preg_replace('/\s+/u', ' ', $str);
    return trim($str);
}

$normalized = normalize_text($query);

// Empty query fallback
if (empty($query)) {
    $empty_messages = [
        'en' => [
            'reply' => "Please ask a question or doubt about any setting or feature in this admin panel.",
            'doubts' => [
                "How do I change villa tariffs?",
                "How to set up Bank & UPI QR code?",
                "How does guest billing and invoicing work?",
                "How to sync calendar with Airbnb?"
            ]
        ],
        'ml' => [
            'reply' => "അഡ്മിൻ പാനലിലെ ഏതെങ്കിലും സെറ്റിംഗ്സ് അല്ലെങ്കിൽ ഫീച്ചറുകളെക്കുറിച്ചുള്ള സംശയങ്ങൾ ചോദിക്കൂ.",
            'doubts' => [
                "വില്ല വാടക എങ്ങനെ മാറ്റാം?",
                "ബാങ്ക് വിവരങ്ങളും UPI QR കോഡും എങ്ങനെ നൽകാം?",
                "ഗസ്റ്റ് ബില്ലിംഗും ഇൻവോയ്സും എങ്ങനെ പ്രവർത്തിക്കുന്നു?",
                "Airbnb കലണ്ടർ എങ്ങനെ സിങ്ക് ചെയ്യാം?"
            ]
        ],
        'ta' => [
            'reply' => "நிர்வாக குழுவில் உள்ள எந்தவொரு அமைப்பு அல்லது அம்சத்தைப் பற்றிய கேள்விகளை கேட்கவும்.",
            'doubts' => [
                "வில்லா கட்டணத்தை மாற்றுவது எப்படி?",
                "வங்கி & UPI QR குறியீட்டை அமைப்பது எப்படி?",
                "பில்லிங் மற்றும் விலைப்பட்டியல் எவ்வாறு செயல்படுகிறது?",
                "Airbnb நாட்காட்டியை எவ்வாறு இணைப்பது?"
            ]
        ]
    ];
    $e_pkg = $empty_messages[$lang] ?? $empty_messages['en'];
    echo json_encode([
        'success' => false,
        'lang' => $lang,
        'reply' => $e_pkg['reply'],
        'follow_up_doubts' => $e_pkg['doubts']
    ]);
    exit;
}

// =========================================================================
// DIRECT FAQ & DOUBT RESOLVER (Exact Specific Answers to Follow-Up Pills & Doubts)
// =========================================================================

$direct_faqs = [
    // 1. Base Rate vs Extra Guest Rate
    'base_vs_extra' => [
        'keywords' => [
            'base rate', 'extra guest', 'extra child', 'difference between base',
            'ബേസ് റേറ്റ്', 'എക്സ്ട്രാ ഗസ്റ്റ്', 'വ്യത്യാസം', 'അധിക അതിഥി', 'base ratum', 'extra guest ratum',
            'அடிப்படை கட்டணம்', 'கூடுதல் விருந்தினர்', 'வித்தியாசம்'
        ],
        'topic' => 'rooms',
        'en' => [
            'badge' => 'Tariff Clarification',
            'title' => 'Difference Between Base Rate and Extra Guest Rate',
            'answer' => "<p><strong>Base Rate per Night:</strong> This covers the standard room occupancy up to the specified <em>Base Guests</em> limit (typically 2 adults).</p>
                         <p><strong>Extra Adult / Child Rate:</strong> If a booking exceeds the base count (e.g., 3 adults staying in a villa), the system charges: <code>Base Rate + (1 × Extra Adult Rate)</code> per night. Extra Child rates only apply if children exceed the room base allowance.</p>
                         <p>This allows couples and families to be priced fairly for the exact same cottage without configuring duplicate rooms.</p>",
            'direct_label' => 'Open Rooms & Tariffs',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "Will changing room rates affect existing bookings?",
                "Can I set special rates for weekends on the Calendar?",
                "How do I upload a 360° virtual tour panorama?"
            ]
        ],
        'ml' => [
            'badge' => 'നിരക്ക് സംശയ നിവാരണം',
            'title' => 'ബേസ് റേറ്റും എക്സ്ട്രാ ഗസ്റ്റ് റേറ്റും തമ്മിലുള്ള വ്യത്യാസം',
            'answer' => "<p><strong>ബേസ് റേറ്റ് (Base Rate per Night):</strong> ഇത് സാധാരണയായി മുറിയിൽ അനുവദിച്ചിട്ടുള്ള അടിസ്ഥാന അതിഥികളുടെ എണ്ണത്തിന് (Base Guests - സാധാരണ 2 മുതിർന്നവർ) വരെയുള്ള ദിവസ വാടകയാണ്.</p>
                         <p><strong>എക്സ്ട്രാ ഗസ്റ്റ് റേറ്റ് (Extra Adult / Child Rate):</strong> അനുവദിച്ചതിലും കൂടുതൽ ആളുകൾ താമസിക്കാൻ വരുമ്പോൾ (ഉദാഹരണത്തിന് 3 മുതിർന്നവർ), സിസ്റ്റം സ്വമേധയാ കണക്കാക്കുന്നത്: <code>Base Rate + (1 × Extra Adult Rate)</code> എന്ന രീതിയിലാണ്. അധികം വരുന്ന കുട്ടികൾക്ക് Extra Child Rate മാത്രമേ ഈടാക്കൂ.</p>
                         <p>ഇത് ഒരേ വില്ലയിൽ ദമ്പതികൾക്കും കുടുംബങ്ങൾക്കും കൃത്യമായ വാടക നിശ്ചയിക്കാൻ സഹായിക്കുന്നു.</p>",
            'direct_label' => 'റൂം വാടക എഡിറ്റർ തുറക്കുക',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "വാടക മാറ്റിയാൽ നിലവിലുള്ള ബുക്കിംഗുകളെ ബാധിക്കുമോ?",
                "വാരാന്ത്യങ്ങൾക്ക് പ്രത്യേക വാടക കലണ്ടറിൽ എങ്ങനെ നൽകാം?",
                "360° വെർച്വൽ പനോരമ എങ്ങനെ അപ്‌ലോഡ് ചെയ്യാം?"
            ]
        ],
        'ta' => [
            'badge' => 'கட்டண சந்தேகம்',
            'title' => 'அடிப்படை கட்டணம் மற்றும் கூடுதல் விருந்தினர் கட்டணத்தின் வித்தியாசம்',
            'answer' => "<p><strong>அடிப்படை கட்டணம் (Base Rate):</strong> இது பொதுவாக 2 பெரியவர்களுக்கான (Base Guests) ஒரு இரவு வாடகை ஆகும்.</p>
                         <p><strong>கூடுதல் விருந்தினர் கட்டணம் (Extra Adult / Child Rate):</strong> 2 நபர்களுக்கு மேல் தங்கும் போது (எ.கா: 3 பெரியவர்கள்), கணினி தானாகவே <code>Base Rate + (1 × Extra Adult Rate)</code> என கணக்கிடும்.</p>",
            'direct_label' => 'அறைகள் & கட்டண எடிட்டரைத் திற',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "வாடகையை மாற்றினால் பழைய முன்பதிவுகள் மாறுமா?",
                "வார இறுதி நாட்களுக்கு தனி கட்டணம் எவ்வாறு அமைப்பது?",
                "360° விர்ச்சுவல் டூர் படத்தை எவ்வாறு பதிவேற்றுவது?"
            ]
        ]
    ],

    // 2. Will changing rates affect existing bookings
    'rate_change_existing' => [
        'keywords' => [
            'affect existing bookings', 'old bookings change', 'existing reservation price',
            'നിലവിലുള്ള ബുക്കിംഗുകളെ ബാധിക്കുമോ', 'പഴയ ബുക്കിംഗ്', 'വാടക മാറ്റിയാൽ',
            'பழைய முன்பதிவுகள் மாறுமா', 'ஏற்கனவே உள்ள முன்பதிவு'
        ],
        'topic' => 'rooms',
        'en' => [
            'badge' => 'Booking Policy',
            'title' => 'Will changing room rates affect existing bookings?',
            'answer' => "<p><strong>No, never!</strong> Changing your room tariffs in Estate Settings or adjusting rates on the calendar applies exclusively to <em>future bookings</em>.</p>
                         <p>Any reservation that has already been confirmed or booked by guests remains permanently locked at its original agreed rate, preserving your legal commitments and accounting accuracy.</p>",
            'direct_label' => 'Open Rooms & Tariffs',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "What is the difference between Base Rate and Extra Guest Rate?",
                "Can I set special rates for weekends on the Calendar?",
                "How do I add a manual phone booking?"
            ]
        ],
        'ml' => [
            'badge' => 'ബുക്കിംഗ് പോളിസി',
            'title' => 'വാടക മാറ്റിയാൽ നിലവിലുള്ള ബുക്കിംഗുകളെ ബാധിക്കുമോ?',
            'answer' => "<p><strong>ഒരിക്കലുമില്ല!</strong> നിങ്ങൾ സെറ്റിംഗ്സിലോ കലണ്ടറിലോ വില്ല വാടക മാറ്റിയാൽ അത് <em>ഭാവിയിൽ വരുന്ന പുതിയ ബുക്കിംഗുകൾക്ക് മാത്രമേ</em> ബാധകമാകൂ.</p>
                         <p>ഇതിനകം സ്ഥിരീകരിച്ചതോ അതിഥികൾ മുൻകൂറായി ബുക്ക് ചെയ്തതോ ആയ ബുക്കിംഗുകളുടെ തുക പഴയ നിരക്കിൽ തന്നെ പൂർണ്ണമായി സുരക്ഷിതമായിരിക്കും. അവരുടെ ബില്ലിലോ വാടകയിലോ യാതൊരു മാറ്റവും ഉണ്ടാകില്ല.</p>",
            'direct_label' => 'റൂം വാടക എഡിറ്റർ തുറക്കുക',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "ബേസ് റേറ്റും എക്സ്ട്രാ ഗസ്റ്റ് റേറ്റും തമ്മിലുള്ള വ്യത്യാസം എന്താണ്?",
                "വാരാന്ത്യങ്ങൾക്ക് പ്രത്യേക വാടക കലണ്ടറിൽ എങ്ങനെ നൽകാം?",
                "നേരിട്ടുള്ള ഫോൺ ബുക്കിംഗ് എങ്ങനെ ചേർക്കാം?"
            ]
        ],
        'ta' => [
            'badge' => 'முன்பதிவு கொள்கை',
            'title' => 'வாடகையை மாற்றினால் பழைய முன்பதிவுகள் மாறுமா?',
            'answer' => "<p><strong>இல்லை, ஒருபோதும் மாறாது!</strong> நீங்கள் புதிய கட்டணங்களை மாற்றினால், அது எதிர்கால முன்பதிவுகளுக்கு மட்டுமே பொருந்தும்.</p>
                         <p>ஏற்கனவே உறுதிசெய்யப்பட்ட முன்பதிவுகள் வாடிக்கையாளர் பதிவு செய்த பழைய கட்டணத்திலேயே பாதுகாப்பாக இருக்கும்.</p>",
            'direct_label' => 'அறைகள் & கட்டண எடிட்டரைத் திற',
            'direct_url' => 'edit_section.php?section=rooms',
            'doubts' => [
                "அடிப்படை கட்டணம் மற்றும் கூடுதல் விருந்தினர் வித்தியாசம் என்ன?",
                "வார இறுதி நாட்களுக்கு தனி கட்டணம் எவ்வாறு அமைப்பது?"
            ]
        ]
    ],

    // 3. Where does QR code appear on guest bill
    'qr_location' => [
        'keywords' => [
            'where does qr code appear', 'qr code on bill', 'scan qr to pay', 'gpay phonepe',
            'ക്യുആർ കോഡ് എവിടെ', 'ബില്ലിൽ ക്യുആർ കോഡ്', 'gpay വഴി അടയ്ക്കാമോ', 'ഫോൺപേ',
            'QR குறியீடு எங்கே தோன்றும்', 'gpay மூலம் செலுத்த முடியுமா'
        ],
        'topic' => 'bank',
        'en' => [
            'badge' => 'Payment & Invoices',
            'title' => 'Where QR Code Appears & Scanning via GPay / PhonePe',
            'answer' => "<p>Your uploaded merchant UPI QR code and Bank IFSC details appear in two prominent guest locations:</p>
                         <ul>
                             <li><strong>Printed A4 Tax Invoices (`billing.php`):</strong> Displayed with your bank details at the bottom right corner of the invoice for easy desktop scanning.</li>
                             <li><strong>Guest Checkout Web Folio:</strong> Displayed on the digital payment screen so guests can scan with GPay, PhonePe, Paytm, BHIM, or any UPI app.</li>
                         </ul>",
            'direct_label' => 'Open Bank & UPI Settings',
            'direct_url' => 'edit_section.php?section=bank&tab=bank',
            'doubts' => [
                "How to set up Bank Account and UPI QR code for billing?",
                "How do I print an A4 bill with the bank details?",
                "Does the system automatically calculate CGST and SGST?"
            ]
        ],
        'ml' => [
            'badge' => 'പേയ്‌മെന്റ് & ഇൻവോയ്സ്',
            'title' => 'ഗസ്റ്റ് ബില്ലിൽ ക്യുആർ കോഡ് എവിടെ കാണാം & GPay/PhonePe പേയ്‌മെന്റ്',
            'answer' => "<p>നിങ്ങൾ അപ്‌ലോഡ് ചെയ്യുന്ന ബാങ്ക് UPI ക്യുആർ കോഡും ബാങ്ക് അക്കൗണ്ട് വിവരങ്ങളും രണ്ട് പ്രധാന സ്ഥലങ്ങളിൽ കാണാം:</p>
                         <ul>
                             <li><strong>ആഡംബര A4 ടാക്സ് ഇൻവോയ്സുകളിൽ:</strong> നിങ്ങൾ അതിഥിക്ക് നൽകുന്ന പ്രിന്റഡ് ബില്ലിന്റെ താഴെ വലതുവശത്ത് ബാങ്ക് അക്കൗണ്ട് വിവരങ്ങൾക്കൊപ്പം വലിയ ക്ലിയർ ക്യുആർ കോഡ് പ്രിന്റ് ചെയ്യപ്പെടുന്നു.</li>
                             <li><strong>ഓൺലൈൻ ചെക്ക്ഔട്ട് പേജിൽ:</strong> അതിഥികളുടെ ഫോണിലോ ഡിജിറ്റൽ ബില്ലിലോ GPay, PhonePe, Paytm, BHIM വഴി സ്കാൻ ചെയ്ത് നേരിട്ട് റിസോർട്ട് അക്കൗണ്ടിലേക്ക് പണമടയ്ക്കാം.</li>
                         </ul>",
            'direct_label' => 'ബാങ്ക് & UPI സെറ്റിംഗ്സ് തുറക്കുക',
            'direct_url' => 'edit_section.php?section=bank&tab=bank',
            'doubts' => [
                "ബാങ്ക് വിവരങ്ങളും UPI QR കോഡും എങ്ങനെ നൽകാം?",
                "ബാങ്ക് വിവരങ്ങൾ അടങ്ങിയ A4 ബിൽ എങ്ങനെ പ്രിന്റ് ചെയ്യാം?",
                "സിസ്റ്റം CGST, SGST തനിയെ കണക്കാക്കുമോ?"
            ]
        ],
        'ta' => [
            'badge' => 'கட்டணம் & பில்லிங்',
            'title' => 'விருந்தினர் பில்லில் QR குறியீடு தோன்றும் இடம்',
            'answer' => "<p>உங்கள் UPI QR குறியீடு அச்சிடப்பட்ட A4 வரி விலைப்பட்டியலின் கீழ் வலது மூலையிலும், விருந்தினர் செக்-அவுட் திரையிலும் தோன்றும். GPay, PhonePe மூலம் உடனடியாக ஸ்கேன் செய்து பணம் செலுத்தலாம்.</p>",
            'direct_label' => 'வங்கி & UPI அமைப்புகளைத் திற',
            'direct_url' => 'edit_section.php?section=bank&tab=bank',
            'doubts' => [
                "வங்கி விவரங்கள் மற்றும் UPI QR குறியீட்டை அமைப்பது எப்படி?",
                "A4 விலைப்பட்டியலை எவ்வாறு அச்சிடுவது?"
            ]
        ]
    ],

    // 4. Airbnb iCal sync automation
    'airbnb_sync_freq' => [
        'keywords' => [
            'how often does airbnb sync', 'ical sync frequency', 'automatic sync airbnb',
            'airbnb സിങ്ക് എത്ര സമയത്തിനുള്ളിൽ', 'കലണ്ടർ തനിയെ സിങ്ക് ആകുമോ',
            'airbnb இணைப்பு எவ்வளவு நேரம்', 'தானியங்கி இணைப்பு'
        ],
        'topic' => 'channel_sync',
        'en' => [
            'badge' => 'OTA Synchronization',
            'title' => 'How Airbnb & OTA iCal Synchronization Works',
            'answer' => "<p><strong>Automatic Background Sync:</strong> The system automatically reads external iCal feeds periodically whenever reservations and calendars are viewed.</p>
                         <p><strong>Instant Manual Sync:</strong> You can click the golden <strong>\"Sync Now\"</strong> button anytime in <code>channel_sync.php</code> to instantly fetch real-time updates from Airbnb and Booking.com.</p>
                         <p>Blocked dates in Food Forest are exported immediately via your unique feed URL so Airbnb blocks them without delay.</p>",
            'direct_label' => 'Open OTA Channel Sync',
            'direct_url' => 'channel_sync.php',
            'doubts' => [
                "How to sync calendar with Airbnb and Booking.com via iCal?",
                "Can I sync with Booking.com and Airbnb at the same time?",
                "What happens if an Airbnb guest cancels?"
            ]
        ],
        'ml' => [
            'badge' => 'OTA കലണ്ടർ സിങ്ക്',
            'title' => 'Airbnb iCal സിങ്ക് എത്ര സമയത്തിനുള്ളിൽ നടക്കും?',
            'answer' => "<p><strong>ഓട്ടോമാറ്റിക് സിങ്ക്:</strong> സിസ്റ്റം തനിയെ ബാക്ക്ഗ്രൗണ്ടിൽ കൃത്യമായ ഇടവേളകളിൽ Airbnb, Booking.com എന്നിവയിലെ മാറ്റങ്ങൾ അപ്ഡേറ്റ് ചെയ്യുന്നു.</p>
                         <p><strong>തത്സമയ സിങ്ക്:</strong> പെട്ടെന്ന് ഒരു ബുക്കിംഗ് വരുമ്പോൾ ഉടൻ തന്നെ അപ്ഡേറ്റ് ലഭിക്കാൻ <code>channel_sync.php</code> പേജിൽ കയറി <strong>\"Sync Now\"</strong> ബട്ടൺ ക്ലിക്ക് ചെയ്യാം.</p>
                         <p>ഇവിടെ കലണ്ടറിൽ തീയതി ബ്ലോക്ക് ചെയ്യുമ്പോൾ തന്നെ Airbnb-ലേക്ക് എക്സ്പോർട്ട് ലിങ്ക് വഴി ആ വിവരം തത്സമയം ലഭിക്കും.</p>",
            'direct_label' => 'ചാനൽ സിങ്ക് തുറക്കുക',
            'direct_url' => 'channel_sync.php',
            'doubts' => [
                "Airbnb കലണ്ടർ എങ്ങനെ iCal വഴി സിങ്ക് ചെയ്യാം?",
                "Booking.com, Airbnb എന്നിവ ഒരേ സമയം സിങ്ക് ചെയ്യാമോ?",
                "Airbnb-ൽ ബുക്കിംഗ് ക്യാൻസൽ ചെയ്താൽ ഇവിടെ തീയതി റിലീസ് ആകുമോ?"
            ]
        ],
        'ta' => [
            'badge' => 'சேனல் இணைப்பு',
            'title' => 'Airbnb காலண்டர் இணைப்பு எவ்வாறு செயல்படுகிறது?',
            'answer' => "<p>கணினி தானாகவே குறிப்பிட்ட கால இடைவெளியில் Airbnb மற்றும் Booking.com தளங்களுடன் இணைகிறது. உடனே புதுப்பிக்க <code>channel_sync.php</code> பக்கத்தில் உள்ள <strong>\"Sync Now\"</strong> பொத்தானை கிளிக் செய்யலாம்.</p>",
            'direct_label' => 'சேனல் இணைப்பைத் திற',
            'direct_url' => 'channel_sync.php',
            'doubts' => [
                "iCal வழியாக Airbnb உடன் இணைப்பது எப்படி?",
                "Booking.com மற்றும் Airbnb இரண்டையும் ஒன்றாக இணைக்க முடியுமா?"
            ]
        ]
    ]
];

// Check if query matches any direct FAQ first!
foreach ($direct_faqs as $faq_key => $faq) {
    $match_faq = false;
    foreach ($faq['keywords'] as $fkw) {
        $fkw_norm = normalize_text($fkw);
        if (empty($fkw_norm)) continue;
        if (mb_strpos($normalized, $fkw_norm, 0, 'UTF-8') !== false) {
            $match_faq = true;
            break;
        }
    }

    if ($match_faq) {
        $fpkg = $faq[$lang] ?? $faq['en'];
        $reply_html = "<div class='adm-bot-answer'>";
        $reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-circle-question'></i> " . htmlspecialchars($fpkg['badge']) . "</div>";
        $reply_html .= "<h4 class='adm-bot-title'>" . htmlspecialchars($fpkg['title']) . "</h4>";
        $reply_html .= "<div class='adm-bot-desc' style='line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary); margin: 12px 0 16px 0;'>";
        $reply_html .= $fpkg['answer'];
        $reply_html .= "</div>";

        if (!empty($fpkg['direct_url']) && !empty($fpkg['direct_label'])) {
            $reply_html .= "<div style='margin-bottom: 14px;'>";
            $reply_html .= "<a href='{$fpkg['direct_url']}' class='adm-btn-action gold' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding: 7px 16px; font-size: 12.5px;'>";
            $reply_html .= "<i class='fa-solid fa-arrow-up-right-from-square'></i> <span>{$fpkg['direct_label']}</span>";
            $reply_html .= "</a>";
            $reply_html .= "</div>";
        }
        $reply_html .= "</div>";

        echo json_encode([
            'success' => true,
            'lang' => $lang,
            'topic' => $faq['topic'] ?? '',
            'reply' => $reply_html,
            'follow_up_doubts' => $fpkg['doubts'] ?? []
        ]);
        exit;
    }
}

// =========================================================================
// MASTER KNOWLEDGE BASE FOR FOOD FOREST ADMIN PANEL (ALL 20 TOPICS)
// =========================================================================

$kb = [
    // 1. ROOMS, VILLAS & TARIFFS
    'rooms' => [
        'id' => 'rooms',
        'direct_url' => 'edit_section.php?section=rooms',
        'keywords' => [
            'room', 'rooms', 'villa', 'villas', 'cottage', 'cottages', 'treehouse', 'mudhouse',
            'tariff', 'tariffs', 'price', 'pricing', 'rate', 'rates', 'rent', 'occupancy', 'guest',
            'base rate', 'extra guest', 'extra child', '360', 'panorama', 'tour', 'photos', 'amenities',
            // Malayalam & Manglish
            'റൂം', 'റൂമുകൾ', 'റൂമുകളുടെ', 'മുറി', 'മുറികൾ', 'മുറിയുടെ', 'വില്ല', 'വില്ലകൾ', 'കോട്ടേജ്',
            'വാടക', 'വില', 'നിരക്ക്', 'താമസം', 'എക്സ്ട്രാ ഗസ്റ്റ്', 'പനോരമ', 'ഫോട്ടോകൾ', 'റൂം വാടക മാറ്റുക',
            'റൂം റേറ്റ്', 'വില്ല വാടക', 'vaadaka', 'vila', 'vilayil', 'room rate engane', 'muri vadaka',
            // Tamil & Tanglish
            'அறை', 'அறைகள்', 'வில்லா', 'வில்லாக்கள்', 'காட்டேஜ்', 'வாடகை', 'விலை', 'கட்டணம்',
            'கூடுதல் விருந்தினர்', 'பனோரமா', 'புகைப்படங்கள்', 'அறை வாடகை மாற்றம்', 'arai', 'vadagai', 'kattanam'
        ],
        'en' => [
            'title' => 'Villas, Cottages, Rates & 360° Tours',
            'card' => 'Card 04 (Villas & Stays)',
            'direct_label' => 'Open Rooms & Tariffs Editor',
            'summary' => 'Configure Villa descriptions, base nightly tariffs, extra person charges, capacity, amenities, 360° virtual tours, and photo galleries in **Card 04 (Villas & Stays)**.',
            'steps' => [
                '1' => 'Click on **Estate Settings** in the left sidebar menu, or navigate directly to **Card 04 (Villas & Stays)** in the Settings Hub.',
                '2' => 'Choose the villa you want to edit (e.g. **Alpine Wood House** or **Earthen Mudhouse**) from the tabs at the top of the editor.',
                '3' => 'Under **Basic Tariffs & Occupancy**, adjust:<br>• **Base Rate per Night (₹)**: Standard rate for standard guest count.<br>• **Base Guests**: Number of guests covered in the base rate (usually 2).<br>• **Max Guests**: Maximum capacity of the villa.<br>• **Extra Adult Rate (₹)**: Per-night charge for each additional adult.<br>• **Extra Child Rate (₹)**: Per-night charge for additional children.',
                '4' => 'Under **Media & 360° Panoramic Tour**, upload fresh photos or set the 360° panorama image for the interactive virtual tour.',
                '5' => 'Click the golden **"Save Changes"** button at the bottom. Once saved, click **"Purge Cache"** in the topbar to ensure guests see the updated rates instantly!'
            ],
            'tips' => [
                '💡 **Pro Tip**: If you want to temporarily increase prices for a holiday weekend, you can also use the **Interactive Calendar** (`calendar.php`) to set custom rate overrides for specific dates without changing the base rate!',
                '⚠️ **Note**: Changing base rates applies to all *future bookings*. Any reservations already confirmed will retain their original booked price.'
            ],
            'step_doubts' => [
                '1' => 'You can access this by clicking "Estate Settings" on the left menu, then clicking the 4th card titled "04 • Villas, Cottages & Stays". Or use the direct link button above.',
                '2' => 'Each villa has its own independent tab in the editor so you can manage their pricing, photos, and descriptions separately.',
                '3' => 'Base Rate applies up to Base Guests (usually 2 adults). Extra adults pay the Extra Adult Rate per night.',
                '4' => 'Upload standard landscape photos (16:9 ratio) and an equirectangular JPEG panorama for 360° virtual tours.',
                '5' => 'Always purge cache after saving so that website visitors get fresh prices immediately.'
            ],
            'follow_up_doubts' => [
                "What is the difference between Base Rate and Extra Guest Rate?",
                "Will changing room rates affect existing bookings?",
                "How do I upload a 360° virtual tour panorama?",
                "Can I set special rates for weekends on the Calendar?"
            ]
        ],
        'ml' => [
            'title' => 'വില്ലകൾ, കോട്ടേജുകൾ, വാടക നിരക്കുകൾ & 360° ടൂറുകൾ',
            'card' => 'കാർഡ് 04 (വില്ലകളും താമസവും)',
            'direct_label' => 'റൂം & വാടക എഡിറ്റർ തുറക്കുക',
            'summary' => 'വില്ല പേരുകൾ, വിവരണം, അടിസ്ഥാന ദിവസ വാടക, അധിക അതിഥി നിരക്കുകൾ, സൗകര്യങ്ങൾ, 360° വെർച്വൽ പനോരമ ടൂറുകൾ എന്നിവ **Card 04 (Villas & Stays)**-ൽ എഡിറ്റ് ചെയ്യാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Estate Settings** ക്ലിക്ക് ചെയ്യുക, അല്ലെങ്കിൽ സെറ്റിംഗ്സ് ഹബ്ബിലെ **Card 04 (Villas & Stays)** തുറക്കുക.',
                '2' => 'മുകളിലുള്ള ടാബുകളിൽ നിന്ന് മാറ്റം വരുത്തേണ്ട വില്ല തിരഞ്ഞെടുക്കുക (ഉദാ: **Alpine Wood House** അല്ലെങ്കിൽ **Earthen Mudhouse**).',
                '3' => '<strong>Basic Tariffs & Occupancy</strong> എന്നതിന് കീഴിൽ നിരക്കുകൾ നൽകുക:<br>• <strong>Base Rate per Night (₹)</strong>: അടിസ്ഥാന ദിവസ വാടക.<br>• <strong>Base Guests</strong>: അടിസ്ഥാന നിരക്കിൽ ഉൾപ്പെടുന്ന അതിഥികളുടെ എണ്ണം (സാധാരണ 2 പേർ).<br>• <strong>Max Guests</strong>: പരമാവധി താമസിക്കാവുന്ന അതിഥികൾ.<br>• <strong>Extra Adult Rate (₹)</strong>: അധികം വരുന്ന ഓരോ മുതിർന്ന ആൾക്കുമുള്ള നിരക്ക്.<br>• <strong>Extra Child Rate (₹)</strong>: അധികം വരുന്ന കുട്ടികൾക്കുള്ള നിരക്ക്.',
                '4' => '<strong>Media & 360° Panoramic Tour</strong> എന്നതിന് കീഴിൽ പുതിയ ഫോട്ടോകളും 360° പനോരമ ഇമേജും അപ്‌ലോഡ് ചെയ്യാം.',
                '5' => 'താഴെയുള്ള ഗോൾഡൻ **"Save Changes"** ബട്ടൺ അമർത്തുക. ശേഷം പുതിയ നിരക്കുകൾ വെബ്‌സൈറ്റിൽ തത്സമയം ലഭ്യമാകാൻ ടോപ്പ്ബാറിലെ **"Purge Cache"** ക്ലിക്ക് ചെയ്യുക!'
            ],
            'tips' => [
                '💡 **പ്രത്യേക നിർദ്ദേശം**: വാരാന്ത്യങ്ങളിലോ അവധി ദിവസങ്ങളിലോ താൽക്കാലികമായി നിരക്ക് കൂട്ടാൻ **Booking Calendar** (`calendar.php`) വഴി ബേസ് റേറ്റ് മാറ്റാതെ തന്നെ കസ്റ്റം റേറ്റ് നൽകാം!',
                '⚠️ **ശ്രദ്ധിക്കുക**: പുതിയ നിരക്കുകൾ ഭാവിയിലെ ബുക്കിംഗുകൾക്ക് മാത്രമേ ബാധകമാകൂ. നിലവിൽ സ്ഥിരീകരിച്ച ബുക്കിംഗുകളുടെ തുക പഴയ നിരക്കിൽ തന്നെ തുടരും.'
            ],
            'step_doubts' => [
                '1' => 'ഇടത് മെനുവിലെ "Estate Settings" ക്ലിക്ക് ചെയ്ത് 4-ാമത്തെ കാർഡിൽ ക്ലിക്ക് ചെയ്യുക, അല്ലെങ്കിൽ മുകളിലെ ബട്ടൺ ഉപയോഗിക്കുക.',
                '2' => 'ഓരോ വില്ലയ്ക്കും പ്രത്യേക ടാബുകൾ ഉള്ളതിനാൽ ചിത്രങ്ങളും നിരക്കുകളും വെവ്വേറെ മാറ്റാൻ കഴിയും.',
                '3' => 'ബേസ് റേറ്റ് 2 പേർക്ക് വരെ ബാധകമാണ്. 3 മുതിർന്നവർ ബുക്ക് ചെയ്താൽ സിസ്റ്റം തനിയെ: Base Rate + Extra Adult Rate കണക്കാക്കും.',
                '4' => '360° ടൂറിനായി ഇക്വിറെക്ടാംഗുലർ പനോരമ (JPEG) ചിത്രം അപ്‌ലോഡ് ചെയ്യുക.',
                '5' => 'സേവ് ചെയ്ത ശേഷം കാഷെ പർജ്ജ് ചെയ്താൽ മാത്രമേ വെബ്‌സൈറ്റ് സന്ദർശകർക്ക് പുതിയ നിരക്ക് ഉടൻ കാണാനാകൂ.'
            ],
            'follow_up_doubts' => [
                "ബേസ് റേറ്റും എക്സ്ട്രാ ഗസ്റ്റ് റേറ്റും തമ്മിലുള്ള വ്യത്യാസം എന്താണ്?",
                "വാടക മാറ്റിയാൽ നിലവിലുള്ള ബുക്കിംഗുകളെ ബാധിക്കുമോ?",
                "360° വെർച്വൽ പനോരമ എങ്ങനെ അപ്‌ലോഡ് ചെയ്യാം?",
                "വാരാന്ത്യങ്ങൾക്ക് പ്രത്യേക വാടക കലണ്ടറിൽ എങ്ങനെ നൽകാം?"
            ]
        ],
        'ta' => [
            'title' => 'வில்லாக்கள், காட்டேஜ்கள், வாடகை & 360° சுற்றுப்பயணம்',
            'card' => 'கார்டு 04 (வில்லாக்கள் & தங்குமிடம்)',
            'direct_label' => 'அறைகள் & கட்டண எடிட்டரைத் திற',
            'summary' => 'வில்லா பெயர்கள், விளக்கங்கள், அடிப்படை இரவு வாடகை, கூடுதல் நபர் கட்டணம், வசதிகள், 360° விர்ச்சுவல் டூர் மற்றும் புகைப்படங்களை **Card 04 (Villas & Stays)**-ல் அமைக்கலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Estate Settings**-ஐ கிளிக் செய்யவும், அல்லது செட்டிங்ஸ் மையத்தில் உள்ள **Card 04 (Villas & Stays)**-ஐ திறக்கவும்.',
                '2' => 'மேல் உள்ள தாவல்களில் இருந்து திருத்த வேண்டிய வில்லாவைத் தேர்ந்தெடுக்கவும் (எ.கா: **Alpine Wood House** அல்லது **Earthen Mudhouse**).',
                '3' => '<strong>Basic Tariffs & Occupancy</strong> பிரிவின் கீழ் கட்டணங்களை மாற்றவும்:<br>• <strong>Base Rate per Night (₹)</strong>: அடிப்படை இரவு வாடகை.<br>• <strong>Base Guests</strong>: அடிப்படை கட்டணத்தில் தங்கும் நபர்கள் (பொதுவாக 2 பேர்).<br>• <strong>Max Guests</strong>: அதிகபட்ச விருந்தினர்கள்.<br>• <strong>Extra Adult Rate (₹)</strong>: கூடுதல் பெரியவர்களுக்கான ஒரு இரவு கட்டணம்.<br>• <strong>Extra Child Rate (₹)</strong>: கூடுதல் குழந்தைகளுக்கான கட்டணம்.',
                '4' => '<strong>Media & 360° Panoramic Tour</strong> பிரிவின் கீழ் புதிய புகைப்படங்கள் மற்றும் 360° விர்ச்சுவல் டூர் படத்தை பதிவேற்றலாம்.',
                '5' => 'கீழே உள்ள தங்க நிற **"Save Changes"** பொத்தானை கிளிக் செய்யவும். பின்னர் புதிய கட்டணங்கள் இணையதளத்தில் உடனே தெரிய மேலே உள்ள **"Purge Cache"**-ஐ அழுத்தவும்!'
            ],
            'tips' => [
                '💡 **குறிப்பு**: விடுமுறை நாட்களில் மட்டும் கட்டணத்தை உயர்த்த **Booking Calendar** வழியாக அடிப்படை கட்டணத்தை மாற்றாமல் சிறப்பு கட்டணம் நிர்ணயிக்கலாம்!',
                '⚠️ **கவனம்**: புதிய கட்டணங்கள் எதிர்கால முன்பதிவுகளுக்கு மட்டுமே பொருந்தும்.'
            ],
            'step_doubts' => [
                '1' => 'இடது மெனுவில் "Estate Settings" கிளிக் செய்து, 4வது கார்டான "Villas & Stays" திறக்கவும்.',
                '3' => 'அடிப்படை கட்டணம் 2 நபர்களுக்கு பொருந்தும். 3 பேர் தங்கினால், கணினி தானாகவே Base Rate + Extra Adult Rate என கணக்கிடும்.'
            ],
            'follow_up_doubts' => [
                "அடிப்படை கட்டணம் மற்றும் கூடுதல் விருந்தினர் கட்டணத்தின் வித்தியாசம் என்ன?",
                "வாடகையை மாற்றினால் பழைய முன்பதிவுகள் மாறுமா?",
                "360° விர்ச்சுவல் டூர் படத்தை எவ்வாறு பதிவேற்றுவது?"
            ]
        ]
    ],

    // 2. BANK & UPI QR CODE
    'bank' => [
        'id' => 'bank',
        'direct_url' => 'edit_section.php?section=bank&tab=bank',
        'keywords' => [
            'bank', 'bank account', 'account number', 'ifsc', 'branch', 'upi', 'upi id', 'qr code', 'payment qr',
            'gpay', 'phonepe', 'paytm', 'bhim', 'payment', 'transfer', 'qr', 'online payment',
            // Malayalam & Manglish
            'ബാങ്ക്', 'ബാങ്ക് അക്കൗണ്ട്', 'അക്കൗണ്ട് നമ്പർ', 'ഐഎഫ്എസ്സി', 'യുപിഐ', 'ക്യുആർ', 'ക്യുആർ കോഡ്',
            'ഗൂഗിൾ പേ', 'ഫോൺപേ', 'പേയ്‌മെന്റ്', 'പണം അടയ്ക്കാൻ', 'ബാങ്ക് വിവരങ്ങൾ', 'യുപിഐ ഐഡി',
            'bank vivarangal', 'upi engane kodukkam', 'qr code upload cheyyaan', 'account number',
            // Tamil & Tanglish
            'வங்கி', 'வங்கி கணக்கு', 'கணக்கு எண்', 'ஐஎப்எஸ்சி', 'யூபிஐ', 'க்யூஆர்', 'க்யூஆர் குறியீடு',
            'கூகுள் பே', 'போன்பே', 'கட்டணம்', 'வங்கி விவரங்கள்', 'பணம் செலுத்துதல்', 'bank kanaku'
        ],
        'en' => [
            'title' => 'Bank Account & UPI QR Code Setup',
            'card' => 'Card 02 (Bank & Payment Gateways)',
            'direct_label' => 'Open Bank & UPI Settings',
            'summary' => 'Configure Sanctuary Bank Account details and upload your official UPI QR Code for guest billing folios in **Card 02**.',
            'steps' => [
                '1' => 'Click on **Estate Settings** in the left sidebar menu, then select **Card 02 (Bank & Payment Gateways)**.',
                '2' => 'Under **Bank Account Details**, fill in your official **Beneficiary Name**, **Account Number**, **IFSC Code**, and **Branch**.',
                '3' => 'Under **Instant UPI QR Code Configuration**, enter your business **UPI ID / VPA** (e.g. `foodforest@okhdfcbank`).',
                '4' => 'Click **"Upload QR Image"** to choose your official merchant UPI QR code image (JPG or PNG).',
                '5' => 'Click **"Save Bank & Payment Settings"**.'
            ],
            'tips' => [
                '💡 Your bank details and UPI QR code are automatically rendered on printed **A4 Tax Invoices** and checkout folios!'
            ],
            'step_doubts' => [
                '2' => 'Double check your IFSC code so guests do not encounter NEFT/RTGS transaction failures.'
            ],
            'follow_up_doubts' => [
                "Where does the QR code appear on the guest bill?",
                "Can guests scan the QR code to pay via GPay or PhonePe?",
                "What if I need to change the bank account later?",
                "How do I print an A4 bill with the bank details?"
            ]
        ],
        'ml' => [
            'title' => 'ബാങ്ക് അക്കൗണ്ട് & UPI QR കോഡ് സെറ്റപ്പ്',
            'card' => 'കാർഡ് 02 (ബാങ്ക് & പേയ്‌മെന്റ് ഗേറ്റ്‌വേ)',
            'direct_label' => 'ബാങ്ക് & UPI സെറ്റിംഗ്സ് തുറക്കുക',
            'summary' => 'അതിഥികളുടെ ബില്ലിംഗിനും ചെക്ക്ഔട്ടിനും ആവശ്യമായ ബാങ്ക് അക്കൗണ്ട് വിവരങ്ങളും (Beneficiary, Account No, IFSC) ഒഫീഷ്യൽ UPI QR കോഡും **Card 02**-ൽ സെറ്റ് ചെയ്യാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Estate Settings** ക്ലിക്ക് ചെയ്ത് **Card 02 (Bank & Payment Gateways)** തുറക്കുക.',
                '2' => '<strong>Bank Account Details</strong> എന്നതിന് കീഴിൽ അക്കൗണ്ട് ഉടമയുടെ പേര്, അക്കൗണ്ട് നമ്പർ, IFSC കോഡ്, ബാങ്ക് പേര്, ബ്രാഞ്ച് എന്നിവ നൽകുക.',
                '3' => '<strong>Instant UPI QR Code Configuration</strong> എന്നതിൽ നിങ്ങളുടെ റിസോർട്ട് **UPI ID** നൽകുക (ഉദാ: `foodforest@okhdfcbank`).',
                '4' => '<strong>"Upload QR Image"</strong> ക്ലിക്ക് ചെയ്ത് നിങ്ങളുടെ ബാങ്ക് UPI ക്യുആർ കോഡിന്റെ ചിത്രം (JPG/PNG) അപ്‌ലോഡ് ചെയ്യുക.',
                '5' => 'താഴെയുള്ള **"Save Bank & Payment Settings"** ബട്ടൺ അമർത്തുക. ഇപ്പോൾ അതിഥികളുടെ ബില്ലിലും ചെക്ക്ഔട്ട് പേജിലും സ്കാൻ ചെയ്യാവുന്ന QR കോഡ് ലഭ്യമാകും!'
            ],
            'tips' => [
                '💡 സേവ് ചെയ്ത ബാങ്ക് വിവരങ്ങളും ക്ലിയർ ക്യുആർ കോഡും അതിഥികൾക്ക് നൽകുന്ന **A4 ടാക്സ് ഇൻവോയ്സുകളിൽ** ഓട്ടോമാറ്റിക്കായി പ്രിന്റ് ചെയ്യപ്പെടും!'
            ],
            'step_doubts' => [
                '2' => 'IFSC കോഡ് കൃത്യമായി നൽകിയാൽ അതിഥികൾക്ക് നെഫ്റ്റ്/ഐഎംപിഎസ് ട്രാൻസ്ഫർ എളുപ്പമാകും.'
            ],
            'follow_up_doubts' => [
                "ഗസ്റ്റ് ബില്ലിൽ ക്യുആർ കോഡ് എവിടെയാണ് കാണിക്കുക?",
                "അതിഥികൾക്ക് GPay അല്ലെങ്കിൽ PhonePe വഴി സ്കാൻ ചെയ്ത് പണമടയ്ക്കാമോ?",
                "പിന്നീട് ബാങ്ക് അക്കൗണ്ട് മാറ്റാൻ സാധിക്കുമോ?",
                "ബാങ്ക് വിവരങ്ങൾ അടങ്ങിയ A4 ബിൽ എങ്ങനെ പ്രിന്റ് ചെയ്യാം?"
            ]
        ],
        'ta' => [
            'title' => 'வங்கி கணக்கு & UPI QR குறியீடு அமைப்பு',
            'card' => 'கார்டு 02 (வங்கி & கட்டண வழிகள்)',
            'direct_label' => 'வங்கி & UPI அமைப்புகளைத் திற',
            'summary' => 'விருந்தினர் பில்லிங்கிற்கான வங்கி கணக்கு விவரங்கள் மற்றும் UPI QR குறியீட்டை **Card 02**-ல் அமைக்கலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Estate Settings** சென்று, **Card 02**-ஐ திறக்கவும்.',
                '2' => 'கணக்கு பெயர், கணக்கு எண், IFSC குறியீடு, வங்கி பெயர் விவரங்களை உள்ளிடவும்.',
                '3' => 'உங்கள் ரிசார்ட்டின் **UPI ID** உள்ளிடவும்.',
                '4' => 'தெளிவான வணிக UPI QR குறியீட்டு படத்தை பதிவேற்றவும்.',
                '5' => 'கீழே உள்ள **"Save Bank & Payment Settings"** பொத்தானை கிளிக் செய்யவும்.'
            ],
            'tips' => [
                '💡 வங்கி விவரங்கள் மற்றும் QR குறியீடு A4 வரி விலைப்பட்டியலில் தானாக அச்சிடப்படும்.'
            ],
            'step_doubts' => [
                '2' => 'IFSC குறியீட்டை இருமுறை சரிபார்க்கவும்.'
            ],
            'follow_up_doubts' => [
                "விருந்தினர் பில்லில் QR குறியீடு எங்கே தோன்றும்?",
                "GPay அல்லது PhonePe மூலம் ஸ்கேன் செய்து பணம் செலுத்த முடியுமா?"
            ]
        ]
    ],

    // 3. GST MULTI-TIER TAXES
    'gst' => [
        'id' => 'gst',
        'direct_url' => 'edit_section.php?section=bank&tab=gst',
        'keywords' => [
            'gst', 'tax', 'taxes', 'gstin', 'tax rate', 'cgst', 'sgst', 'food tax', 'room tax',
            'ജിഎസ്ടി', 'നികുതി', 'നികുതി നിരക്ക്', 'ടാക്സ്', 'ജിഎസ്ടി നമ്പർ', 'സിജിഎസ്ടി', 'എസ്ജിഎസ്ടി', 'ഫുഡ് ടാക്സ്', 'റൂം ടാക്സ്',
            'gst engane kodukkam', 'nikuthi', 'tax change cheyyaan', 'vari', 'vari kattanam', 'ஜிஎஸ்டி', 'வரி'
        ],
        'en' => [
            'title' => 'Multi-Tier GST Tax Rates & GSTIN Setup',
            'card' => 'Card 03 (GST & Invoicing)',
            'direct_label' => 'Open GST & Invoicing Settings',
            'summary' => 'Configure your 15-digit GSTIN, Room Stay GST (12% or 18%), and Food/Restaurant GST (5%) with automatic CGST and SGST splits in **Card 03**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and select **Card 03 (GST & Invoicing)**.',
                '2' => 'Enter your 15-character **GSTIN Number**.',
                '3' => 'Set **Room Stay GST (%)** (Standard is 12% for ₹1,001–₹7,500; 18% above ₹7,500).',
                '4' => 'Set **Food & Restaurant GST (%)** (Standard composition is 5%).',
                '5' => 'Click **"Save GST Settings"**.'
            ],
            'tips' => ['💡 Invoices automatically split taxes into 50% CGST and 50% SGST for intrastate guests.'],
            'step_doubts' => ['3' => 'Enter 0% if unregistered or tax-exempt.'],
            'follow_up_doubts' => [
                "Does the system automatically calculate CGST and SGST?",
                "Can I set 0% GST if unregistered?",
                "How are food orders taxed differently from villa stays?"
            ]
        ],
        'ml' => [
            'title' => 'മൾട്ടി-ടയർ ജിഎസ്ടി നികുതി നിരക്കുകൾ & GSTIN',
            'card' => 'കാർഡ് 03 (ജിഎസ്ടി & ഇൻവോയ്സിംഗ്)',
            'direct_label' => 'ജിഎസ്ടി സെറ്റിംഗ്സ് തുറക്കുക',
            'summary' => 'നിങ്ങളുടെ 15 അക്ക GSTIN നമ്പർ, റൂം താമസത്തിനുള്ള ജിഎസ്ടി (12%), ഭക്ഷണത്തിനുള്ള ജിഎസ്ടി (5%) എന്നിവ **Card 03**-ൽ ക്രമീകരിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് **Card 03 (GST & Invoicing)** തിരഞ്ഞെടുക്കുക.',
                '2' => 'നിങ്ങളുടെ 15 അക്ക **GSTIN നമ്പർ** നൽകുക (ഉദാ: `32AAAAA0000A1Z5`).',
                '3' => '**Room Stay GST (%)** നൽകുക (സാധാരണയായി 12% അല്ലെങ്കിൽ 18%).',
                '4' => '**Food & Restaurant GST (%)** നൽകുക (സാധാരണ റെസ്റ്റോറന്റ് നിരക്ക് 5%).',
                '5' => '**"Save GST Settings"** ക്ലിക്ക് ചെയ്യുക. എല്ലാ പുതിയ ബുക്കിംഗുകൾക്കും ഭക്ഷണ ഓർഡറുകൾക്കും ഈ നികുതി കൃത്യമായി കണക്കാക്കപ്പെടും.'
            ],
            'tips' => ['💡 ബില്ലുകളിൽ നികുതി തുല്യമായി CGST (പകുതി), SGST (പകുതി) എന്ന് സ്വമേധയാ വിഭജിക്കപ്പെടും.'],
            'step_doubts' => ['3' => 'നിങ്ങൾക്ക് ജിഎസ്ടി രജിസ്ട്രേഷൻ ഇല്ലെങ്കിൽ 0% നൽകാം.'],
            'follow_up_doubts' => [
                "സിസ്റ്റം CGST, SGST തനിയെ കണക്കാക്കുമോ?",
                "ജിഎസ്ടി ഇല്ലെങ്കിൽ 0% വെക്കാൻ സാധിക്കുമോ?",
                "റൂം വാടകയ്ക്കും ഭക്ഷണത്തിനും വ്യത്യസ്ത നികുതിയാണോ?"
            ]
        ],
        'ta' => [
            'title' => 'மல்டி-டயர் ஜிஎஸ்டி வரி கட்டமைப்பு & GSTIN',
            'card' => 'கார்டு 03 (ஜிஎஸ்டி & விலைப்பட்டியல்)',
            'direct_label' => 'ஜிஎஸ்டி அமைப்புகளைத் திற',
            'summary' => '15 இலக்க GSTIN எண், அறை ஜிஎஸ்டி (12%), உணவு ஜிஎஸ்டி (5%) ஆகியவற்றை **Card 03**-ல் அமைக்கலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று **Card 03**-ஐ திறக்கவும்.',
                '2' => 'உங்கள் 15 இலக்க **GSTIN எண்**-ஐ உள்ளிடவும்.',
                '3' => '**Room Stay GST (%)** மற்றும் **Food GST (%)** அமைக்கவும்.',
                '4' => '**"Save GST Settings"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 விலைப்பட்டியலில் CGST மற்றும் SGST என தானாகவே சமமாக பிரிக்கப்படும்.'],
            'step_doubts' => ['3' => 'பதிவு செய்யப்படவில்லை என்றால் 0% என அமைக்கலாம்.'],
            'follow_up_doubts' => [
                "CGST மற்றும் SGST தானாக கணக்கிடப்படுமா?",
                "ஜிஎஸ்டி இல்லையெனில் 0% வைக்க முடியுமா?"
            ]
        ]
    ],

    // 4. FOOD ORDERING SWITCH (IN-COTTAGE TOGGLE)
    'food_toggle' => [
        'id' => 'food_toggle',
        'direct_url' => 'kitchen.php',
        'keywords' => [
            'food switch', 'food ordering', 'enable food', 'disable food', 'turn off food', 'kitchen switch',
            'cottage food ordering', 'guest food order', 'ordering open', 'ordering closed',
            'ഫുഡ് സ്വിച്ച്', 'ഫുഡ് ഓർഡറിംഗ്', 'ഭക്ഷണം ഓർഡർ', 'കിച്ചൻ സ്വിച്ച്', 'ഓർഡർ ഓഫാക്കുക', 'ഓർഡർ നിർത്തുക',
            'food ordering off aakkan', 'kitchen off cheyyaan', 'unavu order nirutha', 'உணவு ஆர்டர் சுவிட்ச்'
        ],
        'en' => [
            'title' => 'In-Cottage Food Ordering Master Switch',
            'card' => 'Kitchen Operations Console',
            'direct_label' => 'Open Kitchen Console & Switch',
            'summary' => 'Instantly enable or disable in-cottage digital food ordering for resident guests via the master switch at the top of the **Kitchen Page**.',
            'steps' => [
                '1' => 'Click on **Kitchen** in the left sidebar menu (or open `kitchen.php`).',
                '2' => 'Locate the prominent **"Guest In-Room Food Ordering"** toggle switch in the top banner.',
                '3' => 'Toggle it **OFF** when the kitchen is closed for the night or overwhelmed with peak capacity.',
                '4' => 'Toggle it **ON** during breakfast, lunch, or dinner hours to accept guest room-service orders.'
            ],
            'tips' => ['💡 When toggled OFF, guests see a polite banner: *"Kitchen service is resting. Breakfast ordering begins at 07:30 AM."*'],
            'step_doubts' => ['2' => 'The toggle updates live in real-time without requiring a page refresh.'],
            'follow_up_doubts' => [
                "What message do guests see when ordering is switched off?",
                "Can guests still view the food menu prices when closed?",
                "Does turning off ordering affect existing pending orders?"
            ]
        ],
        'ml' => [
            'title' => 'കോട്ടേജ് ഫുഡ് ഓർഡറിംഗ് മാസ്റ്റർ സ്വിച്ച്',
            'card' => 'കിച്ചൻ കൺട്രോൾ കൺസോൾ',
            'direct_label' => 'കിച്ചൻ കൺസോൾ തുറക്കുക',
            'summary' => 'അതിഥികൾക്ക് കോട്ടേജിൽ നിന്നുള്ള ഓൺലൈൻ റൂം സർവീസ് ഫുഡ് ഓർഡറിംഗ് താൽക്കാലികമായി ഓൺ ചെയ്യാനും ഓഫ് ചെയ്യാനും **Kitchen Page**-ലെ മാസ്റ്റർ സ്വിച്ച് ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Kitchen** പേജ് തുറക്കുക (അല്ലെങ്കിൽ `kitchen.php`).',
                '2' => 'മുകളിലുള്ള ബാനറിൽ കാണുന്ന **"Guest In-Room Food Ordering"** ടോഗിൾ സ്വിച്ച് ശ്രദ്ധിക്കുക.',
                '3' => 'രാത്രി അടുക്കള അടച്ച ശേഷമോ തിരക്ക് കൂടുമ്പോഴോ ഓർഡറുകൾ താൽക്കാലികമായി നിർത്താൻ സ്വിച്ച് **OFF** ആക്കുക.',
                '4' => 'ഭക്ഷണ സമയങ്ങളിൽ അതിഥികൾക്ക് ഓർഡർ ചെയ്യാൻ അനുവദിക്കുന്നതിനായി സ്വിച്ച് **ON** ആക്കുക.'
            ],
            'tips' => ['💡 സ്വിച്ച് OFF ചെയ്യുമ്പോൾ അതിഥികളുടെ ഫോണിൽ *"അടുക്കള സർവീസ് ഇപ്പോൾ വിശ്രമത്തിലാണ്"* എന്ന സന്ദേശം കാണിക്കും.'],
            'step_doubts' => ['2' => 'പേജ് റീലോഡ് ചെയ്യാതെ തന്നെ സ്വിച്ച് തത്സമയം പ്രവർത്തിക്കുന്നു.'],
            'follow_up_doubts' => [
                "ഓർഡറിംഗ് ഓഫ് ചെയ്യുമ്പോൾ അതിഥികൾക്ക് എന്ത് മെസ്സേജ് ആണ് കാണിക്കുക?",
                "ഓഫ് ആയാലും അതിഥികൾക്ക് മെനു വിലകൾ കാണാൻ കഴിയുമോ?",
                "ഇതിനകം നൽകിയ ഓർഡറുകളെ ഇത് ബാധിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'விருந்தினர் அறை உணவு ஆர்டர் மாஸ்டர் சுவிட்ச்',
            'card' => 'சமையலறை கட்டுப்பாட்டு மையம்',
            'direct_label' => 'சமையலறை மையத்தைத் திற',
            'summary' => 'விருந்தினர்களுக்கான அறை உணவு ஆர்டர் வசதியை இயக்க அல்லது முடக்க **Kitchen Page** சுவிட்சை பயன்படுத்தலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Kitchen** பக்கத்தை திறக்கவும்.',
                '2' => 'மேல் பகுதியில் உள்ள **"Guest In-Room Food Ordering"** சுவிட்சை கவனிக்கவும்.',
                '3' => 'ஆர்டர்களை நிறுத்த சுவிட்சை **OFF** செய்யவும்.',
                '4' => 'உணவு நேரங்களில் ஆர்டர்களை ஏற்க சுவிட்சை **ON** செய்யவும்.'
            ],
            'tips' => ['💡 சுவிட்ச் OFF-ல் இருக்கும்போது *"சமையலறை சேவை தற்போது மூடப்பட்டுள்ளது"* என்ற செய்தி தோன்றும்.'],
            'step_doubts' => ['2' => 'பக்கத்தை புதுப்பிக்க தேவையின்றி சுவிட்ச் உடனே செயல்படும்.'],
            'follow_up_doubts' => [
                "ஆர்டர் முடக்கப்பட்டால் விருந்தினர்களுக்கு என்ன செய்தி வரும்?",
                "மூடப்பட்டிருந்தாலும் உணவு பட்டியலை பார்க்க முடியுமா?"
            ]
        ]
    ],

    // 5. FOOD MENU & PRICING
    'food_menu' => [
        'id' => 'food_menu',
        'direct_url' => 'kitchen.php',
        'keywords' => [
            'food menu', 'menu', 'dishes', 'add dish', 'food price', 'breakfast', 'lunch', 'dinner', 'snacks',
            'meal timing', 'veg', 'non-veg', 'edit food',
            'ഫുഡ് മെനു', 'ഭക്ഷണ മെനു', 'വിഭവങ്ങൾ', 'ഭക്ഷണ വില', 'പ്രാതൽ', 'ഉച്ചഭക്ഷണം', 'അത്താഴം', 'ഭക്ഷണ സമയം', 'വെജിറ്റേറിയൻ',
            'food menu change cheyyaan', 'dish rate matuka', 'unavu pattiyal', 'உணவு பட்டியல்', 'உணவு விலை'
        ],
        'en' => [
            'title' => 'Food Menu, Pricing & Meal Slot Timings',
            'card' => 'Kitchen Management Console',
            'direct_label' => 'Open Food Menu Editor',
            'summary' => 'Manage food dishes, descriptions, prices, veg/non-veg tags, availability status, and meal serving times in the **Kitchen Console**.',
            'steps' => [
                '1' => 'Click on **Kitchen** in the left menu, then switch to the **"Food Menu Items"** tab.',
                '2' => 'To add a new dish, click **"+ Add New Dish"** at the top right.',
                '3' => 'Enter Dish Name, Category (Breakfast, Lunch, Dinner, Beverages), Price (₹), and select Veg / Non-Veg.',
                '4' => 'Upload a high-quality food photo.',
                '5' => 'To edit prices or mark a dish out of stock, click the **Edit** icon next to that item and save.'
            ],
            'tips' => ['💡 You can toggle individual dishes "Available" or "Sold Out" instantly with one click!'],
            'step_doubts' => ['3' => 'Price entered is before tax; 5% food GST is applied automatically at checkout.'],
            'follow_up_doubts' => [
                "How do I mark an item as sold out?",
                "Is the 5% restaurant GST added automatically to menu prices?",
                "How do I change breakfast and dinner timings?"
            ]
        ],
        'ml' => [
            'title' => 'ഭക്ഷണ മെനു, നിരക്കുകൾ & സമയക്രമം',
            'card' => 'കിച്ചൻ മാനേജ്മെന്റ് കൺസോൾ',
            'direct_label' => 'ഫുഡ് മെനു എഡിറ്റർ തുറക്കുക',
            'summary' => 'വിഭവങ്ങളുടെ പേരുകൾ, വില, വെജ്/നോൺ-വെജ് ടാഗുകൾ, ലഭ്യത, പ്രഭാതഭക്ഷണം/അത്താഴ സമയങ്ങൾ എന്നിവ **Kitchen Console**-ൽ എഡിറ്റ് ചെയ്യാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിൽ നിന്ന് **Kitchen** പേജ് തുറന്ന് **"Food Menu Items"** ടാബ് തിരഞ്ഞെടുക്കുക.',
                '2' => 'പുതിയ വിഭവം ചേർക്കാൻ മുകളിലെ **"+ Add New Dish"** ക്ലിക്ക് ചെയ്യുക.',
                '3' => 'വിഭവത്തിന്റെ പേര്, വിഭാഗം, വില (₹), വെജ്/നോൺ-വെജ് എന്നിവ നൽകുക.',
                '4' => 'വിഭവത്തിന്റെ ആകർഷകമായ ഫോട്ടോ അപ്‌ലോഡ് ചെയ്യുക.',
                '5' => 'വില മാറ്റാനോ ലഭ്യമല്ലെന്ന് രേഖപ്പെടുത്താനോ വിഭവത്തിന് നേരെയുള്ള **Edit** ഐക്കൺ ക്ലിക്ക് ചെയ്ത് സേവ് ചെയ്യുക.'
            ],
            'tips' => ['💡 ഏതെങ്കിലും വിഭവം തീർന്നുപോയാൽ ഒറ്റ ക്ലിക്കിൽ "Sold Out" ആക്കി മാറ്റാം!'],
            'step_doubts' => ['3' => 'മെനുവിൽ നൽകുന്ന വിലയിൽ 5% ജിഎസ്ടി സിസ്റ്റം ഓട്ടോമാറ്റിക്കായി ബില്ലിൽ ചേർത്തുകൊള്ളും.'],
            'follow_up_doubts' => [
                "ഒരു വിഭവം തീർന്നുപോയാൽ എങ്ങനെ Sold Out ആക്കാം?",
                "ഭക്ഷണ വിലയിൽ 5% ജിഎസ്ടി തനിയെ കൂടുമോ?",
                "പ്രഭാതഭക്ഷണ സമയക്രമം എങ്ങനെ മാറ്റാം?"
            ]
        ],
        'ta' => [
            'title' => 'உணவு பட்டியல், விலை & உணவு நேரம்',
            'card' => 'சமையலறை மேலாண்மை மையம்',
            'direct_label' => 'உணவு மெனு எடிட்டரைத் திற',
            'summary' => 'உணவு வகைகள், விலை, சைவ/அசைவ குறியீடுகள் மற்றும் உணவு நேரங்களை **Kitchen Console**-ல் நிர்வகிக்கலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Kitchen** பக்கத்தில் **"Food Menu Items"** தாவலை தேர்ந்தெடுக்கவும்.',
                '2' => 'புதிய உணவை சேர்க்க **"+ Add New Dish"** பொத்தானை அழுத்தவும்.',
                '3' => 'உணவு பெயர், பிரிவு, விலை (₹), சைவம்/அசைவம் குறிப்பிடவும்.',
                '4' => 'உணவின் புகைப்படத்தை பதிவேற்றவும்.',
                '5' => 'விலை மாற்ற அல்லது உணவை தற்காலிகமாக நிறுத்த **Edit** ஐகானை கிளிக் செய்து சேமிக்கவும்.'
            ],
            'tips' => ['💡 உணவு தீர்ந்துவிட்டால் ஒரே கிளிக்கில் "Sold Out" என மாற்றலாம்!'],
            'step_doubts' => ['3' => 'உணவு விலையுடன் 5% ஜிஎஸ்டி பில்லில் தானாக சேர்க்கப்படும்.'],
            'follow_up_doubts' => [
                "உணவு தீர்ந்துவிட்டால் Sold Out செய்வது எப்படி?",
                "காலை உணவு நேரத்தை மாற்றுவது எப்படி?"
            ]
        ]
    ],

    // 6. BOOKINGS & IN-HOUSE GUESTS
    'bookings' => [
        'id' => 'bookings',
        'direct_url' => 'bookings.php',
        'keywords' => [
            'booking', 'bookings', 'reservation', 'reservations', 'check-in', 'check-out', 'in-house', 'guest',
            'manual booking', 'phone booking', 'stay',
            'ബുക്കിംഗ്', 'ബുക്കിംഗുകൾ', 'താമസം', 'ചെക്ക് ഇൻ', 'ചെക്ക് ഔട്ട്', 'അതിഥി', 'നേരിട്ടുള്ള ബുക്കിംഗ്', 'ഫോൺ ബുക്കിംഗ്',
            'booking engane nokkam', 'check in cheyyaan', 'guest vannu', 'முன்பதிவு', 'செக்-இன்'
        ],
        'en' => [
            'title' => 'Managing Stays, In-House Guests & Check-Ins',
            'card' => 'Reservations Console',
            'direct_label' => 'Open Reservations Hub',
            'summary' => 'Track all upcoming, in-house, and completed bookings, perform check-ins, or record phone/walk-in reservations in **bookings.php**.',
            'steps' => [
                '1' => 'Click **Reservations (Stays)** in the left sidebar menu.',
                '2' => 'View reservations filtered by: All, Pending Confirmation, In-House, and Checked-Out.',
                '3' => 'When guests arrive at the sanctuary, click **"Check-In"** to mark them active in-house.',
                '4' => 'To add a direct phone/walk-in booking, click **"+ New Manual Booking"** at the top right and enter dates, villa, and guest details.',
                '5' => 'On departure, click **"Check-Out"** to finalize the billing folio.'
            ],
            'tips' => ['💡 Marking a booking "In-House" enables the guest to order food directly from their cottage!'],
            'step_doubts' => ['3' => 'Checking in assigns an active stay ID used for room-service orders.'],
            'follow_up_doubts' => [
                "How do I add a manual phone booking?",
                "Can I change the booked villa after confirmation?",
                "How do I cancel a booking and release dates?"
            ]
        ],
        'ml' => [
            'title' => 'ബുക്കിംഗുകൾ, ഇൻ-ഹൗസ് അതിഥികൾ & ചെക്ക്-ഇൻ',
            'card' => 'റിസർവേഷൻസ് കൺസോൾ',
            'direct_label' => 'ബുക്കിംഗ് കൺസോൾ തുറക്കുക',
            'summary' => 'വരാനിരിക്കുന്നതും നിലവിലുള്ളതുമായ ബുക്കിംഗുകൾ കാണാനും, ചെക്ക്-ഇൻ ചെയ്യാനും, നേരിട്ടുള്ള ഫോൺ ബുക്കിംഗുകൾ ചേർക്കാനും **bookings.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Reservations (Stays)** ക്ലിക്ക് ചെയ്യുക.',
                '2' => 'എല്ലാ ബുക്കിംഗുകളും, കൺഫേം ചെയ്തവയും, നിലവിൽ താമസിക്കുന്നവരുടേയും ലിസ്റ്റ് പരിശോധിക്കുക.',
                '3' => 'അതിഥികൾ എത്തുമ്പോൾ സ്റ്റാറ്റസ് **"Check-In"** ക്ലിക്ക് ചെയ്ത് ഇൻ-ഹൗസ് ആക്കുക.',
                '4' => 'പുതിയ ഫോൺ ബുക്കിംഗുകൾ നൽകാൻ മുകളിലെ **"+ New Manual Booking"** ക്ലിക്ക് ചെയ്ത് വിവരങ്ങൾ നൽകുക.',
                '5' => 'അതിഥികൾ മടങ്ങുമ്പോൾ **"Check-Out"** ക്ലിക്ക് ചെയ്ത് ബില്ലിംഗ് പൂർത്തിയാക്കുക.'
            ],
            'tips' => ['💡 അതിഥിയെ "In-House" ആക്കിയാൽ മാത്രമേ അവർക്ക് റൂമിൽ നിന്ന് ഫുഡ് ഓർഡർ ചെയ്യാൻ സാധിക്കൂ!'],
            'step_doubts' => ['3' => 'ചെക്ക്-ഇൻ ചെയ്യുമ്പോൾ അതിഥിക്ക് ഡിജിറ്റൽ റൂം ഓർഡറിംഗിനുള്ള സ്റ്റേ ഐഡി സജീവമാകും.'],
            'follow_up_doubts' => [
                "നേരിട്ടുള്ള ഫോൺ ബുക്കിംഗ് എങ്ങനെ ചേർക്കാം?",
                "ബുക്കിംഗിന് ശേഷം വില്ല മാറ്റാൻ സാധിക്കുമോ?",
                "ബുക്കിംഗ് റദ്ദാക്കി തീയതി റിലീസ് ചെയ്യുന്നത് എങ്ങനെ?"
            ]
        ],
        'ta' => [
            'title' => 'முன்பதிவுகள், விருந்தினர் தங்குமிடம் & செக்-இன்',
            'card' => 'முன்பதிவு மேலாண்மை மையம்',
            'direct_label' => 'முன்பதிவு மையத்தைத் திற',
            'summary' => 'அனைத்து முன்பதிவுகளையும் கண்காணிக்கவும், செக்-இன் செய்யவும் **bookings.php** பயன்படுகிறது.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Reservations (Stays)**-ஐ கிளிக் செய்யவும்.',
                '2' => 'முன்பதிவுகள் மற்றும் தங்கியிருக்கும் விருந்தினர்களை பட்டியலிடலாம்.',
                '3' => 'விருந்தினர் வரும்போது **"Check-In"** செய்து தங்குதலை உறுதிப்படுத்தவும்.',
                '4' => 'புதிய முன்பதிவை உருவாக்க **"+ New Manual Booking"** கிளிக் செய்யவும்.',
                '5' => 'புறப்படும்போது **"Check-Out"** செய்து பில்லை முடிக்கவும்.'
            ],
            'tips' => ['💡 செக்-இன் செய்த பிறகுதான் விருந்தினர்கள் அறையிலிருந்து உணவு ஆர்டர் செய்ய முடியும்!'],
            'step_doubts' => ['3' => 'செக்-இன் செய்வதன் மூலம் டிஜிட்டல் ஆர்டருக்கான தங்குமிடம் செயலில் வைக்கப்படும்.'],
            'follow_up_doubts' => [
                "நேரடி தொலைபேசி முன்பதிவு செய்வது எப்படி?",
                "முன்பதிவுக்குப் பிறகு வில்லாவை மாற்ற முடியுமா?"
            ]
        ]
    ],

    // 7. BILLING & A4 TAX INVOICES
    'billing' => [
        'id' => 'billing',
        'direct_url' => 'billing.php',
        'keywords' => [
            'billing', 'invoice', 'tax invoice', 'a4 bill', 'print bill', 'print invoice', 'folio', 'settlement',
            'ബില്ലിംഗ്', 'ഇൻവോയ്സ്', 'ടാക്സ് ഇൻവോയ്സ്', 'ബിൽ പ്രിന്റ്', 'ബിൽ അടിക്കുക', 'എ4 ഇൻവോയ്സ്', 'ബാക്കി തുക',
            'bill print cheyyaan', 'invoice engane edukkaam', 'பில்லிங்', 'விலைப்பட்டியல்', 'A4 விலைப்பட்டியல்'
        ],
        'en' => [
            'title' => 'Guest Billing Folio & Luxury A4 Invoices',
            'card' => 'Billing & Invoicing Hub',
            'direct_label' => 'Open Billing & Invoices',
            'summary' => 'Consolidate room charges, food room service, custom add-ons, taxes, advance deposits, and generate branded A4 Tax Invoices in **billing.php**.',
            'steps' => [
                '1' => 'Click on **Billing & Invoices** in the left sidebar menu.',
                '2' => 'Select the guest reservation to open their comprehensive Billing Folio.',
                '3' => 'Review itemized charges (Villa Nightly Charges + GST, Kitchen Orders, and Custom Services).',
                '4' => 'Under **Record Payment**, record payments received (Cash, UPI, Card, NEFT) against the balance due.',
                '5' => 'Click **"Print A4 Tax Invoice"** to open a print-ready A4 PDF invoice complete with Sanctuary branding, GST breakdown, and Bank UPI QR code!'
            ],
            'tips' => ['💡 Invoices conform with Indian GST Tax Invoice guidelines and can be printed or saved directly as PDF.'],
            'step_doubts' => ['5' => 'Use browser print options (Ctrl+P) and set Margins to "None" or "Default" with Background Graphics enabled.'],
            'follow_up_doubts' => [
                "How do I add extra charges (e.g. campfire or taxi) to the bill?",
                "Can I give a discount on the final invoice?",
                "How do I download the invoice as a PDF file?"
            ]
        ],
        'ml' => [
            'title' => 'ഗസ്റ്റ് ബില്ലിംഗ് & ആഡംബര A4 ടാക്സ് ഇൻവോയ്സ്',
            'card' => 'ബില്ലിംഗ് & ഇൻവോയ്സ് ഹബ്ബ്',
            'direct_label' => 'ബില്ലിംഗ് & ഇൻവോയ്സുകൾ തുറക്കുക',
            'summary' => 'റൂം വാടക, ഭക്ഷണ ഓർഡറുകൾ, ജിഎസ്ടി, അഡ്വാൻസ് തുക എന്നിവ സംഗ്രഹിച്ച് ആഡംബര A4 ടാക്സ് ഇൻവോയ്സ് പ്രിന്റ് ചെയ്യാൻ **billing.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Billing & Invoices** പേജ് തുറക്കുക.',
                '2' => 'ബിൽ തയ്യാറാക്കേണ്ട അതിഥിയുടെ ബുക്കിംഗ് തിരഞ്ഞെടുക്കുക.',
                '3' => 'റൂം വാടക, ജിഎസ്ടി, കിച്ചൻ ഓർഡറുകൾ, അധിക ചാർജുകൾ എന്നിവ പരിശോധിക്കുക.',
                '4' => '**Record Payment** എന്നതിൽ അതിഥി നൽകിയ തുക (ക്യാഷ്, യുപിഐ, കാർഡ്) രേഖപ്പെടുത്തുക.',
                '5' => 'മുകളിലുള്ള **"Print A4 Tax Invoice"** ക്ലിക്ക് ചെയ്ത് ബ്രാൻഡഡ് എ4 ഇൻവോയ്സ് പ്രിന്റ് ചെയ്യുകയോ PDF ആയി സേവ് ചെയ്യുകയോ ചെയ്യാം!'
            ],
            'tips' => ['💡 ഇൻവോയ്സിൽ ബാങ്ക് വിവരങ്ങളും സ്കാൻ ചെയ്യാവുന്ന UPI QR കോഡും ഓട്ടോമാറ്റിക്കായി പ്രിന്റ് ചെയ്യപ്പെടും.'],
            'step_doubts' => ['5' => 'പ്രിന്റ് ചെയ്യുമ്പോൾ Background Graphics ഓൺ ആക്കി വെച്ചാൽ കളർ കൃത്യമായി ലഭിക്കും.'],
            'follow_up_doubts' => [
                "ക്യാമ്പ്ഫയർ പോലുള്ള അധിക നിരക്കുകൾ ബില്ലിൽ എങ്ങനെ ചേർക്കാം?",
                "ഫൈനൽ ബില്ലിൽ ഡിസ്കൗണ്ട് നൽകാൻ സാധിക്കുമോ?",
                "ഇൻവോയ്സ് PDF ആയി ഡൗൺലോഡ് ചെയ്യുന്നത് എങ്ങനെ?"
            ]
        ],
        'ta' => [
            'title' => 'விருந்தினர் பில்லிங் & ஆடம்பர A4 வரி விலைப்பட்டியல்',
            'card' => 'பில்லிங் & விலைப்பட்டியல் மையம்',
            'direct_label' => 'பில்லிங் மையத்தைத் திற',
            'summary' => 'அறை வாடகை, உணவு ஆர்டர்கள், ஜிஎஸ்டி, முன்பணம் ஆகியவற்றை தொகுத்து A4 விலைப்பட்டியலை **billing.php**-ல் அச்சிடலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Billing & Invoices** பக்கத்தை திறக்கவும்.',
                '2' => 'விருந்தினரின் முன்பதிவை தேர்ந்தெடுத்து பில்லிங் பக்கத்திற்கு செல்லவும்.',
                '3' => 'அறை கட்டணம், ஜிஎஸ்டி மற்றும் உணவு ஆர்டர்களை சரிபார்க்கவும்.',
                '4' => '**Record Payment** மூலம் பெறப்பட்ட பணத்தை பதிவு செய்யவும்.',
                '5' => '**"Print A4 Tax Invoice"** கிளிக் செய்து தொழில்முறை விலைப்பட்டியலை அச்சிடலாம்!'
            ],
            'tips' => ['💡 விலைப்பட்டியலில் உங்கள் வங்கி விவரங்கள் மற்றும் UPI QR குறியீடு அச்சிடப்படும்.'],
            'step_doubts' => ['5' => 'அச்சிடும்போது Background Graphics-ஐ தேர்வு செய்யவும்.'],
            'follow_up_doubts' => [
                "கூடுதல் கட்டணங்களை பில்லில் சேர்ப்பது எப்படி?",
                "விலைப்பட்டியலை PDF வடிவில் பதிவிறக்குவது எப்படி?"
            ]
        ]
    ],

    // 8. CUSTOM BILLING CHARGES & DISCOUNTS
    'custom_bill' => [
        'id' => 'custom_bill',
        'direct_url' => 'billing.php',
        'keywords' => [
            'custom charge', 'add charge', 'discount', 'campfire', 'jeep safari', 'bbq', 'barbeque',
            'extra charge', 'damage fee', 'service charge',
            'അധിക ചാർജ്ജ്', 'ഡിസ്കൗണ്ട്', 'ക്യാമ്പ്ഫയർ', 'സഫാരി', 'ഇളവ്', 'ബാർബിക്യു', 'extra paisa', 'discount kodukkaam',
            'கூடுதல் கட்டணம்', 'தள்ளுபடி', 'கேம்ப்ஃபயர்', 'சவாரி'
        ],
        'en' => [
            'title' => 'Custom Bill Charges, Add-Ons & Discounts',
            'card' => 'Billing Folio Operations',
            'direct_label' => 'Open Billing Folio',
            'summary' => 'Add custom items like Campfire, Jeep Safari, BBQ, or apply promotional discounts directly to any guest folio in **billing.php**.',
            'steps' => [
                '1' => 'Navigate to **Billing & Invoices** and open the active guest stay.',
                '2' => 'Under **Custom Charges & Add-ons**, click **"+ Add Line Item"**.',
                '3' => 'Enter the service title (e.g. *Private Campfire & Marshmallows*) and price (₹).',
                '4' => 'To apply a concession or waiver, use the **Discount (₹)** box.',
                '5' => 'Click **"Update Folio"**. Totals recalculate instantly with tax compliance.'
            ],
            'tips' => ['💡 Line items appear clearly labeled on the final printed tax invoice.'],
            'step_doubts' => ['3' => 'You can specify whether 18% GST applies to this custom service.'],
            'follow_up_doubts' => [
                "Can I give a percentage discount instead of fixed amount?",
                "Where do custom add-ons show up on the A4 invoice?",
                "How do I remove an added charge if added by mistake?"
            ]
        ],
        'ml' => [
            'title' => 'അധിക ചാർജ്ജുകൾ, ആക്ടിവിറ്റികൾ & ഡിസ്കൗണ്ടുകൾ',
            'card' => 'ബില്ലിംഗ് ഫോളിയോ ഓപ്പറേഷൻസ്',
            'direct_label' => 'ബില്ലിംഗ് ഫോളിയോ തുറക്കുക',
            'summary' => 'ക്യാമ്പ്ഫയർ, ജീപ്പ് സഫാരി, ബാർബിക്യു എന്നിവ ബില്ലിൽ ചേർക്കാനും, പ്രത്യേക ഡിസ്കൗണ്ടുകൾ നൽകാനും **billing.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Billing & Invoices** പേജിൽ അതിഥിയുടെ ഫോളിയോ തുറക്കുക.',
                '2' => '**Custom Charges & Add-ons** എന്നതിന് കീഴിൽ **"+ Add Line Item"** ക്ലിക്ക് ചെയ്യുക.',
                '3' => 'സർവീസിന്റെ പേര് (ഉദാ: *ക്യാമ്പ്ഫയർ / ജീപ്പ് സഫാരി*), തുക (₹) എന്നിവ നൽകുക.',
                '4' => 'ഡിസ്കൗണ്ട് നൽകാൻ **Discount (₹)** ബോക്സിൽ ഇളവ് തുക രേഖപ്പെടുത്തുക.',
                '5' => '**"Update Folio"** ക്ലിക്ക് ചെയ്യുക. തുക തത്സമയം കൃത്യമായി കണക്കാക്കപ്പെടും.'
            ],
            'tips' => ['💡 ചേർത്ത അധിക ചാർജ്ജുകൾ അതിഥിയുടെ ഫൈനൽ ഇൻവോയ്സിൽ വ്യക്തമായി കാണിക്കും.'],
            'step_doubts' => ['3' => 'തെറ്റായി ചേർത്ത ചാർജ്ജ് ഡിലീറ്റ് ഐക്കൺ വഴി നീക്കം ചെയ്യാവുന്നതാണ്.'],
            'follow_up_doubts' => [
                "ശതമാനം അടിസ്ഥാനത്തിൽ ഡിസ്കൗണ്ട് നൽകാൻ സാധിക്കുമോ?",
                "തെറ്റായി ചേർത്ത ചാർജ്ജ് എങ്ങനെ ഒഴിവാക്കാം?",
                "ഇതിന് പ്രത്യേക നികുതി ബാധകമാണോ?"
            ]
        ],
        'ta' => [
            'title' => 'கூடுதல் கட்டணங்கள் & தள்ளுபடிகள்',
            'card' => 'பில்லிங் கூடுதல் சேவைகள்',
            'direct_label' => 'பில்லிங் பக்கத்தைத் திற',
            'summary' => 'கேம்ப்ஃபயர், ஜீப் சவாரி போன்ற கூடுதல் சேவைகளை பில்லில் சேர்க்க **billing.php**-ஐ பயன்படுத்தலாம்.',
            'steps' => [
                '1' => '**Billing & Invoices** பக்கத்தில் விருந்தினர் பில்லை திறக்கவும்.',
                '2' => '**"+ Add Line Item"** கிளிக் செய்யவும்.',
                '3' => 'சேவை பெயர் மற்றும் கட்டணத்தை உள்ளிடவும்.',
                '4' => 'தள்ளுபடி வழங்க **Discount (₹)** பெட்டியை பயன்படுத்தவும்.',
                '5' => '**"Update Folio"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 கூடுதல் கட்டணங்கள் விலைப்பட்டியலில் தெளிவாக குறிப்பிடப்படும்.'],
            'step_doubts' => ['3' => 'தேவைப்பட்டால் தவறான கட்டணத்தை நீக்கலாம்.'],
            'follow_up_doubts' => [
                "தள்ளுபடி வழங்குவது எப்படி?",
                "கூடுதல் கட்டணத்தை நீக்குவது எப்படி?"
            ]
        ]
    ],

    // 9. KITCHEN LIVE ORDERS & KOT
    'kitchen' => [
        'id' => 'kitchen',
        'direct_url' => 'kitchen.php',
        'keywords' => [
            'kitchen order', 'live orders', 'kot', 'kitchen management', 'cooking status', 'food status',
            'കിച്ചൻ ഓർഡർ', 'അടുക്കള', 'ലൈവ് ഓർഡറുകൾ', 'ഭക്ഷണം തയ്യാറാക്കൽ', 'ഡെലിവറി', 'ഓർഡർ സ്റ്റാറ്റസ്',
            'kitchen order engane nokkam', 'adukkala', 'சமையலறை ஆர்டர்கள்', 'உணவு நிலை', 'லைவ் ஆர்டர்கள்'
        ],
        'en' => [
            'title' => 'Kitchen Live Orders & Order Status Tracking',
            'card' => 'Kitchen Live Operations',
            'direct_label' => 'Open Kitchen Orders',
            'summary' => 'Track live room-service orders placed by in-house guests, update cooking statuses (Pending, Preparing, Out for Delivery, Delivered) in **kitchen.php**.',
            'steps' => [
                '1' => 'Click **Kitchen** in the left menu (`kitchen.php`).',
                '2' => 'The **"Live Guest Orders"** tab displays active cottage food orders with timestamps and cottage numbers.',
                '3' => 'Click **"Start Preparing"** when kitchen staff commences cooking.',
                '4' => 'Click **"Out for Delivery"** when room-service staff heads to the cottage.',
                '5' => 'Click **"Delivered"** upon successful drop-off. The charges automatically append to the guest\'s stay folio!'
            ],
            'tips' => ['💡 Live notifications chime in real-time when new cottage orders arrive.'],
            'step_doubts' => ['5' => 'Delivered food orders cannot be altered without admin override.'],
            'follow_up_doubts' => [
                "Do food charges automatically get added to the room bill?",
                "Can kitchen staff print KOT kitchen tickets?",
                "How do I cancel a food order if guest changes their mind?"
            ]
        ],
        'ml' => [
            'title' => 'കിച്ചൻ ലൈവ് ഓർഡറുകൾ & ഓർഡർ സ്റ്റാറ്റസ്',
            'card' => 'കിച്ചൻ ലൈവ് ഓപ്പറേഷൻസ്',
            'direct_label' => 'കിച്ചൻ ഓർഡറുകൾ തുറക്കുക',
            'summary' => 'അതിഥികൾ കോട്ടേജിൽ നിന്ന് ഓർഡർ ചെയ്യുന്ന ഭക്ഷണങ്ങൾ തത്സമയം കാണാനും, പാചക പുരോഗതി (Pending, Preparing, Delivered) അപ്‌ഡേറ്റ് ചെയ്യാനും **kitchen.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Kitchen** പേജ് തുറക്കുക (`kitchen.php`).',
                '2' => '**"Live Guest Orders"** ടാബിൽ കോട്ടേജ് നമ്പർ സഹിതം പുതിയ ഓർഡറുകൾ കാണാം.',
                '3' => 'പാചകം തുടങ്ങുമ്പോൾ **"Start Preparing"** ക്ലിക്ക് ചെയ്യുക.',
                '4' => 'ഭക്ഷണം കോട്ടേജിലേക്ക് കൊണ്ടുപോകുമ്പോൾ **"Out for Delivery"** നൽകുക.',
                '5' => 'അതിഥിക്ക് എത്തിച്ച ശേഷം **"Delivered"** അമർത്തുക. ഭക്ഷണ തുക അതിഥിയുടെ റൂം ബില്ലിൽ തനിയെ ചേർക്കപ്പെടും!'
            ],
            'tips' => ['💡 പുതിയ ഓർഡർ വരുമ്പോൾ തത്സമയം ശബ്ദ അലേർട്ട് ലഭിക്കും.'],
            'step_doubts' => ['5' => 'ഡെലിവറി പൂർത്തിയായ ഓർഡറുകൾ ഓട്ടോമാറ്റിക്കായി ബില്ലിംഗിലേക്ക് പോകും.'],
            'follow_up_doubts' => [
                "ഭക്ഷണ ചാർജ്ജ് റൂം ബില്ലിൽ തനിയെ കൂടുമോ?",
                "ഓർഡർ റദ്ദാക്കാൻ സാധിക്കുമോ?",
                "കിച്ചൻ ഓർഡർ പ്രിന്റ് ചെയ്യാൻ പറ്റുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'சமையலறை நேரடி ஆர்டர்கள் & நிலை கண்காணிப்பு',
            'card' => 'சமையலறை நேரடி மையம்',
            'direct_label' => 'சமையலறை ஆர்டர்களைத் திற',
            'summary' => 'விருந்தினர்களின் உணவு ஆர்டர்களை நிகழ்நேரத்தில் கண்காணிக்க **kitchen.php**-ஐ பயன்படுத்தலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Kitchen** பக்கத்திற்கு செல்லவும்.',
                '2' => 'புதிய ஆர்டர்களை **"Live Guest Orders"** தாவலில் பார்க்கலாம்.',
                '3' => 'சமைக்கத் தொடங்கும் போது **"Start Preparing"** அழுத்தவும்.',
                '4' => 'அறைக்கு எடுத்துச் செல்லும்போது **"Out for Delivery"** கொடுக்கவும்.',
                '5' => 'வழங்கிய பின் **"Delivered"** கிளிக் செய்யவும். கட்டணம் தானாக அறை பில்லில் சேரும்!'
            ],
            'tips' => ['💡 புதிய ஆர்டர் வரும்போது ஒலி எச்சரிக்கை ஒலிக்கும்.'],
            'step_doubts' => ['5' => 'வழங்கப்பட்ட உணவு கட்டணம் தானாகவே பில்லில் சேரும்.'],
            'follow_up_doubts' => [
                "உணவு கட்டணம் தானாக அறை பில்லில் சேருமா?",
                "ஆர்டரை ரத்து செய்வது எப்படி?"
            ]
        ]
    ],

    // 10. CALENDAR & DATE BLOCKING
    'calendar' => [
        'id' => 'calendar',
        'direct_url' => 'calendar.php',
        'keywords' => [
            'calendar', 'block date', 'block room', 'unblock date', 'maintenance', 'rate override',
            'weekend price', 'holiday price', 'availability calendar',
            'കലണ്ടർ', 'തീയതി ബ്ലോക്ക്', 'റൂം ബ്ലോക്ക്', 'തീയതി അൺബ്ലോക്ക്', 'അറ്റകുറ്റപ്പണി', 'റേറ്റ് മാറ്റം',
            'വാരാന്ത്യ നിരക്ക്', 'കലണ്ടർ ബ്ലോക്കിംഗ്', 'thethi block', 'calendar block cheyyaan',
            'நாட்காட்டி', 'காலண்டர்', 'தேதி தடுப்பு', 'அறை முடக்கு', 'தேதி விடுவித்தல்'
        ],
        'en' => [
            'title' => 'Booking Calendar, Date Blocking & Rate Overrides',
            'card' => 'Interactive Booking Calendar',
            'direct_label' => 'Open Booking Calendar',
            'summary' => 'Block dates for maintenance, private events, or set custom holiday rates for specific dates in **calendar.php**.',
            'steps' => [
                '1' => 'Click **Booking Calendar** in the left sidebar menu (`calendar.php`).',
                '2' => 'View the monthly grid showing all villas and active reservations.',
                '3' => 'Click on any available date cell for a villa.',
                '4' => 'To block the date from receiving bookings, toggle **"Block Date"** and enter a note (e.g. *Painting & Maintenance*).',
                '5' => 'To set a custom holiday price, enter the overridden amount in **"Custom Rate (₹)"** and click **"Save Date Settings"**.'
            ],
            'tips' => ['💡 Blocked dates immediately reflect on public booking modals and synchronize to Airbnb via iCal!'],
            'step_doubts' => ['4' => 'To unblock, click the blocked date again and click "Unblock Date".'],
            'follow_up_doubts' => [
                "Does blocking a date in the calendar sync to Airbnb?",
                "How do I unblock a date that was previously blocked?",
                "Can I block multiple dates at once?"
            ]
        ],
        'ml' => [
            'title' => 'ബുക്കിംഗ് കലണ്ടർ & തീയതി ബ്ലോക്കിംഗ്',
            'card' => 'ഇന്ററാക്ടീവ് ബുക്കിംഗ് കലണ്ടർ',
            'direct_label' => 'ബുക്കിംഗ് കലണ്ടർ തുറക്കുക',
            'summary' => 'അറ്റകുറ്റപ്പണികൾക്കോ സ്വകാര്യ ആവശ്യങ്ങൾക്കോ തീയതികൾ തടയാനും, അവധി ദിവസങ്ങളിൽ പ്രത്യേക വാടക നൽകാനും **calendar.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Booking Calendar** ക്ലിക്ക് ചെയ്യുക (`calendar.php`).',
                '2' => 'ഓരോ വില്ലയുടെയും പ്രതിമാസ ബുക്കിംഗ് സ്റ്റാറ്റസ് കലണ്ടറിൽ കാണാം.',
                '3' => 'ബ്ലോക്ക് ചെയ്യേണ്ട വില്ലയുടെ തീയതിയിലെ സെല്ലിൽ ക്ലിക്ക് ചെയ്യുക.',
                '4' => 'ബുക്കിംഗ് തടയാൻ **"Block Date"** നൽകി കാരണം രേഖപ്പെടുത്തുക (ഉദാ: അറ്റകുറ്റപ്പണി).',
                '5' => 'പ്രത്യേക ദിവസങ്ങളിൽ വാടക കൂട്ടാൻ **"Custom Rate (₹)"** എന്നതിൽ പുതിയ തുക നൽകി സേവ് ചെയ്യുക.'
            ],
            'tips' => ['💡 ബ്ലോക്ക് ചെയ്യുന്ന തീയതികൾ പബ്ലിക് വെബ്‌സൈറ്റിലും എയർബിഎൻബി കലണ്ടറിലും തത്സമയം ബ്ലോക്ക് ചെയ്യപ്പെടും!'],
            'step_doubts' => ['4' => 'ബ്ലോക്ക് നീക്കം ചെയ്യാൻ ആ തീയതിയിൽ വീണ്ടും ക്ലിക്ക് ചെയ്ത് "Unblock Date" നൽകിയാൽ മതിയാകും.'],
            'follow_up_doubts' => [
                "കലണ്ടറിൽ ബ്ലോക്ക് ചെയ്താൽ Airbnb-യിൽ തനിയെ ബ്ലോക്ക് ആകുമോ?",
                "ബ്ലോക്ക് ചെയ്ത തീയതി എങ്ങനെ അൺബ്ലോക്ക് ചെയ്യാം?",
                "ഒന്നിലധികം തീയതികൾ ഒരുമിച്ച് ബ്ലോക്ക് ചെയ്യാൻ സാധിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'முன்பதிவு காலண்டர் & தேதி தடுப்பு',
            'card' => 'முன்பதிவு காலண்டர் மையம்',
            'direct_label' => 'காலண்டரைத் திற',
            'summary' => 'பராமரிப்பு பணிகளுக்காக குறிப்பிட்ட தேதிகளை முடக்க **calendar.php** பயன்படுகிறது.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Booking Calendar** பக்கத்தை திறக்கவும்.',
                '2' => 'மாதாந்திர காலண்டரில் வில்லாக்களின் முன்பதிவு நிலையை பார்க்கலாம்.',
                '3' => 'தேவையான வில்லாவின் குறிப்பிட்ட தேதியை கிளிக் செய்யவும்.',
                '4' => 'முன்பதிவை தடுக்க **"Block Date"** தேர்வு செய்யவும்.',
                '5' => 'சிறப்பு கட்டணம் அமைக்க **"Custom Rate (₹)"** உள்ளிட்டு சேமிக்கவும்.'
            ],
            'tips' => ['💡 தடுக்கப்பட்ட தேதிகள் இணையதளத்திலும் Airbnb தளத்திலும் உடனடியாக தடுக்கப்படும்!'],
            'step_doubts' => ['4' => 'தடையை நீக்க அந்த தேதியை மீண்டும் கிளிக் செய்து "Unblock Date" அழுத்தவும்.'],
            'follow_up_doubts' => [
                "காலண்டரில் தடுத்தால் Airbnb-ல் தானாக தடுக்கப்படுமா?",
                "தடுக்கப்பட்ட தேதியை எவ்வாறு விடுவிப்பது?"
            ]
        ]
    ],

    // 11. AIRBNB & OTA ICAL SYNC
    'channel_sync' => [
        'id' => 'channel_sync',
        'direct_url' => 'channel_sync.php',
        'keywords' => [
            'airbnb', 'ota', 'ical', 'calendar sync', 'sync airbnb', 'booking.com', 'agoda', 'export ical', 'import ical',
            'എയർബിഎൻബി', 'ഐകാൽ', 'കലണ്ടർ സിങ്ക്', 'ബുക്കിംഗ്.കോം', 'ഒടിഎ സിങ്ക്', 'ഡബിൾ ബുക്കിംഗ്',
            'airbnb engane sync cheyyam', 'ical url', 'ஏர்பிஎன்பி', 'ஐகேல்', 'நாட்காட்டி இணைப்பு'
        ],
        'en' => [
            'title' => 'Airbnb & OTA iCal 2-Way Calendar Sync',
            'card' => 'Channel Sync Hub',
            'direct_label' => 'Open OTA Channel Sync',
            'summary' => 'Prevent double-bookings by linking your calendar bidirectionally with Airbnb and Booking.com using iCal URLs in **channel_sync.php**.',
            'steps' => [
                '1' => 'Navigate to **OTA Channel Sync** (`channel_sync.php`) in the left menu.',
                '2' => 'Copy the Food Forest **Export iCal URL** for the desired villa and paste it into Airbnb under *Calendar Sync > Import Calendar*.',
                '3' => 'Copy the **Airbnb Export Calendar URL** and paste it into the **Import iCal Feed URL** field here.',
                '4' => 'Click **"Save Feed"** and then press **"Sync Now"** to pull external bookings.',
                '5' => 'All Airbnb reservations will now automatically block those dates in Food Forest!'
            ],
            'tips' => ['💡 The system auto-syncs feeds periodically, guaranteeing protection against overlapping bookings.'],
            'step_doubts' => ['2' => 'Each villa has its own unique iCal feed to keep bookings separated.'],
            'follow_up_doubts' => [
                "How often does the Airbnb iCal sync run?",
                "Can I sync with Booking.com and Airbnb at the same time?",
                "What happens if an Airbnb guest cancels?"
            ]
        ],
        'ml' => [
            'title' => 'എയർബിഎൻബി & ഒടിഎ iCal ടു-വേ സിങ്ക്',
            'card' => 'ചാനൽ സിങ്ക് ഹബ്ബ്',
            'direct_label' => 'ചാനൽ സിങ്ക് തുറക്കുക',
            'summary' => 'ഡബിൾ ബുക്കിംഗ് ഒഴിവാക്കാനായി Airbnb, Booking.com എന്നിവയുമായി കലണ്ടർ രണ്ട് വഴികളിലും iCal വഴി സിങ്ക് ചെയ്യാൻ **channel_sync.php** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **OTA Channel Sync** പേജ് തുറക്കുക (`channel_sync.php`).',
                '2' => 'ഫുഡ് ഫോറസ്റ്റ് നൽകുന്ന **Export iCal URL** കോപ്പി ചെയ്ത് Airbnb കലണ്ടർ സെറ്റിംഗ്സിൽ പേസ്റ്റ് ചെയ്യുക.',
                '3' => 'Airbnb നൽകുന്ന കലണ്ടർ ലിങ്ക് ഇവിടെ **Import iCal Feed URL** ബോക്സിൽ പേസ്റ്റ് ചെയ്യുക.',
                '4' => '**"Save Feed"** ക്ലിക്ക് ചെയ്ത ശേഷം **"Sync Now"** അമർത്തുക.',
                '5' => 'ഇതോടെ Airbnb-ൽ നിന്നുള്ള ബുക്കിംഗുകൾ ഓട്ടോമാറ്റിക്കായി ഫുഡ് ഫോറസ്റ്റ് കലണ്ടറിൽ ബ്ലോക്ക് ചെയ്യപ്പെടും!'
            ],
            'tips' => ['💡 രണ്ട് പ്ലാറ്റ്‌ഫോമുകളിലും ഒരേ സമയം ഒരാൾ ബുക്ക് ചെയ്യുന്നത് തടയാൻ ഇത് സഹായിക്കുന്നു.'],
            'step_doubts' => ['2' => 'ഓരോ വില്ലയ്ക്കും പ്രത്യേക iCal ലിങ്കുകൾ ലഭ്യമാണ്.'],
            'follow_up_doubts' => [
                "Airbnb സിങ്ക് എത്ര സമയത്തിനുള്ളിൽ നടക്കും?",
                "Booking.com, Airbnb എന്നിവ ഒരേ സമയം സിങ്ക് ചെയ്യാമോ?",
                "Airbnb-ൽ ബുക്കിംഗ് ക്യാൻസൽ ചെയ്താൽ ഇവിടെ തീയതി റിലീസ് ആകുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'Airbnb & OTA iCal இருவழி இணைப்பு',
            'card' => 'சேனல் இணைப்பு மையம்',
            'direct_label' => 'சேனல் இணைப்பைத் திற',
            'summary' => 'இரட்டை முன்பதிவுகளைத் தவிர்க்க Airbnb உடன் நாட்காட்டியை **channel_sync.php**-ல் இணைக்கலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **OTA Channel Sync** பக்கத்திற்கு செல்லவும்.',
                '2' => 'Food Forest வழங்கும் **Export iCal URL**-ஐ Airbnb-ல் உள்ளிடவும்.',
                '3' => 'Airbnb வழங்கும் காலண்டர் இணைப்பை இங்கே உள்ளிட்டு சேமிக்கவும்.',
                '4' => '**"Sync Now"** அழுத்தவும்.'
            ],
            'tips' => ['💡 ஒரே நேரத்தில் இருமுறை முன்பதிவு செய்யப்படுவதை முழுமையாக தடுக்கும்.'],
            'step_doubts' => ['2' => 'ஒவ்வொரு வில்லாவிற்கும் தனித்தனி iCal இணைப்பு உண்டு.'],
            'follow_up_doubts' => [
                "Airbnb இணைப்பு எவ்வளவு நேரத்திற்கு ஒருமுறை இயங்கும்?",
                "Booking.com மற்றும் Airbnb இரண்டையும் ஒன்றாக இணைக்க முடியுமா?"
            ]
        ]
    ],

    // 12. SANCTUARY MAP & TRAILS
    'sanctuary_map' => [
        'id' => 'sanctuary_map',
        'direct_url' => 'edit_section.php?section=map',
        'keywords' => [
            'map', 'sanctuary map', 'trails', 'walking trails', 'points of interest', 'resort map', 'interactive map',
            'മാപ്പ്', 'റിസോർട്ട് മാപ്പ്', 'വഴികൾ', 'നടപ്പാത', 'സ്ഥലങ്ങൾ', 'ലൊക്കേഷൻ', 'ട്രെക്കിംഗ്', 'map engane edit cheyyam',
            'வரைபடம்', 'நடைபாதை', 'சுற்றுலா வரைபடம்', 'இடங்கள்'
        ],
        'en' => [
            'title' => 'Interactive Sanctuary Map & Walking Trails',
            'card' => 'Card 05 (Sanctuary Map & Trails)',
            'direct_label' => 'Open Sanctuary Map Editor',
            'summary' => 'Manage interactive points of interest (Villas, Campfire spot, Stream, Plantation view) and nature walking trails on the estate map in **Card 05**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and select **Card 05 (Sanctuary Map & Trails)**.',
                '2' => 'Click on the map preview to add a new point of interest or edit existing markers.',
                '3' => 'Assign an icon (e.g. Treehouse, Water Stream, Organic Orchard) and title.',
                '4' => 'Configure walking trails with distance (meters) and walking difficulty level.',
                '5' => 'Click **"Save Map Configuration"**.'
            ],
            'tips' => ['💡 Guests viewing the map on mobile can tap any point for photos and directions!'],
            'step_doubts' => ['2' => 'Coordinates are captured automatically by clicking directly on the map image.'],
            'follow_up_doubts' => [
                "How do I upload a new map illustration image?",
                "Can I add photos to map spots?",
                "How do guests access the interactive map?"
            ]
        ],
        'ml' => [
            'title' => 'റിസോർട്ട് മാപ്പും പ്രകൃതി നടത്ത വഴികളും',
            'card' => 'കാർഡ് 05 (റിസോർട്ട് മാപ്പ് & ട്രെയിൽസ്)',
            'direct_label' => 'മാപ്പ് എഡിറ്റർ തുറക്കുക',
            'summary' => 'റിസോർട്ടിനുള്ളിലെ പ്രധാന സ്ഥലങ്ങൾ (വില്ലകൾ, അരുവി, ക്യാമ്പ്ഫയർ ഏരിയ, ഓർഗാനിക് തോട്ടം) എന്നിവ മാപ്പിൽ അടയാളപ്പെടുത്താൻ **Card 05** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് **Card 05 (Sanctuary Map & Trails)** തിരഞ്ഞെടുക്കുക.',
                '2' => 'മാപ്പിൽ പുതിയ പോയിന്റ് ചേർക്കാൻ മാപ്പ് ചിത്രത്തിൽ ആവശ്യമുള്ള സ്ഥലത്ത് ക്ലിക്ക് ചെയ്യുക.',
                '3' => 'സ്ഥലത്തിന്റെ പേരും ഐക്കണും (ഉദാ: അരുവി, തോട്ടം) നൽകുക.',
                '4' => 'നടപ്പാതകളുടെ ദൂരവും ബുദ്ധിമുട്ട് നിലവാരവും ക്രമീകരിക്കാം.',
                '5' => '**"Save Map Configuration"** ക്ലിക്ക് ചെയ്ത് സേവ് ചെയ്യുക.'
            ],
            'tips' => ['💡 മൊബൈലിൽ അതിഥികൾക്ക് മാപ്പിലെ സ്ഥലങ്ങളിൽ ടാപ്പ് ചെയ്ത് ഫോട്ടോകളും ദിശകളും കാണാം.'],
            'step_doubts' => ['2' => 'മാപ്പിൽ ക്ലിക്ക് ചെയ്യുമ്പോൾ ലൊക്കേഷൻ തനിയെ രേഖപ്പെടുത്തും.'],
            'follow_up_doubts' => [
                "പുതിയ മാപ്പ് ചിത്രം എങ്ങനെ അപ്‌ലോഡ് ചെയ്യാം?",
                "മാപ്പിലെ സ്ഥലങ്ങളിൽ ഫോട്ടോകൾ ചേർക്കാമോ?",
                "അതിഥികൾക്ക് മാപ്പ് എവിടെ കാണാൻ കഴിയും?"
            ]
        ],
        'ta' => [
            'title' => 'ரிசார்ட் வரைபடம் & நடைபாதைகள்',
            'card' => 'கார்டு 05 (வரைபடம் & பாதைகள்)',
            'direct_label' => 'வரைபட எடிட்டரைத் திற',
            'summary' => 'முக்கிய இடங்களை வரைபடத்தில் குறிக்க **Card 05**-ஐ பயன்படுத்தலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று **Card 05**-ஐ திறக்கவும்.',
                '2' => 'வரைபடத்தில் கிளிக் செய்து புதிய இடத்தை சேர்க்கவும்.',
                '3' => 'பெயர் மற்றும் குறியீட்டை உள்ளிடவும்.',
                '4' => '**"Save Map Configuration"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 விருந்தினர்கள் வரைபடத்தை தொட்டு விவரங்களை பார்க்கலாம்.'],
            'step_doubts' => ['2' => 'வரைபடத்தில் கிளிக் செய்யும் போது தானாக இடம் குறிக்கப்படும்.'],
            'follow_up_doubts' => ["வரைபடத்தில் படங்களை சேர்ப்பது எப்படி?"]
        ]
    ],

    // 13. EXPERIENCES & PLANTATION ACTIVITIES
    'experiences' => [
        'id' => 'experiences',
        'direct_url' => 'edit_section.php?section=experiences',
        'keywords' => [
            'experiences', 'activities', 'plantation tour', 'bird watching', 'guided walk', 'farm visit',
            'ആക്ടിവിറ്റികൾ', 'തോട്ടം സന്ദർശനം', 'പക്ഷി നിരീക്ഷണം', 'റിസോർട്ട് പരിപാടികൾ', 'യാത്ര',
            'தோட்ட அனுபவங்கள்', 'பறவை பார்த்தல்', 'சுற்றுலா நடவடிக்கைகள்'
        ],
        'en' => [
            'title' => 'Plantation Activities & Sanctuary Experiences',
            'card' => 'Card 07 (Experiences & Activities)',
            'direct_label' => 'Open Experiences Editor',
            'summary' => 'Add, edit, or curate bespoke sanctuary experiences (Organic Fruit Picking, Forest Bathing, Guided Spice Treks) in **Card 07**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and open **Card 07 (Experiences & Activities)**.',
                '2' => 'Click **"+ Add Experience"** or select an existing activity to edit.',
                '3' => 'Enter Activity Title, Duration (e.g. 90 mins), Best Timing, and Description.',
                '4' => 'Upload rich experience photography.',
                '5' => 'Click **"Save Experiences"**.'
            ],
            'tips' => ['💡 Featured experiences appear on the public homepage with booking inquiries.'],
            'step_doubts' => ['3' => 'Specify if an activity is complimentary or has an additional fee.'],
            'follow_up_doubts' => [
                "Can I set pricing for paid experiences?",
                "How do I reorder activities on the homepage?"
            ]
        ],
        'ml' => [
            'title' => 'തോട്ടം സന്ദർശനവും ആക്ടിവിറ്റികളും',
            'card' => 'കാർഡ് 07 (ആക്ടിവിറ്റികൾ & എക്സ്പീരിയൻസ്)',
            'direct_label' => 'ആക്ടിവിറ്റി എഡിറ്റർ തുറക്കുക',
            'summary' => 'റിസോർട്ടിൽ ലഭ്യമായ പ്രത്യേക അനുഭവങ്ങൾ (പഴം പറിക്കൽ, പക്ഷി നിരീക്ഷണം, ട്രെക്കിംഗ്) എന്നിവ വെബ്‌സൈറ്റിൽ നൽകാൻ **Card 07** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് **Card 07 (Experiences & Activities)** തിരഞ്ഞെടുക്കുക.',
                '2' => '**"+ Add Experience"** ക്ലിക്ക് ചെയ്യുക.',
                '3' => 'പരിപാടിയുടെ പേര്, ദൈർഘ്യം (ഉദാ: 1.5 മണിക്കൂർ), അനുയോജ്യമായ സമയം, വിവരണം എന്നിവ നൽകുക.',
                '4' => 'ആകർഷകമായ ചിത്രങ്ങൾ അപ്‌ലോഡ് ചെയ്യുക.',
                '5' => '**"Save Experiences"** ക്ലിക്ക് ചെയ്യുക.'
            ],
            'tips' => ['💡 ഈ പരിപാടികൾ ഹോംപേജിൽ അതിഥികൾക്കായി മനോഹരമായി കാണിക്കും.'],
            'step_doubts' => ['3' => 'സൗജന്യമാണോ ചാർജ്ജ് ഉള്ളതാണോ എന്ന് വിവരണത്തിൽ വ്യക്തമാക്കാം.'],
            'follow_up_doubts' => [
                "പെയ്ഡ് ആക്ടിവിറ്റികൾക്ക് വില എങ്ങനെ നൽകാം?",
                "ഹോംപേജിൽ ഇവ കാണിക്കുന്നത് എങ്ങനെ?"
            ]
        ],
        'ta' => [
            'title' => 'தோட்ட நடவடிக்கைகள் & அனுபவங்கள்',
            'card' => 'கார்டு 07 (நடவடிக்கைகள்)',
            'direct_label' => 'அனுபவங்கள் எடிட்டரைத் திற',
            'summary' => 'பழம் பறித்தல், பறவை பார்த்தல் போன்ற அனுபவங்களை **Card 07**-ல் சேர்க்கலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று **Card 07**-ஐ திறக்கவும்.',
                '2' => '**"+ Add Experience"** கிளிக் செய்யவும்.',
                '3' => 'தலைப்பு, நேரம், விளக்கம் மற்றும் படங்களை சேர்க்கவும்.',
                '4' => '**"Save Experiences"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 இந்த விவரங்கள் இணையதள முகப்பு பக்கத்தில் தோன்றும்.'],
            'step_doubts' => ['3' => 'கட்டண விவரங்களை விளக்கத்தில் குறிப்பிடலாம்.'],
            'follow_up_doubts' => ["முகப்பு பக்கத்தில் வரிசையை மாற்றுவது எப்படி?"]
        ]
    ],

    // 14. PHOTO GALLERY & CATEGORIES
    'gallery' => [
        'id' => 'gallery',
        'direct_url' => 'edit_section.php?section=gallery',
        'keywords' => [
            'gallery', 'photo gallery', 'upload photos', 'add images', 'resort photos', 'categories',
            'ഗാലറി', 'ഫോട്ടോ ഗാലറി', 'ചിത്രങ്ങൾ', 'ഫോട്ടോ അപ്‌ലോഡ്', 'ചിത്രശാല', 'ഫോട്ടോസ്',
            'புகைப்பட தொகுப்பு', 'படங்கள்', 'புகைப்படம் பதிவேற்றம்'
        ],
        'en' => [
            'title' => 'Photo Gallery & Categorized Media Hub',
            'card' => 'Card 06 (Photo Gallery & Visuals)',
            'direct_label' => 'Open Photo Gallery Editor',
            'summary' => 'Upload high-resolution photography categorized into Villas, Nature & Flora, Dining, and Experiences in **Card 06**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and open **Card 06 (Photo Gallery & Visuals)**.',
                '2' => 'Select the category you wish to add photos to (e.g. *Villas*, *Dining*, *Nature*).',
                '3' => 'Click **"Upload Photos"** and select files from your computer (auto-compressed for ultra-fast web delivery).',
                '4' => 'Reorder photos using drag-and-drop or set captions.',
                '5' => 'Click **"Save Gallery"** and remember to **Purge Cache**.'
            ],
            'tips' => ['💡 All photos are automatically optimized with WebP compression for lightning-fast loads!'],
            'step_doubts' => ['3' => 'Photos are automatically resized to prevent slow mobile loading.'],
            'follow_up_doubts' => [
                "How do I delete or replace an old photo?",
                "What is the recommended image resolution?",
                "Why are uploaded photos not showing immediately?"
            ]
        ],
        'ml' => [
            'title' => 'ഫോട്ടോ ഗാലറി & ചിത്രശാല മാനേജ്മെന്റ്',
            'card' => 'കാർഡ് 06 (ഫോട്ടോ ഗാലറി & വിഷ്വലുകൾ)',
            'direct_label' => 'ഗാലറി എഡിറ്റർ തുറക്കുക',
            'summary' => 'റിസോർട്ടിന്റെ മനോഹര ചിത്രങ്ങൾ വിഭാഗങ്ങൾ തിരിച്ച് (വില്ലകൾ, പ്രകൃതി, ഭക്ഷണം) ഗാലറിയിൽ അപ്‌ലോഡ് ചെയ്യാൻ **Card 06** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് **Card 06 (Photo Gallery & Visuals)** തിരഞ്ഞെടുക്കുക.',
                '2' => 'ഫോട്ടോ ചേർക്കേണ്ട വിഭാഗം തിരഞ്ഞെടുക്കുക (ഉദാ: വില്ലകൾ, ഭക്ഷണം, പ്രകൃതി).',
                '3' => '**"Upload Photos"** ക്ലിക്ക് ചെയ്ത് ഫയലുകൾ തിരഞ്ഞെടുക്കുക (ഓട്ടോ-കംപ്രഷൻ ഉള്ളതിനാൽ വേഗത്തിൽ ലോഡാകും).',
                '4' => 'ഫോട്ടോകളുടെ ക്രമം മാറ്റുകയോ ക്യാപ്ഷൻ നൽകുകയോ ചെയ്യാം.',
                '5' => '**"Save Gallery"** നൽകിയ ശേഷം ടോപ്പ്ബാറിലെ **Purge Cache** അമർത്തുക.'
            ],
            'tips' => ['💡 അപ്‌ലോഡ് ചെയ്ത ശേഷം പുതിയ ചിത്രങ്ങൾ വെബ്‌സൈറ്റിൽ ഉടൻ കാണാൻ Purge Cache ക്ലിക്ക് ചെയ്യുക.'],
            'step_doubts' => ['3' => 'മൊബൈലിൽ വേഗത്തിൽ ലോഡാകാൻ ചിത്രങ്ങൾ സ്വമേധയാ കംപ്രസ് ചെയ്യപ്പെടുന്നു.'],
            'follow_up_doubts' => [
                "പഴയ ഫോട്ടോകൾ എങ്ങനെ നീക്കം ചെയ്യാം?",
                "ഏത് സൈസിലുള്ള ചിത്രങ്ങളാണ് അനുയോജ്യം?",
                "ഫോട്ടോകൾ ഉടൻ വെബ്‌സൈറ്റിൽ വരാൻ എന്തുചെയ്യണം?"
            ]
        ],
        'ta' => [
            'title' => 'புகைப்பட தொகுப்பு & காட்சியகம்',
            'card' => 'கார்டு 06 (புகைப்படங்கள்)',
            'direct_label' => 'புகைப்பட எடிட்டரைத் திற',
            'summary' => 'ரிசார்ட்டின் உயர் தெளிவுத்திறன் கொண்ட புகைப்படங்களை வகைப்படுத்தி **Card 06**-ல் பதிவேற்றலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று **Card 06**-ஐ திறக்கவும்.',
                '2' => 'பிரிவை தேர்வு செய்யவும் (வில்லாக்கள், உணவு, இயற்கை).',
                '3' => '**"Upload Photos"** கிளிக் செய்து படங்களை பதிவேற்றவும்.',
                '4' => '**"Save Gallery"** கொடுத்து **Purge Cache** செய்யவும்.'
            ],
            'tips' => ['💡 படங்கள் வேகமாக திறக்க தானாக சுருக்கப்படுகின்றன.'],
            'step_doubts' => ['3' => 'படங்கள் தானாகவே இணையதளத்திற்கு ஏற்ப அளவிடப்படும்.'],
            'follow_up_doubts' => ["பழைய புகைப்படங்களை எவ்வாறு நீக்குவது?"]
        ]
    ],

    // 15. TESTIMONIALS & GUEST REVIEWS
    'testimonials' => [
        'id' => 'testimonials',
        'direct_url' => 'edit_section.php?section=reviews',
        'keywords' => [
            'reviews', 'testimonials', 'guest reflections', 'approve review', 'star rating', 'delete review',
            'അഭിപ്രായങ്ങൾ', 'റിവ്യൂ', 'അതിഥി റിവ്യൂകൾ', 'സ്റ്റാർ റേറ്റിംഗ്', 'റിവ്യൂ അപ്രൂവ് ചെയ്യുക', 'കമന്റുകൾ',
            'விமர்சனங்கள்', 'மதிப்புரைகள்', 'நட்சத்திர மதிப்பீடு'
        ],
        'en' => [
            'title' => 'Guest Reflections, Reviews & Testimonial Moderation',
            'card' => 'Guest Reflections Moderation',
            'direct_label' => 'Open Guest Reviews Hub',
            'summary' => 'Review, approve, feature, or remove guest reflections and five-star ratings submitted on the website in the **Reviews Hub**.',
            'steps' => [
                '1' => 'Click **Guest Reflections** in the left sidebar menu (with unread notification badge).',
                '2' => 'Browse new pending submissions from resident guests.',
                '3' => 'Click **"Approve & Feature"** to showcase glowing reviews on the public homepage.',
                '4' => 'To hide or discard inappropriate reviews, click **"Archive / Remove"**.',
                '5' => 'You can also add verified guest testimonials manually using **"+ Add Verified Review"**.'
            ],
            'tips' => ['💡 Featured testimonials appear in the luxury rotating quote carousel on the website.'],
            'step_doubts' => ['3' => 'Only approved reviews are visible to website visitors.'],
            'follow_up_doubts' => [
                "How do guests submit a review on the website?",
                "Can I edit a guest's review text before approving?"
            ]
        ],
        'ml' => [
            'title' => 'അതിഥി അഭിപ്രായങ്ങളും റിവ്യൂ മോഡറേഷനും',
            'card' => 'ഗസ്റ്റ് റിഫ്ലക്ഷൻസ് മോഡറേഷൻ',
            'direct_label' => 'റിവ്യൂ ഹബ്ബ് തുറക്കുക',
            'summary' => 'അതിഥികൾ വെബ്‌സൈറ്റിൽ നൽകുന്ന അഭിപ്രായങ്ങളും റേറ്റിംഗുകളും പരിശോധിച്ച് അപ്രൂവ് ചെയ്യാനും വെബ്‌സൈറ്റിൽ കാണിക്കാനും ഈ പേജ് ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'ഇടത് മെനുവിലെ **Guest Reflections** ക്ലിക്ക് ചെയ്യുക.',
                '2' => 'അതിഥികൾ അയച്ച പുതിയ റിവ്യൂകൾ പരിശോധിക്കുക.',
                '3' => 'വെബ്‌സൈറ്റിൽ കാണിക്കുന്നതിനായി **"Approve & Feature"** ക്ലിക്ക് ചെയ്യുക.',
                '4' => 'ആവശ്യമില്ലാത്തതോ തെറ്റായതോ ആയ റിവ്യൂകൾ ഒഴിവാക്കാൻ **"Archive / Remove"** നൽകുക.',
                '5' => 'നേരിട്ട് നല്ല റിവ്യൂകൾ ചേർക്കാൻ **"+ Add Verified Review"** ഉപയോഗിക്കാം.'
            ],
            'tips' => ['💡 അപ്രൂവ് ചെയ്ത റിവ്യൂകൾ ഹോംപേജിലെ കറൗസലിൽ മനോഹരമായി കാണിക്കും.'],
            'step_doubts' => ['3' => 'അഡ്മിൻ അപ്രൂവ് ചെയ്താൽ മാത്രമേ സന്ദർശകർക്ക് റിവ്യൂ കാണാനാകൂ.'],
            'follow_up_doubts' => [
                "അതിഥികൾക്ക് എങ്ങനെയാണ് റിവ്യൂ നൽകാൻ സാധിക്കുക?",
                "റിവ്യൂ അപ്രൂവ് ചെയ്യുന്നതിന് മുൻപ് എഡിറ്റ് ചെയ്യാൻ പറ്റുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'விருந்தினர் விமர்சனங்கள் & மதிப்பீடுகள்',
            'card' => 'விமர்சனங்கள் மையம்',
            'direct_label' => 'விமர்சன மையத்தைத் திற',
            'summary' => 'விருந்தினர்களின் மதிப்புரைகளை சரிபார்த்து இணையதளத்தில் வெளியிட இந்த பக்கத்தை பயன்படுத்தலாம்.',
            'steps' => [
                '1' => 'இடது மெனுவில் உள்ள **Guest Reflections**-ஐ கிளிக் செய்யவும்.',
                '2' => 'புதிய விமர்சனங்களை பார்வையிடவும்.',
                '3' => 'முகப்பு பக்கத்தில் காட்ட **"Approve & Feature"** கொடுக்கவும்.',
                '4' => 'நீக்க விரும்பினால் **"Archive / Remove"** அழுத்தவும்.'
            ],
            'tips' => ['💡 அங்கீகரிக்கப்பட்ட விமர்சனங்கள் முகப்பு பக்கத்தில் தோன்றும்.'],
            'step_doubts' => ['3' => 'அனுமதித்த பின்னரே இணையதளத்தில் தெரியும்.'],
            'follow_up_doubts' => ["விமர்சனங்களை எவ்வாறு திருத்துவது?"]
        ]
    ],

    // 16. WHATSAPP CONCIERGE NUMBER
    'whatsapp' => [
        'id' => 'whatsapp',
        'direct_url' => 'edit_section.php?section=contact',
        'keywords' => [
            'whatsapp', 'whatsapp number', 'chat button', 'concierge chat', 'phone number', 'contact number',
            'വാട്സ്ആപ്പ്', 'വാട്സ്ആപ്പ് നമ്പർ', 'ചാറ്റ് ബട്ടൺ', 'ഫോൺ നമ്പർ', 'ബന്ധപ്പെടാൻ', 'whatsapp maattan',
            'வாட்ஸ்அப்', 'வாட்ஸ்அப் எண்', 'அரட்டை பொத்தான்', 'தொடர்பு எண்'
        ],
        'en' => [
            'title' => 'WhatsApp Concierge Floating Button & Phone Setup',
            'card' => 'Card 01 (General Information & Contacts)',
            'direct_label' => 'Open Contact & WhatsApp Settings',
            'summary' => 'Update the floating 24/7 WhatsApp Concierge contact number and pre-filled greeting message in **Card 01**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and select **Card 01 (General Information & Contacts)**.',
                '2' => 'Locate **"WhatsApp Concierge Number"**.',
                '3' => 'Enter your 10-digit mobile number with international country code (e.g. `919876543210`).',
                '4' => 'Customize the default greeting text (e.g. *Hello Food Forest Sanctuary, I would like to inquire about...*).',
                '5' => 'Click **"Save Contact Settings"** and **Purge Cache**.'
            ],
            'tips' => ['💡 Guests tapping the WhatsApp bubble on the site will be launched straight into a chat with your concierge!'],
            'step_doubts' => ['3' => 'Do not include `+` or spaces; enter digits only with country prefix (e.g. `91`).'],
            'follow_up_doubts' => [
                "Why should I include country code 91 without plus?",
                "Can I set multiple WhatsApp numbers?"
            ]
        ],
        'ml' => [
            'title' => 'വാട്സ്ആപ്പ് കൺസിയർജ് നമ്പർ & ചാറ്റ് ബട്ടൺ',
            'card' => 'കാർഡ് 01 (ജനറൽ വിവരങ്ങളും കോൺടാക്റ്റുകളും)',
            'direct_label' => 'വാട്സ്ആപ്പ് സെറ്റിംഗ്സ് തുറക്കുക',
            'summary' => 'വെബ്‌സൈറ്റിൽ അതിഥികൾക്കായി കാണുന്ന വാട്സ്ആപ്പ് ചാറ്റ് ബട്ടണിലെ ഫോൺ നമ്പറും മെസ്സേജും മാറ്റാൻ **Card 01** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് **Card 01 (General Information & Contacts)** തിരഞ്ഞെടുക്കുക.',
                '2' => '**"WhatsApp Concierge Number"** എന്ന ഫീൽഡ് ശ്രദ്ധിക്കുക.',
                '3' => 'രാജ്യ കോഡ് (91) സഹിതം 10 അക്ക മൊബൈൽ നമ്പർ നൽകുക (ഉദാ: `919876543210`).',
                '4' => 'അതിഥികൾ ചാറ്റ് തുടങ്ങുമ്പോൾ വരുന്ന ഡിഫോൾട്ട് സന്ദേശം നൽകാം.',
                '5' => '**"Save Contact Settings"** നൽകി **Purge Cache** ചെയ്യുക.'
            ],
            'tips' => ['💡 നമ്പറിൽ `+` ചിഹ്നമോ സ്പേസോ ഇടാതെ അക്കങ്ങൾ മാത്രം നൽകുക.'],
            'step_doubts' => ['3' => 'ഉദാഹരണത്തിന് `91` എന്ന് തുടങ്ങി മൊബൈൽ നമ്പർ നൽകുക.'],
            'follow_up_doubts' => [
                "രാജ്യ കോഡ് എന്തിനാണ് ചേർക്കുന്നത്?",
                "ഒന്നിലധികം നമ്പറുകൾ വെക്കാൻ സാധിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'வாட்ஸ்அப் தொடர்பு எண் அமைப்பு',
            'card' => 'கார்டு 01 (பொது தகவல்கள் & தொடர்புகள்)',
            'direct_label' => 'வாட்ஸ்அப் அமைப்புகளைத் திற',
            'summary' => 'இணையதளத்தில் உள்ள வாட்ஸ்அப் அரட்டை எண்ணை **Card 01**-ல் மாற்றலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று **Card 01**-ஐ திறக்கவும்.',
                '2' => '**"WhatsApp Concierge Number"** பிரிவை அணுகவும்.',
                '3' => 'நாட்டு குறியீட்டுடன் (91) எண்களை மட்டும் உள்ளிடவும் (எ.கா: `919876543210`).',
                '4' => '**"Save Contact Settings"** கொடுத்து **Purge Cache** செய்யவும்.'
            ],
            'tips' => ['💡 `+` அல்லது இடைவெளி இன்றி எண்களை மட்டும் உள்ளிடவும்.'],
            'step_doubts' => ['3' => 'நாட்டு குறியீடு 91-ஐ முன்னதாக சேர்க்க வேண்டும்.'],
            'follow_up_doubts' => ["வாட்ஸ்அப் எண் செயல்படுவதை எவ்வாறு சரிபார்ப்பது?"]
        ]
    ],

    // 17. 360 WALKTHROUGH & 16:9 STAGES
    'walkthrough_360' => [
        'id' => 'walkthrough_360',
        'direct_url' => 'edit_section.php?section=rooms',
        'keywords' => [
            '360 walkthrough', 'panorama', 'virtual tour', '16:9 photo', 'interactive walkthrough', 'cropper',
            '360 പനോരമ', 'വെർച്വൽ ടൂർ', '360 ടൂർ', 'പനോരമ ചിത്രം', '360° പനോരമ',
            '360 விர்ச்சுவல் டூர்', 'பனோரமா படம்', 'சுற்றுப்பயணம்'
        ],
        'en' => [
            'title' => '16:9 Walkthrough Stages & 360° Panorama Setup',
            'card' => 'Card 04 & 360° Experience Hub',
            'direct_label' => 'Open 360° Tour Settings',
            'summary' => 'Configure 16:9 high-res cropped walkthrough photo stages and equirectangular 360° WebGL panoramas in **Card 04**.',
            'steps' => [
                '1' => 'Go to **Card 04 (Villas & Stays)** and select the villa.',
                '2' => 'Scroll down to **"Media & 360° Panoramic Tour"**.',
                '3' => 'Upload a 2:1 ratio Equirectangular JPEG image for the 360° interactive viewer.',
                '4' => 'Use the built-in Interactive Cropper tool to frame 16:9 widescreen presentation stills.',
                '5' => 'Click **"Save Changes"** and test the live 360° spin in the preview frame!'
            ],
            'tips' => ['💡 Equirectangular panoramas allow guests to rotate 360 degrees around the cottage with touch controls.'],
            'step_doubts' => ['3' => 'Ensure the image is JPEG format with 2:1 aspect ratio (e.g. 4000x2000 pixels).'],
            'follow_up_doubts' => [
                "Where can I get 360° panorama photos taken?",
                "Does the 360 viewer work smoothly on smartphones?"
            ]
        ],
        'ml' => [
            'title' => '16:9 വാക്ക്‌ത്രൂ & 360° വെർച്വൽ പനോരമ സെറ്റപ്പ്',
            'card' => 'കാർഡ് 04 (വില്ലകളും 360° ടൂറും)',
            'direct_label' => '360° ടൂർ സെറ്റിംഗ്സ് തുറക്കുക',
            'summary' => 'അതിഥികൾക്ക് റൂമുകൾ 360 ഡിഗ്രിയിൽ ചുറ്റിനടന്ന് കാണാൻ സാധിക്കുന്ന വെർച്വൽ പനോരമ ചിത്രം **Card 04**-ൽ അപ്‌ലോഡ് ചെയ്യാം.',
            'steps' => [
                '1' => '**Card 04 (Villas & Stays)** തുറന്ന് വില്ല തിരഞ്ഞെടുക്കുക.',
                '2' => '**"Media & 360° Panoramic Tour"** എന്ന വിഭാഗത്തിലേക്ക് പോകുക.',
                '3' => '2:1 അനുപാതത്തിലുള്ള ഇക്വിറെക്ടാംഗുലർ പനോരമ (JPEG) ചിത്രം അപ്‌ലോഡ് ചെയ്യുക.',
                '4' => '16:9 വൈഡ്‌സ്‌ക്രീൻ ഫോട്ടോകൾക്കായി ക്രോപ്പർ ടൂൾ ഉപയോഗിക്കാം.',
                '5' => '**"Save Changes"** ക്ലിക്ക് ചെയ്ത് പ്രിവ്യൂവിൽ 360° കറക്കി നോക്കുക!'
            ],
            'tips' => ['💡 മൊബൈൽ ഫോണിൽ ടച്ച് ചെയ്ത് വില്ലകൾ 360 ഡിഗ്രിയിൽ അതിഥികൾക്ക് കാണാം.'],
            'step_doubts' => ['3' => '4000x2000 അല്ലെങ്കിൽ 6000x3000 റെസല്യൂഷനിലുള്ള JPEG ഫയൽ ഏറ്റവും അനുയോജ്യമാണ്.'],
            'follow_up_doubts' => [
                "360° ഫോട്ടോകൾ എങ്ങനെ ഷൂട്ട് ചെയ്യാം?",
                "മൊബൈൽ ഫോണുകളിൽ ഇത് വേഗത്തിൽ പ്രവർത്തിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => '360° விர்ச்சுவல் டூர் & பனோரமா அமைப்பு',
            'card' => 'கார்டு 04 (360° டூர்)',
            'direct_label' => '360° டூர் அமைப்புகளைத் திற',
            'summary' => 'விருந்தினர்கள் அறையை 360 டிகிரியில் சுற்றிப் பார்க்க விர்ச்சுவல் பனோரமா படத்தை **Card 04**-ல் அமைக்கலாம்.',
            'steps' => [
                '1' => '**Card 04 (Villas & Stays)** சென்று வில்லாவை தேர்ந்தெடுக்கவும்.',
                '2' => '**"Media & 360° Panoramic Tour"** பிரிவுக்கு செல்லவும்.',
                '3' => '2:1 விகித பனோரமா படத்தை பதிவேற்றவும்.',
                '4' => '**"Save Changes"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 தொடுதிரை மூலம் 360 டிகிரியில் சுழற்றி பார்க்கலாம்.'],
            'step_doubts' => ['3' => '2:1 விகித படம் (4000x2000 பிக்சல்) மிகவும் சிறந்தது.'],
            'follow_up_doubts' => ["360 விர்ச்சுவல் டூர் எவ்வாறு இயங்குகிறது?"]
        ]
    ],

    // 18. CLEAR DEMO DATA SAFELY
    'clear_data' => [
        'id' => 'clear_data',
        'direct_url' => 'edit_section.php?section=system',
        'keywords' => [
            'clear data', 'clear demo data', 'delete test bookings', 'reset bookings', 'purge demo',
            'clean database', 'launch clean', 'remove dummy data',
            'ഡെമോ ഡാറ്റ', 'ടെസ്റ്റ് ബുക്കിംഗ് കളയുക', 'ഡാറ്റ റീസെറ്റ്', 'ഡെമോ ഒഴിവാക്കുക', 'ഡാറ്റാബേസ് ക്ലീൻ',
            'demo data delete aakkan', 'test booking kalayan', 'டெமோ தரவு நீக்குதல்', 'சோதனை தரவு அழித்தல்'
        ],
        'en' => [
            'title' => 'Safely Purge Demo Bookings & Kitchen Orders',
            'card' => 'Card 08 (System Operations & Maintenance)',
            'direct_label' => 'Open System Maintenance & Reset',
            'summary' => 'Safely purge test reservations, dummy kitchen orders, and test inquiries before launching without losing villas, menus, or bank configurations in **Card 08**.',
            'steps' => [
                '1' => 'Go to **Estate Settings** and navigate to **Card 08 (System Operations & Maintenance)**.',
                '2' => 'Locate the **"Reset Demo Data"** section.',
                '3' => 'Select **"Purge Test Bookings & Kitchen Orders Only"** (Recommended for launch).',
                '4' => 'Type **CONFIRM** in the confirmation security challenge prompt.',
                '5' => 'Click **"Execute Safe Purge"**. Your website is now sparkling clean and ready for real guests!'
            ],
            'tips' => ['💡 Safe Purge preserves your villas, photos, food menu, and bank account settings intact!'],
            'step_doubts' => ['3' => 'Do NOT click "Full Factory Reset" unless you wish to reset every custom setting back to default.'],
            'follow_up_doubts' => [
                "Will purging demo data delete my villas or food menu?",
                "Is it possible to undo a demo data purge?",
                "Should I backup the database before clearing data?"
            ]
        ],
        'ml' => [
            'title' => 'ഡെമോ ബുക്കിംഗുകൾ സുരക്ഷിതമായി ഒഴിവാക്കുക',
            'card' => 'കാർഡ് 08 (സിസ്റ്റം & ഡാറ്റാബേസ് കൺട്രോൾ)',
            'direct_label' => 'സിസ്റ്റം മെയിന്റനൻസ് തുറക്കുക',
            'summary' => 'സൈറ്റ് ലോഞ്ച് ചെയ്യുന്നതിന് മുൻപ് വില്ലകളും ഫോട്ടോകളും നഷ്ടപ്പെടാതെ ടെസ്റ്റിംഗ് ബുക്കിംഗുകളും ഓർഡറുകളും മാത്രം സുരക്ഷിതമായി ഒഴിവാക്കാൻ **Card 08** ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => '**Estate Settings** തുറന്ന് താഴെയുള്ള **Card 08 (System Operations & Maintenance)**-ലേക്ക് പോകുക.',
                '2' => '**"Reset Demo Data"** എന്ന വിഭാഗം തിരഞ്ഞെടുക്കുക.',
                '3' => '**"Purge Test Bookings & Kitchen Orders Only"** ക്ലിക്ക് ചെയ്യുക (ലോഞ്ചിനായി ഇത് തിരഞ്ഞെടുക്കുക).',
                '4' => 'സുരക്ഷാ ബോക്സിൽ **CONFIRM** എന്ന് ടൈപ്പ് ചെയ്യുക.',
                '5' => '**"Execute Safe Purge"** ബട്ടൺ അമർത്തുക. നിങ്ങളുടെ സൈറ്റ് യഥാർത്ഥ അതിഥികൾക്കായി പൂർണ്ണ സജ്ജമാകും!'
            ],
            'tips' => ['💡 ഈ പ്രക്രിയ നിങ്ങളുടെ വില്ലകൾ, ഭക്ഷണ മെനു, ബാങ്ക് വിവരങ്ങൾ എന്നിവയെ ബാധിക്കില്ല!'],
            'step_doubts' => ['3' => 'എല്ലാ വിവരങ്ങളും മാറ്റാൻ ആഗ്രഹിക്കുന്നില്ലെങ്കിൽ "Full Factory Reset" ക്ലിക്ക് ചെയ്യരുത്.'],
            'follow_up_doubts' => [
                "ഡെമോ ഡാറ്റ ഒഴിവാക്കിയാൽ വില്ലകളോ ഫോട്ടോകളോ നഷ്ടപ്പെടുമോ?",
                "ഡെമോ ഡാറ്റ നീക്കം ചെയ്തത് തിരിച്ചെടുക്കാൻ സാധിക്കുമോ?",
                "ഇതിന് മുൻപ് ഡാറ്റാബേസ് ബാക്കപ്പ് എടുക്കേണ്ടതുണ്ടോ?"
            ]
        ],
        'ta' => [
            'title' => 'மாதிரி முன்பதிவுகளை பாதுகாப்பாக நீக்குதல்',
            'card' => 'கார்டு 08 (கணினி & தரவுத்தள கட்டுப்பாடு)',
            'direct_label' => 'கணினி பராமரிப்பைத் திற',
            'summary' => 'தளத்தை துவங்குவதற்கு முன் மாதிரி முன்பதிவுகள் மற்றும் உணவு ஆர்டர்களை மட்டும் நீக்க **Card 08**-ஐ பயன்படுத்தலாம்.',
            'steps' => [
                '1' => '**Estate Settings** சென்று கீழே உள்ள **Card 08**-ஐ திறக்கவும்.',
                '2' => '**"Reset Demo Data"** பிரிவுக்கு செல்லவும்.',
                '3' => '**"Purge Test Bookings & Kitchen Orders Only"** என்பதை தேர்ந்தெடுக்கவும்.',
                '4' => 'பாதுகாப்பு பெட்டியில் **CONFIRM** என தட்டச்சு செய்யவும்.',
                '5' => '**"Execute Safe Purge"** கிளிக் செய்யவும்.'
            ],
            'tips' => ['💡 இது வில்லாக்கள், மெனு, வங்கி அமைப்புகளை அழிக்காது!'],
            'step_doubts' => ['3' => 'முழு அமைப்புகளையும் அழிக்க விரும்பினால் மட்டுமே Factory Reset பயன்படுத்த வேண்டும்.'],
            'follow_up_doubts' => ["மாதிரி தரவை நீக்கினால் வில்லாக்கள் அழியுமா?"]
        ]
    ],

    // 19. PURGE CACHE
    'purge_cache' => [
        'id' => 'purge_cache',
        'direct_url' => 'javascript:if(window.purgeServerCache)purgeServerCache();',
        'keywords' => [
            'purge cache', 'clear cache', 'refresh site', 'new photos not showing', 'old price showing', 'cache flush',
            'കാഷെ ക്ലിയർ', 'പർജ്ജ് കാഷെ', 'ഫോട്ടോ കാണുന്നില്ല', 'പഴയ വില കാണിക്കുന്നു', 'സൈറ്റ് റിഫ്രഷ്',
            'cache clear cheyyaan', 'photo kaanunnilla', 'கேச் அழித்தல்', 'தற்காலிக சேமிப்பு நீக்கம்'
        ],
        'en' => [
            'title' => 'Purge Server Cache & Asset Refresh',
            'card' => 'Topbar Instant Action',
            'direct_label' => 'Purge Server Cache Now',
            'summary' => 'Instantly flush compiled schemas, query caches, and bust asset versions so visitors immediately see new photos and tariffs.',
            'steps' => [
                '1' => 'Look at the **Top Header Bar** on any admin page.',
                '2' => 'Click the golden **"Purge Cache"** button featuring the lightning bolt icon.',
                '3' => 'Click "OK" on the confirmation prompt.',
                '4' => 'Refresh your public website (`Ctrl+F5`) to witness the updated content instantly!'
            ],
            'tips' => ['💡 Always click Purge Cache after changing tariffs or uploading photos.'],
            'step_doubts' => ['2' => 'The button is accessible at the top of every screen in the admin dashboard.'],
            'follow_up_doubts' => [
                "Why do uploaded photos sometimes show old versions?",
                "How often should I purge the cache?",
                "Does purging cache log out active guests?"
            ]
        ],
        'ml' => [
            'title' => 'സെർവർ കാഷെ പൂർണ്ണമായി ക്ലിയർ ചെയ്യുക',
            'card' => 'ടോപ്പ്ബാർ തത്സമയ ആക്ഷൻ',
            'direct_label' => 'ഇപ്പോൾ കാഷെ ക്ലിയർ ചെയ്യുക',
            'summary' => 'പുതിയ ഫോട്ടോകളും നിരക്കുകളും സന്ദർശകർക്ക് ഉടൻ ലഭ്യമാകാൻ സെർവർ കാഷെ ക്ലിയർ ചെയ്യുക.',
            'steps' => [
                '1' => 'അഡ്മിൻ പാനലിലെ മുകളിലുള്ള ടോപ്പ് ഹെഡർ ബാർ ശ്രദ്ധിക്കുക.',
                '2' => 'ഇടിമിന്നൽ ഐക്കണുള്ള ഗോൾഡൻ **"Purge Cache"** ബട്ടൺ ക്ലിക്ക് ചെയ്യുക.',
                '3' => 'സ്ഥിരീകരണ പോപ്പ്-അപ്പിൽ "OK" നൽകുക.',
                '4' => 'നിങ്ങളുടെ വെബ്‌സൈറ്റ് റീഫ്രഷ് (`Ctrl+F5`) ചെയ്താൽ പുതിയ മാറ്റങ്ങൾ ഉടൻ കാണാം!'
            ],
            'tips' => ['💡 പുതിയ ഫോട്ടോകൾ അപ്‌ലോഡ് ചെയ്ത ശേഷമോ നിരക്കുകൾ മാറ്റിയ ശേഷമോ എപ്പോഴും Purge Cache അമർത്തുക.'],
            'step_doubts' => ['2' => 'ഈ ബട്ടൺ എല്ലാ അഡ്മിൻ പേജുകളുടെയും മുകളിൽ എപ്പോഴും ലഭ്യമാണ്.'],
            'follow_up_doubts' => [
                "അപ്‌ലോഡ് ചെയ്ത ഫോട്ടോകൾ പഴയതായി കാണിക്കുന്നത് എന്തുകൊണ്ട്?",
                "എപ്പോഴൊക്കെ കാഷെ ക്ലിയർ ചെയ്യണം?",
                "ഇത് അതിഥികളുടെ ബുക്കിംഗുകളെ ബാധിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'தற்காலிக நினைவகத்தை (Cache) அழித்தல்',
            'card' => 'மேல் பட்டி உடனடி நடவடிக்கை',
            'direct_label' => 'இப்போதே Cache-ஐ அழிக்கவும்',
            'summary' => 'புதிய படங்கள் மற்றும் கட்டணங்கள் வாடிக்கையாளர்களுக்கு உடனே தெரிய தற்காலிக நினைவகத்தை அழிக்கவும்.',
            'steps' => [
                '1' => 'அட்மின் பக்கத்தின் மேல் உள்ள பட்டியை கவனிக்கவும்.',
                '2' => 'மின்னல் குறியீட்டுடன் உள்ள **"Purge Cache"** பொத்தானை கிளிக் செய்யவும்.',
                '3' => 'உறுதிப்படுத்தல் பெட்டியில் "OK" கொடுக்கவும்.',
                '4' => 'இணையதளத்தை புதுப்பித்து (`Ctrl+F5`) புதிய உள்ளடக்கத்தை உடனே பார்க்கலாம்!'
            ],
            'tips' => ['💡 புதிய படங்கள் அல்லது கட்டணங்களை மாற்றிய பின் எப்போதும் Purge Cache செய்யவும்.'],
            'step_doubts' => ['2' => 'இந்த பொத்தான் அனைத்து அட்மின் பக்கங்களின் மேலேயும் உள்ளது.'],
            'follow_up_doubts' => ["புதிய படங்கள் பழைய படங்களாகவே தெரிவது ஏன்?"]
        ]
    ],

    // 20. ADMIN PASSWORDS & PROFILE
    'profile' => [
        'id' => 'profile',
        'direct_url' => 'javascript:if(window.openProfileModal)openProfileModal();',
        'keywords' => [
            'password', 'change password', 'username', 'admin user', 'create admin', 'avatar', 'profile photo', 'security',
            'പാസ്‌വേഡ്', 'പാസ്‌വേഡ് മാറ്റുക', 'യൂസർ നെയിം', 'അഡ്മിൻ അക്കൗണ്ട്', 'പ്രൊഫൈൽ ഫോട്ടോ', 'സുരക്ഷ',
            'password engane maataam', 'puthiya admin', 'കടவுச்சொல்', 'கடவுச்சொல் மாற்றம்', 'பயனர் பெயர்'
        ],
        'en' => [
            'title' => 'Admin Credentials, Passwords & User Management',
            'card' => 'Profile & User Management Modal',
            'direct_label' => 'Open Profile & Users Modal',
            'summary' => 'Update administrator passwords, avatars, or add new staff accounts in the **Credentials & User Management Modal**.',
            'steps' => [
                '1' => 'Click your **User Avatar / Name** in the top-right header or bottom-left sidebar.',
                '2' => 'Select **"Account Settings & Profile"** to open the modal.',
                '3' => 'Under **"My Profile & Security"**, enter your **New Password** to update login credentials.',
                '4' => 'Under **"All Users & Admins"** (Super Admin only), click **"+ Add New Administrator"** to create staff accounts.',
                '5' => 'Click **"Save Profile & Security"**.'
            ],
            'tips' => ['💡 Always use a strong password with at least 8 characters including letters and numbers.'],
            'step_doubts' => ['3' => 'Leave the new password field empty if you only want to update your name or profile avatar.'],
            'follow_up_doubts' => [
                "How do I add a new admin account for front desk staff?",
                "What if I forget my admin password?",
                "Can other admins delete my account?"
            ]
        ],
        'ml' => [
            'title' => 'അഡ്മിൻ പാസ്‌വേഡ് & ഉപയോക്തൃ അക്കൗണ്ട് മാനേജ്മെന്റ്',
            'card' => 'പ്രൊഫൈൽ & യൂസർ മാനേജ്മെന്റ് മോഡൽ',
            'direct_label' => 'പ്രൊഫൈൽ മോഡൽ തുറക്കുക',
            'summary' => 'അഡ്മിൻ പാസ്‌വേഡ് മാറ്റാനും, പ്രൊഫൈൽ ഫോട്ടോ അപ്‌ഡേറ്റ് ചെയ്യാനും, പുതിയ ജീവനക്കാർക്കായി അഡ്മിൻ അക്കൗണ്ടുകൾ ഉണ്ടാക്കാനും ഈ മോഡൽ ഉപയോഗിക്കാം.',
            'steps' => [
                '1' => 'മുകളിൽ വലതുവശത്തോ താഴെ ഇടതുവശത്തോ ഉള്ള നിങ്ങളുടെ **പേര് / ഫോട്ടോ** ക്ലിക്ക് ചെയ്യുക.',
                '2' => '**"Account Settings & Profile"** തിരഞ്ഞെടുക്കുക.',
                '3' => '**"My Profile & Security"** എന്നതിൽ പുതിയ പാസ്‌വേഡ് നൽകുക.',
                '4' => 'സൂപ്പർ അഡ്മിന്മാർക്ക് പുതിയ ജീവനക്കാരെ ചേർക്കാൻ **"+ Add New Administrator"** ഉപയോഗിക്കാം.',
                '5' => '**"Save Profile & Security"** ക്ലിക്ക് ചെയ്ത് മാറ്റങ്ങൾ സേവ് ചെയ്യുക.'
            ],
            'tips' => ['💡 സുരക്ഷയ്ക്കായി കുറഞ്ഞത് 8 അക്ഷരങ്ങളും അക്കങ്ങളും അടങ്ങിയ ശക്തമായ പാസ്‌വേഡ് ഉപയോഗിക്കുക.'],
            'step_doubts' => ['3' => 'പേര് അല്ലെങ്കിൽ ഫോട്ടോ മാത്രം മാറ്റണമെങ്കിൽ പുതിയ പാസ്‌വേഡ് ബോക്സ് കാലിയായി ഇടുക.'],
            'follow_up_doubts' => [
                "ഫ്രണ്ട് ഡെസ്ക് സ്റ്റാഫിനായി പുതിയ അക്കൗണ്ട് എങ്ങനെ ഉണ്ടാക്കാം?",
                "പാസ്‌വേഡ് മറന്നുപോയാൽ എന്തുചെയ്യണം?",
                "മറ്റ് അഡ്മിന്മാർക്ക് എന്റെ അക്കൗണ്ട് നീക്കം ചെയ്യാൻ സാധിക്കുമോ?"
            ]
        ],
        'ta' => [
            'title' => 'நிர்வாக கடவுச்சொல் & பயனர் மேலாண்மை',
            'card' => 'சுயவிவரம் & பயனர் மேலாண்மை',
            'direct_label' => 'சுயவிவர அமைப்புகளைத் திற',
            'summary' => 'நிர்வாக கடவுச்சொல்லை மாற்றவும், புதிய பணியாளர் கணக்குகளை சேர்க்கவும் இந்த பகுதியை பயன்படுத்தலாம்.',
            'steps' => [
                '1' => 'மேல் வலதுபுறத்தில் உள்ள உங்கள் **பெயர் / படத்தை** கிளிக் செய்யவும்.',
                '2' => '**"Account Settings & Profile"** தேர்ந்தெடுக்கவும்.',
                '3' => '**"My Profile & Security"** பிரிவில் புதிய கடவுச்சொல்லை உள்ளிடவும்.',
                '4' => 'புதிய ஊழியர்களை சேர்க்க **"+ Add New Administrator"** கிளிக் செய்யவும்.',
                '5' => '**"Save Profile & Security"** கிளிக் செய்து சேமிக்கவும்.'
            ],
            'tips' => ['💡 குறைந்தபட்சம் 8 எழுத்துக்கள் மற்றும் எண்களைக் கொண்ட வலுவான கடவுச்சொல்லைப் பயன்படுத்தவும்.'],
            'step_doubts' => ['3' => 'பெயரை மட்டும் மாற்ற விரும்பினால் கடவுச்சொல் பெட்டியை காலியாக விடவும்.'],
            'follow_up_doubts' => [
                "பணியாளர்களுக்கு புதிய கணக்கை உருவாக்குவது எப்படி?",
                "கடவுச்சொல்லை மறந்துவிட்டால் என்ன செய்வது?"
            ]
        ]
    ]
];

// -------------------------------------------------------------------------
// STEP DOUBT CLARIFICATION MATCHING
// -------------------------------------------------------------------------
$is_step_doubt = false;
$target_step = 0;

if (!empty($current_topic) && isset($kb[$current_topic])) {
    // English step numbers
    if (preg_match('/(?:step|point|number|stpe)\s*([1-9])/i', $query, $matches)) {
        $is_step_doubt = true;
        $target_step = (int)$matches[1];
    }
    // Malayalam step numbers
    elseif (preg_match('/(?:സ്റ്റെപ്പ്|സ്റ്റെപ്|ഘട്ടം)\s*([1-9])/u', $query, $matches)) {
        $is_step_doubt = true;
        $target_step = (int)$matches[1];
    }
    // Tamil step numbers
    elseif (preg_match('/(?:படி)\s*([1-9])/u', $query, $matches)) {
        $is_step_doubt = true;
        $target_step = (int)$matches[1];
    }
    // Word numbers
    elseif (preg_match('/(?:first|1st|ഒന്നാമത്തെ|ഒന്നാം|முதல்)/ui', $query)) {
        $is_step_doubt = true;
        $target_step = 1;
    } elseif (preg_match('/(?:second|2nd|രണ്ടാമത്തെ|രണ്ടാം|இரண்டாவது|இரண்டாம்)/ui', $query)) {
        $is_step_doubt = true;
        $target_step = 2;
    } elseif (preg_match('/(?:third|3rd|മൂന്നാമത്തെ|മൂന്നാം|மூன்றாவது|மூன்றாம்)/ui', $query)) {
        $is_step_doubt = true;
        $target_step = 3;
    } elseif (preg_match('/(?:fourth|4th|നാലാമത്തെ|നാലാം|நான்காவது|நான்காம்)/ui', $query)) {
        $is_step_doubt = true;
        $target_step = 4;
    } elseif (preg_match('/(?:fifth|5th|അഞ്ചാമത്തെ|അഞ്ചാം|ஐந்தாவது|ஐந்தாம்)/ui', $query)) {
        $is_step_doubt = true;
        $target_step = 5;
    }
}

if ($is_step_doubt && $target_step > 0 && isset($kb[$current_topic])) {
    $topic = $kb[$current_topic];
    $pkg = $topic[$lang] ?? $topic['en'];
    $step_str = (string)$target_step;
    $step_text = $pkg['steps'][$step_str] ?? ($topic['en']['steps'][$step_str] ?? '');
    $step_explanation = $pkg['step_doubts'][$step_str] ?? ($topic['en']['step_doubts'][$step_str] ?? "Here is the detailed procedure for Step {$target_step}: {$step_text}");

    $badge_titles = [
        'en' => "Doubt Clarification: Step {$target_step} of {$pkg['title']}",
        'ml' => "സംശയ നിവാരണം: ഘട്ടം {$target_step} — {$pkg['title']}",
        'ta' => "சந்தேக விளக்கம்: படி {$target_step} — {$pkg['title']}"
    ];
    $header_titles = [
        'en' => "Clarification for Step {$target_step}",
        'ml' => "ഘട്ടം {$target_step} വിശദീകരണം",
        'ta' => "படி {$target_step} விளக்கம்"
    ];
    $orig_labels = [
        'en' => "Original Step {$target_step}:",
        'ml' => "യഥാർത്ഥ ഘട്ടം {$target_step}:",
        'ta' => "அசல் படி {$target_step}:"
    ];

    $reply_html = "<div class='adm-bot-answer'>";
    $reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-circle-question'></i> " . htmlspecialchars($badge_titles[$lang] ?? $badge_titles['en']) . "</div>";
    $reply_html .= "<h4 class='adm-bot-title'>" . htmlspecialchars($header_titles[$lang] ?? $header_titles['en']) . "</h4>";
    $reply_html .= "<div class='adm-bot-card-highlight' style='margin: 10px 0; padding: 12px 16px; background: rgba(197, 160, 89, 0.1); border-left: 3px solid var(--adm-gold); border-radius: 6px;'>";
    $reply_html .= "<strong>" . ($orig_labels[$lang] ?? $orig_labels['en']) . "</strong> {$step_text}";
    $reply_html .= "</div>";
    $reply_html .= "<div class='adm-bot-desc' style='line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary); margin-bottom: 14px;'>";
    $reply_html .= $step_explanation;
    $reply_html .= "</div>";

    $direct_label = $pkg['direct_label'] ?? $topic['en']['direct_label'];
    $reply_html .= "<div class='adm-bot-actions' style='margin-top: 14px;'>";
    $reply_html .= "<a href='{$topic['direct_url']}' class='adm-btn-action gold' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding: 8px 16px; font-size: 12.5px;'>";
    $reply_html .= "<i class='fa-solid fa-arrow-up-right-from-square'></i> <span>{$direct_label}</span>";
    $reply_html .= "</a>";
    $reply_html .= "</div>";
    $reply_html .= "</div>";

    echo json_encode([
        'success' => true,
        'lang' => $lang,
        'topic' => $current_topic,
        'reply' => $reply_html,
        'follow_up_doubts' => $pkg['follow_up_doubts'] ?? []
    ]);
    exit;
}

// -------------------------------------------------------------------------
// TOPIC MATCHING ENGINE
// -------------------------------------------------------------------------
$best_match = null;
$best_score = 0;

foreach ($kb as $key => $item) {
    $score = 0;
    foreach ($item['keywords'] as $kw) {
        $kw_norm = normalize_text($kw);
        if (empty($kw_norm)) continue;

        $kw_words = explode(' ', $kw_norm);
        $num_words = count($kw_words);

        // 1. Exact full query match
        if ($normalized === $kw_norm) {
            $score += 150;
        }
        // 2. Exact phrase match inside query
        elseif (mb_strpos($normalized, $kw_norm, 0, 'UTF-8') !== false) {
            $score += ($num_words > 1) ? (60 + ($num_words * 30)) : (20 + (mb_strlen($kw_norm, 'UTF-8') * 2));
        }
        // 3. Multi-word all individual words present in query
        elseif ($num_words > 1) {
            $all_present = true;
            foreach ($kw_words as $w) {
                if (mb_strlen($w, 'UTF-8') > 1 && mb_strpos($normalized, $w, 0, 'UTF-8') === false) {
                    $all_present = false;
                    break;
                }
            }
            if ($all_present) {
                $score += 40 + ($num_words * 15);
            }
        }
    }

    // Boost score if this was the current active topic
    if (!empty($current_topic) && $key === $current_topic) {
        $score += 12;
    }

    if ($score > $best_score) {
        $best_score = $score;
        $best_match = $item;
    }
}

// -------------------------------------------------------------------------
// GREETING OR NO-MATCH FALLBACK
// -------------------------------------------------------------------------
if ($best_score < 10) {
    // Check for greetings in English, Malayalam, and Tamil
    $is_greeting = preg_match('/^(hi|hello|hey|namaste|vanakkam|namaskaram|നമസ്കാരം|വണക്കം|வணக்கம்|help|options)\b/ui', $normalized);

    if ($is_greeting) {
        $welcome_titles = [
            'en' => "Welcome to Food Forest Admin Assistant! 🌿",
            'ml' => "ഫുഡ് ഫോറസ്റ്റ് അഡ്മിൻ സഹായിയിലേക്ക് സ്വാഗതം! 🌿",
            'ta' => "ஃபுட் ஃபாரஸ்ட் நிர்வாகி உதவியாளருக்கு நல்வரவு! 🌿"
        ];
        $welcome_paras = [
            'en' => "I am here to guide you step-by-step through any setting or task in this admin console, and resolve any doubts about each step. What would you like to do?",
            'ml' => "അഡ്മിൻ പാനലിലെ ഏത് സെറ്റിംഗ്സും ക്രമീകരിക്കാനും സംശയങ്ങൾ ദൂരീകരിക്കാനും ഞാൻ സഹായിക്കാം. എന്താണ് ക്രമീകരിക്കേണ്ടത്?",
            'ta' => "இந்த நிர்வாக குழுவில் உள்ள எந்தவொரு அமைப்பையும் மாற்றவும் சந்தேகங்களை தெளிவுபடுத்தவும் நான் தயாராக உள்ளேன். நீங்கள் என்ன செய்ய விரும்புகிறீர்கள்?"
        ];
        $welcome_heads = [
            'en' => "Popular Inquiries & Setup Guides:",
            'ml' => "പ്രധാന സെറ്റിംഗ്സുകളും സഹായികളും:",
            'ta' => "முக்கிய அமைப்புகள் மற்றும் வழிகாட்டிகள்:"
        ];
        $items_list = [
            'en' => [
                "Tariffs & Rooms" => "How to edit villa prices, base guests, and extra person rates.",
                "Bank & UPI QR" => "How to add bank details and QR codes for guest billing.",
                "GST Settings" => "Multi-tier 5% Food and 12% Room tax setup.",
                "Billing & Invoices" => "How to generate and print luxury A4 folios.",
                "Calendar & Airbnb" => "Date blocking and 2-way Airbnb iCal sync."
            ],
            'ml' => [
                "വില്ല വാടക നിരക്കുകൾ" => "റൂം വാടകയും അധിക അതിഥി നിരക്കുകളും മാറ്റുക.",
                "ബാങ്ക് & UPI QR" => "ബില്ലിംഗിനായി ബാങ്ക് വിവരങ്ങളും QR കോഡും നൽകുക.",
                "ജിഎസ്ടി നികുതി" => "ഭക്ഷണത്തിന് 5%, റൂമിന് 12% നികുതി ക്രമീകരണം.",
                "ബില്ലിംഗ് & ഇൻവോയ്സ്" => "ആഡംബര A4 ടാക്സ് ഇൻവോയ്സ് പ്രിന്റ് ചെയ്യുക.",
                "കലണ്ടർ & Airbnb" => "തീയതി ബ്ലോക്കിംഗും iCal സിങ്കും."
            ],
            'ta' => [
                "வில்லா கட்டணங்கள்" => "அறை வாடகை மற்றும் கூடுதல் நபர் கட்டணம் மாற்றுதல்.",
                "வங்கி & UPI QR" => "பில்லிங்கிற்கு வங்கி விவரங்கள் மற்றும் QR குறியீடு அமைத்தல்.",
                "ஜிஎஸ்டி வரி" => "உணவுக்கு 5%, அறைக்கு 12% வரி கட்டமைப்பு.",
                "பில்லிங் & விலைப்பட்டியல்" => "A4 விலைப்பட்டியல் அச்சிடுதல்.",
                "நாட்காட்டி & Airbnb" => "தேதி தடுப்பு மற்றும் iCal இணைப்பு."
            ]
        ];
        $active_list = $items_list[$lang] ?? $items_list['en'];

        $reply_html = "<div class='adm-bot-answer'>";
        $reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-seedling'></i> Sanctuary Concierge Bot</div>";
        $reply_html .= "<h4 class='adm-bot-title'>" . ($welcome_titles[$lang] ?? $welcome_titles['en']) . "</h4>";
        $reply_html .= "<p style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 12px;'>" . ($welcome_paras[$lang] ?? $welcome_paras['en']) . "</p>";
        $reply_html .= "<div style='font-size: 12.5px; font-weight: 700; color: var(--adm-gold); margin-bottom: 8px;'>" . ($welcome_heads[$lang] ?? $welcome_heads['en']) . "</div>";
        $reply_html .= "<ul style='padding-left: 20px; font-size: 13px; line-height: 1.7; color: var(--adm-text-secondary); margin-bottom: 12px;'>";
        foreach ($active_list as $lh => $ld) {
            $reply_html .= "<li><strong>{$lh}:</strong> {$ld}</li>";
        }
        $reply_html .= "</ul>";
        $reply_html .= "</div>";

        $starter_doubts = [
            'en' => ["How do I change villa tariffs?", "How to set up Bank & UPI QR code?", "How does guest billing and invoicing work?", "How to sync calendar with Airbnb?"],
            'ml' => ["വില്ല വാടക എങ്ങനെ മാറ്റാം?", "ബാങ്ക് വിവരങ്ങളും UPI QR കോഡും എങ്ങനെ നൽകാം?", "ഗസ്റ്റ് ബില്ലിംഗും ഇൻവോയ്സും എങ്ങനെ പ്രവർത്തിക്കുന്നു?", "Airbnb കലണ്ടർ എങ്ങനെ സിങ്ക് ചെയ്യാം?"],
            'ta' => ["வில்லா கட்டணத்தை மாற்றுவது எப்படி?", "வங்கி & UPI QR குறியீட்டை அமைப்பது எப்படி?", "பில்லிங் மற்றும் விலைப்பட்டியல் எவ்வாறு செயல்படுகிறது?", "Airbnb நாட்காட்டியை எவ்வாறு இணைப்பது?"]
        ];

        echo json_encode([
            'success' => true,
            'lang' => $lang,
            'topic' => '',
            'reply' => $reply_html,
            'follow_up_doubts' => $starter_doubts[$lang] ?? $starter_doubts['en']
        ]);
        exit;
    }

    // Fallback response with helpful clarification prompt
    $fallback_titles = [
        'en' => "I want to ensure you get the exact procedure!",
        'ml' => "കൃത്യമായ നടപടിക്രമം ലഭ്യമാക്കാൻ സഹായിക്കാം!",
        'ta' => "சரியான நடைமுறையை உங்களுக்கு வழங்க விரும்புகிறேன்!"
    ];
    $fallback_paras = [
        'en' => "Could you clarify what you'd like to set up? You can ask about changing room rates, uploading bank details/QR code, billing and GST, food menu, calendar date blocking, or clearing demo data.",
        'ml' => "ഏത് സെറ്റിംഗ്സ് അല്ലെങ്കിൽ ഫീച്ചറാണ് മാറ്റേണ്ടതെന്ന് വ്യക്തമാക്കാമോ? വില്ല വാടക, ബാങ്ക് വിവരങ്ങൾ, ജിഎസ്ടി, ഫുഡ് മെനു, കലണ്ടർ തീയതി ബ്ലോക്കിംഗ് എന്നിവയെക്കുറിച്ച് ചോദിക്കാം.",
        'ta' => "நீங்கள் எதை அமைக்க விரும்புகிறீர்கள் என்பதை தெளிவுபடுத்த முடியுமா? அறை வாடகை மாற்றம், வங்கி விவரங்கள்/QR குறியீடு, பில்லிங் மற்றும் ஜிஎஸ்டி, உணவு பட்டியல் அல்லது காலண்டர் தடுப்பு பற்றி கேட்கலாம்."
    ];
    $fallback_hints = [
        'en' => "💡 <em>Tip: You can ask in English, Malayalam (മലയാളം), or Tamil (தமிழ்).</em>",
        'ml' => "💡 <em>സൂചന: ഇംഗ്ലീഷിലോ മലയാളത്തിലോ മംഗ്ലീഷിലോ ചോദിക്കാവുന്നതാണ്.</em>",
        'ta' => "💡 <em>குறிப்பு: ஆங்கிலம், தமிழ் அல்லது தங்க்லீஷில் கேட்கலாம்.</em>"
    ];

    $reply_html = "<div class='adm-bot-answer'>";
    $reply_html .= "<div class='adm-bot-badge red'><i class='fa-solid fa-circle-info'></i> Query Helpdesk</div>";
    $reply_html .= "<h4 class='adm-bot-title'>" . ($fallback_titles[$lang] ?? $fallback_titles['en']) . "</h4>";
    $reply_html .= "<p style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 12px;'>" . ($fallback_paras[$lang] ?? $fallback_paras['en']) . "</p>";
    $reply_html .= "<p style='font-size: 12.5px; color: var(--adm-text-muted); margin: 0;'>" . ($fallback_hints[$lang] ?? $fallback_hints['en']) . "</p>";
    $reply_html .= "</div>";

    $fallback_doubts = [
        'en' => ["How do I change villa tariffs?", "How to set up Bank & UPI QR code?", "How does guest billing and invoicing work?", "How to sync calendar with Airbnb?"],
        'ml' => ["വില്ല വാടക എങ്ങനെ മാറ്റാം?", "ബാങ്ക് വിവരങ്ങളും UPI QR കോഡും എങ്ങനെ നൽകാം?", "ഗസ്റ്റ് ബില്ലിംഗും ഇൻവോയ്സും എങ്ങനെ പ്രവർത്തിക്കുന്നു?", "Airbnb കലണ്ടർ എങ്ങനെ സിങ്ക് ചെയ്യാം?"],
        'ta' => ["வில்லா கட்டணத்தை மாற்றுவது எப்படி?", "வங்கி & UPI QR குறியீட்டை அமைப்பது எப்படி?", "பில்லிங் மற்றும் விலைப்பட்டியல் எவ்வாறு செயல்படுகிறது?", "Airbnb நாட்காட்டியை எவ்வாறு இணைப்பது?"]
    ];

    echo json_encode([
        'success' => true,
        'lang' => $lang,
        'topic' => '',
        'reply' => $reply_html,
        'follow_up_doubts' => $fallback_doubts[$lang] ?? $fallback_doubts['en']
    ]);
    exit;
}

// -------------------------------------------------------------------------
// SUCCESSFUL TOPIC MATCH RESPONSE BUILDER (EN, ML, TA)
// -------------------------------------------------------------------------
$item = $best_match;
$pkg = $item[$lang] ?? $item['en'];

$reply_html = "<div class='adm-bot-answer'>";
$reply_html .= "<div class='adm-bot-badge'><i class='fa-solid fa-compass'></i> " . htmlspecialchars($pkg['card'] ?? $item['en']['card']) . "</div>";
$reply_html .= "<h4 class='adm-bot-title'>" . htmlspecialchars($pkg['title'] ?? $item['en']['title']) . "</h4>";
$reply_html .= "<p class='adm-bot-summary' style='font-size: 13.5px; line-height: 1.6; color: var(--adm-text-secondary); margin-bottom: 14px;'>{$pkg['summary']}</p>";

// Direct Jump Shortcut Button
$direct_label = $pkg['direct_label'] ?? $item['en']['direct_label'];
if (!empty($item['direct_url']) && !empty($direct_label)) {
    $reply_html .= "<div style='margin-bottom: 16px;'>";
    $reply_html .= "<a href='{$item['direct_url']}' class='adm-btn-action gold' style='text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding: 7px 16px; font-size: 12.5px;'>";
    $reply_html .= "<i class='fa-solid fa-arrow-up-right-from-square'></i> <span>{$direct_label}</span>";
    $reply_html .= "</a>";
    $reply_html .= "</div>";
}

// Step-by-Step Procedure
$step_headings = [
    'en' => "Step-by-Step Procedure:",
    'ml' => "ഘട്ടം ഘട്ടമായുള്ള നടപടിക്രമം:",
    'ta' => "படிப்படியான செயல்முறை:"
];
$reply_html .= "<div class='adm-bot-steps-wrapper' style='background: rgba(16, 31, 21, 0.5); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px; margin-bottom: 16px;'>";
$reply_html .= "<div style='font-size: 12.5px; font-weight: 700; color: #FFFFFF; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; display:flex; align-items:center; gap:8px;'>";
$reply_html .= "<i class='fa-solid fa-list-ol' style='color: var(--adm-gold);'></i> " . ($step_headings[$lang] ?? $step_headings['en']);
$reply_html .= "</div>";
$reply_html .= "<ol class='adm-bot-steps-list' style='padding-left: 20px; margin: 0; line-height: 1.7; font-size: 13px; color: var(--adm-text-secondary);'>";
$steps = $pkg['steps'] ?? $item['en']['steps'];
foreach ($steps as $step_idx => $step_desc) {
    $reply_html .= "<li style='margin-bottom: 8px;'>{$step_desc}</li>";
}
$reply_html .= "</ol>";
$reply_html .= "</div>";

// Tips and Cautions
$tips = $pkg['tips'] ?? $item['en']['tips'];
if (!empty($tips)) {
    $reply_html .= "<div class='adm-bot-tips' style='margin-bottom: 16px; display:flex; flex-direction:column; gap:8px;'>";
    foreach ($tips as $tip) {
        $reply_html .= "<div style='font-size: 12.5px; line-height: 1.5; padding: 10px 14px; background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--adm-gold); border-radius: 4px; color: #E2E8F0;'>{$tip}</div>";
    }
    $reply_html .= "</div>";
}

// Ask doubts about these steps prompt
$doubt_prompts = [
    'en' => "Have doubts about any of these steps?",
    'ml' => "ഈ ഘട്ടങ്ങളെക്കുറിച്ച് എന്തെങ്കിലും സംശയങ്ങളുണ്ടോ?",
    'ta' => "இந்த படிகள் பற்றி ஏதேனும் சந்தேகங்கள் உள்ளதா?"
];
$doubt_subs = [
    'en' => "Click any question below or type your doubt directly (e.g. <em>'explain step 2'</em> or <em>'what is base guests?'</em>):",
    'ml' => "താഴെയുള്ള ചോദ്യത്തിൽ ക്ലിക്ക് ചെയ്യുക അല്ലെങ്കിൽ നിങ്ങളുടെ സംശയം നേരിട്ട് ടൈപ്പ് ചെയ്യുക (ഉദാ: <em>'സ്റ്റെപ്പ് 2 വിശദീകരിക്കൂ'</em>):",
    'ta' => "கீழே உள்ள கேள்வியைக் கிளிக் செய்யவும் அல்லது உங்கள் சந்தேகத்தை நேரடியாக உள்ளிடவும் (எ.கா: <em>'படி 2 விளக்கம்'</em>):"
];

$reply_html .= "<div style='font-size: 12px; font-weight: 700; color: var(--adm-gold); margin-bottom: 4px; display:flex; align-items:center; gap:6px;'>";
$reply_html .= "<i class='fa-solid fa-circle-question'></i> " . ($doubt_prompts[$lang] ?? $doubt_prompts['en']);
$reply_html .= "</div>";
$reply_html .= "<div style='font-size: 12px; color: var(--adm-text-muted); margin-bottom: 10px;'>" . ($doubt_subs[$lang] ?? $doubt_subs['en']) . "</div>";
$reply_html .= "</div>";

echo json_encode([
    'success' => true,
    'lang' => $lang,
    'topic' => $item['id'],
    'reply' => $reply_html,
    'follow_up_doubts' => $pkg['follow_up_doubts'] ?? ($item['en']['follow_up_doubts'] ?? [])
]);
exit;
