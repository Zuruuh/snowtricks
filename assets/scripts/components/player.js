import Plyr from "plyr";
// const players = document.querySelectorAll(".plyr__video-embed");
const players = Array.from(document.querySelectorAll(".plyr__video-embed")).map(
  (player) => new Plyr(player)
);
