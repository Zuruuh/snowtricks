/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.scss";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";

// assets imports

// bootstrap imports
import { Tooltip, Toast, Popover, Modal } from "bootstrap";
import Swiper, { Navigation, Pagination } from "swiper";

Swiper.use([Navigation, Pagination]);
const swiper = new Swiper(".swiper", {
  direction: "horizontal",
  loop: true,
  slidesPerView: "auto",
  spaceBetween: 10,
  allowTouchMove: false,

  pagination: {
    el: ".swiper-pagination",
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});

// start the Stimulus application
import "./bootstrap";
