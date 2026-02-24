/**
 * 구역 배정 쪽지 (Territory Messaging)
 * - 배정 카드에서 인라인 채팅 패널
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

    function _getInterval() {
        var idx = Math.min(_noChangeCount, POLL_INTERVALS.length - 1);
        return POLL_INTERVALS[idx];
    }

    function _resetInterval() {
        _noChangeCount = 0;
    }

    // 뱃지 갱신: 타입별로 분리하여 안 읽은 수 업데이트
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

        // 타입별로 API 호출
        Object.keys(grouped).forEach(function(type) {
            $.ajax({
                url: BASE_PATH + '/pages/territory_msg_api.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'unread_counts', tt_ids: grouped[type].join(','), type: type },
                success: function(res) {
                    if (!res.counts) return;
                    btns.forEach(function(btn) {
                        var btnType = btn.getAttribute('data-msg-type') || 'T';
                        if (btnType !== type) return;
                        var ttId = btn.getAttribute('data-tt-id');
                        var badge = btn.querySelector('.territory-msg-badge');
                        if (!badge) return;
                        var count = res.counts[ttId] || 0;
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.style.display = '';
                        } else {
                            badge.style.display = 'none';
                        }
                    });
                }
            });
        });
    }

    // 클릭한 카드 바로 아래로 패널 컨테이너 이동
    function _positionContainer(ttId, type) {
        var container = document.getElementById('territory-msg-container');
        if (!container) return;
        var selector = '.territory-msg-btn[data-tt-id="' + ttId + '"][data-msg-type="' + type + '"]';
        var btn = document.querySelector(selector);
        if (btn) {
            var card = btn.closest('.list-group');
            if (card) {
                card.parentNode.insertBefore(container, card.nextSibling);
            }
        }
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

        var container = document.getElementById('territory-msg-container');
        if (!container) return;

        // 클릭한 카드 바로 아래에 위치시키기
        _positionContainer(ttId, type);

        var titleLabel = type === 'D' ? '전시대 쪽지' : _escHtml(ttNum) + ' 구역 쪽지';

        container.innerHTML =
            '<div class="tmsg-panel">' +
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

        container.style.display = '';

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

    // 내부 닫기 (상태 초기화만, 목록 갱신 안 함)
    function _closePanelQuiet() {
        _stopPolling();
        _activeTtId = null;
        _activeTtNum = '';
        _activeType = 'T';
        _lastId = 0;

        var container = document.getElementById('territory-msg-container');
        if (container) {
            container.innerHTML = '';
            container.style.display = 'none';
        }
    }

    // 패널 닫기 (+ 목록 갱신)
    function closePanel() {
        _closePanelQuiet();

        // 패널 열려있는 동안 스킵된 목록 갱신을 즉시 실행
        var serviceList = document.getElementById('today-service-list');
        if (serviceList && typeof pageload_custom === 'function') {
            var localYmd = new Date().toISOString().slice(0, 10);
            pageload_custom(BASE_PATH + '/pages/today_service_list.php?s_date=' + localYmd, '#today-service-list');
        }
    }

    // 수동 새로고침 (목록 갱신 없이 패널만 재로드)
    function reloadPanel() {
        if (!_activeTtId) return;
        var ttId = _activeTtId;
        var ttNum = _activeTtNum;
        var myMbId = _myMbId;
        var type = _activeType;
        _closePanelQuiet();
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

    // visibilitychange: 탭 비활성 시 폴링 중지, 활성 시 재개
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            _stopPolling();
        } else if (_activeTtId) {
            _poll();
        }
    });

    // AJAX 요청 전: 목록 갱신 시 패널 닫기 + 컨테이너 대피
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('today_service_list.php') !== -1) {
            // 패널 열려있으면 상태 초기화 (참석/불참 등 외부 갱신과 충돌 방지)
            if (_activeTtId) {
                _stopPolling();
                _activeTtId = null;
                _activeTtNum = '';
                _activeType = 'T';
                _lastId = 0;
            }
            var container = document.getElementById('territory-msg-container');
            if (container) {
                container.innerHTML = '';
                container.style.display = 'none';
            }
            // 컨테이너가 #today-service-list 안에 있으면 밖으로 대피
            var serviceList = document.getElementById('today-service-list');
            if (container && serviceList && serviceList.contains(container)) {
                serviceList.parentNode.insertBefore(container, serviceList.nextSibling);
            }
        }
    });

    // AJAX 완료 후: 뱃지 새로고침 + 패널 위치 재조정
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('today_service_list.php') !== -1) {
            refreshBadges();
            if (_activeTtId) {
                _positionContainer(_activeTtId, _activeType);
            }
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
