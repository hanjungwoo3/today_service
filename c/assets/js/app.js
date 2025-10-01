(() => {
  const appShell = document.querySelector('.app-shell')
  if (!appShell) return

  const year = Number(appShell.dataset.year)
  const month = Number(appShell.dataset.month)
  const form = document.getElementById('calendarForm')
  const backupList = document.getElementById('backupList')
  const saveBtn = document.getElementById('saveBtn')

  const prevBtn = document.getElementById('prevMonth')
  const nextBtn = document.getElementById('nextMonth')
  const todayBtn = document.getElementById('jumpToday')

  const navigate = (targetYear, targetMonth) => {
    const params = new URLSearchParams({ year: targetYear, month: targetMonth })
    window.location.search = params.toString()
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      const date = new Date(year, month - 2)
      navigate(date.getFullYear(), date.getMonth() + 1)
    })
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      const date = new Date(year, month)
      navigate(date.getFullYear(), date.getMonth() + 1)
    })
  }

  if (todayBtn) {
    todayBtn.addEventListener('click', () => {
      const today = new Date()
      navigate(today.getFullYear(), today.getMonth() + 1)
    })
  }

  const fetchBackups = async () => {
    try {
      const response = await fetch(`api/calendar.php?year=${year}&month=${month}&backups=1`)
      if (!response.ok) return
      const data = await response.json()
      renderBackups(Array.isArray(data.backups) ? data.backups : [])
    } catch (error) {
      console.error(error)
    }
  }

  const renderBackups = (backups) => {
    if (!backupList) return
    if (backups.length === 0) {
      backupList.innerHTML = '<p class="backup-item">백업이 없습니다.</p>'
      return
    }
    backupList.innerHTML = backups
      .map((file) => `<div class="backup-item">${file}</div>`)
      .join('')
  }

  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault()
      if (!saveBtn) return

      const formData = new FormData(form)
      const payload = {
        year: formData.get('year'),
        month: formData.get('month'),
        entries: {},
        schedule_guide: {}
      }

      formData.forEach((value, key) => {
        // Handle entries
        if (key.startsWith('entries[')) {
          const noteMatch = key.match(/entries\[(\d{4}-\d{2}-\d{2})\]\[note\]/)
          if (noteMatch) {
            const [, date] = noteMatch
            if (!payload.entries[date]) {
              payload.entries[date] = { note: '', names: ['', '', ''] }
            }
            payload.entries[date].note = typeof value === 'string' ? value : ''
            return
          }

          const nameMatch = key.match(/entries\[(\d{4}-\d{2}-\d{2})\]\[names\]\[(\d)\]/)
          if (nameMatch) {
            const [, date, index] = nameMatch
            if (!payload.entries[date]) {
              payload.entries[date] = { note: '', names: ['', '', ''] }
            }
            payload.entries[date].names[Number(index)] = typeof value === 'string' ? value : ''
          }
        }
        
        // Handle schedule_guide
        if (key.startsWith('schedule_guide[')) {
          const guideMatch = key.match(/schedule_guide\[(\w+)\]\[(\w+)\]\[(\w+)\]/)
          if (guideMatch) {
            const [, day, time, field] = guideMatch
            if (!payload.schedule_guide[day]) {
              payload.schedule_guide[day] = {}
            }
            if (!payload.schedule_guide[day][time]) {
              payload.schedule_guide[day][time] = { text: '', color: 'white' }
            }
            payload.schedule_guide[day][time][field] = typeof value === 'string' ? value : ''
          }
        }
      })

      saveBtn.disabled = true
      saveBtn.textContent = '저장 중...'

      try {
        const response = await fetch('api/calendar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload),
        })

        const data = await response.json()
        if (response.ok && data.success) {
          window.location.href = `?year=${payload.year}&month=${payload.month}&status=saved`
        } else {
          console.error(data.error)
          window.location.href = `?year=${payload.year}&month=${payload.month}&status=error`
        }
      } catch (error) {
        console.error(error)
        window.location.href = `?year=${payload.year}&month=${payload.month}&status=error`
      } finally {
        saveBtn.disabled = false
        saveBtn.textContent = '저장하기'
      }
    })
  }

  fetchBackups()

  // Color select dropdown functionality
  const colorSelectWrappers = document.querySelectorAll('.color-select-wrapper')
  
  colorSelectWrappers.forEach(wrapper => {
    const trigger = wrapper.querySelector('.color-select-trigger')
    const selectedColor = wrapper.querySelector('.selected-color')
    const options = wrapper.querySelectorAll('.color-option')
    const hiddenInput = wrapper.closest('.guide-time-row')?.querySelector('.color-value')
    
    // Toggle dropdown on trigger click
    trigger.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      
      // Close all other dropdowns
      colorSelectWrappers.forEach(w => {
        if (w !== wrapper) w.classList.remove('open')
      })
      
      // Toggle this dropdown
      wrapper.classList.toggle('open')
    })
    
    // Handle color option selection
    options.forEach(option => {
      option.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        
        const color = option.dataset.color
        const bgColor = option.style.background
        const border = option.style.border
        
        // Update selected color display
        selectedColor.style.background = bgColor
        selectedColor.style.border = border
        selectedColor.dataset.color = color
        
        // Update hidden input
        if (hiddenInput) {
          hiddenInput.value = color
        }
        
        // Close dropdown
        wrapper.classList.remove('open')
      })
    })
  })
  
  // Close dropdowns when clicking outside
  document.addEventListener('click', () => {
    colorSelectWrappers.forEach(wrapper => {
      wrapper.classList.remove('open')
    })
  })

  // Update holidays data
  const updateHolidaysBtn = document.getElementById('updateHolidays')
  if (updateHolidaysBtn) {
    updateHolidaysBtn.addEventListener('click', async () => {
      if (!confirm('공휴일 데이터를 업데이트하시겠습니까?')) {
        return
      }

      updateHolidaysBtn.disabled = true
      updateHolidaysBtn.textContent = '업데이트 중...'

      try {
        const response = await fetch('api/holidays.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()
        
        if (response.ok && data.success) {
          alert(data.message)
          // 페이지 새로고침하여 업데이트된 공휴일 반영
          window.location.reload()
        } else {
          alert('오류: ' + (data.error || '알 수 없는 오류가 발생했습니다.'))
        }
      } catch (error) {
        console.error(error)
        alert('공휴일 데이터를 업데이트하는 중 오류가 발생했습니다.')
      } finally {
        updateHolidaysBtn.disabled = false
        updateHolidaysBtn.textContent = '공휴일 업데이트'
      }
    })
  }

  // Load previous month data
  const loadPrevBtn = document.getElementById('loadPrevMonth')
  if (loadPrevBtn) {
    loadPrevBtn.addEventListener('click', async () => {
      if (!confirm('이전 달의 값을 불러오시겠습니까?\n(현재 입력된 내용이 덮어씌워집니다)')) {
        return
      }

      const prevDate = new Date(year, month - 2)
      const prevYear = prevDate.getFullYear()
      const prevMonth = prevDate.getMonth() + 1

      try {
        const response = await fetch(`api/calendar.php?year=${prevYear}&month=${prevMonth}`)
        if (!response.ok) {
          alert('이전 달 데이터를 불러올 수 없습니다.')
          return
        }

        const prevData = await response.json()
        
        if (!prevData.dates || Object.keys(prevData.dates).length === 0) {
          alert('이전 달에 입력된 데이터가 없습니다.')
          return
        }

        // 달력 주차 계산: 1일이 포함된 일~토 주를 1주차로
        const getCalendarWeek = (year, month, day) => {
          const date = new Date(year, month - 1, day)
          const firstDay = new Date(year, month - 1, 1)
          const firstDayOfWeek = firstDay.getDay() // 0(일) ~ 6(토)
          
          // 1일이 포함된 주의 일요일 날짜
          const firstSunday = 1 - firstDayOfWeek
          
          // 현재 날짜가 몇 주차인지 계산 (1일이 포함된 주가 1주차)
          const weekNumber = Math.floor((day - firstSunday) / 7) + 1
          
          return weekNumber
        }

        // 로컬 날짜를 YYYY-MM-DD 형식으로 변환
        const formatLocalDate = (year, month, day) => {
          return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
        }

        const currentMonthDates = {}
        const prevMonthDates = {}

        // 현재 달의 날짜별 주차와 요일로 매핑
        for (let day = 1; day <= 31; day++) {
          const date = new Date(year, month - 1, day)
          if (date.getMonth() !== month - 1) break
          
          const weekday = date.getDay()
          const weekNum = getCalendarWeek(year, month, day)
          const dateStr = formatLocalDate(year, month, day)
          const key = `${weekday}-${weekNum}`
          
          currentMonthDates[key] = dateStr
        }

        // 이전 달의 날짜별 주차와 요일로 매핑
        const prevMonthLastDay = new Date(prevYear, prevMonth, 0).getDate()
        for (let day = 1; day <= prevMonthLastDay; day++) {
          const date = new Date(prevYear, prevMonth - 1, day)
          const weekday = date.getDay()
          const weekNum = getCalendarWeek(prevYear, prevMonth, day)
          const dateStr = formatLocalDate(prevYear, prevMonth, day)
          const key = `${weekday}-${weekNum}`
          
          prevMonthDates[key] = dateStr
        }

        // 데이터 매칭 및 입력
        let loadedCount = 0
        console.log('=== 날짜 매칭 정보 ===')
        console.log('현재 달 매핑:', currentMonthDates)
        console.log('이전 달 매핑:', prevMonthDates)
        console.log('이전 달 데이터:', prevData.dates)
        
        for (const [key, currentDateStr] of Object.entries(currentMonthDates)) {
          const prevDateStr = prevMonthDates[key]
          const hasData = prevDateStr && prevData.dates[prevDateStr] ? '✓' : '✗'
          console.log(`${key}: ${currentDateStr} ← ${prevDateStr || '없음'} ${hasData}`)
          
          if (prevDateStr && prevData.dates[prevDateStr]) {
            const prevEntry = prevData.dates[prevDateStr]
            
            // 일정 메모 입력
            const noteInput = document.querySelector(`input[name="entries[${currentDateStr}][note]"]`)
            if (noteInput && prevEntry.note) {
              noteInput.value = prevEntry.note
            }
            
            // 이름 1, 2, 3 입력
            if (prevEntry.names && Array.isArray(prevEntry.names)) {
              prevEntry.names.forEach((name, idx) => {
                const nameInput = document.querySelector(`input[name="entries[${currentDateStr}][names][${idx}]"]`)
                if (nameInput && name) {
                  nameInput.value = name
                }
              })
            }
            
            loadedCount++
          }
        }
        console.log(`총 ${loadedCount}개 매칭됨`)

        // 요일별 시간대 안내 복사
        if (prevData.schedule_guide) {
          const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
          const times = ['morning', 'afternoon', 'evening']
          
          console.log('주간 일정 안내 복사 시작')
          
          days.forEach(day => {
            times.forEach(time => {
              if (prevData.schedule_guide[day] && prevData.schedule_guide[day][time]) {
                const guideData = prevData.schedule_guide[day][time]
                
                // 텍스트 입력
                const textArea = document.querySelector(`textarea[name="schedule_guide[${day}][${time}][text]"]`)
                if (textArea) {
                  textArea.value = guideData.text || ''
                  console.log(`${day}-${time} 텍스트:`, guideData.text)
                }
                
                // 색상 입력
                const colorInput = document.querySelector(`input[name="schedule_guide[${day}][${time}][color]"]`)
                if (colorInput) {
                  colorInput.value = guideData.color || 'white'
                  console.log(`${day}-${time} 색상:`, guideData.color)
                  
                  // 색상 버튼 UI 업데이트
                  const row = colorInput.closest('.guide-time-row')
                  if (row) {
                    const selectedColor = row.querySelector('.selected-color')
                    const colorOptions = {
                      'white': { bg: '#f9fbff', border: '1px solid #cbd5e1' },
                      'green': { bg: '#86efac', border: '1px solid #86efac' },
                      'blue': { bg: '#93c5fd', border: '1px solid #93c5fd' },
                      'red': { bg: '#fca5a5', border: '1px solid #fca5a5' }
                    }
                    
                    const color = guideData.color || 'white'
                    if (selectedColor && colorOptions[color]) {
                      selectedColor.style.background = colorOptions[color].bg
                      selectedColor.style.border = colorOptions[color].border
                      selectedColor.dataset.color = color
                      console.log(`${day}-${time} UI 업데이트 완료:`, color)
                    }
                  }
                }
              }
            })
          })
          
          console.log('주간 일정 안내 복사 완료')
        }

        alert(`이전 달(${prevYear}년 ${prevMonth}월)의 데이터를 불러왔습니다.\n${loadedCount}개의 날짜 데이터가 매칭되었습니다.\n\n저장하려면 "저장하기" 버튼을 클릭하세요.`)
      } catch (error) {
        console.error(error)
        alert('데이터를 불러오는 중 오류가 발생했습니다.')
      }
    })
  }
})()

