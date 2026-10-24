<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/country_codes.php';
require_once __DIR__ . '/countries_data.php';

// Fallback to India if no key set
if (isset($CURRENT_COUNTRY_KEY) && is_string($CURRENT_COUNTRY_KEY)) {
    $country_key = $CURRENT_COUNTRY_KEY;
} elseif (!isset($country_key) || !is_string($country_key)) {
    $country_key = 'india';
}

$country = get_country_data($country_key);
if (!$country) {
    $country = get_country_data('india');
    $country_key = 'india';
}

$all_countries = get_all_countries_data();

// Calculate relative path prefix back to the application root directory
// Works seamlessly across local PHP server, Hostinger root domains, and Hostinger subfolders
$app_root_dir = __DIR__;
$entry_dir = dirname($_SERVER['SCRIPT_FILENAME'] ?? __DIR__);

function get_relative_asset_prefix(string $entry, string $root): string {
    $from = str_replace('\\', '/', realpath($entry) ?: $entry);
    $to = str_replace('\\', '/', realpath($root) ?: $root);
    if ($from === $to) {
        return '';
    }
    $fromParts = array_values(array_filter(explode('/', trim($from, '/'))));
    $toParts = array_values(array_filter(explode('/', trim($to, '/'))));
    while (count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]) {
        array_shift($fromParts);
        array_shift($toParts);
    }
    $depth = count($fromParts);
    return $depth > 0 ? str_repeat('../', $depth) : '../';
}

$base_path = get_relative_asset_prefix($entry_dir, $app_root_dir);

