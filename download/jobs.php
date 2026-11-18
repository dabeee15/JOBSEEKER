<?php
require_once __DIR__ . '/includes/auth.php';
$layout = !empty($forceDash) ? 'dash' : 'public';
if ($layout === 'dash') require_role('seeker');
$pageTitle = $layout === 'dash' ? 'Find Jobs' : 'Browse Jobs';

$qv = trim($_GET['q'] ?? ''); $loc = trim($_GET['location'] ?? ''); $cat = (int)($_GET['category'] ?? 0);
$types = array_values(array_intersect($_GET['type'] ?? [], JOB_TYPES));
$w = ["j.status='open'", '(j.deadline IS NULL OR j.deadline >= CURDATE())']; $p = [];
if ($qv !== '') { $w[] = '(j.title LIKE ? OR c.name LIKE ? OR j.description LIKE ?)'; array_push($p, "%$qv%", "%$qv%", "%$qv%"); }
if ($loc !== '') { $w[] = 'j.location LIKE ?'; $p[] = "%$loc%"; }
if ($cat) { $w[] = 'j.category_id=?'; $p[] = $cat; }
if ($types) { $w[] = 'j.job_type IN (' . implode(',', array_fill(0, count($types), '?')) . ')'; $p = array_merge($p, $types); }
$where = ' WHERE ' . implode(' AND ', $w);
$per = max(3, (int)setting('jobs_per_page', 6)); $page = page_no();
$total = (int)val('SELECT COUNT(*) FROM jobs j JOIN companies c ON c.id=j.company_id' . $where, $p);
$jobs = rows(JOB_SELECT . $where . ' ORDER BY j.is_featured DESC, j.created_at DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $p);
$saved = saved_ids();

ob_start();
echo '<div class="text-muted small mb-3">' . $total . ' job(s) found</div>';
foreach ($jobs as $j) echo job_card($j, $saved);
if (!$jobs) echo '<div class="text-center text-muted py-5"><i class="bi bi-search fs-1"></i><p>No jobs match your search.</p></div>';
echo pager($total, $per, $page);
$listHtml = ob_get_clean();
if (isset($_GET['ajax'])) { echo $listHtml; exit; }

$cats = rows('SELECT id, name FROM job_categories ORDER BY name');
$locs = rows("SELECT DISTINCT location FROM jobs WHERE status='open' ORDER BY location");
include ROOT_PATH . '/includes/header.php';
?>
<style>
/* ==========================================================
   Jobs page design: Light Blue + Navy click state (CSS only)
   ========================================================== */
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

:root {
  --jb-ink: #0c2340;
  --jb-muted: #5b7089;
  --jb-bg: #eaf4fd;
  --jb-surface: #ffffff;
  --jb-line: #cfe3f5;
  --jb-brand: #1479c9;      /* light blue (normal) */
  --jb-sky: #4db3f5;        /* lighter blue (gradient) */
  --jb-brand-tint: #e1f0fd;
  --jb-navy: #0a3a78;       /* dark blue (on click / selected) */
  --jb-navy-deep: #072b5a;
  --jb-gold: #f0b13a;
  --jb-radius: 16px;
  --jb-radius-sm: 10px;
  --jb-shadow: 0 1px 2px rgba(12, 35, 64, .05), 0 10px 24px -12px rgba(20, 121, 201, .25);
  --jb-ease: cubic-bezier(.22, .8, .24, 1);
}

/* ---------- Base ---------- */
body {
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
  color: var(--jb-ink);
  background: linear-gradient(180deg, #d6eafb 0, var(--jb-bg) 380px) no-repeat, var(--jb-bg);
  -webkit-font-smoothing: antialiased;
}
h5, h6, .fw-bold, .card-title, .card h5, .card h6 {
  font-family: 'Sora', 'Plus Jakarta Sans', sans-serif;
  letter-spacing: -.02em;
}
.text-muted { color: var(--jb-muted) !important; }
a { color: var(--jb-brand); transition: color .2s var(--jb-ease); }
a:hover { color: var(--jb-navy); }
::selection { background: var(--jb-navy); color: #fff; }

/* ---------- Search banner ---------- */
#filterForm > .card {
  background: linear-gradient(120deg, var(--jb-brand) 0%, var(--jb-sky) 100%);
  border: 0;
  border-radius: 20px;
  box-shadow: 0 18px 36px -18px rgba(20, 121, 201, .55);
  padding: 1.25rem !important;
  margin-bottom: 1.75rem !important;
}
#filterForm > .card .form-control,
#filterForm > .card .form-select {
  height: 52px;
  border: 2px solid transparent;
  border-radius: var(--jb-radius-sm);
  background-color: #fff;
  color: var(--jb-ink);
  font-size: .95rem;
  font-weight: 500;
  padding-inline: 1rem;
  transition: border-color .2s var(--jb-ease), box-shadow .2s var(--jb-ease), transform .2s var(--jb-ease);
}
#filterForm > .card .form-control::placeholder { color: var(--jb-muted); opacity: .8; }
#filterForm > .card .form-control:focus,
#filterForm > .card .form-select:focus {
  border-color: var(--jb-navy);
  box-shadow: 0 0 0 4px rgba(10, 58, 120, .25);
  transform: translateY(-1px);
  outline: 0;
}
#filterForm > .card .btn-primary {
  height: 52px;
  background: #fff;
  border-color: #fff;
  color: var(--jb-brand);
  font-family: 'Sora', sans-serif;
  font-weight: 700;
}
#filterForm > .card .btn-primary:hover { background: var(--jb-brand-tint); border-color: var(--jb-brand-tint); color: var(--jb-navy); transform: translateY(-2px); }
#filterForm > .card .btn-primary:active,
#filterForm > .card .btn-primary:focus-visible {
  background: var(--jb-navy) !important;
  border-color: var(--jb-navy) !important;
  color: #fff !important;
  transform: translateY(0) scale(.98);
}

