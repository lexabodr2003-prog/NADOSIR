function loadOctFonts() {
    let cssId = 'octFonts';
    let octFonts = localStorage.getItem('octFonts');
    let fontFamily = document.documentElement.getAttribute('data-oct-fonts');

    if(!octFonts && !document.getElementById(cssId)) {

        let head  = document.getElementsByTagName('head')[0];
        let link  = document.createElement('link');
        link.id   = cssId;
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = location.protocol + '//' + location.host + `/catalog/view/theme/oct_deals/stylesheet/oct-fonts-${fontFamily}.css`;
        link.media = 'all';
        head.appendChild(link);
        localStorage.setItem('octFonts', '1');

    }
}

loadOctFonts();
