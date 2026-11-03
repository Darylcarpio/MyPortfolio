<?php
// panel.php — Admin Dashboard
session_start();

// Add cache prevention headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php', true, 302);
    exit();
}

if (isset($_GET['logout'])) {
    header('Location: logout.php', true, 302);
    exit();
}

// Include database configuration and DAL
require_once 'config.php';
$dal = new PortfolioDAL();

$admin_user = htmlspecialchars($_SESSION['admin_user'] ?? 'Admin');
$login_time = $_SESSION['admin_login_time'] ?? time();
$elapsed = time() - $login_time;
$elapsed_str = $elapsed < 60 ? $elapsed . 's ago' : floor($elapsed / 60) . 'm ' . ($elapsed % 60) . 's ago';

// Fetch data from database
$profile_data = $dal->getProfile();
if (!$profile_data) {
    $profile_data = []; // Handle case where no profile exists
} else {
    // Normalize column names for backward compatibility
    if (isset($profile_data['full_name'])) {
        $profile_data['name'] = $profile_data['full_name'];
    }
    if (isset($profile_data['professional_title'])) {
        $profile_data['title'] = $profile_data['professional_title'];
    }
    if (isset($profile_data['bio'])) {
        $profile_data['vision'] = $profile_data['bio'];
    }
}

// Get education data and format achievements
$edu_raw = $dal->getEducation();
$education_data = array_map(function($edu) {
    return [
        'id' => $edu['id'],
        'year' => $edu['year_range'],
        'title' => $edu['title'],
        'school' => $edu['school'],
        'description' => $edu['description'] ?? '',
        'achievements' => !empty($edu['achievements']) ? explode('|', $edu['achievements']) : []
    ];
}, $edu_raw);

// Get hobbies and restructure by category
$hobbies_raw = $dal->getHobbies();
$hobbies_data = [];
foreach ($hobbies_raw as $hobby) {
    $cat_name = $hobby['category_name'];
    $cat_key = null;
    
    // Find existing category or create new one
    foreach ($hobbies_data as $key => $cat) {
        if ($cat['category'] === $hobby['display_name']) {
            $cat_key = $key;
            break;
        }
    }
    
    if ($cat_key === null) {
        $hobbies_data[] = [
            'category_id' => null,
            'category' => $hobby['display_name'],
            'category_name' => $cat_name,
            'items' => []
        ];
        $cat_key = count($hobbies_data) - 1;
    }
    
    // Set category_id on first item (before it's null)
    if ($hobbies_data[$cat_key]['category_id'] === null) {
        $hobbies_data[$cat_key]['category_id'] = $hobby['category_id'] ?? null;
    }
    
    // Add item if it exists
    if (!empty($hobby['item_name'])) {
        $hobbies_data[$cat_key]['items'][] = [
            'id' => $hobby['item_id'],
            'name' => $hobby['item_name'],
            'image' => $hobby['image']
        ];
    }
}

// Get contact info and format
$contact_raw = $dal->getContactInfo();
$contact_data = array_map(function($contact) {
    return [
        'id' => $contact['id'],
        'type_id' => $contact['type_id'],
        'type' => $contact['type_name'],
        'icon' => $contact['type_icon'],
        'value' => $contact['value'],
        'link' => $contact['link']
    ];
}, $contact_raw);

// Get projects and format technologies
$projects_raw = $dal->getProjects();
$projects_data = array_map(function($proj) {
    return [
        'id' => $proj['id'],
        'title' => $proj['title'],
        'description' => $proj['description'],
        'image' => $proj['image'] ?? '',
        'technologies' => !empty($proj['technologies']) ? explode('|', $proj['technologies']) : [],
        'status' => $proj['status_name'] ?? 'Pending',
        'status_id' => $proj['status_id'] ?? 1
    ];
}, $projects_raw);