/* ---------- Buttons (all) ---------- */
.btn {
  border-radius: var(--jb-radius-sm);
  font-weight: 600;
  transition: background-color .2s var(--jb-ease), color .2s var(--jb-ease), border-color .2s var(--jb-ease), transform .2s var(--jb-ease), box-shadow .2s var(--jb-ease);
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 16px -8px rgba(20, 121, 201, .45); }
.btn:active { transform: translateY(0) scale(.98); }
.btn-primary {
  --bs-btn-bg: var(--jb-brand);
  --bs-btn-border-color: var(--jb-brand);
  --bs-btn-hover-bg: #0f68ae;
  --bs-btn-hover-border-color: #0f68ae;
  --bs-btn-active-bg: var(--jb-navy);
  --bs-btn-active-border-color: var(--jb-navy);
  --bs-btn-color: #fff;
  --bs-btn-hover-color: #fff;
  --bs-btn-active-color: #fff;
}
.btn-outline-primary {
  --bs-btn-color: var(--jb-brand);
  --bs-btn-border-color: var(--jb-brand);
  --bs-btn-hover-bg: var(--jb-brand);
  --bs-btn-hover-border-color: var(--jb-brand);
  --bs-btn-hover-color: #fff;
  --bs-btn-active-bg: var(--jb-navy);
  --bs-btn-active-border-color: var(--jb-navy);
  --bs-btn-active-color: #fff;
}
.btn-light {
  --bs-btn-bg: var(--jb-brand-tint);
  --bs-btn-border-color: transparent;
  --bs-btn-color: var(--jb-navy);
  --bs-btn-hover-bg: var(--jb-line);
  --bs-btn-hover-color: var(--jb-navy);
  --bs-btn-active-bg: var(--jb-navy);
  --bs-btn-active-color: #fff;
  --bs-btn-active-border-color: var(--jb-navy);
}
.btn:focus-visible, a:focus-visible, .form-check-input:focus-visible {
  outline: 3px solid rgba(10, 58, 120, .5);
  outline-offset: 2px;
}

/* ---------- Layout ---------- */
@media (min-width: 992px) {
  #filters { position: sticky; top: 1rem; }
  #filterForm > .row { --bs-gutter-x: 2rem; }
}

