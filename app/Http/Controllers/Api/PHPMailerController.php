<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
 
use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\Exception;
use App\Models\OTP;


class PHPMailerController extends Controller
{
    public function sendOtp(Request $request) {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions

        try {

            // Email server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';          //  smtp host
            $mail->SMTPAuth = true;
            $mail->Username = 'amacoerp@gmail.com';   //  sender username
            $mail->Password = 'Amacoerp4729699';       // sender password
            $mail->SMTPSecure = 'tls';                  // encryption - ssl/tls
            $mail->Port = 587;                          // port - 587/465

            $mail->setFrom('amacoerp@gmail.com', 'Amaco ERP');
            $mail->addAddress($request -> email,'No-name');
            // $mail->addCC($request->emailCc);
            // $mail->addBCC($request->emailBcc);

            $mail->addReplyTo('amacoerp@gmail.com', 'Amaco ERP');

            // if(isset($_FILES['emailAttachments'])) {
            //     for ($i=0; $i < count($_FILES['emailAttachments']['tmp_name']); $i++) {
            //         $mail->addAttachment($_FILES['emailAttachments']['tmp_name'][$i], $_FILES['emailAttachments']['name'][$i]);
            //     }
            // }
            $otp = rand(111111,999999);

            $eOtp = bcrypt($otp);

            $mail->isHTML(true);                // Set email content format to HTML

            $mail->Subject = 'OTP for Forget Password from Amaco ERP';
            $mail->Body    = '<div style="font-family: Helvetica,Arial,sans-serif;min-width:1000px;overflow:auto;line-height:2">
  <div style="margin:50px auto;width:70%;padding:20px 0">
    <div style="border-bottom:1px solid #eee">
      <a href="" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600">Amaco</a>
    </div>
    <p style="font-size:1.1em">Hi,</p>
    <p>Thank you for choosing Amaco. Use the following OTP to complete your reset password procedures.</p>
    <h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">'.$otp.'</h2>
    <p style="font-size:0.9em;">Regards,<br />Amaco</p>
    <hr style="border:none;border-top:1px solid #eee" />
    <div style="float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300">
      <p>Amaco</p>
      <p> P.O Box 2579 Al-Jubail 31951,</p>
      <p>King Faisal Street (W)</p>
      <p>Kingdom of Saudi Arabia.</p>
    </div>
  </div>
</div>';

            // $mail->AltBody = plain text version of email body;
            $mail->Host = 'smtp.gmail.com'; 
            if( !$mail->send() ) {
                return response()->json([
                'status' => 500,
                'message' => 'Error',
            ]);

                // return back()->with("failed", "Email not sent.")->withErrors($mail->ErrorInfo);
            }
            
            else {
               return response()->json([
                'status' => 200,
                'message' => $eOtp,
            ]);

                // return back()->with("success", "Email has been sent.");
            }

        } catch (Exception $e) {
                return $e;
            //  return back()->with('error','Message could not be sent.');
        }
    }
}
