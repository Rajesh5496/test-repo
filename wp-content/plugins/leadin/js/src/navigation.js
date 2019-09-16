import Raven from './lib/Raven';
import { domElements } from './constants/selectors';
import { changeRoute } from './api/hubspotPluginApi';
import { validAppRoutes } from './constants/routes';

export function initNavigation() {
  function setSelectedMenuItem() {
    jQuery(domElements.subMenuButtons).removeClass('current');
    const pageParam = window.location.search.match(/\?page=leadin_?\w*/)[0]; // filter page query param
    const selectedElement = jQuery(`a[href="admin.php${pageParam}"]`);
    selectedElement.parent().addClass('current');
  }

  function handleNavigation() {
    let appRoute = window.location.search.match(/page=leadin_?(\w*)/)[1];

    // prefix route with /
    if (appRoute) {
      appRoute = `/${appRoute}`;
    }

    changeRoute(appRoute);
    setSelectedMenuItem();
  }

  function handleClick() {
    // Don't interrupt modifier keys
    if (event.metaKey || event.altKey || event.shiftKey) {
      return;
    }
    window.history.pushState(null, null, jQuery(this).attr('href'));
    handleNavigation();
    event.preventDefault();
  }

  // Browser back and forward events navigation
  window.addEventListener('popstate', handleNavigation);

  // Menu Navigation
  jQuery(domElements.spaNavigationButtons).click(Raven.wrap(handleClick));
}

// Given a route like "/settings/forms", parse it into "?page=leadin_settings&leadin_route[0]=forms"
export function syncRoute(path = '') {
  const routes = path.split('/');

  while (routes[0] === '') {
    routes.shift();
  }

  let appRoute = '';

  if (validAppRoutes.includes(routes[0])) {
    appRoute = `_${routes[0]}`;
    routes.shift();
  }

  const queryParamsRoutes = routes.reduce((acc, route, index) => {
    return `${acc}&${encodeURIComponent(`leadin_route[${index}]`)}=${route}`;
  }, '');

  window.history.replaceState(
    null,
    null,
    `?page=leadin${appRoute}${queryParamsRoutes}`
  );
}

export function disableNavigation() {
  jQuery(domElements.allMenuButtons).off('click');
}