/* ---------- Filter sidebar ---------- */
.filter-box {
  background: var(--jb-surface);
  border: 1px solid var(--jb-line);
  border-radius: var(--jb-radius);
  box-shadow: var(--jb-shadow);
  padding: 1.35rem;
  animation: jb-slide-in .6s var(--jb-ease) both;
}
.filter-box h6 {
  font-size: .98rem;
  margin-bottom: .8rem;
  padding-bottom: .6rem;
  border-bottom: 2px solid var(--jb-brand-tint);
}
.filter-box .form-check {
  padding: .5rem .6rem .5rem 2.3rem;
  margin-bottom: .2rem;
  border-radius: 10px;
  transition: background-color .22s var(--jb-ease), color .22s var(--jb-ease), transform .22s var(--jb-ease);
}
.filter-box .form-check:hover { background: var(--jb-brand-tint); transform: translateX(3px); }
.filter-box .form-check-input {
  border: 2px solid #9fbbd6;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color .2s, border-color .2s, transform .2s var(--jb-ease);
}
.filter-box .form-check-input:active { transform: scale(.85); }
.filter-box .form-check-input:checked { background-color: #fff; border-color: #fff; }
.filter-box .form-check-label { cursor: pointer; font-size: .92rem; font-weight: 500; width: 100%; }
/* clicked / selected = dark blue */
.filter-box .form-check:has(.form-check-input:checked),
.filter-box .form-check:active {
  background: var(--jb-navy);
  transform: translateX(3px);
}
.filter-box .form-check:has(.form-check-input:checked) .form-check-label,
.filter-box .form-check:active .form-check-label { color: #fff; font-weight: 700; }
.filter-box .form-check:has(.form-check-input:checked) .form-check-input { background-color: #fff; }

.filter-box a.d-block {
  padding: .5rem .8rem !important;
  border-radius: 10px;
  font-weight: 500;
  transition: background-color .22s var(--jb-ease), color .22s var(--jb-ease), padding-left .22s var(--jb-ease);
}
.filter-box a.d-block:hover { background: var(--jb-brand-tint); color: var(--jb-navy) !important; padding-left: 1.1rem !important; }
.filter-box a.d-block:active,
.filter-box a.d-block.fw-bold {
  background: var(--jb-navy);
  color: #fff !important;
  font-weight: 700;
}

/* ---------- Results header ---------- */
.col-lg-9 > h5.fw-bold {
  font-size: 1.7rem;
  font-weight: 800;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: .7rem;
  animation: jb-slide-in .6s var(--jb-ease) .1s both;
}
.col-lg-9 > h5.fw-bold::before {
  content: "";
  width: 7px;
  height: 1.2em;
  border-radius: 4px;
  background: linear-gradient(180deg, var(--jb-gold), #f7cf7a);
}
#jobList > .text-muted.small {
  display: inline-block;
  background: var(--jb-surface);
  border: 1px solid var(--jb-line);
  border-radius: 999px;
  padding: .3rem .9rem;
  font-weight: 600;
  color: var(--jb-navy) !important;
}

/* ---------- Job cards ---------- */
#jobList .card {
  position: relative;
  background: var(--jb-surface);
  border: 1px solid var(--jb-line);
  border-radius: var(--jb-radius);
  box-shadow: var(--jb-shadow);
  margin-bottom: 1.1rem;
  overflow: hidden;
  cursor: pointer;
  animation: jb-rise .6s var(--jb-ease) both;
  transition: transform .28s var(--jb-ease), box-shadow .28s var(--jb-ease), border-color .28s var(--jb-ease), background-color .2s var(--jb-ease), color .2s var(--jb-ease);
}
#jobList .card:nth-of-type(1) { animation-delay: .05s; }
#jobList .card:nth-of-type(2) { animation-delay: .12s; }
#jobList .card:nth-of-type(3) { animation-delay: .19s; }
#jobList .card:nth-of-type(4) { animation-delay: .26s; }
#jobList .card:nth-of-type(5) { animation-delay: .33s; }
#jobList .card:nth-of-type(n+6) { animation-delay: .4s; }

#jobList .card::before {
  content: "";
  position: absolute;
  inset: 0 auto 0 0;
  width: 5px;
  background: linear-gradient(180deg, var(--jb-sky), var(--jb-brand));
  transform: scaleY(0);
  transform-origin: top;
  transition: transform .3s var(--jb-ease), background .2s;
}
#jobList .card:hover {
  transform: translateY(-4px);
  border-color: #8ec3ec;
  box-shadow: 0 20px 36px -18px rgba(20, 121, 201, .45);
}
#jobList .card:hover::before { transform: scaleY(1); }

