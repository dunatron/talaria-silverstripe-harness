/**
 * Plain exception throws — no Talaria client calls.
 * Relies on the shipped SDK's window.onerror / unhandledrejection handlers.
 */
(function () {
	'use strict';

	function onReady(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function throwLater(factory) {
		setTimeout(function () {
			throw factory();
		}, 0);
	}

	function addButton(list, label, onClick) {
		var li = document.createElement('li');
		li.className = 'talaria-card kind-exception';

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'talaria-btn';
		btn.textContent = label;
		btn.addEventListener('click', onClick);

		li.appendChild(btn);
		list.appendChild(li);
	}

	function mount(root) {
		var grid = document.createElement('ul');
		grid.className = 'talaria-grid';

		addButton(grid, 'Throw Error', function () {
			throwLater(function () {
				return new Error('Talaria throws harness: Error');
			});
		});

		addButton(grid, 'Throw TypeError', function () {
			throwLater(function () {
				return new TypeError('Talaria throws harness: TypeError');
			});
		});

		addButton(grid, 'Throw ReferenceError', function () {
			throwLater(function () {
				return new ReferenceError('Talaria throws harness: ReferenceError');
			});
		});

		addButton(grid, 'Unhandled rejection', function () {
			Promise.reject(new Error('Talaria throws harness: unhandled rejection'));
		});

		root.appendChild(grid);
	}

	onReady(function () {
		var root = document.getElementById('talaria-browser-throws');
		if (!root) {
			return;
		}
		mount(root);
	});
})();
