<?php
/**
 * VAPID 키 생성 (일회성, 관리자 전용)
 * 브라우저에서 접속하여 키를 생성하고 DB에 저장
 */
include_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use Minishlink\WebPush\VAPID;

if (!is_admin(mb_id())) {
    echo '관리자만 접근 가능합니다.';
    exit;
}

$existingPublic = get_site_option('vapid_public_key');
$existingPrivate = get_site_option('vapid_private_key');

if (isset($_POST['generate'])) {
    $keys = VAPID::createVapidKeys();
    set_site_option('vapid_public_key', $keys['publicKey']);
    set_site_option('vapid_private_key', $keys['privateKey']);
    $existingPublic = $keys['publicKey'];
    $existingPrivate = $keys['privateKey'];
    $generated = true;
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>VAPID 키 관리</title></head>
<body style="font-family:sans-serif; max-width:600px; margin:40px auto; padding:0 20px;">
<h2>VAPID 키 관리</h2>

<?php if ($existingPublic && $existingPrivate): ?>
    <p style="color:green; font-weight:bold;">VAPID 키가 설정되어 있습니다.</p>
    <p><strong>Public Key:</strong><br>
    <code style="word-break:break-all; font-size:12px;"><?= htmlspecialchars($existingPublic) ?></code></p>
    <p><strong>Private Key:</strong><br>
    <code style="word-break:break-all; font-size:12px;"><?= str_repeat('*', 20) ?> (보안상 숨김)</code></p>
    <?php if (isset($generated)): ?>
        <p style="color:blue;">새 키가 생성되었습니다.</p>
    <?php endif; ?>
    <hr>
    <p style="color:red;">주의: 키를 재생성하면 기존 구독이 모두 무효화됩니다.</p>
<?php else: ?>
    <p>VAPID 키가 아직 생성되지 않았습니다.</p>
<?php endif; ?>

<form method="post">
    <button type="submit" name="generate" value="1"
        onclick="return confirm('<?= $existingPublic ? '기존 키를 재생성하면 모든 구독이 무효화됩니다. 계속하시겠습니까?' : 'VAPID 키를 생성하시겠습니까?' ?>');"
        style="padding:10px 20px; font-size:16px; cursor:pointer;">
        <?= $existingPublic ? '키 재생성' : '키 생성' ?>
    </button>
</form>
</body>
</html>
