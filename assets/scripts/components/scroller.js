// TODO Refactor to OOP
// const scrollables = document.querySelectorAll(".scrollable");

// const youtubeLinkTemplate = `https://youtube.com/embed/$`;
// const dailymotionLinkTemplate = `https://www.dailymotion.com/embed/video/$`;
// const vimeoLinkTemplate = `https://player.vimeo.com/video/$`;

// const createButton = (type, i) => {
//   const button = document.createElement("button");
//   button.setAttribute("id", `carousel-${i}-${type}-button`);
//   button.classList.add("carousel-button");
//   button.classList.add(`carousel-button-${type}`);

//   const icon = document.createElement("i");
//   icon.classList.add("fas");
//   icon.classList.add(`fa-caret-${type}`);

//   button.appendChild(icon);
//   return button;
// };

// const createVideoElement = (video, service, index, i) => {
//   const videoElement = document.createElement("iframe");
//   let template;
//   switch (service) {
//     case "youtube":
//       template = youtubeLinkTemplate;
//       break;
//     case "dailymotion":
//       template = dailymotionLinkTemplate;
//       break;
//     case "vimeo":
//       template = vimeoLinkTemplate;
//       break;
//   }

//   template = template.replace("$", video);

//   videoElement.setAttribute("src", template);
//   const div = document.createElement("div");

//   div.setAttribute("id", `carousel-${i}-element-${index}`);
//   div.classList.add("carousel-element");
//   div.classList.add("carousel-video-element");
//   //   div.style.order = index;

//   div.appendChild(videoElement);

//   return div;
// };

// const createImageElement = (image, index, i) => {
//   const img = document.createElement("img");
//   img.setAttribute("src", image);
//   const div = document.createElement("div");
//   div.setAttribute("id", `carousel-${i}-element-${index}`);

//   div.classList.add("carousel-element");
//   div.classList.add("carousel-image-element");
//   div.appendChild(img);
//   //   div.style.order = index;

//   return div;
// };

// const createScrollable = (scrollable, i) => {
//   const videosData = JSON.parse(scrollable.getAttribute("data-videos"));
//   const imagesData = scrollable.getAttribute("data-images").split("|");

//   scrollable.removeAttribute("data-images");
//   scrollable.removeAttribute("data-videos");

//   const leftButton = createButton("left", i);
//   const rightButton = createButton("right", i);
//   scrollable.parentElement.appendChild(leftButton);
//   scrollable.parentElement.appendChild(rightButton);

//   const updateScrollable = (direction, items, innerScroller) => {
//     if (direction === "left") {
//       const last = items[items.length - 1];
//       items[items.length - 1].remove();
//       innerScroller.prepend(last);
//     } else if (direction === "right") {
//       const first = items[0];
//       items[0].remove();
//       innerScroller.appendChild(first);
//     }
//   };

//   const elementsContainer = document.createElement("div");
//   elementsContainer.classList.add("scrollable-inner");

//   let index = 1;

//   videosData.forEach((video) => {
//     elementsContainer.appendChild(
//       createVideoElement(video.id, video.service, index, i)
//     );
//     index++;
//   });

//   imagesData.forEach((image) => {
//     elementsContainer.appendChild(createImageElement(image, index, i));
//     index++;
//   });

//   scrollable.appendChild(elementsContainer);

//   let items = scrollable.querySelectorAll(`.carousel-element`);

//   leftButton.addEventListener("click", () => {
//     updateScrollable(
//       "left",
//       items[0].parentNode.querySelectorAll("div"),
//       items[0].parentNode
//     );
//   });

//   rightButton.addEventListener("click", () => {
//     updateScrollable(
//       "right",
//       items[0].parentNode.querySelectorAll("div"),
//       items[0].parentNode
//     );
//   });
// };

// scrollables.forEach((scrollable, i) => {
//   createScrollable(scrollable, i + 1);
// });
