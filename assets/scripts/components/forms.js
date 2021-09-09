const passwordButtons = document.querySelectorAll('.password-field-toggler');
const checkboxElements = document.querySelectorAll('.checkbox-element');

const togglePasswordField = (event) => {
    const { target } = event;
    if (target.classList.contains("fa-eye")) {
        target.classList.remove("fa-eye");
        target.classList.add("fa-eye-slash");
        target.nextElementSibling.type = "password";
    } else {
        target.classList.remove("fa-eye-slash");
        target.classList.add("fa-eye");
        target.nextElementSibling.type = "text";
    }
}

const toggleCheckbox = (event) => {
    const node = event.target;
    switch(node.nodeName.toLowerCase()) {
        case "label":
            const div = node.parentElement;
            var checkbox = div.querySelector("input[type=checkbox]");
            checkbox.checked = !checkbox.checked;
            break;
        case "div":
            var checkbox = node.querySelector("input[type=checkbox]");
            checkbox.checked = !checkbox.checked;
            break;
    }
}

passwordButtons.forEach(button => {
    button.addEventListener('click', togglePasswordField);
});

checkboxElements.forEach(checkboxDiv => {
    checkboxDiv.addEventListener('click', toggleCheckbox);
});