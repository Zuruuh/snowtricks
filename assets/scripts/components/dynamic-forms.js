const defaultFields = document.querySelectorAll(".dynamic-field");
const fieldsContainer = document.querySelector("#video-fields");
const defaultData = JSON.parse(fieldsContainer.getAttribute("data-default-values"));
const addBtn = document.querySelector("#add-video-button");

fieldsContainer.removeAttribute("data-default-values");
let index = defaultFields.length;
const MAX_FIELDS = 3;

defaultFields.forEach((field) => {
  const btn = field.querySelector("button");
  btn.onclick = (event) => removeField(event, btn.getAttribute("data-field"));
});

defaultData.forEach(savedField => {
  addField(savedField.url, savedField.provider)
})

addBtn.onclick = (event) => {
  event.preventDefault();
  addField();
};

function addField(videoId = "", provider = "") {
  let defaultValue = ""
  if (videoId && provider) {
    defaultValue = getFullLink(videoId, provider);
  }
  const fields = document.querySelectorAll(".dynamic-field");
  if (fields.length < MAX_FIELDS) {
    // * Create new field and append to container
    const li = document.createElement("li");
    li.classList.add("video-item");
    li.setAttribute("data-field-id", index);

    const div = document.createElement("div");
    div.classList.add("dynamic-field");
    const input = document.createElement("input");

    input.placeholder = "https://www.youtube.com/watch?v=...";
    input.classList.add("me-2");
    input.type = "text";
    input.value = defaultValue;
    input.name = `trick_form[videos][${index}]`;
    input.id = `trick_form_videos_${index}`;

    const button = document.createElement("button");
    button.classList.add("btn");
    button.classList.add("btn-danger");
    button.setAttribute("data-field", index);
    button.onclick = (event) =>
      removeField(event, button.getAttribute("data-field"));

    const icon = document.createElement("i");
    icon.classList.add("fas");
    icon.classList.add("fa-minus-square");

    button.appendChild(icon);
    div.appendChild(input);
    div.appendChild(button);
    li.appendChild(div);

    fieldsContainer.appendChild(li);
    input.focus();
  }
  index++;
  if (fields.length + 1 === MAX_FIELDS) {
    addBtn.disabled = true;
  }
}


function removeField(event, id) {
  event.preventDefault();
  const fields = document.querySelectorAll(".video-item");
  fields.forEach((field) => {
    if (field.getAttribute("data-field-id") === id) {
      addBtn.disabled = false;
      field.remove();
    }
  });
}

function getFullLink(id, provider) {
  let url = "";
  switch(provider) {
    case "youtube":
      url = "https://www.youtube.com/watch?v="
      break;
    case "vimeo":
      url = "hhttps://vimeo.com/"
      break;
  }
  return url + id;
}