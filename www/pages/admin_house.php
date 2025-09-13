<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<?php $c_territory_type = unserialize(TERRITORY_TYPE);?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">세대 관리</span></h1>
  <?php echo header_menu('admin','세대 관리'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="v_admin_house">

  <div id="container" class="container-fluid">
    <form method="post" id="admin-house-search-form" class="mb-4 clearfix">

      <div class="form-group row">
        <label for="admin_house_search_type" class="col-4 col-md-2 col-form-label">구역 유형</label>
        <div class="col-8 col-md-10">
          <select name="type" class="form-control" id="admin_house_search_type" v-model="type">
            <option value="1">일반</option>
            <option value="2">전화</option>
            <option value="3">편지</option>
          </select>
        </div>
      </div>

      <div class="form-group row search-territory">
        <label for="s_type" class="col-4 col-md-2 col-form-label">구역 형태</label>
        <div class="col-8 col-md-10">
          <select name="s_type" class="form-control" id="s_type" v-model="s_type" v-model="s_type">
            <?php echo get_territory_type_options('search', '');?>
          </select>
        </div>
      </div>

      <div class="form-group row">
        <label for="h_assign" class="col-4 col-md-2 col-form-label">특이사항</label>
        <div class="col-8 col-md-10">
          <select name="h_assign" id="h_assign" class="form-control" v-model="h_assign">
            <option value="전체">전체</option>
            <?php if(RETURNVISIT_USE == 'use'): ?>
            <option value="1"><?=get_house_condition_text(1)?></option>
            <option value="2"><?=get_house_condition_text(2)?></option>
            <?php endif; ?>
            <?php for($i=3; $i<11; $i++) echo '<option value="'.$i.'">'.get_house_condition_text($i).'</option>'; ?>
          </select>
        </div>
      </div>

      <div class="form-group row search-territory search-letter">
        <label for="h_address1" class="col-4 col-md-2 col-form-label">주소 1</label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="h_address1" id="h_address1" placeholder="" aria-describedby="h_address1HelpBlock" v-model="h_address1" v-on:keyup.enter="searchHouse(1)">
          <small id="h_address1HelpBlock" class="form-text text-muted">
          [<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?>] 길 이름(도로명)을 입력해 주세요. 예) 구포동길 38번길, 와우안길<br>
          [<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?>] <?=!empty($c_territory_type['type_2'][1])?$c_territory_type['type_2'][1]:'아파트명'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?>] <?=!empty($c_territory_type['type_3'][1])?$c_territory_type['type_3'][1]:'빌라명'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?>] 길 이름(도로명)을 입력해 주세요. 예) 구포동길 38번길, 와우안길<br>
          [편지] 길이름(도로명)을 입력해 주세요. 예) 구포동길 38번길, 와우안길<br>

          [<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?>] 길 이름(도로명)을 입력해 주세요. 예) 구포동길 38번길, 와우안길<br>
          [<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?>] <?=!empty($c_territory_type['type_8'][1])?$c_territory_type['type_8'][1]:''?>을/를 입력해 주세요.<br>
          </small>
        </div>
      </div>

      <div class="form-group row search-territory search-letter">
        <label for="h_address2" class="col-4 col-md-2 col-form-label">주소 2</label>
        <div class="col-8 col-md-10">
        <input type="text" class="form-control" name="h_address2" id="h_address2" placeholder="" aria-describedby="h_address2HelpBlock" v-model="h_address2" v-on:keyup.enter="searchHouse(1)">
          <small id="h_address2HelpBlock" class="form-text text-muted">
          [<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?>] 건물 번호를 입력해 주세요. 예) 90-4<br>
          [<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?>] <?=!empty($c_territory_type['type_2'][2])?$c_territory_type['type_2'][2]:'동'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?>] <?=!empty($c_territory_type['type_3'][2])?$c_territory_type['type_3'][2]:'동'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?>] 건물 번호를 입력해 주세요. 예) 90-4<br>
          [편지] 건물번호를 입력해주세요. 예) 90-4<br>

          [<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?>] 건물 번호를 입력해 주세요. 예) 90-4예) 구포동길 38번길, 와우안길<br>
          [<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?>] <?=!empty($c_territory_type['type_8'][2])?$c_territory_type['type_8'][2]:''?>을/를 입력해 주세요.<br>
          </small>
        </div>
      </div>

      <div class="form-group row search-territory search-letter">
        <label for="h_address3" class="col-4 col-md-2 col-form-label">주소 3</label>
        <div class="col-8 col-md-10">
        <input type="text" class="form-control" name="h_address3" id="h_address3" placeholder="" aria-describedby="h_address3HelpBlock" v-model="h_address3" v-on:keyup.enter="searchHouse(1)">
          <small id="h_address3HelpBlock" class="form-text text-muted">
          [<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?>] <?=!empty($c_territory_type['type_1'][3])?$c_territory_type['type_1'][3]:'상세주소'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?>] <?=!empty($c_territory_type['type_2'][3])?$c_territory_type['type_2'][3]:'호'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?>] <?=!empty($c_territory_type['type_3'][3])?$c_territory_type['type_3'][3]:'호'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?>] <?=!empty($c_territory_type['type_4'][3])?$c_territory_type['type_4'][3]:'상세주소'?>을/를 입력해 주세요.<br>
          [편지] <?=!empty($c_territory_type['type_5'][3])?$c_territory_type['type_5'][3]:'상세주소'?>을/를 입력해 주세요.<br>

          [<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?>] <?=!empty($c_territory_type['type_7'][3])?$c_territory_type['type_7'][3]:''?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?>] <?=!empty($c_territory_type['type_8'][3])?$c_territory_type['type_8'][3]:''?>을/를 입력해 주세요.<br>
          </small>
        </div>
      </div>

      <div class="form-group row search-territory search-letter">
        <label for="h_address4" class="col-4 col-md-2 col-form-label">주소 4</label>
        <div class="col-8 col-md-10">
        <input type="text" class="form-control" name="h_address4" id="h_address4" placeholder="" aria-describedby="h_address4HelpBlock" v-model="h_address4" v-on:keyup.enter="searchHouse(1)">
          <small id="h_address4HelpBlock" class="form-text text-muted">
          [<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?>] <?=!empty($c_territory_type['type_1'][4])?$c_territory_type['type_1'][4]:'층'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?>] 해당되지 않습니다.<br>
          [<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?>] 해당되지 않습니다.<br>
          [<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?>] <?=!empty($c_territory_type['type_4'][4])?$c_territory_type['type_4'][4]:'층'?>을/를 입력해 주세요.<br>
          [편지] <?=!empty($c_territory_type['type_5'][4])?$c_territory_type['type_5'][4]:'우편번호'?>을/를 입력해 주세요.<br>

          [<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?>] <?=!empty($c_territory_type['type_7'][4])?$c_territory_type['type_7'][4]:''?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?>] 해당되지 않습니다.<br>
          </small>
        </div>
      </div>

      <div class="form-group row search-territory search-letter">
        <label for="h_address5" class="col-4 col-md-2 col-form-label">주소 5</label>
        <div class="col-8 col-md-10">
        <input type="text" class="form-control" name="h_address5" id="h_address5" placeholder="" aria-describedby="h_address5HelpBlock" v-model="h_address5" v-on:keyup.enter="searchHouse(1)">
          <small id="h_address5HelpBlock" class="form-text text-muted">
          [<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?>] <?=!empty($c_territory_type['type_1'][5])?$c_territory_type['type_1'][5]:'이름/호'?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?>] 해당되지 않습니다.<br>
          [<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?>] 해당되지 않습니다.<br>
          [<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?>] <?=!empty($c_territory_type['type_4'][5])?$c_territory_type['type_4'][5]:'이름/호'?>을/를 입력해 주세요.<br>
          [편지] <?=!empty($c_territory_type['type_5'][5])?$c_territory_type['type_5'][5]:'이름'?>을/를 입력해 주세요.<br>

          [<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?>] <?=!empty($c_territory_type['type_7'][5])?$c_territory_type['type_7'][5]:''?>을/를 입력해 주세요.<br>
          [<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?>] 해당되지 않습니다.<br>
          </small>
        </div>
      </div>

      <div class="form-group row search-telephone">
        <label for="tph_number" class="col-4 col-md-2 col-form-label">전화번호</label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="tph_number" id="tph_number" placeholder="- 를 제외하고 공백없이 입력해주세요." v-model="tph_number" v-on:keyup.enter="searchHouse(1)">
        </div>
      </div>

      <div class="form-group row search-telephone">
        <label for="tph_type" class="col-4 col-md-2 col-form-label"><?=!empty($c_territory_type['type_6'][2])?$c_territory_type['type_6'][2]:'업종'?></label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="tph_type" id="tph_type" placeholder="" v-model="tph_type" v-on:keyup.enter="searchHouse(1)">
        </div>
      </div>

      <div class="form-group row search-telephone">
        <label for="tph_name" class="col-4 col-md-2 col-form-label"><?=!empty($c_territory_type['type_6'][3])?$c_territory_type['type_6'][3]:'상호'?></label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="tph_name" id="tph_name" placeholder="" v-model="tph_name" v-on:keyup.enter="searchHouse(1)">
        </div>
      </div>

      <div class="form-group row search-telephone">
        <label for="tph_address" class="col-4 col-md-2 col-form-label">주소</label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="tph_address" id="tph_address" placeholder="" v-model="tph_address" v-on:keyup.enter="searchHouse(1)">
        </div>
      </div>

      <div class="form-group row">
        <label for="p_id" class="col-4 col-md-2 col-form-label">구역 ID</label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="p_id" id="p_id" v-model="p_id" v-on:keyup.enter="searchHouse(1)">
          <small class="text-muted">같은 유형의 구역 ID를 정확히 입력해 주세요</small>
        </div>
      </div>

      <div class="form-group row">
        <label for="h_id" class="col-4 col-md-2 col-form-label">세대 ID</label>
        <div class="col-8 col-md-10">
          <input type="text" class="form-control" name="h_id" id="h_id" v-model="h_id" v-on:keyup.enter="searchHouse(1)">
        </div>
      </div>

      <button type="button" class="btn btn-outline-secondary float-right" v-on:click="searchHouse(1)">
        <template v-if="search_spinner">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span class="sr-only">Loading...</span>
        </template>
        <template v-else>
          <i class="bi bi-search"></i> 검색
        </template>
      </button>

    </form>

    <form id="admin_house_assign" action="" method="post">
      <input type="hidden" name="work" value="assign">
      <input type="hidden" name="type" :value="type">
      <div style="overflow:hidden;" class="mb-4">
        <div class="row">
          <div class="col-12 mb-3">선택한 세대들을</div>
        </div>
        <div class="row">
          <div class="col-4 text-center border-right" style="line-height: 80px;">
            <button type="submit" onclick="javascript: jQuery('#admin_house_assign input[name=work]').val('check_delete');" class="btn btn-outline-danger" ><i class="bi bi-trash"></i> 삭제</button>
          </div>
          <div class="col-8">
            <div>
              <input type="text" class="form-control" name="id" placeholder="구역 ID">
            </div>
            <small class="text-muted">같은 유형의 구역 ID를 정확히 입력해주세요</small>
            <div class="mt-2">구역으로 <button type="submit" onclick="javascript: jQuery('#admin_house_assign input[name=work]').val('assign');" class="btn btn-outline-secondary">이동</button></div>
          </div>
        </div>
      </div>

      <section id="admin_house_list">
        <?php include_once('admin_house_list.php'); ?>
      </section>
      
    </form>

  </div>
</div>

<?php include_once('../footer.php');?>
