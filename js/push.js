/**
 * Web Push 알림 클라이언트
 * - 구독/해제
 * - Service Worker 연동
 * - 홈 화면 배너에서 호출
 */
var PushNotify = (function() {
    var _vapidKey = null;
    var _basePath = '';
    var _ready = false;

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; i++) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function init(vapidPublicKey, basePath) {
        _vapidKey = vapidPublicKey;
        _basePath = basePath || '';

        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        if (!_vapidKey) return;

        _ready = true;
        // 구독 상태 확인하여 전역 플래그 설정
        checkSubscription();
    }

    // 구독 상태 확인 → window._pushSubscribed 설정 + 콜백
    function checkSubscription(callback) {
        if (!_ready) { if (callback) callback(false); return; }

        navigator.serviceWorker.ready.then(function(registration) {
            registration.pushManager.getSubscription().then(function(subscription) {
                window._pushSubscribed = !!subscription;
                if (callback) callback(!!subscription);
            });
        });
    }

    function toggleSubscription(callback) {
        if (!_ready) return;

        navigator.serviceWorker.ready.then(function(registration) {
            registration.pushManager.getSubscription().then(function(subscription) {
                if (subscription) {
                    unsubscribe(subscription, callback);
                } else {
                    subscribe(registration, callback);
                }
            });
        });
    }

    function subscribe(registration, callback) {
        Notification.requestPermission().then(function(permission) {
            if (permission !== 'granted') {
                alert('알림 권한이 거부되었습니다.\n브라우저 설정에서 알림을 허용해주세요.');
                if (callback) callback(false);
                return;
            }

            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(_vapidKey)
            }).then(function(subscription) {
                var key = subscription.getKey('p256dh');
                var auth = subscription.getKey('auth');

                $.ajax({
                    url: _basePath + '/pages/push_api.php',
                    method: 'POST',
                    data: {
                        action: 'subscribe',
                        endpoint: subscription.endpoint,
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
                    },
                    success: function(res) {
                        if (res.success) {
                            window._pushSubscribed = true;
                            showToast('알림이 활성화되었습니다.');
                            if (callback) callback(true);
                        } else if (res.error) {
                            alert(res.error);
                            if (callback) callback(false);
                        }
                    },
                    error: function() {
                        alert('구독 저장에 실패했습니다.');
                        if (callback) callback(false);
                    }
                });
            }).catch(function(err) {
                console.error('Push 구독 실패:', err);
                alert('Push 구독 실패: ' + err.message);
                if (callback) callback(false);
            });
        });
    }

    function unsubscribe(subscription, callback) {
        var endpoint = subscription.endpoint;

        subscription.unsubscribe().then(function() {
            $.ajax({
                url: _basePath + '/pages/push_api.php',
                method: 'POST',
                data: {
                    action: 'unsubscribe',
                    endpoint: endpoint
                },
                success: function() {
                    window._pushSubscribed = false;
                    showToast('알림이 비활성화되었습니다.');
                    if (callback) callback(false);
                }
            });
        });
    }

    function showToast(message) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);' +
            'background:#333;color:#fff;padding:10px 20px;border-radius:8px;' +
            'font-size:14px;z-index:99999;opacity:0;transition:opacity 0.3s;';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.style.opacity = '1'; }, 10);
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 2500);
    }

    return {
        init: init,
        toggle: toggleSubscription,
        checkSubscription: checkSubscription,
        isSupported: function() { return _ready; }
    };
})();
