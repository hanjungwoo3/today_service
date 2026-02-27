self.addEventListener('install', event => {
    console.log('Service Worker: Installed');
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('Service Worker: Activated');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});

// Push 알림 수신
self.addEventListener('push', event => {
    var data = { title: '오늘의 봉사', body: '새 알림이 있습니다.', url: '/' };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    var options = {
        body: data.body,
        icon: '/icons/icon-jw-n.png',
        badge: '/icons/icon-jw-n.png',
        data: { url: data.url || '/' },
        vibrate: [200, 100, 200]
    };

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            var visibleClients = clientList.filter(function(c) {
                return c.visibilityState === 'visible';
            });

            // 앱이 안 보이면 → OS 알림
            if (visibleClients.length === 0) {
                return self.registration.showNotification(data.title, options);
            }

            // 앱이 보이면 → 클라이언트에 채팅창 상태 확인
            return new Promise(function(resolve) {
                var answered = false;
                var channel = new MessageChannel();

                channel.port1.onmessage = function(e) {
                    if (answered) return;
                    answered = true;
                    if (e.data && e.data.chatOpen) {
                        // 채팅창 열려있음 → 알림 불필요 (클라이언트가 즉시 갱신)
                        resolve();
                    } else {
                        // 채팅창 닫혀있음 → OS 알림 표시
                        resolve(self.registration.showNotification(data.title, options));
                    }
                };

                // 첫 번째 보이는 클라이언트에 질의
                visibleClients[0].postMessage(
                    { type: 'PUSH_CHECK', data: data },
                    [channel.port2]
                );

                // 500ms 내 응답 없으면 알림 표시
                setTimeout(function() {
                    if (answered) return;
                    answered = true;
                    resolve(self.registration.showNotification(data.title, options));
                }, 500);
            });
        })
    );
});

// 알림 클릭 시 해당 페이지로 이동
self.addEventListener('notificationclick', event => {
    event.notification.close();

    var url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // 이미 열린 탭이 있으면 포커스
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf(self.location.origin) !== -1 && 'focus' in client) {
                    client.focus();
                    client.navigate(url);
                    return;
                }
            }
            // 열린 탭이 없으면 새 창
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
