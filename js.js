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
  } else {
    x = '\u3000';
  }
  document.getElementById("strcount").innerHTML = x;
}

//これ何か忘れた(；´∀｀) おそらくLojax？
String.prototype.replaceAll = function (org, dest){
  return this.split(org).join(dest);
}