$errors = [];
$success = false;
$old = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'country_code' => $country['dial_code'] ?? '+91',
    'phone' => '',
    'experience' => '',
    'background' => '',
    'preferred_market' => $country['name'] ?? '',
    'career_goal' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($old['country_code'] === '') {
        $old['country_code'] = $country['dial_code'] ?? '+91';
    }
    if ($old['preferred_market'] === '') {
        $old['preferred_market'] = $country['name'] ?? '';
    }

    if ($old['first_name'] === '') { $errors['first_name'] = 'Please enter your first name.'; }
    if ($old['last_name'] === '') { $errors['last_name'] = 'Please enter your last name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Please enter a valid email address.'; }
    
    $clean_digits = preg_replace('/\D/', '', $old['phone']);
    if ($clean_digits === '' || strlen($clean_digits) < 6) { 
        $errors['phone'] = 'Please enter a valid phone or WhatsApp number.'; 
    }
    
    if ($old['experience'] === '') { $errors['experience'] = 'Please select your work experience.'; }
    if ($old['background'] === '') { $errors['background'] = 'Please select your professional background.'; }
    if (empty($_POST['consent'])) { $errors['consent'] = 'Please provide consent to be contacted about the program.'; }

    if (!$errors) {
        $phone_raw = $old['phone'];
        if (str_starts_with($phone_raw, '+')) {
            $full_phone = $phone_raw;
        } else {
            $full_phone = $old['country_code'] . ' ' . $phone_raw;
        }

        try {
            $stmt = db()->prepare('INSERT INTO leads
                (first_name,last_name,email,phone,experience,background,preferred_market,career_goal,ip_address,user_agent)
                VALUES (:first_name,:last_name,:email,:phone,:experience,:background,:preferred_market,:career_goal,:ip_address,:user_agent)');
            $stmt->execute([
                ':first_name' => $old['first_name'],
                ':last_name' => $old['last_name'],
                ':email' => $old['email'],
                ':phone' => substr($full_phone, 0, 30),
                ':experience' => $old['experience'],
                ':background' => $old['background'],
                ':preferred_market' => $old['preferred_market'] ?: $country['name'],
                ':career_goal' => $old['career_goal'] ?: null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
            $success = true;
            $old = array_fill_keys(array_keys($old), '');
            $old['country_code'] = $country['dial_code'] ?? '+91';
            $old['preferred_market'] = $country['name'];
        } catch (Throwable $e) {
            $errors['general'] = ENVIRONMENT === 'local' ? $e->getMessage() : 'We could not save your enquiry. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($country['seo_title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($country['meta_desc']) ?>">
<link rel="canonical" href="<?= htmlspecialchars($country['canonical_url']) ?>">

<?php foreach ($all_countries as $c_slug => $c_item): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars($c_item['hreflang']) ?>" href="<?= htmlspecialchars($c_item['canonical_url']) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="https://aviation.coepd.co/">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": <?= json_encode($country['h1']) ?>,
  "description": <?= json_encode($country['meta_desc']) ?>,
  "provider": {
    "@type": "EducationalOrganization",
    "name": "COEPD Aviation Institute",
    "sameAs": "https://aviation.coepd.co"
  },
  "timeRequired": "P5M",
  "educationalCredentialAwarded": "Certificate in Technical Product Management — Aviation Domain",
  "hasCourseInstance": {
    "@type": "CourseInstance",
    "courseMode": "Online",
    "courseWorkload": "PT5H",
    "location": {
      "@type": "VirtualLocation",
      "name": <?= json_encode($country['name'] . " Live Online Weekend Sessions") ?>
    }
  }
}
</script>

<style>
:root{
  --navy:#17324d; --navy-dark:#102538; --blue:#2f6da4; --blue-light:#eaf2f8;
  --sand:#f6f4ef; --white:#fff; --ink:#20272e; --muted:#5f6b76;
  --line:#dfe4e8; --accent:#b4622b; --error:#b3261e; --success:#275c31;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',Arial,sans-serif;color:var(--ink);background:var(--white);line-height:1.7;font-size:16px}
h1,h2,h3,h4{font-family:'Source Serif 4',Georgia,serif;color:var(--navy);line-height:1.25;font-weight:600}
p{color:var(--muted)}
a{color:var(--blue);text-decoration:none}
a:hover{text-decoration:underline}
.wrap{max-width:1140px;margin:0 auto;padding:0 24px}
.topbar{background:var(--navy-dark);color:#d8e2ea;font-size:13.5px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.topbar .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:center}
.topbar strong{color:#fff}
.topbar-highlight{color:#8ec5f1}

.site-header{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:30}
.header-inner{display:flex;align-items:center;justify-content:space-between;height:76px;gap:20px}
.brand{display:flex;align-items:center;text-decoration:none}
.brand-logo{height:46px;width:auto;max-width:220px;object-fit:contain;display:block}
@media(max-width:620px){.brand-logo{height:36px;max-width:160px}}

.main-nav{display:flex;align-items:center;gap:22px}
.main-nav a{color:var(--ink);font-size:14.5px;font-weight:500}
.main-nav a:hover{color:var(--blue)}
.nav-cta{background:var(--navy);color:#fff!important;padding:10px 18px;border-radius:6px;font-weight:600;font-size:14.5px}
.nav-cta:hover{background:var(--blue);text-decoration:none}

/* Country Selector */
.country-selector{position:relative;display:inline-flex;align-items:center}
.country-select-btn{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;background:#f5f8fb;border:1px solid #cdd8e2;border-radius:6px;font-size:13.5px;font-weight:600;color:var(--navy);cursor:pointer;transition:all .18s}
.country-select-btn:hover{background:#eaf2f8;border-color:var(--blue);text-decoration:none}
.country-select-flag{width:20px;height:14px;object-fit:cover;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,.15);border:1px solid rgba(0,0,0,.08)}
.country-dropdown{display:none;position:absolute;top:100%;right:0;margin-top:6px;background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 12px 32px rgba(16,37,56,.18);width:260px;max-height:360px;overflow-y:auto;z-index:50;padding:6px 0}
.country-selector:hover .country-dropdown, .country-dropdown:hover, .country-dropdown.is-open{display:block}
.country-dropdown a{display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:13.5px;color:var(--ink);text-decoration:none;transition:background .12s}
.country-dropdown a:hover{background:#f0f5fa;color:var(--blue)}
.country-dropdown a.active{background:#eaf2f8;font-weight:600;color:var(--blue)}
.country-drop-flag{width:22px;height:15px;object-fit:cover;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,.15);flex-shrink:0}

/* Hero Section */
.hero{
  position:relative;
  background:
    linear-gradient(135deg, rgba(13, 30, 48, 0.94) 0%, rgba(20, 45, 69, 0.89) 55%, rgba(10, 24, 38, 0.96) 100%),
    url('<?= htmlspecialchars($base_path . 'assets/images/hero-bg.jpg') ?>') center/cover no-repeat;
  border-bottom:1px solid rgba(255,255,255,.08);
  padding:72px 0 82px;
  color:#fff;
}
.hero-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:48px;align-items:start}
.hero-badge{display:inline-flex;align-items:center;gap:9px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.2);color:#dbe9f6;font-size:13px;font-weight:600;padding:7px 16px;border-radius:30px;margin-bottom:20px;letter-spacing:.02em}
.hero-badge-flag{width:18px;height:12px;object-fit:cover;border-radius:2px;box-shadow:0 1px 2px rgba(0,0,0,.25)}
.hero-badge-dot{width:8px;height:8px;border-radius:50%;background:#4da6e9;box-shadow:0 0 0 2px rgba(77,166,233,.3)}
.hero h1{font-size:clamp(32px,4vw,46px);color:#ffffff;line-height:1.18;font-weight:600;letter-spacing:-.015em;margin-bottom:18px}
.hero-domain{color:#8ec5f1;font-weight:400}
.hero-intro{font-size:17.5px;color:#d1e0ee;line-height:1.65;max-width:580px;margin-bottom:24px}
.hero-list{margin:0 0 28px;list-style:none;display:flex;flex-direction:column;gap:12px}
.hero-list li{display:flex;align-items:flex-start;gap:12px;color:#e3edf5;font-size:15px;line-height:1.55}
.hero-check{width:18px;height:18px;color:#4da6e9;flex-shrink:0;margin-top:3px}
.hero-actions{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.btn{display:inline-block;padding:13px 24px;border-radius:6px;font-weight:600;font-size:15px;border:1px solid transparent;cursor:pointer;transition:.18s;text-align:center}
.hero .btn-primary{background:#2a72b5;color:#fff;border-color:#3d8bd6;box-shadow:0 4px 14px rgba(0,0,0,.25)}
.hero .btn-primary:hover{background:#1f609b;border-color:#2a72b5;text-decoration:none}
.hero .btn-secondary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.35);color:#ffffff}
.hero .btn-secondary:hover{background:rgba(255,255,255,.16);border-color:#ffffff;text-decoration:none}

.hero-highlights{margin-top:32px;padding-top:22px;border-top:1px solid rgba(255,255,255,.12);display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.hero-high-item{display:flex;flex-direction:column}
.hero-high-item strong{font-size:14px;font-weight:700;color:#ffffff;letter-spacing:-.01em}
.hero-high-item span{font-size:12px;color:#9ab5cb}
.hero-high-sep{width:1px;height:26px;background:rgba(255,255,255,.14)}

/* Enquiry Form */
.enquiry{background:#fff;border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:32px 30px;box-shadow:0 20px 48px -10px rgba(5,18,30,.38)}
.enquiry-kicker{display:block;font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--blue);margin-bottom:6px}
.enquiry h2{font-size:23px;margin-bottom:6px;color:var(--navy)}
.enquiry>p{font-size:14.5px;margin-bottom:20px;color:var(--muted);line-height:1.55}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{margin-bottom:14px}
.field.full{grid-column:1/-1}
label{display:block;font-size:13.5px;font-weight:600;margin-bottom:5px;color:#17324d}
input,select,textarea{width:100%;padding:11px 12px;border:1px solid #ccd5db;border-radius:6px;font:inherit;font-size:14.5px;background:#fbfcfd;transition:border-color .15s,box-shadow .15s,background .15s}
input:focus,select:focus,textarea:focus{outline:none;background:#fff;border-color:var(--blue);box-shadow:0 0 0 3px rgba(47,109,164,.15)}
textarea{min-height:80px;resize:vertical}
.phone-input-group{display:flex;gap:10px;align-items:stretch}
.country-code-wrap{flex:0 0 150px;min-width:125px}
.country-code-select{cursor:pointer;text-overflow:ellipsis;white-space:nowrap}
.phone-input{flex:1;min-width:0}
@media(max-width:520px){.country-code-wrap{flex:0 0 125px;min-width:110px}}
.error-text{color:var(--error);font-size:13px;margin-top:5px}
.input-error{border-color:var(--error)!important}
.alert{padding:14px;border-radius:6px;font-size:14.5px;margin-bottom:18px}
.alert-success{background:#eef6ee;border:1px solid #b7d3b8;color:var(--success)}
.alert-error{background:#fdecea;border:1px solid #f0b6b1;color:var(--error)}
.consent{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:var(--muted);margin:4px 0 16px}
.consent input{width:16px;height:16px;margin-top:3px;accent-color:var(--navy)}
.form-submit{width:100%;padding:13px 20px;font-size:15.5px}
.privacy-note{font-size:12.5px;color:var(--muted);margin-top:11px;text-align:center}

/* Common Section */
.section{padding:80px 0}
.section-alt{background:var(--sand);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.section-head{max-width:800px;margin-bottom:46px}
.section-head h2{font-size:clamp(28px,3.2vw,38px);margin-bottom:14px}
.section-head p{font-size:16.5px;line-height:1.65}
.kicker{display:block;font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin-bottom:10px}

/* Why Learn Grid */
.domain-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.domain-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:26px;box-shadow:0 4px 16px rgba(16,37,56,.04);display:flex;flex-direction:column;gap:10px}
.domain-card h3{font-size:20px;color:var(--navy)}
.domain-card p{font-size:14.5px;line-height:1.6}
.domain-card-tag{font-size:11.5px;font-weight:700;color:var(--blue);letter-spacing:.06em;text-transform:uppercase}

/* Audience Grid */
.audience-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.audience-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:26px;box-shadow:0 4px 16px rgba(16,37,56,.04);display:flex;flex-direction:column;gap:10px}
.audience-card h3{font-size:19px;color:var(--navy)}
.audience-card p{font-size:14.5px;line-height:1.6}

/* Pillars */
.pillars{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.pillar{background:#fff;border:1px solid var(--line);border-radius:10px;padding:30px}
.pillar h3{font-size:22px;margin-bottom:12px}
.pillar ul{margin:18px 0 0 18px;color:var(--muted);font-size:15px}
.pillar li{margin-bottom:8px}
.pillar-number{font-size:13px;font-weight:600;color:var(--blue);letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px}

/* Unified Curriculum Card */
.curriculum-grid{display:grid;grid-template-columns:.88fr 1.12fr;gap:44px;align-items:start}
.curriculum-card{background:var(--navy);color:#dbe5ed;border-radius:12px;overflow:hidden;border:1px solid rgba(23,50,77,.15);box-shadow:0 12px 28px rgba(16,37,56,.08);display:flex;flex-direction:column}
.curriculum-card-media{width:100%;line-height:0;background:#0d233a;overflow:hidden}
.curriculum-card-img{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;transition:transform .35s ease}
.curriculum-card:hover .curriculum-card-img{transform:scale(1.02)}
.curriculum-card-body{padding:30px 28px;flex:1;display:flex;flex-direction:column}
.curriculum-card-kicker{display:block;font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8ec5f1;margin-bottom:8px}
.curriculum-card-body h3{color:#fff;font-size:23px;margin-bottom:10px}
.curriculum-card-body p{color:#c3d0da;font-size:15px;line-height:1.6}
.curriculum-card-list{margin-top:18px;list-style:none;display:flex;flex-direction:column}
.curriculum-card-list li{padding:11px 0;border-top:1px solid rgba(255,255,255,.14);font-size:14.5px;color:#e2ecf5;display:flex;align-items:center;gap:10px}
.curriculum-card-list li::before{content:"";display:inline-block;width:6px;height:6px;border-radius:50%;background:#4da6e9;flex-shrink:0}

.module-list{display:grid;gap:16px}
.module{background:#fff;border:1px solid var(--line);border-left:4px solid var(--blue);border-radius:8px;padding:24px 26px}
.module h4{font-size:19px;margin-bottom:8px}
.module p{font-size:15px}

/* Roadmap */
.roadmap-wrap{margin-top:40px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:32px}
.roadmap-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.roadmap-col{display:flex;flex-direction:column;gap:10px}
.roadmap-tag{display:inline-block;font-size:12px;font-weight:700;text-transform:uppercase;color:var(--accent);letter-spacing:.06em}
.roadmap-col h4{font-size:18px;color:var(--navy)}
.roadmap-col p{font-size:14.5px;line-height:1.6}

/* Capstone Grid */
.capstone-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.capstone{background:#fff;border:1px solid var(--line);border-radius:10px;padding:26px}
.capstone h4{font-size:18px;margin-bottom:8px}
.capstone-number{font-size:12.5px;font-weight:600;color:var(--accent);margin-bottom:8px}

/* Career Section */
.career-hero-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:30px;margin-bottom:32px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.career-comp-box{background:#fdf5ee;border:1px solid #f4d3b8;border-radius:8px;padding:16px 22px;display:flex;flex-direction:column;gap:4px}
.career-comp-label{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--accent)}
.career-comp-val{font-size:20px;font-weight:700;color:#8f4518}
.career-employers-box{font-size:14.5px;color:var(--muted);max-width:540px}
.career-employers-box strong{color:var(--navy)}

.career-roles-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.career-role{background:#fff;border:1px solid var(--line);border-radius:10px;padding:24px;display:flex;flex-direction:column;gap:8px}
.career-role h4{font-size:18px;color:var(--navy)}
.career-role p{font-size:14px;line-height:1.6}

/* Programme Details Card */
.program-details-strip{background:var(--navy);color:#fff;border-radius:12px;padding:34px;margin-top:36px;display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
.p-detail-item{display:flex;flex-direction:column;gap:6px}
.p-detail-lbl{font-size:12px;color:#9ab5cb;text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.p-detail-val{font-size:16px;color:#fff;font-weight:600}

/* FAQ Accordion */
.faq-list{display:flex;flex-direction:column;gap:14px;max-width:920px}
.faq-item{background:#fff;border:1px solid var(--line);border-radius:8px;overflow:hidden;transition:border-color .2s}
.faq-item:hover{border-color:var(--blue)}
.faq-item summary{padding:18px 22px;font-size:17px;font-weight:600;color:var(--navy);cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:16px;user-select:none}
.faq-item summary::-webkit-details-marker{display:none}
.faq-icon{width:18px;height:18px;color:var(--blue);transition:transform .2s ease;flex-shrink:0}
.faq-item[open] .faq-icon{transform:rotate(180deg)}
.faq-body{padding:0 22px 20px;font-size:15px;color:var(--muted);line-height:1.65;border-top:1px solid rgba(0,0,0,.05);padding-top:14px}

/* Final CTA */
.final-cta{background:var(--navy);color:#fff;padding:76px 0;text-align:center}
.final-cta h2{color:#fff;font-size:clamp(28px,3.4vw,40px);max-width:760px;margin:0 auto 16px}
.final-cta p{color:#c8d5df;max-width:640px;margin:0 auto 28px;font-size:17px}
.final-cta .btn-primary{background:#fff;color:var(--navy)}

/* Footer */
footer{background:var(--navy-dark);color:#a9b8c5;padding:46px 0 32px;font-size:14px;border-top:1px solid rgba(255,255,255,.08)}
.footer-inner{display:flex;justify-content:space-between;gap:28px;flex-wrap:wrap;align-items:center;margin-bottom:34px}
.footer-brand-wrap{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.footer-logo-link{display:inline-flex;align-items:center;background:#fff;padding:8px 14px;border-radius:7px;transition:opacity .2s;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.footer-logo-link:hover{opacity:.92;text-decoration:none}
.footer-logo{height:36px;width:auto;display:block;object-fit:contain}
.footer-program-info strong{color:#fff;font-family:'Source Serif 4',Georgia,serif;font-size:16px;display:block}
.footer-program-info span{color:#9cb2c4;font-size:13px}
.footer-meta{text-align:right;font-size:13.5px;color:#9cb2c4;line-height:1.6}
.footer-copyright{color:#6c8295;font-size:12.5px;margin-top:6px}

/* International Directory in Footer */
.footer-countries{border-top:1px solid rgba(255,255,255,.1);padding-top:24px}
.footer-countries h4{color:#fff;font-size:13px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;font-family:'Inter',sans-serif;font-weight:600}
.country-links-grid{display:flex;flex-wrap:wrap;gap:10px 18px}
.country-links-grid a{color:#a2b7c9;font-size:13px;display:inline-flex;align-items:center;gap:6px}
.country-links-grid a:hover{color:#fff;text-decoration:none}

@media(max-width:960px){
  .hero-grid,.curriculum-grid{grid-template-columns:1fr}
  .domain-grid,.audience-grid,.pillars,.capstone-grid,.career-roles-grid,.roadmap-grid{grid-template-columns:1fr}
  .program-details-strip{grid-template-columns:1fr 1fr}
  .main-nav{display:none}
  .section{padding:60px 0}
}
@media(max-width:768px){
  .footer-inner{flex-direction:column;align-items:flex-start;gap:20px}
  .footer-meta{text-align:left}
}
@media(max-width:620px){
  .form-grid{grid-template-columns:1fr}
  .enquiry{padding:24px}
  .hero{padding:54px 0}
  .program-details-strip{grid-template-columns:1fr}
}
</style>
</head>
<body id="top">

<div class="topbar">
  <div class="wrap">
    <div><strong>Admissions open in <?= htmlspecialchars($country['name']) ?>:</strong> Technical Product Manager — Aviation Domain</div>
    <div>5-month program · Live learning (<span class="topbar-highlight"><?= htmlspecialchars($country['timezone']) ?></span>) · Six capstone projects</div>
  </div>
</div>

<header class="site-header">
  <div class="wrap header-inner">
    <a href="<?= htmlspecialchars($base_path ?: './') ?>" class="brand" aria-label="COEPD Aviation">
      <img src="<?= htmlspecialchars($base_path . 'assets/images/logo.png') ?>" alt="COEPD Logo" class="brand-logo">
    </a>
    <nav class="main-nav">
      <a href="#why-learn">Why <?= htmlspecialchars($country['name']) ?></a>
      <a href="#curriculum">Curriculum</a>
      <a href="#capstones">Capstones</a>
      <a href="#careers">Careers &amp; Salaries</a>
      <a href="#faqs">FAQs</a>
      
      <!-- Country Selector Dropdown -->
      <div class="country-selector">
        <a class="country-select-btn" href="javascript:void(0)" aria-label="Select Country">
          <?php if (!empty($country['flag']) && file_exists(__DIR__ . '/' . $country['flag'])): ?>
            <img src="<?= htmlspecialchars($base_path . $country['flag']) ?>" alt="<?= htmlspecialchars($country['name']) ?>" class="country-select-flag">
          <?php else: ?>
            <span>🌐</span>
          <?php endif; ?>
          <span><?= htmlspecialchars($country['name']) ?></span>
          <span style="font-size:10px">▼</span>
        </a>
        <div class="country-dropdown">
          <?php foreach ($all_countries as $c_slug => $c_item): ?>
            <a href="<?= htmlspecialchars($base_path . $c_slug) ?>/" class="<?= $c_slug === $country_key ? 'active' : '' ?>">
              <?php if (!empty($c_item['flag']) && file_exists(__DIR__ . '/' . $c_item['flag'])): ?>
                <img src="<?= htmlspecialchars($base_path . $c_item['flag']) ?>" alt="<?= htmlspecialchars($c_item['name']) ?>" class="country-drop-flag">
              <?php else: ?>
                <span>🌐</span>
              <?php endif; ?>
              <span><?= htmlspecialchars($c_item['name']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <a class="nav-cta" href="#enquiry">Request Details</a>
    </nav>
  </div>
</header>

<main>
<!-- Hero Section -->
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <div class="hero-badge">
        <?php if (!empty($country['flag']) && file_exists(__DIR__ . '/' . $country['flag'])): ?>
          <img src="<?= htmlspecialchars($base_path . $country['flag']) ?>" alt="<?= htmlspecialchars($country['name']) ?>" class="hero-badge-flag">
        <?php else: ?>
          <span class="hero-badge-dot"></span>
        <?php endif; ?>
        <span><?= htmlspecialchars($country['hero_eyebrow']) ?></span>
      </div>
      <h1><?= htmlspecialchars($country['h1']) ?></h1>
      <p class="hero-intro"><?= htmlspecialchars($country['hero_intro']) ?></p>
      
      <ul class="hero-list">
        <?php foreach ($country['hero_takeaways'] as $takeaway): ?>
          <li>
            <svg class="hero-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <span><?= htmlspecialchars($takeaway) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      
      <div class="hero-actions">
        <a class="btn btn-primary" href="#enquiry">Request Program Details</a>
        <a class="btn btn-secondary" href="#curriculum">View Curriculum Structure</a>
      </div>

      <div class="hero-highlights">
        <div class="hero-high-item">
          <strong>5 Months</strong>
          <span>Live Online Program</span>
        </div>
        <div class="hero-high-sep"></div>
        <div class="hero-high-item">
          <strong>Weekend Live</strong>
          <span><?= htmlspecialchars($country['timezone']) ?></span>
        </div>
        <div class="hero-high-sep"></div>
        <div class="hero-high-item">
          <strong>6 Capstones</strong>
          <span>Agile Portfolio</span>
        </div>
        <div class="hero-high-sep"></div>
        <div class="hero-high-item">
          <strong>IATA &amp; ONE Record</strong>
          <span>Industry Aligned</span>
        </div>
      </div>
    </div>

    <!-- Admissions Enquiry Card -->
    <aside class="enquiry" id="enquiry">
      <span class="enquiry-kicker">Admissions Open — <?= htmlspecialchars($country['name']) ?></span>
      <h2>Request program details</h2>
      <p>Share your details to receive the comprehensive brochure, syllabus, batch calendar in <?= htmlspecialchars($country['timezone']) ?>, and fee breakdown in <?= htmlspecialchars($country['currency']) ?>.</p>

      <?php if ($success): ?>
        <div class="alert alert-success">Thank you for your interest! Our admissions advisor for <?= htmlspecialchars($country['name']) ?> will contact you with the program brochure and batch schedule.</div>
      <?php endif; ?>

      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
      <?php endif; ?>

      <form method="post" action="#enquiry" novalidate>
        <div class="form-grid">
          <div class="field">
            <label for="firstName">First name *</label>
            <input class="<?= isset($errors['first_name']) ? 'input-error' : '' ?>" id="firstName" name="first_name" value="<?= htmlspecialchars($old['first_name']) ?>" required>
            <?php if (isset($errors['first_name'])): ?><div class="error-text"><?= htmlspecialchars($errors['first_name']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="lastName">Last name *</label>
            <input class="<?= isset($errors['last_name']) ? 'input-error' : '' ?>" id="lastName" name="last_name" value="<?= htmlspecialchars($old['last_name']) ?>" required>
            <?php if (isset($errors['last_name'])): ?><div class="error-text"><?= htmlspecialchars($errors['last_name']) ?></div><?php endif; ?>
          </div>
          <div class="field full">
            <label for="email">Email address *</label>
            <input class="<?= isset($errors['email']) ? 'input-error' : '' ?>" id="email" name="email" type="email" value="<?= htmlspecialchars($old['email']) ?>" required>
            <?php if (isset($errors['email'])): ?><div class="error-text"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="field full">
            <label for="phone">Phone / WhatsApp number *</label>
            <div class="phone-input-group">
              <div class="country-code-wrap">
                <select class="country-code-select" id="countryCode" name="country_code" aria-label="Country Code">
                  <?php
                    $selected_code = $old['country_code'] !== '' ? $old['country_code'] : ($country['dial_code'] ?? '+91');
                    $has_selected = false;
                  ?>
                  <optgroup label="Popular Aviation Hubs">
                    <?php foreach (get_popular_country_codes() as $c): 
                      $is_sel = (!$has_selected && $c['code'] === $selected_code);
                      if ($is_sel) $has_selected = true;
                    ?>
                      <option value="<?= htmlspecialchars($c['code']) ?>" <?= $is_sel ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ' (' . $c['code'] . ')') ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                  <optgroup label="All Countries (A–Z)">
                    <?php foreach (get_country_codes() as $c): 
                      $is_sel = (!$has_selected && $c['code'] === $selected_code);
                      if ($is_sel) $has_selected = true;
                    ?>
                      <option value="<?= htmlspecialchars($c['code']) ?>" <?= $is_sel ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ' (' . $c['code'] . ')') ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                </select>
              </div>
              <input class="phone-input <?= isset($errors['phone']) ? 'input-error' : '' ?>" id="phone" name="phone" type="tel" placeholder="e.g. phone or WhatsApp" value="<?= htmlspecialchars($old['phone']) ?>" required>
            </div>
            <?php if (isset($errors['phone'])): ?><div class="error-text"><?= htmlspecialchars($errors['phone']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="experience">Work experience *</label>
            <select class="<?= isset($errors['experience']) ? 'input-error' : '' ?>" id="experience" name="experience" required>
              <option value="">Select experience</option>
              <?php foreach (['Fresher / Student','1–3 years','4–7 years','8–12 years','More than 12 years'] as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $old['experience'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['experience'])): ?><div class="error-text"><?= htmlspecialchars($errors['experience']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="background">Professional background *</label>
            <select class="<?= isset($errors['background']) ? 'input-error' : '' ?>" id="background" name="background" required>
              <option value="">Select background</option>
              <?php foreach (['Product management','Software / engineering','Aviation / airline / cargo','Business analysis','Project or program management','Consulting / operations','Other'] as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $old['background'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['background'])): ?><div class="error-text"><?= htmlspecialchars($errors['background']) ?></div><?php endif; ?>
          </div>
          <div class="field full">
            <label for="market">Target Career Market</label>
            <select id="market" name="preferred_market">
              <option value="<?= htmlspecialchars($country['name']) ?>" selected><?= htmlspecialchars($country['name']) ?> (Current Selection)</option>
              <?php foreach ($all_countries as $c_item): 
                if ($c_item['name'] === $country['name']) continue;
              ?>
                <option value="<?= htmlspecialchars($c_item['name']) ?>" <?= $old['preferred_market'] === $c_item['name'] ? 'selected' : '' ?>><?= htmlspecialchars($c_item['name']) ?></option>
              <?php endforeach; ?>
              <option value="Global / Exploring options">Global / Exploring options</option>
            </select>
          </div>
          <div class="field full">
            <label for="goal">Your career goal</label>
            <textarea id="goal" name="career_goal" placeholder="Tell us briefly about your background or target role in <?= htmlspecialchars($country['name']) ?>."><?= htmlspecialchars($old['career_goal']) ?></textarea>
          </div>
        </div>

        <label class="consent">
          <input type="checkbox" name="consent" value="1" required>
          <span>I agree to receive programme syllabus, fee schedule and admissions guidance by email, phone, or WhatsApp.</span>
        </label>
        <?php if (isset($errors['consent'])): ?><div class="error-text" style="margin-top:-10px;margin-bottom:14px"><?= htmlspecialchars($errors['consent']) ?></div><?php endif; ?>

        <button type="submit" class="btn btn-primary form-submit">Request Brochure &amp; Schedule</button>
        <p class="privacy-note">Your information is confidential and used exclusively for programme communication.</p>
      </form>
    </aside>
  </div>
</section>

<!-- Section 2: Why Learn in Country -->
<section class="section section-alt" id="why-learn">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker"><?= htmlspecialchars($country['name']) ?> Market Insight</span>
      <h2><?= htmlspecialchars($country['why_learn_title']) ?></h2>
      <?php if (!empty($country['why_learn_intro'])): ?>
        <p><?= nl2br(htmlspecialchars($country['why_learn_intro'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="domain-grid">
      <?php foreach ($country['why_learn_points'] as $idx => $pt): ?>
        <article class="domain-card">
          <span class="domain-card-tag">Domain Focus <?= $idx + 1 ?></span>
          <h3><?= htmlspecialchars($pt['title']) ?></h3>
          <p><?= htmlspecialchars($pt['desc']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Section 3: Who Should Enrol -->
<section class="section" id="audience">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Candidate Profiles</span>
      <h2><?= htmlspecialchars($country['audience_title']) ?></h2>
      <?php if (!empty($country['audience_intro'])): ?>
        <p><?= nl2br(htmlspecialchars($country['audience_intro'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="audience-grid">
      <?php foreach ($country['audience_points'] as $idx => $apt): ?>
        <article class="audience-card">
          <span class="domain-card-tag">Target Profile <?= $idx + 1 ?></span>
          <h3><?= htmlspecialchars($apt['role']) ?></h3>
          <p><?= htmlspecialchars($apt['desc']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Section 4: Curriculum & Learning Pillars -->
<section class="section section-alt" id="curriculum">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Course Structure</span>
      <h2>What you will learn during the 5-month programme</h2>
      <p>The curriculum moves systematically from core digital product-management foundations to specialised aviation-domain knowledge and hands-on Agile delivery skills.</p>
    </div>

    <div class="pillars" style="margin-bottom:44px">
      <article class="pillar">
        <div class="pillar-number">Pillar 1</div>
        <h3>Product Literacy</h3>
        <p>Build a strong foundation in modern product management and learn how digital products are planned, built, launched, and improved.</p>
        <ul>
          <li>Product strategy and vision</li>
          <li>Product discovery and customer research</li>
          <li>Execution and delivery</li>
          <li>Compliance and governance</li>
          <li>Product lifecycle management</li>
        </ul>
      </article>
      <article class="pillar">
        <div class="pillar-number">Pillar 2</div>
        <h3>Aviation Product Knowledge</h3>
        <p>Understand how airlines, cargo businesses, airports, and software providers collaborate across the international aviation ecosystem.</p>
        <ul>
          <li>Airline and cargo value chain</li>
          <li>Core airline and airport operations</li>
          <li>AWB management and cargo documentation</li>
          <li>IATA, ONE Record, e-AWB, and CargoiQ</li>
          <li>Data privacy and regulatory awareness</li>
        </ul>
      </article>
      <article class="pillar">
        <div class="pillar-number">Pillar 3</div>
        <h3>Agile Capstone Projects</h3>
        <p>Apply what you learn through six comprehensive capstone projects covering key stages of an aviation product lifecycle.</p>
        <ul>
          <li>Discovery and problem definition</li>
          <li>Roadmap and product strategy</li>
          <li>Agile execution &amp; backlog management</li>
          <li>Technical excellence &amp; data flows</li>
          <li>UX, quality, and business impact</li>
        </ul>
      </article>
    </div>

    <!-- Curriculum Left Card + Module List -->
    <div class="curriculum-grid">
      <aside class="curriculum-card">
        <div class="curriculum-card-media">
          <img src="<?= htmlspecialchars($base_path . 'assets/images/curriculum-learning.jpg') ?>" alt="Aviation Product Management Learning and Operations Workshop" class="curriculum-card-img" width="720" height="450">
        </div>
        <div class="curriculum-card-body">
          <span class="curriculum-card-kicker">Learning Methodology</span>
          <h3>Practical learning approach</h3>
          <p>The programme is designed to be practical and application-focused. Learners in <?= htmlspecialchars($country['name']) ?> work through real-world scenarios, create industry-standard product artefacts, and receive direct instructor feedback.</p>
          <ul class="curriculum-card-list">
            <li>Live instructor-led weekend sessions (<?= htmlspecialchars($country['timezone']) ?>)</li>
            <li>Aviation case studies and operational discussions</li>
            <li>Product templates and PRD frameworks</li>
            <li>Practical assignments and peer reviews</li>
            <li>Six portfolio-ready aviation capstone projects</li>
          </ul>
        </div>
      </aside>

      <div class="module-list">
        <article class="module"><h4>1. Product strategy and discovery</h4><p>Learn how to identify customer and business problems, conduct market research, define product opportunities, and build a strategic product roadmap.</p></article>
        <article class="module"><h4>2. Technical product execution</h4><p>Understand Agile delivery practices, backlog management, user stories, acceptance criteria, sprint planning, and engineering collaboration.</p></article>
        <article class="module"><h4>3. Airline and cargo value chain</h4><p>Explore passenger booking journeys, departure control, turnaround operations, cargo workflows, ground handling, and aviation software systems.</p></article>
        <article class="module"><h4>4. AWB, e-AWB, ONE Record, and CargoiQ</h4><p>Gain practical exposure to Air Waybill processes, electronic AWB, IATA ONE Record data sharing, cargo messaging, and CargoiQ milestones.</p></article>
        <article class="module"><h4>5. Compliance, standards, and data privacy</h4><p>Learn how product managers navigate international aviation regulations, security requirements, data privacy, and operational risk.</p></article>
        <article class="module"><h4>6. UX, quality, and business impact</h4><p>Define intuitive user experiences, quality benchmarks, adoption metrics, and measurable business value for aviation digital products.</p></article>
      </div>
    </div>

    <!-- 5-Month Learning Roadmap -->
    <div class="roadmap-wrap">
      <span class="kicker">Program Timeline</span>
      <h3 style="font-size:24px;margin-bottom:14px">Five-Month Learning Roadmap</h3>
      <div class="roadmap-grid">
        <div class="roadmap-col">
          <span class="roadmap-tag">Months 1 &amp; 2</span>
          <h4>Foundations &amp; Domain Basics</h4>
          <p>Product management literacy, discovery frameworks, airline business models, core operations workflows, and aviation regulatory basics.</p>
        </div>
        <div class="roadmap-col">
          <span class="roadmap-tag">Months 3 &amp; 4</span>
          <h4>Technical Delivery &amp; Capstones 1–3</h4>
          <p>Technical PRDs, Agile sprint planning, AWB/ONE Record data flows, and completing Capstone Projects 1, 2, and 3.</p>
        </div>
        <div class="roadmap-col">
          <span class="roadmap-tag">Month 5</span>
          <h4>Leadership, Capstones 4–6 &amp; Portfolio</h4>
          <p>Business case development, go-to-market strategy, Capstones 4, 5, and 6, portfolio presentation, and career interview preparation.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section 5: Capstone Projects -->
<section class="section" id="capstones">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Portfolio Development</span>
      <h2>Six Agile capstone projects in the aviation domain</h2>
      <p>Learners complete six portfolio-ready capstone projects that demonstrate their practical capability to manage an aviation digital product from discovery through delivery.</p>
    </div>
    <div class="capstone-grid">
      <article class="capstone"><div class="capstone-number">Project 1</div><h4>Discovery &amp; Problem Definition</h4><p>Identify an aviation customer or operational problem, conduct user discovery, build personas, and validate the product opportunity.</p></article>
      <article class="capstone"><div class="capstone-number">Project 2</div><h4>Roadmap &amp; Strategy</h4><p>Create a product vision, business case, success metrics, release roadmap, and stakeholder alignment plan.</p></article>
      <article class="capstone"><div class="capstone-number">Project 3</div><h4>Agile Execution</h4><p>Develop a prioritised product backlog, user stories, sprint plan, and delivery approach using Agile practices.</p></article>
      <article class="capstone"><div class="capstone-number">Project 4</div><h4>Technical PRD &amp; Architecture</h4><p>Define technical specifications, API considerations, data exchange flows, and scalability requirements.</p></article>
      <article class="capstone"><div class="capstone-number">Project 5</div><h4>UX &amp; Quality Acceptance</h4><p>Map user workflows, design usability improvements, and define acceptance criteria and quality expectations.</p></article>
      <article class="capstone"><div class="capstone-number">Project 6</div><h4>Go-to-Market &amp; Business Value</h4><p>Prepare a go-to-market plan, pricing considerations, adoption metrics, and an executive business impact pitch.</p></article>
    </div>
  </div>
</section>

<!-- Section 6: Career Opportunities & Salaries -->
<section class="section section-alt" id="careers">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker"><?= htmlspecialchars($country['name']) ?> Career Pathways</span>
      <h2><?= htmlspecialchars($country['career_title']) ?></h2>
      <?php if (!empty($country['career_intro'])): ?>
        <p><?= nl2br(htmlspecialchars($country['career_intro'])) ?></p>
      <?php endif; ?>
    </div>

    <!-- Career Top Highlight Card -->
    <div class="career-hero-card">
      <div class="career-comp-box">
        <span class="career-comp-label">Indicative Market Compensation</span>
        <span class="career-comp-val"><?= htmlspecialchars($country['indicative_comp']) ?></span>
      </div>
      <div class="career-employers-box">
        <strong>Representative Organizations in <?= htmlspecialchars($country['name']) ?> &amp; Global Hubs:</strong><br>
        <?= htmlspecialchars($country['top_employers']) ?>
      </div>
    </div>

    <!-- Career Roles Grid -->
    <div class="career-roles-grid">
      <?php foreach ($country['career_roles'] as $r): ?>
        <article class="career-role">
          <h4><?= htmlspecialchars($r['title']) ?></h4>
          <p><?= htmlspecialchars($r['desc']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Program Information Strip -->
    <div class="program-details-strip">
      <div class="p-detail-item">
        <span class="p-detail-lbl">Delivery Mode</span>
        <span class="p-detail-val">Live Online + Self-Study</span>
      </div>
      <div class="p-detail-item">
        <span class="p-detail-lbl">Batch Schedule</span>
        <span class="p-detail-val">Weekends (<?= htmlspecialchars($country['timezone']) ?>)</span>
      </div>
      <div class="p-detail-item">
        <span class="p-detail-lbl">Duration</span>
        <span class="p-detail-val">5 Months Structured</span>
      </div>
      <div class="p-detail-item">
        <span class="p-detail-lbl">Currency</span>
        <span class="p-detail-val"><?= htmlspecialchars($country['currency']) ?> or Equivalent</span>
      </div>
    </div>
  </div>
</section>

<!-- Section 7: FAQs -->
<section class="section" id="faqs">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Frequently Asked Questions</span>
      <h2>Questions about the Aviation Product Management course in <?= htmlspecialchars($country['name']) ?></h2>
      <p>Everything you need to know about eligibility, batch timings in <?= htmlspecialchars($country['timezone']) ?>, curriculum depth, and admissions process.</p>
    </div>

    <div class="faq-list">
      <?php foreach ($country['faqs'] as $idx => $faq): ?>
        <details class="faq-item" <?= $idx === 0 ? 'open' : '' ?>>
          <summary>
            <span><?= htmlspecialchars($faq['q']) ?></span>
            <svg class="faq-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
          </summary>
          <div class="faq-body">
            <p><?= nl2br(htmlspecialchars($faq['a'])) ?></p>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Final CTA Section -->
<section class="final-cta">
  <div class="wrap">
    <h2>Take the next step in your aviation technology career</h2>
    <p>Request the complete programme syllabus, batch schedule in <?= htmlspecialchars($country['timezone']) ?>, and admissions guidance for <?= htmlspecialchars($country['name']) ?>.</p>
    <a class="btn btn-primary" href="#enquiry">Request Program Details</a>
  </div>
</section>
</main>

<!-- Footer -->
<footer>
  <div class="wrap">
    <div class="footer-inner">
      <div class="footer-brand-wrap">
        <a href="<?= htmlspecialchars($base_path ?: './') ?>" class="footer-logo-link" aria-label="COEPD Aviation">
          <img src="<?= htmlspecialchars($base_path . 'assets/images/logo.png') ?>" alt="COEPD Logo" class="footer-logo" width="180" height="67">
        </a>
        <div class="footer-program-info">
          <strong>COEPD Aviation Institute — <?= htmlspecialchars($country['name']) ?></strong>
          <span>Technical Product Manager — Aviation Domain | 5-Month Professional Program</span>
        </div>
      </div>
      <div class="footer-meta">
        <div>Product strategy · Airline operations · IATA standards · Agile capstone projects · Career support</div>
        <div class="footer-copyright">&copy; <?= date('Y') ?> COEPD. All rights reserved. Professional Aviation Training.</div>
      </div>
    </div>

    <!-- International Country Directory -->
    <div class="footer-countries">
      <h4>Global Training Programs by Country</h4>
      <div class="country-links-grid">
        <a href="<?= htmlspecialchars($base_path ?: './') ?>">🌐 Global / All Countries</a>
        <?php foreach ($all_countries as $c_slug => $c_item): ?>
          <a href="<?= htmlspecialchars($base_path . $c_slug) ?>/">
            <?php if (!empty($c_item['flag']) && file_exists(__DIR__ . '/' . $c_item['flag'])): ?>
              <img src="<?= htmlspecialchars($base_path . $c_item['flag']) ?>" alt="" style="width:16px;height:11px;object-fit:cover;border-radius:1px;display:inline-block">
            <?php endif; ?>
            <span><?= htmlspecialchars($c_item['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.querySelector('.country-select-btn');
  const dropdown = document.querySelector('.country-dropdown');
  if (btn && dropdown) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      dropdown.classList.toggle('is-open');
    });
    document.addEventListener('click', function(e) {
      if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove('is-open');
      }
    });
  }
});
</script>
</body>
</html>
