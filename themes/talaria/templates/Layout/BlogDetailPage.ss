<div class="content-container">
	<article>
		<% if $Content %>
			<div class="content visually-hidden">$Content</div>
		<% end_if %>

		<div
			id="blog-app"
			class="blog-app"
			data-view="detail"
			data-props="$BlogPropsJSON.RAW"
			data-talaria-config="$TalariaConfigJSON.RAW"
		>
			<p class="blog-app__loading">Loading post…</p>
		</div>
	</article>
</div>
