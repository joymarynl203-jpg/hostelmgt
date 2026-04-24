<?php
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/maps.php';

require_login();

$user = hms_current_user();
$userId = (int) $user['id'];
$db = hms_db();
$nearbyColStmt = $db->query("SHOW COLUMNS FROM hostels LIKE 'nearby_institutions'");
$hasNearbyInstitutions = (bool)$nearbyColStmt->fetch();
$search = trim((string)($_GET['q'] ?? ''));
$institutionFilter = trim((string)($_GET['institution'] ?? ''));
if ($hasNearbyInstitutions) {
    $instOptions = $db->query('
        SELECT institution_name
        FROM (
            SELECT DISTINCT TRIM(institution) AS institution_name
            FROM users
            WHERE institution IS NOT NULL AND TRIM(institution) <> ""
            UNION
            SELECT DISTINCT TRIM(nearby_institutions) AS institution_name
            FROM hostels
            WHERE nearby_institutions IS NOT NULL AND TRIM(nearby_institutions) <> ""
        ) x
        ORDER BY institution_name ASC
    ')->fetchAll();
} else {
    $instOptions = $db->query('
        SELECT DISTINCT TRIM(institution) AS institution_name
        FROM users
        WHERE institution IS NOT NULL AND TRIM(institution) <> ""
        ORDER BY institution_name ASC
    ')->fetchAll();
}

// Role-based visibility.
if ($user['role'] === 'warden') {
    $sql = '
        SELECT h.*,
            (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) AS room_count,
            (SELECT COALESCE(SUM(r.current_occupancy),0) FROM rooms r WHERE r.hostel_id = h.id) AS occupancy
        FROM hostels h
        WHERE h.is_active = 1 AND (h.managed_by = ? OR h.managed_by IS NULL)
    ';
    $params = [$userId];
    if ($search !== '') {
        $searchExpr = $hasNearbyInstitutions
            ? '(h.name LIKE ? OR h.location LIKE ? OR COALESCE(h.nearby_institutions, "") LIKE ?)'
            : '(h.name LIKE ? OR h.location LIKE ?)';
        $sql .= ' AND ' . $searchExpr;
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        if ($hasNearbyInstitutions) {
            $params[] = $term;
        }
    }
    if ($hasNearbyInstitutions && $institutionFilter !== '') {
        $sql .= ' AND COALESCE(h.nearby_institutions, "") LIKE ?';
        $params[] = '%' . $institutionFilter . '%';
    }
    $sql .= '
        ORDER BY h.name ASC
    ';
    $hostels = $db->prepare($sql);
    $hostels->execute($params);
    $hostels = $hostels->fetchAll();
} elseif ($user['role'] === 'university_admin') {
    $sql = '
        SELECT h.*,
            (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) AS room_count,
            (SELECT COALESCE(SUM(r.current_occupancy),0) FROM rooms r WHERE r.hostel_id = h.id) AS occupancy
        FROM hostels h
        WHERE 1=1
    ';
    $params = [];
    if ($search !== '') {
        $searchExpr = $hasNearbyInstitutions
            ? '(h.name LIKE ? OR h.location LIKE ? OR COALESCE(h.nearby_institutions, "") LIKE ?)'
            : '(h.name LIKE ? OR h.location LIKE ?)';
        $sql .= ' AND ' . $searchExpr;
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        if ($hasNearbyInstitutions) {
            $params[] = $term;
        }
    }
    if ($hasNearbyInstitutions && $institutionFilter !== '') {
        $sql .= ' AND COALESCE(h.nearby_institutions, "") LIKE ?';
        $params[] = '%' . $institutionFilter . '%';
    }
    $sql .= ' ORDER BY h.is_active DESC, h.name ASC';
    $hostels = $db->prepare($sql);
    $hostels->execute($params);
    $hostels = $hostels->fetchAll();
} else {
    $sql = '
        SELECT h.*,
            (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) AS room_count,
            (SELECT COALESCE(SUM(r.current_occupancy),0) FROM rooms r WHERE r.hostel_id = h.id) AS occupancy
        FROM hostels h
        WHERE h.is_active = 1
          AND h.rent_period_start IS NOT NULL
          AND h.rent_period_end IS NOT NULL
    ';
    $params = [];
    if ($search !== '') {
        $searchExpr = $hasNearbyInstitutions
            ? '(h.name LIKE ? OR h.location LIKE ? OR COALESCE(h.nearby_institutions, "") LIKE ?)'
            : '(h.name LIKE ? OR h.location LIKE ?)';
        $sql .= ' AND ' . $searchExpr;
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        if ($hasNearbyInstitutions) {
            $params[] = $term;
        }
    }
    if ($hasNearbyInstitutions && $institutionFilter !== '') {
        $sql .= ' AND COALESCE(h.nearby_institutions, "") LIKE ?';
        $params[] = '%' . $institutionFilter . '%';
    }
    $sql .= ' ORDER BY h.name ASC';
    $hostels = $db->prepare($sql);
    $hostels->execute($params);
    $hostels = $hostels->fetchAll();
}

layout_header('Hostels');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="h4 mb-1">Browse Hostels</h2>
        <div class="text-muted small">Select a hostel to view available rooms.</div>
    </div>
    <?php if ($user['role'] !== 'student'): ?>
        <a class="btn btn-outline-primary" href="<?php echo hms_url('admin/hostels.php'); ?>">Manage</a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-3 p-md-4">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1">Search by hostel, location, or nearby institution</label>
                <input type="text" name="q" class="form-control" value="<?php echo e($search); ?>" placeholder="e.g. Makerere, Wandegeya, Kyambogo">
            </div>
            <?php if ($hasNearbyInstitutions): ?>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Institution filter</label>
                    <input type="text" name="institution" class="form-control" list="institution-filter-options" value="<?php echo e($institutionFilter); ?>" placeholder="Any institution">
                </div>
            <?php endif; ?>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
        <div class="mt-2 small">
            <a href="<?php echo hms_url('hostels.php'); ?>">Clear filters</a>
        </div>
    </div>
</div>

<?php if ($hasNearbyInstitutions): ?>
    <datalist id="institution-filter-options">
        <?php foreach ($instOptions as $opt): ?>
            <?php $optVal = trim((string)($opt['institution_name'] ?? '')); ?>
            <?php if ($optVal !== ''): ?>
                <option value="<?php echo e($optVal); ?>"></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </datalist>
<?php endif; ?>

<div class="row g-3">
    <?php if (empty($hostels)): ?>
        <div class="col-12"><div class="alert alert-info">No hostels available yet.</div></div>
    <?php endif; ?>
    <?php foreach ($hostels as $h): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold"><?php echo e($h['name']); ?></div>
                            <div class="text-muted small"><?php echo e($h['location']); ?></div>
                            <?php if ($hasNearbyInstitutions && !empty($h['nearby_institutions'])): ?>
                                <div class="text-muted small">Nearby institutions: <?php echo e((string)($h['nearby_institutions'] ?? '')); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?php echo ((int)$h['is_active'] === 1) ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo ((int)$h['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

                    <?php if (!empty($h['description'])): ?>
                        <p class="text-muted mt-2 mb-3 small"><?php echo e($h['description']); ?></p>
                    <?php endif; ?>

                    <div class="d-flex gap-3 small text-muted mb-3">
                        <div>Rooms: <span class="fw-semibold text-dark"><?php echo e((string)($h['room_count'] ?? 0)); ?></span></div>
                        <div>Occupied: <span class="fw-semibold text-dark"><?php echo e((string)($h['occupancy'] ?? 0)); ?></span></div>
                    </div>

                    <?php
                        $pin = hms_hostel_map_lat_lng($h);
                        if ($pin !== null):
                            $extMap = hms_google_maps_external_url($pin['lat'], $pin['lng']);
                            $embedSrc = hms_google_maps_embed_url($pin['lat'], $pin['lng']);
                    ?>
                        <div class="mb-3">
                            <?php if ($embedSrc !== ''): ?>
                                <iframe class="rounded w-100 border-0 shadow-sm" style="min-height: 180px;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map: <?php echo e($h['name']); ?>" src="<?php echo e($embedSrc); ?>"></iframe>
                            <?php endif; ?>
                            <a class="btn btn-outline-secondary btn-sm w-100 mt-2" href="<?php echo e($extMap); ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
                        </div>
                    <?php endif; ?>

                    <a class="btn btn-primary w-100" href="<?php echo hms_url('hostels_view.php?hostel_id=' . (int)$h['id']); ?>">
                        View Rooms
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php layout_footer(); ?>

