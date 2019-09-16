import EventBus from './EventBus';
import { log } from '../utils';
import { domElements } from '../constants/selectors';
import { hubspotBaseUrl } from '../constants/leadinConfig';
import Raven from './Raven';

const eventBus = new EventBus();
const callbacks = [];
const postMessageBuffer = [];
let interframeReady = false;

function postMessageObject(message) {
  log('Posting message');
  log(JSON.stringify(message));
  jQuery(domElements.iframe)[0].contentWindow.postMessage(
    JSON.stringify(message),
    hubspotBaseUrl
  );
}

function reply(message, response) {
  if (!response) {
    response = 'Message Received';
  }
  const newMessage = Object.assign({}, message);
  newMessage.response = response;
  postMessageObject(newMessage);
}

function handleResponse(message) {
  callbacks[message._leadinCallbackId - 1](message.response);
}

function handleMessage(message) {
  log('Received message');
  log(JSON.stringify(message));

  if (message.hasOwnProperty('response') && message._leadinCallbackId) {
    handleResponse(message);
  } else {
    Object.keys(message).forEach(key => {
      eventBus.trigger(key, [message[key], reply.bind(null, message)]);
    });
  }
}

function handleMessageEvent(event) {
  if (event.origin === hubspotBaseUrl) {
    try {
      const data = JSON.parse(event.data);
      handleMessage(data);
    } catch (e) {
      // Error in parsing message
    }
  }
}

function setInterframeReady() {
  interframeReady = true;
  while (postMessageBuffer.length > 0) {
    postMessageObject(postMessageBuffer.pop());
  }
}

export function postMessage(
  key,
  payload,
  onResponse,
  onTimeout,
  timeout = 500
) {
  const timeoutCallback = function() {
    const errorMessage = `LeadinWordpressPlugin postMessage response timeout on message key: ${key}`;
    log(errorMessage);
    Raven.captureMessage(errorMessage);
    if (onTimeout) {
      onTimeout();
    }
  };

  const timeoutId = setTimeout(Raven.wrap(timeoutCallback), timeout);

  const message = {};
  message[key] = payload;
  message._leadinCallbackId = callbacks.push((...args) => {
    clearTimeout(timeoutId);
    onResponse(...args);
  });
  if (interframeReady) {
    postMessageObject(message);
  } else {
    postMessageBuffer.push(message);
  }
}

export function onMessage(key, callback) {
  eventBus.on(key, (...args) => {
    callback.apply(null, args.slice(1));
  });
}

export function initInterframe() {
  onMessage('interframe_ready', (message, sendReply) => {
    sendReply();
    setInterframeReady();
  });
  window.addEventListener('message', handleMessageEvent);
}
