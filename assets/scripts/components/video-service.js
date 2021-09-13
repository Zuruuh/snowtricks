import { toggleCheckbox } from "./forms";

const tricksVideoElement = document.getElementById("trick-videos");
const initialVideos = JSON.parse(
  tricksVideoElement.getAttribute("data-initial-values").trim()
);

// // /TODO Parse initialVideos & add content to page
// TODO Refactor code to use OOP

const removeVideoButton = document.getElementById("trick-videos-remove");
const addVideoButton = document.getElementById("trick-videos-add");

const tricksVideoScrollable = document.getElementById(
  "tricks-videos-scrollable"
);
const videoField = document.getElementById("trick_videos");

const youtubeLinkTemplate = `https://youtube.com/embed/$`;
const dailymotionLinkTemplate = `https://www.dailymotion.com/embed/video/$`;
const vimeoLinkTemplate = `https://player.vimeo.com/video/$`;

// <div class="col-md-8 card p-2 " id="trick-video-$">
const videoTemplate = `
    <p style="margin: 0;">Choose your video provider</p>
    <div class="d-flex align-items-center">
        <div class="video-service check-element">
            <input type="radio" name="video-$-service" value="youtube" checked>
            <label for="youtube">Youtube</label>
        </div>
        <div class="video-service check-element">
            <input type="radio" name="video-$-service" value="dailymotion">
            <label for="dailymotion">Dailymotion</label>
        </div>
        <div class="video-service check-element">
            <input type="radio" name="video-$-service" value="vimeo">
            <label for="vimeo">Vimeo</label>
        </div>
    </div>
    <div>
        <input type="text" data-index="$" id="video-$-id" name="video-$-id" maxlength="16" placeholder="Enter your video id">
        <label for="video-$-id">Enter an id (ex: https://youtu.be/<span class="highlighted">dQw4w9WgXcQ</span>)</label>
    </div>
    <iframe id="video-player-$" style="width:100%;height:100%" src=""></iframe>
`;

let index = 0;

const updateIndex = (action = "add") => {
  if (action === "add") {
    if (index > 3) {
      addVideoButton.disabled = true;
      index--;
    } else {
      removeVideoButton.disabled = false;
      addVideoButton.disabled = index === 3;
      return true;
    }
  } else if (action === "remove") {
    if (index < 0) {
      removeVideoButton.disabled = true;
      index++;
    } else {
      addVideoButton.disabled = false;
      removeVideoButton.disabled = index === 0;
      return true;
    }
  }
};

let timeout;

const updateFrame = (id, value, iframe, i = index, to = false) => {
  clearTimeout(timeout);
  const update = () => {
    let frameTemplate;
    switch (value) {
      case "youtube":
        frameTemplate = youtubeLinkTemplate;
        break;
      case "dailymotion":
        frameTemplate = dailymotionLinkTemplate;
        break;
      case "vimeo":
        frameTemplate = vimeoLinkTemplate;
        break;
      default:
        frameTemplate = "about:blank";
        break;
    }

    const link = frameTemplate.replace("$", id);
    let content = JSON.parse(videoField.value);

    content[i - 1] = {};
    content[i - 1].id = id;
    content[i - 1].service = value;

    if (id === "" || id === null) content.splice(i, 1);
    if (value === "" || id === null) content[videoIndex].service = "youtube";
    videoField.value = JSON.stringify(content);

    iframe.src = "about:blank";
    iframe.src = link;
  };
  if (to) {
    update();
  } else {
    timeout = setTimeout(update, 100);
  }
};

const addVideo = (service, id, event) => {
  if (event) {
    event.preventDefault();
  }
  index++;
  if (updateIndex("add")) {
    const div = document.createElement("div");
    const template = videoTemplate.replaceAll("$", index);
    div.classList.add("col-md-8");
    div.classList.add("card");
    div.classList.add("p-2");
    div.innerHTML = template;
    div.setAttribute("id", index);

    const input = div.querySelector(`#video-${index}-id`);
    const iframe = div.querySelector(`#video-player-${index}`);
    const radiosElements = div.querySelectorAll(".check-element");

    input.value = id;
    radiosElements.forEach((radio) => {
      const radioElement = radio.querySelector("input[type='radio']");
      if (radio.querySelector("input").value === service) {
        radio.checked = true;
        updateFrame(
          input.value,
          service,
          iframe,
          input.getAttribute("data-index"),
          true
        );
      }
      radio.addEventListener("click", (event) => {
        toggleCheckbox(event);
        updateFrame(
          input.value,
          radioElement.value,
          iframe,
          input.getAttribute("data-index")
        );
      });
    });
    input.addEventListener("input", (event) => {
      const value = div.querySelector("input[type='radio']:checked");
      updateFrame(
        event.target.value,
        value.value,
        iframe,
        event.target.getAttribute("data-index")
      );
    });
    tricksVideoScrollable.appendChild(div);
  }
};

const removeVideo = (event) => {
  event.preventDefault();
  index--;
  if (updateIndex("remove")) {
    const last = tricksVideoScrollable.lastChild;
    tricksVideoScrollable.removeChild(last);
    let content = JSON.parse(videoField.value);
    content.splice(index, 1);
    videoField.value = JSON.stringify(content);
  }
};
if (initialVideos.length > 0) {
  console.log(initialVideos);
  initialVideos.forEach((video) => {
    if (video.id && video.service) {
      addVideo(video.service, video.id);
    }
  });
}

addVideoButton.addEventListener("click", (event) => {
  addVideo("", "", event);
});
removeVideoButton.addEventListener("click", (event) => {
  removeVideo(event);
});
