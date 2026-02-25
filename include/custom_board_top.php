<!-- 커스텀 상단 링크 -->
<style>
.custom-nav-section {
    padding: 10px 6px 14px;
    margin-bottom: 4px;
    border-bottom: 1px solid #e0e0e0;
}
.custom-nav-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 5px;
}
.custom-nav-item {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 6px;
    border-radius: 10px;
    text-decoration: none !important;
    color: #333 !important;
    border: 1px solid #ccc;
    transition: all 0.15s ease;
}
.custom-nav-item:hover,
.custom-nav-item:active {
    filter: brightness(0.96);
}
.custom-nav-icon {
    position: absolute;
    right: 5px;
    bottom: 3px;
    font-size: 24px;
    opacity: 0.3;
    line-height: 1;
}
.custom-nav-item.nav-bg-meeting { background: white; border-color: rgba(74,110,158,0.35); }
.custom-nav-item.nav-bg-talk { background: white; border-color: rgba(90,138,92,0.35); }
.custom-nav-item.nav-bg-guide { background: white; border-color: rgba(158,118,64,0.35); }
.custom-nav-item.nav-bg-duty { background: white; border-color: rgba(74,138,139,0.35); }
.custom-nav-item.nav-bg-record { background: white; border-color: rgba(122,106,158,0.35); }
.custom-nav-icon.nav-meeting { color: #4a6e9e; }
.custom-nav-icon.nav-talk { color: #5a8a5c; }
.custom-nav-icon.nav-guide { color: #9e7640; }
.custom-nav-icon.nav-duty { color: #4a8a8b; }
.custom-nav-icon.nav-record { color: #7a6a9e; }
.custom-nav-label {
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.35;
    color: #444;
    text-align: center;
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .custom-nav-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 400px) {
    .custom-nav-item { padding: 10px 4px; }
    .custom-nav-label { font-size: 11.5px; }
}
</style>

<div class="custom-nav-section">
    <div class="custom-nav-grid">
        <a href="<?=BASE_PATH?>/pages/meeting_program.php" class="custom-nav-item nav-bg-meeting">
            <div class="custom-nav-icon nav-meeting"><i class="bi bi-journal-text"></i></div>
            <div class="custom-nav-label">평일집회<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/public_talk.php" class="custom-nav-item nav-bg-talk">
            <div class="custom-nav-icon nav-talk"><i class="bi bi-journal-text"></i></div>
            <div class="custom-nav-label">주말집회<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/service_guide_calendar.php" class="custom-nav-item nav-bg-guide">
            <div class="custom-nav-icon nav-guide"><i class="bi bi-calendar3"></i></div>
            <div class="custom-nav-label">봉사인도<br>계획표</div>
        </a>
        <a href="<?=BASE_PATH?>/pages/duty_schedule.php" class="custom-nav-item nav-bg-duty">
            <div class="custom-nav-icon nav-duty"><i class="bi bi-mic"></i></div>
            <div class="custom-nav-label">청소·음료<br>마이크·안내</div>
        </a>
        <?php if(is_admin(mb_id())): ?>
        <a href="<?=BASE_PATH?>/pages/ministry_record.php" class="custom-nav-item nav-bg-record">
            <div class="custom-nav-icon nav-record"><i class="bi bi-people"></i></div>
            <div class="custom-nav-label">호별봉사<br>짝배정</div>
        </a>
        <?php endif; ?>
    </div>
</div>
