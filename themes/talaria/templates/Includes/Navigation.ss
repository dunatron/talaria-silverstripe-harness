<div
	class="nav-palette"
	id="site-nav-palette"
	data-nav-palette
	hidden
	role="dialog"
	aria-modal="true"
	aria-label="Site navigation"
>
	<div class="nav-palette__backdrop" data-nav-close tabindex="-1"></div>
	<div class="nav-palette__panel" role="document">
		<div class="nav-palette__search">
			<svg class="nav-palette__search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
				<circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.75"/>
				<path d="M16.2 16.2L20 20" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
			</svg>
			<input
				type="search"
				class="nav-palette__input"
				data-nav-filter
				placeholder="Jump to demo or action…"
				autocomplete="off"
				spellcheck="false"
				aria-label="Filter navigation"
			>
		</div>

		<div class="nav-palette__body" data-nav-list>
			<% loop $NavigationGroups %>
				<section class="nav-palette__group" data-nav-group>
					<h2 class="nav-palette__group-title">$Title</h2>
					<ul class="nav-palette__items">
						<% loop $Items %>
							<li class="nav-palette__item<% if $IsCurrent %> is-current<% end_if %>" data-nav-item data-search="$SearchText.XML">
								<a class="nav-palette__link" href="$Link" data-nav-link>
									<span class="nav-palette__icon" data-icon="$Icon.XML" aria-hidden="true"></span>
									<span class="nav-palette__text">
										<span class="nav-palette__title">$Title.XML</span>
										<% if $Subtitle %>
											<span class="nav-palette__subtitle">$Subtitle.XML</span>
										<% end_if %>
									</span>
								</a>
							</li>
						<% end_loop %>
					</ul>
				</section>
			<% end_loop %>
			<p class="nav-palette__empty" data-nav-empty hidden>No matches</p>
		</div>
	</div>
</div>
