<?php
/* ---------------------------------------------------------
 * Eduwitter Lite
 * Lastest update: 2010-10-02
 * License: MIT or BSD
 * ------------------------------------------------------- */
if ($_FILES['image']) {
  require 'class.php';
  $oauthdata = new OAuthData();
  $data = $oauthdata->accountget();
  $consumer_key = Config::CONSUMER_KEY;
  $consumer_secret = Config::CONSUMER_SECRET;
  $oauth_token = $data['oauth_token'];
  $oauth_token_secret = $data['oauth_token_secret'];

  /**
   * custom space of Request
   */
  $url = 'http://api.twitter.com/1/account/update_profile_image.json';
  $method = 'POST';
  $post = array();
  $image_path = $_FILES['image']['tmp_name']; // path or null

  /* ------------------------------------------------------- */

  function params2Authorization($params) {
    $parts = array();
    foreach ($params as $k => $v) {
      $parts[] = "{$k}=\"{$v}\"";
    }
    return implode(', ', $parts);
  }

// rawurlencode post datas(PHP >= 5.3.0)
  array_walk($post, function (&$str) {
            $str = rawurlencode($str);
          });

  /**
   * build parameters for signature and query string
   */
  $params = array(
      'oauth_consumer_key' => $consumer_key,
      'oauth_signature_method' => 'HMAC-SHA1',
      'oauth_timestamp' => time(),
      'oauth_nonce' => md5(Config::SALT . microtime() . mt_rand()),
      'oauth_version' => '1.0a',
      'oauth_token' => $oauth_token,
  );
  $params = array_merge($params, $post);
  ksort($params);

  /**
   * create Signature: oauth 1.0a reference#9
   */
  $q = rawurldecode(http_build_query($params));
  $k = $consumer_secret . '&' . $oauth_token_secret;
  $bs = $method . '&' . rawurlencode($url) . '&' . rawurlencode($q);
  $params['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $bs, $k, true)));

  /**
   * build HTTP Header-Body field
   */
  $query_string = rawurldecode(http_build_query($params));

  $pu = parse_url($url);

  $headers = array(
      "{$method} {$pu['path']} HTTP/1.1",
      "Host: {$pu['host']}",
      "Expect:"
  );

  if (isset($image_path)) {
    $boundary = "--poochin_boundary";
    $body_field = "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($image_path) . "\"\r\n"
            . "Content-Type: image/jpeg\r\n" // TODO: Mime-type
            . "\r\n"
            . file_get_contents($image_path) . "\r\n"
            . "--{$boundary}--";
    $headers[] = "Authorization: OAuth realm=\"{$pu['scheme']}://{$pu['host']}/\", " . params2Authorization($params);
    $headers[] = "Content-Type: multipart/form-data; boundary={$boundary}";
    $headers[] = "Content-Length: " . strlen($body_field);
  } else if ($method == 'GET') {
    $headers[] = "Authorization: OAuth realm=\"{$pu['scheme']}://{$pu['host']}/\", " . params2Authorization($params);
  } else {
    $body_field = $query_string;
    $headers[] = "Content-Length: " . strlen($body_field);
  }

  $header_field = implode("\r\n", $headers) . "\r\n";

  /**
   * start to send and recieve
   */
  $port = isset($pu['port']) ? $pu['port'] : 80;

// if use SSL, change $port to 443 and exchange this line to
// $fp = fsockopen('tls://' . $pu['host'], $port);
  $fp = fsockopen($pu['host'], $port);
  if (!$fp) {
    die("Can not open socket\n");
  }

  fwrite($fp, $header_field);
  fwrite($fp, "\r\n");
  fwrite($fp, (isset($body_field) ? $body_field : ""));

  $response = fread($fp, 120);
  $pattern = '/HTTP\/1.1\s([0-9]{3})/';
  preg_match($pattern, $response, $matches);
  fclose($fp);
  $http_status = $matches[1];
}
?>

<?php if ($http_status == 200) { ?>
  成功しました。 <a href="http://psptter.dip.jp/">戻る</a>
<?php } else { ?>
  失敗しました。 error code:<?php echo $http_status ?>　<a href="http://psptter.dip.jp/">戻る</a>
<?php } ?>
