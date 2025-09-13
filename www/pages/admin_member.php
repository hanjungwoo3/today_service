<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">전도인 관리</span></h1>
  <?php echo header_menu('admin','전도인 관리'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="v_admin_member">

  <div id="container" class="container-fluid">

    <?php if(get_member_auth(mb_id()) == 1):?>
    <div class="mb-3">
      <div class="btn-group" role="group" aria-label="엑셀 다운로드 & 업로드">
        <button type="button" class="btn btn-outline-info" onclick="location.href='<?=BASE_PATH?>/include/member_excel_download.php'"><i class="bi bi-download"></i> Excel</button>
        <button type="button" class="btn btn-outline-info" onclick="member_excelupload_modal();"><i class="bi bi-upload"></i> Excel</button>
      </div>
      <button onclick="location.href='<?=BASE_PATH?>/pages/admin_member_form.php'" class="btn btn-outline-primary float-right" >
        <i class="bi bi-plus-circle-dotted"></i> 추가
      </button>
    </div>
    <?php endif;?>

    <form id="admin-member-search-form" method="post" class="mb-3" @submit.prevent="searchMember">
      <div class="input-group mb-2">
        <div class="input-group-prepend">
          <div class="input-group-text">
            <label class="mb-0"><input type="checkbox" name="moveout" class="mr-1" v-model="moveout">전출포함</label>
          </div>
        </div>
        <input type="text" class="form-control" name="name" placeholder="전도인 이름" aria-label="전도인 이름" aria-describedby="button-addon2" v-model="name">
        <div class="input-group-append">
          <button type="button" class="btn btn-outline-secondary" v-on:click="searchMember()">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </div>
    </form>

    <section id="admin-member-list" class="no-padding">
      <?php include_once('admin_member_list.php'); ?>
    </section>

  </div>

</div>

<?php include_once('../footer.php');?>
