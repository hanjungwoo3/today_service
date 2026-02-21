<!-- 커스텀 상단 링크 -->
<style>
.custom-nav-section {
    padding: 10px 0 14px;
    margin-bottom: 4px;
    border-bottom: 1px solid #e0e0e0;
}
.custom-nav-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.custom-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none !important;
    color: #333 !important;
    background: white;
    border: 1px solid #e8e8e8;
    transition: all 0.15s ease;
}
.custom-nav-item:hover,
.custom-nav-item:active {
    background: #f5f5f5;
    border-color: #d0d0d0;
}
.custom-nav-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: white;
    flex-shrink: 0;
}
.custom-nav-label {
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.35;
    color: #444;
}
.custom-nav-icon.nav-meeting { background: #4a6e9e; }
.custom-nav-icon.nav-talk { background: #5a8a5c; }
.custom-nav-icon.nav-guide { background: #9e7640; }
.custom-nav-icon.nav-duty { background: #4a8a8b; }
.custom-nav-icon.nav-record { background: #7a6a9e; }

@media (max-width: 400px) {
    .custom-nav-item { padding: 8px 10px; gap: 8px; }
    .custom-nav-icon { width: 34px; height: 34px; font-size: 15px; }
    .custom-nav-label { font-size: 11.5px; }
}
</style>

<div class="custom-nav-section">
    <div class="custom-nav-grid">
        <a href="<?=BASE_PATH?>/pages/meeting_program.php" class="custom-nav-item">
            <div class="custom-nav-icon nav-meeting"><i class="bi bi-journal-text"></i></div>
            <div class="custom-nav-label">평일집회<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/public_talk.php" class="custom-nav-item">
            <div class="custom-nav-icon nav-talk"><i class="bi bi-mic"></i></div>
            <div class="custom-nav-label">주말집회<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/service_guide_calendar.php" class="custom-nav-item">
            <div class="custom-nav-icon nav-guide"><i class="bi bi-calendar3"></i></div>
            <div class="custom-nav-label">봉사인도<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/duty_schedule.php" class="custom-nav-item">
            <div class="custom-nav-icon nav-duty"><i class="bi bi-clipboard-check"></i></div>
            <div class="custom-nav-label">청소,연사음료<br>안내인,마이크</div>
        </a>
        <?php if(is_admin(mb_id())): ?>
        <a href="<?=BASE_PATH?>/pages/ministry_record.php" class="custom-nav-item">
            <div class="custom-nav-icon nav-record"><i class="bi bi-people"></i></div>
            <div class="custom-nav-label">호별봉사<br>짝배정</div>
        </a>
        <?php endif; ?>
    </div>
</div>