// Get page view count
$page_view_count = $dal->getPageViewCount('dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Bryan Darryl Carpio</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ============================================================
       PANEL.PHP — COMPLETE SELF-CONTAINED ADMIN DASHBOARD STYLES
       Does NOT depend on style.css or admin.css
       ============================================================ */
    :root {
        --sw:      260px;
        --th:      64px;
        --bg:      #0d0d1a;
        --sbg:     #111127;
        --cbg:     #16162e;
        --cborder: rgba(255,255,255,0.07);
        --accent:  #667eea;
        --accent2: #764ba2;
        --red:     #f05454;
        --green:   #43d98c;
        --yellow:  #f6b93b;
        --text:    #eef0ff;
        --muted:   #8b92c0;
        --grad1:   linear-gradient(135deg,#667eea,#764ba2);
        --grad2:   linear-gradient(135deg,#f093fb,#f5576c);
        --grad3:   linear-gradient(135deg,#43d98c,#1a9e62);
        --r:       12px;
        --rl:      18px;
    }

    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    html, body {
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        overflow-x: hidden;
        font-size: 16px;
        line-height: 1.6;
    }

    /* ─── SIDEBAR ───────────────────────────────── */
    .sb {
        position: fixed;
        top:0; left:0;
        width: var(--sw);
        height: 100vh;
        background: var(--sbg);
        border-right: 1px solid var(--cborder);
        display: flex;
        flex-direction: column;
        z-index: 300;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sb::-webkit-scrollbar { width:4px; }
    .sb::-webkit-scrollbar-track { background:transparent; }
    .sb::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:4px; }

    /* Sidebar header */
    .sb-head {
        padding: 22px 20px 18px;
        border-bottom: 1px solid var(--cborder);
        flex-shrink: 0;
    }

    .sb-brand {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-bottom: 18px;
        text-decoration: none;
    }

    .sb-brand-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: var(--grad1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        box-shadow: 0 4px 16px rgba(102,126,234,0.45);
        flex-shrink: 0;
    }

    .sb-brand-name {
        font-size: 0.95rem;
        font-weight: 800;
        letter-spacing: 1.2px;
        background: var(--grad1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Profile mini */
    .sb-profile {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 12px 14px;
        background: rgba(255,255,255,0.04);
        border-radius: var(--r);
        border: 1px solid var(--cborder);
    }

    .sb-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: var(--grad1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        border: 2px solid rgba(102,126,234,0.5);
    }

    .sb-profile-info { flex:1; min-width:0; }
    .sb-profile-name { font-size:0.9rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .sb-profile-role { font-size:0.78rem; color:var(--muted); margin-top:1px; }

    .sb-dot {
        width:8px; height:8px;
        border-radius:50%;
        background:var(--green);
        flex-shrink:0;
        animation: sbdot 2s infinite;
    }

    @keyframes sbdot {
        0%,100% { box-shadow:0 0 0 0 rgba(67,217,140,0.5); }
        50%      { box-shadow:0 0 0 5px rgba(67,217,140,0); }
    }

    /* Nav */
    .sb-nav { padding: 16px 14px; flex:1; }

    .sb-nav-label {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--muted);
        padding: 0 6px;
        margin-bottom: 6px;
        margin-top: 14px;
        display: block;
    }

    .sb-nav-label:first-child { margin-top:0; }

    .sb-item {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 10px 12px;
        border-radius: var(--r);
        color: var(--muted);
        cursor: pointer;
        transition: all 0.18s ease;
        margin-bottom: 2px;
        font-size: 0.92rem;
        font-weight: 500;
        border: 1px solid transparent;
        position: relative;
        user-select: none;
        text-decoration: none;
    }

    .sb-item:hover {
        background: rgba(255,255,255,0.05);
        color: var(--text);
        border-color: var(--cborder);
    }

    .sb-item.on {
        background: rgba(102,126,234,0.14);
        color: var(--text);
        border-color: rgba(102,126,234,0.28);
    }

    .sb-item.on::before {
        content: '';
        position: absolute;
        left: 0; top: 22%; bottom: 22%;
        width: 3px;
        background: var(--grad1);
        border-radius: 0 3px 3px 0;
    }

    .sb-item.on .ni { color: var(--accent); }

    .ni { font-size: 1.1rem; width: 20px; text-align: center; flex-shrink: 0; }

    /* Footer */
    .sb-foot {
        padding: 14px;
        border-top: 1px solid var(--cborder);
        flex-shrink: 0;
    }

    .btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 11px;
        background: rgba(240,84,84,0.08);
        border: 1px solid rgba(240,84,84,0.28);
        border-radius: var(--r);
        color: var(--red);
        font-size: 0.86rem;
        font-weight: 700;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-logout:hover {
        background: var(--red);
        color: #fff;
        border-color: var(--red);
        box-shadow: 0 5px 18px rgba(240,84,84,0.35);
    }

    /* ─── TOPBAR ────────────────────────────────── */
    .tb {
        position: fixed;
        top:0; left: var(--sw); right:0;
        height: var(--th);
        background: rgba(11,11,22,0.94);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--cborder);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 26px;
        z-index: 200;
    }

    .tb-title { font-size: 1.05rem; font-weight: 700; color: var(--text); }
    .tb-title span { color:var(--muted); font-weight:400; font-size:0.82rem; margin-left:7px; }

    .tb-right { display:flex; align-items:center; gap:10px; }

    .tb-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(102,126,234,0.1);
        border: 1px solid rgba(102,126,234,0.22);
        border-radius: 30px;
        font-size: 0.78rem;
        color: var(--muted);
    }

    .tb-badge .dot {
        width: 6px; height: 6px;
        background: var(--green);
        border-radius: 50%;
    }

    .tb-session {
        font-size: 0.75rem;
        color: var(--muted);
        padding: 6px 12px;
        background: rgba(255,255,255,0.04);
        border-radius: 20px;
        border: 1px solid var(--cborder);
    }

    .tb-session strong { color: var(--green); }

    .tb-settings {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--cborder);
        color: var(--muted);
        font-size: 1.1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .tb-settings:hover {
        background: rgba(102,126,234,0.15);
        border-color: rgba(102,126,234,0.3);
        color: var(--accent);
        transform: rotate(45deg);
    }

    /* ─── HAMBURGER MENU (Mobile) ─────────────── */
    .btn-menu {
        display: none; /* Hidden on desktop */
        background: transparent;
        border: 1px solid var(--cborder);
        width: 40px;
        height: 40px;
        border-radius: var(--r);
        color: var(--text);
        font-size: 1.2rem;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        margin-right: 6px;
    }

    .btn-menu:hover {
        background: rgba(255,255,255,0.08);
        border-color: var(--accent);
    }

    /* ─── MAIN ──────────────────────────────────── */
    .main {
        margin-left: var(--sw);
        margin-top: var(--th);
        padding: 26px;
        min-height: calc(100vh - var(--th));
    }

    /* ─── PAGE HEADER ───────────────────────────── */
    .ph { margin-bottom: 22px; }
    .ph h1 { font-size: 1.5rem; font-weight: 800; color: var(--text); margin-bottom: 3px; }
    .ph p  { font-size: 0.83rem; color: var(--muted); }

    /* ─── SECTION SWITCHER ──────────────────────── */
    .sec { display:none; animation: fup .28s ease; }
    .sec.on { display:block; }

    @keyframes fup {
        from { opacity:0; transform:translateY(10px); }
        to   { opacity:1; transform:translateY(0); }
    }

    /* ─── STATS ROW ─────────────────────────────── */
    .stats {
        display: grid;
        grid-template-columns: repeat(4,1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    .sc {
        background: var(--cbg);
        border: 1px solid var(--cborder);
        border-radius: var(--rl);
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }

    .sc:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.3); }

    .sc::after {
        content:'';
        position:absolute;
        top:0; left:0; right:0;
        height:3px;
    }

    .sc.s1::after { background:var(--grad1); }
    .sc.s2::after { background:var(--grad2); }
    .sc.s3::after { background:var(--grad3); }
    .sc.s4::after { background:linear-gradient(135deg,#f6b93b,#e55039); }

    .sc-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }

    .sc-ico {
        width:40px; height:40px;
        border-radius:10px;
        display:flex; align-items:center; justify-content:center;
        font-size:1.1rem;
    }

    .sc.s1 .sc-ico { background:rgba(102,126,234,.14); }
    .sc.s2 .sc-ico { background:rgba(240,84,84,.14); }
    .sc.s3 .sc-ico { background:rgba(67,217,140,.14); }
    .sc.s4 .sc-ico { background:rgba(246,185,59,.14); }

    .sc-tag {
        font-size: .68rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
    }

    .sc-tag.g  { background:rgba(67,217,140,.14);  color:var(--green); }
    .sc-tag.b  { background:rgba(102,126,234,.14); color:var(--accent); }
    .sc-tag.y  { background:rgba(246,185,59,.14);  color:var(--yellow); }

    .sc-val  { font-size:2rem; font-weight:800; color:var(--text); line-height:1; margin-bottom:4px; }
    .sc-lbl  { font-size:.78rem; color:var(--muted); font-weight:500; text-transform:uppercase; letter-spacing:.4px; }

    /* ─── CARD ──────────────────────────────────── */
    .card {
        background: var(--cbg);
        border: 1px solid var(--cborder);
        border-radius: var(--rl);
        padding: 22px;
        margin-bottom: 18px;
    }

    .card-h {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--cborder);
    }

    .card-t {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-t-ico {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: rgba(102,126,234,.12);
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
    }

    .card-t h2 {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: var(--text) !important;
        background: none !important;
        -webkit-text-fill-color: var(--text) !important;
        margin: 0 !important;
        display: block !important;
    }

    .card-t h2::after { display:none !important; }

    /* ─── QUICK GRID ────────────────────────────── */
    .qg {
        display: grid;
        grid-template-columns: repeat(4,1fr);
        gap: 10px;
    }

    .qb {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 16px 8px;
        border-radius: var(--r);
        border: 1px solid var(--cborder);
        background: rgba(255,255,255,0.025);
        cursor: pointer;
        transition: all .2s;
        font-family: 'Outfit', sans-serif;
        font-size: .8rem;
        font-weight: 600;
        color: var(--muted);
    }

    .qb:hover {
        background: rgba(102,126,234,.1);
        border-color: rgba(102,126,234,.3);
        color: var(--text);
        transform: translateY(-2px);
    }

    .qb-ico {
        width: 36px; height: 36px;
        border-radius: 9px;
        background: var(--grad1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        box-shadow: 0 3px 12px rgba(102,126,234,.35);
    }

    /* ─── ACTIVITY ──────────────────────────────── */
    .act-list { display:flex; flex-direction:column; gap:7px; }

    .act-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        background: rgba(255,255,255,.025);
        border-radius: var(--r);
        border: 1px solid transparent;
        transition: all .18s;
    }

    .act-row:hover { border-color:var(--cborder); background:rgba(255,255,255,.045); }

    .act-d { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .act-d.bl { background:var(--accent); }
    .act-d.gr { background:var(--green); }
    .act-d.ye { background:var(--yellow); }

    .act-info { flex:1; }
    .act-info strong { display:block; font-size:.86rem; font-weight:600; color:var(--text); }
    .act-info span   { font-size:.74rem; color:var(--muted); }
    .act-time        { font-size:.7rem; color:var(--muted); white-space:nowrap; }

    /* ─── SESSION PILLS ─────────────────────────── */
    .spills { display:flex; gap:10px; flex-wrap:wrap; }

    .spill {
        padding: 11px 16px;
        background: rgba(255,255,255,.04);
        border: 1px solid var(--cborder);
        border-radius: var(--r);
        flex:1; min-width:120px;
    }

    .spill-l { font-size:.68rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
    .spill-v { font-size:.9rem; font-weight:700; color:var(--text); }
    .spill-v.on { color:var(--green); }

    /* ─── TWO-COL ROW ───────────────────────────── */
    .two-col { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    .two-col .card { margin-bottom:0; }

    /* ─── FORMS ─────────────────────────────────── */
    .fg  { display:flex; flex-direction:column; gap:16px; }
    .fr  { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

    .fg-row { display:flex; flex-direction:column; gap:5px; }

    .fg-row label {
        font-size: .75rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .fc {
        padding: 10px 13px;
        background: rgba(255,255,255,.05);
        border: 1px solid var(--cborder);
        border-radius: var(--r);
        color: var(--text);
        font-size: .88rem;
        font-family: 'Outfit', sans-serif;
        transition: all .2s;
        width: 100%;
    }

    .fc:focus {
        outline: none;
        border-color: var(--accent);
        background: rgba(102,126,234,.07);
        box-shadow: 0 0 0 3px rgba(102,126,234,.14);
    }

    .fc::placeholder { color:rgba(139,146,192,.4); }
    textarea.fc      { min-height:105px; resize:vertical; }
    select.fc        { cursor:pointer; appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238b92c0' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center; }
    select.fc option   { background:#1a1a2e; color:#e0e0ff; padding:8px; }

    .img-up {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px;
        background: rgba(255,255,255,.03);
        border: 1px dashed rgba(139,146,192,.25);
        border-radius: var(--r);
    }

    .cur-img {
        width:72px; height:72px;
        border-radius:50%;
        object-fit:cover;
        border:2px solid var(--accent);
        flex-shrink:0;
    }

    .up-btn {
        padding: 8px 16px;
        background: var(--grad1);
        border-radius: 30px;
        color: #fff;
        font-size: .8rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-block;
        transition: opacity .2s;
    }

    .up-btn:hover { opacity:.82; }
    input[type="file"] { display:none; }

    .fa-row {
        display: flex;
        gap: 10px;
        padding-top: 16px;
        border-top: 1px solid var(--cborder);
        margin-top: 4px;
    }

    /* ─── BUTTONS ───────────────────────────────── */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 18px;
        border-radius: 30px;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        font-family: 'Outfit', sans-serif;
        transition: all .2s;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-p  { background:var(--grad1); color:#fff; }
    .btn-p:hover  { opacity:.85; box-shadow:0 5px 16px rgba(102,126,234,.38); transform:translateY(-1px); }

    .btn-d  { background:rgba(240,84,84,.1); color:var(--red); border:1px solid rgba(240,84,84,.28); }
    .btn-d:hover  { background:var(--red); color:#fff; }

    .btn-g  { background:rgba(255,255,255,.06); color:var(--muted); border:1px solid var(--cborder); }
    .btn-g:hover  { background:rgba(255,255,255,.1); color:var(--text); }

    .btn-e  { background:rgba(102,126,234,.1); color:var(--accent); border:1px solid rgba(102,126,234,.28); }
    .btn-e:hover  { background:var(--accent); color:#fff; }

    .btn-sm { padding:7px 16px; font-size:.82rem; }

    .add-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px;
        background: transparent;
        border: 1.5px dashed rgba(139,146,192,.22);
        border-radius: var(--r);
        color: var(--muted);
        font-size: .84rem;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Outfit', sans-serif;
        margin-top: 10px;
        transition: all .2s;
    }

    .add-row:hover { border-color:var(--accent); color:var(--accent); background:rgba(102,126,234,.04); }

    /* ─── CRUD ITEMS ────────────────────────────── */
    .crud { display:flex; flex-direction:column; gap:9px; }

    .ci {
        background: rgba(255,255,255,.025);
        border: 1px solid var(--cborder);
        border-radius: var(--r);
        padding: 14px 16px;
        transition: all .18s;
    }

    .ci:hover { border-color:rgba(102,126,234,.3); background:rgba(255,255,255,.04); }

    .ci-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 7px;
        flex-wrap: wrap;
        gap: 7px;
    }

    .ci-badge {
        font-size: .68rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        background: var(--grad1);
        color: #fff;
    }

    .ci-acts { display:flex; gap:5px; }

    .ci-title { font-size:.95rem; font-weight:700; color:var(--text); margin-bottom:2px; }
    .ci-sub   { font-size:.8rem; color:var(--accent); margin-bottom:5px; }
    .ci-desc  { font-size:.8rem; color:var(--muted); line-height:1.55; margin-bottom:7px; }

    .tags { display:flex; flex-wrap:wrap; gap:5px; }
    .tag  { font-size:.7rem; font-weight:600; padding:2px 8px; border-radius:14px; background:rgba(102,126,234,.12); color:var(--accent); }

    .sbadge { font-size:.7rem; font-weight:700; padding:3px 9px; border-radius:14px; display:inline-block; }
    .s-c    { background:rgba(67,217,140,.14); color:var(--green); border:1px solid rgba(67,217,140,.25); }
    .s-p    { background:rgba(246,185,59,.14);  color:var(--yellow); border:1px solid rgba(246,185,59,.25); }
    .s-pl   { background:rgba(139,146,192,.1); color:var(--muted); border:1px solid var(--cborder); }

    /* Hobby grid */
    .hcat  { margin-bottom:18px; }
    .hcat-l { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-bottom:9px; padding-left:2px; }

    .hgrid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:9px; }

    .hcard {
        background: rgba(255,255,255,.03);
        border: 1px solid var(--cborder);
        border-radius: var(--r);
        overflow: hidden;
        transition: all .18s;
    }

    .hcard:hover { border-color:rgba(102,126,234,.3); transform:translateY(-2px); }

    .hcard-img { width:100%; height:95px; object-fit:cover; display:block; background:rgba(255,255,255,.05); }

    .hcard-body { padding:9px 11px; display:flex; justify-content:space-between; align-items:center; }
    .hcard-name { font-size:.8rem; font-weight:600; color:var(--text); }

    /* Contact items */
    .cgrid { display:flex; flex-direction:column; gap:9px; }

    .citem {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 13px 15px;
        background: rgba(255,255,255,.025);
        border: 1px solid var(--cborder);
        border-radius: var(--r);
        transition: all .18s;
    }

    .citem:hover { border-color:rgba(102,126,234,.3); background:rgba(255,255,255,.04); }

    .cico {
        width: 38px; height: 38px;
        border-radius: 9px;
        background: rgba(102,126,234,.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .cinfo { flex:1; }
    .ctype  { font-size:.68rem; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
    .cval   { font-size:.86rem; color:var(--text); font-weight:600; }

    /* ─── MODAL ─────────────────────────────────── */
    .mo {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.78);
        backdrop-filter: blur(7px) brightness(0.95);
        -webkit-backdrop-filter: blur(7px) brightness(0.95);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        animation: backdropIn .22s ease;
    }

    .mo.open { 
        display: flex;
        animation: backdropIn .22s ease;
    }

    @keyframes backdropIn {
        from { background-color: rgba(0,0,0,0); }
        to { background-color: rgba(0,0,0,.78); }
    }

    .mbox {
        background: #161630;
        border: 1px solid var(--cborder);
        border-radius: var(--rl);
        padding: 26px;
        width: 90%;
        max-width: 460px;
        max-height: 86vh;
        overflow-y: auto;
        animation: moin .22s ease;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .mbox::-webkit-scrollbar { width:4px; }
    .mbox::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }

    @keyframes moin {
        from { opacity:0; transform:scale(.93) translateY(8px); }
        to   { opacity:1; transform:scale(1) translateY(0); }
    }

    .mhead {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 13px;
        border-bottom: 1px solid var(--cborder);
    }

    .mhead h3 { font-size:.98rem; font-weight:700; color:var(--text); }

    .mclose {
        background: rgba(255,255,255,.07);
        border: 1px solid var(--cborder);
        width: 28px; height: 28px;
        border-radius: 50%;
        color: var(--muted);
        font-size: .9rem;
        cursor: pointer;
        display: flex; align-items:center; justify-content:center;
        transition: all .18s;
        font-family: 'Outfit', sans-serif;
    }

    .mclose:hover { background:var(--red); color:#fff; border-color:var(--red); }

    .mfoot {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        margin-top: 18px;
        padding-top: 13px;
        border-top: 1px solid var(--cborder);
    }

    /* ─── TOAST ─────────────────────────────────── */
    .toast {
        position: fixed;
        bottom: 24px; right: 24px;
        padding: 13px 18px;
        background: rgba(67,217,140,.14);
        border: 1px solid var(--green);
        border-radius: var(--r);
        color: var(--green);
        font-size: .86rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 9px;
        z-index: 9999;
        transform: translateY(80px);
        opacity: 0;
        transition: all .3s ease;
        box-shadow: 0 6px 24px rgba(67,217,140,.2);
    }

    .toast.on { transform:translateY(0); opacity:1; }

    /* ─── RESPONSIVE ────────────────────────────── */
    @media (max-width:1080px) {
        :root { --sw: 240px; }
        .stats { grid-template-columns:repeat(2,1fr); }
        .qg    { grid-template-columns:repeat(2,1fr); }
        .two-col { grid-template-columns:1fr; }
    }

    @media (max-width:860px) {
        :root { --sw: 240px; }
        .sb { transform:translateX(-260px); transition:transform .3s; z-index: 250; }
        .sb.open { transform:translateX(0); }
        .sb-nav { padding: 12px 10px; }
        .sb-item { font-size: 0.88rem; padding: 8px 10px; }
        .sb-brand-name { font-size: 0.85rem; letter-spacing: 0.8px; }
        .tb { left:0; display: flex; }
        .btn-menu { display: flex; } /* Show hamburger on tablet/mobile */
        .tb-title { font-size: 0.95rem; }
        .main { margin-left:0; padding: 16px 12px; }
        .fr    { grid-template-columns:repeat(2,1fr); }
        .stats { grid-template-columns:repeat(2,1fr); gap: 12px; }
        .stat-card { padding: 14px; }
        .qg { grid-template-columns:repeat(2,1fr); gap: 12px; }
    }

    @media (max-width:700px) {
        :root { --sw: 200px; --th: 56px; font-size: 14px; }
        .sb { width: 200px; }
        .btn-menu { display: flex !important; }
        .tb { height: 56px; padding: 0 16px; }
        .tb-title { font-size: 0.9rem; }
        .tb-badge { font-size: 0.7rem; padding: 4px 10px; }
        .main { margin-top: 56px; padding: 12px; }
        .card { padding: 14px; border-radius: 10px; }
        .mhead { margin-bottom: 12px; padding-bottom: 10px; }
        .mhead h3 { font-size: 0.9rem; }
        input, select, textarea { font-size: 16px; padding: 8px 10px; }
        button { padding: 8px 14px; font-size: 0.85rem; min-height: 36px; }
    }

    @media (max-width:600px) {
        :root { --sw: 220px; }
        .main  { padding: 12px; }
        .fr    { grid-template-columns:1fr; gap: 12px; }
        .stats { grid-template-columns:1fr 1fr; gap: 10px; }
        .qg    { grid-template-columns:1fr 1fr; gap: 10px; }
        .stat-card { padding: 12px; }
        .card { padding: 12px; }
        .card h4 { font-size: 0.95rem; }
        input, select, textarea { font-size: 16px !important; }
        button { min-height: 40px; padding: 10px 14px; font-size: 0.85rem; }
        .btn-submit { width: 100%; }
        .modal { border-radius: 10px; padding: 14px; }
        .mfoot { flex-direction: column; gap: 8px; }
        .mfoot button { width: 100%; }
    }

    @media (max-width:480px) {
        :root { --sw: 200px; --th: 50px; font-size: 13px; }
        
        .sb { width: 200px; }
        .sb-brand-name { font-size: 0.7rem; }
        .sb-avatar { width: 32px; height: 32px; font-size: 0.8rem; }
        .sb-profile-name { font-size: 0.75rem; }
        .sb-profile-role { font-size: 0.65rem; }
        .sb-item { font-size: 0.75rem; padding: 6px 8px; gap: 6px; margin-bottom: 1px; }
        .sb-nav-label { font-size: 0.55rem; padding: 0 4px; margin-bottom: 4px; margin-top: 8px; }
        .ni { font-size: 0.9rem; width: 16px; }
        
        .tb { height: 50px; padding: 0 10px; }
        .tb-title { font-size: 0.8rem; }
        .tb-badge { font-size: 0.6rem; padding: 3px 8px; }
        .tb-session { font-size: 0.65rem; padding: 4px 8px; }
        
        .main { margin-top: 50px; padding: 10px; }
        .page-header { margin-bottom: 12px; }
        .page-title { font-size: 1.2rem; }
        
        .stats { grid-template-columns:repeat(2,1fr); gap: 8px; }
        .stat-card { padding: 10px; }
        .stat-number { font-size: 1rem; }
        .stat-label { font-size: 0.7rem; }
        
        .qg { grid-template-columns:1fr; gap: 10px; }
        .fr { grid-template-columns:1fr; gap: 10px; }
        
        .card { padding: 10px; margin-bottom: 10px; border-radius: 8px; }
        .card h4 { font-size: 0.85rem; margin-bottom: 8px; }
        .card p { font-size: 0.75rem; }
        
        input, select, textarea { font-size: 16px !important; padding: 8px 8px; height: auto; }
        button { min-height: 36px; padding: 8px 12px; font-size: 0.75rem; border-radius: 8px; }
        .btn-submit, .mfoot button { width: 100%; }
        
        .modal { border-radius: 8px; padding: 12px; }
        .mhead { margin-bottom: 10px; padding-bottom: 8px; }
        .mhead h3 { font-size: 0.85rem; }
        .mfoot { flex-direction: column; gap: 6px; }
        
        .field { margin-bottom: 12px; }
        label { font-size: 0.75rem; margin-bottom: 4px; }
        
        table { font-size: 0.7rem; }
        th, td { padding: 6px 4px; }
        
        .overlay { backdrop-filter: blur(4px); }
    }

    @media (max-width:380px) {
        :root { --sw: 180px; }
        .sb { width: 180px; }
        .sb-item { font-size: 0.7rem; padding: 5px 6px; }
        .tb-title { font-size: 0.7rem; }
        .main { padding: 8px; }
        .card { padding: 8px; }
        input, select, textarea { font-size: 16px !important; }
        button { min-height: 34px; padding: 6px 10px; font-size: 0.7rem; }
    }

    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
        button, .btn-logout, .sb-item { 
            min-height: 44px !important; 
            padding: 12px 14px !important;
        }
        input, select, textarea { 
            font-size: 16px !important; 
            min-height: 44px !important;
        }
    }

    /* ─── RICH TEXT EDITOR ──────────────────────── */
    .rte-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: 8px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-bottom: none;
        border-radius: 8px 8px 0 0;
    }
    .rte-toolbar button {
        padding: 6px 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 4px;
        color: var(--text);
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }
    .rte-toolbar button:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .rte-toolbar select {
        padding: 6px 8px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 4px;
        color: var(--text);
        cursor: pointer;
        font-size: 12px;
    }
    .rte-toolbar select option {
        background: #1a1a2e;
        color: #fff;
        padding: 8px;
    }
    .rte-sep {
        width: 1px;
        background: var(--border);
        margin: 0 4px;
    }
    .rte-editor {
        border-radius: 0 0 8px 8px !important;
        padding: 12px;
        line-height: 1.6;
        overflow-y: auto;
    }
    .rte-editor:focus {
        outline: 2px solid var(--primary);
        outline-offset: -2px;
    }
    .rte-editor:empty:before {
        content: 'Start typing...';
        color: var(--muted);
    }
    </style>
</head>
<body>

<!-- ═══════════════ SIDEBAR ══════════════════════════════════ -->
<aside class="sb" id="sb">

    <div class="sb-head">
        <div class="sb-brand">
            <div class="sb-brand-icon" style="font-weight:900;font-size:1.2rem;">D</div>
            <span class="sb-brand-name">DARYL DEV HUB</span>
        </div>
        <div class="sb-profile">
            <div class="sb-avatar">👤</div>
            <div class="sb-profile-info">
                <div class="sb-profile-name">Daryl Carpio</div>
                <div class="sb-profile-role">Administrator</div>
            </div>
            <div class="sb-dot"></div>
        </div>
    </div>

    <nav class="sb-nav">
        <span class="sb-nav-label">Main</span>
        <div class="sb-item on" onclick="goTo('dashboard',this)">
            <span class="ni">📊</span> Dashboard
        </div>

        <span class="sb-nav-label">Portfolio Content</span>
        <div class="sb-item" onclick="goTo('profile',this)">
            <span class="ni">👤</span> Profile Management
        </div>
        <div class="sb-item" onclick="goTo('education',this)">
            <span class="ni">🎓</span> Education
        </div>
        <div class="sb-item" onclick="goTo('projects',this)">
            <span class="ni">💻</span> Projects
        </div>
        <div class="sb-item" onclick="goTo('hobbies',this)">
            <span class="ni">⭐</span> Hobbies &amp; Skills
        </div>
        <div class="sb-item" onclick="goTo('contact',this)">
            <span class="ni">📧</span> Contact Info
        </div>

        <span class="sb-nav-label">Links</span>
        <a class="sb-item" href="dashboard.php" target="_blank">
            <span class="ni">🌐</span> View Portfolio
        </a>
    </nav>

    <div class="sb-foot">
        <a href="panel.php?logout=1" class="btn-logout" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
    </div>
</aside>

<!-- ═══════════════ TOPBAR ═══════════════════════════════════ -->
<header class="tb">
    <button class="btn-menu" id="btnMenu" onclick="toggleSidebar()" title="Toggle Menu">☰</button>
    <div class="tb-title" id="tbTitle">
        Dashboard <span>/ Overview</span>
    </div>
    <div class="tb-right">
        <div class="tb-badge">
            <div class="dot"></div>
            <?= $admin_user ?>
        </div>
        <div class="tb-session">Session: <strong><?= $elapsed_str ?></strong></div>
        <button class="tb-settings" onclick="window.location.href='admin_recover.php'" title="Manage Admin Credentials">
            ⚙️
        </button>
    </div>
</header>

<!-- Sidebar Overlay (Close sidebar when clicked on mobile) -->
<div id="sidebarOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:200; cursor:pointer;" onclick="closeSidebar()"></div>

<!-- ═══════════════ MAIN ══════════════════════════════════════ -->
<main class="main">

<!-- Toast -->
<div class="toast" id="toast">✅ Saved successfully!</div>

<!-- ═══ DASHBOARD ════════════════════════════════════════════ -->
<div class="sec on" id="sec-dashboard">
    <div class="ph">
        <h1>Dashboard Overview</h1>
        <p>Welcome back, <?= $admin_user ?>! Here's a summary of your portfolio.</p>
    </div>

    <div class="stats">
        <div class="sc s1">
            <div class="sc-top">
                <div class="sc-ico">👁️</div>
                <span class="sc-tag g">↑ Active</span>
            </div>
            <div class="sc-val"><?= number_format($page_view_count) ?></div>
            <div class="sc-lbl">Profile Views</div>
        </div>
        <div class="sc s2">
            <div class="sc-top">
                <div class="sc-ico">🎓</div>
                <span class="sc-tag b"><?= count($education_data) ?> records</span>
            </div>
            <div class="sc-val"><?= count($education_data) ?></div>
            <div class="sc-lbl">Education Items</div>
        </div>
        <div class="sc s3">
            <div class="sc-top">
                <div class="sc-ico">⭐</div>
                <span class="sc-tag b"><?php 
                    $hobby_count = 0;
                    foreach ($hobbies_data as $category) {
                        $hobby_count += count($category['items']);
                    }
                    echo count($hobbies_data) . ' categories';
                ?></span>
            </div>
            <div class="sc-val"><?php echo $hobby_count; ?></div>
            <div class="sc-lbl">Hobbies &amp; Skills</div>
        </div>
        <div class="sc s4">
            <div class="sc-top">
                <div class="sc-ico">💻</div>
                <span class="sc-tag y"><?= count($projects_data) ?> total</span>
            </div>
            <div class="sc-val"><?= count($projects_data) ?></div>
            <div class="sc-lbl">Projects</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">⚡</div>
                <h2>Quick Actions</h2>
            </div>
        </div>
        <div class="qg">
            <button class="qb" onclick="goTo('profile',null)">
                <div class="qb-ico">✏️</div>
                Edit Profile
            </button>
            <button class="qb" onclick="goTo('education',null)">
                <div class="qb-ico">🎓</div>
                Manage Education
            </button>
            <button class="qb" onclick="goTo('hobbies',null)">
                <div class="qb-ico">⭐</div>
                Manage Hobbies
            </button>
            <button class="qb" onclick="goTo('projects',null)">
                <div class="qb-ico">💻</div>
                Projects
            </button>
            <button class="qb" onclick="window.location.href='FULL_BACKUP_RESTORE.php'" style="border: 2px solid #dc3545; background: rgba(220, 53, 69, 0.05);">
                <div class="qb-ico">🔐</div>
                Backup & Restore
            </button>
        </div>
    </div>

    <!-- Activity + Session row -->
    <div class="two-col">
        <div class="card">
            <div class="card-h">
                <div class="card-t">
                    <div class="card-t-ico">🕒</div>
                    <h2>Recent Activity</h2>
                </div>
            </div>
            <div class="act-list">
                <?php 
                $recent_activities = $dal->getRecentActivity(10);
                if (count($recent_activities) > 0):
                    foreach ($recent_activities as $act):
                        $icon = $dal->getActivityIcon($act['action']);
                        $label = $dal->getActivityLabel($act['action']);
                        $detail = $act['details'] ? htmlspecialchars($act['details']) : '';
                        $relative_time = $dal->formatRelativeTime($act['created_at']);
                        $color = $dal->getActivityColor($act['action']);
                        $admin = $act['username'] ?? 'System';
                ?>
                <div class="act-row" style="border-left: 3px solid <?= $color ?>;">
                    <div class="act-d" style="font-size: 1.2em;"><?= $icon ?></div>
                    <div class="act-info">
                        <strong><?= $label ?></strong><br>
                        <span><?= $detail ?></span><br>
                        <small style="color: #8b92c0;">by <?= htmlspecialchars($admin) ?></small>
                    </div>
                    <div class="act-time">
                        <?= $relative_time ?>
                        <br>
                        <button class="btn btn-d btn-sm" style="margin-top: 4px; padding: 3px 6px; font-size: 0.7rem;" onclick="delActivity(<?= $act['id'] ?>)">🗑</button>
                    </div>
                </div>
                <?php 
                    endforeach;
                else:
                ?>
                <div class="act-row" style="text-align: center; color: #8b92c0; padding: 20px;">
                    <p>No recent activities yet. Start creating content!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-h">
                <div class="card-t">
                    <div class="card-t-ico">🔐</div>
                    <h2>Session Information</h2>
                </div>
            </div>
            <div class="spills">
                <div class="spill">
                    <div class="spill-l">Status</div>
                    <div class="spill-v on">● Active Session</div>
                </div>
                <div class="spill">
                    <div class="spill-l">Logged in as</div>
                    <div class="spill-v"><?= $admin_user ?></div>
                </div>
                <div class="spill">
                    <div class="spill-l">Duration</div>
                    <div class="spill-v"><?= $elapsed_str ?></div>
                </div>
            </div>
            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--cborder);">
                <div style="color: var(--muted); font-size: 0.78rem;">
                    Emergency bypass URL (not public): <strong>admin_bypass.php?token=YOUR_TOKEN</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ PROFILE ═══════════════════════════════════════════════ -->
<div class="sec" id="sec-profile">
    <div class="ph">
        <h1>Profile Management</h1>
        <p>Update your personal details and photo.</p>
    </div>
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">👤</div>
                <h2>Personal Information</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-e btn-sm" onclick="backupSection('profile')" title="Save current profile as backup">📦 Backup</button>
                <button class="btn btn-g btn-sm" onclick="restoreSection('profile')" title="Restore profile from backup">♻️ Restore</button>
            </div>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveProfile();">
            <div class="fr">
                <div class="fg-row">
                    <label>Full Name</label>
                    <input type="text" class="fc" id="fullname" value="<?= $profile_data['name'] ?? '' ?>" placeholder="Enter your full name">
                </div>
                <div class="fg-row">
                    <label>Title / Tagline</label>
                    <input type="text" class="fc" id="ptitle" value="<?= $profile_data['title'] ?? '' ?>" placeholder="BSIT Student | Web Developer">
                </div>
            </div>
            
            <div class="fg-row">
                <label>About Me / Bio</label>
                <div class="rte-toolbar">
                    <button type="button" onclick="formatText('bold')" title="Bold"><b>B</b></button>
                    <button type="button" onclick="formatText('italic')" title="Italic"><i>I</i></button>
                    <button type="button" onclick="formatText('underline')" title="Underline"><u>U</u></button>
                    <span class="rte-sep"></span>
                    <select onchange="formatFont(this.value); this.value='';" title="Font Family">
                        <option value="">Font</option>
                        <option value="Arial" style="font-family:Arial">Arial</option>
                        <option value="Georgia" style="font-family:Georgia">Georgia</option>
                        <option value="Times New Roman" style="font-family:Times New Roman">Times</option>
                        <option value="Courier New" style="font-family:Courier New">Courier</option>
                        <option value="Verdana" style="font-family:Verdana">Verdana</option>
                        <option value="Trebuchet MS" style="font-family:Trebuchet MS">Trebuchet</option>
                    </select>
                    <span class="rte-sep"></span>
                    <select onchange="formatColor(this.value); this.value='';" title="Text Color">
                        <option value="">Color</option>
                        <option value="#ff6b6b">🔴 Red</option>
                        <option value="#4ecdc4">🔵 Teal</option>
                        <option value="#667eea">🟣 Purple</option>
                        <option value="#f9ca24">🟡 Yellow</option>
                        <option value="#27ae60">🟢 Green</option>
                        <option value="#e17055">🟠 Orange</option>
                        <option value="#74b9ff">💙 Blue</option>
                        <option value="#ffffff">⚪ White</option>
                    </select>
                    <select onchange="formatFontSize(this.value); this.value='';" title="Font Size">
                        <option value="">Size</option>
                        <option value="1">8</option>
                        <option value="2">10</option>
                        <option value="3">12</option>
                        <option value="4">14</option>
                        <option value="5">18</option>
                        <option value="6">24</option>
                        <option value="7">36</option>
                    </select>
                    <span class="rte-sep"></span>
                    <button type="button" onclick="formatText('insertUnorderedList')" title="Bullet List">• List</button>
                    <span class="rte-sep"></span>
                    <button type="button" onclick="formatText('justifyLeft')" title="Align Left">⫷</button>
                    <button type="button" onclick="formatText('justifyCenter')" title="Align Center">☰</button>
                    <button type="button" onclick="formatText('justifyRight')" title="Align Right">⫸</button>
                    <button type="button" onclick="formatText('justifyFull')" title="Justify">≡</button>
                </div>
                <div class="rte-editor fc" id="bio" contenteditable="true" style="min-height: 200px;"><?= $profile_data['bio'] ?? '' ?></div>
            </div>
            <div class="fg-row">
                <label>Vision & Aspirations</label>
                <div class="rte-editor fc" id="vision" contenteditable="true" style="min-height: 120px; border-radius: 8px !important;"><?= $profile_data['vision'] ?? '' ?></div>
            </div>
            
            <div class="fr">
                <div class="fg-row">
                    <label>Location</label>
                    <input type="text" class="fc" id="location" value="<?= $profile_data['location'] ?? '' ?>">
                </div>
                <div class="fg-row">
                    <label>Email Address</label>
                    <input type="email" class="fc" id="email" value="<?= $profile_data['email'] ?? '' ?>">
                </div>
            </div>
            <div class="fg-row">
                <label>Facebook URL</label>
                <input type="text" class="fc" id="facebook" value="<?= $profile_data['facebook'] ?? '' ?>" placeholder="https://facebook.com/yourprofile">
            </div>
            <div class="img-up">
                <img src="img/Me.jpg" alt="Profile" class="cur-img" id="pprev" onerror="this.style.display='none'">
                <div>
                    <div style="font-size:.82rem;color:var(--muted);margin-bottom:9px;">Profile Photo</div>
                    <label for="pfile" class="up-btn">📷 Choose Photo</label>
                    <input type="file" id="pfile" accept="image/*" onchange="prevImg(this,'pprev')">
                </div>
            </div>
            <div class="fa-row">
                <button type="submit" class="btn btn-p">💾 Save Changes</button>
                <button type="reset" class="btn btn-g">↩ Reset</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ EDUCATION ═════════════════════════════════════════════ -->
<div class="sec" id="sec-education">
    <div class="ph">
        <h1>Education Timeline</h1>
        <p>Manage your academic background.</p>
    </div>
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">🎓</div>
                <h2>Education Records</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-p btn-sm" onclick="openEduModal()">+ Add New</button>
                <button class="btn btn-e btn-sm" onclick="backupSection('education')" title="Save current education as backup">📦 Backup</button>
                <button class="btn btn-g btn-sm" onclick="restoreSection('education')" title="Restore education from backup">♻️ Restore</button>
            </div>
        </div>
        <div class="crud" id="eduList"></div>
        <button class="add-row" onclick="openEduModal()">＋ Add Education Item</button>
    </div>
</div>

<!-- ═══ PROJECTS ══════════════════════════════════════════════ -->
<div class="sec" id="sec-projects">
    <div class="ph">
        <h1>Projects</h1>
        <p>Manage your projects.</p>
    </div>
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">💻</div>
                <h2>All Projects</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-p btn-sm" onclick="openProjModal()">+ Add New</button>
                <button class="btn btn-e btn-sm" onclick="backupSection('projects')" title="Save current projects as backup">📦 Backup</button>
                <button class="btn btn-g btn-sm" onclick="restoreSection('projects')" title="Restore projects from backup">♻️ Restore</button>
            </div>
        </div>
        <div class="crud" id="projList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;"></div>
        <button class="add-row" onclick="openProjModal()">＋ Add Project</button>
    </div>
</div>

<!-- ═══ HOBBIES ═══════════════════════════════════════════════ -->
<div class="sec" id="sec-hobbies">
    <div class="ph">
        <h1>Hobbies &amp; Skills</h1>
        <p>Manage your interests by category and items.</p>
    </div>
    
    <!-- Category Management -->
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">📚</div>
                <h2>Categories</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-p btn-sm" onclick="openModal('moCategoryHobby')">+ Add Category</button>
            </div>
        </div>
        <div id="hobbyCategories" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;"></div>
    </div>
    
    <!-- Hobby Items -->
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">⭐</div>
                <h2>My Interests</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-p btn-sm" onclick="openModal('moHobby')">+ Add Item</button>
                <button class="btn btn-e btn-sm" onclick="backupSection('hobbies')" title="Save current hobbies as backup">📦 Backup</button>
                <button class="btn btn-g btn-sm" onclick="restoreSection('hobbies')" title="Restore hobbies from backup">♻️ Restore</button>
            </div>
        </div>
        <div id="hobbyList"></div>
        <button class="add-row" onclick="openModal('moHobby')">＋ Add Hobby / Skill</button>
    </div>
</div>

<!-- ═══ CONTACT ═══════════════════════════════════════════════ -->
<div class="sec" id="sec-contact">
    <div class="ph">
        <h1>Contact Information</h1>
        <p>Manage your contact methods.</p>
    </div>
    <div class="card">
        <div class="card-h">
            <div class="card-t">
                <div class="card-t-ico">📧</div>
                <h2>Contact Methods</h2>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-p btn-sm" onclick="openModal('moContact')">+ Add New</button>
                <button class="btn btn-e btn-sm" onclick="backupSection('contacts')" title="Save current contacts as backup">📦 Backup</button>
                <button class="btn btn-g btn-sm" onclick="restoreSection('contacts')" title="Restore contacts from backup">♻️ Restore</button>
            </div>
        </div>
        <div class="cgrid" id="contactList"></div>
        <button class="add-row" onclick="openModal('moContact')">＋ Add Contact Method</button>
    </div>
</div>

</main>

<!-- ═══════════════ MODALS ════════════════════════════════════ -->

<!-- Education Modal -->
<div class="mo" id="moEdu">
    <div class="mbox">
        <div class="mhead">
            <h3 id="eduMTitle">Add Education</h3>
            <button class="mclose" onclick="closeModal('moEdu')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveEdu();">
            <input type="hidden" id="eduIdx">
            <div class="fg-row"><label>Year / Period</label><input type="text" class="fc" id="eduYear" placeholder="e.g., 2022 - Present"></div>
            <div class="fg-row"><label>Level</label><input type="text" class="fc" id="eduLevel" placeholder="e.g., College"></div>
            <div class="fg-row"><label>School / Institution</label><input type="text" class="fc" id="eduSchool" placeholder="School name"></div>
            <div class="fg-row"><label>Description</label><input type="text" class="fc" id="eduDesc" placeholder="e.g., BSIT - 3rd Year"></div>
            <div class="fg-row"><label>Achievements (one per line)</label><textarea class="fc" id="eduAch" rows="4" placeholder="Major in...&#10;Learning HTML..."></textarea></div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moEdu')">Cancel</button>
                <button type="submit" class="btn btn-p">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Project Modal -->
<div class="mo" id="moProj">
    <div class="mbox">
        <div class="mhead">
            <h3 id="projMTitle">Add Project</h3>
            <button class="mclose" onclick="closeModal('moProj')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveProj();">
            <input type="hidden" id="projIdx">
            <div class="fg-row"><label>Project Name</label><input type="text" class="fc" id="projTitle" placeholder="Project name"></div>
            <div class="fg-row"><label>Description</label><textarea class="fc" id="projDesc" rows="3" placeholder="Project description"></textarea></div>
            <div class="fg-row">
                <label>Upload Image</label>
                <div style="position:relative;">
                    <input type="file" id="projImage" accept="*/*" style="display:none;" onchange="previewProjImg(this)">
                    <button type="button" onclick="document.getElementById('projImage').click()" style="width:100%;padding:12px 16px;font-size:.95rem;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s ease;box-shadow:0 4px 15px rgba(102,126,234,.4);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(102,126,234,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(102,126,234,.4)'">📤 Choose File <span style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:12px;font-size:.8rem;">max 30MB</span></button>
                </div>
                <div id="projImagePreview" style="margin-top:10px;display:none;position:relative;">
                    <img id="projImageThumb" style="max-width:100%;max-height:160px;border-radius:8px;display:block;" />
                    <button type="button" onclick="clearProjImg()" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:.8rem;">✕</button>
                </div>
            </div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moProj')">Cancel</button>
                <button type="submit" class="btn btn-p">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Hobby Category Modal -->
<div class="mo" id="moCategoryHobby">
    <div class="mbox">
        <div class="mhead">
            <h3>Add Hobby Category</h3>
            <button class="mclose" onclick="closeModal('moCategoryHobby')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveHobbyCategory();">
            <div class="fg-row">
                <label>Category Name</label>
                <input type="text" class="fc" id="catName" placeholder="e.g., favorite_songs" required>
                <small style="color:var(--muted);">Internal name (lowercase, no spaces)</small>
            </div>
            <div class="fg-row">
                <label>Display Name</label>
                <input type="text" class="fc" id="catDisplayName" placeholder="e.g., Favorite Songs" required>
            </div>
            <div class="fg-row">
                <label>Icon/Emoji</label>
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <input type="text" class="fc" id="catIcon" placeholder="🎵" maxlength="5" value="⭐" style="flex:1;">
                    <button type="button" onclick="toggleEmojiPicker('catEmojiPicker')" style="padding:8px 16px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">🎨 Pick</button>
                </div>
                <div id="catEmojiPicker" style="display:none;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;padding:12px;max-height:150px;overflow-y:auto;margin-bottom:8px;">
                    <input type="text" class="fc" id="emojiSearch" placeholder="Search: music, game, book..." style="margin-bottom:8px;width:100%;" onkeyup="filterEmojis(this.value)">
                    <div id="emojiGrid" style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px;"></div>
                </div>
            </div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moCategoryHobby')">Cancel</button>
                <button type="submit" class="btn btn-p">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="mo" id="moEditCategoryHobby">
    <div class="mbox">
        <div class="mhead">
            <h3>Edit Hobby Category</h3>
            <button class="mclose" onclick="closeModal('moEditCategoryHobby')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveEditHobbyCategory();">
            <input type="hidden" id="editCatId">
            <div class="fg-row">
                <label>Display Name</label>
                <input type="text" class="fc" id="editCatDisplayName" placeholder="e.g., Favorite Songs" required>
            </div>
            <div class="fg-row">
                <label>Icon/Emoji</label>
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <input type="text" class="fc" id="editCatIcon" placeholder="🎵" maxlength="5" style="flex:1;">
                    <button type="button" onclick="toggleEmojiPicker('editCatEmojiPicker')" style="padding:8px 16px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">🎨 Pick</button>
                </div>
                <div id="editCatEmojiPicker" style="display:none;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;padding:12px;max-height:150px;overflow-y:auto;margin-bottom:8px;">
                    <input type="text" class="fc" id="editEmojiSearch" placeholder="Search: music, game, book..." style="margin-bottom:8px;width:100%;" onkeyup="filterEditEmojis(this.value)">
                    <div id="editEmojiGrid" style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px;"></div>
                </div>
            </div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moEditCategoryHobby')">Cancel</button>
                <button type="submit" class="btn btn-p">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Hobby Modal -->
<div class="mo" id="moHobby">
    <div class="mbox">
        <div class="mhead">
            <h3>Add Hobby / Skill</h3>
            <button class="mclose" onclick="closeModal('moHobby')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveHobby();">
            <div class="fg-row">
                <label>Category</label>
                <select class="fc" id="hobbyCat" onchange="toggleHobbyAudioField()">
                    <option value="">Loading categories...</option>
                </select>
            </div>
            <div class="fg-row"><label>Name</label><input type="text" class="fc" id="hobbyName" placeholder="e.g., One Piece"></div>
            <div class="fg-row"><label>Description</label><textarea class="fc" id="hobbyDesc" rows="2" placeholder="Short description (optional)"></textarea></div>
            <div class="fg-row">
                <label>Image</label>
                <div style="position:relative;">
                    <input type="file" id="hobbyImg" accept="*/*" style="display:none;" onchange="previewHobbyImg(this)">
                    <button type="button" onclick="document.getElementById('hobbyImg').click()" style="width:100%;padding:12px 16px;font-size:.95rem;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s ease;box-shadow:0 4px 15px rgba(102,126,234,.4);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(102,126,234,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(102,126,234,.4)'">📂 Choose Image <span style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:12px;font-size:.8rem;">max 10MB</span></button>
                </div>
                <div id="hobbyImgPreview" style="margin-top:10px;display:none;position:relative;">
                    <img id="hobbyImgThumb" style="max-width:100%;max-height:120px;border-radius:8px;display:block;" />
                    <button type="button" onclick="clearHobbyImg()" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:.8rem;">✕</button>
                </div>
            </div>
            <div class="fg-row" id="hobbyAudioRow" style="display:none;">
                <label>🎵 Audio File (Music Categories)</label>
                <div style="position:relative;">
                    <input type="file" id="hobbyAudio" accept=".mp3,.wav,.m4a,.aac,.ogg,.flac,.wma" style="display:none;" onchange="previewHobbyAudio(this)">
                    <button type="button" onclick="document.getElementById('hobbyAudio').click()" style="width:100%;padding:12px 16px;font-size:.95rem;background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s ease;box-shadow:0 4px 15px rgba(245,87,108,.4);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(245,87,108,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(245,87,108,.4)'">🎵 Choose Audio <span style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:12px;font-size:.8rem;">max 50MB</span></button>
                </div>
                <div id="hobbyAudioPreview" style="margin-top:10px;display:none;">
                    <div style="padding:10px;background:var(--dark-fg);border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
                        <span id="hobbyAudioName" style="flex:1;font-size:0.9rem;word-break:break-all;">-</span>
                        <button type="button" onclick="clearHobbyAudio()" style="background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:.8rem;margin-left:10px;">✕</button>
                    </div>
                </div>
            </div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moHobby')">Cancel</button>
                <button type="submit" class="btn btn-p">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Hobby Modal -->
<div class="mo" id="moEditHobby">
    <div class="mbox">
        <div class="mhead">
            <h3>Edit Hobby / Skill</h3>
            <button class="mclose" onclick="closeModal('moEditHobby')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveHobEdit();">
            <input type="hidden" id="editHobId">
            <input type="hidden" id="editHobCatId">
            <div style="text-align: center; margin-bottom: 20px;">
                <img id="editHobPreview" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid var(--border);">
            </div>
            <div class="fg-row">
                <label>Item Name</label>
                <input type="text" class="fc" id="editHobName" placeholder="e.g., One Piece">
            </div>
            <div class="fg-row">
                <label>New Image (optional)</label>
                <div style="position:relative;">
                    <input type="file" id="editHobFile" accept="*/*" style="display:none;" onchange="previewEditHobImg(this)">
                    <button type="button" onclick="document.getElementById('editHobFile').click()" style="width:100%;padding:12px 16px;font-size:.95rem;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s ease;box-shadow:0 4px 15px rgba(102,126,234,.4);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(102,126,234,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(102,126,234,.4)'">📂 Choose Image <span style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:12px;font-size:.8rem;">max 10MB</span></button>
                </div>
                <p style="font-size:0.75rem;color:var(--muted);margin-top:6px;">Leave empty to keep current image</p>
            </div>
            <div class="fg-row" id="editHobAudioRow" style="display:none;">
                <label>🎵 Audio File (optional)</label>
                <div style="position:relative;">
                    <input type="file" id="editHobAudio" accept=".mp3,.wav,.m4a,.aac,.ogg,.flac,.wma" style="display:none;" onchange="previewEditHobbyAudio(this)">
                    <button type="button" onclick="document.getElementById('editHobAudio').click()" style="width:100%;padding:12px 16px;font-size:.95rem;background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s ease;box-shadow:0 4px 15px rgba(245,87,108,.4);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(245,87,108,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(245,87,108,.4)'">🎵 Choose Audio <span style="background:rgba(255,255,255,.2);padding:2px 10px;border-radius:12px;font-size:.8rem;">max 50MB</span></button>
                </div>
                <div id="editHobAudioPreview" style="margin-top:10px;display:none;">
                    <div style="padding:10px;background:var(--dark-fg);border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
                        <span id="editHobAudioName" style="flex:1;font-size:0.9rem;word-break:break-all;">-</span>
                        <button type="button" onclick="clearEditHobbyAudio()" style="background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:.8rem;margin-left:10px;">✕</button>
                    </div>
                </div>
                <p style="font-size:0.75rem;color:var(--muted);margin-top:6px;">Leave empty to keep current audio</p>
            </div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moEditHobby')">Cancel</button>
                <button type="submit" class="btn btn-p">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Contact Modal -->
<div class="mo" id="moContact">
    <div class="mbox">
        <div class="mhead">
            <h3 id="ctMTitle">Add Contact Method</h3>
            <button class="mclose" onclick="closeModal('moContact')">✕</button>
        </div>
        <form class="fg" onsubmit="event.preventDefault(); saveContact();">
            <input type="hidden" id="ctIdx">
            <div class="fg-row"><label>Type (e.g., Twitter, GitHub)</label><input type="text" class="fc" id="ctType" placeholder="Email, Facebook..."></div>
            <div class="fg-row"><label>Icon (emoji)</label><input type="text" class="fc" id="ctIcon" placeholder="📧"></div>
            <div class="fg-row"><label>Value / Username</label><input type="text" class="fc" id="ctVal" placeholder="yourname@email.com"></div>
            <div class="mfoot">
                <button type="button" class="btn btn-g" onclick="closeModal('moContact')">Cancel</button>
                <button type="submit" class="btn btn-p">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ JAVASCRIPT ════════════════════════════════ -->
<script>
// ── MOBILE SIDEBAR TOGGLE ────────────────────────────────────
function toggleSidebar() {
    const sb = document.getElementById('sb');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sb && overlay) {
        if (sb.classList.contains('open')) {
            sb.classList.remove('open');
            overlay.style.display = 'none';
        } else {
            sb.classList.add('open');
            overlay.style.display = 'block';
        }
    }
}

function closeSidebar() {
    const sb = document.getElementById('sb');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sb && overlay) {
        sb.classList.remove('open');
        overlay.style.display = 'none';
    }
}

// Close sidebar when window resizes to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 860) {
        const sb = document.getElementById('sb');
        const overlay = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.remove('open');
        if (overlay) overlay.style.display = 'none';
    }
});

// ── DATA (injected from PHP) ─────────────────────────────────
let edu  = <?= json_encode($education_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let proj = <?= json_encode($projects_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let hob  = <?= json_encode($hobbies_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let ct   = <?= json_encode($contact_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// ── HOBBY CATEGORIES (loaded dynamically) ───────────────────
let hobbyCategories = [];

function loadHobbyCategories() {
    fetch('api/hobbies.php?action=categories')
        .then(r => r.json())
        .then(d => {
            if (d.success && d.categories) {
                hobbyCategories = d.categories;
                updateHobbyCategoryDropdown();
            }
        })
        .catch(e => console.warn('Failed to load hobby categories:', e));
}

function updateHobbyCategoryDropdown() {
    const select = document.getElementById('hobbyCat');
    if (!select) return;
    
    if (hobbyCategories.length === 0) {
        select.innerHTML = '<option value="">No categories available - create one first</option>';
        return;
    }
    
    const currentValue = select.value;
    select.innerHTML = hobbyCategories
        .map(cat => `<option value="${cat.id}" data-name="${cat.category_name}">${cat.display_name}</option>`)
        .join('');
    
    if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
        select.value = currentValue;
    } else if (hobbyCategories.length > 0) {
        select.value = hobbyCategories[0].id;
    }
    
    renderHobbyCategories();
}

function renderHobbyCategories() {
    const container = document.getElementById('hobbyCategories');
    if (!container) return;
    
    if (hobbyCategories.length === 0) {
        container.innerHTML = '<p style="grid-column:1/-1;color:var(--muted);text-align:center;padding:20px;">No categories yet. Add one to get started!</p>';
        return;
    }
    
    container.innerHTML = hobbyCategories.map(cat => `
        <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:8px;position:relative;">
            <div style="font-size:1.5rem;text-align:center;">${cat.category_icon || '⭐'}</div>
            <div style="font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${cat.display_name}</div>
            <div style="font-size:0.8rem;color:var(--muted);text-align:center;">${cat.category_name}</div>
            <div style="display:flex;gap:4px;margin-top:auto;">
                <button class="btn btn-e btn-sm" style="flex:1;font-size:0.75rem;padding:4px;" onclick="editHobbyCategory(${cat.id})">Edit</button>
                <button class="btn btn-d btn-sm" style="flex:1;font-size:0.75rem;padding:4px;" onclick="delHobbyCategory(${cat.id})">Delete</button>
            </div>
        </div>
    `).join('');
}

function saveHobbyCategory() {
    const catName = document.getElementById('catName').value.trim();
    const catDisplay = document.getElementById('catDisplayName').value.trim();
    const catIcon = document.getElementById('catIcon').value.trim() || '⭐';
    
    if (!catName || !catDisplay) {
        showToast('Category name and display name are required!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('category_name', catName);
    formData.append('display_name', catDisplay);
    formData.append('category_icon', catIcon);
    formData.append('sort_order', hobbyCategories.length + 1);
    
    fetch('api/hobbies.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeModal('moCategoryHobby');
                document.getElementById('catName').value = '';
                document.getElementById('catDisplayName').value = '';
                document.getElementById('catIcon').value = '⭐';
                showToast('Category added! Refreshing...');
                setTimeout(() => loadHobbyCategories(), 1000);
            } else {
                showToast('Error: ' + (d.message || 'Failed to add category'));
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

function delHobbyCategory(catId) {
    if (!confirm('Delete this category? Items in this category will also be deleted!')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('id', catId);
    
    fetch('api/hobbies.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast('Category deleted!');
                setTimeout(() => loadHobbyCategories(), 500);
            } else {
                showToast('Error: ' + (d.message || 'Failed to delete'));
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

// ── EMOJI PICKER ──────────────────────────────────────────────
const emojiList = [
    // Music & Entertainment
    { emoji: '🎵', keywords: 'music song' },
    { emoji: '🎶', keywords: 'music melody' },
    { emoji: '🎤', keywords: 'music sing microphone' },
    { emoji: '🎧', keywords: 'music headphones audio' },
    { emoji: '🎸', keywords: 'music guitar' },
    { emoji: '🎹', keywords: 'music piano keyboard' },
    { emoji: '🎺', keywords: 'music trumpet' },
    { emoji: '🎻', keywords: 'music violin' },
    // Gaming
    { emoji: '🎮', keywords: 'game gaming video' },
    { emoji: '🕹️', keywords: 'game joystick arcade' },
    { emoji: '🎯', keywords: 'game target' },
    { emoji: '🎲', keywords: 'game dice' },
    { emoji: '🃏', keywords: 'game card' },
    // Sports & Fitness
    { emoji: '⚽', keywords: 'sports soccer football' },
    { emoji: '🏀', keywords: 'sports basketball' },
    { emoji: '🏈', keywords: 'sports football' },
    { emoji: '⚾', keywords: 'sports baseball' },
    { emoji: '🎾', keywords: 'sports tennis' },
    { emoji: '🏐', keywords: 'sports volleyball' },
    { emoji: '🏸', keywords: 'sports badminton' },
    { emoji: '🏓', keywords: 'sports ping pong' },
    { emoji: '🏋️', keywords: 'fitness gym weight' },
    { emoji: '🤸', keywords: 'fitness gymnastics' },
    { emoji: '🧘', keywords: 'fitness yoga' },
    { emoji: '🚴', keywords: 'fitness bike cycling' },
    { emoji: '🏃', keywords: 'fitness running' },
    { emoji: '🤾', keywords: 'sports handball' },
    // Entertainment
    { emoji: '🎬', keywords: 'movie film cinema' },
    { emoji: '📺', keywords: 'tv television series' },
    { emoji: '🎭', keywords: 'theater drama entertainment' },
    { emoji: '🎪', keywords: 'circus entertainment' },
    // Arts & Creativity
    { emoji: '🎨', keywords: 'art painting drawing' },
    { emoji: '🖌️', keywords: 'art paintbrush draw' },
    { emoji: '🖍️', keywords: 'art crayon draw' },
    { emoji: '📐', keywords: 'design geometry' },
    // Books & Learning
    { emoji: '📚', keywords: 'book reading learn' },
    { emoji: '📖', keywords: 'book reading' },
    { emoji: '📝', keywords: 'write writing' },
    { emoji: '✏️', keywords: 'write pencil' },
    // Hobbies
    { emoji: '🍕', keywords: 'food cooking' },
    { emoji: '👨‍🍳', keywords: 'cooking chef' },
    { emoji: '🧗', keywords: 'climbing adventure' },
    { emoji: '🏕️', keywords: 'camping outdoor' },
    { emoji: '🎣', keywords: 'fishing' },
    { emoji: '📷', keywords: 'photography camera' },
    { emoji: '🚗', keywords: 'car driving' },
    { emoji: '✈️', keywords: 'travel flight' },
    { emoji: '🌍', keywords: 'travel world' },
    // Anime
    { emoji: '🎌', keywords: 'anime japanese' },
    { emoji: '⛩️', keywords: 'anime japan culture' },
    { emoji: '🗻', keywords: 'japan mount fuji' },
    // Popular
    { emoji: '⭐', keywords: 'star favorite' },
    { emoji: '❤️', keywords: 'love heart favorite' },
    { emoji: '💎', keywords: 'special premium' },
    { emoji: '🔥', keywords: 'hot popular trending' },
    { emoji: '🌟', keywords: 'star sparkle' },
    { emoji: '✨', keywords: 'sparkle shine' },
];

function toggleEmojiPicker(pickerId) {
    const picker = document.getElementById(pickerId);
    if (picker.style.display === 'none') {
        picker.style.display = 'block';
        renderEmojiGrid(pickerId);
    } else {
        picker.style.display = 'none';
    }
}

function renderEmojiGrid(pickerId) {
    const gridId = pickerId === 'catEmojiPicker' ? 'emojiGrid' : 'editEmojiGrid';
    const grid = document.getElementById(gridId);
    const inputId = pickerId === 'catEmojiPicker' ? 'emojiSearch' : 'editEmojiSearch';
    const searchVal = document.getElementById(inputId).value.toLowerCase();
    
    const filtered = searchVal ? emojiList.filter(e => e.keywords.includes(searchVal)) : emojiList;
    
    grid.innerHTML = filtered.map(e => {
        const clickFn = pickerId === 'catEmojiPicker' ? `selectEmoji('${e.emoji}')` : `selectEditEmoji('${e.emoji}')`;
        return `<button type="button" style="background:rgba(255,255,255,0.1);border:1px solid var(--border);border-radius:6px;padding:8px;cursor:pointer;font-size:1.3rem;transition:all .2s;" onmouseover="this.background='rgba(102,126,234,0.3)'" onclick="${clickFn}; return false;">${e.emoji}</button>`;
    }).join('');
}

function filterEmojis(searchVal) {
    renderEmojiGrid('catEmojiPicker');
}

function filterEditEmojis(searchVal) {
    renderEmojiGrid('editCatEmojiPicker');
}

function selectEmoji(emoji) {
    document.getElementById('catIcon').value = emoji;
    document.getElementById('emojiSearch').value = '';
    document.getElementById('catEmojiPicker').style.display = 'none';
}

function selectEditEmoji(emoji) {
    document.getElementById('editCatIcon').value = emoji;
    document.getElementById('editEmojiSearch').value = '';
    document.getElementById('editCatEmojiPicker').style.display = 'none';
}

// ── CATEGORY EDIT ──────────────────────────────────────────────
function editHobbyCategory(catId) {
    const cat = hobbyCategories.find(c => c.id === catId);
    if (!cat) {
        showToast('Category not found!');
        return;
    }
    
    document.getElementById('editCatId').value = catId;
    document.getElementById('editCatDisplayName').value = cat.display_name;
    document.getElementById('editCatIcon').value = cat.category_icon || '⭐';
    document.getElementById('editEmojiSearch').value = '';
    document.getElementById('editEmojiGrid').innerHTML = '';
    document.getElementById('editCatEmojiPicker').style.display = 'none';
    
    openModal('moEditCategoryHobby');
}

function saveEditHobbyCategory() {
    const catId = document.getElementById('editCatId').value;
    const catDisplay = document.getElementById('editCatDisplayName').value.trim();
    const catIcon = document.getElementById('editCatIcon').value.trim() || '⭐';
    
    if (!catDisplay) {
        showToast('Display name is required!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_category');
    formData.append('id', catId);
    formData.append('display_name', catDisplay);
    formData.append('category_icon', catIcon);
    
    fetch('api/hobbies.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeModal('moEditCategoryHobby');
                showToast('Category updated!');
                setTimeout(() => loadHobbyCategories(), 1000);
            } else {
                showToast('Error: ' + (d.message || 'Failed to update'));
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

// Load categories when page loads
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(loadHobbyCategories, 500);
});

// ── NAV ──────────────────────────────────────────────────────
const tbTitles = {
    dashboard: ['Dashboard','/ Overview'],
    profile:   ['Profile Management','/ Edit'],
    education: ['Education','/ Timeline'],
    projects:  ['Projects','/ Manage'],
    hobbies:   ['Hobbies & Skills','/ Manage'],
    contact:   ['Contact Info','/ Manage'],
};

function goTo(name, el) {
    document.querySelectorAll('.sec').forEach(s => s.classList.remove('on'));
    const sec = document.getElementById('sec-' + name);
    if (sec) sec.classList.add('on');

    document.querySelectorAll('.sb-item').forEach(n => n.classList.remove('on'));
    if (el) { el.classList.add('on'); }
    else {
        document.querySelectorAll('.sb-item').forEach(n => {
            if (n.getAttribute('onclick') && n.getAttribute('onclick').includes("'" + name + "'")) n.classList.add('on');
        });
    }

    const t = tbTitles[name] || [name,''];
    document.getElementById('tbTitle').innerHTML = t[0] + ' <span>' + t[1] + '</span>';

    if (name === 'education') renderEdu();
    if (name === 'projects')  renderProj();
    if (name === 'hobbies')   renderHob();
    if (name === 'contact')   renderCt();
    
    // Close mobile sidebar after navigation
    closeSidebar();
}

// ── TOAST ────────────────────────────────────────────────────
function showToast(msg) {
    const t = document.getElementById('toast');
    t.innerHTML = '✅ ' + (msg || 'Saved successfully!');
    t.classList.add('on');
    setTimeout(() => t.classList.remove('on'), 3000);
}

// ── MODALS ───────────────────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('open');
    
    // Ensure categories are loaded when opening hobby modals
    if (id === 'moHobby' || id === 'moEditHobby') {
        if (hobbyCategories.length === 0) {
            loadHobbyCategories();
        } else {
            updateHobbyCategoryDropdown();
        }
    }
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.mo').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// ── EDUCATION ────────────────────────────────────────────────
function renderEdu() {
    const c = document.getElementById('eduList');
    if (!edu.length) { c.innerHTML = '<p style="color:var(--muted);text-align:center;padding:16px;">No records yet.</p>'; return; }
    c.innerHTML = edu.map((e, i) => `
    <div class="ci">
        <div class="ci-head">
            <span class="ci-badge">${e.year}</span>
            <div class="ci-acts">
                <button class="btn btn-e btn-sm" onclick="editEdu(${i})">✏️ Edit</button>
                <button class="btn btn-d btn-sm" onclick="delEdu(${i})">🗑</button>
            </div>
        </div>
        <div class="ci-title">${e.title}</div>
        <div class="ci-sub">${e.school}</div>
        ${e.description ? `<div class="ci-desc">${e.description}</div>` : ''}
        ${e.achievements && e.achievements.length ? `<div class="tags">${e.achievements.map(a=>`<span class="tag">✓ ${a}</span>`).join('')}</div>` : ''}
    </div>`).join('');
}

function openEduModal(i) {
    document.getElementById('eduMTitle').textContent = i == null ? 'Add Education' : 'Edit Education';
    document.getElementById('eduIdx').value = i == null ? '' : i;
    const e = i == null ? {} : edu[i];
    document.getElementById('eduYear').value   = e.year || '';
    document.getElementById('eduLevel').value  = e.title || '';
    document.getElementById('eduSchool').value = e.school || '';
    document.getElementById('eduDesc').value   = e.description || '';
    document.getElementById('eduAch').value    = (e.achievements || []).join('\n');
    openModal('moEdu');
}

function editEdu(i) { openEduModal(i); }

function saveEdu() {
    const idx = document.getElementById('eduIdx').value;
    const year = document.getElementById('eduYear').value;
    const title = document.getElementById('eduLevel').value;
    const school = document.getElementById('eduSchool').value;
    const description = document.getElementById('eduDesc').value;
    const achievements = document.getElementById('eduAch').value;
    
    if (!year || !school) {
        showToast('Year and School are required!');
        return;
    }
    
    const formData = new FormData();
    formData.append('year', year);
    formData.append('title', title);
    formData.append('school', school);
    formData.append('description', description);
    formData.append('achievements', achievements);
    
    const action = idx === '' ? 'add' : 'update';
    formData.append('action', action);
    if (action === 'update') formData.append('id', edu[+idx].id);
    
    fetch('api/education.php', { method: 'POST', body: formData })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
            return r.text();
        })
        .then(text => {
            try {
                const d = JSON.parse(text);
                if (d.success) {
                    closeModal('moEdu');
                    showToast(d.message || 'Education saved!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(d.message || 'Error saving education');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e, 'Response:', text);
                showToast('Error: Invalid response from server. Check console.');
            }
        })
        .catch(e => {
            console.error('Fetch Error:', e);
            showToast('Error: ' + e.message);
        });
}

function delEdu(i) {
    if (!confirm('Delete this record?')) return;
    const id = edu[i].id;
    fetch('api/education.php', { 
        method: 'DELETE', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id 
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Education record deleted successfully!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Error deleting education');
        }
    })
    .catch(e => showToast('Error: ' + e.message));
}

function delActivity(id) {
    if (!confirm('Delete this activity record?')) return;
    fetch('api/activity.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete&id=' + id 
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Activity record deleted successfully!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Error deleting activity');
        }
    })
    .catch(e => showToast('Error: ' + e.message));
}

// ── PROJECTS ─────────────────────────────────────────────────
function renderProj() {
    const c = document.getElementById('projList');
    if (!proj.length) { c.innerHTML = '<p style="color:var(--muted);text-align:center;padding:16px;grid-column:1/-1;">No projects yet.</p>'; return; }
    c.innerHTML = proj.map((p, i) => {
        const placeholder = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 225'%3E%3Crect fill='%2316162e' width='400' height='225'/%3E%3Ctext x='50%25' y='50%25' font-size='18' fill='%235a6fb8' text-anchor='middle' dy='.3em'%3E📁 No Image%3C/text%3E%3C/svg%3E`;
        const imgSrc = p.image ? p.image : placeholder;
        return `<div class="ci" style="border-radius:12px;overflow:hidden;">
            <div style="width:100%;height:160px;background:#16162e;overflow:hidden;">
                <img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='${placeholder}'" />
            </div>
            <div style="padding:12px;">
                <div class="ci-head">
                    <div class="ci-acts">
                        <button class="btn btn-e btn-sm" onclick="editProj(${i})">✏️ Edit</button>
                        <button class="btn btn-d btn-sm" onclick="delProj(${i})">🗑</button>
                    </div>
                </div>
                <div class="ci-title">${p.title}</div>
                <div class="ci-desc">${p.description}</div>
            </div>
        </div>`;
    }).join('');
}

function previewProjImg(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 30 * 1024 * 1024) {
        showToast('File exceeds 30MB limit!');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('projImageThumb').src = e.target.result;
        document.getElementById('projImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearProjImg() {
    document.getElementById('projImage').value = '';
    document.getElementById('projImagePreview').style.display = 'none';
}

function openProjModal(i) {
    document.getElementById('projMTitle').textContent = i == null ? 'Add Project' : 'Edit Project';
    document.getElementById('projIdx').value = i == null ? '' : i;
    const p = i == null ? {} : proj[i];
    document.getElementById('projTitle').value = p.title || '';
    document.getElementById('projDesc').value  = p.description || '';
    document.getElementById('projImage').value = '';
    if (i != null && p.image) {
        document.getElementById('projImageThumb').src = p.image;
        document.getElementById('projImagePreview').style.display = 'block';
    } else {
        document.getElementById('projImagePreview').style.display = 'none';
    }
    openModal('moProj');
}

function editProj(i) { openProjModal(i); }

function saveProj() {
    const idx = document.getElementById('projIdx').value;
    const title = document.getElementById('projTitle').value;
    const description = document.getElementById('projDesc').value;
    const imageFile = document.getElementById('projImage').files[0];
    
    if (!title) {
        showToast('Title is required!');
        return;
    }
    
    const maxSize = 30 * 1024 * 1024; // 30MB
    if (imageFile && imageFile.size > maxSize) {
        showToast('File exceeds 30MB limit! Selected: ' + (imageFile.size / 1024 / 1024).toFixed(1) + 'MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    if (imageFile) formData.append('image', imageFile);
    
    const action = idx === '' ? 'add' : 'update';
    formData.append('action', action);
    if (action === 'update') formData.append('id', proj[+idx].id);
    
    fetch('api/projects.php', { method: 'POST', body: formData })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
            return r.text();
        })
        .then(text => {
            try {
                const d = JSON.parse(text);
                if (d.success) {
                    closeModal('moProj');
                    showToast(d.message || 'Project saved!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(d.message || 'Error saving project');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e, 'Response:', text);
                showToast('Error: Invalid response from server. Check console.');
            }
        })
        .catch(e => {
            console.error('Fetch Error:', e);
            showToast('Error: ' + e.message);
        });
}

function delProj(i) {
    if (!confirm('Delete this project?')) return;
    const id = proj[i].id;
    fetch('api/projects.php', { 
        method: 'DELETE', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id 
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Project deleted successfully!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Error deleting project');
        }
    })
    .catch(e => showToast('Error: ' + e.message));
}

// ── HOBBIES ──────────────────────────────────────────────────
function renderHob() {
    const c = document.getElementById('hobbyList');
    if (!hob.length) { c.innerHTML = '<p style="color:var(--muted);text-align:center;padding:16px;">No hobbies added yet.</p>'; return; }
    c.innerHTML = hob.map((cat, ci) => `
    <div class="hcat">
        <div class="hcat-l">${cat.category}</div>
        <div class="hgrid">
            ${(cat.items||[]).map((item, ii) => `
            <div class="hcard">
                <img class="hcard-img" src="img/${item.image}" alt="${item.name}"
                    onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22160%22 height=%2295%22><rect width=%22160%22 height=%2295%22 fill=%22%2316162e%22/><text x=%2280%22 y=%2254%22 text-anchor=%22middle%22 fill=%22%238b92c0%22 font-size=%2228%22>⭐</text></svg>'">
                <div class="hcard-body">
                    <span class="hcard-name">${item.name}</span>
                    <div style="display:flex;gap:4px;">
                        <button class="btn btn-e btn-sm" style="padding:3px 8px;font-size:.68rem;" onclick="editHobImg(${ci},${ii})">✏️</button>
                        <button class="btn btn-d btn-sm" style="padding:3px 8px;font-size:.68rem;" onclick="delHob(${ci},${ii})">🗑</button>
                    </div>
                </div>
            </div>`).join('')}
        </div>
    </div>`).join('');
}

function previewHobbyImg(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        showToast('File exceeds 10MB limit!');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('hobbyImgThumb').src = e.target.result;
        document.getElementById('hobbyImgPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearHobbyImg() {
    document.getElementById('hobbyImg').value = '';
    document.getElementById('hobbyImgPreview').style.display = 'none';
}

function previewHobbyAudio(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 50 * 1024 * 1024) {
        showToast('Audio file exceeds 50MB limit!');
        input.value = '';
        return;
    }
    document.getElementById('hobbyAudioName').textContent = '🎵 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + 'MB)';
    document.getElementById('hobbyAudioPreview').style.display = 'block';
}

function clearHobbyAudio() {
    document.getElementById('hobbyAudio').value = '';
    document.getElementById('hobbyAudioPreview').style.display = 'none';
}

function toggleHobbyAudioField() {
    const catId = document.getElementById('hobbyCat').value;
    const category = hobbyCategories.find(c => c.id === parseInt(catId));
    const audioRow = document.getElementById('hobbyAudioRow');
    
    if (category && category.category_name && 
        (category.category_name.toLowerCase().includes('music') || 
         category.category_name.toLowerCase().includes('song') ||
         category.display_name.toLowerCase().includes('music') ||
         category.display_name.toLowerCase().includes('song'))) {
        audioRow.style.display = 'block';
    } else {
        audioRow.style.display = 'none';
        clearHobbyAudio();
    }
}

function saveHobby() {
    const catSelect = document.getElementById('hobbyCat');
    const categoryId = parseInt(catSelect.value) || hobbyCategories[0]?.id || 1;
    const name = document.getElementById('hobbyName').value.trim();
    const description = document.getElementById('hobbyDesc').value.trim();
    const imageFile = document.getElementById('hobbyImg').files[0];
    const audioFile = document.getElementById('hobbyAudio').files[0];
    
    // Only name is required
    if (!name) {
        showToast('⚠️ Hobby name is required!');
        return;
    }
    
    // Validate file sizes if files are provided
    const maxImgSize = 10 * 1024 * 1024; // 10MB
    if (imageFile && imageFile.size > maxImgSize) {
        showToast('Image exceeds 10MB limit! Selected: ' + (imageFile.size / 1024 / 1024).toFixed(1) + 'MB');
        return;
    }
    
    const maxAudioSize = 50 * 1024 * 1024; // 50MB
    if (audioFile && audioFile.size > maxAudioSize) {
        showToast('Audio exceeds 50MB limit! Selected: ' + (audioFile.size / 1024 / 1024).toFixed(1) + 'MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('category_id', categoryId);
    formData.append('item_name', name);
    formData.append('description', description);
    if (imageFile) {
        formData.append('image', imageFile);
    } else {
        formData.append('image', 'placeholder.jpg');
    }
    if (audioFile) {
        formData.append('audio', audioFile);
    }
    
    fetch('api/hobbies.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeModal('moHobby');
                showToast(d.message || 'Hobby added!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(d.message || 'Error saving hobby');
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

function delHob(ci, ii) {
    if (!confirm('Delete this hobby?')) return;
    const id = hob[ci].items[ii].id;
    fetch('api/hobbies.php', { 
        method: 'DELETE', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id 
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Hobby deleted successfully!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Error deleting hobby');
        }
    })
    .catch(e => showToast('Error: ' + e.message));
}

function editHobImg(ci, ii) {
    const item = hob[ci].items[ii];
    document.getElementById('editHobId').value = item.id;
    document.getElementById('editHobCatId').value = hob[ci].category_id;
    document.getElementById('editHobName').value = item.name;
    document.getElementById('editHobPreview').src = 'img/' + item.image;
    document.getElementById('editHobFile').value = '';
    document.getElementById('editHobAudio').value = '';
    clearEditHobbyAudio();
    toggleEditHobbyAudioField(hob[ci].category_id);
    openModal('moEditHobby');
}

function previewEditHobImg(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        showToast('File exceeds 10MB limit!');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('editHobPreview').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function previewEditHobbyAudio(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 50 * 1024 * 1024) {
        showToast('Audio file exceeds 50MB limit!');
        input.value = '';
        return;
    }
    document.getElementById('editHobAudioName').textContent = '🎵 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + 'MB)';
    document.getElementById('editHobAudioPreview').style.display = 'block';
}

function clearEditHobbyAudio() {
    document.getElementById('editHobAudio').value = '';
    document.getElementById('editHobAudioPreview').style.display = 'none';
}

function toggleEditHobbyAudioField(categoryId) {
    const category = hobbyCategories.find(c => c.id === parseInt(categoryId));
    const audioRow = document.getElementById('editHobAudioRow');
    
    if (category && category.category_name && 
        (category.category_name.toLowerCase().includes('music') || 
         category.category_name.toLowerCase().includes('song') ||
         category.display_name.toLowerCase().includes('music') ||
         category.display_name.toLowerCase().includes('song'))) {
        audioRow.style.display = 'block';
    } else {
        audioRow.style.display = 'none';
        clearEditHobbyAudio();
    }
}

function saveHobEdit() {
    const id = document.getElementById('editHobId').value;
    const name = document.getElementById('editHobName').value.trim();
    const categoryId = document.getElementById('editHobCatId').value;
    const imgFile = document.getElementById('editHobFile').files[0];
    const audioFile = document.getElementById('editHobAudio').files[0];
    
    if (!name) {
        showToast('Item name is required');
        return;
    }
    
    if (imgFile && imgFile.size > 10 * 1024 * 1024) {
        showToast('Image exceeds 10MB limit! Selected: ' + (imgFile.size / 1024 / 1024).toFixed(1) + 'MB');
        return;
    }
    
    if (audioFile && audioFile.size > 50 * 1024 * 1024) {
        showToast('Audio exceeds 50MB limit! Selected: ' + (audioFile.size / 1024 / 1024).toFixed(1) + 'MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_details');
    formData.append('id', id);
    formData.append('item_name', name);
    formData.append('category_id', categoryId);
    if (imgFile) {
        formData.append('image', imgFile);
    }
    if (audioFile) {
        formData.append('audio', audioFile);
    }
    
    fetch('api/hobbies.php', { method: 'POST', body: formData })
        .then(r => r.text())
        .then(text => {
            try {
                const d = JSON.parse(text);
                if (d.success) {
                    closeModal('moEditHobby');
                    showToast(d.message || 'Hobby updated!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(d.message || 'Error updating hobby');
                }
            } catch(e) {
                console.error('Response:', text);
                showToast('Error: ' + e.message);
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

// ── CONTACT ──────────────────────────────────────────────────
function renderCt() {
    const c = document.getElementById('contactList');
    if (!ct.length) { c.innerHTML = '<p style="color:var(--muted);text-align:center;padding:16px;">No contact methods yet.</p>'; return; }
    c.innerHTML = ct.map((item, i) => `
    <div class="citem">
        <div class="cico">${item.icon}</div>
        <div class="cinfo">
            <div class="ctype">${item.type}</div>
            <div class="cval">${item.value}</div>
        </div>
        <div style="display:flex;gap:5px;">
            <button class="btn btn-e btn-sm" onclick="editCt(${i})">✏️</button>
            <button class="btn btn-d btn-sm" onclick="delCt(${i})">🗑</button>
        </div>
    </div>`).join('');
}

function editCt(i) {
    const item = ct[i];
    document.getElementById('ctMTitle').textContent = 'Edit Contact Method';
    document.getElementById('ctIdx').value  = i;
    document.getElementById('ctType').value = item.type;
    document.getElementById('ctIcon').value = item.icon;
    document.getElementById('ctVal').value  = item.value;
    openModal('moContact');
}

function saveContact() {
    const idx = document.getElementById('ctIdx').value;
    const type = document.getElementById('ctType').value;
    const icon = document.getElementById('ctIcon').value;
    const value = document.getElementById('ctVal').value;
    
    if (!type || !value) {
        showToast('Type and Value are required!');
        return;
    }
    
    // Map contact types to type_ids from database
    const typeMap = {
        'Email': 1,
        'Facebook': 2,
        'Institution': 3,
        'Location': 4
    };
    const typeId = typeMap[type] || 1;
    
    const formData = new FormData();
    formData.append('type_id', typeId);
    formData.append('value', value);
    formData.append('link', '');
    
    const action = idx === '' ? 'add' : 'update';
    formData.append('action', action);
    if (action === 'update') formData.append('id', ct[+idx].id);
    
    fetch('api/contact.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeModal('moContact');
                location.reload();
            } else {
                showToast(d.message || 'Error saving contact');
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

function delCt(i) {
    if (!confirm('Delete this contact?')) return;
    const id = ct[i].id;
    fetch('api/contact.php', { 
        method: 'DELETE', 
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id 
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Contact deleted successfully!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.message || 'Error deleting contact');
        }
    })
    .catch(e => showToast('Error: ' + e.message));
}

// ── IMAGE PREVIEW ─────────────────────────────────────────────
function prevImg(input, id) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { const img = document.getElementById(id); if(img){img.src=e.target.result;img.style.display='block';} };
        r.readAsDataURL(input.files[0]);
    }
}

// ── UNIFIED BACKUP/RESTORE (Server-side Snapshots) ───────────
function backupSection(section) {
    if (!confirm('Save current ' + section + ' data as a backup snapshot?')) return;
    
    const formData = new FormData();
    formData.append('action', 'backup');
    formData.append('section', section);
    
    fetch('api/snapshot.php', { method: 'POST', body: formData })
        .then(r => {
            if (!r.ok) throw new Error('HTTP error ' + r.status);
            return r.json();
        })
        .then(d => {
            if (d.success) {
                showToast(d.message || section + ' backed up!');
            } else {
                showToast(d.message || 'Backup failed');
            }
        })
        .catch(e => {
            console.error('Backup error:', e);
            showToast('Backup failed: ' + e.message);
        });
}

function restoreSection(section) {
    if (!confirm('Restore ' + section + ' from backup?\n\nThis will load your last saved backup.')) return;
    
    const formData = new FormData();
    formData.append('action', 'restore');
    formData.append('section', section);
    
    fetch('api/snapshot.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(d.message + ' Reloading...');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(d.message || 'Restore failed');
            }
        })
        .catch(e => {
            console.error('Restore error:', e);
            showToast('Restore failed: ' + e.message);
        });
}

// ── PROFILE ──────────────────────────────────────────────────
// Rich text editor functions
function formatText(cmd) {
    document.execCommand(cmd, false, null);
}
function formatColor(color) {
    if (color) document.execCommand('foreColor', false, color);
}
function formatFontSize(size) {
    if (size) document.execCommand('fontSize', false, size);
}
function formatFont(fontName) {
    if (fontName) document.execCommand('fontName', false, fontName);
}

function saveProfile() {
    const name = document.getElementById('fullname').value.trim();
    const title = document.getElementById('ptitle').value.trim();
    const bio = document.getElementById('bio').innerHTML.trim();
    const vision = document.getElementById('vision').innerHTML.trim();
    const loc = document.getElementById('location').value.trim();
    const email = document.getElementById('email').value.trim();
    const facebook = document.getElementById('facebook').value.trim();
    const imageFile = document.getElementById('pfile').files[0];
    
    if (!name || !email) {
        showToast('Name and Email are required!');
        return;
    }
    
    if (imageFile && imageFile.size > 10 * 1024 * 1024) {
        showToast('Image exceeds 10MB limit!');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    formData.append('title', title);
    formData.append('bio', bio);
    formData.append('vision', vision);
    formData.append('location', loc);
    formData.append('email', email);
    formData.append('facebook', facebook);
    if (imageFile) {
        formData.append('profile_image', imageFile);
    }
    
    fetch('api/profile.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast('✅ Profile saved!');
                setTimeout(() => location.reload(true), 1000);
            } else {
                showToast('Error: ' + (d.message || 'Save failed'));
            }
        })
        .catch(e => showToast('Error: ' + e.message));
}

// ── INIT ──────────────────────────────────────────────────────
goTo('dashboard', document.querySelector('.sb-item.on'));

// ── SESSION TIMEOUT WARNING ───────────────────────────────────
(function() {
    const SESSION_LIFETIME = <?= SESSION_LIFETIME ?>; // seconds from config
    const WARNING_BEFORE = 300; // warn 5 minutes before expiry
    const loginTime = <?= $login_time ?>;
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - loginTime;
    const remaining = SESSION_LIFETIME - elapsed;
    
    if (remaining > WARNING_BEFORE) {
        // Set timeout to show warning
        setTimeout(function() {
            if (confirm('⚠️ Your session will expire in 5 minutes.\n\nClick OK to stay logged in, or Cancel to logout now.')) {
                // Refresh page to reset session
                location.reload();
            } else {
                window.location.href = 'panel.php?logout=1';
            }
        }, (remaining - WARNING_BEFORE) * 1000);
    }
    
    // Auto logout when session expires
    if (remaining > 0) {
        setTimeout(function() {
            alert('Session expired. You will be logged out.');
            window.location.href = 'panel.php?logout=1';
        }, remaining * 1000);
    }
})();

// ── KEYBOARD NAVIGATION ───────────────────────────────────────
document.addEventListener('keydown', function(e) {
    // Only work if not typing in an input
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    
    const items = document.querySelectorAll('.sb-item:not([href])');
    const currentIndex = Array.from(items).findIndex(item => item.classList.contains('on'));
    
    if (e.key === 'ArrowUp' && currentIndex > 0) {
        e.preventDefault();
        items[currentIndex - 1].click();
    } else if (e.key === 'ArrowDown' && currentIndex < items.length - 1) {
        e.preventDefault();
        items[currentIndex + 1].click();
    }
});
</script>
<script src="JS/security.js"></script>
</body>
</html>