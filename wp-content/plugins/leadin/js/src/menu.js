import { hubspotBaseUrl, portalId, i18n } from './constants/leadinConfig';

function addMenuItem(text, href) {
  jQuery('#toplevel_page_leadin')
    .find('li')
    .last()
    .before(`<li><a href="${href}" target="_blank">${text}</a></li>`);
}

export function addExternalLinks() {
  const chatflowsUrl = `${hubspotBaseUrl}/chatflows/${portalId}`;
  const emailUrl = `${hubspotBaseUrl}/email/${portalId}`;
  addMenuItem(i18n.chatflows, chatflowsUrl);
  addMenuItem(i18n.email, emailUrl);
}