/* click = dark blue */
#jobList .card:active,
#jobList .card:focus-within {
  background: var(--jb-navy);
  border-color: var(--jb-navy-deep);
  transform: translateY(0) scale(.992);
}
#jobList .card:active::before,
#jobList .card:focus-within::before { transform: scaleY(1); background: var(--jb-gold); }
#jobList .card:active *,
#jobList .card:focus-within h5 a,
#jobList .card:focus-within h6 a,
#jobList .card:focus-within .card-title a,
#jobList .card:focus-within .text-muted,
#jobList .card:focus-within .bi { color: #fff !important; }

#jobList .card-body { padding: 1.4rem 1.6rem; }
#jobList .card h5, #jobList .card h6, #jobList .card .card-title { font-size: 1.15rem; font-weight: 700; margin-bottom: .25rem; }
#jobList .card h5 a, #jobList .card h6 a, #jobList .card .card-title a { color: var(--jb-ink); text-decoration: none; }
#jobList .card h5 a:hover, #jobList .card h6 a:hover, #jobList .card .card-title a:hover { color: var(--jb-brand); }
#jobList .card img {
  border-radius: 14px;
  object-fit: cover;
  border: 1px solid var(--jb-line);
  background: var(--jb-bg);
  transition: transform .3s var(--jb-ease);
}
#jobList .card:hover img { transform: scale(1.06) rotate(-2deg); }
#jobList .card .bi { color: var(--jb-brand); transition: color .2s, transform .25s var(--jb-ease); }
#jobList .card .btn:hover .bi { transform: scale(1.2); }
#jobList .card .btn-outline-secondary,
#jobList .card .btn-outline-danger { border-color: var(--jb-line); color: var(--jb-muted); }
#jobList .card .btn-outline-secondary:hover,
#jobList .card .btn-outline-danger:hover { background: var(--jb-brand-tint); color: var(--jb-navy); border-color: var(--jb-brand); }
#jobList .card .btn:active { background: var(--jb-navy-deep) !important; border-color: var(--jb-navy-deep) !important; color: #fff !important; }

/* ---------- Badges ---------- */
#jobList .badge {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: .76rem;
  padding: .45em .9em;
  border-radius: 999px;
  transition: background-color .2s, color .2s;
}
#jobList .badge.bg-primary,
#jobList .badge.bg-success,
#jobList .badge.bg-info,
#jobList .badge.bg-light,
#jobList .badge.bg-secondary {
  background: var(--jb-brand-tint) !important;
  color: var(--jb-navy) !important;
}
#jobList .badge.bg-warning { background: rgba(240, 177, 58, .28) !important; color: #7a5200 !important; }
#jobList .card:active .badge,
#jobList .card:focus-within .badge { background: rgba(255, 255, 255, .18) !important; color: #fff !important; }

/* ---------- Empty state ---------- */
#jobList > .text-center.py-5 {
  background: var(--jb-surface);
  border: 2px dashed #a9cdec;
  border-radius: var(--jb-radius);
  animation: jb-rise .6s var(--jb-ease) both;
}
#jobList > .text-center.py-5 .bi { color: var(--jb-brand); animation: jb-float 3s ease-in-out infinite; display: inline-block; }

