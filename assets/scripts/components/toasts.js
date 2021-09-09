import { Toast } from "bootstrap";

window.addEventListener("DOMContentLoaded", (e) => {
    var toastElList = [].slice.call(document.querySelectorAll(".toast"));
    var toastList = toastElList.map(function (toastEl) {
        return new Toast(toastEl);
    });
    toastList.map((element) => {
            element.show();
    });
});
