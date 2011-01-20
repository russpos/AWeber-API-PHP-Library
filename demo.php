<?php
/*
Demo application
Requires an application with subscriber access. Can be used as a functional test
or a beginners guide for the PHP API library.

To start, place the keys of your application in a file named data.json in JSON format.
Example:

{
    "key" : "xxxxxxxxxxxxxxxxxxxxxx",
    "secret" : "xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}

NEVER SHARE OR DISTRIBUTE YOUR APPLICATIONS'S KEYS!
 */
require_once('aweber_api/aweber_api.php');
$data = json_decode(file_get_contents('data.json'));
$consumerKey    = $data->key;
$consumerSecret = $data->secret;
$aweber = new AWeberAPI($consumerKey, $consumerSecret);

if (empty($_COOKIE['accessToken'])) {
    if (empty($_GET['oauth_token'])) {
        $callbackUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        list($requestToken, $requestTokenSecret) = $aweber->getRequestToken($callbackUrl);
        setcookie('requestTokenSecret', $requestTokenSecret);
        setcookie('callbackUrl', $callbackUrl);
        header("Location: {$aweber->getAuthorizeUrl()}");
        exit();
    }

    $aweber->user->tokenSecret = $_COOKIE['requestTokenSecret'];
    $aweber->user->requestToken = $_GET['oauth_token'];
    $aweber->user->verifier = $_GET['oauth_verifier'];
    list($accessToken, $accessTokenSecret) = $aweber->getAccessToken();
    setcookie('accessToken', $accessToken);
    setcookie('accessTokenSecret', $accessTokenSecret);
    header('Location: '.$_COOKIE['callbackUrl']);
    exit();
}
$account = $aweber->getAccount($_COOKIE['accessToken'], $_COOKIE['accessTokenSecret']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>AWeber Test Application</title>
  <link type="text/css" rel="stylesheet" href="styles.css" />
<body>
<?php
foreach($account->lists as $offset => $list) {
?>
<h1>List: <?php echo $list->name; ?></h1>
<h3><?php echo $list->id; ?></h3>
<table>
  <tr>
    <th class="stat">Id</th>
    <th class="value">Name</th>
    <th class="value">Email</th>
    <th class="value">Status</th>
  </tr>
<?php
    $count = 0;
    foreach($list->subscribers as $sub) {
        if ($count++ > 10) break;
?>
    <tr>
        <td class="stat"><em><?php echo $sub->id;  ?></em></td>
        <td class="value"><?php echo $sub->name; ?></td>
        <td class="value"><?php echo $sub->email; ?></td>
        <td class="value"><?php echo $sub->status; ?></td>
    </tr>
<?php
} ?>
</table>
<?php }
?>
<body>
</html>

