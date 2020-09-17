<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

//Load Composer's autoloader
require '../vendor/autoload.php';

function json_response($message = null, $code = 200)
{
    // clear the old headers
    header_remove();
    // set the actual code
    http_response_code($code);
    // set the header to make sure cache is forced
    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
    // treat this as json
    header('Content-Type: application/json');
    $status = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
        );
    // ok, validation error, or failure
    header('Status: '.$status[$code]);
    // return the encoded json
    return json_encode(array(
        'status' => $code < 300, // success or not?
        'message' => $message
        ));
}


$log = new Logger('logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/../log/notifications.log', Logger::WARNING));

$name = isset($_POST['name']) ? $_POST['name'] : '';
$title = isset($_POST['email']) ? $_POST['email'] : '';
$email = isset($_POST['title']) ? $_POST['title'] : '';
$comment = isset($_POST['comment']) ? $_POST['comment'] : '';

if (isset($_POST['checkspam']) && !empty($_POST['checkspam'])) {
    echo json_response('Message marked as spam!', 400);
    $log->warning('Message from ' . $_POST['email'] . 'marked as spam!');
    die();
}

$dotenv = new Dotenv\Dotenv(realpath(__DIR__ . '/..'));
$dotenv->load();

$messageHtml = '<b>Message from:</b><br>'
    . '<b>Name: </b>' . $name . '<br>'
    . '<b>Title: </b>' . $title . '<br>'
    . '<b>Email: </b>' . $email . '<br>'
    . '<b>Comment: </b>' . $comment . '<br>';

$messageAlt = 'Name: ' . $name . ', Title: ' . $title . ', Email: ' . $email . ', Comment: ' . $comment;



$mail = new PHPMailer(true);



try {
    //Server settings
    //Recipients
    $mail->setFrom(getenv('FROM'));
    $mail->addAddress(getenv('TO'));     // Add a recipient
    //$mail->addAddress('ellen@example.com');               // Name is optional
    $mail->addReplyTo(getenv('REPLY_TO'));
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'New Message from ' . $name;
    $mail->Body    = $messageHtml;
    $mail->AltBody = $messageAlt;

    //$mail->send();
    
    echo json_response('Message sent!', 200);

} catch (Exception $e) {
    echo json_response('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo, 500);
    $log->error('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    $log->error($e->getMessage());
}
