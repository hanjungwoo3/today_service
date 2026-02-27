/**
 * 구역 배정 쪽지 (Territory Messaging)
 * - 팝업 채팅 창 (fixed position overlay)
 * - 적응형 폴링 (5s → 10s → 30s → 60s)
 * - type: 'T'=호별구역, 'D'=전시대
 */
var TerritoryMsg = (function() {
    var _pollTimer = null;
    var _activeTtId = null;
    var _activeTtNum = '';
    var _activeType = 'T';
    var _lastId = 0;
    var _noChangeCount = 0;
    var _myMbId = 0;

    var POLL_INTERVALS = [5000, 5000, 10000, 10000, 30000, 30000, 60000];
    var _prevUnreadTotal = -1; // 새 쪽지 알림용 이전 안 읽은 수 (-1: 초기화 전)

    function _getInterval() {
        var idx = Math.min(_noChangeCount, POLL_INTERVALS.length - 1);
        return POLL_INTERVALS[idx];
    }

    function _resetInterval() {
        _noChangeCount = 0;
    }

    // 뱃지 갱신: 타입별로 분리하여 안 읽은 수 업데이트 + 새 쪽지 알림
    function refreshBadges() {
        var btns = document.querySelectorAll('.territory-msg-btn');
        if (btns.length === 0) return;

        // 타입별로 ID 수집
        var grouped = {};
        btns.forEach(function(btn) {
            var type = btn.getAttribute('data-msg-type') || 'T';
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(btn.getAttribute('data-tt-id'));
        });

        var types = Object.keys(grouped);
        var totalUnread = 0;
        var completed = 0;

        // 타입별로 API 호출
        types.forEach(function(type) {
            $.ajax({
                url: BASE_PATH + '/pages/territory_msg_api.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'unread_counts', tt_ids: grouped[type].join(','), type: type },
                success: function(res) {
                    if (res.counts) {
                        btns.forEach(function(btn) {
                            var btnType = btn.getAttribute('data-msg-type') || 'T';
                            if (btnType !== type) return;
                            var ttId = btn.getAttribute('data-tt-id');
                            var badge = btn.querySelector('.territory-msg-badge');
                            var count = res.counts[ttId] || 0;
                            totalUnread += count;
                            if (!badge) return;
                            if (count > 0) {
                                badge.textContent = count > 99 ? '99+' : count;
                                badge.style.display = '';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    }
                    completed++;
                    if (completed >= types.length) {
                        // 안 읽은 수가 증가했으면 알림 표시
                        if (_prevUnreadTotal >= 0 && totalUnread > _prevUnreadTotal) {
                            _showNewMsgToast();
                        }
                        _prevUnreadTotal = totalUnread;
                    }
                }
            });
        });
    }

    // 새 쪽지 도착 토스트 알림 (모달 위에도 표시)
    function _showNewMsgToast() {
        // 팝업이 열려있으면 알림 불필요 (이미 실시간 폴링 중)
        if (_activeTtId) return;
        // Push 구독 중이면 토스트 생략 (Push 알림이 대신 처리)
        if (window._pushSubscribed) return;

        var existing = document.getElementById('tmsg-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'tmsg-toast';
        toast.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:99999;' +
            'background:#333;color:#fff;padding:10px 20px;border-radius:24px;font-size:14px;' +
            'box-shadow:0 4px 12px rgba(0,0,0,0.3);cursor:pointer;opacity:0;transition:opacity 0.3s;';
        toast.innerHTML = '<i class="bi bi-chat-dots"></i> 새 쪽지가 도착했습니다';
        toast.onclick = function() {
            // 모달 열려있으면 닫기
            $('.modal.show').modal('hide');
            toast.remove();
        };

        document.body.appendChild(toast);
        requestAnimationFrame(function() { toast.style.opacity = '1'; });
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
        }, 4000);
    }

    // 패널 열기
    function openPanel(ttId, ttNum, myMbId, type) {
        type = type || 'T';

        // 이미 같은 구역 열려있으면 닫기 (토글)
        if (_activeTtId === ttId && _activeType === type) {
            closePanel();
            return;
        }

        // 다른 패널 열려있으면 먼저 닫기
        if (_activeTtId) closePanel();

        _activeTtId = ttId;
        _activeTtNum = ttNum;
        _activeType = type;
        _myMbId = myMbId;
        _lastId = 0;
        _resetInterval();

        var popup = document.getElementById('tmsg-popup');
        if (!popup) return;

        // 배경 딤 표시
        var backdrop = document.getElementById('tmsg-backdrop');
        if (backdrop) backdrop.style.display = 'block';

        var titleLabel = type === 'D' ? '전시대 쪽지' : _escHtml(ttNum) + ' 구역 쪽지';

        popup.innerHTML =
            '<div class="tmsg-popup-inner">' +
                '<div class="tmsg-header">' +
                    '<span class="tmsg-title">' + titleLabel + '</span>' +
                    '<span class="tmsg-header-btns">' +
                        '<button type="button" class="tmsg-refresh" onclick="TerritoryMsg.reloadPanel()" title="새로고침"><i class="bi bi-arrow-clockwise"></i></button>' +
                        '<button type="button" class="tmsg-close" onclick="TerritoryMsg.closePanel()">&times;</button>' +
                    '</span>' +
                '</div>' +
                '<div class="tmsg-body" id="tmsg-body">' +
                    '<div class="tmsg-loading">불러오는 중...</div>' +
                '</div>' +
                '<div class="tmsg-footer">' +
                    '<input type="text" id="tmsg-input" placeholder="메시지 입력..." maxlength="500" autocomplete="off">' +
                    '<button type="button" id="tmsg-send" onclick="TerritoryMsg.sendMessage()"><i class="bi bi-send"></i></button>' +
                '</div>' +
            '</div>';

        popup.style.display = '';

        // Enter 키 전송
        document.getElementById('tmsg-input').addEventListener('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                TerritoryMsg.sendMessage();
            }
        });

        // 메시지 로드
        $.ajax({
            url: BASE_PATH + '/pages/territory_msg_api.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'load', tt_id: ttId, type: _activeType },
            success: function(res) {
                if (res.error) {
                    _showError(res.error);
                    return;
                }
                _lastId = res.last_id || 0;
                var body = document.getElementById('tmsg-body');
                if (!body) return;
                body.innerHTML = '';
                if (res.messages && res.messages.length > 0) {
                    res.messages.forEach(function(msg) {
                        body.appendChild(_createMsgEl(msg));
                    });
                } else {
                    body.innerHTML = '<div class="tmsg-empty">쪽지가 없습니다. 첫 메시지를 보내보세요.</div>';
                }
                _scrollToBottom(body);

                // 해당 뱃지 숨기기
                _clearBadge(ttId, _activeType);

                // 폴링 시작
                _startPolling();
            },
            error: function() {
                _showError('쪽지를 불러올 수 없습니다.');
            }
        });
    }

    // 패널 닫기
    function closePanel() {
        _stopPolling();
        _activeTtId = null;
        _activeTtNum = '';
        _activeType = 'T';
        _lastId = 0;

        var popup = document.getElementById('tmsg-popup');
        if (popup) {
            popup.innerHTML = '';
            popup.style.display = 'none';
        }

        // 배경 딤 숨기기
        var backdrop = document.getElementById('tmsg-backdrop');
        if (backdrop) backdrop.style.display = 'none';

        // 뱃지 즉시 갱신 (읽음 처리 반영)
        refreshBadges();
    }

    // 수동 새로고침
    function reloadPanel() {
        if (!_activeTtId) return;
        var ttId = _activeTtId;
        var ttNum = _activeTtNum;
        var myMbId = _myMbId;
        var type = _activeType;
        closePanel();
        openPanel(ttId, ttNum, myMbId, type);
    }

    // 메시지 전송
    function sendMessage() {
        if (!_activeTtId) return;

        var input = document.getElementById('tmsg-input');
        if (!input) return;
        var message = input.value.trim();
        if (!message) return;

        input.value = '';

        // Optimistic UI: 즉시 표시
        var body = document.getElementById('tmsg-body');
        if (body) {
            var empty = body.querySelector('.tmsg-empty');
            if (empty) empty.remove();

            var tempMsg = {
                tm_id: 0,
                mb_id: _myMbId,
                mb_name: '',
                tm_message: message,
                tm_datetime: _formatNow()
            };
            body.appendChild(_createMsgEl(tempMsg));
            _scrollToBottom(body);
        }

        $.ajax({
            url: BASE_PATH + '/pages/territory_msg_api.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'send', tt_id: _activeTtId, type: _activeType, message: message },
            success: function(res) {
                if (res.success && res.tm_id) {
                    _lastId = Math.max(_lastId, res.tm_id);
                }
                _resetInterval();
                _stopPolling();
                _poll(); // 즉시 새 메시지 확인
                refreshBadges();
            }
        });
    }

    // 폴링 시작
    function _startPolling() {
        _stopPolling();
        _pollTimer = setTimeout(_poll, _getInterval());
    }

    // 폴링 중지
    function _stopPolling() {
        if (_pollTimer) {
            clearTimeout(_pollTimer);
            _pollTimer = null;
        }
    }

    // 폴링 실행
    function _poll() {
        if (!_activeTtId || document.hidden) {
            _pollTimer = setTimeout(_poll, _getInterval());
            return;
        }

        $.ajax({
            url: BASE_PATH + '/pages/territory_msg_api.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'poll', tt_id: _activeTtId, type: _activeType, last_id: _lastId },
            success: function(res) {
                if (res.error) return;

                if (res.messages && res.messages.length > 0) {
                    var body = document.getElementById('tmsg-body');
                    if (body) {
                        var empty = body.querySelector('.tmsg-empty');
                        if (empty) empty.remove();

                        res.messages.forEach(function(msg) {
                            if (msg.mb_id === _myMbId) return;
                            body.appendChild(_createMsgEl(msg));
                        });
                        _scrollToBottom(body);
                    }
                    _lastId = res.last_id;
                    _resetInterval();
                    refreshBadges();
                } else {
                    _noChangeCount++;
                }

                if (_activeTtId) {
                    _pollTimer = setTimeout(_poll, _getInterval());
                }
            },
            error: function() {
                _noChangeCount++;
                if (_activeTtId) {
                    _pollTimer = setTimeout(_poll, _getInterval());
                }
            }
        });
    }

    // 메시지 DOM 요소 생성
    function _createMsgEl(msg) {
        var isMine = (msg.mb_id === _myMbId);
        var div = document.createElement('div');
        div.className = 'tmsg-item' + (isMine ? ' mine' : '');

        if (!isMine && msg.mb_name) {
            var nameEl = document.createElement('div');
            nameEl.className = 'tmsg-name';
            nameEl.textContent = msg.mb_name;
            div.appendChild(nameEl);
        }

        var textEl = document.createElement('div');
        textEl.className = 'tmsg-text';
        textEl.textContent = msg.tm_message;
        div.appendChild(textEl);

        var timeEl = document.createElement('div');
        timeEl.className = 'tmsg-time';
        timeEl.textContent = _formatTime(msg.tm_datetime);
        div.appendChild(timeEl);

        return div;
    }

    function _formatTime(datetime) {
        if (!datetime) return '';
        var d = new Date(datetime.replace(' ', 'T'));
        var now = new Date();
        var time = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        if (d.toDateString() === now.toDateString()) {
            return time;
        }
        return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + time;
    }

    function _formatNow() {
        var d = new Date();
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0') + ' ' +
            String(d.getHours()).padStart(2, '0') + ':' +
            String(d.getMinutes()).padStart(2, '0') + ':' +
            String(d.getSeconds()).padStart(2, '0');
    }

    function _scrollToBottom(el) {
        if (el) el.scrollTop = el.scrollHeight;
    }

    function _clearBadge(ttId, type) {
        var prefix = (type === 'D') ? 'msg-badge-d-' : 'msg-badge-';
        var badge = document.getElementById(prefix + ttId);
        if (badge) badge.style.display = 'none';
    }

    function _showError(msg) {
        var body = document.getElementById('tmsg-body');
        if (body) body.innerHTML = '<div class="tmsg-empty">' + _escHtml(msg) + '</div>';
    }

    function _escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // 배경 딤 클릭 시 패널 닫기
    $(document).on('click', '#tmsg-backdrop', function() {
        closePanel();
    });

    // visibilitychange: 탭 비활성 시 폴링 중지, 활성 시 재개
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            _stopPolling();
        } else if (_activeTtId) {
            _poll();
        }
    });

    // Bootstrap 모달 열릴 때 팝업 닫기 (모달이 포커스를 가져가므로)
    $(document).on('show.bs.modal', function() {
        if (_activeTtId) closePanel();
    });

    // Service Worker에서 Push 수신 시 채팅창 상태 응답 + 즉시 갱신
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'PUSH_CHECK') {
                var chatOpen = !!_activeTtId;
                // SW에 채팅창 상태 응답
                if (event.ports && event.ports[0]) {
                    event.ports[0].postMessage({ chatOpen: chatOpen });
                }
                // 뱃지 즉시 갱신
                refreshBadges();
                // 채팅창 열려있으면 새 메시지도 즉시 로드
                if (chatOpen) {
                    _resetInterval();
                    _stopPolling();
                    _poll();
                }
            }
        });
    }

    // AJAX 완료 후: 뱃지 새로고침
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('today_service_list.php') !== -1) {
            refreshBadges();
        }
    });

    return {
        refreshBadges: refreshBadges,
        openPanel: openPanel,
        closePanel: closePanel,
        reloadPanel: reloadPanel,
        sendMessage: sendMessage,
        isOpen: function() { return _activeTtId !== null; }
    };
})();
