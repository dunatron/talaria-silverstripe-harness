<% if $Menu(2) %>
	<aside class="site-sidebar">
		<nav class="secondary" aria-label="Section">
			<% with $Level(1) %>
				<h3>$MenuTitle</h3>
				<ul>
					<% include SidebarMenu %>
				</ul>
			<% end_with %>
		</nav>
	</aside>
<% end_if %>
