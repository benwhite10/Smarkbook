function goToWorksheet(vid) {
    window.location.href = "viewWorksheet.php?id=" + vid;
}

// The function actually applying the offset
function offsetAnchor() {
    if(location.hash.length !== 0) {
        window.scrollTo(window.scrollX, window.scrollY - 200);
    }
}

window.addEventListener("hashchange", offsetAnchor);

window.setTimeout(offsetAnchor, 1);