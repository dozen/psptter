<?php
/* * *****************************************************************************
  LJX1.0 :: LoJAX (Low-Technology AJAX)
  ------------------------------------------------------------------------------
  Copyright (c) 2006 James Edwards (brothercake)          <cake@brothercake.com>
  BSD License                          See license.txt for licensing information
  Info/Docs             http://www.brothercake.com/site/resources/scripts/lojax/
  ------------------------------------------------------------------------------
 * ***************************************************************************** */

//make an http request for a URI, validated by origin host
//this was originally based on the function at http://de3.php.net/manual/en/function.get-headers.php#64073
//snoopy was also very helpful http://sourceforge.net/projects/snoopy/
function lojax_request($uri, $method, $postdata, $auth) {
  //define a response array
  $response = array('Body' => '', 'Errors' => '');

  //if the uri is a local path and not a uri
  if (!preg_match('/^[a-z]+\:\/\//', $uri)) {
    //get the parts for the current script uri
    $parts = parse_url($_SERVER['SCRIPT_URI']);

    //change the host to this host
    $parts['host'] = $_SERVER['HTTP_HOST'];

    //remove this script name from the path
    $parts['path'] = str_replace(basename($_SERVER['PHP_SELF']), '', $parts['path']);

    //compile the complete uri, including the path only if
    //it's a relative path rather than a web-root
    $uri = $parts['scheme'] . '://'
            . $parts['host']
            . (substr($uri, 0, 1) == '/' ? '' : $parts['path'])
            . $uri;
  }

  //split the request uri into parts
  $parts = parse_url($uri);

  //host validation is to prevent the script being used as a proxy
  //by spammers, hackers and script kiddies
  //so, if the host is not this host,
  if ($parts['host'] != $_SERVER['HTTP_HOST']) {
    //by default this request is not allowed
    $allowed = false;

    //if we have a hosts file in this directory
    if (@include_once('lojax-hosts.php')) {
      //if the request host is contained in the hosts array
      //then this request is allowed
      if (isset($lojax_hosts) && in_array($parts['host'], $lojax_hosts)) {
        $allowed = true;
      }
    }

    //if the request is not allowed
    if (!$allowed) {
      //define an error in the response status
      //and an explanation in the response errors array
      //(for client-side throw()), then return the reponse
      $response['Status'] = 'HTTP/1.0 466 Host Not Allowed';
      $response['Errors'] = '[LoJAX] Permission denied to call open() with URI ' . $uri;
      return $response;
    }
  }

  //if the protocol is unsupported,
  //define an error in the response status
  //and an explanation in the response errors array
  //(for client-side throw()), then return the reponse
  if (!preg_match('/^(http)$/', $parts['scheme'])) {
    $response['Status'] = 'HTTP/1.0 467 Unsupported Protocol';
    $response['Errors'] = '[LoJAX] Unsupported protocol ' . $parts['scheme'] . '://';
    return $response;
  }

  //if we're still going, we're good to make the request
  //so try to open a socket at port 80
  if (@$fp = fsockopen($parts['host'], 80, $errno, $errstr, 10)) {
    //set stream timeout
    stream_set_timeout($fp, 10);

    //compile input headers for the request
    //use HTTP 1.0 so we don't get a chunked response
    //** because I don't know how to deal with that
    $headers = "$method $uri HTTP/1.0\r\n"
            //if we're sending postdata, we must also send a content-length header
            . ($postdata != '' ? "Content-Length: " . strlen($postdata) . "\r\n" : "")
            //add encoded authorisation information if used
            . ($auth != '' ? "Authorization: Basic " . base64_encode($auth) . "\r\n" : "")
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            //we can only deal with the body of a text-based response
            //but we can retrieve the headers of any file
            //so I'm gonna leave it up to developers to think about what they're requesting
            //and just prefer text-based formats, but allow anything
            . "Accept: *; q=0.5, text/*, application/x-javascript\r\n"
            //we can only accept a non-encoded response
            . "Accept-Encoding: identity\r\n"
            . (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? "Accept-Language: " . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\r\n" : "")
            . "Connection: close\r\n"
            . (isset($_SERVER['HTTP_COOKIE']) ? "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n" : "")
            . "Host: " . $parts['host'] . "\r\n"
            . (isset($_SERVER['HTTP_REFERER']) ? "Referer: " . $_SERVER['HTTP_REFERER'] . "\r\n" : "")
            . (isset($_SERVER['HTTP_USER_AGENT']) ? "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . " LoJAX/1.0\r\n" : "User-Agent: LoJAX/1.0\r\n")
            //a double line-break signifies the end of the headers
            . "\r\n";

    //send the headers
    fputs($fp, $headers);

    //if we have postdata, and the method is POST, send the data
    if ($postdata != '' && $method == 'POST') {
      fputs($fp, $postdata);
    }


    //get the response output
    $got_headers = false;
    while (!feof($fp)) {
      if ($line = fgets($fp, 1024)) {
        //if the line is just an empty line
        //we've got all the headers
        if ($line == "\r\n") {
          $got_headers = true;
        }

        //the first line is the status message
        if (!isset($response['Status'])) {
          $response['Status'] = trim($line);
        }

        //or if we haven't got all the headers
        elseif (!$got_headers) {
          //trim and split this line by header delimiter (colon space)
          //and add it's data to array indexed by header name
          //so we get members like "Date" => "Thu, 18 May 2006 15:17:06 GMT"
          $tmp = explode(': ', trim($line));
          $response[$tmp[0]] = $tmp[1];
        }

        //or if we have got all the headers and this is not the empty line
        //add the line to response body, including its whitespace
        elseif ($line != "\r\n") {
          $response['Body'] .= $line;
        }
      }
    }

    //close the connection
    fclose($fp);

    //return the response
    return $response;
  }

  //if we fail to open a socket
  //return false for failure
  else {
    return false;
  }
}

//if we have a lojax_uri parameter in the POST data
//copy it to GET data and delete it from POST
//this was originally for convenience so it's always in the same collection
//but in fact it's necessary so that it's possible to pass GET and POST data in the same request
if (isset($_POST['lojax_uri'])) {
  $_GET['lojax_uri'] = $_POST['lojax_uri'];
  unset($_POST['lojax_uri']);
}

//copy any lojax_auth parameter in the same way
//in this case, just for convenience
if (isset($_POST['lojax_auth'])) {
  $_GET['lojax_auth'] = $_POST['lojax_auth'];
  unset($_POST['lojax_auth']);
}


//if we have a lojax_uri parameter in the GET data (a file URI)
if (isset($_GET['lojax_uri'])) {
  //if we have a lojax_data parameter (POST data in the request)
  //store the data to postdata var and then delete it
  if (isset($_POST['lojax_data'])) {
    $postdata = $_POST['lojax_data'];
    unset($_POST['lojax_data']);
  }
  //otherwise store an empty string
  else {
    $postdata = '';
  }

  //request the file and store the response
  if ($response = lojax_request($_GET['lojax_uri'], $_SERVER['REQUEST_METHOD'], $postdata, $_GET['lojax_auth'])) {
    //count the number of redirects we go through
    //so we can limit it to prevent excessive or infinite recursion
    $redirects = 0;

    //while the status code is 301, 302, 303 or 307 (non-proxy redirect)
    //** don't support proxy redirect (305) because I don't know how to deal with it
    while (preg_match('/( 30[1237] )/u', $response['Status'])) {
      //add to redirects iterations, and if it goes over 5, break
      //the end result of breaking when finding a recursive redirect will be a 302 (Found) status code
      //with headers for the file it found, but no response body
      //(because there isn't any - it's effectively stopped at the redirect script's headers)
      if (++$redirects > 5) {
        break;
      }

      //get the new URI from the Location header and iterate
      //changing the method to GET because we shouldn't re-POST in response to a redirect
      //however the HTTP RFC says that auto-converting to GET is wrong for 301 and 302
      //(and that's why 303 exists), but I still reckon it's the best thing to do here
      //it maintains security while being a good deal simpler to implement
      //than handling each redirect type differently and having a user-input stage to reconfirm a POST
      $_GET['lojax_uri'] = $response['Location'];
      $response = lojax_request($_GET['lojax_uri'], 'GET', '', $_GET['lojax_auth']);
    }

    //if we have a response (it didn't return false)
    if ($response) {
      //if we don't have any errors
      if ($response['Errors'] == '') {
        //compile the headers var
        //a single string delimited with line breaks
        $headers = '';
        foreach ($response as $key => $data) {
          //don't include the body of the response (because it's not a header)
          //and don't include status either, because there isn't really a header called "Status"
          //I just stored it with that name for convenience
          //(that information is available elswhere, from the status and statusText properties
          //so it's no loss to remove it from the general headers information,
          //and it's consistent with native implementations)
          //don't include the errors either, which are custom messages just for this program
          if (!preg_match('/^(Body|Status|Errors)$/', $key)) {
            $headers .= $key . ': ' . $data . "\n";
          }
        }
        $headers = trim($headers);

        //if the status is not 304
        //store the response body to output var
        if (!strstr($response['Status'], ' 304 ')) {
          $output = $response['Body'];
        }
      }

      //if we do have errors, store the errors to output var
      else {
        $output = $response['Errors'];
      }

      //store the status value with + for space in description
      //so that the javascript can split its key parts using space delimiter
      $status = str_replace(' ', '+', $response['Status']);
      $status = preg_replace('/[\\\+][0-9]{3}[\\\+]/u', ' \\1 ', $status);
    }
  }
}

//set the content-type of this page to text/html
//otherwise it won't work in Opera 7 (which doesn't support scripting in XHTML mode)
//and we must use utf-8 encoding for compatibility with native AJAX
//(in which all requests are utf-8)
header('Content-Type: text/html; charset=utf-8');

//don't allow this page to be cached
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
  <head>
    <title>LoJAX [courier page]</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  </head>
  <body>
    <script type="text/javascript">

      //respond function sends the reponse data
      //back up to a form in the calling document
      function respond()
      {
        //if we have a sender form in this document
        var sender = document.getElementById('lojax_sender');
        if(sender)
        {
          //if we have a receiver form in the parent
          var receiver = parent.document.getElementById('lojax_form');
          if(receiver)
          {
            //write the response value into the data area
            //then clear the value from this one
            receiver['lojax_data'].value = sender['lojax_response'].value;
            sender['lojax_response'].value = '';

            //write the headers data into the headers field
            //then clear the value from this one
            receiver['lojax_headers'].value = sender['lojax_headers'].value;
            sender['lojax_headers'].value = '';

            //write the status value into the status field
            //then clear the value from this one
            //do this last, because its value is what the host page is watching
            //and we don't want that to respond until all its data is there
            receiver['lojax_status'].value = sender['lojax_status'].value;
            sender['lojax_status'].value = '';
          }
        }
      };

      //respond onload
      window.onload = respond;

    </script>
    <?php
//expose the transfer mechanism
    $expose = false;

//compile the form HTML -- rows and cols must be at least "1" or it doesn't work properly in Opera 6
    $attrs = ($expose ? 'rows="10" cols="30"' : 'rows="1" cols="1" disabled="disabled"');
    $type = ($expose ? 'type="text"' : 'type="hidden"');
    $html = '<form action="" id="lojax_sender"><fieldset>'
            . '<textarea id="lojax_response" ' . $attrs . '>' . (isset($output) ? htmlentities($output) : '') . '</textarea>'
            . '<textarea id="lojax_headers" ' . $attrs . '>' . (isset($headers) ? htmlentities($headers) : '') . '</textarea>'
            . '<input id="lojax_status" ' . $type . ' value="' . (isset($status) ? htmlentities($status) : '') . '" />'
            . '</fieldset></form>';

//output the form
    echo $html;
    ?>

  </body>
</html>
