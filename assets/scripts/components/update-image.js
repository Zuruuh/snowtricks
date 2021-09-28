const inputs = document.querySelectorAll(
  "input[type='file']:not(input[multiple='multiple'])"
);

inputs.forEach((input, index) => {
  input.onchange = () => {
    const { files } = input;
    const image = document.querySelector(input.getAttribute("data-preview"));
    if (files && image) {
      image.src = URL.createObjectURL(files[index]);
    }
  };
});
