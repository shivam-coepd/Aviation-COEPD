<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/country_codes.php';

$errors = [];
$success = false;
$old = ['first_name'=>'','last_name'=>'','email'=>'','country_code'=>'+91','phone'=>'','experience'=>'','background'=>'','preferred_market'=>'','career_goal'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) { $old[$k] = trim((string)($_POST[$k] ?? '')); }
    if ($old['country_code'] === '') {
        $old['country_code'] = '+91';
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
                ':first_name'=>$old['first_name'], ':last_name'=>$old['last_name'], ':email'=>$old['email'],
                ':phone'=>substr($full_phone, 0, 30),
                ':experience'=>$old['experience'], ':background'=>$old['background'],
                ':preferred_market'=>$old['preferred_market'] ?: null, ':career_goal'=>$old['career_goal'] ?: null,
                ':ip_address'=>$_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent'=>substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,255),
            ]);
            $success = true;
            $old = array_fill_keys(array_keys($old),'');
            $old['country_code'] = '+91';
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
<title>Technical Product Manager — Aviation Domain | 5-Month Professional Program</title>
<meta name="description" content="A five-month professional training program for product managers and technology professionals entering the aviation industry.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
.wrap{max-width:1120px;margin:0 auto;padding:0 24px}
.topbar{background:var(--navy-dark);color:#d8e2ea;font-size:13.5px;padding:10px 0}
.topbar .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
.topbar strong{color:#fff}
.site-header{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:20}
.header-inner{display:flex;align-items:center;justify-content:space-between;height:76px;gap:24px}
.brand{display:flex;align-items:center;text-decoration:none}
.brand-logo{height:46px;width:auto;max-width:230px;object-fit:contain;display:block}
@media(max-width:620px){.brand-logo{height:38px;max-width:170px}}
.main-nav{display:flex;align-items:center;gap:26px}
.main-nav a{color:var(--ink);font-size:15px;font-weight:500}
.main-nav a:hover{color:var(--blue)}
.nav-cta{background:var(--navy);color:#fff!important;padding:11px 20px;border-radius:6px;font-weight:600}
.nav-cta:hover{background:var(--blue);text-decoration:none}

.hero{
  position:relative;
  background:
    linear-gradient(rgba(212, 212, 212, 0.72), rgba(149, 149, 149, 0.72)),
    url('assets/images/hero-bg.jpg') center/cover no-repeat;
  border-bottom:1px solid var(--line);
  padding:88px 0 96px;
  color:#fff;
}
.hero h1{color:#000}
.hero .eyebrow{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);color:#fff}
.hero-intro{color:#000}
.hero-list li{color:#000}
.hero .btn-secondary{border-color:rgba(255,255,255,.65);color:#fff}
.hero .btn-secondary:hover{background:rgba(255,255,255,.12);color:#fff}

.hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:start}
.eyebrow{display:inline-block;background:var(--blue-light);color:var(--navy);font-size:13px;font-weight:600;padding:7px 14px;border-radius:5px;margin-bottom:22px}
.hero h1{font-size:clamp(34px,4.4vw,52px);margin-bottom:20px}
.hero-intro{font-size:18px;max-width:560px}
.hero-list{margin:28px 0 32px;list-style:none}
.hero-list li{position:relative;padding-left:28px;margin-bottom:12px}
.hero-list li:before{content:"";position:absolute;left:0;top:10px;width:14px;height:2px;background:var(--accent)}
.hero-actions{display:flex;gap:14px;flex-wrap:wrap}
.btn{display:inline-block;padding:14px 24px;border-radius:6px;font-weight:600;font-size:15.5px;border:1px solid transparent;cursor:pointer;transition:.18s}
.btn-primary{background:var(--navy);color:#fff}
.btn-primary:hover{background:var(--blue);text-decoration:none}
.btn-secondary{background:transparent;border-color:var(--navy);color:var(--navy)}
.btn-secondary:hover{background:var(--navy);color:#fff;text-decoration:none}
.enquiry{background:#fff;border:1px solid var(--line);border-radius:10px;padding:32px;box-shadow:0 12px 32px rgba(23,50,77,.08)}
.enquiry h2{font-size:24px;margin-bottom:8px}
.enquiry>p{font-size:15px;margin-bottom:22px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.field{margin-bottom:16px}
.field.full{grid-column:1/-1}
label{display:block;font-size:14px;font-weight:600;margin-bottom:6px;color:#000}
input,select,textarea{width:100%;padding:12px 13px;border:1px solid #c8d0d7;border-radius:6px;font:inherit;font-size:15px;background:#fcfcfb}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(47,109,164,.13)}
textarea{min-height:90px;resize:vertical}
.phone-input-group{display:flex;gap:10px;align-items:stretch}
.country-code-wrap{flex:0 0 155px;min-width:130px}
.country-code-select{cursor:pointer;text-overflow:ellipsis;white-space:nowrap}
.phone-input{flex:1;min-width:0}
@media(max-width:520px){.country-code-wrap{flex:0 0 130px;min-width:115px}}
.error-text{color:var(--error);font-size:13px;margin-top:5px}
.input-error{border-color:var(--error)!important}
.alert{padding:14px;border-radius:6px;font-size:14.5px;margin-bottom:18px}
.alert-success{background:#eef6ee;border:1px solid #b7d3b8;color:var(--success)}
.alert-error{background:#fdecea;border:1px solid #f0b6b1;color:var(--error)}
.consent{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:var(--muted);margin:4px 0 18px}
.consent input{width:16px;height:16px;margin-top:4px;accent-color:var(--navy)}
.form-submit{width:100%}
.privacy-note{font-size:12.5px;color:var(--muted);margin-top:12px;text-align:center}
.section{padding:82px 0}
.section-alt{background:var(--sand);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.section-head{max-width:760px;margin-bottom:48px}
.section-head h2{font-size:clamp(28px,3.2vw,38px);margin-bottom:14px}
.section-head p{font-size:17px}
.kicker{display:block;font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin-bottom:12px}
.pillars{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.pillar{background:#fff;border:1px solid var(--line);border-radius:10px;padding:30px}
.pillar h3{font-size:22px;margin-bottom:12px}
.pillar ul{margin:18px 0 0 18px;color:var(--muted);font-size:15px}
.pillar li{margin-bottom:8px}
.pillar-number{font-size:13px;font-weight:600;color:var(--blue);letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px}
.curriculum-grid{display:grid;grid-template-columns:.85fr 1.15fr;gap:48px;align-items:start}
.curriculum-sidebar{display:flex;flex-direction:column;gap:24px}
.curriculum-image-wrap{overflow:hidden;border-radius:10px;border:1px solid var(--line);box-shadow:0 8px 24px rgba(16,37,56,.08);background:#fff}
.curriculum-image{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;transition:transform .3s ease}
.curriculum-image-wrap:hover .curriculum-image{transform:scale(1.02)}
.curriculum-intro{background:var(--navy);color:#dbe5ed;border-radius:10px;padding:34px}
.curriculum-intro h3{color:#fff;font-size:24px;margin-bottom:12px}
.curriculum-intro p{color:#c3d0da;font-size:15.5px}
.curriculum-intro ul{margin-top:20px;list-style:none}
.curriculum-intro li{padding:12px 0;border-top:1px solid rgba(255,255,255,.16);font-size:15px}
.module-list{display:grid;gap:16px}
.module{background:#fff;border:1px solid var(--line);border-left:4px solid var(--blue);border-radius:8px;padding:24px 26px}
.module h4{font-size:19px;margin-bottom:8px}
.module p{font-size:15.5px}
.capstone-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.capstone{background:#fff;border:1px solid var(--line);border-radius:10px;padding:28px}
.capstone h4{font-size:19px;margin-bottom:10px}
.capstone-number{font-size:13px;font-weight:600;color:var(--accent);margin-bottom:10px}
.table-wrap{overflow-x:auto;background:#fff;border:1px solid var(--line);border-radius:10px}
table{width:100%;border-collapse:collapse;min-width:820px}
th{background:var(--navy);color:#fff;text-align:left;padding:16px 18px;font-size:13.5px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
td{padding:18px;border-bottom:1px solid var(--line);vertical-align:top;font-size:15px}
tr:last-child td{border-bottom:none}
td strong{color:var(--navy)}
.region{display:block;font-size:13px;color:var(--muted);margin-top:3px}
.comp{font-weight:600;color:var(--accent);white-space:nowrap}
.employers{color:var(--muted);font-size:14px}
.disclaimer{font-size:13px;color:var(--muted);margin-top:14px;max-width:860px}
.value-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
.value{background:#fff;border:1px solid var(--line);border-radius:10px;padding:28px}
.value h3{font-size:19px;margin-bottom:8px}
.value p{font-size:15px}
.final-cta{background:var(--navy);color:#fff;padding:76px 0;text-align:center}
.final-cta h2{color:#fff;font-size:clamp(28px,3.4vw,40px);max-width:760px;margin:0 auto 16px}
.final-cta p{color:#c8d5df;max-width:620px;margin:0 auto 30px;font-size:17px}
.final-cta .btn-primary{background:#fff;color:var(--navy)}
footer{background:var(--navy-dark);color:#a9b8c5;padding:34px 0;font-size:14px}
.footer-inner{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;align-items:center}
footer strong{color:#fff;font-family:'Source Serif 4',serif;font-size:16px}
@media(max-width:960px){.hero-grid,.curriculum-grid{grid-template-columns:1fr}.pillars,.capstone-grid,.value-grid{grid-template-columns:1fr}.main-nav{display:none}.section{padding:64px 0}}
@media(max-width:620px){.form-grid{grid-template-columns:1fr}.enquiry{padding:24px}.hero{padding:56px 0}}
</style>
</head>
<body id="top">

<div class="topbar">
  <div class="wrap">
    <div><strong>Admissions open:</strong> Technical Product Manager — Aviation Domain</div>
    <div>5-month program · Live learning · Six capstone projects</div>
  </div>
</div>

<header class="site-header">
  <div class="wrap header-inner">
    <a href="#top" class="brand" aria-label="COEPD">
      <img src="assets/images/logo.png" alt="COEPD Logo" class="brand-logo">
    </a>
    <nav class="main-nav">
      <a href="#program">Program</a>
      <a href="#curriculum">Curriculum</a>
      <a href="#capstones">Capstone Projects</a>
      <a href="#careers">Career Opportunities</a>
      <a class="nav-cta" href="#enquiry">Request Details</a>
    </nav>
  </div>
</header>

<main>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow">New program launch by COEPD</span>
      <h1>Technical Product Manager — Aviation Domain</h1>
      <p class="hero-intro">A five-month professional program for product, technology, and business professionals who want to build a career in aviation product management.</p>
      <ul class="hero-list">
        <li>Learn product strategy, discovery, execution, compliance, and the complete product lifecycle.</li>
        <li>Understand airline and cargo operations, AWB management, IATA standards, ONE Record, e-AWB, and CargoiQ.</li>
        <li>Apply your learning through six Agile capstone projects based on practical aviation product scenarios.</li>
        <li>Prepare for product roles across airlines, cargo, travel technology, aerospace, and aviation software.</li>
      </ul>
      <div class="hero-actions">
        <a class="btn btn-primary" href="#enquiry">Request Program Details</a>
        <a class="btn btn-secondary" href="#program">View Program Structure</a>
      </div>
    </div>

    <aside class="enquiry" id="enquiry">
      <h2>Request program details</h2>
      <p>Share your details and our admissions team will send you the brochure, curriculum, batch schedule, and eligibility guidance.</p>

      <?php if ($success): ?>
        <div class="alert alert-success">Thank you for your interest. Our admissions team will contact you with the program brochure and next steps.</div>
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
                    $selected_code = $old['country_code'] !== '' ? $old['country_code'] : '+91';
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
              <input class="phone-input <?= isset($errors['phone']) ? 'input-error' : '' ?>" id="phone" name="phone" type="tel" placeholder="e.g. 98765 43210" value="<?= htmlspecialchars($old['phone']) ?>" required>
            </div>
            <?php if (isset($errors['phone'])): ?><div class="error-text"><?= htmlspecialchars($errors['phone']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="experience">Work experience *</label>
            <select class="<?= isset($errors['experience']) ? 'input-error' : '' ?>" id="experience" name="experience" required>
              <option value="">Select your experience</option>
              <?php foreach (['Fresher / Student','1–3 years','4–7 years','8–12 years','More than 12 years'] as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $old['experience'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['experience'])): ?><div class="error-text"><?= htmlspecialchars($errors['experience']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="background">Professional background *</label>
            <select class="<?= isset($errors['background']) ? 'input-error' : '' ?>" id="background" name="background" required>
              <option value="">Select your background</option>
              <?php foreach (['Product management','Software / engineering','Aviation / airline / cargo','Business analysis','Project or program management','Consulting / operations','Other'] as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $old['background'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['background'])): ?><div class="error-text"><?= htmlspecialchars($errors['background']) ?></div><?php endif; ?>
          </div>
          <div class="field full">
            <label for="market">Preferred career market</label>
            <select id="market" name="preferred_market">
              <option value="">Select, if applicable</option>
              <?php foreach (['India','United States','UAE — Dubai / Abu Dhabi','Qatar','Germany / European Union','United Kingdom','Singapore','Canada','Exploring options'] as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $old['preferred_market'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field full">
            <label for="goal">Your career goal</label>
            <textarea id="goal" name="career_goal" placeholder="Tell us briefly about your career goal or the type of aviation product role you are interested in."><?= htmlspecialchars($old['career_goal']) ?></textarea>
          </div>
        </div>

        <label class="consent">
          <input type="checkbox" name="consent" value="1" required>
          <span>I agree to receive information about this program and admissions support by email, phone, or WhatsApp.</span>
        </label>
        <?php if (isset($errors['consent'])): ?><div class="error-text" style="margin-top:-10px;margin-bottom:14px"><?= htmlspecialchars($errors['consent']) ?></div><?php endif; ?>

        <button type="submit" class="btn btn-primary form-submit">Request Brochure and Advisor Call</button>
        <p class="privacy-note">Your information will be used only for program-related communication.</p>
      </form>
    </aside>
  </div>
</section>

<section class="section" id="program">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Program structure</span>
      <h2>A practical program built around aviation product management</h2>
      <p>The program combines product-management fundamentals with aviation-domain knowledge and hands-on project experience.</p>
    </div>
    <div class="pillars">
      <article class="pillar"><div class="pillar-number">Pillar 1</div><h3>Product Literacy</h3><p>Build a strong foundation in product management and learn how digital products are planned, built, launched, and improved.</p><ul><li>Product strategy and vision</li><li>Product discovery and research</li><li>Execution and delivery</li><li>Compliance and governance</li><li>Product lifecycle management</li></ul></article>
      <article class="pillar"><div class="pillar-number">Pillar 2</div><h3>Aviation Product Knowledge</h3><p>Understand how airlines, cargo businesses, airports, and technology providers work together across the aviation ecosystem.</p><ul><li>Airline and cargo value chain</li><li>Core airline and airport operations</li><li>AWB management and cargo documentation</li><li>IATA, ONE Record, e-AWB, and CargoiQ</li><li>Data privacy and regulatory awareness</li></ul></article>
      <article class="pillar"><div class="pillar-number">Pillar 3</div><h3>Agile Capstone Projects</h3><p>Apply what you learn through six capstone projects covering the key stages of an aviation product lifecycle.</p><ul><li>Discovery and problem definition</li><li>Roadmap and product strategy</li><li>Agile execution</li><li>Technical excellence</li><li>UX, quality, and business impact</li></ul></article>
    </div>
  </div>
</section>

<section class="section section-alt" id="curriculum">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Curriculum</span>
      <h2>What you will learn during the program</h2>
      <p>The curriculum moves from core product-management concepts to aviation-specific knowledge and practical delivery skills.</p>
    </div>
    <div class="curriculum-grid">
      <aside class="curriculum-sidebar">
        <div class="curriculum-image-wrap">
          <img src="assets/images/curriculum-learning.jpg" alt="Aviation Product Management Learning and Operations Workshop" class="curriculum-image" width="720" height="450" />
        </div>
        <div class="curriculum-intro">
          <h3>Learning approach</h3>
          <p>The program is designed to be practical and application-focused. Participants work through real-world scenarios, create product artefacts, and receive feedback on their work.</p>
          <ul><li>Live instructor-led sessions</li><li>Aviation case studies and discussions</li><li>Product templates and frameworks</li><li>Practical assignments and reviews</li><li>Six portfolio-ready capstone projects</li></ul>
        </div>
      </aside>
      <div class="module-list">
        <article class="module"><h4>Product strategy and discovery</h4><p>Learn how to identify customer and business problems, conduct research, define product opportunities, and create a clear product strategy.</p></article>
        <article class="module"><h4>Technical product execution</h4><p>Understand Agile delivery practices, backlog management, user stories, acceptance criteria, release planning, and collaboration with engineering and design teams.</p></article>
        <article class="module"><h4>Airline and cargo value chain</h4><p>Explore passenger journeys, airline operations, cargo workflows, ground handling, freight forwarding, and the digital systems that connect them.</p></article>
        <article class="module"><h4>AWB, e-AWB, ONE Record, and CargoiQ</h4><p>Gain practical exposure to air waybill processes, electronic AWB, IATA ONE Record, cargo data exchange, and CargoiQ.</p></article>
        <article class="module"><h4>Compliance, standards, and data privacy</h4><p>Learn how product managers work with aviation regulations, industry standards, security requirements, data privacy, and operational risk.</p></article>
        <article class="module"><h4>UX, quality, and business impact</h4><p>Understand how to define user experiences, quality expectations, adoption metrics, and the business value of aviation product decisions.</p></article>
      </div>
    </div>
  </div>
</section>

<section class="section" id="capstones">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Capstone projects</span>
      <h2>Six projects using Agile in the aviation domain</h2>
      <p>Participants complete six capstone projects that demonstrate their ability to manage an aviation product from discovery through delivery and business impact.</p>
    </div>
    <div class="capstone-grid">
      <article class="capstone"><div class="capstone-number">Project 1</div><h4>Discovery</h4><p>Identify an aviation business or customer problem, conduct discovery, define user personas, and validate the opportunity.</p></article>
      <article class="capstone"><div class="capstone-number">Project 2</div><h4>Roadmap and Strategy</h4><p>Create a product vision, business case, success metrics, release roadmap, and stakeholder alignment plan.</p></article>
      <article class="capstone"><div class="capstone-number">Project 3</div><h4>Agile Execution</h4><p>Develop a prioritised product backlog, user stories, sprint plan, and delivery approach using Agile practices.</p></article>
      <article class="capstone"><div class="capstone-number">Project 4</div><h4>Technical Excellence</h4><p>Define technical requirements, integration needs, data flows, API considerations, and scalability requirements.</p></article>
      <article class="capstone"><div class="capstone-number">Project 5</div><h4>UX and Quality</h4><p>Design user journeys, identify usability improvements, and define acceptance criteria and quality expectations.</p></article>
      <article class="capstone"><div class="capstone-number">Project 6</div><h4>Market and Business</h4><p>Prepare a go-to-market approach, pricing considerations, adoption metrics, and a business-impact presentation.</p></article>
    </div>
  </div>
</section>

<section class="section section-alt" id="careers">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Career opportunities</span>
      <h2>Global hiring hubs for aviation product professionals</h2>
      <p>Aviation product roles are available across airline groups, cargo platforms, aerospace companies, travel-technology providers, and aviation software businesses.</p>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Market</th><th>Opportunity</th><th>Indicative compensation</th><th>Representative organisations</th></tr></thead>
        <tbody>
          <tr><td><strong>United States</strong><span class="region">Seattle, Dallas, Denver, Austin</span></td><td>One of the largest markets for aviation software, airline technology, and aerospace product roles.</td><td class="comp">$107,000–$214,000 per year</td><td class="employers">Boeing, American Airlines, JetBlue, ForeFlight</td></tr>
          <tr><td><strong>United Arab Emirates</strong><span class="region">Dubai and Abu Dhabi</span></td><td>A rapidly growing aviation technology market with technology-visa pathways for eligible professionals.</td><td class="comp">AED 15,000–30,000 per month, tax-free</td><td class="employers">Emirates Group, Etihad</td></tr>
          <tr><td><strong>Qatar</strong><span class="region">Doha</span></td><td>Opportunities in digital booking, ancillary revenue, loyalty, and airline digital products.</td><td class="comp">Role-specific</td><td class="employers">Qatar Airways</td></tr>
          <tr><td><strong>Germany</strong><span class="region">Hamburg and Munich</span></td><td>Strong aerospace and aviation-technology ecosystem, with EU Blue Card and long-term settlement pathways for eligible candidates.</td><td class="comp">€60,000–€120,000 per year</td><td class="employers">Airbus, Lufthansa Technik</td></tr>
          <tr><td><strong>United Kingdom</strong><span class="region">London and regional hubs</span></td><td>An active aviation hiring market, with 646 aviation roles reported over a six-month period.</td><td class="comp">£47,000–£83,000</td><td class="employers">BAE Systems, Rolls-Royce, easyJet Labs</td></tr>
          <tr><td><strong>Singapore</strong><span class="region">Singapore</span></td><td>Growing opportunities across airline technology, aerospace engineering, and aviation services.</td><td class="comp">SGD 82,000–146,000 per year</td><td class="employers">Boeing, Singapore Airlines ecosystem, ST Engineering</td></tr>
          <tr><td><strong>India</strong><span class="region">Bengaluru, Delhi NCR, Mumbai</span></td><td>A large market for digital aviation, airline technology, cargo platforms, and travel-technology roles.</td><td class="comp">₹15 lakh–₹50 lakh per year</td><td class="employers">IndiGo Digital, Air India, IBS, Amadeus, SITA</td></tr>
          <tr><td><strong>Other markets</strong><span class="region">Finland, Canada, South Korea</span></td><td>Emerging opportunities in sustainable aviation fuel, aerospace manufacturing, and simulation technology.</td><td class="comp">Market- and role-specific</td><td class="employers">Neste SAF, Bombardier, CAE</td></tr>
        </tbody>
      </table>
    </div>
    <p class="disclaimer">Compensation figures and organisation names are indicative market references provided in the program brochure. Actual salaries and hiring requirements vary based on experience, location, employer, role level, and market conditions.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <span class="kicker">Why this program</span>
      <h2>Designed for professionals entering aviation product roles</h2>
      <p>This program is suitable for product managers, business analysts, engineers, consultants, and aviation professionals who want to move into product roles within the aviation industry.</p>
    </div>
    <div class="value-grid">
      <div class="value"><h3>Domain and product skills</h3><p>Develop both product-management capability and practical knowledge of aviation operations and technology.</p></div>
      <div class="value"><h3>Project-based learning</h3><p>Build a portfolio through six capstone projects that reflect real product-management work.</p></div>
      <div class="value"><h3>Industry-relevant standards</h3><p>Learn about IATA, ONE Record, e-AWB, CargoiQ, and data-privacy considerations relevant to aviation products.</p></div>
      <div class="value"><h3>Global career focus</h3><p>Understand hiring demand and role expectations across major aviation and technology markets.</p></div>
    </div>
  </div>
</section>

<section class="final-cta">
  <div class="wrap">
    <h2>Take the next step toward an aviation product management career</h2>
    <p>Request the program brochure to review the curriculum, capstone projects, batch schedule, and admission process.</p>
    <a class="btn btn-primary" href="#enquiry">Request Program Details</a>
  </div>
</section>
</main>

<footer>
  <div class="wrap footer-inner">
    <div><strong>COEPD</strong><br>Technical Product Manager — Aviation Domain | 5-Month Program</div>
    <div>Product strategy · Aviation operations · IATA standards · Agile capstone projects · Career support</div>
  </div>
</footer>
</body>
</html>
