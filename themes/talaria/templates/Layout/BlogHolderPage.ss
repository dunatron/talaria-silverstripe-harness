<div class="content-container">
	<article>
		<h1>$Title</h1>
		<% if $Content %>
			<div class="content">$Content</div>
		<% end_if %>

		<div
			id="blog-app"
			class="blog-app"
			data-view="list"
			data-props="$BlogPropsJSON.RAW"
			data-talaria-config="$TalariaConfigJSON.RAW"
		>
			<p class="blog-app__loading">Loading posts…</p>
		</div>
	</article>
</div>
