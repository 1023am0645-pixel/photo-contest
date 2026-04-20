<?php
$host = 'localhost'; 
$db   = 'HAYEON';
$user = 'root'; 
$pass = 'm:Xa/RGQ4K5P';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("SELECT * FROM photo_event ORDER BY rating DESC, uploaded_at DESC");
    $photos = $stmt->fetchAll();
} catch (\PDOException $e) {
    $photos = [];
}

// Calculate Rating Distribution for Dashboard
$ratingDist = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
foreach($photos as $p) {
    $r = (int)$p['rating'];
    if(isset($ratingDist[$r])) $ratingDist[$r]++;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 강사 찍사 대회 - 관리자</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;800&family=Gaegu:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #a78bfa;
            --primary-light: #c4b5fd;
            --bg-color: #f5f3ff;
            --text-main: #4c1d95;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Pretendard', sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            color: var(--text-main);
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 3rem;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .title {
            font-family: 'Gaegu', cursive;
            font-size: 2.5rem;
            color: var(--primary);
            letter-spacing: -2px;
        }

        /* Dashboard Container */
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .chart-container {
            height: 250px;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .filter-tab {
            padding: 0.8rem 1.5rem;
            background: white;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
            white-space: nowrap;
            border: 2px solid transparent;
        }
        .filter-tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(167, 139, 250, 0.3);
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .photo-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }
        .photo-card:hover {
            transform: translateY(-20px) scale(1.6);
            z-index: 1000;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .photo-wrapper {
            width: 100%;
            height: 300px;
            background: #f1f5f9;
        }
        .photo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .photo-card:hover .photo-wrapper img {
            background: #000;
        }

        .info { padding: 1.2rem; }
        .uploader-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .uploader-name {
            font-family: 'Gaegu', cursive;
            font-size: 1.6rem;
            color: var(--primary);
        }

        .delete-btn {
            color: #fca5a5;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s;
            margin-left: 10px;
        }
        .delete-btn:hover {
            color: #ef4444;
            transform: scale(1.2);
        }

        /* Editable Star Rating */
        .admin-rating {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }
        .admin-star {
            font-size: 1.2rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        .admin-star:hover { transform: scale(1.2); }
        .admin-star.active { color: #ffb703; }

        .message { font-size: 1rem; color: #6d28d9; line-height: 1.4; }
        .time { font-size: 0.8rem; color: #a78bfa; margin-top: 10px; text-align: right; }

        .crown { color: #ddd; cursor: pointer; font-size: 1.5rem; }
        .crown.active { color: #fbbf24; transform: scale(1.2) rotate(15deg); }

        .floating-nav {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
        }
        .nav-btn {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            border-radius: 20px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-main);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .nav-btn:hover {
            transform: scale(1.05);
            background: white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">👑 강사 찍사 대회 - 관리자</h1>
        <div>
            <button class="btn" style="background:#c4b5fd; color:white; border:none; padding:10px 20px; border-radius:50px; cursor:pointer;" onclick="location.reload()">새로고침</button>
        </div>
    </div>

    <!-- Dashboard -->
    <div class="dashboard">
        <div class="stat-card">
            <h3>📊 총 참여 현황</h3>
            <p style="font-size: 3rem; font-weight: 800; color: var(--primary); margin: 1rem 0;"><?= count($photos) ?> <span style="font-size:1.2rem; font-weight:400;">장 접수</span></p>
            <p style="color:#888;">실시간으로 사진이 쌓이고 있습니다!</p>
        </div>
        <div class="stat-card">
            <h3>⭐ 별점 분포 (강사 평가)</h3>
            <div class="chart-container">
                <canvas id="ratingChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Filter Tab -->
    <div class="filter-tabs">
        <div class="filter-tab active" onclick="filterByRating('all')">전체보기</div>
        <div class="filter-tab" onclick="filterByRating(5)">⭐⭐⭐⭐⭐ (<?= $ratingDist[5] ?>)</div>
        <div class="filter-tab" onclick="filterByRating(4)">⭐⭐⭐⭐ (<?= $ratingDist[4] ?>)</div>
        <div class="filter-tab" onclick="filterByRating(3)">⭐⭐⭐ (<?= $ratingDist[3] ?>)</div>
        <div class="filter-tab" onclick="filterByRating(2)">⭐⭐ (<?= $ratingDist[2] ?>)</div>
        <div class="filter-tab" onclick="filterByRating(1)">⭐ (<?= $ratingDist[1] ?>)</div>
        <div class="filter-tab" onclick="filterByRating(0)">미평가 (<?= $ratingDist[0] ?>)</div>
    </div>

    <div class="photo-grid">
        <?php foreach($photos as $photo): ?>
            <div class="photo-card" data-rating="<?= $photo['rating'] ?>">
                <div class="photo-wrapper">
                    <img src="./uploads/<?= htmlspecialchars($photo['filename']) ?>" loading="lazy">
                </div>
                <div class="info">
                    <div class="admin-rating" data-id="<?= $photo['id'] ?>">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="fa-solid fa-star admin-star <?= $photo['rating'] >= $i ? 'active' : '' ?>" data-val="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="uploader-row">
                        <div>
                            <span class="uploader-name"><?= htmlspecialchars($photo['uploader_name']) ?></span>
                            <i class="fa-solid fa-trash delete-btn" onclick="deletePhoto(<?= $photo['id'] ?>, this)"></i>
                        </div>
                        <i class="fa-solid fa-crown crown" onclick="toggleCrown(this)"></i>
                    </div>
                    <?php if(!empty($photo['message'])): ?>
                        <div class="message">"<?= htmlspecialchars($photo['message']) ?>"</div>
                    <?php endif; ?>
                    <div class="time"><?= date('H:i', strtotime($photo['uploaded_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="floating-nav">
        <button onclick="location.href='http://54.116.99.115/HAYEON/photo_event_app/index.html';" class="nav-btn">🏠 메인페이지</button>
    </div>

    <script>
        // Chart.js - Rating Distribution
        const distData = <?= json_encode(array_values($ratingDist)) ?>;
        const ctx = document.getElementById('ratingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['0점', '1점', '2점', '3점', '4점', '5점'],
                datasets: [{
                    label: '사진 수',
                    data: distData,
                    backgroundColor: ['#e2e8f0', '#fca5a5', '#fdba74', '#fcd34d', '#86efac', '#93c5fd'],
                    borderRadius: 8
                }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // Live Star Rating Update
        document.querySelectorAll('.admin-star').forEach(star => {
            star.addEventListener('click', function(e) {
                e.stopPropagation();
                const card = this.closest('.photo-card');
                const adminRatingDiv = this.parentElement;
                const photoId = adminRatingDiv.getAttribute('data-id');
                const rating = this.getAttribute('data-val');

                fetch('update_rating.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${photoId}&rating=${rating}`
                }).then(res => res.json()).then(data => {
                    if(data.success) {
                        card.setAttribute('data-rating', rating);
                        const stars = adminRatingDiv.querySelectorAll('.admin-star');
                        stars.forEach(s => {
                            s.classList.toggle('active', s.getAttribute('data-val') <= rating);
                        });
                    }
                });
            });
        });

        function filterByRating(val) {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('.photo-card').forEach(card => {
                const r = card.getAttribute('data-rating');
                card.style.display = (val === 'all' || r == val) ? 'block' : 'none';
            });
        }

        function toggleCrown(el) { el.classList.toggle('active'); event.stopPropagation(); }

        function deletePhoto(id, btn) {
            if(confirm('정말 이 사진을 삭제하시겠습니까?')) {
                fetch('delete_photo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                }).then(res => res.json()).then(data => {
                    if(data.success) {
                        const card = btn.closest('.photo-card');
                        card.style.transform = 'scale(0)';
                        setTimeout(() => card.remove(), 300);
                    } else {
                        alert('삭제에 실패했습니다.');
                    }
                }).catch(() => alert('서버 통신 오류가 발생했습니다.'));
            }
            event.stopPropagation();
        }
    </script>
</body>
</html>
