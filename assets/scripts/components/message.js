import { Popover } from "bootstrap";

new Popover(document.body, {
  selector: ".message-actions",
  html: true,
  template: `
  <div class="popover message-action-popover" role="tooltip">
    <div class="popover-arrow"></div>
    <h3 class="popover-header"></h3>
    <div class="popover-body"></div>
  </div>`,
});
