const selects = document.querySelectorAll(".form-select");

selects.forEach((select) => {
  const defaultSelectValue = select.getAttribute("data-default");
  if (defaultSelectValue) {
    select.firstElementChild.innerText = defaultSelectValue;
    select.removeAttribute("data-default");
  }
});