/* ---------- Pagination ---------- */
.pagination { gap: .4rem; justify-content: center; margin-top: 1.75rem; }
.pagination .page-link {
  border: 1px solid var(--jb-line);
  border-radius: var(--jb-radius-sm) !important;
  color: var(--jb-ink);
  background: var(--jb-surface);
  min-width: 42px;
  text-align: center;
  font-weight: 700;
  transition: background-color .2s var(--jb-ease), color .2s var(--jb-ease), border-color .2s var(--jb-ease), transform .2s var(--jb-ease);
}
.pagination .page-link:hover { background: var(--jb-brand-tint); border-color: var(--jb-brand); color: var(--jb-navy); transform: translateY(-2px); }
.pagination .page-link:active,
.pagination .page-item.active .page-link { background: var(--jb-navy); border-color: var(--jb-navy); color: #fff; }
.pagination .page-item.disabled .page-link { opacity: .45; }

/* ---------- Keyframes ---------- */
@keyframes jb-rise {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes jb-slide-in {
  from { opacity: 0; transform: translateX(-18px); }
  to   { opacity: 1; transform: translateX(0); }
}
@keyframes jb-float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-8px); }
}

/* ---------- Mobile ---------- */
@media (max-width: 991.98px) {
  .filter-box { margin-top: .5rem; }
  #jobList .card-body { padding: 1.1rem; }
  .col-lg-9 > h5.fw-bold { font-size: 1.35rem; }
}
@media (max-width: 575.98px) {
  #filterForm > .card { padding: .85rem !important; border-radius: 16px; }
}

/* ---------- Reduced motion ---------- */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation: none !important; transition: none !important; }
}
</style>
<div class="<?= $layout === 'public' ? 'container py-4' : '' ?>">
<form id="filterForm" method="get">
  <div class="card p-3 mb-3"><div class="row g-2">
    <div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Search jobs, skills, company" value="<?= e($qv) ?>"></div>
    <div class="col-md-3"><input type="search" name="location" class="form-control" placeholder="Location" value="<?= e($loc) ?>" list="locList"><datalist id="locList"><?php foreach ($locs as $l) echo '<option value="' . e($l['location']) . '">'; ?></datalist></div>
    <div class="col-6 col-md-3"><select name="category" class="form-select"><option value="0">All Categories</option><?php foreach ($cats as $c) echo '<option value="' . $c['id'] . '"' . ($cat == $c['id'] ? ' selected' : '') . '>' . e($c['name']) . '</option>'; ?></select></div>
    <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> Search</button></div>
  </div></div>
  <div class="row">
    <div class="col-lg-3 mb-3">
      <button class="btn btn-outline-primary w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filters"><i class="bi bi-funnel"></i> Filters</button>
      <div class="collapse d-lg-block" id="filters"><div class="filter-box">
        <h6 class="fw-bold">Job Type</h6>
        <?php foreach (JOB_TYPES as $t): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="type[]" value="<?= $t ?>" id="t<?= md5($t) ?>" <?= in_array($t, $types) ? 'checked' : '' ?>><label class="form-check-label" for="t<?= md5($t) ?>"><?= $t ?></label></div><?php endforeach; ?>
        <h6 class="fw-bold mt-3">Category</h6>
        <?php foreach ($cats as $c): ?><a class="d-block text-decoration-none small py-1 <?= $cat == $c['id'] ? 'fw-bold' : 'text-muted' ?>" href="?category=<?= $c['id'] ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
        <a href="<?= e($self = basename($_SERVER['SCRIPT_NAME'])) ?>" class="btn btn-sm btn-light w-100 mt-3">Clear filters</a>
      </div></div>
    </div>
    <div class="col-lg-9"><h5 class="fw-bold">Featured Jobs</h5><div id="jobList"><?= $listHtml ?></div></div>
  </div>
</form></div>
<script>
/* Job Type: isang pili lang sa isang pagkakataon (parang radio).
   Kapag may pinindot na bago, mawawala yung dating napili.
   Pindutin ulit yung napili para i-clear. */
(function () {
  var form = document.getElementById('filterForm');
  if (!form) return;
  form.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || t.name !== 'type[]' || !t.checked) return;
    form.querySelectorAll('input[name="type[]"]').forEach(function (cb) {
      if (cb !== t) cb.checked = false;
    });
  }, true); /* capture: tumatakbo muna bago ang ibang handlers */
})();
</script>
<?php include ROOT_PATH . '/includes/footer.php'; ?>