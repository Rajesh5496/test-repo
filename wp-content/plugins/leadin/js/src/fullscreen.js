import { domElements } from './constants/selectors';

export default function enterFullScreen() {
  jQuery(domElements.iframe).addClass('leadin-iframe-fullscreen');
}

export function exitFullScreen() {
  jQuery(domElements.iframe).removeClass('leadin-iframe-fullscreen');
}
