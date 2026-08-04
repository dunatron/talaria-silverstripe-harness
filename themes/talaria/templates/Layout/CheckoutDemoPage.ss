<% include SideBar %>
<div class="content-container talaria-harness">
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
