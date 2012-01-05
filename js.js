function add_text(str,ids){
  var tweet = document.getElementsByName("tweet")[0];
  var id = document.getElementsByName("id")[0];
  tweet.value += str;
  id.value += ids;
}

function checkCount(){
  var x = document.post.tweet.value.length;
  if (x > 140) {
    x = (x - 140) + '\u6587\u5b57\u30aa\u30fc\u30d0\u30fc\u3057\u3066\u3044\u307e\u3059\u305e\uff01';
  } else {
    x = x + '\u6587\u5b57';
  }
  document.getElementById("log").innerHTML = x;
  return false;
}

String.prototype.replaceAll = function (org, dest){
  return this.split(org).join(dest);
}

var request = null;
function makeRequest(user_id, count, sendtype) {
  if(typeof window.XMLHttpRequest != 'undefined') {
    try {
      request = new XMLHttpRequest();
    } catch(err) {
      request = null;
    }
  }
  request.onreadystatechange = function() {
    if (request.responseText == 'undefined') {
      message = '\u614c\u3066\u306a\u3044\u3067\uff01';
    } else {
      message = request.responseText;
    }
    document.getElementById(sendtype+count).innerHTML = (request.responseText);
  }
  request.open('POST', 'http://npsptter.dip.jp/sendlojax.php', true);
  request.setRequestHeader('Content-type', 'text/plain;charset=utf-8');
  request.send(sendtype + '=' + user_id);
}
