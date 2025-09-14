<%@ Page Language="C#" AutoEventWireup="true" ValidateRequest="false" %>

<script runat="server">

    //    commonfunc func = new commonfunc();

    private string req_board = "";
    private string req_recordidx = "";
    private string req_gubun = "";
    private string str_gubun = "";
    private string req_boardname = "";
    private string returnUrl = "";

    protected void Page_Load(object sender, EventArgs e)
    {
        req_board = Request["board"];
        req_recordidx = Request["recordIdx"];
        req_gubun = Request["gubun"];
        req_boardname = Request["boardname"];

        if (req_gubun == "contentfile" || req_gubun == "movfile")
        {
            str_gubun = "contentfile";
        }
        else
        {
            str_gubun = req_gubun;
        }

        if (Session["userid"] == null)
        {
            returnUrl = "/board/write_redactor.aspx?recordidx=" + req_recordidx + "&boardname=" + req_boardname;

            Response.Write("<script>alert('로그인후 이용하실수 있습니다.');opener.location.href='/join/login.aspx?returnUrl=" + Server.UrlEncode(returnUrl) + "';self.close();</" + "script>");
            Response.End();
        }
    }
    
</script>

<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

    <title>File Upload</title>

    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js" charset="UTF-8"></script>

    <script src="/board/dropzone/dropzone.js"></script>
    <link rel="stylesheet" href="/board/dropzone/dropzone.css">

</head>
<body style="font: 13px Verdana; background: #eee; color: #333">

    <h1>File Upload</h1>

    <ul>
        <li class="txt1"> 이미지(jpg, png)는 최대 <strong>5MB</strong>까지 업로드 가능합니다.</li>
        <li class="txt2"> 동영상(mp4)은 최대 <strong>1GB</strong>까지 업로드 가능합니다.</li>
        <li class="txt3"> 문서는 최대 <strong>1GB</strong>까지 업로드 가능합니다.</li>
        <li class="txt4"> 최대 <strong>1MB</strong>까지 업로드 가능합니다.</li>
        <li> 업로드된 파일은 현재창에서는 지울수 없고 <strong>리스트뷰에서 삭제할 수 있습니다.</strong></li>
        <li> 드래그앤드롭(drag&drop)은 <strong>브라우저 종류</strong>에 따라 가능하거나 지원이 안될 수도 있습니다.</li>
    </ul>
    <div id="dZUpload" class="dropzone">
        <div class="dz-default dz-message">
            Drop files here or click to upload.<br />
            <span class="note needsclick">(This is just a demo dropzone. Selected files are <strong>not</strong> actually uploaded.)</span> 
        </div>
    </div>

<section>

  <h1 id="tips">Tips</h1>

  <p>If you do not want the default message at all (»Drop files to upload (or click)«), you can
put an element inside your dropzone element with the class <code>dz-message</code> and dropzone
will not create the message for you.</p>
    
</section>

</body>
</html>

<script type="text/javascript">

    $(".txt1, .txt2, .txt3, .txt4").css("display", "none");

    var gubun = "<%=req_gubun%>";
    var maxfilesize = "";
    var filter = "";

    if (gubun == "contentfile") {
        maxfilesize = '5mb';
        filter = ".jpeg, .jpg, .png";
        $(".txt1").css("display", "");
    } else if (gubun == "movfile") {
        maxfilesize = '1024mb';
        filter = ".mp4, .flv";
        $(".txt2").css("display", "");
    } else if (gubun == "docfile") {
        maxfilesize = '1024mb';
        filter = null;
        $(".txt3").css("display", "");
    } else {
        maxfilesize = '1mb';
        filter = null;
        $(".txt4").css("display", "");
    }

    $(document).ready(function () {
        //console.log("Hello");
        Dropzone.autoDiscover = false;
        //Simple Dropzonejs 
        $("#dZUpload").dropzone({
            maxFilesize: maxfilesize,
            acceptedFiles: filter,
            url: "Handler1.ashx?recordidx=<%=req_recordidx%>&board=<%=req_board%>&gubun=<%=str_gubun%>",
            addRemoveLinks: true,
            success: function (file, response) {
                var imgName = response;
                file.previewElement.classList.add("dz-success");
                console.log("Successfully uploaded :" + imgName);

                //location.href = "modal_edit.aspx?table=" + table + "&recordidx=" + recordidx + "&recordno=" + recordno;
            },
            error: function (file, response) {
                file.previewElement.classList.add("dz-error");
            },
            complete: function (file) {
                //alert("ok");
                //this.removeFile(file);
            },
            queuecomplete: function () {
                self.close();
                opener.SetList('<%=str_gubun%>', '<%=req_recordidx%>');
            }
        });
    });
</script>


