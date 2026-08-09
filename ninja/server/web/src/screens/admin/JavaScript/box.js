var timeout = null; 
function box_nf(str, s) {
    document.getElementById('box-nf').style.display = 'block';
    document.getElementById('box-nf').innerText = str;
    box_nfstart(s);
}
function box_nfstart(s) {
    timeout = setTimeout(function(){
        s--;
        if (s == 0){
            document.getElementById('box-nf').style.display = 'none';
            document.getElementById('box-nf').innerText = '';
            clearTimeout(timeout);
            return false;
         }
        box_nfstart(s);
    }, 1000);
}