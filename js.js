function add_text(str,ids){
  document.getElementsByName("tweet")[0].value += decodeURIComponent(str);
  document.getElementsByName("id")[0].value += ids;
}

function strCount(){
  var x = document.post.tweet.value.length;
  
  if (x > 140) {
    x = (x - 140) + '\u6587\u5b57\u30aa\u30fc\u30d0\u30fc\u3057\u3066\u3044\u307e\u3059\u305e\uff01';
  } else {
    x = x + '\u6587\u5b57';
  }
  document.getElementById("strcount").innerHTML = x;
}