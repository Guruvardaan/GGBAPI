<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title> GGB </title>
    <meta name="description" content="GGB Contact reply template">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
    <style type="text/css">
        a:hover {text-decoration: underline !important;}
    </style>
</head>
<style type="text/css">
img{
    position: absolute;
    top: 107px;
    left: 50%;
    transform: translateX(-50%);
}
</style>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #f2f3f8;" leftmargin="0">
    <!-- 100% body table -->
    <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8"
        style="@import: url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
        <tr>
            <td>
                <table style="background-color: #f2f3f8; max-width:670px; margin:0 auto;" width="100%" border="0"
                    align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                                style="max-width:670px; background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:0px 35px 0px;">
                                        <!-- <div class="icon">
                                            <i style="font-size: 60px; color: #0c0c0c;" class="fas fa-envelope-open-text"></i>
                                        </div> -->

                                        <h1 style="color:#ffba33; font-weight:500; margin:0;font-size:35px;padding-bottom: 10px; padding-top: 10px; font-family:'Rubik',sans-serif;margin-bottom: 35px;"><img src="{{url('/uploads')}}/logo/logo.2ccc897cb4764bcea549.png">
                                        </h1>

                                        <div class="ei-text">
                                            <h1 style="font-size: 14px; font-weight: 400; text-transform: capitalize; margin: 0px;text-align: start;">
                                                New inquery from {{$name}}
                                            </h1>

                                            <table>
                                                <tr>
                                                    <td>Name : </td>
                                                    <td>{{$name}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Mobile : </td>
                                                    <td>{{$phone}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Email : </td>
                                                    <td>{{$email}}</td>
                                                </tr>
                                                <tr>
                                                    <td>Category : </td>
                                                    <td>{{$category}}</td>
                                                </tr>
                                                @if(isset($sub_category))
                                                <tr>
                                                    <td>Sub Category : </td>
                                                    <td>{{$sub_category}}</td>
                                                </tr>
                                                @endif
                                                @if(isset($idcustomer_order))
                                                <tr>
                                                    <td>Order ID : </td>
                                                    <td>{{$idcustomer_order}}</td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td>Description : </td>
                                                    <td>{{$description}}</td>
                                                </tr>
                                            </table>
                                        </div>

                                        <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:17px;line-height: 1.5; margin-top:10px;font-family:'Rubik',sans-serif;text-align: start;display: none;">You're receiving this e-mail because someone is requesting for Expert Help.
                                        </h1>
                                     
                                        <div class="account_btn">
                                           
                                         <h5 style="font-size:17px;text-align: start;"></h5>
                                        </div>

                                        <p style="padding-bottom: 20px;font-size: 15px;width: 60%;font-weight: 600; margin: 50px auto 0px auto;line-height: 1.5;"></p>

                                        <span style="font-size: 15px;">Need more help?</span><br>
                                        <a href=""
                                             style="text-decoration:none !important; display:inline-block; font-weight:500;color:#df453e; font-size:14px;"><b> we are here, ready to talk </b>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:40px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:20px;">&nbsp;</td>
                    </tr>
                    <tr>
                        
                    </tr>
                    <tr>
                        <td style="height:80px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!--/100% body table-->
</body>

</html>