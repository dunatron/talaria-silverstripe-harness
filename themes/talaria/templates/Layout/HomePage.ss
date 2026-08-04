<div class="home-page">
	<section class="home-hero">
		<p class="home-hero__brand">Talaria</p>
		<h1 class="home-hero__title">Silverstripe SDK harness</h1>
		<p class="home-hero__lead">
			A local Silverstripe 5 project for exercising <code>newtalaria/logging</code> and
			<code>@newtalaria/browser</code> against your Talaria project.
		</p>
		<div class="home-hero__actions">
			<% with $FeaturedDemo %>
				<a class="home-btn home-btn--primary" href="$Link">Open Bookstore</a>
			<% end_with %>
			<a class="home-btn home-btn--secondary" href="#demos">Browse demos</a>
		</div>
	</section>

	<section class="home-section" id="about">
		<h2 class="home-section__title">What this is</h2>
		<p class="home-section__lead">
			Use this site to send real events from PHP (and optionally the browser) into Talaria.
			Configure a project client key, run the demos, then inspect ingest in the dashboard.
		</p>

		<% with $ConfigStatus %>
			<div class="home-config<% if $Configured %> is-ok<% else %> is-warn<% end_if %>">
				<% if $Configured %>
					<p class="home-config__status">Credentials detected — events should ingest when the API is reachable.</p>
				<% else %>
					<p class="home-config__status">Credentials missing — set <code>TALARIA_DSN</code> and <code>TALARIA_API_KEY</code> (<code>tal_live_…</code>) in <code>.env</code>, then <code>ddev exec vendor/bin/sake dev/build flush=1</code>.</p>
				<% end_if %>
				<ul class="home-config__list">
					<li><span>DSN</span> $Dsn</li>
					<li><span>API key</span> $ApiKey</li>
					<li><span>Environment</span> $Environment</li>
					<li><span>Release</span> $Release</li>
					<li><span>Browser DSN</span> $BrowserDsn</li>
				</ul>
			</div>
		<% end_with %>
	</section>

	<section class="home-section" id="approaches">
		<h2 class="home-section__title">Ways to instrument</h2>
		<p class="home-section__lead">
			Pick the path that matches your call sites. They share the same project credentials and can coexist.
		</p>
		<ul class="home-approaches">
			<% loop $InstrumentationApproaches %>
				<li class="home-approach">
					<h3 class="home-approach__title">$Title</h3>
					<p class="home-approach__body">$Body</p>
					<p class="home-approach__example">See: $Example</p>
				</li>
			<% end_loop %>
		</ul>
	</section>

	<section class="home-section" id="demos">
		<h2 class="home-section__title">Demos</h2>
		<p class="home-section__lead">
			Each page is a focused exercise. Start with the Bookstore for the pattern to copy into application services.
		</p>

		<% with $FeaturedDemo %>
			<article class="home-featured">
				<div class="home-featured__meta">
					<% if $Badge %><span class="home-badge">$Badge</span><% end_if %>
					<h3 class="home-featured__title">$Title</h3>
					<p class="home-featured__summary">$Summary</p>
				</div>
				<p class="home-featured__detail">$Detail</p>
				<p class="home-featured__showcases"><strong>Showcases</strong> $Showcases</p>
				<p class="home-featured__filter"><strong>Dashboard filter</strong> <code>$FilterHint</code></p>
				<a class="home-btn home-btn--primary" href="$Link">Open Bookstore</a>
			</article>
		<% end_with %>

		<ul class="home-demo-list">
			<% loop $OtherDemos %>
				<li class="home-demo">
					<div class="home-demo__header">
						<h3 class="home-demo__title"><a href="$Link">$Title</a></h3>
						<p class="home-demo__summary">$Summary</p>
					</div>
					<p class="home-demo__detail">$Detail</p>
					<p class="home-demo__showcases"><strong>Showcases</strong> $Showcases</p>
					<p class="home-demo__filter"><strong>Filter</strong> <code>$FilterHint</code></p>
				</li>
			<% end_loop %>
		</ul>
	</section>

	<section class="home-section home-section--next" id="next">
		<h2 class="home-section__title">Next step</h2>
		<p class="home-section__lead">
			Confirm <code>.env</code>, flush config, open the Bookstore, run a checkout outcome, then filter the dashboard by
			<code>feature=bookstore</code>.
		</p>
		<% with $FeaturedDemo %>
			<a class="home-btn home-btn--primary" href="$Link">Go to Bookstore</a>
		<% end_with %>
	</section>
</div>
