<% include SideBar %>
<div class="content-container unit size3of4 lastUnit talaria-harness">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>

		<% if $FlashMessage %>
			<p class="talaria-flash">$FlashMessage</p>
		<% end_if %>

		<% with $ConfigStatus %>
			<section class="talaria-status">
				<h2>Talaria config</h2>
				<% if $Configured %>
					<p class="ok">Configured — events should ingest.</p>
				<% else %>
					<p class="warn">Not configured — set <code>TALARIA_DSN</code> and <code>TALARIA_API_KEY</code> in <code>.env</code>, then <code>ddev exec vendor/bin/sake dev/build flush=1</code>.</p>
				<% end_if %>
				<ul>
					<li><strong>DSN</strong>: $Dsn</li>
					<li><strong>API key</strong>: $ApiKey</li>
					<li><strong>Environment</strong>: $Environment</li>
					<li><strong>Release</strong>: $Release</li>
					<li><strong>Queue size</strong>: $QueueSize</li>
				</ul>
			</section>
		<% end_with %>

		<section class="talaria-practice">
			<h2>Best-practice pattern</h2>
			<ol>
				<li>Resolve the Injector <code>TalariaClient</code> (do not call <code>Talaria::init</code> again).</li>
				<li>At the start of the action: <code>setTags</code>, <code>setExtra</code>, <code>setUser</code> for this request.</li>
				<li>Instrument each workflow step with <code>captureMessage</code> / <code>captureException</code> and a <code>step</code> tag.</li>
				<li>Call <code>flush()</code> when the action finishes.</li>
			</ol>
			<p>The Injector client is process-shared, so each action re-applies setup at the top. Filter dashboard events by tag <code>entity=CheckoutDemoPage</code>.</p>
		</section>

		<section class="talaria-actions">
			<h2>Run a checkout</h2>
			<p>Each button bootstraps the client, steps through the flow, flushes, and redirects back here.</p>

			<ul class="talaria-grid">
				<% loop $WorkflowCases %>
					<li class="talaria-card kind-$Kind">
						<a class="talaria-btn" href="$Href">$Title</a>
						<p>$Description</p>
					</li>
				<% end_loop %>
			</ul>
		</section>
	</article>
</div>

<style>
.talaria-harness .talaria-flash {
	background: #e8f5e9;
	border: 1px solid #81c784;
	padding: 0.75rem 1rem;
	border-radius: 6px;
}
.talaria-harness .talaria-status,
.talaria-harness .talaria-practice {
	margin: 1.5rem 0;
	padding: 1rem;
	background: #f7f7f5;
	border-radius: 8px;
}
.talaria-harness .talaria-status .ok { color: #2e7d32; font-weight: 600; }
.talaria-harness .talaria-status .warn { color: #c62828; font-weight: 600; }
.talaria-harness .talaria-practice ol {
	margin: 0.5rem 0 1rem 1.25rem;
	padding: 0;
	line-height: 1.45;
}
.talaria-harness .talaria-practice p {
	margin: 0;
	color: #555;
	font-size: 0.95rem;
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
.talaria-harness .talaria-btn {
	display: inline-block;
	margin-bottom: 0.5rem;
	padding: 0.5rem 0.85rem;
	background: #1a1a1a;
	color: #fff !important;
	text-decoration: none !important;
	border-radius: 4px;
	font-weight: 600;
}
.talaria-harness .talaria-btn:hover { background: #333; }
.talaria-harness .talaria-card p {
	margin: 0;
	font-size: 0.9rem;
	color: #555;
	line-height: 1.35;
}
</style>
