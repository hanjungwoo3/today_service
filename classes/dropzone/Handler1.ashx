<%@ WebHandler Language="C#" Class="dropzone_uploader.UploadHandler" %>

using System.Web.Script.Serialization;

using System.Data;
using System.Data.SqlClient;
using System.Configuration;

using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.IO;
using System.Drawing;
using System.Data.Odbc;

namespace dropzone_uploader
{
    /// <summary>
    /// Summary description for UploadHandler
    /// </summary>
    public class UploadHandler : IHttpHandler
    {

        public void ProcessRequest(HttpContext context)
        {
            context.Response.ContentType = "text/plain";

            string req_board = context.Request["board"];
            string req_recordidx = context.Request["recordidx"];
            string req_gubun = context.Request["gubun"];


            string dirFullPath = HttpContext.Current.Server.MapPath("/Ka3o_data/" + req_board + "/" + req_recordidx + "/");
            string[] files;
            int numFiles;
            files = System.IO.Directory.GetFiles(dirFullPath);
            numFiles = files.Length;
            numFiles = numFiles + 1;

            //string str_image = "";

            HttpPostedFile file;
            string fileName = "";
            string fileExtension;

            foreach (string s in context.Request.Files)
            {
                file = context.Request.Files[s];
                fileName = file.FileName;
                fileExtension = file.ContentType;

                if (!string.IsNullOrEmpty(fileName))
                {
                    fileName = checkFileExists(fileName, dirFullPath, "filename");

                    //fileExtension = Path.GetExtension(fileName);
                    //str_image = "MyPHOTO_" + numFiles.ToString() + fileExtension;
                    string pathToSave = dirFullPath + fileName;
                    file.SaveAs(pathToSave);
                }
            }
                       
           
            
            string cnString = ConfigurationManager.ConnectionStrings["dbconn_ka3o_mysql"].ConnectionString;
            OdbcConnection con = new OdbcConnection(cnString);
            con.Open();

            string sql = "insert into " + req_board + "s_file(recordIdx, gubun, filename, content, regdate) values('" + req_recordidx + "', '" + req_gubun + "', '" + fileName + "','',now())";
            OdbcCommand cmd = new OdbcCommand(sql, con);
            cmd.ExecuteNonQuery();

            con.Close();

            context.Response.Write(fileName); 
        }

        public bool IsReusable
        {
            get { return false; }
        }

        public string checkFileExists(string filename, string upload_path, string gubun)
        {
            filename = filename.Replace(" ", "_");
            filename = filename.Replace("&", "_");
            filename = filename.Replace("'", "");
            string file_ext = System.IO.Path.GetExtension(filename).Replace(".", "");
            string file_base = System.IO.Path.GetFileNameWithoutExtension(filename);
            string full_path = upload_path + filename;

            //avoid file override, check if file exists and generate another name
            //to override file with same name just disable this while
            int c = 0;
            while (System.IO.File.Exists(full_path))
            {
                c++;
                filename = file_base + "(" + c.ToString() + ")." + file_ext;
                full_path = upload_path + filename;
            }

            string returnfile = "";
            if (gubun == "filename") { returnfile = filename; }
            else if (gubun == "filefullname") { returnfile = full_path; }
            return returnfile;
        } 
        
    }
}