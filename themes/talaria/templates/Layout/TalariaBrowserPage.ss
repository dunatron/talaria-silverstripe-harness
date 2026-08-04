<% include SideBar %>
<div class="content-container talaria-harness">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>

		<% with $ConfigStatus %>
			<section class="talaria-status">
				<h2>Talaria config (PHP)</h2>
				<% if $Configured %>
					<p class="ok">Configured — browser SDK should inject when DSN/key are valid.</p>
				<% else %>
					<p class="warn">Not configured — set <code>TALARIA_DSN</code> and <code>TALARIA_API_KEY</code> in <code>.env</code>, then <code>ddev sake dev/build flush=1</code>.</p>
				<% end_if %>
				<ul>
					<li><strong>DSN (PHP)</strong>: $Dsn</li>
					<li><strong>DSN (browser)</strong>: $BrowserDsn</li>
					<li><strong>API key</strong>: $ApiKey</li>
					<li><strong>Environment</strong>: $Environment</li>
					<li><strong>Release</strong>: $Release</li>
					<li><strong>PHP queue size</strong>: $QueueSize</li>
				</ul>
			</section>
		<% end_with %>

		<section class="talaria-actions">
			<h2>1. Browser SDK API</h2>
			<p class="talaria-hint">
				Buttons are injected by <code>talaria-browser-harness.js</code> against the client shipped
				by the Silverstripe module (<code>FrontendExtension</code> → <code>window.Talaria</code>).
				Includes manual <code>captureException</code> / <code>captureMessage</code>, network demos,
				and auto-capture. Expect <code>platform: web</code> /
				<code>runtime: silverstripe-frontend</code>. Filter by tag
				<code>entity=TalariaBrowserPage</code>.
			</p>
			<div
				id="talaria-browser-harness"
				data-entity="TalariaBrowserPage"
				data-server-error-url="$Link('serverError')"
				data-status-url="$Link('status')"
			></div>
		</section>

		<section class="talaria-actions talaria-throws-section">
			<h2>2. Plain throws (no Talaria client)</h2>
			<p class="talaria-hint">
				Separate bundle <code>talaria-browser-throws.js</code> — only throws exceptions /
				rejects promises. No <code>window.Talaria</code> calls. Proves the shipped SDK’s
				<code>onerror</code> / <code>unhandledrejection</code> auto-capture still works for
				unrelated page scripts.
			</p>
			<div id="talaria-browser-throws"></div>
		</section>
	</article>
</div>
