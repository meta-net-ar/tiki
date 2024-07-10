function tiki_load_embeddable_content(div_id, url) {
    let req = new XMLHttpRequest();
    req.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById(div_id).innerHTML = this.responseText;
        }
    };
    req.open("GET", url);
    req.send();
}
