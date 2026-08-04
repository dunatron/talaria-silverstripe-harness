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
					<p class="warn">Not configured — set <code>TALARIA_DSN</code> and <code>TALARIA_API_KEY</code> in <code>.env</code>, then <code>ddev sake dev/build flush=1</code>.</p>
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

		<section class="talaria-actions">
			<h2>Test actions</h2>
			<p>Each button fires a different logging path, flushes the queue, and redirects back here. Check your Talaria dashboard for the event — exception tests should show <code>platform: php</code> and structured <code>exception.values[].stacktrace.frames</code>.</p>

			<ul class="talaria-grid">
				<% loop $TestCases %>
					<li class="talaria-card kind-$Kind">
						<a class="talaria-btn" href="$Href">$Title</a>
						<p>$Description</p>
					</li>
				<% end_loop %>
			</ul>
		</section>
	</article>
</div>
