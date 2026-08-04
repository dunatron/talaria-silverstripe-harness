/**
 * Browser SDK harness UI.
 * Uses only window.Talaria / window.__TALARIA_CONFIG__ injected by the
 * Silverstripe module (FrontendExtension → RequirementsHelper). Does not
 * load or init @newtalaria/browser itself.
 */
(function () {
	'use strict';

	var SOURCE = 'talaria_ss_test';

	function onReady(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function baseTags(root, operation) {
		return {
			source: SOURCE,
			entity: root.getAttribute('data-entity') || 'TalariaBrowserPage',
			operation: operation,
		};
	}

	function log(el, message) {
		var line = '[' + new Date().toISOString().slice(11, 19) + '] ' + message;
		el.textContent = el.textContent ? el.textContent + '\n' + line : line;
		el.scrollTop = el.scrollHeight;
	}

	function shippedClientReady() {
		return !!(
			window.__TALARIA_CONFIG__ &&
			window.Talaria &&
			typeof window.Talaria.captureException === 'function' &&
			typeof window.Talaria.captureMessage === 'function'
		);
	}

	function setButtonsEnabled(list, enabled) {
		for (var i = 0; i < list.length; i++) {
			list[i].disabled = !enabled;
		}
	}

	function createCard(title, description, kind, onClick) {
		var li = document.createElement('li');
		li.className = 'talaria-card kind-' + kind;

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'talaria-btn';
		btn.textContent = title;
		btn.addEventListener('click', onClick);

		var p = document.createElement('p');
		p.textContent = description;

		li.appendChild(btn);
		li.appendChild(p);
		return { li: li, btn: btn };
	}

	function throwUncaught(factory) {
		// Defer so the click handler can finish and log before the throw.
		setTimeout(function () {
			throw factory();
		}, 0);
	}

	function mount(root) {
		var serverErrorUrl = root.getAttribute('data-server-error-url') || '';
		var ready = shippedClientReady();

		var banner = document.createElement('div');
		banner.className = 'talaria-sdk-banner ' + (ready ? 'ok' : 'warn');
		banner.textContent = ready
			? 'Shipped browser client ready (window.Talaria + __TALARIA_CONFIG__).'
			: 'Shipped browser client missing — FrontendExtension / RequirementsHelper did not expose window.Talaria. Check TALARIA_DSN, TALARIA_API_KEY, and flush.';

		var logEl = document.createElement('div');
		logEl.className = 'talaria-log';
		logEl.setAttribute('aria-live', 'polite');
		logEl.textContent = ready ? 'Ready.' : 'Capture buttons disabled until the module inject succeeds.';

		var grid = document.createElement('ul');
		grid.className = 'talaria-grid';

		var buttons = [];

		function addCase(title, description, kind, operation, handler) {
			var card = createCard(title, description, kind, function () {
				log(logEl, 'Fired: ' + operation);
				handler();
			});
			buttons.push(card.btn);
			grid.appendChild(card.li);
		}

		addCase(
			'Uncaught Error',
			'setTimeout throw new Error — window.onerror auto-capture',
			'exception',
			'uncaughtError',
			function () {
				throwUncaught(function () {
					return new Error('Talaria browser harness: uncaught Error');
				});
			}
		);

		addCase(
			'Uncaught TypeError',
			'setTimeout throw new TypeError — typed exception',
			'exception',
			'uncaughtTypeError',
			function () {
				throwUncaught(function () {
					return new TypeError('Talaria browser harness: uncaught TypeError');
				});
			}
		);

		addCase(
			'Uncaught ReferenceError',
			'setTimeout throw new ReferenceError — typed exception',
			'exception',
			'uncaughtReferenceError',
			function () {
				throwUncaught(function () {
					return new ReferenceError('Talaria browser harness: uncaught ReferenceError');
				});
			}
		);

		addCase(
			'Unhandled rejection',
			'Promise.reject with no catch — unhandledrejection',
			'exception',
			'unhandledRejection',
			function () {
				Promise.reject(new Error('Talaria browser harness: unhandled rejection'));
			}
		);

		addCase(
			'Handled captureException',
			'try/catch + window.Talaria.captureException + flush',
			'exception',
			'captureException',
			function () {
				if (!shippedClientReady()) {
					log(logEl, 'Skipped: window.Talaria unavailable');
					return;
				}
				try {
					throw new Error('Talaria browser harness: handled captureException');
				} catch (err) {
					window.Talaria.captureException(err, {
						tags: baseTags(root, 'captureException'),
						extra: { harness: 'captureException' },
					})
						.then(function () {
							return window.Talaria.flush();
						})
						.then(function () {
							log(logEl, 'captureException flushed');
						})
						.catch(function (e) {
							log(logEl, 'captureException failed: ' + (e && e.message ? e.message : e));
						});
				}
			}
		);

		addCase(
			'captureMessage (warning)',
			'window.Talaria.captureMessage at warning + flush',
			'message',
			'captureMessage',
			function () {
				if (!shippedClientReady()) {
					log(logEl, 'Skipped: window.Talaria unavailable');
					return;
				}
				window.Talaria.captureMessage(
					'Talaria browser harness: captureMessage warning',
					'warning',
					{
						tags: baseTags(root, 'captureMessage'),
						extra: { harness: 'captureMessage' },
					}
				)
					.then(function () {
						return window.Talaria.flush();
					})
					.then(function () {
						log(logEl, 'captureMessage flushed');
					})
					.catch(function (e) {
						log(logEl, 'captureMessage failed: ' + (e && e.message ? e.message : e));
					});
			}
		);

		addCase(
			'Failed fetch 500',
			'Same-origin fetch to serverError — HTTP promotion',
			'network',
			'failedFetch',
			function () {
				if (!serverErrorUrl) {
					log(logEl, 'Skipped: missing data-server-error-url');
					return;
				}
				fetch(serverErrorUrl, { method: 'GET', credentials: 'same-origin' })
					.then(function (res) {
						log(logEl, 'fetch completed status=' + res.status);
					})
					.catch(function (e) {
						log(logEl, 'fetch error: ' + (e && e.message ? e.message : e));
					});
			}
		);

		addCase(
			'Failed XHR 500',
			'Same-origin XHR to serverError — XHR transport path',
			'network',
			'failedXhr',
			function () {
				if (!serverErrorUrl) {
					log(logEl, 'Skipped: missing data-server-error-url');
					return;
				}
				var xhr = new XMLHttpRequest();
				xhr.open('GET', serverErrorUrl, true);
				xhr.onload = function () {
					log(logEl, 'XHR completed status=' + xhr.status);
				};
				xhr.onerror = function () {
					log(logEl, 'XHR network error');
				};
				xhr.send();
			}
		);

		setButtonsEnabled(buttons, ready);

		root.appendChild(banner);
		root.appendChild(logEl);
		root.appendChild(grid);

		// Module inject is deferred; retry briefly if DOMContentLoaded raced the import.
		if (!ready) {
			var attempts = 0;
			var timer = setInterval(function () {
				attempts += 1;
				if (shippedClientReady()) {
					clearInterval(timer);
					banner.className = 'talaria-sdk-banner ok';
					banner.textContent =
						'Shipped browser client ready (window.Talaria + __TALARIA_CONFIG__).';
					log(logEl, 'Client became available after wait.');
					setButtonsEnabled(buttons, true);
					return;
				}
				if (attempts >= 20) {
					clearInterval(timer);
				}
			}, 100);
		}
	}

	onReady(function () {
		var root = document.getElementById('talaria-browser-harness');
		if (!root) {
			return;
		}
		mount(root);
	});
})();
