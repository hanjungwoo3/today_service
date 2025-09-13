<?
$req_map_idx = $_REQUEST["map_idx"];
$req_map_service=$_REQUEST["map_service"];
$req_map_sub_idx = $_REQUEST["map_sub_idx"];
?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<title>엑셀파일 업로드</title>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js" charset="UTF-8"></script>

<script src="dropzone.js"></script>
<link rel="stylesheet" href="dropzone.css">
<style type="text/css">
#load{
	display: none;
	position: absolute;
	background: url(/img/loader.gif) no-repeat center;
	width:64px;
	height:64px;
	text-indent: -9999em;
}

</style>
</head>
<body style="font: 13px Verdana; background: #eee; color: #333">

    <h1>엑셀파일 업로드</h1>

    <ul>
        <li> 엑셀파일(xlsx, xls)는 최대 <strong>2MB</strong>까지 1개의 파일만 업로드 가능합니다.</li>
        <li> 드래그앤드롭(drag&drop)은 <strong>브라우저 종류</strong>에 따라 가능하거나 지원이 안될 수도 있습니다.</li>
    </ul>
    <div id="dZUpload" class="dropzone">
        <div class="dz-default dz-message">
            이미지 파일을 이곳으로 <strong>드래그 드롭</strong>하거나,
			이곳을 <strong>클릭</strong>하세요.
        </div>
    </div>

</body>
</html>

<script type="text/javascript">


    $(document).ready(function () {

		var tt_id="<?=$tt_id?>";

        console.log("dropzone start");
        Dropzone.autoDiscover = false;
        //Simple Dropzonejs
        $("#dZUpload").dropzone({
            maxFilesize: "2",
						maxFiles: "1",
            acceptedFiles: ".xlsx, .xls",
            url: "Handler1.php?idx=<?=$req_idx?>",
            addRemoveLinks: true,
            success: function (file, response) {
                var filename = response;
                file.previewElement.classList.add("dz-success");
                console.log("Successfully uploaded :" + filename);
								excel_file_reader(tt_id, filename);
	//				location.href="../map_normal_list_excel_reader.php?map_idx="+map_idx+"&map_sub_idx="+map_sub_idx+"&filename="+filename;
            },
            error: function (file, response) {
                file.previewElement.classList.add("dz-error");
            },
            complete: function (file) {
//                alert(file);
                //this.removeFile(file);
            },
            queuecomplete: function () {
//                alert("ok");
//                self.close();
            }
        });


		function excel_file_reader(tt_id, filename){

			$.ajax({
				type: "post",
				url: "../../include/territory_excel_upload.php",
				data: "tt_id="+tt_id+"&filename="+filename,
				beforeSend: function() {
					var h=$(document).height()+$(document).scrollTop();
					var hh=(h-64)/2;

					var w=$(document).width();
					var ww=(w-64)/2;

					$('#load').remove();
					$('#load').remove();
					$('body').append('<span id="load">LOADING...</span>');
					$("#load").css("top",hh);
					$("#load").css("left",ww);
					$('#load').fadeIn('normal');
				},
				success: function (msg) {
					alert("완료");
					opener.location.href="../../map_normal_list.html?map_idx="+map_idx+"&map_service="+map_service+"&map_sub_idx="+map_sub_idx;
					self.close();
				},
				error: function (request, status, error) {
					//alert(status + ' : ' + error);
				}
			}).done(function( data ) {
				$('#load').fadeOut('normal');
			});

		}
    });


</script>
