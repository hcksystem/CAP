function filter(filtro) {
    switch (filtro) {
        case 1:
            document.getElementById("ifilter").value = 1;
            break;
        case 2:
            document.getElementById("ifilter").value = 2;
            break;
        case 3:
            document.getElementById("ifilter").value = 3;
            break;
    }
}

function filtroHuella(huella) {
    switch (huella) {
        case 1:
            document.getElementById("ifilterH").value = 1;
            break;
        case 2:
            document.getElementById("ifilterH").value = 2;
            break;
    }
}