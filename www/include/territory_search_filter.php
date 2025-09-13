<?php $c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE); ?>

<template v-if="location === 'guide'">
  <div class="form-group row">
    <label for="search_type" class="col-4 col-md-2 col-form-label">구역 유형</label>
    <div class="col-8 col-md-10">
      <select name="search_type" class="form-control" id="search_type" v-model="search_type">
        <option value="territory">일반</option>
        <option value="telephone">전화</option>
        <option value="letter">편지</option>
      </select>
    </div>
  </div>
</template>

<template v-if="search_type === 'territory'">
  <div class="form-group row">
    <label for="s_type" class="col-4 col-md-2 col-form-label">구역 형태</label>
    <div class="col-8 col-md-10">
      <select name="s_type" class="form-control" id="s_type" v-model="s_type">
        <?php echo get_territory_type_options('search', ''); ?>
      </select>
    </div>
  </div>
</template>

<div class="form-group row">
  <label for="s_assign" class="col-4 col-md-2 col-form-label">분배 상태</label>
  <div class="col-8 col-md-10">
    <select name="s_assign" class="form-control" id="s_assign" v-model="s_assign">
      <option value="선택안함">선택 안 함</option>
      <template v-if="location === 'admin'">
        <option value="분배되지않음">분배되지 않음</option>
        <option value="개인구역">개인 구역</option>
      </template>
      <optgroup label="모임 형태">
        <option value="전체">전체</option>
        <option value="호별"><?=get_meeting_schedule_type_text(1)?></option>
        <option value="전시대"><?=get_meeting_schedule_type_text(2)?></option>
        <template v-if="location === 'guide'">
          <?php if(!isset($c_meeting_schedule_type_use[3]) || $c_meeting_schedule_type_use[3] === 'use'): ?>
          <option value="추가1"><?=get_meeting_schedule_type_text(3)?></option>
          <?php endif; ?>
          <?php if(!isset($c_meeting_schedule_type_use[4]) || $c_meeting_schedule_type_use[4] === 'use'): ?>
          <option value="추가2"><?=get_meeting_schedule_type_text(4)?></option>
          <?php endif; ?>
          <?php if(!isset($c_meeting_schedule_type_use[5]) || $c_meeting_schedule_type_use[5] === 'use'): ?>
          <option value="추가3"><?=get_meeting_schedule_type_text(5)?></option>
          <?php endif; ?>
          <?php if(!isset($c_meeting_schedule_type_use[6]) || $c_meeting_schedule_type_use[6] === 'use'): ?>
          <option value="추가4"><?=get_meeting_schedule_type_text(6)?></option>
          <?php endif; ?>
        </template>
        <template v-if="location === 'admin'">
          <option value="추가1"><?php if(empty($c_meeting_schedule_type_use[3])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(3)?></option>
          <option value="추가2"><?php if(empty($c_meeting_schedule_type_use[4])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(4)?></option>
          <option value="추가3"><?php if(empty($c_meeting_schedule_type_use[5])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(5)?></option>
          <option value="추가4"><?php if(empty($c_meeting_schedule_type_use[6])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(6)?></option>
        </template>
      </optgroup>
      <optgroup label="모임 계획">
        <?php echo $ms_options;?>
      </optgroup>
    </select>
  </div>
</div>

<div class="form-group row">
  <label for="s_status" class="col-4 col-md-2 col-form-label">배정 상태</label>
  <div class="col-8 col-md-10">
    <select name="s_status" class="form-control" id="s_status" v-model="s_status">
      <option value="선택안함">선택 안 함</option>
      <option value="미배정">미배정</option>
      <option value="첫배정">첫 배정</option>
      <option value="재배정">재배정</option>
      <option value="부재자">부재자 첫 배정</option>
      <option value="부재자재배정">부재자 재배정</option>
    </select>
  </div>
</div>

<div class="form-group row">
  <label for="s_num" class="col-4 col-md-2 col-form-label">구역 번호</label>
  <div class="col-8 col-md-10">
    <input type="text" class="form-control" name="s_num" id="s_num" placeholder="구역 번호 검색" v-model="s_num" v-on:keyup.enter="searchFilter(1)">
  </div>
</div>

<div class="form-group row">
  <label for="s_name" class="col-4 col-md-2 col-form-label">구역 이름</label>
  <div class="col-8 col-md-10">
    <input type="text" class="form-control" name="s_name" id="s_name" placeholder="구역 이름 검색" v-model="s_name" v-on:keyup.enter="searchFilter(1)">
  </div>
</div>

<template v-if="location === 'admin'">
  <div class="form-group row">
    <label for="s_memo" class="col-4 col-md-2 col-form-label">특이사항</label>
    <div class="col-8 col-md-10">
      <select name="s_memo" class="form-control" id="s_memo" v-model="s_memo">
        <option value="선택안함">선택 안 함</option>
        <option value="미포함">미포함</option>
        <option value="포함">포함</option> 
      </select>
    </div>
  </div>
</template>

<button type="button" class="btn btn-outline-secondary float-right" v-on:click="searchFilter(1)">
    <template v-if="search_spinner">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span class="sr-only">Loading...</span>
    </template>
    <template v-else>
      <i class="bi bi-search"></i> 검색
    </template>
</button>
