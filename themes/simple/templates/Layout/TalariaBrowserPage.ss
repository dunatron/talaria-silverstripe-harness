<% include SideBar %>
<div class="content-container unit size3of4 lastUnit talaria-harness">
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

<style>
.talaria-harness .talaria-status {
	margin: 1.5rem 0;
	padding: 1rem;
	background: #f7f7f5;
	border-radius: 8px;
}
.talaria-harness .talaria-status .ok { color: #2e7d32; font-weight: 600; }
.talaria-harness .talaria-status .warn { color: #c62828; font-weight: 600; }
.talaria-harness .talaria-actions {
	margin: 1.5rem 0;
}
.talaria-harness .talaria-throws-section {
	margin-top: 2.5rem;
	padding-top: 2rem;
	border-top: 2px solid #ccc;
}
.talaria-harness .talaria-throws-section h2 {
	margin-top: 0;
}
.talaria-harness .talaria-hint {
	margin: 0 0 1rem;
	font-size: 0.95rem;
	color: #444;
	line-height: 1.4;
}
.talaria-harness .talaria-sdk-banner {
	margin: 0 0 1rem;
	padding: 0.75rem 1rem;
	border-radius: 6px;
	font-size: 0.9rem;
	line-height: 1.35;
}
.talaria-harness .talaria-sdk-banner.ok {
	background: #e8f5e9;
	border: 1px solid #81c784;
	color: #1b5e20;
}
.talaria-harness .talaria-sdk-banner.warn {
	background: #ffebee;
	border: 1px solid #ef9a9a;
	color: #b71c1c;
}
.talaria-harness .talaria-log {
	margin: 0 0 1rem;
	padding: 0.75rem 1rem;
	background: #fafafa;
	border: 1px solid #e0e0e0;
	border-radius: 6px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
	font-size: 0.8rem;
	max-height: 10rem;
	overflow: auto;
	white-space: pre-wrap;
}
.talaria-harness .talaria-grid {
	list-style: none;
	margin: 0;
	padding: 0;
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
	gap: 1rem;
}
.talaria-harness .talaria-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 1rem;
	background: #fff;
}
.talaria-harness .talaria-card.kind-exception { border-color: #ef9a9a; }
.talaria-harness .talaria-card.kind-message { border-color: #90caf9; }
.talaria-harness .talaria-card.kind-network { border-color: #ce93d8; }
.talaria-harness .talaria-btn {
	display: inline-block;
	margin-bottom: 0.5rem;
	padding: 0.5rem 0.85rem;
	background: #1a1a1a;
	color: #fff !important;
	text-decoration: none !important;
	border: none;
	border-radius: 4px;
	font-weight: 600;
	font-size: 0.95rem;
	cursor: pointer;
}
.talaria-harness .talaria-btn:hover:not(:disabled) { background: #333; }
.talaria-harness .talaria-btn:disabled {
	opacity: 0.45;
	cursor: not-allowed;
}
.talaria-harness .talaria-card p {
	margin: 0;
	font-size: 0.9rem;
	color: #555;
	line-height: 1.35;
}
</style>
