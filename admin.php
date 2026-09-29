<?php
session_start();
define('ADMIN_PASSWORD', 'ChangeMe123'); // <-- apna password yahan badlein
$f = __DIR__ . '/rates.json';
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (isset($_POST['password'])) {
  if (hash_equals(ADMIN_PASSWORD, $_POST['password'])) {
    session_regenerate_id(true);
    $_SESSION['ok'] = 1; $_SESSION['t'] = bin2hex(random_bytes(16));
    header('Location: admin.php'); exit;
  }
  $err = 'Wrong password';
}
if (empty($_SESSION['ok'])) { ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin Login</title>
<style>body{font-family:system-ui,sans-serif;background:#eef3f8;display:grid;place-items:center;min-height:100vh;margin:0}form{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px #0002;width:min(320px,90vw)}input,button{width:100%;padding:11px;margin-top:10px;font-size:15px;border-radius:8px;border:1px solid #ccd}button{background:#0b2a55;color:#fff;border:0;font-weight:700;cursor:pointer}p{color:#b02020;margin:8px 0 0}</style></head>
<body><form method="post"><h2 style="margin:0;color:#0b2a55">MH Solar Admin</h2><input type="password" name="password" placeholder="Password" autofocus required><?php if (!empty($err)) echo "<p>$err</p>"; ?><button>Login</button></form></body></html>
<?php exit; }

if (isset($_GET['save'])) {
  header('Content-Type: application/json');
  if (!hash_equals($_SESSION['t'], $_SERVER['HTTP_X_TOKEN'] ?? '')) { http_response_code(403); echo '{"ok":false}'; exit; }
  $d = json_decode(file_get_contents('php://input'), true);
  if (!is_array($d) || !isset($d['categories']) || !is_array($d['categories'])) { http_response_code(400); echo '{"ok":false}'; exit; }
  date_default_timezone_set('Asia/Karachi');
  $d['updated'] = date('c');
  $ok = file_put_contents($f, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
  echo json_encode(['ok' => $ok !== false]); exit;
}
$data = json_encode(json_decode(file_get_contents($f)), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>MH Solar Admin</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,sans-serif;background:#eef3f8;margin:0;padding:0 0 80px;color:#1b2733}
.top{position:sticky;top:0;background:#0b2a55;color:#fff;padding:10px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;z-index:5}
.top b{flex:1}.top a{color:#fff;font-size:13px}
button{border:0;border-radius:8px;padding:9px 14px;font-weight:700;cursor:pointer;font-size:13px;background:#1c5bb8;color:#fff}
button.g{background:#1a7a3c}button.r{background:#c0392b}button.l{background:#dfe6ee;color:#123}
main{max-width:980px;margin:0 auto;padding:12px}
.cat{background:#fff;border-radius:12px;padding:12px;margin-bottom:14px;box-shadow:0 2px 8px #0b2a5515}
.cat h3{margin:0 0 8px;color:#0b2a55}
input,select{padding:8px;border:1px solid #ccd5e0;border-radius:6px;font-size:14px;width:100%}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
label{font-size:11px;color:#567;display:block;margin-bottom:2px}
.rows{overflow-x:auto}
table{border-collapse:collapse;min-width:640px;width:100%}th{font-size:11px;text-align:left;color:#567;padding:4px}td{padding:3px}
#msg{margin-left:8px;font-size:13px}
@media(max-width:600px){.g2{grid-template-columns:1fr}}
</style></head><body>
<div class="top"><b>MH Solar Admin</b><a href="index.html" target="_blank">View website</a><a href="?logout">Logout</a><button class="g" id="save">Save &amp; Publish</button><span id="msg"></span></div>
<main id="app"></main>
<script>
var D=<?= $data ?>, T="<?= $_SESSION['t'] ?>";
function e(s){return String(s==null?"":s).replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;")}
function render(){
  var h="";
  D.categories.forEach(function(c,ci){
    h+='<div class="cat"><h3>Category '+(ci+1)+'</h3><div class="g2">'+
    '<div><label>Category name</label><input data-c="'+ci+'" data-k="title" value="'+e(c.title)+'"></div>'+
    '<div><label>Subtitle</label><input data-c="'+ci+'" data-k="sub" value="'+e(c.sub)+'"></div>'+
    '<div><label>Icon (emoji / symbol)</label><input data-c="'+ci+'" data-k="icon" value="'+e(c.icon)+'"></div>'+
    '<div><label>Extra columns (comma separated)</label><input data-c="'+ci+'" data-k="headers" value="'+e((c.headers||[]).join(", "))+'"></div></div>'+
    '<div class="rows"><table><tr><th>Brand</th><th>Model</th>'+(c.headers||[]).map(function(x){return '<th>'+e(x)+'</th>'}).join("")+'<th>Price (PKR)</th><th>Status</th><th></th></tr>';
    c.rows.forEach(function(r,ri){
      var a='data-c="'+ci+'" data-r="'+ri+'"';
      h+='<tr><td><input '+a+' data-k="brand" value="'+e(r.brand)+'"></td><td><input '+a+' data-k="model" value="'+e(r.model)+'"></td>'+
      (c.headers||[]).map(function(_,i){return '<td><input '+a+' data-k="cell" data-i="'+i+'" value="'+e((r.cells||[])[i])+'"></td>'}).join("")+
      '<td><input type="number" '+a+' data-k="price" value="'+e(r.price)+'"></td>'+
      '<td><select '+a+' data-k="status"><option'+(r.status=="In Stock"?" selected":"")+'>In Stock</option><option'+(r.status=="In Stock"?"":" selected")+'>Out of Stock</option></select></td>'+
      '<td><button class="r" data-do="delrow" data-c="'+ci+'" data-r="'+ri+'">Remove</button></td></tr>';
    });
    h+='</table></div><div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap"><button class="l" data-do="addrow" data-c="'+ci+'">+ Add item</button>'+
    '<button class="l" data-do="up" data-c="'+ci+'">↑ Move up</button><button class="l" data-do="down" data-c="'+ci+'">↓ Move down</button>'+
    '<button class="r" data-do="delcat" data-c="'+ci+'">Delete category</button></div></div>';
  });
  h+='<button data-do="addcat">+ Add new category</button>';
  document.getElementById("app").innerHTML=h;
}
document.addEventListener("change",function(ev){
  var t=ev.target,k=t.dataset.k; if(!k)return;
  var C=D.categories[+t.dataset.c];
  if(t.dataset.r==null){
    if(k=="headers"){C.headers=t.value.split(",").map(function(s){return s.trim()}).filter(Boolean);
      C.rows.forEach(function(r){r.cells=C.headers.map(function(_,i){return (r.cells||[])[i]||""})});render();}
    else C[k]=t.value;
    return;
  }
  var R=C.rows[+t.dataset.r];
  if(k=="cell")R.cells[+t.dataset.i]=t.value;
  else if(k=="price")R.price=Number(t.value)||0;
  else R[k]=t.value;
});
document.addEventListener("click",function(ev){
  var t=ev.target,a=t.dataset.do; if(!a)return;
  var ci=+t.dataset.c,C=D.categories[ci];
  if(a=="addrow")C.rows.push({brand:"",model:"",cells:(C.headers||[]).map(function(){return ""}),price:0,status:"In Stock"});
  if(a=="delrow"&&confirm("Is item ko remove karein?"))C.rows.splice(+t.dataset.r,1);
  if(a=="delcat"&&confirm("Poori category delete karein?"))D.categories.splice(ci,1);
  if(a=="addcat")D.categories.push({id:"cat"+Date.now(),title:"New Category",sub:"",icon:"▦",headers:["Unit"],rows:[]});
  if(a=="up"&&ci>0)D.categories.splice(ci-1,0,D.categories.splice(ci,1)[0]);
  if(a=="down"&&ci<D.categories.length-1)D.categories.splice(ci+1,0,D.categories.splice(ci,1)[0]);
  render();
});
document.getElementById("save").onclick=function(){
  var m=document.getElementById("msg");m.textContent="Saving...";
  fetch("admin.php?save",{method:"POST",headers:{"X-Token":T},body:JSON.stringify(D)})
  .then(function(r){return r.json()}).then(function(j){m.textContent=j.ok?"Saved - website updated":"Save failed (file permission check karein)"})
  .catch(function(){m.textContent="Save failed"});
};
render();
</script></body></html>
