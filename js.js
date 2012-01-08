function add_text(str,ids){
  var tweet = document.getElementsByName("tweet")[0];
  var id = document.getElementsByName("id")[0];
  tweet.value += str;
  id.value += ids;
}

function strCount(){
  var x = document.post.tweet.value.length;
  if (x > 140) {
    x = (x - 140) + '\u6587\u5b57\u30aa\u30fc\u30d0\u30fc\u3057\u3066\u3044\u307e\u3059\u305e\uff01';
    document.getElementById("strcount").innerHTML = x;
    return false;
  }
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
    if (request.responseText != 'undefined') {
      document.getElementById(count).innerHTML=(request.responseText);
      document.getElementById('link'+count).innerHTML=('');
    } else {
      document.getElementById(count).innerHTML=('\u614c\u3066\u306a\u3044\u3067\uff01');
    }
  }
  request.open('POST', 'http://npsptter.dip.jp/sendlojax.php', true);
  request.setRequestHeader('Content-type', 'text/plain;charset=utf-8');
  request.send(sendtype + '=' + user_id);
}
